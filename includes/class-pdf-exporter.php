<?php
if (!defined('ABSPATH')) exit;

class SPG_PDF_Exporter {
    
    private $paper_data;
    private $mpdf;
    
    public function __construct($paper_data = array()) {
        $this->paper_data = $paper_data;
    }
    
    public function generate() {
        try {
            // Include mPDF library
            if (!class_exists('Mpdf\Mpdf')) {
                require_once SPG_PLUGIN_DIR . 'vendor/autoload.php';
            }
            
            $this->mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 20,
                'margin_right' => 20,
                'margin_top' => 30,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'default_font' => 'dejavusans'
            ]);
            
            // Set document metadata
            $this->set_metadata();
            
            // Generate content
            $html = $this->generate_html();
            $this->mpdf->WriteHTML($html);
            
            // Add footer
            $this->add_footer();
            
            return $this->mpdf->Output('', 'S'); // Return as string
            
        } catch (Exception $e) {
            return new WP_Error('pdf_generation', __('PDF generation failed: ', 'school-paper-generator') . $e->getMessage());
        }
    }
    
    private function set_metadata() {
        $this->mpdf->SetTitle($this->paper_data['title'] ?? __('Exam Paper', 'school-paper-generator'));
        $this->mpdf->SetAuthor($this->paper_data['school_name'] ?? get_bloginfo('name'));
        $this->mpdf->SetCreator('School Paper Generator');
    }
    
    private function generate_html() {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                <?php echo $this->get_styles(); ?>
            </style>
        </head>
        <body>
            <?php echo $this->generate_header(); ?>
            <?php echo $this->generate_instructions(); ?>
            <?php echo $this->generate_questions(); ?>
            <?php echo $this->generate_footer(); ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function get_styles() {
        return '
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12pt; line-height: 1.5; }
        
        .paper-header { 
            text-align: center; 
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .school-logo { 
            max-height: 80px; 
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .school-name { 
            font-size: 18pt; 
            font-weight: bold;
            margin: 5px 0;
        }
        
        .school-address { 
            font-size: 10pt; 
            color: #666;
            margin: 5px 0;
        }
        
        .paper-title { 
            font-size: 16pt; 
            margin: 15px 0;
            text-decoration: underline;
        }
        
        .paper-info { 
            display: table;
            width: 100%;
            margin: 15px 0;
            font-size: 11pt;
        }
        
        .info-row { display: table-row; }
        .info-label, .info-value { display: table-cell; padding: 3px 0; }
        .info-label { font-weight: bold; width: 150px; }
        
        .instructions { 
            background: #f5f5f5;
            padding: 12px;
            border-left: 4px solid #333;
            margin: 20px 0;
            font-size: 11pt;
        }
        
        .instructions strong { display: block; margin-bottom: 5px; }
        
        .section { 
            margin: 25px 0;
            page-break-inside: avoid;
        }
        
        .section-title { 
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .question { 
            margin: 15px 0;
            page-break-inside: avoid;
        }
        
        .question-number { 
            font-weight: bold;
            float: left;
            width: 30px;
        }
        
        .question-text { 
            margin-left: 30px;
            margin-bottom: 10px;
        }
        
        .options { 
            margin-left: 40px;
            margin-bottom: 10px;
        }
        
        .option { 
            margin: 5px 0;
        }
        
        .option-letter { 
            font-weight: bold;
            margin-right: 10px;
        }
        
        .marks { 
            float: right;
            font-weight: bold;
        }
        
        .page-break { page-break-before: always; }
        
        .paper-footer { 
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 10pt;
            color: #666;
            text-align: center;
        }
        
        .answer-key { 
            margin-top: 40px;
            page-break-before: always;
        }
        
        .answer-key-title { 
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .answer-row { 
            margin: 8px 0;
            border-bottom: 1px dashed #ddd;
            padding-bottom: 8px;
        }
        
        .answer-question { font-weight: bold; }
        .answer-correct { color: #008000; }
        .answer-explanation { color: #666; font-style: italic; margin-top: 5px; }
        ';
    }
    
    private function generate_header() {
        $school_logo = !empty($this->paper_data['school_logo']) ? $this->paper_data['school_logo'] : '';
        $school_name = !empty($this->paper_data['school_name']) ? $this->paper_data['school_name'] : get_bloginfo('name');
        $school_address = !empty($this->paper_data['school_address']) ? $this->paper_data['school_address'] : '';
        
        ob_start();
        ?>
        <div class="paper-header">
            <?php if ($school_logo): ?>
            <img src="<?php echo esc_url($school_logo); ?>" class="school-logo" alt="<?php echo esc_attr($school_name); ?>">
            <?php endif; ?>
            
            <div class="school-name"><?php echo esc_html($school_name); ?></div>
            
            <?php if ($school_address): ?>
            <div class="school-address"><?php echo nl2br(esc_html($school_address)); ?></div>
            <?php endif; ?>
            
            <div class="paper-title"><?php echo esc_html($this->paper_data['title'] ?? __('Exam Paper', 'school-paper-generator')); ?></div>
            
            <div class="paper-info">
                <div class="info-row">
                    <div class="info-label"><?php _e('Subject:', 'school-paper-generator'); ?></div>
                    <div class="info-value"><?php echo esc_html($this->paper_data['subject'] ?? ''); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><?php _e('Class:', 'school-paper-generator'); ?></div>
                    <div class="info-value"><?php echo esc_html($this->paper_data['class_level'] ?? ''); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><?php _e('Total Marks:', 'school-paper-generator'); ?></div>
                    <div class="info-value"><?php echo esc_html($this->paper_data['total_marks'] ?? 100); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label"><?php _e('Time:', 'school-paper-generator'); ?></div>
                    <div class="info-value"><?php echo esc_html($this->paper_data['time_duration'] ?? '3 hours'); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function generate_instructions() {
        if (empty($this->paper_data['instructions'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="instructions">
            <strong><?php _e('General Instructions:', 'school-paper-generator'); ?></strong>
            <?php echo wpautop($this->paper_data['instructions']); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function generate_questions() {
        if (empty($this->paper_data['questions'])) {
            return '<p>' . __('No questions found.', 'school-paper-generator') . '</p>';
        }
        
        ob_start();
        
        // Group questions by section/type
        $sections = array();
        foreach ($this->paper_data['questions'] as $question) {
            $section = $question['section'] ?? $question['type'];
            if (!isset($sections[$section])) {
                $sections[$section] = array();
            }
            $sections[$section][] = $question;
        }
        
        $question_number = 1;
        
        foreach ($sections as $section_name => $section_questions) {
            ?>
            <div class="section">
                <div class="section-title">
                    <?php echo esc_html(ucfirst($section_name)); ?> 
                    <?php _e('Questions', 'school-paper-generator'); ?>
                </div>
                
                <?php foreach ($section_questions as $question): ?>
                <div class="question">
                    <div class="question-number">Q<?php echo $question_number; ?>.</div>
                    <div class="marks">[<?php echo esc_html($question['marks']); ?> <?php _e('marks', 'school-paper-generator'); ?>]</div>
                    
                    <div class="question-text"><?php echo wpautop($question['text']); ?></div>
                    
                    <?php if (!empty($question['options']) && $question['type'] === 'mcq'): ?>
                    <div class="options">
                        <?php foreach ($question['options'] as $index => $option): 
                            $letter = chr(65 + $index); // A, B, C, D
                        ?>
                        <div class="option">
                            <span class="option-letter"><?php echo $letter; ?>.</span>
                            <?php echo esc_html($option); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($question['type'] === 'true_false'): ?>
                    <div class="options">
                        <div class="option">
                            <span class="option-letter">A.</span> True
                        </div>
                        <div class="option">
                            <span class="option-letter">B.</span> False
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $question_number++;
                endforeach; ?>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
    
    private function generate_footer() {
        ob_start();
        ?>
        <div class="paper-footer">
            <div><?php _e('Page', 'school-paper-generator'); ?> {PAGENO} / {nbpg}</div>
            <div><?php _e('Generated by School Paper Generator', 'school-paper-generator'); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function add_footer() {
        $footer = '
        <table width="100%">
            <tr>
                <td width="33%" style="text-align: left; font-size: 9pt;">{DATE j-m-Y}</td>
                <td width="34%" style="text-align: center; font-size: 9pt;">{PAGENO} / {nbpg}</td>
                <td width="33%" style="text-align: right; font-size: 9pt;">' . __('School Paper Generator', 'school-paper-generator') . '</td>
            </tr>
        </table>';
        
        $this->mpdf->SetFooter($footer);
    }
    
    public function generate_answer_key() {
        if (empty($this->paper_data['questions'])) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="answer-key">
            <div class="answer-key-title"><?php _e('Answer Key', 'school-paper-generator'); ?></div>
            
            <?php 
            $question_number = 1;
            foreach ($this->paper_data['questions'] as $question): 
                if (!in_array($question['type'], array('mcq', 'true_false'))) {
                    continue;
                }
            ?>
            <div class="answer-row">
                <div class="answer-question">
                    Q<?php echo $question_number; ?>: <?php echo esc_html($question['text']); ?>
                </div>
                <div class="answer-correct">
                    <?php _e('Correct Answer:', 'school-paper-generator'); ?> 
                    <strong><?php echo esc_html($question['correct_answer']); ?></strong>
                </div>
                <?php if (!empty($question['explanation'])): ?>
                <div class="answer-explanation">
                    <?php _e('Explanation:', 'school-paper-generator'); ?> 
                    <?php echo esc_html($question['explanation']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php 
            $question_number++;
            endforeach; 
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function save_to_file($filename = '') {
        if (empty($filename)) {
            $filename = 'paper-' . date('Y-m-d-H-i-s') . '.pdf';
        }
        
        $pdf_content = $this->generate();
        
        if (is_wp_error($pdf_content)) {
            return $pdf_content;
        }
        
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/school-paper-generator/pdfs/';
        
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $filepath = $pdf_dir . $filename;
        
        if (file_put_contents($filepath, $pdf_content)) {
            return array(
                'url' => $upload_dir['baseurl'] . '/school-paper-generator/pdfs/' . $filename,
                'path' => $filepath,
                'filename' => $filename
            );
        }
        
        return new WP_Error('file_save', __('Failed to save PDF file', 'school-paper-generator'));
    }
}
?>