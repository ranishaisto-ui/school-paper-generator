<?php
// includes/class-paper-exporter.php

if (!defined('ABSPATH')) {
    exit;
}

class Online_Exam_Paper_Exporter {
    
    private $paper_model;
    private $question_model;
    
    public function __construct() {
        // Initialize models if they exist
        if (class_exists('Online_Exam_Paper_Model')) {
            $this->paper_model = new Online_Exam_Paper_Model();
        }
        
        if (class_exists('Online_Exam_Question_Model')) {
            $this->question_model = new Online_Exam_Question_Model();
        }
        
        add_action('admin_post_export_paper', [$this, 'handle_export']);
        add_action('wp_ajax_generate_paper_preview', [$this, 'ajax_generate_preview']);
    }
    
    /**
     * Handle paper export request
     */
    public function handle_export() {
        // Verify nonce
        if (!isset($_POST['export_paper_nonce']) || !wp_verify_nonce($_POST['export_paper_nonce'], 'export_paper')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to export papers');
        }
        
        // Get parameters
        $paper_id = intval($_POST['paper_id'] ?? 0);
        $export_format = sanitize_text_field($_POST['export_format'] ?? 'pdf');
        $include_answer_key = isset($_POST['include_answer_key']);
        $include_instructions = isset($_POST['include_instructions']);
        $include_marks = isset($_POST['include_marks']);
        
        if ($paper_id === 0) {
            wp_die('Invalid paper ID');
        }
        
        // Generate export
        $this->export_paper($paper_id, [
            'format' => $export_format,
            'include_answer_key' => $include_answer_key,
            'include_instructions' => $include_instructions,
            'include_marks' => $include_marks,
            'student_version' => isset($_POST['student_version'])
        ]);
    }
    
    /**
     * Export paper in specified format
     */
    private function export_paper($paper_id, $options) {
        // Get paper data
        $paper = $this->get_paper_data($paper_id);
        
        if (!$paper) {
            wp_die('Paper not found');
        }
        
        // Get questions for this paper
        $questions = $this->get_paper_questions($paper_id);
        
        // Generate filename
        $filename = sanitize_title($paper->title) . '_' . date('Y-m-d') . '_' . $options['format'];
        
        switch ($options['format']) {
            case 'pdf':
                $this->export_to_pdf($paper, $questions, $options, $filename);
                break;
                
            case 'word':
                $this->export_to_word($paper, $questions, $options, $filename);
                break;
                
            case 'excel':
                $this->export_to_excel($paper, $questions, $options, $filename);
                break;
                
            case 'html':
                $this->export_to_html($paper, $questions, $options, $filename);
                break;
                
            default:
                wp_die('Unsupported export format');
        }
    }
    
