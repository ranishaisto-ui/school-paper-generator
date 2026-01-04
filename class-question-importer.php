<?php
// includes/class-question-importer.php

if (!defined('ABSPATH')) {
    exit;
}

class Online_Exam_Question_Importer {
    
    private $allowed_mime_types = [
        'csv'  => 'text/csv',
        'json' => 'application/json',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    public function __construct() {
        add_action('admin_post_import_questions', [$this, 'handle_import']);
        add_action('wp_ajax_validate_import_file', [$this, 'ajax_validate_file']);
    }
    
    /**
     * Handle question import from uploaded file
     */
    public function handle_import() {
        // Verify nonce
        if (!isset($_POST['import_nonce']) || !wp_verify_nonce($_POST['import_nonce'], 'import_questions')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to import questions');
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('import_error', 'no_file', wp_get_referer()));
            exit;
        }
        
        $file = $_FILES['import_file'];
        $file_type = $this->get_file_type($file['name']);
        
        // Validate file type
        if (!$file_type || !in_array($file_type, array_keys($this->allowed_mime_types))) {
            wp_redirect(add_query_arg('import_error', 'invalid_type', wp_get_referer()));
            exit;
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_redirect(add_query_arg('import_error', 'file_too_large', wp_get_referer()));
            exit;
        }
        
        // Import based on file type
        $results = $this->import_file($file['tmp_name'], $file_type);
        
        // Store import results in transient for display
        set_transient('import_results_' . get_current_user_id(), $results, 60);
        
        // Redirect back with success message
        wp_redirect(add_query_arg('import_success', '1', wp_get_referer()));
        exit;
    }
    
    /**
     * Import file based on type
     */
    private function import_file($file_path, $file_type) {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            switch ($file_type) {
                case 'csv':
                    $data = $this->import_csv($file_path);
                    break;
                    
                case 'json':
                    $data = $this->import_json($file_path);
                    break;
                    
                case 'xls':
                case 'xlsx':
                    $data = $this->import_excel($file_path);
                    break;
                    
                default:
                    throw new Exception('Unsupported file format');
            }
            
            // Process and save questions
            $results = $this->save_questions($data, $results);
            
        } catch (Exception $e) {
            $results['errors'][] = 'Import failed: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Import CSV file
     */
    private function import_csv($file_path) {
        $data = [];
        $handle = fopen($file_path, 'r');
        
        if ($handle === false) {
            throw new Exception('Could not open CSV file');
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            fclose($handle);
            throw new Exception('Empty CSV file or invalid format');
        }
        
        // Normalize headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $item = array_combine($headers, $row);
                $data[] = $this->normalize_question_data($item);
            }
        }
        
