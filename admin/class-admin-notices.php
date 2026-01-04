<?php
// admin/class-admin-notices.php

if (!defined('ABSPATH')) {
    exit;
}

class Online_Exam_Admin_Notices {
    
    private static $instance = null;
    private $notices = [];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_notices', [$this, 'display_notices']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_dismiss_exam_notice', [$this, 'ajax_dismiss_notice']);
    }
    
    /**
     * Add a notice
     */
    public function add_notice($message, $type = 'info', $dismissible = true, $id = null) {
        if (!in_array($type, ['success', 'error', 'warning', 'info'])) {
            $type = 'info';
        }
        
        $notice = [
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
            'id' => $id ?: uniqid('notice_'),
            'time' => current_time('timestamp')
        ];
        
        $this->notices[] = $notice;
        
        // Store in database for persistence across page loads
        $this->store_notice($notice);
    }
    
    /**
     * Store notice in database
     */
    private function store_notice($notice) {
        $stored_notices = get_option('online_exam_admin_notices', []);
        $stored_notices[] = $notice;
        
        // Keep only last 50 notices
        if (count($stored_notices) > 50) {
            $stored_notices = array_slice($stored_notices, -50);
        }
        
        update_option('online_exam_admin_notices', $stored_notices);
    }
    
    /**
     * Get stored notices
     */
    private function get_stored_notices() {
        return get_option('online_exam_admin_notices', []);
    }
    
    /**
     * Clear stored notices
     */
    private function clear_stored_notices() {
        delete_option('online_exam_admin_notices');
    }
    
    /**
     * Remove a notice by ID
     */
    public function remove_notice($id) {
        $stored_notices = $this->get_stored_notices();
        $filtered_notices = array_filter($stored_notices, function($notice) use ($id) {
            return $notice['id'] !== $id;
        });
        
        update_option('online_exam_admin_notices', array_values($filtered_notices));
    }
    
    /**
     * Add success notice
     */
    public function add_success($message, $dismissible = true, $id = null) {
        $this->add_notice($message, 'success', $dismissible, $id);
    }
    
    /**
     * Add error notice
     */
    public function add_error($message, $dismissible = true, $id = null) {
        $this->add_notice($message, 'error', $dismissible, $id);
    }
    
    /**
     * Add warning notice
     */
    public function add_warning($message, $dismissible = true, $id = null) {
        $this->add_notice($message, 'warning', $dismissible, $id);
    }
    
    /**
     * Add info notice
     */
    public function add_info($message, $dismissible = true, $id = null) {
        $this->add_notice($message, 'info', $dismissible, $id);
    }
    
    /**
     * Display all notices
     */
    public function display_notices() {
        // Only show on our plugin pages
        $current_screen = get_current_screen();
        if (strpos($current_screen->id, 'online-exam') === false) {
            return;
        }
        
        // Get stored notices
        $stored_notices = $this->get_stored_notices();
        
        // Add current session notices
        $all_notices = array_merge($stored_notices, $this->notices);
        
        // Remove duplicates
        $unique_notices = [];
        $displayed_ids = [];
        
        foreach ($all_notices as $notice) {
            if (!in_array($notice['id'], $displayed_ids)) {
                $unique_notices[] = $notice;
                $displayed_ids[] = $notice['id'];
            }
        }
        
        // Display notices
        foreach ($unique_notices as $notice) {
            $this->display_notice($notice);
        }
        
        // Clear stored notices after displaying
        if (!empty($stored_notices)) {
            $this->clear_stored_notices();
        }
    }
    
    /**
     * Display single notice
     */
    private function display_notice($notice) {
        $classes = 'notice notice-' . $notice['type'];
        if ($notice['dismissible']) {
            $classes .= ' is-dismissible online-exam-notice';
        }
        
        // Check if notice should be dismissed
        $dismissed_notices = get_user_meta(get_current_user_id(), 'online_exam_dismissed_notices', true);
        if (is_array($dismissed_notices) && in_array($notice['id'], $dismissed_notices)) {
            return;
        }
        
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-notice-id="<?php echo esc_attr($notice['id']); ?>">
            <p><?php echo wp_kses_post($notice['message']); ?></p>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'online-exam') === false) {
            return;
        }
        
        wp_enqueue_style(
            'online-exam-admin-notices',
            plugin_dir_url(__FILE__) . '../assets/css/admin-notices.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'online-exam-admin-notices',
            plugin_dir_url(__FILE__) . '../assets/js/admin-notices.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('online-exam-admin-notices', 'examNotices', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dismiss_exam_notice')
        ]);
    }
    
    /**
     * AJAX dismiss notice
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer('dismiss_exam_notice', 'nonce');
        
        $notice_id = sanitize_text_field($_POST['notice_id'] ?? '');
        $user_id = get_current_user_id();
        
        if (empty($notice_id) || !$user_id) {
            wp_send_json_error('Invalid request');
        }
        
        // Get current dismissed notices
        $dismissed_notices = get_user_meta($user_id, 'online_exam_dismissed_notices', true);
        
        if (!is_array($dismissed_notices)) {
            $dismissed_notices = [];
        }
        
        // Add new notice ID
        if (!in_array($notice_id, $dismissed_notices)) {
            $dismissed_notices[] = $notice_id;
            update_user_meta($user_id, 'online_exam_dismissed_notices', $dismissed_notices);
        }
        
        wp_send_json_success('Notice dismissed');
    }
    
    /**
     * Add system status notices
     */
    public function add_system_notices() {
        global $wpdb;
        
        // Check database tables
        $tables = [
            $wpdb->prefix . 'exam_categories',
            $wpdb->prefix . 'exam_questions',
            $wpdb->prefix . 'exam_question_options',
            $wpdb->prefix . 'exam_papers',
            $wpdb->prefix . 'exam_paper_questions',
            $wpdb->prefix . 'exam_results',
            $wpdb->prefix . 'exam_user_progress'
        ];
        
        $missing_tables = [];
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $missing_tables[] = str_replace($wpdb->prefix, '', $table);
            }
        }
        
        if (!empty($missing_tables)) {
            $this->add_warning(
                sprintf(
                    __('Some database tables are missing: %s. Please run the plugin activation process.', 'online-exam'),
                    implode(', ', $missing_tables)
                ),
                false,
                'missing_tables'
            );
        }
        
        // Check PHP version
        $min_php_version = '7.2';
        if (version_compare(PHP_VERSION, $min_php_version, '<')) {
            $this->add_error(
                sprintf(
                    __('Your PHP version (%s) is outdated. Online Exam System requires PHP %s or higher.', 'online-exam'),
                    PHP_VERSION,
                    $min_php_version
                ),
                false,
                'php_version'
            );
        }
        
        // Check for required extensions
        $required_extensions = ['json', 'mbstring'];
        $missing_extensions = [];
        
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }
        
        if (!empty($missing_extensions)) {
            $this->add_warning(
                sprintf(
                    __('Required PHP extensions are missing: %s. Some features may not work properly.', 'online-exam'),
                    implode(', ', $missing_extensions)
                ),
                false,
                'missing_extensions'
            );
        }
        
        // Check file permissions
        $upload_dir = wp_upload_dir();
        $exam_upload_dir = $upload_dir['basedir'] . '/online-exam/';
        
        if (!is_writable($exam_upload_dir)) {
            $this->add_warning(
                __('Upload directory is not writable. PDF generation and file uploads may not work.', 'online-exam'),
                true,
                'upload_permissions'
            );
        }
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(WP_MEMORY_LIMIT);
        $recommended_memory = 128 * 1024 * 1024; // 128MB
        
        if ($memory_limit < $recommended_memory) {
            $this->add_info(
                sprintf(
                    __('Your memory limit is %s. For better performance, consider increasing it to at least 128MB.', 'online-exam'),
                    WP_MEMORY_LIMIT
                ),
                true,
                'memory_limit'
            );
        }
    }
    
    /**
     * Add welcome notice
     */
    public function add_welcome_notice() {
        $user_id = get_current_user_id();
        $dismissed_notices = get_user_meta($user_id, 'online_exam_dismissed_notices', true);
        
        if (!is_array($dismissed_notices) || !in_array('welcome_message', $dismissed_notices)) {
            $message = sprintf(
                __('Welcome to Online Exam System! Get started by <a href="%s">adding questions</a> or <a href="%s">creating your first exam paper</a>.', 'online-exam'),
                admin_url('admin.php?page=online-exam-questions&action=add'),
                admin_url('admin.php?page=online-exam-papers&action=add')
            );
            
            $this->add_success($message, true, 'welcome_message');
        }
    }
    
    /**
     * Add import/export notices
     */
    public function add_import_export_notices() {
        if (isset($_GET['import_success'])) {
            $count = intval($_GET['import_success']);
            $this->add_success(
                sprintf(
                    __('Successfully imported %d question(s).', 'online-exam'),
                    $count
                ),
                true,
                'import_success'
            );
        }
        
        if (isset($_GET['export_success'])) {
            $this->add_success(
                __('Export completed successfully. Your download should begin shortly.', 'online-exam'),
                true,
                'export_success'
            );
        }
        
        if (isset($_GET['import_error'])) {
            $error_messages = [
                'no_file' => __('No file was uploaded.', 'online-exam'),
                'invalid_type' => __('Invalid file type. Please upload CSV, JSON, or Excel files.', 'online-exam'),
                'file_too_large' => __('File is too large. Maximum size is 5MB.', 'online-exam'),
                'parse_error' => __('Error parsing the file. Please check the format.', 'online-exam'),
                'save_error' => __('Error saving questions to database.', 'online-exam')
            ];
            
            $error_key = sanitize_text_field($_GET['import_error']);
            $message = $error_messages[$error_key] ?? __('An error occurred during import.', 'online-exam');
            
            $this->add_error($message, true, 'import_error');
        }
    }
    
    /**
     * Add paper creation notices
     */
    public function add_paper_notices() {
        if (isset($_GET['paper_created'])) {
            $paper_id = intval($_GET['paper_created']);
            $paper_title = get_the_title($paper_id) ?: __('New Paper', 'online-exam');
            
            $this->add_success(
                sprintf(
                    __('Paper "%s" created successfully. <a href="%s">View paper</a> | <a href="%s">Export paper</a>', 'online-exam'),
                    esc_html($paper_title),
                    admin_url('admin.php?page=online-exam-papers&action=view&id=' . $paper_id),
                    admin_url('admin.php?page=online-exam-export&paper_id=' . $paper_id)
                ),
                true,
                'paper_created'
            );
        }
        
        if (isset($_GET['paper_updated'])) {
            $paper_id = intval($_GET['paper_updated']);
            $paper_title = get_the_title($paper_id) ?: __('Paper', 'online-exam');
            
            $this->add_success(
                sprintf(
                    __('Paper "%s" updated successfully.', 'online-exam'),
                    esc_html($paper_title)
                ),
                true,
                'paper_updated'
            );
        }
        
        if (isset($_GET['paper_deleted'])) {
            $this->add_success(
                __('Paper deleted successfully.', 'online-exam'),
                true,
                'paper_deleted'
            );
        }
    }
    
    /**
     * Add question management notices
     */
    public function add_question_notices() {
        if (isset($_GET['question_added'])) {
            $this->add_success(
                __('Question added successfully.', 'online-exam'),
                true,
                'question_added'
            );
        }
        
        if (isset($_GET['question_updated'])) {
            $this->add_success(
                __('Question updated successfully.', 'online-exam'),
                true,
                'question_updated'
            );
        }
        
        if (isset($_GET['question_deleted'])) {
            $this->add_success(
                __('Question deleted successfully.', 'online-exam'),
                true,
                'question_deleted'
            );
        }
        
        if (isset($_GET['bulk_action_completed'])) {
            $action = sanitize_text_field($_GET['action_type'] ?? '');
            $count = intval($_GET['count'] ?? 0);
            
            if ($count > 0) {
                $messages = [
                    'delete' => sprintf(_n('%d question deleted.', '%d questions deleted.', $count, 'online-exam'), $count),
                    'trash' => sprintf(_n('%d question moved to trash.', '%d questions moved to trash.', $count, 'online-exam'), $count),
                    'restore' => sprintf(_n('%d question restored.', '%d questions restored.', $count, 'online-exam'), $count),
                    'change_category' => sprintf(_n('%d question category updated.', '%d questions category updated.', $count, 'online-exam'), $count),
                    'change_difficulty' => sprintf(_n('%d question difficulty updated.', '%d questions difficulty updated.', $count, 'online-exam'), $count)
                ];
                
                if (isset($messages[$action])) {
                    $this->add_success($messages[$action], true, 'bulk_action_' . $action);
                }
            }
        }
    }
    
    /**
     * Add result notices
     */
    public function add_result_notices() {
        if (isset($_GET['result_deleted'])) {
            $this->add_success(
                __('Result deleted successfully.', 'online-exam'),
                true,
                'result_deleted'
            );
        }
        
        if (isset($_GET['results_exported'])) {
            $this->add_success(
                __('Results exported successfully.', 'online-exam'),
                true,
                'results_exported'
            );
        }
    }
    
    /**
     * Add setup wizard notice
     */
    public function add_setup_wizard_notice() {
        $user_id = get_current_user_id();
        $dismissed_notices = get_user_meta($user_id, 'online_exam_dismissed_notices', true);
        
        if (!is_array($dismissed_notices) || !in_array('setup_wizard', $dismissed_notices)) {
            // Check if setup is complete
            $setup_complete = get_option('online_exam_setup_complete', false);
            
            if (!$setup_complete) {
                $this->add_info(
                    sprintf(
                        __('Complete the setup wizard to configure your Online Exam System. <a href="%s">Start setup wizard</a>', 'online-exam'),
                        admin_url('admin.php?page=online-exam-setup')
                    ),
                    true,
                    'setup_wizard'
                );
            }
        }
    }
    
    /**
     * Add review notice
     */
    public function add_review_notice() {
        $user_id = get_current_user_id();
        $dismissed_notices = get_user_meta($user_id, 'online_exam_dismissed_notices', true);
        
        // Check if notice was already dismissed
        if (is_array($dismissed_notices) && in_array('review_request', $dismissed_notices)) {
            return;
        }
        
        // Check if plugin has been active for 7 days
        $activation_time = get_option('online_exam_activation_time', 0);
        if ($activation_time && (time() - $activation_time) > (7 * DAY_IN_SECONDS)) {
            $this->add_info(
                sprintf(
                    __('Enjoying Online Exam System? Please consider <a href="%s" target="_blank">leaving a review</a>. It helps us improve the plugin!', 'online-exam'),
                    'https://wordpress.org/support/plugin/online-exam/reviews/#new-post'
                ),
                true,
                'review_request'
            );
        }
    }
    
    /**
     * Clear all notices for current user
     */
    public function clear_all_notices() {
        delete_user_meta(get_current_user_id(), 'online_exam_dismissed_notices');
    }
    
    /**
     * Get notice count
     */
    public function get_notice_count() {
        $stored_notices = $this->get_stored_notices();
        $user_id = get_current_user_id();
        $dismissed_notices = get_user_meta($user_id, 'online_exam_dismissed_notices', true);
        
        if (!is_array($dismissed_notices)) {
            $dismissed_notices = [];
        }
        
        // Filter out dismissed notices
        $active_notices = array_filter($stored_notices, function($notice) use ($dismissed_notices) {
            return !in_array($notice['id'], $dismissed_notices);
        });
        
        return count($active_notices) + count($this->notices);
    }
}

// Initialize the notice system
function init_exam_admin_notices() {
    return Online_Exam_Admin_Notices::get_instance();
}
add_action('admin_init', 'init_exam_admin_notices');

// Helper functions
function exam_add_notice($message, $type = 'info', $dismissible = true, $id = null) {
    $notices = Online_Exam_Admin_Notices::get_instance();
    $notices->add_notice($message, $type, $dismissible, $id);
}

function exam_add_success($message, $dismissible = true, $id = null) {
    $notices = Online_Exam_Admin_Notices::get_instance();
    $notices->add_success($message, $dismissible, $id);
}

function exam_add_error($message, $dismissible = true, $id = null) {
    $notices = Online_Exam_Admin_Notices::get_instance();
    $notices->add_error($message, $dismissible, $id);
}

function exam_add_warning($message, $dismissible = true, $id = null) {
    $notices = Online_Exam_Admin_Notices::get_instance();
    $notices->add_warning($message, $dismissible, $id);
}

function exam_add_info($message, $dismissible = true, $id = null) {
    $notices = Online_Exam_Admin_Notices::get_instance();
    $notices->add_info($message, $dismissible, $id);
}