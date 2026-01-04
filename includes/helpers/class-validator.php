<?php
if (!defined('ABSPATH')) exit;

class SPG_Validator {
    
    public static function validate_question_data($data) {
        $errors = array();
        
        // Required fields
        $required = array(
            'question_text' => __('Question text', 'school-paper-generator'),
            'question_type' => __('Question type', 'school-paper-generator'),
            'subject' => __('Subject', 'school-paper-generator'),
            'class_level' => __('Class level', 'school-paper-generator')
        );
        
        foreach ($required as $field => $field_name) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('%s is required', 'school-paper-generator'), $field_name);
            }
        }
        
        // Validate question type
        if (!empty($data['question_type'])) {
            $valid_types = array('mcq', 'short', 'long', 'true_false');
            if (!in_array($data['question_type'], $valid_types)) {
                $errors[] = __('Invalid question type', 'school-paper-generator');
            }
        }
        
        // Validate MCQ options
        if (!empty($data['question_type']) && $data['question_type'] === 'mcq') {
            if (empty($data['options']) || !is_array($data['options']) || count($data['options']) < 2) {
                $errors[] = __('MCQ questions require at least 2 options', 'school-paper-generator');
            }
            
            if (empty($data['correct_answer'])) {
                $errors[] = __('Correct answer is required for MCQ questions', 'school-paper-generator');
            } elseif (!empty($data['options']) && !in_array($data['correct_answer'], $data['options'])) {
                $errors[] = __('Correct answer must be one of the options', 'school-paper-generator');
            }
        }
        
        // Validate True/False
        if (!empty($data['question_type']) && $data['question_type'] === 'true_false') {
            if (!in_array($data['correct_answer'], array('True', 'False'))) {
                $errors[] = __('Correct answer must be True or False', 'school-paper-generator');
            }
        }
        
        // Validate marks
        if (!empty($data['marks'])) {
            $marks = intval($data['marks']);
            if ($marks < 1 || $marks > 100) {
                $errors[] = __('Marks must be between 1 and 100', 'school-paper-generator');
            }
        }
        
        // Validate difficulty
        if (!empty($data['difficulty'])) {
            $valid_difficulties = array('easy', 'medium', 'hard');
            if (!in_array($data['difficulty'], $valid_difficulties)) {
                $errors[] = __('Invalid difficulty level', 'school-paper-generator');
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    public static function validate_paper_data($data) {
        $errors = array();
        
        // Required fields
        $required = array(
            'title' => __('Paper title', 'school-paper-generator'),
            'subject' => __('Subject', 'school-paper-generator'),
            'class_level' => __('Class level', 'school-paper-generator')
        );
        
        foreach ($required as $field => $field_name) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('%s is required', 'school-paper-generator'), $field_name);
            }
        }
        
        // Validate questions
        if (empty($data['questions']) || !is_array($data['questions']) || count($data['questions']) === 0) {
            $errors[] = __('Paper must contain at least one question', 'school-paper-generator');
        } else {
            $total_marks = 0;
            foreach ($data['questions'] as $question) {
                if (empty($question['id'])) {
                    $errors[] = __('Invalid question data', 'school-paper-generator');
                    break;
                }
                $total_marks += intval($question['marks'] ?? 1);
            }
            
            // Check if total marks matches
            $paper_total_marks = intval($data['total_marks'] ?? 100);
            if ($total_marks !== $paper_total_marks) {
                $errors[] = sprintf(
                    __('Total marks (%d) do not match sum of question marks (%d)', 'school-paper-generator'),
                    $paper_total_marks,
                    $total_marks
                );
            }
        }
        
        // Validate total marks
        if (!empty($data['total_marks'])) {
            $total_marks = intval($data['total_marks']);
            if ($total_marks < 1 || $total_marks > 500) {
                $errors[] = __('Total marks must be between 1 and 500', 'school-paper-generator');
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    public static function validate_import_file($file, $type) {
        $errors = array();
        
        // Check if file exists
        if (empty($file) || !file_exists($file)) {
            return array(__('File not found', 'school-paper-generator'));
        }
        
        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if (filesize($file) > $max_size) {
            $errors[] = __('File size exceeds maximum limit of 5MB', 'school-paper-generator');
        }
        
        // Validate based on file type
        switch ($type) {
            case 'csv':
                $errors = array_merge($errors, self::validate_csv_file($file));
                break;
                
            case 'json':
                $errors = array_merge($errors, self::validate_json_file($file));
                break;
                
            case 'xlsx':
            case 'xls':
                $errors = array_merge($errors, self::validate_excel_file($file));
                break;
                
            default:
                $errors[] = __('Unsupported file format', 'school-paper-generator');
        }
        
        return $errors;
    }
    
    private static function validate_csv_file($file) {
        $errors = array();
        
        $handle = fopen($file, 'r');
        if ($handle === false) {
            return array(__('Cannot open CSV file', 'school-paper-generator'));
        }
        
        // Check if file has content
        if (filesize($file) === 0) {
            fclose($handle);
            return array(__('CSV file is empty', 'school-paper-generator'));
        }
        
        // Read first row (headers)
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return array(__('Invalid CSV format', 'school-paper-generator'));
        }
        
        // Required headers
        $required_headers = array('question_text', 'question_type', 'subject', 'class_level');
        foreach ($required_headers as $required) {
            if (!in_array($required, $headers)) {
                $errors[] = sprintf(__('Missing required column: %s', 'school-paper-generator'), $required);
            }
        }
        
        fclose($handle);
        return $errors;
    }
    
    private static function validate_json_file($file) {
        $errors = array();
        
        $content = file_get_contents($file);
        if ($content === false) {
            return array(__('Cannot read JSON file', 'school-paper-generator'));
        }
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(__('Invalid JSON format', 'school-paper-generator'));
        }
        
        if (empty($data) || !is_array($data)) {
            return array(__('JSON file must contain an array of questions', 'school-paper-generator'));
        }
        
        // Check first item for required fields
        if (count($data) > 0) {
            $first_item = $data[0];
            $required_fields = array('question_text', 'question_type', 'subject', 'class_level');
            
            foreach ($required_fields as $field) {
                if (!isset($first_item[$field])) {
                    $errors[] = sprintf(__('Missing required field: %s', 'school-paper-generator'), $field);
                }
            }
        }
        
        return $errors;
    }
    
    private static function validate_excel_file($file) {
        $errors = array();
        
        // Check if PHPExcel/PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            return array(__('Excel file processing requires PhpSpreadsheet library', 'school-paper-generator'));
        }
        
        return $errors;
    }
    
    public static function sanitize_question_text($text) {
        // Remove script tags and style tags
        $text = wp_strip_all_tags($text, true);
        
        // Allow basic HTML tags for formatting
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'u' => array(),
            'ol' => array(),
            'ul' => array(),
            'li' => array(),
            'sub' => array(),
            'sup' => array()
        );
        
        $text = wp_kses($text, $allowed_tags);
        
        // Trim and clean
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text);
        
        return $text;
    }
    
    public static function sanitize_options($options) {
        if (!is_array($options)) {
            return array();
        }
        
        $sanitized = array();
        foreach ($options as $option) {
            $sanitized[] = sanitize_text_field($option);
        }
        
        // Remove empty options
        $sanitized = array_filter($sanitized);
        
        return $sanitized;
    }
    
    public static function sanitize_school_data($data) {
        $sanitized = array();
        
        if (!empty($data['school_name'])) {
            $sanitized['school_name'] = sanitize_text_field($data['school_name']);
        }
        
        if (!empty($data['school_address'])) {
            $sanitized['school_address'] = sanitize_textarea_field($data['school_address']);
        }
        
        if (!empty($data['school_logo'])) {
            $sanitized['school_logo'] = esc_url_raw($data['school_logo']);
        }
        
        return $sanitized;
    }
    
    public static function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}
?>