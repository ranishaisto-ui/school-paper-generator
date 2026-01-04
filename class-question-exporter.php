<?php
// includes/class-question-exporter.php

if (!defined('ABSPATH')) {
    exit;
}

class Online_Exam_Question_Exporter {
    
    public function __construct() {
        add_action('admin_post_export_questions', [$this, 'handle_export']);
        add_action('wp_ajax_generate_export_preview', [$this, 'ajax_export_preview']);
    }
    
    /**
     * Handle export request
     */
    public function handle_export() {
        // Verify nonce
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_questions')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to export questions');
        }
        
        // Get export parameters
        $format = sanitize_text_field($_POST['export_format'] ?? 'csv');
        $category_id = intval($_POST['category_id'] ?? 0);
        $question_type = sanitize_text_field($_POST['question_type'] ?? '');
        $difficulty = sanitize_text_field($_POST['difficulty'] ?? '');
        
        // Generate export
        $this->generate_export($format, [
            'category_id' => $category_id,
            'question_type' => $question_type,
            'difficulty' => $difficulty,
            'include_options' => isset($_POST['include_options']),
            'include_explanations' => isset($_POST['include_explanations'])
        ]);
    }
    
    /**
     * Generate export file
     */
    private function generate_export($format, $params) {
        global $wpdb;
        
        // Build query
        $where = ['1=1'];
        $query_params = [];
        
        if ($params['category_id'] > 0) {
            $where[] = 'q.category_id = %d';
            $query_params[] = $params['category_id'];
        }
        
        if (!empty($params['question_type'])) {
            $where[] = 'q.question_type = %s';
            $query_params[] = $params['question_type'];
        }
        
        if (!empty($params['difficulty'])) {
            $where[] = 'q.difficulty = %s';
            $query_params[] = $params['difficulty'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get questions with optional joins
        $query = "
            SELECT 
                q.*,
                c.category_name,
                u.display_name as created_by_name
            FROM {$wpdb->prefix}exam_questions q
            LEFT JOIN {$wpdb->prefix}exam_categories c ON q.category_id = c.category_id
            LEFT JOIN {$wpdb->users} u ON q.created_by = u.ID
            WHERE {$where_clause}
            ORDER BY q.category_id, q.question_id
        ";
        
        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, $query_params);
        }
        
        $questions = $wpdb->get_results($query);
        
        // Get options for multiple choice questions
        if ($params['include_options']) {
            $question_ids = array_map(function($q) { return $q->question_id; }, $questions);
            
            if (!empty($question_ids)) {
                $options = $wpdb->get_results("
                    SELECT * FROM {$wpdb->prefix}exam_question_options 
                    WHERE question_id IN (" . implode(',', $question_ids) . ")
                    ORDER BY question_id, option_letter
                ");
                
                // Group options by question
                $options_by_question = [];
                foreach ($options as $option) {
                    $options_by_question[$option->question_id][] = $option;
                }
            }
        }
        
        // Prepare data based on format
        switch ($format) {
            case 'csv':
                $this->export_csv($questions, $options_by_question ?? [], $params);
                break;
                
            case 'json':
                $this->export_json($questions, $options_by_question ?? [], $params);
                break;
                
            case 'excel':
                $this->export_excel($questions, $options_by_question ?? [], $params);
                break;
                
            case 'pdf':
                $this->export_pdf($questions, $params);
                break;
                
            default:
                wp_die('Unsupported export format');
        }
    }
    
    /**
     * Export to CSV
     */
    private function export_csv($questions, $options_by_question, $params) {
        $filename = 'questions_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        $headers = [
            'question_id',
            'question_text',
            'question_type',
            'correct_answer',
            'marks',
            'category',
            'difficulty',
            'explanation',
            'created_by',
            'created_at'
        ];
        
        if ($params['include_options']) {
            $headers[] = 'options';
        }
        
        fputcsv($output, $headers);
        
        // Write data
        foreach ($questions as $question) {
            $row = [
                $question->question_id,
                strip_tags($question->question_text),
                $question->question_type,
                $question->correct_answer,
                $question->marks,
                $question->category_name,
                $question->difficulty,
                $params['include_explanations'] ? strip_tags($question->explanation) : '',
                $question->created_by_name,
                $question->created_at
            ];
            
            if ($params['include_options'] && isset($options_by_question[$question->question_id])) {
                $options_text = [];
                foreach ($options_by_question[$question->question_id] as $option) {
                    $options_text[] = $option->option_letter . ': ' . strip_tags($option->option_text);
                }
                $row[] = implode('; ', $options_text);
            } else {
                $row[] = '';
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export to JSON
     */
    private function export_json($questions, $options_by_question, $params) {
        $filename = 'questions_export_' . date('Y-m-d_H-i-s') . '.json';
        
        $export_data = [
            'export_date' => current_time('mysql'),
            'total_questions' => count($questions),
            'export_params' => $params,
            'questions' => []
        ];
        
        foreach ($questions as $question) {
            $question_data = [
                'question_id' => $question->question_id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'correct_answer' => $question->correct_answer,
                'marks' => $question->marks,
                'category' => $question->category_name,
                'difficulty' => $question->difficulty,
                'created_by' => $question->created_by_name,
                'created_at' => $question->created_at
            ];
            
            if ($params['include_explanations']) {
                $question_data['explanation'] = $question->explanation;
            }
            
            if ($params['include_options'] && isset($options_by_question[$question->question_id])) {
                $question_data['options'] = [];
                foreach ($options_by_question[$question->question_id] as $option) {
                    $question_data['options'][] = [
                        'letter' => $option->option_letter,
                        'text' => $option->option_text
                    ];
                }
            }
            
            $export_data['questions'][] = $question_data;
        }
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Export to Excel
     */
    private function export_excel($questions, $options_by_question, $params) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            wp_die('PhpSpreadsheet library required for Excel export');
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = ['ID', 'Question Text', 'Type', 'Correct Answer', 'Marks', 'Category', 'Difficulty', 'Explanation', 'Created By', 'Created At'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col++, 1, $header);
            $sheet->getStyleByColumnAndRow($col - 1, 1)->getFont()->setBold(true);
        }
        
        if ($params['include_options']) {
            $sheet->setCellValueByColumnAndRow($col, 1, 'Options');
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);
        }
        
        // Add data
        $row = 2;
        foreach ($questions as $question) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->question_id);
            $sheet->setCellValueByColumnAndRow($col++, $row, strip_tags($question->question_text));
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->question_type);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->correct_answer);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->marks);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->category_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->difficulty);
            $sheet->setCellValueByColumnAndRow($col++, $row, $params['include_explanations'] ? strip_tags($question->explanation) : '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->created_by_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->created_at);
            
            if ($params['include_options']) {
                $options_text = '';
                if (isset($options_by_question[$question->question_id])) {
                    foreach ($options_by_question[$question->question_id] as $option) {
                        $options_text .= $option->option_letter . ': ' . strip_tags($option->option_text) . "\n";
                    }
                }
                $sheet->setCellValueByColumnAndRow($col, $row, trim($options_text));
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setWrapText(true);
            }
            
            $row++;
        }
        
        // Auto-size columns
        for ($i = 1; $i <= $col; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        
        // Set filename
        $filename = 'questions_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Export to PDF
     */
    private function export_pdf($questions, $params) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            wp_die('TCPDF library required for PDF export');
        }
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle('Questions Export');
        $pdf->SetSubject('Exam Questions');
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Add title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Questions Export', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Add export info
        $pdf->SetFont('helvetica', '', 10);
        $info = 'Exported on: ' . date('F j, Y H:i:s') . "\n";
        $info .= 'Total Questions: ' . count($questions) . "\n";
        if ($params['category_id']) {
            $info .= 'Category: ' . $this->get_category_name($params['category_id']) . "\n";
        }
        $pdf->MultiCell(0, 10, $info, 0, 'L');
        $pdf->Ln(10);
        
        // Add questions
        $pdf->SetFont('helvetica', '', 11);
        
        $counter = 1;
        foreach ($questions as $question) {
            // Question number
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Question ' . $counter++, 0, 1, 'L');
            
            // Question text
            $pdf->SetFont('helvetica', '', 11);
            $question_text = strip_tags($question->question_text);
            $pdf->MultiCell(0, 10, $question_text, 0, 'L');
            
            // Question meta
            $meta = 'Type: ' . $question->question_type . ' | ';
            $meta .= 'Marks: ' . $question->marks . ' | ';
            $meta .= 'Category: ' . $question->category_name . ' | ';
            $meta .= 'Difficulty: ' . $question->difficulty;
            
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->MultiCell(0, 10, $meta, 0, 'L');
            
            // Correct answer
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 10, 'Correct Answer: ' . $question->correct_answer, 0, 1, 'L');
            
            // Explanation if included
            if ($params['include_explanations'] && !empty($question->explanation)) {
                $pdf->SetFont('helvetica', '', 10);
                $pdf->MultiCell(0, 10, 'Explanation: ' . strip_tags($question->explanation), 0, 'L');
            }
            
            $pdf->Ln(8);
            
            // Add page break if needed
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
        }
        
        // Output PDF
        $filename = 'questions_export_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
    
    /**
     * AJAX export preview
     */
    public function ajax_export_preview() {
        check_ajax_referer('export_questions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $category_id = intval($_POST['category_id'] ?? 0);
        
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}exam_questions WHERE category_id = %d",
            $category_id
        ));
        
        $response = [
            'count' => $count,
            'category_name' => $this->get_category_name($category_id)
        ];
        
        wp_send_json_success($response);
    }
    
    /**
     * Get category name
     */
    private function get_category_name($category_id) {
        global $wpdb;
        
        if ($category_id === 0) {
            return 'All Categories';
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT category_name FROM {$wpdb->prefix}exam_categories WHERE category_id = %d",
            $category_id
        )) ?? 'Unknown Category';
    }
    
    /**
     * Render export form
     */
    public static function render_export_form() {
        global $wpdb;
        
        // Get categories for dropdown
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}exam_categories ORDER BY category_name");
        
        // Get unique question types
        $question_types = $wpdb->get_col("SELECT DISTINCT question_type FROM {$wpdb->prefix}exam_questions");
        
        // Get unique difficulty levels
        $difficulties = $wpdb->get_col("SELECT DISTINCT difficulty FROM {$wpdb->prefix}exam_questions");
        ?>
        
        <div class="wrap">
            <h1><?php _e('Export Questions', 'online-exam'); ?></h1>
            
            <div class="export-instructions">
                <div class="notice notice-info">
                    <p><?php _e('Export your questions to various formats for backup, migration, or offline use.', 'online-exam'); ?></p>
                </div>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="export-form">
                <input type="hidden" name="action" value="export_questions">
                <?php wp_nonce_field('export_questions', 'export_nonce'); ?>
                
                <div class="export-options-card">
                    <h2><?php _e('Export Options', 'online-exam'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="export_format"><?php _e('Export Format', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="export_format" id="export_format" class="regular-text">
                                    <option value="csv">CSV (Comma Separated Values)</option>
                                    <option value="json">JSON (Recommended for backup)</option>
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="pdf">PDF (Printable format)</option>
                                </select>
                                <p class="description"><?php _e('Choose the format for your exported file', 'online-exam'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="category_id"><?php _e('Category Filter', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="category_id" id="category_id" class="regular-text">
                                    <option value="0"><?php _e('All Categories', 'online-exam'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->category_id; ?>">
                                            <?php echo esc_html($category->category_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="preview_export" class="button button-small">
                                    <?php _e('Preview Count', 'online-exam'); ?>
                                </button>
                                <div id="preview_result" style="display: none; margin-top: 5px;"></div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="question_type"><?php _e('Question Type', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="question_type" id="question_type" class="regular-text">
                                    <option value=""><?php _e('All Types', 'online-exam'); ?></option>
                                    <?php foreach ($question_types as $type): ?>
                                        <option value="<?php echo esc_attr($type); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="difficulty"><?php _e('Difficulty Level', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="difficulty" id="difficulty" class="regular-text">
                                    <option value=""><?php _e('All Levels', 'online-exam'); ?></option>
                                    <?php foreach ($difficulties as $difficulty): ?>
                                        <option value="<?php echo esc_attr($difficulty); ?>">
                                            <?php echo ucfirst($difficulty); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Include Options', 'online-exam'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_options" value="1" checked>
                                    <?php _e('Include multiple choice options (for MCQ questions)', 'online-exam'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Include Explanations', 'online-exam'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_explanations" value="1">
                                    <?php _e('Include question explanations', 'online-exam'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export Questions', 'online-exam'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=online-exam-questions'); ?>" class="button button-large">
                            <?php _e('Cancel', 'online-exam'); ?>
                        </a>
                    </p>
                </div>
            </form>
            
            <div class="export-notes">
                <h3><?php _e('Export Notes', 'online-exam'); ?></h3>
                <ul>
                    <li><?php _e('CSV format is compatible with spreadsheet software like Excel, Google Sheets, etc.', 'online-exam'); ?></li>
                    <li><?php _e('JSON format preserves all question data and is recommended for backups.', 'online-exam'); ?></li>
                    <li><?php _e('Excel format provides better formatting for complex data.', 'online-exam'); ?></li>
                    <li><?php _e('PDF format creates a printable document suitable for paper-based exams.', 'online-exam'); ?></li>
                    <li><?php _e('Large exports may take some time to process. Please be patient.', 'online-exam'); ?></li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Preview export count
            $('#preview_export').on('click', function() {
                var categoryId = $('#category_id').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_export_preview',
                        category_id: categoryId,
                        nonce: '<?php echo wp_create_nonce('export_questions'); ?>'
                    },
                    beforeSend: function() {
                        $('#preview_result').html('<span class="spinner is-active"></span> Loading...').show();
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="notice notice-success inline">';
                            html += '<p><strong>' + response.data.count + '</strong> questions found';
                            if (categoryId != 0) {
                                html += ' in <em>' + response.data.category_name + '</em>';
                            }
                            html += '</p></div>';
                            $('#preview_result').html(html).show();
                        } else {
                            $('#preview_result').html('<div class="notice notice-error inline"><p>Error loading preview</p></div>').show();
                        }
                    },
                    error: function() {
                        $('#preview_result').html('<div class="notice notice-error inline"><p>Error loading preview</p></div>').show();
                    }
                });
            });
            
            // Auto-preview on category change
            $('#category_id').on('change', function() {
                $('#preview_result').hide();
            });
            
            // Format-specific options
            $('#export_format').on('change', function() {
                var format = $(this).val();
                
                if (format === 'pdf') {
                    $('input[name="include_options"]').prop('checked', false);
                    $('input[name="include_options"]').prop('disabled', true);
                } else {
                    $('input[name="include_options"]').prop('disabled', false);
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize if needed
function init_question_exporter() {
    new Online_Exam_Question_Exporter();
}
add_action('init', 'init_question_exporter');