    /**
     * Get paper data
     */
    private function get_paper_data($paper_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exam_papers WHERE paper_id = %d",
            $paper_id
        ));
    }
    
    /**
     * Get paper questions with details
     */
    private function get_paper_questions($paper_id) {
        global $wpdb;
        
        $query = "
            SELECT 
                pq.*,
                q.question_text,
                q.question_type,
                q.correct_answer,
                q.marks,
                q.difficulty,
                q.explanation,
                c.category_name
            FROM {$wpdb->prefix}exam_paper_questions pq
            LEFT JOIN {$wpdb->prefix}exam_questions q ON pq.question_id = q.question_id
            LEFT JOIN {$wpdb->prefix}exam_categories c ON q.category_id = c.category_id
            WHERE pq.paper_id = %d
            ORDER BY pq.question_order
        ";
        
        $questions = $wpdb->get_results($wpdb->prepare($query, $paper_id));
        
        // Get options for multiple choice questions
        foreach ($questions as &$question) {
            if ($question->question_type === 'multiple_choice') {
                $question->options = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}exam_question_options 
                     WHERE question_id = %d ORDER BY option_letter",
                    $question->question_id
                ));
            }
        }
        
        return $questions;
    }
    
    /**
     * Export to PDF
     */
    private function export_to_pdf($paper, $questions, $options, $filename) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            wp_die('TCPDF library required for PDF export. Please install it.');
        }
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle($paper->title);
        $pdf->SetSubject('Exam Paper');
        $pdf->SetKeywords('Exam, Paper, Test, Questions');
        
        // Set margins
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Add first page
        $pdf->AddPage();
        
        // Generate HTML content
        $html = $this->generate_paper_html($paper, $questions, $options, true);
        
        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        $pdf->Output($filename . '.pdf', 'D');
        exit;
    }
    
    /**
     * Export to Word (DOCX)
     */
    private function export_to_word($paper, $questions, $options, $filename) {
        // Check if PHPWord is available
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            wp_die('PHPWord library required for Word export. Please install it.');
        }
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Add a section
        $section = $phpWord->addSection();
        
        // Add title
        $section->addText($paper->title, ['bold' => true, 'size' => 16]);
        $section->addTextBreak(2);
        
        // Add paper details
        $details = [];
        if ($paper->duration) {
            $details[] = "Duration: " . $this->format_duration($paper->duration);
        }
        if ($paper->total_marks) {
            $details[] = "Total Marks: " . $paper->total_marks;
        }
        if ($paper->passing_marks) {
            $details[] = "Passing Marks: " . $paper->passing_marks;
        }
        
        if (!empty($details)) {
            foreach ($details as $detail) {
                $section->addText($detail);
            }
            $section->addTextBreak(1);
        }
        
        // Add instructions if requested
        if ($options['include_instructions'] && !empty($paper->instructions)) {
            $section->addText("Instructions:", ['bold' => true]);
            $section->addText(strip_tags($paper->instructions));
            $section->addTextBreak(2);
        }
        
        // Add questions
        $question_num = 1;
        foreach ($questions as $question) {
            // Question number and text
            $question_text = "Q{$question_num}. " . strip_tags($question->question_text);
            $section->addText($question_text);
            
            // Add marks if requested
            if ($options['include_marks']) {
                $section->addText("[" . $question->marks . " marks]", ['italic' => true]);
            }
            
            // Add options for multiple choice
            if ($question->question_type === 'multiple_choice' && isset($question->options)) {
                foreach ($question->options as $option) {
                    $section->addText("  " . $option->option_letter . ") " . strip_tags($option->option_text));
                }
            }
            
            // Add answer space for subjective questions
            if ($question->question_type === 'short_answer' || $question->question_type === 'essay') {
                $section->addText("Answer: ________________________________________________");
                $section->addTextBreak(1);
            }
            
            // Add correct answer if answer key requested
            if ($options['include_answer_key']) {
                $answer_text = "Correct Answer: " . $question->correct_answer;
                if ($question->explanation) {
                    $answer_text .= " (" . strip_tags($question->explanation) . ")";
                }
                $section->addText($answer_text, ['color' => '006600', 'italic' => true]);
            }
            
            $section->addTextBreak(2);
            $question_num++;
        }
        
        // Save file
        $temp_file = tempnam(sys_get_temp_dir(), 'word_') . '.docx';
        $phpWord->save($temp_file);
        
        // Output file
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '.docx"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($temp_file));
        
        readfile($temp_file);
        unlink($temp_file);
        exit;
    }
    
    /**
     * Export to Excel
     */
    private function export_to_excel($paper, $questions, $options, $filename) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            wp_die('PhpSpreadsheet library required for Excel export');
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set title
        $sheet->setCellValue('A1', $paper->title);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        // Paper details
        $row = 3;
        $sheet->setCellValue('A' . $row, 'Duration:');
        $sheet->setCellValue('B' . $row, $this->format_duration($paper->duration));
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Marks:');
        $sheet->setCellValue('B' . $row, $paper->total_marks);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Passing Marks:');
        $sheet->setCellValue('B' . $row, $paper->passing_marks);
        
        // Instructions
        if ($options['include_instructions'] && !empty($paper->instructions)) {
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Instructions:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, strip_tags($paper->instructions));
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
        }
        
        // Questions header
        $row += 3;
        $headers = ['Q#', 'Question', 'Type', 'Marks', 'Difficulty', 'Correct Answer'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, $row, $header);
            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
            $col++;
        }
        
        // Questions data
        $question_num = 1;
        $row++;
        $start_questions_row = $row;
        
        foreach ($questions as $question) {
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $row, $question_num);
            $sheet->setCellValueByColumnAndRow($col++, $row, strip_tags($question->question_text));
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->question_type);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->marks);
            $sheet->setCellValueByColumnAndRow($col++, $row, $question->difficulty);
            
            // Add correct answer if requested
            if ($options['include_answer_key']) {
                $sheet->setCellValueByColumnAndRow($col, $row, $question->correct_answer);
                
                // Add explanation as comment
                if ($question->explanation) {
                    $sheet->getCommentByColumnAndRow($col, $row)
                          ->getText()
                          ->createTextRun(strip_tags($question->explanation));
                }
            }
            
            // Add options for multiple choice
            if ($question->question_type === 'multiple_choice' && isset($question->options)) {
                $options_text = '';
                foreach ($question->options as $option) {
                    $options_text .= $option->option_letter . ') ' . strip_tags($option->option_text) . "\n";
                }
                
                $comment_col = $col + 1;
                $sheet->setCellValueByColumnAndRow($comment_col, $row, 'Options:');
                $sheet->getCommentByColumnAndRow($comment_col, $row)
                      ->getText()
                      ->createTextRun(trim($options_text));
            }
            
            $row++;
            $question_num++;
        }
        
        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Add borders to questions table
        $end_questions_row = $row - 1;
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ];
        $sheet->getStyle('A' . ($start_questions_row - 1) . ':F' . $end_questions_row)
              ->applyFromArray($styleArray);
        
        // Output Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Export to HTML
     */
    private function export_to_html($paper, $questions, $options, $filename) {
        $html = $this->generate_paper_html($paper, $questions, $options, false);
        
        header('Content-Type: text/html');
        header('Content-Disposition: attachment;filename="' . $filename . '.html"');
        
        echo $html;
        exit;
    }
    
    /**
     * Generate HTML for paper
     */
    private function generate_paper_html($paper, $questions, $options, $for_pdf = false) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($paper->title); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .paper-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .paper-title {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .paper-meta {
                    display: flex;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    margin-top: 20px;
                }
                .paper-meta-item {
                    margin: 5px 0;
                }
                .paper-meta-label {
                    font-weight: bold;
                    margin-right: 5px;
                }
                .instructions {
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin-bottom: 30px;
                    border-radius: 4px;
                }
                .instructions h3 {
                    margin-top: 0;
                }
                .question {
                    margin-bottom: 30px;
                    page-break-inside: avoid;
                }
                .question-number {
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .question-text {
                    margin-bottom: 15px;
                }
                .question-meta {
                    font-size: 0.9em;
                    color: #666;
                    margin-bottom: 10px;
                }
                .options {
                    margin-left: 20px;
                }
                .option {
                    margin-bottom: 8px;
                }
                .option-letter {
                    font-weight: bold;
                    margin-right: 10px;
                }
                .answer-space {
                    border-bottom: 1px dashed #000;
                    min-width: 300px;
                    display: inline-block;
                    height: 20px;
                    margin-left: 10px;
                }
                .answer-key {
                    background: #e8f5e8;
                    border: 1px solid #4CAF50;
                    padding: 10px;
                    margin-top: 10px;
                    border-radius: 4px;
                }
                .answer-key-title {
                    font-weight: bold;
                    color: #2E7D32;
                }
                .page-break {
                    page-break-before: always;
                }
                @media print {
                    .no-print {
                        display: none;
                    }
                    .page-break {
                        page-break-before: always;
                    }
                }
            </style>
        </head>
        <body>
            <div class="paper-header">
                <div class="paper-title"><?php echo esc_html($paper->title); ?></div>
                
                <div class="paper-meta">
                    <?php if ($paper->duration): ?>
                        <div class="paper-meta-item">
                            <span class="paper-meta-label">Duration:</span>
                            <?php echo $this->format_duration($paper->duration); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($paper->total_marks): ?>
                        <div class="paper-meta-item">
                            <span class="paper-meta-label">Total Marks:</span>
                            <?php echo $paper->total_marks; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($paper->passing_marks): ?>
                        <div class="paper-meta-item">
                            <span class="paper-meta-label">Passing Marks:</span>
                            <?php echo $paper->passing_marks; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($options['include_instructions'] && !empty($paper->instructions)): ?>
                <div class="instructions">
                    <h3>Instructions:</h3>
                    <?php echo wpautop($paper->instructions); ?>
                </div>
            <?php endif; ?>
            
            <div class="questions-container">
                <?php $question_num = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question">
                        <div class="question-number">Question <?php echo $question_num; ?></div>
                        
                        <div class="question-text">
                            <?php echo wpautop($question->question_text); ?>
                        </div>
                        
                        <div class="question-meta">
                            <?php if ($options['include_marks']): ?>
                                <span>Marks: <?php echo $question->marks; ?></span> | 
                            <?php endif; ?>
                            <span>Type: <?php echo ucfirst(str_replace('_', ' ', $question->question_type)); ?></span> | 
                            <span>Difficulty: <?php echo ucfirst($question->difficulty); ?></span>
                        </div>
                        
                        <?php if ($question->question_type === 'multiple_choice' && isset($question->options)): ?>
                            <div class="options">
                                <?php foreach ($question->options as $option): ?>
                                    <div class="option">
                                        <span class="option-letter"><?php echo $option->option_letter; ?>.</span>
                                        <span class="option-text"><?php echo esc_html($option->option_text); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (in_array($question->question_type, ['short_answer', 'essay'])): ?>
                            <div class="answer-space-container">
                                Answer: <span class="answer-space"></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($options['include_answer_key']): ?>
                            <div class="answer-key">
                                <div class="answer-key-title">Correct Answer:</div>
                                <div><?php echo esc_html($question->correct_answer); ?></div>
                                <?php if ($question->explanation): ?>
                                    <div style="margin-top: 5px; font-style: italic;">
                                        <?php echo esc_html($question->explanation); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Add page break every 3 questions for PDF
                    if ($for_pdf && $question_num % 3 === 0 && $question_num < count($questions)): 
                    ?>
                        <div class="page-break"></div>
                    <?php endif; ?>
                    
                    <?php $question_num++; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$options['student_version']): ?>
                <div class="footer">
                    <hr>
                    <p style="text-align: center; font-size: 0.8em; color: #666;">
                        Generated on <?php echo date('F j, Y H:i:s'); ?> | 
                        <?php echo get_bloginfo('name'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format duration in minutes to readable format
     */
    private function format_duration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf('%d hour%s %d minute%s', 
                $hours, $hours > 1 ? 's' : '', 
                $mins, $mins > 1 ? 's' : '');
        }
        
        return sprintf('%d minute%s', $mins, $mins > 1 ? 's' : '');
    }
    
    /**
     * AJAX generate preview
     */
    public function ajax_generate_preview() {
        check_ajax_referer('export_paper', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $paper_id = intval($_POST['paper_id'] ?? 0);
        $paper = $this->get_paper_data($paper_id);
        
        if (!$paper) {
            wp_send_json_error('Paper not found');
        }
        
        $questions = $this->get_paper_questions($paper_id);
        
        $preview_html = $this->generate_paper_preview($paper, $questions);
        
        wp_send_json_success([
            'preview' => $preview_html,
            'paper_title' => $paper->title,
            'question_count' => count($questions)
        ]);
    }
    
    /**
     * Generate preview HTML
     */
    private function generate_paper_preview($paper, $questions) {
        ob_start();
        ?>
        <div class="paper-preview">
            <h3><?php echo esc_html($paper->title); ?></h3>
            
            <div class="paper-info">
                <p><strong>Duration:</strong> <?php echo $this->format_duration($paper->duration); ?></p>
                <p><strong>Total Questions:</strong> <?php echo count($questions); ?></p>
                <p><strong>Total Marks:</strong> <?php echo $paper->total_marks; ?></p>
            </div>
            
            <?php if (count($questions) > 0): ?>
                <div class="questions-preview">
                    <h4>Questions Preview (first 3 questions):</h4>
                    
                    <?php for ($i = 0; $i < min(3, count($questions)); $i++): ?>
                        <div class="preview-question">
                            <p><strong>Q<?php echo $i + 1; ?>:</strong> 
                            <?php echo wp_trim_words(strip_tags($questions[$i]->question_text), 20); ?></p>
                        </div>
                    <?php endfor; ?>
                    
                    <?php if (count($questions) > 3): ?>
                        <p>... and <?php echo count($questions) - 3; ?> more questions</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p>No questions found in this paper.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render export form
     */
    public static function render_export_form($paper_id = 0) {
        global $wpdb;
        
        // Get all papers for dropdown
        $papers = $wpdb->get_results("SELECT paper_id, title FROM {$wpdb->prefix}exam_papers ORDER BY created_at DESC");
        ?>
        
        <div class="wrap">
            <h1><?php _e('Export Exam Paper', 'online-exam'); ?></h1>
            
            <div class="export-instructions">
                <div class="notice notice-info">
                    <p><?php _e('Export exam papers to various formats for printing, sharing, or backup.', 'online-exam'); ?></p>
                </div>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="paper-export-form">
                <input type="hidden" name="action" value="export_paper">
                <?php wp_nonce_field('export_paper', 'export_paper_nonce'); ?>
                
                <div class="export-options-card">
                    <h2><?php _e('Paper Selection', 'online-exam'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="paper_id"><?php _e('Select Paper', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="paper_id" id="paper_id" class="regular-text" required>
                                    <option value=""><?php _e('-- Select a Paper --', 'online-exam'); ?></option>
                                    <?php foreach ($papers as $paper): ?>
                                        <option value="<?php echo $paper->paper_id; ?>" 
                                                <?php selected($paper_id, $paper->paper_id); ?>>
                                            <?php echo esc_html($paper->title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="preview_paper" class="button button-small">
                                    <?php _e('Preview Paper', 'online-exam'); ?>
                                </button>
                                <div id="paper_preview" style="margin-top: 15px;"></div>
                            </td>
                        </tr>
                    </table>
                    
                    <h2><?php _e('Export Options', 'online-exam'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="export_format"><?php _e('Export Format', 'online-exam'); ?></label>
                            </th>
                            <td>
                                <select name="export_format" id="export_format" class="regular-text">
                                    <option value="pdf">PDF (Printable)</option>
                                    <option value="word">Microsoft Word (.docx)</option>
                                    <option value="excel">Microsoft Excel (.xlsx)</option>
                                    <option value="html">HTML (Web Page)</option>
                                </select>
                                <p class="description"><?php _e('Choose the format for your exported paper', 'online-exam'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Content Options', 'online-exam'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="include_instructions" value="1" checked>
                                    <?php _e('Include paper instructions', 'online-exam'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="include_marks" value="1" checked>
                                    <?php _e('Include marks for each question', 'online-exam'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="include_answer_key" value="1">
                                    <?php _e('Include answer key (for teachers)', 'online-exam'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="student_version" value="1">
                                    <?php _e('Generate student version (without answer key)', 'online-exam'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr id="pdf_options" style="display: none;">
                            <th scope="row"><?php _e('PDF Options', 'online-exam'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="pdf_include_header" value="1" checked>
                                    <?php _e('Include header with paper title', 'online-exam'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="pdf_include_footer" value="1" checked>
                                    <?php _e('Include footer with page numbers', 'online-exam'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export Paper', 'online-exam'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=online-exam-papers'); ?>" class="button button-large">
                            <?php _e('Cancel', 'online-exam'); ?>
                        </a>
                    </p>
                </div>
            </form>
            
            <div class="export-notes">
                <h3><?php _e('Export Notes', 'online-exam'); ?></h3>
                <ul>
                    <li><?php _e('PDF format is best for printing and distributing paper copies.', 'online-exam'); ?></li>
                    <li><?php _e('Word format allows easy editing and customization.', 'online-exam'); ?></li>
                    <li><?php _e('Excel format is useful for question banks and analysis.', 'online-exam'); ?></li>
                    <li><?php _e('HTML format can be easily posted on websites or learning management systems.', 'online-exam'); ?></li>
                    <li><?php _e('Select "Student Version" to create papers without answer keys for distribution.', 'online-exam'); ?></li>
                </ul>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Show/hide PDF options
            $('#export_format').on('change', function() {
                if ($(this).val() === 'pdf') {
                    $('#pdf_options').show();
                } else {
                    $('#pdf_options').hide();
                }
            });
            
            // Preview paper
            $('#preview_paper').on('click', function() {
                var paperId = $('#paper_id').val();
                
                if (!paperId) {
                    alert('Please select a paper first.');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_paper_preview',
                        paper_id: paperId,
                        nonce: '<?php echo wp_create_nonce('export_paper'); ?>'
                    },
                    beforeSend: function() {
                        $('#paper_preview').html('<div class="spinner is-active"></div> Loading preview...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#paper_preview').html(response.data.preview);
                        } else {
                            $('#paper_preview').html('<div class="notice notice-error"><p>Error loading preview</p></div>');
                        }
                    },
                    error: function() {
                        $('#paper_preview').html('<div class="notice notice-error"><p>Error loading preview</p></div>');
                    }
                });
            });
            
            // Auto-preview on paper change
            $('#paper_id').on('change', function() {
                $('#paper_preview').empty();
            });
        });
        </script>
        <?php
    }
}

// Initialize if needed
function init_paper_exporter() {
    new Online_Exam_Paper_Exporter();
}
add_action('init', 'init_paper_exporter');