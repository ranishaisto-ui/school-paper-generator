<?php
/**
 * Plugin Name: School Paper Generator
 * Plugin URI: https://yourwebsite.com/school-paper-generator
 * Description: Professional exam paper generator for schools with MCQ, Short, Long questions and school branding.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: school-paper-generator
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPG_VERSION', '1.0.0');
define('SPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPG_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SPG_TRIAL_DAYS', 30);
define('SPG_MAX_TRIAL_QUESTIONS', 50);

// Check if premium version exists
function spg_is_premium_active() {
    return apply_filters('spg_is_premium', false);
}

// Auto-loader for classes
spl_autoload_register(function ($class_name) {
    $prefix = 'SPG_';
    $base_dir = SPG_PLUGIN_DIR . 'includes/';
    
    if (strpos($class_name, $prefix) === 0) {
        $relative_class = substr($class_name, strlen($prefix));
        $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Include required files
require_once SPG_PLUGIN_DIR . 'includes/functions.php';
require_once SPG_PLUGIN_DIR . 'admin/admin-menu.php';

// Initialize plugin
class SchoolPaperGenerator {
    
    private static $instance = null;
    private $database;
    private $question_bank;
    private $paper_generator;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }
    
    private function load_dependencies() {
        $this->database = new SPG_Database_Handler();
        $this->question_bank = new SPG_Question_Bank();
        $this->paper_generator = new SPG_Paper_Generator();
        
        // Load premium placeholder if premium not active
        if (!spg_is_premium_active()) {
            require_once SPG_PLUGIN_DIR . 'includes/premium-features-placeholder.php';
        }
    }
    
    public function activate() {
        $this->database->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup temporary files
        $this->cleanup_temp_files();
        flush_rewrite_rules();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'school-paper-generator',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    public function init() {
        // Register shortcodes
        add_shortcode('spg_paper', array($this, 'paper_shortcode'));
        add_shortcode('spg_question_bank', array($this, 'question_bank_shortcode'));
        
        // Initialize AJAX handlers
        new SPG_Ajax_Handler();
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'school-paper-generator') !== false) {
            wp_enqueue_style('spg-admin-style', SPG_PLUGIN_URL . 'admin/assets/css/admin-style.css', array(), SPG_VERSION);
            wp_enqueue_style('spg-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
            wp_enqueue_style('spg-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            
            wp_enqueue_script('spg-admin-script', SPG_PLUGIN_URL . 'admin/assets/js/admin-script.js', 
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'), SPG_VERSION, true);
            wp_enqueue_script('spg-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
            wp_enqueue_script('spg-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
            
            wp_localize_script('spg-admin-script', 'spg_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spg_nonce'),
                'is_premium' => spg_is_premium_active(),
                'max_trial_questions' => SPG_MAX_TRIAL_QUESTIONS,
                'text' => array(
                    'confirm_delete' => __('Are you sure you want to delete this?', 'school-paper-generator'),
                    'saving' => __('Saving...', 'school-paper-generator'),
                    'generating' => __('Generating Paper...', 'school-paper-generator')
                )
            ));
        }
    }
    
    public function enqueue_public_assets() {
        if (is_page() && has_shortcode(get_post()->post_content, 'spg_paper')) {
            wp_enqueue_style('spg-paper-style', SPG_PLUGIN_URL . 'public/assets/css/paper-style.css', array(), SPG_VERSION);
            wp_enqueue_script('spg-print-script', SPG_PLUGIN_URL . 'public/assets/js/print-paper.js', array('jquery'), SPG_VERSION, true);
        }
    }
    
    private function set_default_options() {
        $defaults = array(
            'spg_version' => SPG_VERSION,
            'spg_installed_date' => current_time('timestamp'),
            'spg_school_name' => get_bloginfo('name'),
            'spg_max_marks' => 100,
            'spg_time_duration' => '3 hours',
            'spg_instructions' => $this->get_default_instructions(),
            'spg_enable_trial' => 'yes'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    private function get_default_instructions() {
        return implode("\n", array(
            __('All questions are compulsory', 'school-paper-generator'),
            __('Write your answers neatly', 'school-paper-generator'),
            __('Use black or blue pen only', 'school-paper-generator'),
            __('Calculators are not allowed', 'school-paper-generator')
        ));
    }
    
    private function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/spg-temp/';
        
        if (file_exists($temp_dir)) {
            $files = glob($temp_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    public function paper_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'mode' => 'view', // view, print, student
            'show_answers' => false
        ), $atts, 'spg_paper');
        
        ob_start();
        include SPG_PLUGIN_DIR . 'public/templates/paper-display.php';
        return ob_get_clean();
    }
    
    public function question_bank_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to access question bank', 'school-paper-generator') . '</p>';
        }
        
        ob_start();
        include SPG_PLUGIN_DIR . 'public/templates/student-view.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
SchoolPaperGenerator::get_instance();
?>