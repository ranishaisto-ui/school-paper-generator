<?php
if (!defined('ABSPATH')) exit;

class SPG_Paper_Generator {
    
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
        add_action('wp_ajax_spg_generate_paper', array($this, 'ajax_generate_paper'));
        add_action('wp_ajax_spg_save_paper', array($this, 'ajax_save_paper'));
        add_action('wp_ajax_spg_export_paper', array($this, 'ajax_export_paper'));
        add_action('wp_ajax_spg_get_paper', array($this, 'ajax_get_paper'));
    }
    
    public function generate_paper($config) {
        $questions = array();
        
        // Calculate marks distribution
        $marks_distribution = $this->calculate_marks_distribution($config);
        
        // Get questions for each section
        foreach ($marks_distribution as $section => $section_data) {
            $section_questions = $this->get_questions_for_section($section_data, $config);
            $questions = array_merge($questions, $section_questions);
        }
        
        // Shuffle questions if needed
        if (!empty($config['shuffle_questions'])) {
            shuffle($questions);
        }
        
        // Shuffle MCQ options if needed
        if (!empty($config['shuffle_options'])) {
            foreach ($questions as &$question) {
                if ($question['type'] === 'mcq' && !empty($question['options'])) {
                    $options = $question['options'];
                    $correct_answer = $question['correct_answer'];
                    
                    // Store original index of correct answer
                    $correct_index = array_search($correct_answer, $options);
                    
                    // Shuffle options
                    shuffle($options);
                    
                    // Update correct answer to new position
                    $new_correct_index = array_search($correct_answer, $options);
                    $question['options'] = $options;
                    $question['correct_answer'] = $correct_answer;
                    $question['original_correct_index'] = $correct_index;
                }
            }
        }
        
        return $questions;
    }
    
    private function calculate_marks_distribution($config) {
        $total_marks = $config['total_marks'] ?? 100;
        $distribution = array();
        
        // Default distribution if not specified
        if (empty($config['sections'])) {
            return array(
                'mcq' => array(
                    'type' => 'mcq',
                    'count' => 20,
                    'marks_per' => 1,
                    'total_marks' => 20
                ),
                'short' => array(
                    'type' => 'short',
                    'count' => 10,
                    'marks_per' => 3,
                    'total_marks' => 30
                ),
                'long' => array(
                    'type' => 'long',
                    'count' => 5,
                    'marks_per' => 10,
                    'total_marks' => 50
                )
            );
        }
        
        // Calculate based on provided sections
        foreach ($config['sections'] as $section) {
            $distribution[$section['type']] = array(
                'type' => $section['type'],
                'count' => $section['count'],
                'marks_per' => $section['marks_per'],
                'total_marks' => $section['count'] * $section['marks_per']
            );
        }
        
        return $distribution;
    }
    
    private function get_questions_for_section($section_data, $config) {
        global $wpdb;
        
        $where = array(
            "question_type = '{$section_data['type']}'",
            "subject = '{$config['subject']}'",
            "class_level = '{$config['class_level']}'",
            "status = 'active'"
        );
        
        if (!empty($config['chapters'])) {
            $chapters = array_map(function($chap) use ($wpdb) {
                return $wpdb->prepare('%s', $chap);
            }, $config['chapters']);
            $where[] = "chapter IN (" . implode(',', $chapters) . ")";
        }
        
        if (!empty($config['difficulty'])) {
            $where[] = "difficulty = '{$config['difficulty']}'";
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$wpdb->prefix}spg_questions 
                  WHERE {$where_clause} 
                  ORDER BY RAND() 
                  LIMIT {$section_data['count']}";
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Format questions
        $questions = array();
        foreach ($results as $result) {
            $questions[] = array(
                'id' => $result['id'],
                'text' => $result['question_text'],
                'type' => $result['question_type'],
                'marks' => $section_data['marks_per'],
                'options' => !empty($result['options']) ? json_decode($result['options'], true) : null,
                'correct_answer' => $result['correct_answer'],
                'explanation' => $result['explanation'],
                'difficulty' => $result['difficulty'],
                'section' => ucfirst($result['question_type'])
            );
        }
        
        return $questions;
    }
    
    public function create_paper_template($paper_data) {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . esc_html($paper_data['title']) . '</title>
            <style>
                ' . $this->get_paper_styles() . '
            </style>
        </head>
        <body>
            <div class="paper-container">
                ' . $this->generate_paper_header($paper_data) . '
                ' . $this->generate_instructions($paper_data) . '
                ' . $this->generate_questions($paper_data['questions']) . '
                ' . $this->generate_footer($paper_data) . '
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    private function get_paper_styles() {
        return '
        @page { margin: 1cm; }
        body { font-family: "Times New Roman", serif; font-size: 12pt; }
        .paper-container { max-width: 21cm; margin: 0 auto; }
        .paper-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .school-logo { max-height: 80px; max-width: 200px; }
        .school-name { font-size: 18pt; font-weight: bold; }
        .paper-title { font-size: 16pt; margin: 10px 0; }
        .paper-info { display: flex; justify-content: space-between; margin: 15px 0; }
        .instructions { background: #f5f5f5; padding: 10px; border-left: 3px solid #333; margin: 15px 0; }
        .question { margin: 15px 0; }
        .question-number { font-weight: bold; }
        .mcq-options { margin-left: 20px; }
        .option { margin: 3px 0; }
        .section-title { font-weight: bold; border-bottom: 1px solid #ccc; padding: 5px 0; margin: 20px 0 10px 0; }
        .footer { text-align: center; margin-top: 30px; font-style: italic; border-top: 1px solid #ccc; padding-top: 10px; }
        .marks { float: right; font-weight: bold; }
        ';
    }
    
    // More methods for header, questions, footer generation...
}
?>