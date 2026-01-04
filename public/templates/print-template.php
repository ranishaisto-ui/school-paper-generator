<?php
// public/templates/print-template.php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Print template for exam papers
 */
class Online_Exam_Print_Template {
    
    /**
     * Get print template for a paper
     */
    public static function get_print_template($paper_id, $include_answers = false, $student_info = []) {
        global $wpdb;
        
        // Get paper data
        $paper = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exam_papers WHERE paper_id = %d",
            $paper_id
        ));
        
        if (!$paper) {
            return '<div class="error">Paper not found</div>';
        }
        
        // Get paper questions
        $questions = self::get_paper_questions($paper_id);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($paper->title); ?> - Print View</title>
            <style>
                /* Print-specific styles */
                @media print {
                    @page {
                        margin: 0.5in;
                        size: A4;
                    }
                    
                    body {
                        font-family: "Times New Roman", Times, serif;
                        font-size: 12pt;
                        line-height: 1.4;
                        color: #000;
                        background: #fff;
                        margin: 0;
                        padding: 0;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                    
                    .page-break {
                        page-break-before: always;
                    }
                    
                    .avoid-break {
                        page-break-inside: avoid;
                    }
                    
                    h1, h2, h3, h4, h5, h6 {
                        page-break-after: avoid;
                    }
                    
                    table, figure {
                        page-break-inside: avoid;
                    }
                }
                
                /* General styles */
                * {
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #fff;
                    max-width: 8.5in;
                    margin: 0 auto;
                    padding: 0.5in;
                }
                
                .print-header {
                    text-align: center;
                    border-bottom: 3px double #000;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                
                .paper-title {
                    font-size: 24pt;
                    font-weight: bold;
                    margin: 0 0 10px 0;
                    text-transform: uppercase;
                }
                
                .paper-subtitle {
                    font-size: 14pt;
                    font-style: italic;
                    margin-bottom: 15px;
                    color: #666;
                }
                
                .paper-info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 10px;
                    margin-top: 20px;
                }
                
                .info-item {
                    display: flex;
                    align-items: center;
                }
                
                .info-label {
                    font-weight: bold;
                    min-width: 120px;
                    margin-right: 10px;
                }
                
                .info-value {
                    flex: 1;
                }
                
                .student-info-section {
                    border: 2px solid #000;
                    padding: 15px;
                    margin-bottom: 30px;
                    background: #f9f9f9;
                }
                
                .student-info-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 10px;
                }
                
                .student-info-field {
                    margin-bottom: 10px;
                }
                
                .student-info-label {
                    font-weight: bold;
                    display: block;
                    margin-bottom: 5px;
                }
                
                .student-info-input {
                    border-bottom: 1px solid #000;
                    min-height: 25px;
                    width: 100%;
                }
                
                .instructions-section {
                    background: #f0f0f0;
                    border: 1px solid #ddd;
                    padding: 15px;
                    margin-bottom: 30px;
                    border-radius: 5px;
                }
                
                .instructions-title {
                    font-weight: bold;
                    font-size: 14pt;
                    margin-bottom: 10px;
                    color: #000;
                }
                
                .instructions-content {
                    font-size: 11pt;
                }
                
                .questions-section {
                    margin-top: 40px;
                }
                
                .section-title {
                    font-size: 16pt;
                    font-weight: bold;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                    margin-bottom: 25px;
                }
                
                .question-container {
                    margin-bottom: 35px;
                    page-break-inside: avoid;
                }
                
                .question-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 15px;
                }
                
                .question-number {
                    font-weight: bold;
                    font-size: 12pt;
                    color: #000;
                }
                
                .question-marks {
                    font-weight: bold;
                    font-style: italic;
                    color: #666;
                    font-size: 10pt;
                }
                
                .question-text {
                    font-size: 11pt;
                    margin-bottom: 15px;
                    line-height: 1.5;
                }
                
                .question-text img {
                    max-width: 100%;
                    height: auto;
                    display: block;
                    margin: 10px auto;
                }
                
                .options-container {
                    margin-left: 20px;
                }
                
                .option-item {
                    margin-bottom: 10px;
                    display: flex;
                    align-items: flex-start;
                }
                
                .option-letter {
                    font-weight: bold;
                    min-width: 25px;
                    margin-right: 10px;
                }
                
                .option-text {
                    flex: 1;
                    font-size: 11pt;
                }
                
                .answer-space {
                    border-bottom: 1px dashed #000;
                    min-width: 300px;
                    display: inline-block;
                    height: 25px;
                    margin: 0 10px;
                }
                
                .long-answer-space {
                    border: 1px solid #ddd;
                    min-height: 100px;
                    width: 100%;
                    margin-top: 10px;
                    padding: 10px;
                }
                
                .answer-key-section {
                    background: #e8f5e8;
                    border: 1px solid #4CAF50;
                    padding: 15px;
                    margin-top: 20px;
                    border-radius: 5px;
                }
                
                .answer-key-title {
                    font-weight: bold;
                    color: #2E7D32;
                    margin-bottom: 10px;
                }
                
                .answer-key-item {
                    margin-bottom: 8px;
                }
                
                .footer-section {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 10pt;
                    color: #666;
                }
                
                .page-number {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    font-size: 10pt;
                    color: #999;
                }
                
                .barcode {
                    text-align: center;
                    margin-top: 30px;
                }
                
                .barcode img {
                    max-width: 200px;
                    height: auto;
                }
                
                /* Multiple columns for MCQ papers */
                .mcq-columns {
                    column-count: 2;
                    column-gap: 40px;
                }
                
                .mcq-question {
                    break-inside: avoid;
                    margin-bottom: 25px;
                }
                
                /* For answer sheets */
                .answer-sheet-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                
                .answer-sheet-table th,
                .answer-sheet-table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: center;
                    font-size: 10pt;
                }
                
                .answer-sheet-table th {
                    background: #f0f0f0;
                    font-weight: bold;
                }
                
                .answer-sheet-bubble {
                    width: 20px;
                    height: 20px;
                    border: 1px solid #000;
                    border-radius: 50%;
                    display: inline-block;
                    margin: 0 5px;
                }
                
                /* Watermark */
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    font-size: 80pt;
                    color: rgba(0,0,0,0.1);
                    z-index: -1;
                    pointer-events: none;
                    white-space: nowrap;
                }
            </style>
        </head>
        <body>
            
            <?php if (!empty($student_info)): ?>
                <div class="watermark">
                    <?php echo esc_html(get_bloginfo('name')); ?> EXAM
                </div>
            <?php endif; ?>
            
            <!-- Header Section -->
            <div class="print-header">
                <div class="paper-title"><?php echo esc_html($paper->title); ?></div>
                
                <?php if (!empty($paper->subtitle)): ?>
                    <div class="paper-subtitle"><?php echo esc_html($paper->subtitle); ?></div>
                <?php endif; ?>
                
                <div class="paper-info-grid">
                    <?php if ($paper->duration): ?>
                        <div class="info-item">
                            <span class="info-label">Duration:</span>
                            <span class="info-value"><?php echo self::format_duration($paper->duration); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($paper->total_marks): ?>
                        <div class="info-item">
                            <span class="info-label">Total Marks:</span>
                            <span class="info-value"><?php echo $paper->total_marks; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($paper->passing_marks): ?>
                        <div class="info-item">
                            <span class="info-label">Passing Marks:</span>
                            <span class="info-value"><?php echo $paper->passing_marks; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <span class="info-label">Date:</span>
                        <span class="info-value">____________________</span>
                    </div>
                </div>
            </div>
            
            <!-- Student Information Section -->
            <?php if (!empty($student_info)): ?>
                <div class="student-info-section">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">Student Information</h3>
                    <div class="student-info-grid">
                        <div class="student-info-field">
                            <span class="student-info-label">Full Name:</span>
                            <div class="student-info-input"></div>
                        </div>
                        <div class="student-info-field">
                            <span class="student-info-label">Student ID:</span>
                            <div class="student-info-input"></div>
                        </div>
                        <div class="student-info-field">
                            <span class="student-info-label">Class/Roll No:</span>
                            <div class="student-info-input"></div>
                        </div>
                        <div class="student-info-field">
                            <span class="student-info-label">Subject:</span>
                            <div class="student-info-input"><?php echo esc_html($paper->title); ?></div>
                        </div>
                        <div class="student-info-field">
                            <span class="student-info-label">Date:</span>
                            <div class="student-info-input"></div>
                        </div>
                        <div class="student-info-field">
                            <span class="student-info-label">Signature:</span>
                            <div class="student-info-input" style="min-height: 40px;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Instructions Section -->
            <?php if (!empty($paper->instructions)): ?>
                <div class="instructions-section">
                    <div class="instructions-title">General Instructions:</div>
                    <div class="instructions-content">
                        <?php echo wpautop($paper->instructions); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Questions Section -->
            <div class="questions-section">
                <div class="section-title">Questions</div>
                
                <?php
                $question_counter = 1;
                $current_section = '';
                
                foreach ($questions as $question):
                    
                    // Check for section break
                    if ($question->section_name !== $current_section && !empty($question->section_name)) {
                        $current_section = $question->section_name;
                        echo '<div class="page-break"></div>';
                        echo '<h3 style="border-bottom: 2px solid #000; padding-bottom: 10px; margin: 30px 0 20px 0;">' . esc_html($current_section) . '</h3>';
                    }
                    
                    // Determine if we need columns for MCQ
                    $use_columns = ($question->question_type === 'multiple_choice' && count($questions) > 10);
                    
                    if ($use_columns && $question_counter === 1) {
                        echo '<div class="mcq-columns">';
                    }
                ?>
                
                <div class="<?php echo $use_columns ? 'mcq-question' : 'question-container'; ?> avoid-break">
                    <div class="question-header">
                        <div class="question-number">Question <?php echo $question_counter; ?></div>
                        <div class="question-marks">[<?php echo $question->marks; ?> marks]</div>
                    </div>
                    
                    <div class="question-text">
                        <?php echo wpautop($question->question_text); ?>
                    </div>
                    
                    <?php if ($question->question_type === 'multiple_choice' && !empty($question->options)): ?>
                        <div class="options-container">
                            <?php foreach ($question->options as $option): ?>
                                <div class="option-item">
                                    <div class="option-letter"><?php echo $option->option_letter; ?>.</div>
                                    <div class="option-text"><?php echo esc_html($option->option_text); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <strong>Answer:</strong> 
                            <span class="answer-space"></span>
                        </div>
                        
                    <?php elseif ($question->question_type === 'true_false'): ?>
                        <div style="margin-top: 15px;">
                            <strong>Answer:</strong> 
                            <label style="margin-left: 20px;">
                                <input type="radio" name="q<?php echo $question_counter; ?>" value="true"> True
                            </label>
                            <label style="margin-left: 20px;">
                                <input type="radio" name="q<?php echo $question_counter; ?>" value="false"> False
                            </label>
                        </div>
                        
                    <?php elseif ($question->question_type === 'short_answer'): ?>
                        <div style="margin-top: 15px;">
                            <strong>Answer:</strong> 
                            <span class="answer-space" style="min-width: 400px;"></span>
                        </div>
                        
                    <?php elseif ($question->question_type === 'essay'): ?>
                        <div style="margin-top: 15px;">
                            <strong>Answer:</strong>
                            <div class="long-answer-space"></div>
                        </div>
                        
                    <?php elseif ($question->question_type === 'fill_in_blank'): ?>
                        <div style="margin-top: 15px;">
                            <?php 
                            // Replace blanks with answer spaces
                            $text_with_blanks = preg_replace_callback('/_{3,}/', function($matches) {
                                return '<span class="answer-space" style="min-width: ' . (strlen($matches[0]) * 10) . 'px;"></span>';
                            }, $question->question_text);
                            echo wpautop($text_with_blanks);
                            ?>
                        </div>
                        
                    <?php elseif ($question->question_type === 'matching'): ?>
                        <div style="margin-top: 15px;">
                            <table class="answer-sheet-table">
                                <thead>
                                    <tr>
                                        <th>Column A</th>
                                        <th>Answer</th>
                                        <th>Column B</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Parse matching pairs
                                    $pairs = explode("\n", $question->correct_answer);
                                    foreach ($pairs as $index => $pair):
                                        $parts = explode('=', $pair);
                                        if (count($parts) >= 2):
                                    ?>
                                    <tr>
                                        <td><?php echo trim($parts[0]); ?></td>
                                        <td><span class="answer-space" style="min-width: 50px;"></span></td>
                                        <td><?php echo isset($parts[1]) ? trim($parts[1]) : ''; ?></td>
                                    </tr>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($include_answers): ?>
                        <div class="answer-key-section">
                            <div class="answer-key-title">Answer Key:</div>
                            <div class="answer-key-item">
                                <strong>Correct Answer:</strong> <?php echo esc_html($question->correct_answer); ?>
                            </div>
                            <?php if (!empty($question->explanation)): ?>
                                <div class="answer-key-item">
                                    <strong>Explanation:</strong> <?php echo esc_html($question->explanation); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php
                    $question_counter++;
                    
                    // Close columns div if needed
                    if ($use_columns && $question_counter > count($questions)) {
                        echo '</div>';
                    }
                    
                    // Add page break every 3 questions (adjust as needed)
                    if ($question_counter % 3 === 0 && $question_counter < count($questions)) {
                        echo '<div class="page-break"></div>';
                    }
                    
                endforeach;
                ?>
            </div>
            
            <!-- Footer Section -->
            <div class="footer-section">
                <div style="margin-bottom: 10px;">
                    <strong><?php echo esc_html(get_bloginfo('name')); ?></strong><br>
                    <?php echo esc_html(get_bloginfo('description')); ?>
                </div>
                <div>
                    Generated on: <?php echo date('F j, Y'); ?> | 
                    Page <span class="page-number">1</span>
                </div>
            </div>
            
            <!-- Print Controls (hidden when printing) -->
            <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Print Paper
                </button>
                <button onclick="window.close()" style="padding: 10px 20px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                    Close
                </button>
            </div>
            
            <script>
                // Add page numbers
                window.onload = function() {
                    const pages = document.querySelectorAll('.page-break, body');
                    let pageNum = 1;
                    
                    pages.forEach((page, index) => {
                        if (index > 0) {
                            const pageNumber = document.createElement('div');
                            pageNumber.className = 'page-number';
                            pageNumber.textContent = pageNum;
                            page.appendChild(pageNumber);
                            pageNum++;
                        }
                    });
                    
                    // Auto-print if specified in URL
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('autoprint') && urlParams.get('autoprint') === 'true') {
                        setTimeout(() => {
                            window.print();
                        }, 1000);
                    }
                };
                
                // Handle print dialog
                window.addEventListener('afterprint', function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('closeafterprint') && urlParams.get('closeafterprint') === 'true') {
                        setTimeout(() => {
                            window.close();
                        }, 500);
                    }
                });
            </script>
            
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get paper questions with options
     */
    private static function get_paper_questions($paper_id) {
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
                s.section_name
            FROM {$wpdb->prefix}exam_paper_questions pq
            LEFT JOIN {$wpdb->prefix}exam_questions q ON pq.question_id = q.question_id
            LEFT JOIN {$wpdb->prefix}exam_sections s ON pq.section_id = s.section_id
            WHERE pq.paper_id = %d
            ORDER BY pq.section_order, pq.question_order
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
     * Format duration in minutes
     */
    private static function format_duration($minutes) {
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
     * Generate answer sheet template
     */
    public static function get_answer_sheet_template($paper_id, $student_info = []) {
        global $wpdb;
        
        $paper = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exam_papers WHERE paper_id = %d",
            $paper_id
        ));
        
        if (!$paper) {
            return '<div class="error">Paper not found</div>';
        }
        
        $questions = self::get_paper_questions($paper_id);
        $mcq_questions = array_filter($questions, function($q) {
            return $q->question_type === 'multiple_choice';
        });
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Answer Sheet - <?php echo esc_html($paper->title); ?></title>
            <style>
                @media print {
                    @page {
                        margin: 0.5in;
                    }
                    
                    body {
                        font-size: 12pt;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
                
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.4;
                    max-width: 8.5in;
                    margin: 0 auto;
                    padding: 0.5in;
                }
                
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .title {
                    font-size: 20pt;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                
                .student-info {
                    border: 2px solid #000;
                    padding: 15px;
                    margin-bottom: 30px;
                }
                
                .info-row {
                    display: flex;
                    margin-bottom: 10px;
                }
                
                .info-label {
                    font-weight: bold;
                    min-width: 120px;
                }
                
                .info-value {
                    flex: 1;
                    border-bottom: 1px solid #000;
                    min-height: 25px;
                }
                
                .answer-grid {
                    margin-top: 30px;
                }
                
                .answer-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 30px;
                }
                
                .answer-table th,
                .answer-table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: center;
                }
                
                .answer-table th {
                    background: #f0f0f0;
                    font-weight: bold;
                }
                
                .bubble {
                    width: 20px;
                    height: 20px;
                    border: 1px solid #000;
                    border-radius: 50%;
                    display: inline-block;
                    margin: 0 2px;
                }
                
                .bubble-label {
                    font-size: 10pt;
                    margin: 0 2px;
                }
                
                .footer {
                    text-align: center;
                    margin-top: 50px;
                    font-size: 10pt;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">ANSWER SHEET</div>
                <div><?php echo esc_html($paper->title); ?></div>
            </div>
            
            <div class="student-info">
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Student ID:</div>
                    <div class="info-value"></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date:</div>
                    <div class="info-value"></div>
                </div>
            </div>
            
            <div class="instructions">
                <p><strong>Instructions:</strong></p>
                <ol>
                    <li>Fill in your personal information above.</li>
                    <li>For multiple choice questions, darken the circle corresponding to your answer.</li>
                    <li>Use a black or blue pen only.</li>
                    <li>Do not write outside the designated areas.</li>
                </ol>
            </div>
            
            <?php if (!empty($mcq_questions)): ?>
                <div class="answer-grid">
                    <h3>Multiple Choice Questions</h3>
                    <table class="answer-table">
                        <thead>
                            <tr>
                                <th width="10%">Q.No.</th>
                                <th width="15%">A</th>
                                <th width="15%">B</th>
                                <th width="15%">C</th>
                                <th width="15%">D</th>
                                <th width="15%">E</th>
                                <th width="15%">Marks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mcq_questions as $question): ?>
                                <tr>
                                    <td><?php echo $question->question_order; ?></td>
                                    <td><div class="bubble"></div></td>
                                    <td><div class="bubble"></div></td>
                                    <td><div class="bubble"></div></td>
                                    <td><div class="bubble"></div></td>
                                    <td><div class="bubble"></div></td>
                                    <td>[<?php echo $question->marks; ?>]</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="answer-grid">
                <h3>Subjective Questions</h3>
                <p>Write your answers in the spaces provided below:</p>
                
                <?php
                $subjective_questions = array_filter($questions, function($q) {
                    return in_array($q->question_type, ['short_answer', 'essay', 'fill_in_blank']);
                });
                
                $counter = 1;
                foreach ($subjective_questions as $question):
                ?>
                    <div style="margin-bottom: 30px; page-break-inside: avoid;">
                        <p><strong>Q<?php echo $counter; ?>.</strong> [<?php echo $question->marks; ?> marks]</p>
                        <div style="border: 1px solid #ddd; min-height: 100px; padding: 10px; margin-top: 10px;">
                            <!-- Answer space -->
                        </div>
                    </div>
                    <?php $counter++; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="footer">
                <p><?php echo get_bloginfo('name'); ?> | Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            
            <div class="no-print" style="position: fixed; top: 20px; right: 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; background: #0073aa; color: white; border: none; cursor: pointer;">
                    Print Answer Sheet
                </button>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for getting print template
     */
    public static function ajax_get_print_template() {
        check_ajax_referer('print_paper', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $paper_id = intval($_POST['paper_id'] ?? 0);
        $template_type = sanitize_text_field($_POST['template_type'] ?? 'paper');
        $include_answers = isset($_POST['include_answers']);
        $student_info = isset($_POST['student_info']) ? (array)$_POST['student_info'] : [];
        
        switch ($template_type) {
            case 'answer_sheet':
                $html = self::get_answer_sheet_template($paper_id, $student_info);
                break;
            case 'paper':
            default:
                $html = self::get_print_template($paper_id, $include_answers, $student_info);
                break;
        }
        
        wp_send_json_success(['html' => $html]);
    }
}

// Initialize if needed
add_action('wp_ajax_get_print_template', ['Online_Exam_Print_Template', 'ajax_get_print_template']);