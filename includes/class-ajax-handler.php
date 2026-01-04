<?php
if (!defined('ABSPATH')) exit;

class SPG_Ajax_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_ajax_actions();
    }
    
    private function init_ajax_actions() {
        // Question Bank Actions
        add_action('wp_ajax_spg_get_questions', array($this, 'get_questions'));
        add_action('wp_ajax_spg_add_question', array($this, 'add_question'));
        add_action('wp_ajax_spg_update_question', array($this, 'update_question'));
        add_action('wp_ajax_spg_delete_question', array($this, 'delete_question'));
        add_action('wp_ajax_spg_bulk_delete_questions', array($this, 'bulk_delete_questions'));
        add_action('wp_ajax_spg_import_questions', array($this, 'import_questions'));
        add_action('wp_ajax_spg_export_questions', array($this, 'export_questions'));
        
        // Paper Actions
        add_action('wp_ajax_spg_generate_paper', array($this, 'generate_paper'));
        add_action('wp_ajax_spg_save_paper', array($this, 'save_paper'));
        add_action('wp_ajax_spg_get_paper', array($this, 'get_paper'));
        add_action('wp_ajax_spg_delete_paper', array($this, 'delete_paper'));
        add_action('wp_ajax_spg_duplicate_paper', array($this, 'duplicate_paper'));
        add_action('wp_ajax_spg_export_paper', array($this, 'export_paper'));
        
        // Settings Actions
        add_action('wp_ajax_spg_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_spg_upload_logo', array($this, 'upload_logo'));
        
        // Frontend Actions
        add_action('wp_ajax_nopriv_spg_get_paper_view', array($this, 'get_paper_view'));
        add_action('wp_ajax_spg_get_paper_view', array($this, 'get_paper_view'));
    }
    
    public function get_questions() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $filters = array();
        $params = array('post_status' => 'publish');
        
        if (!empty($_POST['filters'])) {
            $filters = wp_unslash($_POST['filters']);
            
            if (!empty($filters['subject'])) {
                $params['subject'] = sanitize_text_field($filters['subject']);
            }
            
            if (!empty($filters['class_level'])) {
                $params['class_level'] = sanitize_text_field($filters['class_level']);
            }
            
            if (!empty($filters['question_type'])) {
                $params['question_type'] = sanitize_text_field($filters['question_type']);
            }
            
            if (!empty($filters['difficulty'])) {
                $params['difficulty'] = sanitize_text_field($filters['difficulty']);
            }
            
            if (!empty($filters['search'])) {
                $params['search'] = sanitize_text_field($filters['search']);
            }
            
            if (!empty($filters['limit'])) {
                $params['limit'] = intval($filters['limit']);
            }
        }
        
        $question_bank = SPG_Question_Bank::get_instance();
        $questions = $question_bank->get_filtered_questions($params);
        
        wp_send_json_success(array(
            'questions' => $questions,
            'total' => count($questions),
            'filters' => $filters
        ));
    }
    
    public function add_question() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $data = wp_unslash($_POST);
        
        // Check trial limitations
        if (!spg_is_premium_active()) {
            $question_count = SPG_Question_Bank::get_instance()->get_total_questions();
            if ($question_count >= SPG_MAX_TRIAL_QUESTIONS) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Trial version limited to %d questions. Upgrade to premium for unlimited questions.', 'school-paper-generator'),
                        SPG_MAX_TRIAL_QUESTIONS
                    ),
                    'upgrade_url' => 'https://yourwebsite.com/upgrade'
                ));
            }
        }
        
        $question_bank = SPG_Question_Bank::get_instance();
        $result = $question_bank->add_question($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Question added successfully!', 'school-paper-generator'),
            'question_id' => $result,
            'total_questions' => $question_bank->get_total_questions()
        ));
    }
    
    public function update_question() {
        $this->verify_nonce();
        $this->check_permissions();
        
        if (empty($_POST['question_id'])) {
            wp_send_json_error(array(
                'message' => __('Question ID is required', 'school-paper-generator')
            ));
        }
        
        $question_id = intval($_POST['question_id']);
        $data = wp_unslash($_POST);
        
        $question_bank = SPG_Question_Bank::get_instance();
        $result = $question_bank->update_question($question_id, $data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Question updated successfully!', 'school-paper-generator')
        ));
    }
    
    public function delete_question() {
        $this->verify_nonce();
        $this->check_permissions();
        
        if (empty($_POST['question_id'])) {
            wp_send_json_error(array(
                'message' => __('Question ID is required', 'school-paper-generator')
            ));
        }
        
        $question_id = intval($_POST['question_id']);
        $question_bank = SPG_Question_Bank::get_instance();
        $result = $question_bank->delete_question($question_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Question deleted successfully!', 'school-paper-generator'),
            'total_questions' => $question_bank->get_total_questions()
        ));
    }
    
    public function bulk_delete_questions() {
        $this->verify_nonce();
        $this->check_permissions();
        
        if (empty($_POST['question_ids']) || !is_array($_POST['question_ids'])) {
            wp_send_json_error(array(
                'message' => __('No questions selected', 'school-paper-generator')
            ));
        }
        
        $question_ids = array_map('intval', $_POST['question_ids']);
        $question_bank = SPG_Question_Bank::get_instance();
        $deleted = 0;
        
        foreach ($question_ids as $question_id) {
            $result = $question_bank->delete_question($question_id);
            if (!is_wp_error($result)) {
                $deleted++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d questions deleted successfully!', 'school-paper-generator'), $deleted),
            'total_questions' => $question_bank->get_total_questions()
        ));
    }
    
    public function import_questions() {
        $this->verify_nonce();
        $this->check_permissions();
        
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(array(
                'message' => __('No file uploaded', 'school-paper-generator')
            ));
        }
        
        $file = $_FILES['import_file'];
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('csv', 'json', 'xlsx');
        
        if (!in_array($file_ext, $allowed_extensions)) {
            wp_send_json_error(array(
                'message' => __('Invalid file format. Allowed formats: CSV, JSON, Excel', 'school-paper-generator')
            ));
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array(
                'message' => __('File size too large. Maximum size is 5MB', 'school-paper-generator')
            ));
        }
        
        $importer = new SPG_Question_Importer();
        $result = $importer->import($file['tmp_name'], $file_ext);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('%d questions imported successfully!', 'school-paper-generator'),
                $result['imported']
            ),
            'total_questions' => SPG_Question_Bank::get_instance()->get_total_questions(),
            'stats' => $result
        ));
    }
    
    public function export_questions() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $format = !empty($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        $filters = !empty($_POST['filters']) ? wp_unslash($_POST['filters']) : array();
        
        $exporter = new SPG_Question_Exporter();
        $result = $exporter->export($format, $filters);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Export completed successfully', 'school-paper-generator'),
            'download_url' => $result
        ));
    }
    
    public function generate_paper() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $config = !empty($_POST['config']) ? wp_unslash($_POST['config']) : array();
        
        // Validate required fields
        $required = array('subject', 'class_level');
        foreach ($required as $field) {
            if (empty($config[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('%s is required', 'school-paper-generator'), ucfirst($field))
                ));
            }
        }
        
        $paper_generator = SPG_Paper_Generator::get_instance();
        $questions = $paper_generator->generate_paper($config);
        
        if (empty($questions)) {
            wp_send_json_error(array(
                'message' => __('No questions found matching your criteria. Please adjust your filters.', 'school-paper-generator')
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d questions generated successfully!', 'school-paper-generator'), count($questions)),
            'questions' => $questions,
            'total_marks' => array_sum(array_column($questions, 'marks'))
        ));
    }
    
    public function save_paper() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $paper_data = !empty($_POST['paper_data']) ? wp_unslash($_POST['paper_data']) : array();
        
        // Validate required fields
        if (empty($paper_data['title'])) {
            wp_send_json_error(array(
                'message' => __('Paper title is required', 'school-paper-generator')
            ));
        }
        
        if (empty($paper_data['questions']) || !is_array($paper_data['questions'])) {
            wp_send_json_error(array(
                'message' => __('Paper must contain at least one question', 'school-paper-generator')
            ));
        }
        
        $paper_generator = SPG_Paper_Generator::get_instance();
        $paper_id = $paper_generator->save_paper($paper_data);
        
        if (is_wp_error($paper_id)) {
            wp_send_json_error(array(
                'message' => $paper_id->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Paper saved successfully!', 'school-paper-generator'),
            'paper_id' => $paper_id,
            'paper_url' => admin_url('admin.php?page=spg-generated-papers&action=edit&paper_id=' . $paper_id),
            'download_url' => admin_url('admin-ajax.php?action=spg_export_paper&format=pdf&paper_id=' . $paper_id)
        ));
    }
    
    public function export_paper() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $format = !empty($_REQUEST['format']) ? sanitize_text_field($_REQUEST['format']) : 'pdf';
        $paper_id = !empty($_REQUEST['paper_id']) ? intval($_REQUEST['paper_id']) : 0;
        
        // Check premium features
        if (!spg_is_premium_active() && $format !== 'pdf') {
            wp_send_json_error(array(
                'message' => __('Multiple export formats are available in premium version only.', 'school-paper-generator'),
                'upgrade_url' => 'https://yourwebsite.com/upgrade'
            ));
        }
        
        $exporter = new SPG_Paper_Exporter();
        
        if ($paper_id > 0) {
            $result = $exporter->export_by_id($paper_id, $format);
        } elseif (!empty($_POST['paper_data'])) {
            $paper_data = wp_unslash($_POST['paper_data']);
            $result = $exporter->export_data($paper_data, $format);
        } else {
            wp_send_json_error(array(
                'message' => __('Paper data is required', 'school-paper-generator')
            ));
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        // For direct download
        if (!wp_doing_ajax()) {
            header('Content-Type: application/' . $format);
            header('Content-Disposition: attachment; filename="paper-' . date('Y-m-d') . '.' . $format . '"');
            echo $result;
            exit;
        }
        
        wp_send_json_success(array(
            'message' => __('Paper exported successfully!', 'school-paper-generator'),
            'content' => $result
        ));
    }
    
    public function save_settings() {
        $this->verify_nonce();
        $this->check_permissions();
        
        $settings = !empty($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        
        foreach ($settings as $key => $value) {
            if (strpos($key, 'spg_') === 0) {
                if (is_array($value)) {
                    $value = array_map('sanitize_text_field', $value);
                } else {
                    $value = sanitize_text_field($value);
                }
                update_option($key, $value);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'school-paper-generator')
        ));
    }
    
    public function upload_logo() {
        $this->verify_nonce();
        $this->check_permissions();
        
        // Check premium features
        if (!spg_is_premium_active()) {
            wp_send_json_error(array(
                'message' => __('School logo feature is available in premium version only.', 'school-paper-generator'),
                'upgrade_url' => 'https://yourwebsite.com/upgrade'
            ));
        }
        
        if (empty($_FILES['school_logo'])) {
            wp_send_json_error(array(
                'message' => __('No file uploaded', 'school-paper-generator')
            ));
        }
        
        $file = $_FILES['school_logo'];
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Invalid file type. Allowed types: JPG, PNG, GIF', 'school-paper-generator')
            ));
        }
        
        // Check file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array(
                'message' => __('File size too large. Maximum size is 2MB', 'school-paper-generator')
            ));
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('school_logo', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array(
                'message' => $attachment_id->get_error_message()
            ));
        }
        
        $logo_url = wp_get_attachment_url($attachment_id);
        
        // Save to settings
        update_option('spg_school_logo', $logo_url);
        update_option('spg_school_logo_id', $attachment_id);
        
        wp_send_json_success(array(
            'message' => __('Logo uploaded successfully!', 'school-paper-generator'),
            'logo_url' => $logo_url,
            'attachment_id' => $attachment_id
        ));
    }
    
    public function get_paper_view() {
        if (empty($_GET['paper_id'])) {
            wp_die(__('Paper ID is required', 'school-paper-generator'));
        }
        
        $paper_id = intval($_GET['paper_id']);
        $paper_generator = SPG_Paper_Generator::get_instance();
        $paper = $paper_generator->get_paper($paper_id);
        
        if (!$paper) {
            wp_die(__('Paper not found', 'school-paper-generator'));
        }
        
        include SPG_PLUGIN_DIR . 'public/templates/paper-display.php';
        exit;
    }
    
    private function verify_nonce() {
        if (!check_ajax_referer('spg_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'school-paper-generator')
            ));
        }
    }
    
    private function check_permissions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'school-paper-generator')
            ));
        }
    }
}
?>