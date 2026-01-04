<?php
if (!defined('ABSPATH')) exit;

class SPG_Question_Bank {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_spg_add_question', array($this, 'ajax_add_question'));
        add_action('wp_ajax_spg_update_question', array($this, 'ajax_update_question'));
        add_action('wp_ajax_spg_delete_question', array($this, 'ajax_delete_question'));
        add_action('wp_ajax_spg_get_questions', array($this, 'ajax_get_questions'));
        add_action('wp_ajax_spg_import_questions', array($this, 'ajax_import_questions'));
        add_action('wp_ajax_spg_export_questions', array($this, 'ajax_export_questions'));
    }
    
    public function add_question($data) {
        // Check trial limitations
        if (!spg_is_premium_active()) {
            $total_questions = $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}spg_questions");
            if ($total_questions >= SPG_MAX_TRIAL_QUESTIONS) {
                return new WP_Error('trial_limit', 
                    sprintf(__('Trial version limited to %d questions. Upgrade to premium for unlimited questions.', 'school-paper-generator'), 
                    SPG_MAX_TRIAL_QUESTIONS));
            }
        }
        
        $question_data = $this->validate_question_data($data);
        
        if (is_wp_error($question_data)) {
            return $question_data;
        }
        
        $result = $this->db->insert("{$this->db->prefix}spg_questions", $question_data);
        
        if ($result) {
            $question_id = $this->db->insert_id;
            do_action('spg_question_added', $question_id, $question_data);
            return $question_id;
        }
        
        return new WP_Error('db_error', __('Failed to add question', 'school-paper-generator'));
    }
    
    private function validate_question_data($data) {
        $validated = array();
        
        // Required fields
        $required = array('question_text', 'question_type', 'subject', 'class_level');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('%s is required', 'school-paper-generator'), $field));
            }
        }
        
        $validated['question_text'] = wp_kses_post($data['question_text']);
        $validated['question_type'] = in_array($data['question_type'], array('mcq', 'short', 'long', 'true_false')) 
            ? $data['question_type'] : 'mcq';
        $validated['subject'] = sanitize_text_field($data['subject']);
        $validated['class_level'] = sanitize_text_field($data['class_level']);
        $validated['chapter'] = !empty($data['chapter']) ? sanitize_text_field($data['chapter']) : null;
        $validated['topic'] = !empty($data['topic']) ? sanitize_text_field($data['topic']) : null;
        $validated['marks'] = !empty($data['marks']) ? intval($data['marks']) : 1;
        $validated['difficulty'] = !empty($data['difficulty']) ? sanitize_text_field($data['difficulty']) : 'medium';
        $validated['created_by'] = get_current_user_id();
        $validated['status'] = 'active';
        
        // Handle question type specific data
        switch ($validated['question_type']) {
            case 'mcq':
                if (empty($data['options']) || empty($data['correct_answer'])) {
                    return new WP_Error('mcq_missing', __('MCQ questions require options and correct answer', 'school-paper-generator'));
                }
                $validated['options'] = json_encode(array_map('sanitize_text_field', $data['options']));
                $validated['correct_answer'] = sanitize_text_field($data['correct_answer']);
                break;
                
            case 'true_false':
                $validated['options'] = json_encode(array('True', 'False'));
                $validated['correct_answer'] = in_array($data['correct_answer'], array('True', 'False')) 
                    ? $data['correct_answer'] : 'True';
                break;
                
            case 'short':
            case 'long':
                $validated['correct_answer'] = !empty($data['correct_answer']) ? wp_kses_post($data['correct_answer']) : null;
                break;
        }
        
        $validated['explanation'] = !empty($data['explanation']) ? wp_kses_post($data['explanation']) : null;
        
        return $validated;
    }
    
    public function get_question_formats() {
        return array(
            'mcq' => array(
                'name' => __('Multiple Choice Question', 'school-paper-generator'),
                'icon' => 'far fa-dot-circle',
                'description' => __('Questions with multiple choices and one correct answer', 'school-paper-generator'),
                'fields' => array('options', 'correct_answer')
            ),
            'short' => array(
                'name' => __('Short Answer', 'school-paper-generator'),
                'icon' => 'far fa-comment',
                'description' => __('Questions requiring brief answers (2-3 sentences)', 'school-paper-generator'),
                'fields' => array('correct_answer')
            ),
            'long' => array(
                'name' => __('Long Answer', 'school-paper-generator'),
                'icon' => 'far fa-file-alt',
                'description' => __('Questions requiring detailed explanations', 'school-paper-generator'),
                'fields' => array('correct_answer')
            ),
            'true_false' => array(
                'name' => __('True/False', 'school-paper-generator'),
                'icon' => 'fas fa-check',
                'description' => __('Questions with True or False answer', 'school-paper-generator'),
                'fields' => array('correct_answer')
            )
        );
    }
    
    public function get_subjects() {
        $subjects = $this->db->get_col("SELECT DISTINCT subject FROM {$this->db->prefix}spg_questions ORDER BY subject");
        
        if (empty($subjects)) {
            return array(
                'Mathematics',
                'Science',
                'English',
                'Social Studies',
                'Computer Science',
                'Physics',
                'Chemistry',
                'Biology',
                'History',
                'Geography'
            );
        }
        
        return $subjects;
    }
    
    public function get_class_levels() {
        $levels = $this->db->get_col("SELECT DISTINCT class_level FROM {$this->db->prefix}spg_questions ORDER BY class_level");
        
        if (empty($levels)) {
            return array(
                'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5',
                'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10',
                'Class 11', 'Class 12'
            );
        }
        
        return $levels;
    }
    
    public function ajax_add_question() {
        check_ajax_referer('spg_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'school-paper-generator'));
        }
        
        $data = $_POST;
        $result = $this->add_question($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => __('Question added successfully', 'school-paper-generator'),
                'question_id' => $result
            ));
        }
    }
    
    // More methods for update, delete, import, export...
}
?>