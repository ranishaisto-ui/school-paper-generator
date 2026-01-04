<?php
class SPG_Export {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
    }
    
    public function add_menu_pages() {
        add_submenu_page(
            null, // Hidden page
            __('Export Paper', 'school-paper-generator'),
            __('Export Paper', 'school-paper-generator'),
            'manage_options',
            'spg-export',
            [$this, 'render_page']
        );
    }
    
    public function render_page() {
        $paper_id = isset($_GET['paper_id']) ? intval($_GET['paper_id']) : 0;
        
        if (!$paper_id) {
            echo '<div class="error"><p>' . __('No paper specified.', 'school-paper-generator') . '</p></div>';
            return;
        }
        
        global $wpdb;
        $papers_table = $wpdb->prefix . 'spg_papers';
        
        $paper = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$papers_table} WHERE id = %d",
            $paper_id
        ));
        
        if (!$paper) {
            echo '<div class="error"><p>' . __('Paper not found.', 'school-paper-generator') . '</p></div>';
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Export Paper', 'school-paper-generator'); ?></h1>
            
            <div class="spg-paper-preview">
                <h2><?php echo esc_html($paper->paper_title); ?></h2>
                <p><strong><?php _e('Subject:', 'school-paper-generator'); ?></strong> <?php echo esc_html($paper->subject); ?></p>
                <p><strong><?php _e('Class:', 'school-paper-generator'); ?></strong> <?php echo esc_html($paper->class_level); ?></p>
                <p><strong><?php _e('Total Marks:', 'school-paper-generator'); ?></strong> <?php echo esc_html($paper->total_marks); ?></p>
                <p><strong><?php _e('Time Allowed:', 'school-paper-generator'); ?></strong> <?php echo esc_html($paper->time_allowed); ?></p>
                
                <?php if ($paper->instructions): ?>
                    <div class="instructions">
                        <h3><?php _e('Instructions:', 'school-paper-generator'); ?></h3>
                        <?php echo wpautop($paper->instructions); ?>
                    </div>
                <?php endif; ?>
                
                <?php
                $paper_data = json_decode($paper->paper_data, true);
                if ($paper_data && isset($paper_data['questions'])) {
                    echo '<div class="questions-list">';
                    foreach ($paper_data['questions'] as $question) {
                        echo '<div class="question">';
                        echo '<p><strong>Q' . $question['order'] . ':</strong> ' . wp_kses_post($question['question_text']) . ' (' . $question['marks'] . ' marks)</p>';
                        
                        if ($question['question_type'] === 'mcq' && $question['options']) {
                            echo '<ul>';
                            foreach ($question['options'] as $index => $option) {
                                echo '<li>' . esc_html(chr(65 + $index) . '. ' . $option) . '</li>';
                            }
                            echo '</ul>';
                        }
                        
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
            
            <div class="export-options">
                <h2><?php _e('Export Options', 'school-paper-generator'); ?></h2>
                
                <button class="button button-primary spg-export-btn" data-format="pdf" data-paper-id="<?php echo $paper_id; ?>">
                    <?php _e('Export as PDF', 'school-paper-generator'); ?>
                </button>
                
                <?php if (SPG_PREMIUM_ACTIVE): ?>
                    <button class="button button-primary spg-export-btn" data-format="word" data-paper-id="<?php echo $paper_id; ?>">
                        <?php _e('Export as Word', 'school-paper-generator'); ?>
                    </button>
                    <button class="button button-primary spg-export-btn" data-format="excel" data-paper-id="<?php echo $paper_id; ?>">
                        <?php _e('Export as Excel', 'school-paper-generator'); ?>
                    </button>
                <?php else: ?>
                    <p class="description">
                        <?php _e('Upgrade to Premium for Word and Excel export formats.', 'school-paper-generator'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.spg-export-btn').on('click', function() {
                const format = $(this).data('format');
                const paperId = $(this).data('paper-id');
                
                alert('Exporting as ' + format + '... This feature requires additional libraries to be installed.');
                
                // In a real implementation, you would make an AJAX call here
                // $.ajax({
                //     url: ajaxurl,
                //     type: 'POST',
                //     data: {
                //         action: 'spg_export_paper',
                //         paper_id: paperId,
                //         format: format,
                //         nonce: '<?php echo wp_create_nonce('spg_export'); ?>'
                //     },
                //     success: function(response) {
                //         // Handle export
                //     }
                // });
            });
        });
        </script>
        <?php
    }
}