        fclose($handle);
        return $data;
    }
    
    /**
     * Import JSON file
     */
    private function import_json($file_path) {
        $json_content = file_get_contents($file_path);
        
        if ($json_content === false) {
            throw new Exception('Could not read JSON file');
        }
        
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format: ' . json_last_error_msg());
        }
        
        // Normalize data structure
        if (isset($data['questions'])) {
            $data = $data['questions'];
        }
        
        return array_map([$this, 'normalize_question_data'], $data);
    }
    
    /**
     * Import Excel file
     */
    private function import_excel($file_path) {
        // Check if PHPExcel/PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new Exception('PhpSpreadsheet library not found. Please install it.');
        }
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];
        
        // Get headers from first row
        $headers = [];
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            $headers[] = strtolower(trim($worksheet->getCellByColumnAndRow($col, 1)->getValue()));
        }
        
        // Read data rows
        $highestRow = $worksheet->getHighestRow();
        
        for ($row = 2; $row <= $highestRow; ++$row) {
            $item = [];
            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $header = $headers[$col - 1];
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $item[$header] = $value;
            }
            
            if (!empty(array_filter($item))) {
                $data[] = $this->normalize_question_data($item);
            }
        }
        
        return $data;
    }
    
    /**
     * Normalize question data from different formats
     */
    private function normalize_question_data($item) {
        $normalized = [
            'question_text' => '',
            'question_type' => 'multiple_choice',
            'correct_answer' => '',
            'marks' => 1,
            'category_id' => 0,
            'difficulty' => 'medium',
            'explanation' => '',
            'options' => []
        ];
        
        // Map different column names to standard fields
        $field_mapping = [
            'question_text' => ['question', 'question_text', 'text', 'query'],
            'question_type' => ['type', 'question_type', 'qtype'],
            'correct_answer' => ['correct_answer', 'answer', 'correct', 'solution'],
            'marks' => ['marks', 'points', 'score'],
            'category_id' => ['category', 'category_id', 'topic', 'subject'],
            'difficulty' => ['difficulty', 'level'],
            'explanation' => ['explanation', 'solution_explanation', 'hint']
        ];
        
        foreach ($field_mapping as $standard_field => $possible_fields) {
            foreach ($possible_fields as $field) {
                if (isset($item[$field]) && !empty(trim($item[$field]))) {
                    $normalized[$standard_field] = trim($item[$field]);
                    break;
                }
            }
        }
        
        // Process options for multiple choice
        if ($normalized['question_type'] === 'multiple_choice') {
            $normalized['options'] = $this->extract_options($item);
        }
        
        // Convert category name to ID if needed
        if (!is_numeric($normalized['category_id']) && !empty($normalized['category_id'])) {
            $normalized['category_id'] = $this->get_or_create_category($normalized['category_id']);
        }
        
        return $normalized;
    }
    
    /**
     * Extract options from data row
     */
    private function extract_options($item) {
        $options = [];
        
        // Look for option fields (option_a, option_b, etc.)
        foreach ($item as $key => $value) {
            if (preg_match('/^option_([a-z])$/i', $key, $matches) && !empty(trim($value))) {
                $option_letter = strtoupper($matches[1]);
                $options[] = [
                    'letter' => $option_letter,
                    'text' => trim($value)
                ];
            }
        }
        
        // Alternative: options in a single column separated by semicolons
        if (empty($options) && isset($item['options']) && !empty($item['options'])) {
            $option_list = explode(';', $item['options']);
            $letters = range('A', 'Z');
            
            foreach ($option_list as $index => $option_text) {
                if (!empty(trim($option_text))) {
                    $options[] = [
                        'letter' => $letters[$index],
                        'text' => trim($option_text)
                    ];
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Get or create category
     */
    private function get_or_create_category($category_name) {
        global $wpdb;
        
        // Check if category exists
        $category_id = $wpdb->get_var($wpdb->prepare(
            "SELECT category_id FROM {$wpdb->prefix}exam_categories WHERE category_name = %s",
            $category_name
        ));
        
        // Create if doesn't exist
        if (!$category_id) {
            $wpdb->insert(
                "{$wpdb->prefix}exam_categories",
                [
                    'category_name' => $category_name,
                    'description' => '',
                    'parent_id' => 0,
                    'created_at' => current_time('mysql')
                ]
            );
            $category_id = $wpdb->insert_id;
        }
        
        return $category_id;
    }
    
    /**
     * Save questions to database
     */
    private function save_questions($questions, $results) {
        global $wpdb;
        
        foreach ($questions as $index => $question) {
            $results['total']++;
            
            try {
                // Validate required fields
                if (empty($question['question_text'])) {
                    throw new Exception('Question text is required');
                }
                
                if ($question['question_type'] === 'multiple_choice' && empty($question['options'])) {
                    throw new Exception('Multiple choice questions require options');
                }
                
                // Insert question
                $wpdb->insert(
                    "{$wpdb->prefix}exam_questions",
                    [
                        'question_text' => wp_kses_post($question['question_text']),
                        'question_type' => sanitize_text_field($question['question_type']),
                        'correct_answer' => sanitize_text_field($question['correct_answer']),
                        'marks' => intval($question['marks']),
                        'category_id' => intval($question['category_id']),
                        'difficulty' => sanitize_text_field($question['difficulty']),
                        'explanation' => wp_kses_post($question['explanation']),
                        'created_by' => get_current_user_id(),
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql')
                    ]
                );
                
                $question_id = $wpdb->insert_id;
                
                // Save options for multiple choice
                if ($question['question_type'] === 'multiple_choice' && !empty($question['options'])) {
                    foreach ($question['options'] as $option) {
                        $wpdb->insert(
                            "{$wpdb->prefix}exam_question_options",
                            [
                                'question_id' => $question_id,
                                'option_letter' => sanitize_text_field($option['letter']),
                                'option_text' => wp_kses_post($option['text'])
                            ]
                        );
                    }
                }
                
                $results['success']++;
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = sprintf('Row %d: %s', $index + 1, $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Get file type from extension
     */
    private function get_file_type($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $extension;
    }
    
    /**
     * AJAX file validation
     */
    public function ajax_validate_file() {
        check_ajax_referer('import_questions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $file_type = sanitize_text_field($_POST['file_type']);
        
        $response = [
            'valid' => in_array($file_type, array_keys($this->allowed_mime_types)),
            'max_size' => '5MB',
            'supported_formats' => array_keys($this->allowed_mime_types)
        ];
        
        wp_send_json_success($response);
    }
    
    /**
     * Display import results
     */
    public static function display_import_results() {
        $user_id = get_current_user_id();
        $results = get_transient('import_results_' . $user_id);
        
        if ($results) {
            delete_transient('import_results_' . $user_id);
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Import completed!</strong></p>';
            echo '<p>Total processed: ' . $results['total'] . '</p>';
            echo '<p>Successfully imported: ' . $results['success'] . '</p>';
            echo '<p>Failed: ' . $results['failed'] . '</p>';
            
            if (!empty($results['errors'])) {
                echo '<div class="error-details" style="margin-top: 10px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb;">';
                echo '<p><strong>Errors:</strong></p>';
                echo '<ul style="margin: 0;">';
                foreach ($results['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Render import form
     */
    public static function render_import_form() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import Questions', 'online-exam'); ?></h1>
            
            <div class="import-instructions">
                <div class="notice notice-info">
                    <p><strong><?php _e('Supported formats:', 'online-exam'); ?></strong></p>
                    <ul>
                        <li><?php _e('CSV (Comma Separated Values)', 'online-exam'); ?></li>
                        <li><?php _e('JSON (JavaScript Object Notation)', 'online-exam'); ?></li>
                        <li><?php _e('Excel (.xls, .xlsx)', 'online-exam'); ?></li>
                    </ul>
                    <p><strong><?php _e('Maximum file size:', 'online-exam'); ?></strong> 5MB</p>
                    <p><a href="<?php echo plugin_dir_url(__FILE__) . 'templates/sample.csv'; ?>" download>
                        <?php _e('Download CSV sample template', 'online-exam'); ?>
                    </a></p>
                </div>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="import-form">
                <input type="hidden" name="action" value="import_questions">
                <?php wp_nonce_field('import_questions', 'import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_file"><?php _e('Select File', 'online-exam'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".csv,.json,.xls,.xlsx" required>
                            <p class="description"><?php _e('Choose a CSV, JSON, or Excel file to import', 'online-exam'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="import_mode"><?php _e('Import Mode', 'online-exam'); ?></label>
                        </th>
                        <td>
                            <select name="import_mode" id="import_mode">
                                <option value="add_new"><?php _e('Add new questions only', 'online-exam'); ?></option>
                                <option value="update_existing"><?php _e('Update existing questions', 'online-exam'); ?></option>
                                <option value="replace_all"><?php _e('Replace all questions', 'online-exam'); ?></option>
                            </select>
                            <p class="description"><?php _e('Choose how to handle existing questions', 'online-exam'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_category"><?php _e('Default Category', 'online-exam'); ?></label>
                        </th>
                        <td>
                            <select name="default_category" id="default_category">
                                <option value="0"><?php _e('Use category from file', 'online-exam'); ?></option>
                                <?php
                                $categories = $GLOBALS['wpdb']->get_results("SELECT * FROM {$GLOBALS['wpdb']->prefix}exam_categories");
                                foreach ($categories as $category) {
                                    echo '<option value="' . $category->category_id . '">' . esc_html($category->category_name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Apply this category if not specified in file', 'online-exam'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Import Questions', 'online-exam'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=online-exam-questions'); ?>" class="button">
                        <?php _e('Cancel', 'online-exam'); ?>
                    </a>
                </p>
            </form>
            
            <div class="file-format-info">
                <h3><?php _e('File Format Requirements', 'online-exam'); ?></h3>
                
                <h4><?php _e('CSV/Excel Format:', 'online-exam'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Column Name', 'online-exam'); ?></th>
                            <th><?php _e('Description', 'online-exam'); ?></th>
                            <th><?php _e('Required', 'online-exam'); ?></th>
                            <th><?php _e('Example', 'online-exam'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>question_text</td>
                            <td><?php _e('The question text', 'online-exam'); ?></td>
                            <td><?php _e('Yes', 'online-exam'); ?></td>
                            <td>What is the capital of France?</td>
                        </tr>
                        <tr>
                            <td>question_type</td>
                            <td><?php _e('Type of question', 'online-exam'); ?></td>
                            <td><?php _e('No', 'online-exam'); ?></td>
                            <td>multiple_choice, true_false, short_answer</td>
                        </tr>
                        <tr>
                            <td>option_a, option_b, etc.</td>
                            <td><?php _e('Multiple choice options', 'online-exam'); ?></td>
                            <td><?php _e('For MCQs', 'online-exam'); ?></td>
                            <td>Paris, London, Berlin, Madrid</td>
                        </tr>
                        <tr>
                            <td>correct_answer</td>
                            <td><?php _e('Correct answer', 'online-exam'); ?></td>
                            <td><?php _e('Yes', 'online-exam'); ?></td>
                            <td>A (for MCQ) or "Paris"</td>
                        </tr>
                        <tr>
                            <td>marks</td>
                            <td><?php _e('Marks allocated', 'online-exam'); ?></td>
                            <td><?php _e('No', 'online-exam'); ?></td>
                            <td>1, 2, 5</td>
                        </tr>
                        <tr>
                            <td>category</td>
                            <td><?php _e('Category name', 'online-exam'); ?></td>
                            <td><?php _e('No', 'online-exam'); ?></td>
                            <td>Geography, Math, Science</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4><?php _e('JSON Format:', 'online-exam'); ?></h4>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd;">
{
    "questions": [
        {
            "question_text": "What is the capital of France?",
            "question_type": "multiple_choice",
            "options": [
                {"letter": "A", "text": "Paris"},
                {"letter": "B", "text": "London"},
                {"letter": "C", "text": "Berlin"}
            ],
            "correct_answer": "A",
            "marks": 1,
            "category": "Geography",
            "difficulty": "easy",
            "explanation": "Paris is the capital and most populous city of France."
        }
    ]
}
                </pre>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // File validation
            $('#import_file').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var fileSize = file.size / 1024 / 1024; // in MB
                    if (fileSize > 5) {
                        alert('File size exceeds 5MB limit.');
                        $(this).val('');
                    }
                    
                    // Validate file extension
                    var fileName = file.name;
                    var extension = fileName.split('.').pop().toLowerCase();
                    var allowed = ['csv', 'json', 'xls', 'xlsx'];
                    
                    if ($.inArray(extension, allowed) === -1) {
                        alert('Please select a valid file (CSV, JSON, XLS, XLSX).');
                        $(this).val('');
                    }
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize if needed
function init_question_importer() {
    new Online_Exam_Question_Importer();
}
add_action('init', 'init_question_importer');