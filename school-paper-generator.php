<?php
/**
 * Plugin Name: School Paper Generator
 * Plugin URI: https://yourwebsite.com/school-paper-generator
 * Description: A professional exam paper generator plugin for schools and educational institutions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: school-paper-generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define basic constants
define('SPG_VERSION', '1.0.0');
define('SPG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPG_QUESTION_LIMIT', 50);

// Activation hook
register_activation_hook(__FILE__, 'spg_activation');
function spg_activation() {
    spg_create_tables();
    add_option('spg_school_name', get_bloginfo('name'));
    add_option('spg_question_limit', SPG_QUESTION_LIMIT);
    add_option('spg_trial_days', 30);
    add_option('spg_premium_price', 99.99); // Default price
    flush_rewrite_rules();
}

// Create database tables
function spg_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // ... existing tables ...
    
    // User Subscriptions table
    $sql_subscriptions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_subscriptions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        subscription_type enum('free','trial','premium') DEFAULT 'free',
        status enum('active','expired','cancelled','pending') DEFAULT 'active',
        start_date datetime DEFAULT CURRENT_TIMESTAMP,
        end_date datetime,
        trial_end_date datetime,
        payment_amount decimal(10,2),
        payment_currency varchar(10) DEFAULT 'USD',
        payment_method varchar(50),
        transaction_id varchar(100),
        features text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY subscription_type (subscription_type)
    ) $charset_collate;";
    
    // Payment Transactions table
    $sql_payments = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_payments (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        currency varchar(10) DEFAULT 'USD',
        payment_method varchar(50) NOT NULL,
        transaction_id varchar(100),
        status enum('pending','completed','failed','refunded') DEFAULT 'pending',
        description text,
        metadata text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";
    
    // Premium Features Access table
    $sql_features = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_user_features (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        feature_key varchar(100) NOT NULL,
        feature_value text,
        expiry_date datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_feature (user_id, feature_key)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_subscriptions);
    dbDelta($sql_payments);
    dbDelta($sql_features);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'spg_deactivation');
function spg_deactivation() {
    flush_rewrite_rules();
}

// ============ ADMIN MENUS ============
add_action('admin_menu', 'spg_admin_menu');
function spg_admin_menu() {
    add_menu_page(
        'Paper Generator',
        'Paper Generator',
        'manage_options',
        'spg-dashboard',
        'spg_dashboard_page',
        'dashicons-media-document',
        30
    );
    
    add_submenu_page(
        'spg-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'spg-dashboard',
        'spg_dashboard_page'
    );
    
    add_submenu_page(
        'spg-dashboard',
        'Question Bank',
        'Question Bank',
        'manage_options',
        'spg-question-bank',
        'spg_question_bank_page'
    );
    
    add_submenu_page(
        'spg-dashboard',
        'Create Paper',
        'Create Paper',
        'manage_options',
        'spg-create-paper',
        'spg_create_paper_page'
    );
    
    add_submenu_page(
        'spg-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'spg-settings',
        'spg_settings_page'
    );
    
    // Hidden pages
    add_submenu_page(
        null,
        'Export Paper',
        'Export Paper',
        'manage_options',
        'spg-export',
        'spg_export_page'
    );
}





// ============ DASHBOARD PAGE ============
function spg_dashboard_page() {
    global $wpdb;
    
    $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
    $total_papers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers");
    $mcq_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions WHERE question_type = 'mcq'");
    $short_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions WHERE question_type = 'short'");
    $long_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions WHERE question_type = 'long'");
    $recent_papers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spg_papers ORDER BY created_at DESC LIMIT 5");
    
    // Trial calculation
    $trial_start = get_option('spg_trial_start');
    $days_used = floor((time() - strtotime($trial_start)) / (60 * 60 * 24));
    $days_left = max(0, 30 - $days_used);
    ?>
    
    <div class="wrap">
        <h1>School Paper Generator Dashboard</h1>
        
        <?php if ($days_left <= 7): ?>
        <div class="notice notice-warning">
            <p><strong>⚠ Trial Ending Soon!</strong> Your trial ends in <?php echo $days_left; ?> days. <a href="#">Upgrade to premium</a> to continue using all features.</p>
        </div>
        <?php endif; ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Statistics</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center; border-left: 4px solid #0073aa;">
                    <h3>Total Questions</h3>
                    <div style="font-size: 36px; font-weight: bold; color: #0073aa; margin: 10px 0;"><?php echo $total_questions; ?></div>
                    <div style="font-size: 12px; color: #666;">
                        <span>MCQ: <?php echo $mcq_count; ?></span> | 
                        <span>Short: <?php echo $short_count; ?></span> | 
                        <span>Long: <?php echo $long_count; ?></span>
                    </div>
                </div>
                
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center; border-left: 4px solid #46b450;">
                    <h3>Total Papers</h3>
                    <div style="font-size: 36px; font-weight: bold; color: #46b450; margin: 10px 0;"><?php echo $total_papers; ?></div>
                    <p style="font-size: 12px; color: #666;">Generated papers</p>
                </div>
                
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center; border-left: 4px solid #ffb900;">
                    <h3>Trial Status</h3>
                    <div style="font-size: 36px; font-weight: bold; color: #ffb900; margin: 10px 0;"><?php echo $days_left; ?> days</div>
                    <p style="font-size: 12px; color: #666;">Free trial active</p>
                </div>
                
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 5px; text-align: center;">
                    <h3 style="color: white;">Upgrade to Premium</h3>
                    <p style="color: rgba(255,255,255,0.9); margin: 10px 0;">Unlock all features</p>
                    <button class="button button-primary" style="background: white; color: #667eea; border: none; font-weight: bold;">Upgrade Now</button>
                </div>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;">
                <a href="<?php echo admin_url('admin.php?page=spg-question-bank'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                    Add New Question
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span>
                    Create New Paper
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=spg-question-bank'); ?>" class="button">
                    <span class="dashicons dashicons-database" style="vertical-align: middle;"></span>
                    Manage Question Bank
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=spg-settings'); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span>
                    Settings
                </a>
            </div>
        </div>
        
        <?php if ($recent_papers): ?>
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Recent Papers</h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Total Marks</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_papers as $paper): ?>
                    <tr>
                        <td><?php echo $paper->id; ?></td>
                        <td><?php echo esc_html($paper->paper_title); ?></td>
                        <td><?php echo esc_html($paper->subject); ?></td>
                        <td><?php echo esc_html($paper->class_level); ?></td>
                        <td><?php echo $paper->total_marks; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($paper->created_at)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=spg-export&paper_id=' . $paper->id); ?>" class="button button-small">Export</a>
                            <button class="button button-small view-paper" data-id="<?php echo $paper->id; ?>">View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.view-paper').on('click', function() {
            var paperId = $(this).data('id');
            window.open('<?php echo admin_url('admin.php?page=spg-export&paper_id='); ?>' + paperId, '_blank');
        });
    });
    </script>
    <?php
}

// ============ QUESTION BANK PAGE ============
function spg_question_bank_page() {
    ?>
    <div class="wrap">
        <h1>Question Bank</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Add New Question</h2>
            
            <form method="post" action="" id="question-form">
                <?php wp_nonce_field('spg_add_question'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="question_type">Question Type</label></th>
                        <td>
                            <select name="question_type" id="question_type" style="width: 100%; padding: 5px;" onchange="toggleQuestionFields()">
                                <option value="mcq">Multiple Choice (MCQ)</option>
                                <option value="short">Short Answer</option>
                                <option value="long">Long Answer</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="subject">Subject</label></th>
                        <td><input type="text" name="subject" id="subject" style="width: 100%; padding: 5px;" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="class_level">Class Level</label></th>
                        <td><input type="text" name="class_level" id="class_level" style="width: 100%; padding: 5px;" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="question_text">Question Text</label></th>
                        <td><textarea name="question_text" id="question_text" rows="4" style="width: 100%; padding: 5px;" required></textarea></td>
                    </tr>
                    
                    <tr id="mcq-options-row" style="display: none;">
                        <th scope="row"><label>MCQ Options</label></th>
                        <td>
                            <div id="mcq-options-container">
                                <div style="margin-bottom: 5px;">
                                    <input type="text" name="mcq_options[]" placeholder="Option A" style="width: 80%; padding: 5px;" required>
                                </div>
                                <div style="margin-bottom: 5px;">
                                    <input type="text" name="mcq_options[]" placeholder="Option B" style="width: 80%; padding: 5px;" required>
                                </div>
                            </div>
                            <button type="button" class="button button-small" onclick="addMcqOption()" style="margin-top: 5px;">Add Option</button>
                            
                            <div style="margin-top: 10px;">
                                <label for="correct_option">Correct Option:</label>
                                <select name="correct_option" id="correct_option" style="margin-left: 10px; padding: 5px;">
                                    <option value="0">A</option>
                                    <option value="1">B</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    
                    <tr id="correct-answer-row" style="display: none;">
                        <th scope="row"><label for="correct_answer">Correct Answer</label></th>
                        <td><textarea name="correct_answer" id="correct_answer" rows="3" style="width: 100%; padding: 5px;"></textarea></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="marks">Marks</label></th>
                        <td>
                            <input type="number" name="marks" id="marks" value="1" min="1" style="width: 100px; padding: 5px;" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="difficulty">Difficulty</label></th>
                        <td>
                            <select name="difficulty" id="difficulty" style="width: 100px; padding: 5px;">
                                <option value="easy">Easy</option>
                                <option value="medium" selected>Medium</option>
                                <option value="hard">Hard</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit_question" class="button button-primary" value="Add Question">
                </p>
            </form>
            
            <script>
            function toggleQuestionFields() {
                var type = document.getElementById('question_type').value;
                var mcqRow = document.getElementById('mcq-options-row');
                var answerRow = document.getElementById('correct-answer-row');
                
                if (type === 'mcq') {
                    mcqRow.style.display = '';
                    answerRow.style.display = 'none';
                    updateCorrectOptionSelect();
                } else {
                    mcqRow.style.display = 'none';
                    answerRow.style.display = '';
                    
                    if (type === 'long') {
                        document.getElementById('correct_answer').rows = 6;
                    } else {
                        document.getElementById('correct_answer').rows = 3;
                    }
                }
            }
            
            function addMcqOption() {
                var container = document.getElementById('mcq-options-container');
                var optionCount = container.children.length;
                var optionLetter = String.fromCharCode(65 + optionCount);
                
                var div = document.createElement('div');
                div.style.marginBottom = '5px';
                div.innerHTML = `
                    <input type="text" name="mcq_options[]" placeholder="Option ${optionLetter}" style="width: 80%; padding: 5px;">
                    <button type="button" class="button button-small" onclick="this.parentElement.remove(); updateCorrectOptionSelect();" style="margin-left: 5px;">Remove</button>
                `;
                container.appendChild(div);
                updateCorrectOptionSelect();
            }
            
            function updateCorrectOptionSelect() {
                var select = document.getElementById('correct_option');
                var options = document.getElementsByName('mcq_options[]');
                
                select.innerHTML = '';
                
                for (var i = 0; i < options.length; i++) {
                    var option = document.createElement('option');
                    option.value = i;
                    option.text = String.fromCharCode(65 + i);
                    select.appendChild(option);
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                toggleQuestionFields();
            });
            </script>
        </div>
        
        <?php
        // Handle form submission
        if (isset($_POST['submit_question']) && wp_verify_nonce($_POST['_wpnonce'], 'spg_add_question')) {
            global $wpdb;
            
            $question_type = sanitize_text_field($_POST['question_type']);
            $subject = sanitize_text_field($_POST['subject']);
            $class_level = sanitize_text_field($_POST['class_level']);
            $question_text = wp_kses_post($_POST['question_text']);
            $marks = intval($_POST['marks']);
            $difficulty = sanitize_text_field($_POST['difficulty']);
            
            $options = '';
            $correct_answer = '';
            
            if ($question_type === 'mcq') {
                $mcq_options = array_map('sanitize_text_field', $_POST['mcq_options']);
                $correct_option = intval($_POST['correct_option']);
                $options = json_encode($mcq_options);
                $correct_answer = isset($mcq_options[$correct_option]) ? $mcq_options[$correct_option] : '';
            } else {
                $correct_answer = wp_kses_post($_POST['correct_answer']);
            }
            
            // Check question limit
            $question_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
            $question_limit = get_option('spg_question_limit', SPG_QUESTION_LIMIT);
            
            if ($question_count >= $question_limit) {
                echo '<div class="notice notice-warning"><p>You have reached the free version limit of ' . $question_limit . ' questions. <a href="#">Upgrade to premium</a> for unlimited questions.</p></div>';
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'spg_questions',
                    array(
                        'question_type' => $question_type,
                        'subject' => $subject,
                        'class_level' => $class_level,
                        'question_text' => $question_text,
                        'options' => $options,
                        'correct_answer' => $correct_answer,
                        'marks' => $marks,
                        'difficulty' => $difficulty,
                        'created_at' => current_time('mysql')
                    )
                );
                
                echo '<div class="notice notice-success"><p>Question added successfully! (' . ($question_count + 1) . '/' . $question_limit . ' questions used)</p></div>';
            }
        }
        ?>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>All Questions</h2>
            
            <?php
            global $wpdb;
            $questions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spg_questions ORDER BY id DESC");
            
            if ($questions) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Question</th>
                            <th>Marks</th>
                            <th>Difficulty</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): ?>
                        <tr>
                            <td><?php echo $question->id; ?></td>
                            <td>
                                <span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                    <?php echo strtoupper($question->question_type); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($question->subject); ?></td>
                            <td><?php echo esc_html($question->class_level); ?></td>
                            <td><?php echo wp_trim_words(wp_kses_post($question->question_text), 10); ?></td>
                            <td><?php echo $question->marks; ?></td>
                            <td>
                                <?php 
                                $difficulty_colors = [
                                    'easy' => '#46b450',
                                    'medium' => '#ffb900',
                                    'hard' => '#dc3232'
                                ];
                                $color = isset($difficulty_colors[$question->difficulty]) ? $difficulty_colors[$question->difficulty] : '#666';
                                ?>
                                <span style="background: <?php echo $color; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                    <?php echo ucfirst($question->difficulty); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($question->created_at)); ?></td>
                            <td>
                                <button class="button button-small edit-question" data-id="<?php echo $question->id; ?>">Edit</button>
                                <button class="button button-small delete-question" data-id="<?php echo $question->id; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>No questions found. Add your first question above!</p>';
            }
            ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Delete question
        $('.delete-question').on('click', function() {
            if (confirm('Are you sure you want to delete this question?')) {
                var questionId = $(this).data('id');
                var row = $(this).closest('tr');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'spg_delete_question',
                        question_id: questionId,
                        _wpnonce: '<?php echo wp_create_nonce('spg_delete'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut();
                            alert('Question deleted successfully!');
                        }
                    }
                });
            }
        });
        
        // Edit question (placeholder)
        $('.edit-question').on('click', function() {
            alert('Edit feature will be available in premium version!');
        });
    });
    </script>
    <?php
}

// ============ CREATE PAPER PAGE ============
function spg_create_paper_page() {
    global $wpdb;
    ?>
    <div class="wrap">
        <h1>Create Paper</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Paper Details</h2>
            
            <form method="post" action="" id="paper-form">
                <?php wp_nonce_field('spg_create_paper'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="paper_title">Paper Title *</label></th>
                        <td><input type="text" name="paper_title" id="paper_title" style="width: 100%; padding: 5px;" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="paper_subject">Subject *</label></th>
                        <td><input type="text" name="paper_subject" id="paper_subject" style="width: 100%; padding: 5px;" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="paper_class">Class Level *</label></th>
                        <td><input type="text" name="paper_class" id="paper_class" style="width: 100%; padding: 5px;" required></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="school_name">School Name</label></th>
                        <td>
                            <input type="text" name="school_name" id="school_name" 
                                   value="<?php echo esc_attr(get_option('spg_school_name', get_bloginfo('name'))); ?>" 
                                   style="width: 100%; padding: 5px;">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="total_marks">Total Marks</label></th>
                        <td><input type="number" name="total_marks" id="total_marks" min="1" style="width: 100px; padding: 5px;" placeholder="Auto-calculated"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="time_allowed">Time Allowed</label></th>
                        <td><input type="text" name="time_allowed" id="time_allowed" placeholder="e.g., 2 hours" style="width: 200px; padding: 5px;"></td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="instructions">Instructions</label></th>
                        <td><textarea name="instructions" id="instructions" rows="3" style="width: 100%; padding: 5px;"></textarea></td>
                    </tr>
                </table>
                
                <h3>Select Questions</h3>
                
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <h4>Filter Questions</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <input type="text" id="filter_subject" placeholder="Filter by subject" style="padding: 5px;">
                        </div>
                        <div>
                            <input type="text" id="filter_class" placeholder="Filter by class" style="padding: 5px;">
                        </div>
                        <div>
                            <select id="filter_type" style="padding: 5px;">
                                <option value="">All Types</option>
                                <option value="mcq">MCQ</option>
                                <option value="short">Short Answer</option>
                                <option value="long">Long Answer</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" id="filter_questions" class="button">Filter Questions</button>
                            <button type="button" id="clear_filter" class="button">Clear</button>
                        </div>
                    </div>
                </div>
                
                <div id="available-questions-container" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #fff;">
                    <h4>Available Questions</h4>
                    <div id="available-questions">
                        <?php
                        $questions = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spg_questions ORDER BY id DESC LIMIT 20");
                        
                        if ($questions) {
                            foreach ($questions as $question) {
                                echo '<div class="question-item" data-id="' . $question->id . '" data-marks="' . $question->marks . '" data-type="' . $question->question_type . '">';
                                echo '<label>';
                                echo '<input type="checkbox" name="question_ids[]" value="' . $question->id . '" class="question-checkbox"> ';
                                echo '<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 5px;">' . strtoupper($question->question_type) . '</span>';
                                echo wp_trim_words(esc_html($question->question_text), 10);
                                echo ' (' . $question->marks . ' marks)';
                                echo '</label>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No questions found. Please add questions first from the Question Bank.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div style="background: #f0f8ff; padding: 15px; border: 2px dashed #0073aa; border-radius: 5px; margin: 15px 0;">
                    <h4>Selected Questions</h4>
                    <div id="selected-questions" style="min-height: 50px; padding: 10px;">
                        <p style="color: #666; font-style: italic;">No questions selected yet. Check questions above to add them.</p>
                    </div>
                    <p><strong>Total Selected Marks: <span id="total-selected-marks">0</span></strong></p>
                </div>
                
                <p class="submit">
                    <input type="submit" name="create_paper" class="button button-primary" value="Generate Paper">
                    <button type="button" id="calculate-marks" class="button">Calculate Total Marks</button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var selectedQuestions = [];
            
            // Filter questions
            $('#filter_questions').on('click', function() {
                var subject = $('#filter_subject').val();
                var classLevel = $('#filter_class').val();
                var type = $('#filter_type').val();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'spg_get_questions',
                        subject: subject,
                        class_level: classLevel,
                        question_type: type
                    },
                    success: function(response) {
                        if (response.success) {
                            displayAvailableQuestions(response.data);
                        }
                    }
                });
            });
            
            // Clear filter
            $('#clear_filter').on('click', function() {
                $('#filter_subject').val('');
                $('#filter_class').val('');
                $('#filter_type').val('');
                $('#filter_questions').click();
            });
            
            function displayAvailableQuestions(questions) {
                var container = $('#available-questions');
                container.empty();
                
                if (questions.length === 0) {
                    container.html('<p>No questions found.</p>');
                    return;
                }
                
                questions.forEach(function(question) {
                    if (selectedQuestions.includes(question.id)) return;
                    
                    var html = '<div class="question-item" data-id="' + question.id + '" data-marks="' + question.marks + '" data-type="' + question.question_type + '">' +
                               '<label>' +
                               '<input type="checkbox" name="question_ids[]" value="' + question.id + '" class="question-checkbox"> ' +
                               '<span style="background: #0073aa; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 5px;">' + 
                               question.question_type.toUpperCase() + '</span>' +
                               question.question_text.substring(0, 100) + '... (' + question.marks + ' marks)' +
                               '</label>' +
                               '</div>';
                    container.append(html);
                });
                
                $('.question-checkbox').off('change').on('change', handleQuestionSelection);
            }
            
            function handleQuestionSelection() {
                var questionId = parseInt($(this).val());
                var questionItem = $(this).closest('.question-item');
                var questionMarks = parseInt(questionItem.data('marks'));
                var questionText = questionItem.find('label').text().replace(/^.*?\]\s*/, '').trim();
                var questionType = questionItem.data('type');
                
                if ($(this).is(':checked')) {
                    selectedQuestions.push(questionId);
                    
                    var selectedHtml = '<div class="selected-question" data-id="' + questionId + '" style="padding: 5px; border-bottom: 1px solid #ddd; margin-bottom: 5px;">' +
                                      '<span style="background: #46b450; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 5px;">' + 
                                      questionType.toUpperCase() + '</span>' +
                                      questionText +
                                      ' <button type="button" class="button button-small remove-question" style="float: right;" data-id="' + questionId + '">Remove</button>' +
                                      '</div>';
                    
                    if ($('#selected-questions p').length > 0) {
                        $('#selected-questions p').remove();
                    }
                    
                    $('#selected-questions').append(selectedHtml);
                } else {
                    selectedQuestions = selectedQuestions.filter(id => id !== questionId);
                    $('#selected-questions .selected-question[data-id="' + questionId + '"]').remove();
                    
                    if ($('#selected-questions .selected-question').length === 0) {
                        $('#selected-questions').html('<p style="color: #666; font-style: italic;">No questions selected yet. Check questions above to add them.</p>');
                    }
                }
                
                updateTotalMarks();
            }
            
            $(document).on('click', '.remove-question', function() {
                var questionId = parseInt($(this).data('id'));
                $('.question-checkbox[value="' + questionId + '"]').prop('checked', false);
                selectedQuestions = selectedQuestions.filter(id => id !== questionId);
                $(this).closest('.selected-question').remove();
                
                if ($('#selected-questions .selected-question').length === 0) {
                    $('#selected-questions').html('<p style="color: #666; font-style: italic;">No questions selected yet. Check questions above to add them.</p>');
                }
                
                updateTotalMarks();
            });
            
            function updateTotalMarks() {
                var totalMarks = 0;
                
                $('.selected-question').each(function() {
                    var questionId = $(this).data('id');
                    var questionItem = $('.question-item[data-id="' + questionId + '"]');
                    totalMarks += parseInt(questionItem.data('marks'));
                });
                
                $('#total-selected-marks').text(totalMarks);
                
                if ($('#total_marks').val() === '' || $('#total_marks').val() === '0') {
                    $('#total_marks').val(totalMarks);
                }
            }
            
            $('#calculate-marks').on('click', function() {
                updateTotalMarks();
                var total = $('#total-selected-marks').text();
                $('#total_marks').val(total);
                alert('Total marks calculated: ' + total);
            });
            
            $('.question-checkbox').on('change', handleQuestionSelection);
            
            $('#paper-form').on('submit', function(e) {
                if (selectedQuestions.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one question for the paper.');
                    return false;
                }
                
                $('.question-checkbox').each(function() {
                    if (!selectedQuestions.includes(parseInt($(this).val()))) {
                        $(this).prop('checked', false);
                    }
                });
            });
        });
        </script>
        
        <?php
        // Handle paper creation
        if (isset($_POST['create_paper']) && wp_verify_nonce($_POST['_wpnonce'], 'spg_create_paper')) {
            global $wpdb;
            
            $paper_title = sanitize_text_field($_POST['paper_title']);
            $subject = sanitize_text_field($_POST['paper_subject']);
            $class_level = sanitize_text_field($_POST['paper_class']);
            $school_name = sanitize_text_field($_POST['school_name']);
            $total_marks = intval($_POST['total_marks']);
            $time_allowed = sanitize_text_field($_POST['time_allowed']);
            $instructions = wp_kses_post($_POST['instructions']);
            $question_ids = isset($_POST['question_ids']) ? array_map('intval', $_POST['question_ids']) : array();
            
            // Get questions data
            $questions_data = array();
            if (!empty($question_ids)) {
                $placeholders = implode(',', array_fill(0, count($question_ids), '%d'));
                $questions = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}spg_questions WHERE id IN ($placeholders)",
                    $question_ids
                ));
                
                foreach ($questions as $index => $question) {
                    $options = !empty($question->options) ? json_decode($question->options, true) : null;
                    
                    $questions_data[] = array(
                        'id' => $question->id,
                        'question_type' => $question->question_type,
                        'question_text' => $question->question_text,
                        'options' => $options,
                        'correct_answer' => $question->correct_answer,
                        'marks' => $question->marks,
                        'difficulty' => $question->difficulty,
                        'order' => $index + 1
                    );
                }
            }
            
            if ($total_marks === 0) {
                $total_marks = array_sum(array_column($questions_data, 'marks'));
            }
            
            $paper_data = json_encode(array(
                'questions' => $questions_data,
                'metadata' => array(
                    'total_marks' => $total_marks,
                    'time_allowed' => $time_allowed,
                    'instructions' => $instructions
                )
            ));
            
            $wpdb->insert(
                $wpdb->prefix . 'spg_papers',
                array(
                    'paper_title' => $paper_title,
                    'subject' => $subject,
                    'class_level' => $class_level,
                    'school_name' => $school_name,
                    'instructions' => $instructions,
                    'total_marks' => $total_marks,
                    'time_allowed' => $time_allowed,
                    'paper_data' => $paper_data,
                    'created_at' => current_time('mysql')
                )
            );
            
            $paper_id = $wpdb->insert_id;
            
            echo '<div class="notice notice-success"><p>Paper created successfully! Paper ID: ' . $paper_id . '</p></div>';
            
            // Show preview
            ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2>Paper Preview</h2>
                <h3 style="text-align: center;"><?php echo esc_html($paper_title); ?></h3>
                <p style="text-align: center;">
                    <strong>Subject:</strong> <?php echo esc_html($subject); ?> | 
                    <strong>Class:</strong> <?php echo esc_html($class_level); ?> | 
                    <strong>Total Marks:</strong> <?php echo $total_marks; ?> | 
                    <strong>Time Allowed:</strong> <?php echo esc_html($time_allowed); ?>
                </p>
                
                <?php if ($school_name): ?>
                    <h4 style="text-align: center;"><?php echo esc_html($school_name); ?></h4>
                <?php endif; ?>
                
                <?php if ($instructions): ?>
                    <div style="background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;">
                        <h4>Instructions:</h4>
                        <p><?php echo nl2br(esc_html($instructions)); ?></p>
                    </div>
                <?php endif; ?>
                
                <h4>Questions:</h4>
                <?php foreach ($questions_data as $question): ?>
                    <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                        <p><strong>Q<?php echo $question['order']; ?>:</strong> <?php echo wp_kses_post($question['question_text']); ?> (<?php echo $question['marks']; ?> marks)</p>
                        
                        <?php if ($question['question_type'] === 'mcq' && $question['options']): ?>
                            <ul style="margin: 10px 0 0 30px;">
                                <?php foreach ($question['options'] as $index => $option): ?>
                                    <li><?php echo esc_html(chr(65 + $index) . '. ' . $option); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <p style="text-align: center; margin-top: 30px;">
                    <a href="<?php echo admin_url('admin.php?page=spg-export&paper_id=' . $paper_id); ?>" class="button button-primary">Export Paper</a>
                    <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button">Create Another Paper</a>
                </p>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

// ============ SETTINGS PAGE ============
function spg_settings_page() {
    if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'spg_settings')) {
        update_option('spg_school_name', sanitize_text_field($_POST['school_name']));
        update_option('spg_default_subjects', sanitize_textarea_field($_POST['default_subjects']));
        update_option('spg_default_classes', sanitize_textarea_field($_POST['default_classes']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    $school_name = get_option('spg_school_name', get_bloginfo('name'));
    $default_subjects = get_option('spg_default_subjects', "Mathematics\nScience\nEnglish\nSocial Studies\nPhysics\nChemistry\nBiology");
    $default_classes = get_option('spg_default_classes', "Grade 1\nGrade 2\nGrade 3\nGrade 4\nGrade 5\nGrade 6\nGrade 7\nGrade 8\nGrade 9\nGrade 10\nGrade 11\nGrade 12");
    ?>
    
    <div class="wrap">
        <h1>School Paper Generator Settings</h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>General Settings</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('spg_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="school_name">Default School Name</label></th>
                        <td>
                            <input type="text" name="school_name" id="school_name" 
                                   value="<?php echo esc_attr($school_name); ?>" 
                                   class="regular-text">
                            <p class="description">This name will appear on generated papers.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="default_subjects">Default Subjects</label></th>
                        <td>
                            <textarea name="default_subjects" id="default_subjects" 
                                      rows="5" class="large-text"><?php echo esc_textarea($default_subjects); ?></textarea>
                            <p class="description">Enter one subject per line. These will appear as suggestions when adding questions.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="default_classes">Default Classes</label></th>
                        <td>
                            <textarea name="default_classes" id="default_classes" 
                                      rows="5" class="large-text"><?php echo esc_textarea($default_classes); ?></textarea>
                            <p class="description">Enter one class level per line.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="Save Settings">
                </p>
            </form>
        </div>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>System Information</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Plugin Version</th>
                    <td><?php echo SPG_VERSION; ?></td>
                </tr>
                
                <tr>
                    <th scope="row">Total Questions</th>
                    <td>
                        <?php 
                        global $wpdb;
                        echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
                        ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Total Papers</th>
                    <td>
                        <?php 
                        echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers");
                        ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Trial Status</th>
                    <td>
                        <?php
                        $trial_start = get_option('spg_trial_start');
                        $days_used = floor((time() - strtotime($trial_start)) / (60 * 60 * 24));
                        $days_left = max(0, 30 - $days_used);
                        echo $days_left . ' days remaining';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; margin: 20px 0; border-radius: 5px; text-align: center;">
            <h2 style="color: white;">Upgrade to Premium</h2>
            <p style="font-size: 18px; margin: 20px 0;">Get unlimited questions, multiple export formats, school logo, and priority support!</p>
            <button class="button" style="background: white; color: #667eea; padding: 15px 30px; font-size: 16px; font-weight: bold; border: none;">Upgrade Now</button>
        </div>
    </div>
    <?php
}

// ============ EXPORT PAGE ============
function spg_export_page() {
    $paper_id = isset($_GET['paper_id']) ? intval($_GET['paper_id']) : 0;
    
    if (!$paper_id) {
        echo '<div class="notice notice-error"><p>No paper specified.</p></div>';
        return;
    }
    
    global $wpdb;
    $paper = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spg_papers WHERE id = %d",
        $paper_id
    ));
    
    if (!$paper) {
        echo '<div class="notice notice-error"><p>Paper not found.</p></div>';
        return;
    }
    ?>
    <div class="wrap">
        <h1>Export Paper: <?php echo esc_html($paper->paper_title); ?></h1>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2>Export Options</h2>
            
            <div style="text-align: center; padding: 30px;">
                <button class="button button-primary" onclick="printPaper()" style="padding: 12px 24px; font-size: 16px;">
                    <span class="dashicons dashicons-printer"></span> Print Paper
                </button>
                
                <button class="button button-secondary" onclick="exportAsPDF()" style="padding: 12px 24px; font-size: 16px;">
                    <span class="dashicons dashicons-pdf"></span> Export as PDF
                </button>
                
                <button class="button" onclick="exportAsWord()" style="padding: 12px 24px; font-size: 16px;">
                    <span class="dashicons dashicons-media-document"></span> Export as Word
                </button>
                
                <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button" style="padding: 12px 24px; font-size: 16px;">
                    <span class="dashicons dashicons-plus"></span> Create New Paper
                </a>
            </div>
            
            <div style="margin-top: 30px; color: #666; text-align: center;">
                <p><strong>Note:</strong> PDF and Word export require premium version. Print option works in all browsers.</p>
            </div>
        </div>
        
        <div id="paper-content" style="background: #f9f9f9; padding: 40px; margin: 20px 0; border: 1px solid #ddd;">
            <h2 style="text-align: center;"><?php echo esc_html($paper->paper_title); ?></h2>
            <p style="text-align: center;">
                <strong>Subject:</strong> <?php echo esc_html($paper->subject); ?> | 
                <strong>Class:</strong> <?php echo esc_html($paper->class_level); ?> | 
                <strong>Total Marks:</strong> <?php echo esc_html($paper->total_marks); ?> | 
                <strong>Time Allowed:</strong> <?php echo esc_html($paper->time_allowed); ?>
            </p>
            
            <?php if ($paper->school_name): ?>
                <h3 style="text-align: center;"><?php echo esc_html($paper->school_name); ?></h3>
            <?php endif; ?>
            
            <?php if ($paper->instructions): ?>
                <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
                    <h4>Instructions:</h4>
                    <p><?php echo nl2br(esc_html($paper->instructions)); ?></p>
                </div>
            <?php endif; ?>
            
            <?php
            $paper_data = json_decode($paper->paper_data, true);
            if ($paper_data && isset($paper_data['questions'])) {
                echo '<div style="margin-top: 30px;">';
                foreach ($paper_data['questions'] as $question) {
                    echo '<div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #ddd;">';
                    echo '<p><strong>Q' . $question['order'] . ':</strong> ' . wp_kses_post($question['question_text']) . ' (' . $question['marks'] . ' marks)</p>';
                    
                    if ($question['question_type'] === 'mcq' && $question['options']) {
                        echo '<ul style="margin: 10px 0 0 30px;">';
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
    </div>
    
    <script>
    function printPaper() {
        var content = document.getElementById('paper-content').innerHTML;
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title><?php echo esc_js($paper->paper_title); ?></title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 40px; line-height: 1.6; }');
        printWindow.document.write('h2 { text-align: center; }');
        printWindow.document.write('p { margin: 10px 0; }');
        printWindow.document.write('ul { margin: 10px 0 10px 30px; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    
    function exportAsPDF() {
        alert('PDF export requires premium version. Please use the Print option and save as PDF.');
    }
    
    function exportAsWord() {
        alert('Word export requires premium version.');
    }
    </script>
    <?php
}

// ============ AJAX HANDLERS ============
add_action('wp_ajax_spg_get_questions', 'spg_ajax_get_questions');
function spg_ajax_get_questions() {
    global $wpdb;
    
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
    $class_level = isset($_POST['class_level']) ? sanitize_text_field($_POST['class_level']) : '';
    $question_type = isset($_POST['question_type']) ? sanitize_text_field($_POST['question_type']) : '';
    
    $sql = "SELECT * FROM {$wpdb->prefix}spg_questions WHERE 1=1";
    $params = array();
    
    if (!empty($subject)) {
        $sql .= " AND subject LIKE %s";
        $params[] = '%' . $wpdb->esc_like($subject) . '%';
    }
    
    if (!empty($class_level)) {
        $sql .= " AND class_level LIKE %s";
        $params[] = '%' . $wpdb->esc_like($class_level) . '%';
    }
    
    if (!empty($question_type)) {
        $sql .= " AND question_type = %s";
        $params[] = $question_type;
    }
    
    $sql .= " ORDER BY id DESC LIMIT 100";
    
    if (!empty($params)) {
        $questions = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $questions = $wpdb->get_results($sql);
    }
    
    wp_send_json_success($questions);
}

add_action('wp_ajax_spg_delete_question', 'spg_ajax_delete_question');
function spg_ajax_delete_question() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'spg_delete')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    $question_id = intval($_POST['question_id']);
    
    $result = $wpdb->delete(
        $wpdb->prefix . 'spg_questions',
        array('id' => $question_id)
    );
    
    if ($result) {
        wp_send_json_success(array('message' => 'Question deleted'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete question'));
    }
}

// ============ SHORTCODES ============
add_shortcode('spg_paper', 'spg_paper_shortcode');
function spg_paper_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0
    ), $atts);
    
    $paper_id = intval($atts['id']);
    
    if (!$paper_id) {
        return '<p>Please specify a paper ID. Example: [spg_paper id="123"]</p>';
    }
    
    global $wpdb;
    $paper = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spg_papers WHERE id = %d",
        $paper_id
    ));
    
    if (!$paper) {
        return '<p>Paper not found.</p>';
    }
    
    ob_start();
    ?>
    <div class="spg-paper-display" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="text-align: center;"><?php echo esc_html($paper->paper_title); ?></h2>
        <p style="text-align: center;">
            <strong>Subject:</strong> <?php echo esc_html($paper->subject); ?> | 
            <strong>Class:</strong> <?php echo esc_html($paper->class_level); ?> | 
            <strong>Total Marks:</strong> <?php echo esc_html($paper->total_marks); ?> | 
            <strong>Time Allowed:</strong> <?php echo esc_html($paper->time_allowed); ?>
        </p>
        
        <?php if ($paper->school_name): ?>
            <h3 style="text-align: center;"><?php echo esc_html($paper->school_name); ?></h3>
        <?php endif; ?>
        
        <?php if ($paper->instructions): ?>
            <div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
                <h4>Instructions:</h4>
                <p><?php echo nl2br(esc_html($paper->instructions)); ?></p>
            </div>
        <?php endif; ?>
        
        <?php
        $paper_data = json_decode($paper->paper_data, true);
        if ($paper_data && isset($paper_data['questions'])) {
            echo '<div style="margin-top: 30px;">';
            foreach ($paper_data['questions'] as $question) {
                echo '<div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #eee;">';
                echo '<p><strong>Q' . $question['order'] . ':</strong> ' . wp_kses_post($question['question_text']) . ' (' . $question['marks'] . ' marks)</p>';
                
                if ($question['question_type'] === 'mcq' && $question['options']) {
                    echo '<ul style="margin: 10px 0 0 30px;">';
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
    <?php
    
    return ob_get_clean();
}

add_shortcode('spg_question_bank', 'spg_question_bank_shortcode');
function spg_question_bank_shortcode($atts) {
    $atts = shortcode_atts(array(
        'subject' => '',
        'class_level' => '',
        'type' => '',
        'limit' => 20
    ), $atts);
    
    global $wpdb;
    
    $sql = "SELECT * FROM {$wpdb->prefix}spg_questions WHERE 1=1";
    $params = array();
    
    if (!empty($atts['subject'])) {
        $sql .= " AND subject = %s";
        $params[] = $atts['subject'];
    }
    
    if (!empty($atts['class_level'])) {
        $sql .= " AND class_level = %s";
        $params[] = $atts['class_level'];
    }
    
    if (!empty($atts['type'])) {
        $sql .= " AND question_type = %s";
        $params[] = $atts['type'];
    }
    
    $sql .= " ORDER BY RAND() LIMIT %d";
    $params[] = intval($atts['limit']);
    
    if (!empty($params)) {
        $questions = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $questions = $wpdb->get_results($sql);
    }
    
    ob_start();
    ?>
    <div class="spg-question-bank" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Practice Questions</h3>
        
        <?php if (empty($questions)): ?>
            <p>No questions found.</p>
        <?php else: ?>
            <div class="questions-list">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
                        <p style="margin: 0 0 10px 0;"><strong>Q<?php echo $index + 1; ?>:</strong> <?php echo wp_kses_post($question->question_text); ?></p>
                        
                        <?php if ($question->question_type === 'mcq' && $question->options): ?>
                            <?php $options = json_decode($question->options, true); ?>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <?php foreach ($options as $opt_index => $option): ?>
                                    <li><?php echo esc_html(chr(65 + $opt_index) . '. ' . $option); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <details style="margin-top: 10px;">
                            <summary style="color: #0073aa; cursor: pointer; font-weight: bold;">Show Answer</summary>
                            <div style="background: #f9f9f9; padding: 10px; margin-top: 5px; border-radius: 3px;">
                                <?php echo wp_kses_post($question->correct_answer); ?>
                            </div>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

// ============ ADMIN STYLES ============
add_action('admin_enqueue_scripts', 'spg_admin_styles');
function spg_admin_styles($hook) {
    if (strpos($hook, 'spg-') !== false) {
        echo '<style>
        .spg-admin-notice {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .spg-admin-notice h3 {
            margin-top: 0;
            color: white;
        }
        .question-type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .question-type-badge.mcq { background: #0073aa; }
        .question-type-badge.short { background: #46b450; }
        .question-type-badge.long { background: #ffb900; }
        </style>';
    }
}

// Load text domain
add_action('init', 'spg_load_textdomain');
function spg_load_textdomain() {
    load_plugin_textdomain('school-paper-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
}





// Add this after the constants definition
define('SPG_PREMIUM_ACTIVE', false); // Change to true for premium users
define('SPG_PREMIUM_FEATURES', array(
    'unlimited_questions' => true,
    'multiple_export_formats' => true,
    'school_logo' => true,
    'advanced_analytics' => true,
    'bulk_operations' => true,
    'question_templates' => true,
    'priority_support' => true
));

// Add premium features initialization
function spg_init_premium_features() {
    if (SPG_PREMIUM_ACTIVE) {
        add_action('admin_menu', 'spg_add_premium_menu_items');
        add_filter('spg_question_limit', 'spg_premium_question_limit');
        add_action('spg_export_options', 'spg_add_premium_export_options');
        add_action('spg_paper_header', 'spg_add_school_logo_to_paper');
        add_action('wp_dashboard_setup', 'spg_add_premium_dashboard_widget');
    }
}
add_action('init', 'spg_init_premium_features');

// ============ PREMIUM MENU ITEMS ============
function spg_add_premium_menu_items() {
    // Bulk Operations
    add_submenu_page(
        'spg-dashboard',
        'Bulk Operations',
        '<span style="color: #ffb900">★ Bulk Operations</span>',
        'manage_options',
        'spg-bulk-operations',
        'spg_bulk_operations_page'
    );
    
    // Analytics
    add_submenu_page(
        'spg-dashboard',
        'Analytics',
        '<span style="color: #ffb900">★ Analytics</span>',
        'manage_options',
        'spg-analytics',
        'spg_analytics_page'
    );
    
    // Templates
    add_submenu_page(
        'spg-dashboard',
        'Paper Templates',
        '<span style="color: #ffb900">★ Templates</span>',
        'manage_options',
        'spg-templates',
        'spg_templates_page'
    );
    
    // Premium Settings
    add_submenu_page(
        'spg-dashboard',
        'Premium Settings',
        '<span style="color: #ffb900">★ Settings</span>',
        'manage_options',
        'spg-premium-settings',
        'spg_premium_settings_page'
    );
}

// ============ BULK OPERATIONS PAGE ============
function spg_bulk_operations_page() {
    ?>
    <div class="wrap">
        <h1>★ Bulk Operations <span style="background: #ffb900; color: #333; padding: 3px 10px; border-radius: 12px; font-size: 14px;">Premium</span></h1>
        
        <div style="background: linear-gradient(135deg, #fdfcfb 0%, #e2d1c3 100%); padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2>Bulk Question Import</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                <!-- CSV Import -->
                <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #46b450;">
                    <h3>Import from CSV</h3>
                    <p>Upload a CSV file with questions. Download template:</p>
                    <a href="#" class="button button-primary" onclick="downloadCSVTemplate()">Download CSV Template</a>
                    
                    <div style="border: 2px dashed #ddd; padding: 30px; text-align: center; margin: 15px 0; border-radius: 5px; cursor: pointer;" id="csv-drop-zone">
                        <span class="dashicons dashicons-upload" style="font-size: 40px; color: #0073aa;"></span>
                        <p>Drag & drop CSV file here or click to browse</p>
                        <input type="file" id="csv-file" accept=".csv" style="display: none;">
                        <button class="button" onclick="document.getElementById('csv-file').click()">Browse Files</button>
                    </div>
                    
                    <div id="import-progress" style="display: none;">
                        <div style="background: #f5f5f5; height: 20px; border-radius: 10px; overflow: hidden;">
                            <div id="import-progress-bar" style="background: #0073aa; height: 100%; width: 0%;"></div>
                        </div>
                        <p id="import-status">Processing...</p>
                    </div>
                </div>
                
                <!-- Bulk Export -->
                <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #0073aa;">
                    <h3>Bulk Export Questions</h3>
                    <p>Export all questions in various formats:</p>
                    
                    <div style="margin: 15px 0;">
                        <select id="export-format" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <option value="csv">CSV Format</option>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="json">JSON Format</option>
                            <option value="pdf">PDF Document</option>
                        </select>
                        
                        <select id="export-filter" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <option value="all">All Questions</option>
                            <option value="subject">By Subject</option>
                            <option value="type">By Question Type</option>
                            <option value="difficulty">By Difficulty</option>
                        </select>
                        
                        <button class="button button-primary" onclick="bulkExportQuestions()" style="width: 100%;">Export Questions</button>
                    </div>
                </div>
                
                <!-- Batch Paper Generation -->
                <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #ffb900;">
                    <h3>Batch Paper Generation</h3>
                    <p>Generate multiple papers at once:</p>
                    
                    <div style="margin: 15px 0;">
                        <input type="number" id="batch-count" min="1" max="50" value="5" style="width: 100%; padding: 8px; margin-bottom: 10px;" placeholder="Number of papers">
                        
                        <select id="batch-template" style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <option value="random">Random Questions</option>
                            <option value="mcq_only">MCQ Only</option>
                            <option value="mixed">Mixed Questions</option>
                            <option value="by_difficulty">By Difficulty Level</option>
                        </select>
                        
                        <label style="display: block; margin-bottom: 10px;">
                            <input type="checkbox" id="randomize-questions" checked> Randomize questions for each paper
                        </label>
                        
                        <button class="button button-primary" onclick="generateBatchPapers()" style="width: 100%;">Generate Batch</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function downloadCSVTemplate() {
        // Create CSV template content
        var csvContent = "question_type,subject,class_level,question_text,option_a,option_b,option_c,option_d,correct_option,marks,difficulty,correct_answer\n";
        csvContent += "mcq,Mathematics,Grade 10,What is the value of π (pi)?,3.14,2.71,1.61,4.67,A,1,easy,\n";
        csvContent += "short,Science,Grade 8,Explain photosynthesis.,,,,,,5,medium,Photosynthesis is the process...\n";
        csvContent += "long,History,Grade 11,Discuss World War II causes.,,,,,,10,hard,World War II was caused by...";
        
        var blob = new Blob([csvContent], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'questions_template.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    function bulkExportQuestions() {
        var format = document.getElementById('export-format').value;
        var filter = document.getElementById('export-filter').value;
        
        alert('Exporting questions as ' + format.toUpperCase() + '...\nFilter: ' + filter);
        // In real implementation, this would make an AJAX call
    }
    
    function generateBatchPapers() {
        var count = document.getElementById('batch-count').value;
        var template = document.getElementById('batch-template').value;
        var randomize = document.getElementById('randomize-questions').checked;
        
        alert('Generating ' + count + ' papers...\nTemplate: ' + template + '\nRandomize: ' + (randomize ? 'Yes' : 'No'));
        // In real implementation, this would make an AJAX call
    }
    
    // Drag and drop for CSV
    document.getElementById('csv-drop-zone').addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#0073aa';
        this.style.background = '#f0f8ff';
    });
    
    document.getElementById('csv-drop-zone').addEventListener('dragleave', function(e) {
        this.style.borderColor = '#ddd';
        this.style.background = 'white';
    });
    
    document.getElementById('csv-drop-zone').addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#46b450';
        this.style.background = '#f0fff0';
        
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            handleCSVFile(files[0]);
        }
    });
    
    document.getElementById('csv-file').addEventListener('change', function(e) {
        if (this.files.length > 0) {
            handleCSVFile(this.files[0]);
        }
    });
    
    function handleCSVFile(file) {
        if (!file.name.endsWith('.csv')) {
            alert('Please upload a CSV file.');
            return;
        }
        
        document.getElementById('import-progress').style.display = 'block';
        document.getElementById('import-progress-bar').style.width = '0%';
        document.getElementById('import-status').textContent = 'Processing file...';
        
        // Simulate import progress
        var progress = 0;
        var interval = setInterval(function() {
            progress += 10;
            document.getElementById('import-progress-bar').style.width = progress + '%';
            document.getElementById('import-status').textContent = 'Importing... ' + progress + '%';
            
            if (progress >= 100) {
                clearInterval(interval);
                document.getElementById('import-status').textContent = 'Import completed successfully!';
                setTimeout(function() {
                    document.getElementById('import-progress').style.display = 'none';
                    alert('CSV file imported successfully!');
                }, 1000);
            }
        }, 200);
    }
    </script>
    <?php
}

// ============ ANALYTICS PAGE ============
function spg_analytics_page() {
    global $wpdb;
    
    // Get analytics data
    $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
    $total_papers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers");
    
    // Questions by type
    $questions_by_type = $wpdb->get_results("
        SELECT question_type, COUNT(*) as count 
        FROM {$wpdb->prefix}spg_questions 
        GROUP BY question_type
    ");
    
    // Papers by month
    $monthly_papers = $wpdb->get_results("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM {$wpdb->prefix}spg_papers
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ");
    
    // Most active subjects
    $popular_subjects = $wpdb->get_results("
        SELECT subject, COUNT(*) as count 
        FROM {$wpdb->prefix}spg_questions 
        GROUP BY subject 
        ORDER BY count DESC 
        LIMIT 5
    ");
    ?>
    
    <div class="wrap">
        <h1>★ Analytics Dashboard <span style="background: #ffb900; color: #333; padding: 3px 10px; border-radius: 12px; font-size: 14px;">Premium</span></h1>
        
        <div style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2>Overview Statistics</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $total_questions; ?></div>
                    <div style="font-size: 12px; color: #666;">Total Questions</div>
                </div>
                
                <div style="background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo $total_papers; ?></div>
                    <div style="font-size: 12px; color: #666;">Total Papers</div>
                </div>
                
                <?php foreach ($questions_by_type as $type): ?>
                <div style="background: white; padding: 15px; border-radius: 5px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 24px; font-weight: bold; color: #ffb900;"><?php echo $type->count; ?></div>
                    <div style="font-size: 12px; color: #666;"><?php echo strtoupper($type->question_type); ?> Questions</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 30px 0;">
            <!-- Questions by Type Chart -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>Questions by Type</h3>
                <div style="height: 200px; display: flex; align-items: flex-end; gap: 20px; margin-top: 20px;">
                    <?php foreach ($questions_by_type as $type): 
                        $percentage = ($type->count / max(1, $total_questions)) * 100;
                        $colors = [
                            'mcq' => '#0073aa',
                            'short' => '#46b450',
                            'long' => '#ffb900'
                        ];
                        $color = isset($colors[$type->question_type]) ? $colors[$type->question_type] : '#666';
                    ?>
                    <div style="text-align: center; flex: 1;">
                        <div style="background: <?php echo $color; ?>; height: <?php echo $percentage * 1.5; ?>px; border-radius: 5px 5px 0 0;"></div>
                        <div style="margin-top: 10px; font-weight: bold;"><?php echo strtoupper($type->question_type); ?></div>
                        <div style="font-size: 12px; color: #666;"><?php echo $type->count; ?> (<?php echo round($percentage, 1); ?>%)</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Monthly Papers Chart -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>Papers Generated (Last 6 Months)</h3>
                <div style="height: 200px; display: flex; align-items: flex-end; gap: 10px; margin-top: 20px;">
                    <?php foreach ($monthly_papers as $month): 
                        $max_count = max(array_column($monthly_papers, 'count'));
                        $height = $max_count > 0 ? ($month->count / $max_count) * 150 : 0;
                    ?>
                    <div style="text-align: center; flex: 1;">
                        <div style="background: linear-gradient(to top, #0073aa, #00a0d2); height: <?php echo $height; ?>px; border-radius: 5px 5px 0 0;"></div>
                        <div style="margin-top: 10px; font-size: 11px; color: #666;"><?php echo date('M', strtotime($month->month . '-01')); ?></div>
                        <div style="font-size: 12px; font-weight: bold;"><?php echo $month->count; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Popular Subjects -->
        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Most Popular Subjects</h3>
            <div style="margin-top: 20px;">
                <?php foreach ($popular_subjects as $subject): 
                    $percentage = ($subject->count / max(1, $total_questions)) * 100;
                ?>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span><?php echo esc_html($subject->subject); ?></span>
                        <span><?php echo $subject->count; ?> questions (<?php echo round($percentage, 1); ?>%)</span>
                    </div>
                    <div style="background: #f5f5f5; height: 10px; border-radius: 5px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #0073aa, #00a0d2); height: 100%; width: <?php echo $percentage; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Export Analytics -->
        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
            <h3>Export Analytics Data</h3>
            <div style="margin: 20px 0;">
                <button class="button button-primary" onclick="exportAnalytics('csv')">Export as CSV</button>
                <button class="button button-primary" onclick="exportAnalytics('pdf')">Export as PDF Report</button>
                <button class="button" onclick="window.print()">Print Report</button>
            </div>
        </div>
    </div>
    
    <script>
    function exportAnalytics(format) {
        alert('Exporting analytics data as ' + format.toUpperCase() + '...\nThis feature is available in premium version.');
    }
    </script>
    <?php
}

// ============ TEMPLATES PAGE ============
function spg_templates_page() {
    ?>
    <div class="wrap">
        <h1>★ Paper Templates <span style="background: #ffb900; color: #333; padding: 3px 10px; border-radius: 12px; font-size: 14px;">Premium</span></h1>
        
        <div style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%); padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Available Templates</h2>
                <button class="button button-primary" onclick="createNewTemplate()">
                    <span class="dashicons dashicons-plus"></span> Create New Template
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <!-- Standard Exam Template -->
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    <div style="background: #0073aa; color: white; padding: 15px;">
                        <h3 style="margin: 0; color: white;">Standard Exam</h3>
                        <span style="font-size: 12px; opacity: 0.9;">Default Template</span>
                    </div>
                    <div style="padding: 20px;">
                        <p><strong>Layout:</strong> Single column</p>
                        <p><strong>Sections:</strong> All question types</p>
                        <p><strong>Style:</strong> Professional</p>
                        <p><strong>Font:</strong> Times New Roman</p>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px;">
                            <button class="button button-primary" onclick="useTemplate('standard')" style="flex: 1;">Use Template</button>
                            <button class="button" onclick="editTemplate('standard')">Edit</button>
                            <button class="button" onclick="previewTemplate('standard')">Preview</button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Quiz Template -->
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    <div style="background: #46b450; color: white; padding: 15px;">
                        <h3 style="margin: 0; color: white;">Quick Quiz</h3>
                        <span style="font-size: 12px; opacity: 0.9;">MCQ Focused</span>
                    </div>
                    <div style="padding: 20px;">
                        <p><strong>Layout:</strong> Two columns</p>
                        <p><strong>Sections:</strong> MCQ only</p>
                        <p><strong>Style:</strong> Modern</p>
                        <p><strong>Font:</strong> Arial</p>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px;">
                            <button class="button button-primary" onclick="useTemplate('quiz')" style="flex: 1;">Use Template</button>
                            <button class="button" onclick="editTemplate('quiz')">Edit</button>
                            <button class="button" onclick="previewTemplate('quiz')">Preview</button>
                        </div>
                    </div>
                </div>
                
                <!-- Final Exam Template -->
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    <div style="background: #ffb900; color: #333; padding: 15px;">
                        <h3 style="margin: 0; color: #333;">Final Exam</h3>
                        <span style="font-size: 12px; opacity: 0.9;">Comprehensive</span>
                    </div>
                    <div style="padding: 20px;">
                        <p><strong>Layout:</strong> Multi-section</p>
                        <p><strong>Sections:</strong> All + Answer key</p>
                        <p><strong>Style:</strong> Formal</p>
                        <p><strong>Font:</strong> Georgia</p>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px;">
                            <button class="button button-primary" onclick="useTemplate('final')" style="flex: 1;">Use Template</button>
                            <button class="button" onclick="editTemplate('final')">Edit</button>
                            <button class="button" onclick="previewTemplate('final')">Preview</button>
                        </div>
                    </div>
                </div>
                
                <!-- Worksheet Template -->
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                    <div style="background: #764ba2; color: white; padding: 15px;">
                        <h3 style="margin: 0; color: white;">Worksheet</h3>
                        <span style="font-size: 12px; opacity: 0.9;">Student Friendly</span>
                    </div>
                    <div style="padding: 20px;">
                        <p><strong>Layout:</strong> Interactive</p>
                        <p><strong>Sections:</strong> Fill in blanks</p>
                        <p><strong>Style:</strong> Informal</p>
                        <p><strong>Font:</strong> Comic Sans MS</p>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px;">
                            <button class="button button-primary" onclick="useTemplate('worksheet')" style="flex: 1;">Use Template</button>
                            <button class="button" onclick="editTemplate('worksheet')">Edit</button>
                            <button class="button" onclick="previewTemplate('worksheet')">Preview</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Template Editor (Hidden by default) -->
        <div id="template-editor" style="display: none; background: white; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Template Editor</h2>
                <button class="button" onclick="closeTemplateEditor()">Close</button>
            </div>
            
            <form id="template-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Template Name</label>
                        <input type="text" id="template-name" style="width: 100%; padding: 8px;" placeholder="Enter template name">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Layout</label>
                        <select id="template-layout" style="width: 100%; padding: 8px;">
                            <option value="single">Single Column</option>
                            <option value="two-column">Two Columns</option>
                            <option value="multi-section">Multi-section</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Style</label>
                        <select id="template-style" style="width: 100%; padding: 8px;">
                            <option value="professional">Professional</option>
                            <option value="modern">Modern</option>
                            <option value="classic">Classic</option>
                            <option value="minimal">Minimal</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Sections</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                        <label><input type="checkbox" name="sections[]" value="header" checked> Header</label>
                        <label><input type="checkbox" name="sections[]" value="instructions" checked> Instructions</label>
                        <label><input type="checkbox" name="sections[]" value="mcq" checked> MCQ Section</label>
                        <label><input type="checkbox" name="sections[]" value="short"> Short Answer</label>
                        <label><input type="checkbox" name="sections[]" value="long"> Long Answer</label>
                        <label><input type="checkbox" name="sections[]" value="answer_key"> Answer Key</label>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="button button-primary" onclick="saveTemplate()">Save Template</button>
                    <button type="button" class="button" onclick="previewCurrentTemplate()">Preview</button>
                    <button type="button" class="button button-link-delete" onclick="deleteTemplate()">Delete</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function createNewTemplate() {
        document.getElementById('template-editor').style.display = 'block';
        document.getElementById('template-name').value = '';
        document.getElementById('template-form').reset();
        window.scrollTo({ top: document.getElementById('template-editor').offsetTop, behavior: 'smooth' });
    }
    
    function useTemplate(templateId) {
        alert('Using template: ' + templateId + '\nThis will apply the template settings to your next paper.');
    }
    
    function editTemplate(templateId) {
        document.getElementById('template-editor').style.display = 'block';
        document.getElementById('template-name').value = templateId.charAt(0).toUpperCase() + templateId.slice(1) + ' Template';
        // In real implementation, load template settings
        window.scrollTo({ top: document.getElementById('template-editor').offsetTop, behavior: 'smooth' });
    }
    
    function previewTemplate(templateId) {
        window.open('<?php echo admin_url('admin.php?page=spg-create-paper&template='); ?>' + templateId, '_blank');
    }
    
    function closeTemplateEditor() {
        document.getElementById('template-editor').style.display = 'none';
    }
    
    function saveTemplate() {
        var name = document.getElementById('template-name').value;
        if (!name) {
            alert('Please enter a template name.');
            return;
        }
        alert('Template "' + name + '" saved successfully!');
        closeTemplateEditor();
    }
    
    function previewCurrentTemplate() {
        var name = document.getElementById('template-name').value;
        alert('Previewing template: ' + name);
    }
    
    function deleteTemplate() {
        if (confirm('Are you sure you want to delete this template?')) {
            alert('Template deleted.');
            closeTemplateEditor();
        }
    }
    </script>
    <?php
}

// ============ PREMIUM SETTINGS PAGE ============
function spg_premium_settings_page() {
    ?>
    <div class="wrap">
        <h1>★ Premium Settings <span style="background: #ffb900; color: #333; padding: 3px 10px; border-radius: 12px; font-size: 14px;">Premium</span></h1>
        
        <div style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <form method="post" action="">
                <?php wp_nonce_field('spg_premium_settings'); ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                    <!-- School Logo -->
                    <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #0073aa;">
                        <h3>School Logo</h3>
                        <div id="logo-preview" style="border: 2px dashed #ddd; padding: 20px; text-align: center; margin: 15px 0; min-height: 100px; display: flex; align-items: center; justify-content: center;">
                            <p style="color: #666;">No logo uploaded</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" class="button" onclick="uploadLogo()">Upload Logo</button>
                            <button type="button" class="button" onclick="removeLogo()">Remove Logo</button>
                        </div>
                        <input type="hidden" id="school_logo_url" name="school_logo_url">
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">Recommended size: 300x100 pixels. Will appear on all papers.</p>
                    </div>
                    
                    <!-- Export Settings -->
                    <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #46b450;">
                        <h3>Export Settings</h3>
                        <div style="margin: 15px 0;">
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_pdf_export" checked> Enable PDF Export
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_word_export" checked> Enable Word Export
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_excel_export"> Enable Excel Export
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_html_export"> Enable HTML Export
                            </label>
                        </div>
                    </div>
                    
                    <!-- Advanced Features -->
                    <div style="background: white; padding: 20px; border-radius: 5px; border: 2px solid #ffb900;">
                        <h3>Advanced Features</h3>
                        <div style="margin: 15px 0;">
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_analytics" checked> Enable Advanced Analytics
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_randomization" checked> Enable Question Randomization
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_bulk_ops" checked> Enable Bulk Operations
                            </label>
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="enable_templates" checked> Enable Paper Templates
                            </label>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: center;">
                    <input type="submit" name="save_premium_settings" class="button button-primary button-large" value="Save Premium Settings">
                </div>
            </form>
        </div>
        
        <!-- License Information -->
        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>License Information</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">License Status</th>
                    <td><span style="color: #46b450; font-weight: bold;">★ Premium Active</span></td>
                </tr>
                <tr>
                    <th scope="row">License Key</th>
                    <td>SPG-PRO-XXXX-XXXX-XXXX-XXXX</td>
                </tr>
                <tr>
                    <th scope="row">Expiry Date</th>
                    <td>December 31, 2026</td>
                </tr>
                <tr>
                    <th scope="row">Support</th>
                    <td>Priority Support Active (Email: support@yourdomain.com)</td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
    function uploadLogo() {
        alert('Logo upload dialog would open here.\nIn production, use WordPress media uploader.');
        // Simulate upload
        document.getElementById('logo-preview').innerHTML = '<img src="https://via.placeholder.com/300x100/0073aa/ffffff?text=School+Logo" style="max-width: 100%; max-height: 100px;">';
        document.getElementById('school_logo_url').value = 'https://via.placeholder.com/300x100/0073aa/ffffff?text=School+Logo';
    }
    
    function removeLogo() {
        document.getElementById('logo-preview').innerHTML = '<p style="color: #666;">No logo uploaded</p>';
        document.getElementById('school_logo_url').value = '';
    }
    </script>
    <?php
}

// ============ PREMIUM FEATURE FUNCTIONS ============

// Remove question limit for premium
function spg_premium_question_limit($limit) {
    if (SPG_PREMIUM_ACTIVE) {
        return 0; // 0 means unlimited
    }
    return $limit;
}

// Add premium export options
function spg_add_premium_export_options($options) {
    if (SPG_PREMIUM_ACTIVE) {
        $premium_options = array(
            'word' => 'Microsoft Word (.docx)',
            'excel' => 'Microsoft Excel (.xlsx)',
            'html' => 'HTML Document',
            'odt' => 'OpenDocument Text'
        );
        return array_merge($options, $premium_options);
    }
    return $options;
}

// Add school logo to papers
function spg_add_school_logo_to_paper($paper_id) {
    if (SPG_PREMIUM_ACTIVE && get_option('spg_school_logo')) {
        $logo_url = get_option('spg_school_logo');
        echo '<div class="school-logo" style="text-align: center; margin: 20px 0;">';
        echo '<img src="' . esc_url($logo_url) . '" alt="School Logo" style="max-height: 80px;">';
        echo '</div>';
    }
}

// Add premium dashboard widget
function spg_add_premium_dashboard_widget() {
    if (SPG_PREMIUM_ACTIVE && current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'spg_premium_widget',
            '★ Paper Generator Premium',
            'spg_premium_dashboard_widget_content'
        );
    }
}

function spg_premium_dashboard_widget_content() {
    global $wpdb;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $today_papers = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers WHERE DATE(created_at) = %s",
        $today
    ));
    
    $yesterday_papers = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers WHERE DATE(created_at) = %s",
        $yesterday
    ));
    ?>
    <div style="padding: 10px;">
        <h3 style="margin-top: 0; color: #ffb900;">★ Premium Active</h3>
        <p>All premium features are enabled.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
            <div style="text-align: center; background: #f5f5f5; padding: 10px; border-radius: 5px;">
                <div style="font-size: 20px; font-weight: bold; color: #0073aa;"><?php echo $today_papers; ?></div>
                <div style="font-size: 12px; color: #666;">Today's Papers</div>
            </div>
            <div style="text-align: center; background: #f5f5f5; padding: 10px; border-radius: 5px;">
                <div style="font-size: 20px; font-weight: bold; color: #46b450;"><?php echo $yesterday_papers; ?></div>
                <div style="font-size: 12px; color: #666;">Yesterday's Papers</div>
            </div>
        </div>
        
        <div style="margin-top: 15px;">
            <a href="<?php echo admin_url('admin.php?page=spg-analytics'); ?>" class="button button-small" style="width: 100%; text-align: center;">View Analytics</a>
        </div>
    </div>
    <?php
}

// ============ ENHANCE EXISTING FEATURES WITH PREMIUM ============

// Update the dashboard page to show premium status
add_action('spg_dashboard_after_stats', 'spg_show_premium_dashboard_status');
function spg_show_premium_dashboard_status() {
    if (SPG_PREMIUM_ACTIVE) {
        ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;">
            <h3 style="color: white; margin-top: 0;">★ Premium Features Active</h3>
            <p style="color: rgba(255,255,255,0.9);">All premium features are unlocked and active.</p>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 15px;">
                <span style="background: rgba(255,255,255,0.2); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">Unlimited Questions</span>
                <span style="background: rgba(255,255,255,0.2); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">Multiple Export Formats</span>
                <span style="background: rgba(255,255,255,0.2); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">School Logo</span>
                <span style="background: rgba(255,255,255,0.2); color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;">Advanced Analytics</span>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;">
            <h3 style="color: white; margin-top: 0;">★ Upgrade to Premium</h3>
            <p style="color: rgba(255,255,255,0.9); margin: 10px 0;">Unlock all features with premium version!</p>
            <button class="button button-primary" style="background: white; color: #667eea; border: none; font-weight: bold; padding: 10px 20px;">Upgrade Now</button>
        </div>
        <?php
    }
}

// Enhance export page with premium options
add_filter('spg_export_page_options', 'spg_add_premium_export_buttons');
function spg_add_premium_export_buttons($buttons) {
    if (SPG_PREMIUM_ACTIVE) {
        $premium_buttons = array(
            'word' => array(
                'label' => 'Export as Word',
                'class' => 'button button-primary',
                'icon' => 'dashicons-media-document'
            ),
            'excel' => array(
                'label' => 'Export as Excel',
                'class' => 'button button-primary',
                'icon' => 'dashicons-media-spreadsheet'
            ),
            'html' => array(
                'label' => 'Export as HTML',
                'class' => 'button',
                'icon' => 'dashicons-editor-code'
            )
        );
        return array_merge($buttons, $premium_buttons);
    }
    return $buttons;
}

// Add premium features to question bank page
add_action('spg_question_bank_after_form', 'spg_show_premium_question_features');
function spg_show_premium_question_features() {
    if (SPG_PREMIUM_ACTIVE) {
        ?>
        <div style="background: #f0f8ff; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; border-radius: 0 5px 5px 0;">
            <h4>★ Premium Features Available:</h4>
            <ul style="margin: 10px 0 0 20px;">
                <li>Unlimited questions (no restrictions)</li>
                <li>Bulk import/export from CSV/Excel</li>
                <li>Question templates and categories</li>
                <li>Advanced search and filtering</li>
            </ul>
        </div>
        <?php
    } else {
        global $wpdb;
        $question_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
        $question_limit = get_option('spg_question_limit', SPG_QUESTION_LIMIT);
        
        if ($question_count >= $question_limit * 0.8) { // Show warning at 80% limit
            ?>
            <div style="background: #fff8e5; padding: 15px; margin: 15px 0; border-left: 4px solid #ffb900; border-radius: 0 5px 5px 0;">
                <h4>⚠ Question Limit Approaching</h4>
                <p>You have used <?php echo $question_count; ?> of <?php echo $question_limit; ?> questions. 
                <a href="#" style="color: #0073aa; font-weight: bold;">Upgrade to premium</a> for unlimited questions.</p>
                <div style="background: #f5f5f5; height: 10px; border-radius: 5px; margin: 10px 0; overflow: hidden;">
                    <div style="background: #ffb900; height: 100%; width: <?php echo ($question_count / $question_limit) * 100; ?>%;"></div>
                </div>
            </div>
            <?php
        }
    }
}

// ============ USER-FACING PREMIUM FEATURES ============

// Add premium shortcode for students/parents
add_shortcode('spg_premium_features', 'spg_premium_features_shortcode');
function spg_premium_features_shortcode() {
    ob_start();
    ?>
    <div class="spg-premium-features" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; color: #333; margin-bottom: 30px;">★ Premium Learning Features</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="text-align: center; padding: 20px;">
                <div style="background: #0073aa; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 15px; font-size: 24px;">
                    📚
                </div>
                <h3 style="margin: 0 0 10px 0;">Unlimited Practice</h3>
                <p style="color: #666;">Access unlimited practice questions and mock tests.</p>
            </div>
            
            <div style="text-align: center; padding: 20px;">
                <div style="background: #46b450; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 15px; font-size: 24px;">
                    📊
                </div>
                <h3 style="margin: 0 0 10px 0;">Performance Analytics</h3>
                <p style="color: #666;">Track progress with detailed analytics and reports.</p>
            </div>
            
            <div style="text-align: center; padding: 20px;">
                <div style="background: #ffb900; color: #333; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 15px; font-size: 24px;">
                    🎯
                </div>
                <h3 style="margin: 0 0 10px 0;">Personalized Tests</h3>
                <p style="color: #666;">Get personalized tests based on your performance.</p>
            </div>
            
            <div style="text-align: center; padding: 20px;">
                <div style="background: #764ba2; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 15px; font-size: 24px;">
                    📱
                </div>
                <h3 style="margin: 0 0 10px 0;">Mobile Access</h3>
                <p style="color: #666;">Access all features on mobile devices.</p>
            </div>
        </div>
        
        <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px;">
            <h3 style="color: white; margin-top: 0;">Ready to Upgrade?</h3>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 20px;">Unlock all premium features for students and parents.</p>
            <a href="#" class="button" style="background: white; color: #667eea; padding: 12px 30px; font-size: 16px; font-weight: bold; text-decoration: none; border-radius: 5px;">Upgrade to Premium</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Add premium features to public question bank
add_filter('spg_question_bank_shortcode_content', 'spg_add_premium_to_public_bank', 10, 2);
function spg_add_premium_to_public_bank($content, $atts) {
    if (!SPG_PREMIUM_ACTIVE) {
        $premium_content = '
        <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #ffb900; border-radius: 0 5px 5px 0;">
            <h4 style="margin-top: 0; color: #333;">★ Get More Questions with Premium</h4>
            <p>Upgrade to premium for unlimited practice questions, detailed analytics, and personalized tests.</p>
            <a href="#" style="display: inline-block; background: #ffb900; color: #333; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px;">Learn More</a>
        </div>';
        
        $content = $premium_content . $content;
    }
    return $content;
}


// ============ PREMIUM ACCESS MANAGEMENT ============

// Check if user has premium access
function spg_user_has_premium_access($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if user is admin (always has premium)
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    global $wpdb;
    
    // Check active premium subscription
    $subscription = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spg_subscriptions 
         WHERE user_id = %d 
         AND status = 'active' 
         AND subscription_type = 'premium'
         AND (end_date IS NULL OR end_date > NOW())",
        $user_id
    ));
    
    if ($subscription) {
        return true;
    }
    
    // Check trial access
    $trial = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spg_subscriptions 
         WHERE user_id = %d 
         AND status = 'active' 
         AND subscription_type = 'trial'
         AND trial_end_date > NOW()",
        $user_id
    ));
    
    return !empty($trial);
}

// Get user subscription details
function spg_get_user_subscription($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spg_subscriptions 
         WHERE user_id = %d 
         AND status = 'active'
         ORDER BY created_at DESC LIMIT 1",
        $user_id
    ));
}

// Get days remaining in trial/premium
function spg_get_days_remaining($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $subscription = spg_get_user_subscription($user_id);
    
    if (!$subscription) {
        return 0;
    }
    
    $end_date = $subscription->subscription_type === 'trial' 
                ? $subscription->trial_end_date 
                : $subscription->end_date;
    
    if (!$end_date) {
        return 999; // Lifetime access
    }
    
    $now = new DateTime();
    $end = new DateTime($end_date);
    
    if ($end < $now) {
        return 0;
    }
    
    $interval = $now->diff($end);
    return $interval->days;
}

// Grant trial access to user
function spg_grant_trial_access($user_id, $days = null) {
    if (!$days) {
        $days = get_option('spg_trial_days', 30);
    }
    
    global $wpdb;
    
    // End any existing trial
    $wpdb->update(
        $wpdb->prefix . 'spg_subscriptions',
        array('status' => 'expired'),
        array(
            'user_id' => $user_id,
            'subscription_type' => 'trial',
            'status' => 'active'
        )
    );
    
    // Calculate trial end date
    $trial_end = date('Y-m-d H:i:s', strtotime("+{$days} days"));
    
    // Grant new trial
    $wpdb->insert(
        $wpdb->prefix . 'spg_subscriptions',
        array(
            'user_id' => $user_id,
            'subscription_type' => 'trial',
            'status' => 'active',
            'trial_end_date' => $trial_end,
            'features' => json_encode(array(
                'unlimited_questions' => true,
                'export_formats' => array('pdf'),
                'analytics' => false,
                'bulk_operations' => false
            ))
        )
    );
    
    // Update user meta
    update_user_meta($user_id, 'spg_trial_start', current_time('mysql'));
    update_user_meta($user_id, 'spg_trial_end', $trial_end);
    
    return $wpdb->insert_id;
}

// Upgrade user to premium
function spg_upgrade_to_premium($user_id, $plan_data = array()) {
    global $wpdb;
    
    $default_plan = array(
        'amount' => get_option('spg_premium_price', 99.99),
        'currency' => 'USD',
        'duration' => 365, // days
        'features' => array(
            'unlimited_questions' => true,
            'export_formats' => array('pdf', 'word', 'excel', 'html'),
            'advanced_analytics' => true,
            'bulk_operations' => true,
            'paper_templates' => true,
            'school_logo' => true,
            'priority_support' => true
        )
    );
    
    $plan = wp_parse_args($plan_data, $default_plan);
    
    // Calculate end date
    $end_date = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));
    
    // End any existing subscription
    $wpdb->update(
        $wpdb->prefix . 'spg_subscriptions',
        array('status' => 'expired'),
        array(
            'user_id' => $user_id,
            'status' => 'active'
        )
    );
    
    // Create premium subscription
    $wpdb->insert(
        $wpdb->prefix . 'spg_subscriptions',
        array(
            'user_id' => $user_id,
            'subscription_type' => 'premium',
            'status' => 'active',
            'end_date' => $end_date,
            'payment_amount' => $plan['amount'],
            'payment_currency' => $plan['currency'],
            'features' => json_encode($plan['features'])
        )
    );
    
    // Update user meta
    update_user_meta($user_id, 'spg_premium_start', current_time('mysql'));
    update_user_meta($user_id, 'spg_premium_end', $end_date);
    update_user_meta($user_id, 'spg_premium_features', $plan['features']);
    
    // Send upgrade email
    spg_send_upgrade_email($user_id);
    
    return $wpdb->insert_id;
}

// Check user's question limit
function spg_get_user_question_limit($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (spg_user_has_premium_access($user_id)) {
        return 0; // Unlimited for premium users
    }
    
    // For free/trial users, get current usage
    global $wpdb;
    
    $question_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions WHERE user_id = %d",
        $user_id
    ));
    
    $limit = get_option('spg_question_limit', SPG_QUESTION_LIMIT);
    
    return max(0, $limit - $question_count);
}

// ============ PAYMENT PROCESSING ============

// Process payment and upgrade
function spg_process_payment($user_id, $payment_data) {
    global $wpdb;
    
    // Create payment record
    $payment_id = $wpdb->insert(
        $wpdb->prefix . 'spg_payments',
        array(
            'user_id' => $user_id,
            'amount' => $payment_data['amount'],
            'currency' => $payment_data['currency'] ?? 'USD',
            'payment_method' => $payment_data['method'],
            'transaction_id' => $payment_data['transaction_id'] ?? '',
            'status' => 'pending',
            'description' => $payment_data['description'] ?? 'Premium Subscription',
            'metadata' => json_encode($payment_data['metadata'] ?? array())
        )
    );
    
    if (!$payment_id) {
        return false;
    }
    
    // Process payment based on method
    $result = false;
    
    switch ($payment_data['method']) {
        case 'paypal':
            $result = spg_process_paypal_payment($payment_data);
            break;
            
        case 'stripe':
            $result = spg_process_stripe_payment($payment_data);
            break;
            
        case 'bank_transfer':
            $result = spg_process_bank_transfer($payment_data);
            break;
            
        default:
            $result = false;
    }
    
    if ($result) {
        // Update payment status
        $wpdb->update(
            $wpdb->prefix . 'spg_payments',
            array(
                'status' => 'completed',
                'transaction_id' => $result['transaction_id'] ?? $payment_data['transaction_id']
            ),
            array('id' => $payment_id)
        );
        
        // Upgrade user to premium
        $plan_data = array(
            'amount' => $payment_data['amount'],
            'currency' => $payment_data['currency'] ?? 'USD',
            'duration' => $payment_data['duration'] ?? 365
        );
        
        spg_upgrade_to_premium($user_id, $plan_data);
        
        return array(
            'success' => true,
            'payment_id' => $payment_id,
            'subscription_id' => spg_get_user_subscription($user_id)->id
        );
    }
    
    // Payment failed
    $wpdb->update(
        $wpdb->prefix . 'spg_payments',
        array('status' => 'failed'),
        array('id' => $payment_id)
    );
    
    return false;
}

// Payment gateway integrations (stubs - implement based on your payment provider)
function spg_process_paypal_payment($payment_data) {
    // Implement PayPal API integration
    return array('transaction_id' => 'PAYPAL_' . uniqid());
}

function spg_process_stripe_payment($payment_data) {
    // Implement Stripe API integration
    return array('transaction_id' => 'STRIPE_' . uniqid());
}

function spg_process_bank_transfer($payment_data) {
    // For bank transfer, mark as pending until manually verified
    return array('transaction_id' => 'BANK_' . uniqid());
}

// ============ USER INTERFACE FOR PREMIUM ============

// Add premium menu for logged-in users
add_action('admin_menu', 'spg_user_premium_menu');
function spg_user_premium_menu() {
    if (is_user_logged_in() && !current_user_can('manage_options')) {
        add_menu_page(
            'My Premium',
            'My Premium',
            'read',
            'spg-my-premium',
            'spg_user_premium_page',
            'dashicons-awards',
            30
        );
    }
}

// User premium page
function spg_user_premium_page() {
    $user_id = get_current_user_id();
    $subscription = spg_get_user_subscription($user_id);
    $days_remaining = spg_get_days_remaining($user_id);
    ?>
    
    <div class="wrap">
        <h1>My Premium Account</h1>
        
        <?php if ($subscription): ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; margin: 20px 0; border-radius: 10px;">
                <h2 style="color: white; margin-top: 0;">
                    ★ <?php echo ucfirst($subscription->subscription_type); ?> Account Active
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: bold;"><?php echo $days_remaining; ?></div>
                        <div>Days Remaining</div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: bold;">
                            <?php echo date('M d, Y', strtotime($subscription->subscription_type === 'trial' ? $subscription->trial_end_date : $subscription->end_date)); ?>
                        </div>
                        <div>Valid Until</div>
                    </div>
                    
                    <?php if ($subscription->subscription_type === 'premium'): ?>
                    <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                        <div style="font-size: 24px; font-weight: bold;">
                            $<?php echo number_format($subscription->payment_amount, 2); ?>
                        </div>
                        <div>Amount Paid</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Features List -->
            <div style="background: white; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3>Your Premium Features</h3>
                
                <?php 
                $features = json_decode($subscription->features, true);
                $all_features = array(
                    'unlimited_questions' => 'Unlimited Questions',
                    'export_formats' => 'Multiple Export Formats',
                    'advanced_analytics' => 'Advanced Analytics',
                    'bulk_operations' => 'Bulk Operations',
                    'paper_templates' => 'Paper Templates',
                    'school_logo' => 'School Logo',
                    'priority_support' => 'Priority Support'
                );
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php foreach ($all_features as $key => $label): ?>
                        <div style="padding: 15px; border: 1px solid #eee; border-radius: 5px; background: #f9f9f9;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if (isset($features[$key]) && $features[$key]): ?>
                                    <span style="color: #46b450;">✓</span>
                                <?php else: ?>
                                    <span style="color: #ccc;">✗</span>
                                <?php endif; ?>
                                <span><?php echo $label; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Upgrade Options -->
            <?php if ($subscription->subscription_type !== 'premium'): ?>
            <div style="background: white; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h3>Upgrade to Full Premium</h3>
                <p>Get all features with our premium plan!</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                    <!-- Monthly Plan -->
                    <div style="border: 2px solid #0073aa; border-radius: 8px; padding: 20px;">
                        <h4>Monthly Plan</h4>
                        <div style="font-size: 36px; font-weight: bold; color: #0073aa;">$9.99</div>
                        <div style="color: #666; margin-bottom: 20px;">per month</div>
                        <button class="button button-primary" style="width: 100%;" onclick="upgradeToPremium('monthly')">Upgrade Now</button>
                    </div>
                    
                    <!-- Yearly Plan (Best Value) -->
                    <div style="border: 2px solid #ffb900; border-radius: 8px; padding: 20px; position: relative;">
                        <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #ffb900; color: #333; padding: 5px 15px; border-radius: 15px; font-weight: bold;">
                            Best Value
                        </div>
                        <h4>Yearly Plan</h4>
                        <div style="font-size: 36px; font-weight: bold; color: #ffb900;">$99.99</div>
                        <div style="color: #666; margin-bottom: 20px;">per year (Save 20%)</div>
                        <button class="button button-primary" style="width: 100%; background: #ffb900; border-color: #ffb900;" onclick="upgradeToPremium('yearly')">Upgrade Now</button>
                    </div>
                    
                    <!-- Lifetime Plan -->
                    <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px;">
                        <h4>Lifetime Plan</h4>
                        <div style="font-size: 36px; font-weight: bold; color: #46b450;">$299.99</div>
                        <div style="color: #666; margin-bottom: 20px;">one-time payment</div>
                        <button class="button button-primary" style="width: 100%; background: #46b450; border-color: #46b450;" onclick="upgradeToPremium('lifetime')">Upgrade Now</button>
                    </div>
                </div>
                
                <!-- Payment Methods -->
                <div style="margin-top: 30px;">
                    <h4>Payment Methods</h4>
                    <div style="display: flex; justify-content: center; gap: 20px; margin: 20px 0;">
                        <div style="text-align: center;">
                            <div style="background: #003087; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 10px; font-weight: bold;">PP</div>
                            <div>PayPal</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="background: #635bff; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 10px; font-weight: bold;">S</div>
                            <div>Stripe</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="background: #0073aa; color: white; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; margin: 0 auto 10px; font-weight: bold;">BT</div>
                            <div>Bank Transfer</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- No subscription - show free account -->
            <div style="background: white; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <h2>Free Account</h2>
                <p>You're currently using the free version with limited features.</p>
                
                <div style="max-width: 600px; margin: 30px auto;">
                    <button class="button button-primary button-hero" onclick="startFreeTrial()">
                        Start 30-Day Free Trial
                    </button>
                    <p style="margin-top: 10px; color: #666;">No credit card required</p>
                </div>
                
                <!-- Trial vs Premium Comparison -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px;">
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <h3>Free Trial</h3>
                        <ul style="text-align: left; padding-left: 20px;">
                            <li>30 days free access</li>
                            <li>Up to 100 questions</li>
                            <li>Basic PDF export</li>
                            <li>Standard support</li>
                        </ul>
                    </div>
                    
                    <div style="border: 2px solid #ffb900; border-radius: 8px; padding: 20px; background: #fff8e5;">
                        <h3 style="color: #ffb900;">★ Premium</h3>
                        <ul style="text-align: left; padding-left: 20px;">
                            <li>Unlimited questions</li>
                            <li>Multiple export formats</li>
                            <li>Advanced analytics</li>
                            <li>Bulk operations</li>
                            <li>Paper templates</li>
                            <li>Priority support</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Payment History -->
        <?php 
        global $wpdb;
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spg_payments 
             WHERE user_id = %d 
             ORDER BY created_at DESC",
            $user_id
        ));
        
        if ($payments): ?>
        <div style="background: white; padding: 30px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Payment History</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($payment->created_at)); ?></td>
                        <td><?php echo esc_html($payment->description); ?></td>
                        <td>$<?php echo number_format($payment->amount, 2); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $payment->payment_method)); ?></td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; 
                                background: <?php echo $payment->status === 'completed' ? '#d4edda' : '#f8d7da'; ?>; 
                                color: <?php echo $payment->status === 'completed' ? '#155724' : '#721c24'; ?>;">
                                <?php echo ucfirst($payment->status); ?>
                            </span>
                        </td>
                        <td><code><?php echo esc_html($payment->transaction_id); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function startFreeTrial() {
        if (confirm('Start your 30-day free trial? No credit card required.')) {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'spg_start_free_trial',
                    _wpnonce: '<?php echo wp_create_nonce('spg_trial_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Trial started successfully! You now have 30 days of premium access.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    }
    
    function upgradeToPremium(plan) {
        var amount, duration;
        
        switch(plan) {
            case 'monthly':
                amount = 9.99;
                duration = 30;
                break;
            case 'yearly':
                amount = 99.99;
                duration = 365;
                break;
            case 'lifetime':
                amount = 299.99;
                duration = 9999;
                break;
        }
        
        // Show payment modal
        showPaymentModal(plan, amount, duration);
    }
    
    function showPaymentModal(plan, amount, duration) {
        var modal = '<div id="payment-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">';
        modal += '<div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">';
        modal += '<h2>Upgrade to Premium</h2>';
        modal += '<p>Plan: ' + plan.charAt(0).toUpperCase() + plan.slice(1) + '</p>';
        modal += '<p>Amount: $' + amount.toFixed(2) + '</p>';
        
        modal += '<div style="margin: 20px 0;">';
        modal += '<label style="display: block; margin-bottom: 10px; font-weight: bold;">Payment Method:</label>';
        modal += '<select id="payment-method" style="width: 100%; padding: 10px;">';
        modal += '<option value="paypal">PayPal</option>';
        modal += '<option value="stripe">Stripe (Credit Card)</option>';
        modal += '<option value="bank_transfer">Bank Transfer</option>';
        modal += '</select>';
        modal += '</div>';
        
        modal += '<div id="card-details" style="display: none; margin: 20px 0;">';
        modal += '<input type="text" placeholder="Card Number" style="width: 100%; padding: 10px; margin-bottom: 10px;">';
        modal += '<input type="text" placeholder="MM/YY" style="width: 48%; padding: 10px; margin-right: 4%;">';
        modal += '<input type="text" placeholder="CVC" style="width: 48%; padding: 10px;">';
        modal += '</div>';
        
        modal += '<div style="display: flex; gap: 10px; margin-top: 20px;">';
        modal += '<button class="button button-primary" onclick="processPayment(\'' + plan + '\', ' + amount + ', ' + duration + ')">Pay Now</button>';
        modal += '<button class="button" onclick="closePaymentModal()">Cancel</button>';
        modal += '</div>';
        modal += '</div></div>';
        
        jQuery('body').append(modal);
        
        // Show/hide card details based on payment method
        jQuery('#payment-method').on('change', function() {
            if (jQuery(this).val() === 'stripe') {
                jQuery('#card-details').show();
            } else {
                jQuery('#card-details').hide();
            }
        });
    }
    
    function closePaymentModal() {
        jQuery('#payment-modal').remove();
    }
    
    function processPayment(plan, amount, duration) {
        var method = jQuery('#payment-method').val();
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'spg_process_payment',
                plan: plan,
                amount: amount,
                duration: duration,
                method: method,
                _wpnonce: '<?php echo wp_create_nonce('spg_payment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Payment successful! Your account has been upgraded to premium.');
                    closePaymentModal();
                    location.reload();
                } else {
                    alert('Payment failed: ' + response.data.message);
                }
            }
        });
    }





    
// ============ MISSING AJAX HANDLERS ============

add_action('wp_ajax_spg_get_dashboard_stats', 'spg_ajax_get_dashboard_stats');
add_action('wp_ajax_nopriv_spg_get_dashboard_stats', 'spg_ajax_get_dashboard_stats');
function spg_ajax_get_dashboard_stats() {
    check_ajax_referer('spg_nonce', 'nonce');
    
    global $wpdb;
    
    $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
    $total_papers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_papers");
    
    // Trial calculation
    $trial_start = get_option('spg_trial_start');
    $days_used = floor((time() - strtotime($trial_start)) / (60 * 60 * 24));
    $days_left = max(0, 30 - $days_used);
    
    // Question limit calculation
    $question_limit = get_option('spg_question_limit', SPG_QUESTION_LIMIT);
    $question_percentage = ($total_questions / max(1, $question_limit)) * 100;
    
    wp_send_json_success(array(
        'total_questions' => $total_questions,
        'total_papers' => $total_papers,
        'days_left' => $days_left,
        'question_percentage' => min(100, $question_percentage)
    ));
}

add_action('wp_ajax_spg_filter_questions', 'spg_ajax_filter_questions');
function spg_ajax_filter_questions() {
    check_ajax_referer('spg_nonce', 'nonce');
    
    global $wpdb;
    
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
    $class_level = isset($_POST['class_level']) ? sanitize_text_field($_POST['class_level']) : '';
    $question_type = isset($_POST['question_type']) ? sanitize_text_field($_POST['question_type']) : '';
    
    $sql = "SELECT * FROM {$wpdb->prefix}spg_questions WHERE 1=1";
    $params = array();
    
    if (!empty($subject)) {
        $sql .= " AND subject LIKE %s";
        $params[] = '%' . $wpdb->esc_like($subject) . '%';
    }
    
    if (!empty($class_level)) {
        $sql .= " AND class_level LIKE %s";
        $params[] = '%' . $wpdb->esc_like($class_level) . '%';
    }
    
    if (!empty($question_type)) {
        $sql .= " AND question_type = %s";
        $params[] = $question_type;
    }
    
    $sql .= " ORDER BY id DESC LIMIT 50";
    
    if (!empty($params)) {
        $questions = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $questions = $wpdb->get_results($sql);
    }
    
    wp_send_json_success(array(
        'questions' => $questions,
        'count' => count($questions)
    ));
}

add_action('wp_ajax_spg_delete_question', 'spg_ajax_delete_question');
function spg_ajax_delete_question() {
    check_ajax_referer('spg_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    $question_id = intval($_POST['question_id']);
    
    global $wpdb;
    
    $deleted = $wpdb->delete(
        $wpdb->prefix . 'spg_questions',
        array('id' => $question_id)
    );
    
    if ($deleted) {
        wp_send_json_success(array('message' => 'Question deleted'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete question'));
    }
}

add_action('wp_ajax_spg_export_pdf', 'spg_ajax_export_pdf');
function spg_ajax_export_pdf() {
    check_ajax_referer('spg_nonce', 'nonce');
    
    $paper_id = intval($_POST['paper_id']);
    
    // In a real implementation, you would generate PDF here
    // For now, return a dummy response
    wp_send_json_success(array(
        'message' => 'PDF export requires additional setup',
        'url' => '#',
        'filename' => 'paper-' . $paper_id . '.pdf'
    ));
}

add_action('wp_ajax_spg_save_settings', 'spg_ajax_save_settings');
function spg_ajax_save_settings() {
    check_ajax_referer('spg_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }
    
    $school_name = isset($_POST['school_name']) ? sanitize_text_field($_POST['school_name']) : '';
    $default_subjects = isset($_POST['default_subjects']) ? sanitize_textarea_field($_POST['default_subjects']) : '';
    $default_classes = isset($_POST['default_classes']) ? sanitize_textarea_field($_POST['default_classes']) : '';
    
    update_option('spg_school_name', $school_name);
    update_option('spg_default_subjects', $default_subjects);
    update_option('spg_default_classes', $default_classes);
    
    wp_send_json_success(array('message' => 'Settings saved successfully'));
}

// Frontend AJAX handlers
add_action('wp_ajax_spg_public_filter_questions', 'spg_ajax_public_filter_questions');
add_action('wp_ajax_nopriv_spg_public_filter_questions', 'spg_ajax_public_filter_questions');
function spg_ajax_public_filter_questions() {
    global $wpdb;
    
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
    $class_level = isset($_POST['class_level']) ? sanitize_text_field($_POST['class_level']) : '';
    $question_type = isset($_POST['question_type']) ? sanitize_text_field($_POST['question_type']) : '';
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    
    $sql = "SELECT * FROM {$wpdb->prefix}spg_questions WHERE 1=1";
    $params = array();
    
    if (!empty($subject)) {
        $sql .= " AND subject = %s";
        $params[] = $subject;
    }
    
    if (!empty($class_level)) {
        $sql .= " AND class_level = %s";
        $params[] = $class_level;
    }
    
    if (!empty($question_type)) {
        $sql .= " AND question_type = %s";
        $params[] = $question_type;
    }
    
    $sql .= " ORDER BY RAND() LIMIT %d";
    $params[] = $limit;
    
    if (!empty($params)) {
        $questions = $wpdb->get_results($wpdb->prepare($sql, $params));
    } else {
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spg_questions ORDER BY RAND() LIMIT %d",
            $limit
        ));
    }
    
    // Generate HTML
    $html = '';
    foreach ($questions as $index => $question) {
        $html .= '<div class="spg-question-item">';
        $html .= '<p><strong>Q' . ($index + 1) . ':</strong> ' . wp_kses_post($question->question_text) . '</p>';
        
        if ($question->question_type === 'mcq' && $question->options) {
            $options = json_decode($question->options, true);
            $html .= '<ul>';
            foreach ($options as $opt_index => $option) {
                $html .= '<li>' . esc_html(chr(65 + $opt_index) . '. ' . $option) . '</li>';
            }
            $html .= '</ul>';
        }
        
        $html .= '<button class="show-answer-btn" data-answer-id="answer-' . $question->id . '">Show Answer</button>';
        $html .= '<div id="answer-' . $question->id . '" style="display: none; margin-top: 10px; padding: 10px; background: #f9f9f9;">';
        $html .= '<strong>Answer:</strong> ' . wp_kses_post($question->correct_answer);
        $html .= '</div>';
        $html .= '</div>';
    }
    
    wp_send_json_success(array('html' => $html));
}

add_action('wp_ajax_spg_start_trial_frontend', 'spg_ajax_start_trial_frontend');
function spg_ajax_start_trial_frontend() {
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'You must be logged in'));
    }
    
    // Check if user already has trial
    $existing_trial = spg_get_user_subscription($user_id);
    if ($existing_trial && $existing_trial->subscription_type === 'trial') {
        wp_send_json_error(array('message' => 'You already have an active trial'));
    }
    
    // Grant trial access
    $trial_id = spg_grant_trial_access($user_id);
    
    if ($trial_id) {
        wp_send_json_success(array(
            'message' => 'Trial started successfully',
            'redirect' => admin_url('admin.php?page=spg-dashboard')
        ));
    }
    
    wp_send_json_error(array('message' => 'Failed to start trial'));
}
    </script>
    <?php
}

// ============ AJAX HANDLERS ============

add_action('wp_ajax_spg_start_free_trial', 'spg_ajax_start_free_trial');
function spg_ajax_start_free_trial() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'spg_trial_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'You must be logged in'));
    }
    
    // Check if user already has active trial
    $existing_trial = spg_get_user_subscription($user_id);
    if ($existing_trial && $existing_trial->subscription_type === 'trial' && $existing_trial->status === 'active') {
        wp_send_json_error(array('message' => 'You already have an active trial'));
    }
    
    // Grant trial access
    $trial_id = spg_grant_trial_access($user_id);
    
    if ($trial_id) {
        wp_send_json_success(array(
            'message' => 'Trial started successfully',
            'trial_id' => $trial_id,
            'end_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ));
    }
    
    wp_send_json_error(array('message' => 'Failed to start trial'));
}

add_action('wp_ajax_spg_process_payment', 'spg_ajax_process_payment');
function spg_ajax_process_payment() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'spg_payment_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'You must be logged in'));
    }
    
    $payment_data = array(
        'amount' => floatval($_POST['amount']),
        'currency' => 'USD',
        'method' => sanitize_text_field($_POST['method']),
        'duration' => intval($_POST['duration']),
        'description' => 'Premium ' . sanitize_text_field($_POST['plan']) . ' plan'
    );
    
    $result = spg_process_payment($user_id, $payment_data);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Payment processed successfully',
            'payment_id' => $result['payment_id']
        ));
    }
    
    wp_send_json_error(array('message' => 'Payment processing failed'));
}




// ============ INTEGRATE WITH EXISTING FEATURES ============

// Modify SPG_PREMIUM_ACTIVE to check user access
function spg_is_premium_active() {
    if (current_user_can('manage_options')) {
        return true; // Admins always have premium
    }
    
    return spg_user_has_premium_access(get_current_user_id());
}

// Update all premium checks to use this function
function spg_premium_features_init() {
    if (spg_is_premium_active()) {
        add_action('admin_menu', 'spg_add_premium_menu_items');
        // ... other premium initializations
    }
}

// Show upgrade prompts in free features
add_action('spg_question_bank_before_form', 'spg_show_upgrade_prompt');
function spg_show_upgrade_prompt() {
    if (!spg_is_premium_active()) {
        $question_limit = spg_get_user_question_limit();
        ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin: 0 0 20px 0; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: white; margin: 0 0 10px 0;"><?php echo $question_limit; ?> Questions Remaining</h3>
                    <p style="margin: 0; opacity: 0.9;">Upgrade to premium for unlimited questions</p>
                </div>
                <div>
                    <a href="<?php echo admin_url('admin.php?page=spg-my-premium'); ?>" class="button" style="background: white; color: #667eea; border: none; font-weight: bold;">
                        ★ Upgrade Now
                    </a>
                </div>
            </div>
            
            <?php if ($question_limit < 10): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-warning" style="color: #ffb900;"></span>
                    <span>You're running low on questions. Upgrade to continue creating papers.</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// ============ EMAIL NOTIFICATIONS ============

function spg_send_upgrade_email($user_id) {
    $user = get_userdata($user_id);
    $to = $user->user_email;
    $subject = 'Your Paper Generator Premium Account is Active!';
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #0073aa;'>Welcome to Paper Generator Premium!</h2>
            
            <p>Dear {$user->display_name},</p>
            
            <p>Thank you for upgrading to Paper Generator Premium! Your account is now active with all premium features.</p>
            
            <div style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <h3 style='color: #333;'>Your Premium Features:</h3>
                <ul>
                    <li>✓ Unlimited questions</li>
                    <li>✓ Multiple export formats (PDF, Word, Excel)</li>
                    <li>✓ Advanced analytics and reports</li>
                    <li>✓ Bulk import/export operations</li>
                    <li>✓ Paper templates</li>
                    <li>✓ Priority support</li>
                </ul>
            </div>
            
            <p>You can access your premium features from your dashboard.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . admin_url('admin.php?page=spg-dashboard') . "' style='background: #0073aa; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Go to Dashboard</a>
            </div>
            
            <p>If you have any questions, please contact our support team.</p>
            
            <p>Best regards,<br>
            Paper Generator Team</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Paper Generator <no-reply@' . $_SERVER['HTTP_HOST'] . '>'
    );
    
    wp_mail($to, $subject, $message, $headers);
}

// Send trial expiration reminder
function spg_send_trial_expiry_reminder($user_id, $days_left) {
    if ($days_left <= 7) {
        $user = get_userdata($user_id);
        $to = $user->user_email;
        
        $subject = "Your Paper Generator Trial Expires in {$days_left} Days";
        
        $message = "
        <html>
        <body>
            <h2>Your Trial is Ending Soon!</h2>
            <p>Your Paper Generator trial will expire in {$days_left} days.</p>
            <p>Upgrade to premium to continue using all features and keep your data.</p>
            <a href='" . admin_url('admin.php?page=spg-my-premium') . "'>Upgrade Now</a>
        </body>
        </html>
        ";
        
        wp_mail($to, $subject, $message, array('Content-Type: text/html'));
    }
}

// Cron job for checking expiring trials
add_action('spg_check_expiring_trials', 'spg_check_expiring_trials_cron');
function spg_check_expiring_trials_cron() {
    global $wpdb;
    
    // Get trials expiring in next 7 days
    $trials = $wpdb->get_results("
        SELECT s.*, u.user_email, u.display_name 
        FROM {$wpdb->prefix}spg_subscriptions s
        JOIN {$wpdb->users} u ON s.user_id = u.ID
        WHERE s.subscription_type = 'trial'
        AND s.status = 'active'
        AND s.trial_end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ");
    
    foreach ($trials as $trial) {
        $days_left = ceil((strtotime($trial->trial_end_date) - time()) / (60 * 60 * 24));
        spg_send_trial_expiry_reminder($trial->user_id, $days_left);
    }
    
    // Expire old trials
    $wpdb->query("
        UPDATE {$wpdb->prefix}spg_subscriptions 
        SET status = 'expired'
        WHERE subscription_type = 'trial'
        AND status = 'active'
        AND trial_end_date < NOW()
    ");
}

// Schedule cron job
register_activation_hook(__FILE__, 'spg_schedule_cron_jobs');
function spg_schedule_cron_jobs() {
    if (!wp_next_scheduled('spg_check_expiring_trials')) {
        wp_schedule_event(time(), 'daily', 'spg_check_expiring_trials');
    }
}

register_deactivation_hook(__FILE__, 'spg_unschedule_cron_jobs');
function spg_unschedule_cron_jobs() {
    wp_clear_scheduled_hook('spg_check_expiring_trials');
}




// ============ FRONTEND SHORTCODES ============

// Premium pricing table shortcode
add_shortcode('spg_pricing_table', 'spg_pricing_table_shortcode');
function spg_pricing_table_shortcode() {
    ob_start();
    ?>
    <div class="spg-pricing-table">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin: 40px 0;">
            <!-- Free Plan -->
            <div style="border: 2px solid #ddd; border-radius: 10px; padding: 30px; text-align: center; position: relative;">
                <h3 style="margin-top: 0;">Free</h3>
                <div style="font-size: 36px; font-weight: bold; color: #333;">$0</div>
                <div style="color: #666; margin-bottom: 30px;">forever</div>
                
                <ul style="text-align: left; padding-left: 20px; margin-bottom: 30px;">
                    <li>Up to 50 questions</li>
                    <li>Basic PDF export</li>
                    <li>Standard support</li>
                    <li>Community access</li>
                </ul>
                
                <a href="<?php echo wp_registration_url(); ?>" class="button" style="width: 100%;">Get Started</a>
            </div>
            
            <!-- Trial Plan -->
            <div style="border: 2px solid #0073aa; border-radius: 10px; padding: 30px; text-align: center; position: relative;">
                <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #0073aa; color: white; padding: 5px 20px; border-radius: 20px; font-weight: bold;">
                    Most Popular
                </div>
                <h3 style="margin-top: 0;">30-Day Trial</h3>
                <div style="font-size: 36px; font-weight: bold; color: #0073aa;">$0</div>
                <div style="color: #666; margin-bottom: 30px;">for 30 days</div>
                
                <ul style="text-align: left; padding-left: 20px; margin-bottom: 30px;">
                    <li>All premium features</li>
                    <li>Unlimited questions</li>
                    <li>Multiple export formats</li>
                    <li>Priority support</li>
                    <li>No credit card required</li>
                </ul>
                
                <?php if (is_user_logged_in()): ?>
                    <button class="button button-primary" style="width: 100%;" onclick="spgStartTrial()">Start Free Trial</button>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>" class="button button-primary" style="width: 100%;">Login to Start Trial</a>
                <?php endif; ?>
            </div>
            
            <!-- Premium Plan -->
            <div style="border: 2px solid #ffb900; border-radius: 10px; padding: 30px; text-align: center; position: relative;">
                <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #ffb900; color: #333; padding: 5px 20px; border-radius: 20px; font-weight: bold;">
                    Best Value
                </div>
                <h3 style="margin-top: 0;">Premium</h3>
                <div style="font-size: 36px; font-weight: bold; color: #ffb900;">$99.99</div>
                <div style="color: #666; margin-bottom: 30px;">per year</div>
                
                <ul style="text-align: left; padding-left: 20px; margin-bottom: 30px;">
                    <li>Everything in Trial</li>
                    <li>Lifetime updates</li>
                    <li>Advanced analytics</li>
                    <li>Bulk operations</li>
                    <li>Paper templates</li>
                    <li>School logo support</li>
                </ul>
                
                <?php if (is_user_logged_in()): ?>
                    <button class="button" style="width: 100%; background: #ffb900; border-color: #ffb900;" onclick="spgUpgradePremium()">Upgrade Now</button>
                <?php else: ?>
                    <a href="<?php echo wp_registration_url(); ?>" class="button" style="width: 100%; background: #ffb900; border-color: #ffb900;">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function spgStartTrial() {
        if (confirm('Start your 30-day free trial? No credit card required.')) {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'spg_start_free_trial',
                    _wpnonce: '<?php echo wp_create_nonce('spg_trial_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Trial started! Redirecting to dashboard...');
                        window.location.href = '<?php echo admin_url('admin.php?page=spg-dashboard'); ?>';
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    }
    
    function spgUpgradePremium() {
        window.location.href = '<?php echo admin_url('admin.php?page=spg-my-premium'); ?>';
    }
    </script>
    <?php
    return ob_get_clean();
}

// User account status shortcode
add_shortcode('spg_my_account', 'spg_my_account_shortcode');
function spg_my_account_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url() . '">login</a> to view your account.</p>';
    }
    
    $user_id = get_current_user_id();
    $subscription = spg_get_user_subscription($user_id);
    
    ob_start();
    ?>
    <div class="spg-my-account">
        <h3>My Paper Generator Account</h3>
        
        <?php if ($subscription): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h4><?php echo ucfirst($subscription->subscription_type); ?> Account</h4>
                <p>Status: <span style="color: #46b450;">Active</span></p>
                <p>Valid until: <?php echo date('F d, Y', strtotime($subscription->subscription_type === 'trial' ? $subscription->trial_end_date : $subscription->end_date)); ?></p>
                <a href="<?php echo admin_url('admin.php?page=spg-my-premium'); ?>" class="button button-small">Manage Account</a>
            </div>
        <?php else: ?>
            <div style="background: #fff8e5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h4>Free Account</h4>
                <p>Upgrade to unlock premium features!</p>
                <a href="<?php echo admin_url('admin.php?page=spg-my-premium'); ?>" class="button button-small button-primary">Upgrade Now</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=spg-dashboard'); ?>" class="button">Go to Dashboard</a>
            <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button">Create Paper</a>
            <a href="<?php echo admin_url('admin.php?page=spg-question-bank'); ?>" class="button">Question Bank</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


// ============ ADMIN USER MANAGEMENT ============

// Add user management to admin
add_action('admin_menu', 'spg_add_user_management_menu');
function spg_add_user_management_menu() {
    add_submenu_page(
        'spg-dashboard',
        'User Management',
        'User Management',
        'manage_options',
        'spg-user-management',
        'spg_user_management_page'
    );
}

function spg_user_management_page() {
    global $wpdb;
    
    // Handle actions
    if (isset($_GET['action']) && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $action = sanitize_text_field($_GET['action']);
        
        switch ($action) {
            case 'grant_trial':
                spg_grant_trial_access($user_id);
                echo '<div class="notice notice-success"><p>Trial granted to user.</p></div>';
                break;
                
            case 'upgrade_premium':
                spg_upgrade_to_premium($user_id);
                echo '<div class="notice notice-success"><p>User upgraded to premium.</p></div>';
                break;
                
            case 'revoke_access':
                $wpdb->update(
                    $wpdb->prefix . 'spg_subscriptions',
                    array('status' => 'expired'),
                    array('user_id' => $user_id, 'status' => 'active')
                );
                echo '<div class="notice notice-success"><p>User access revoked.</p></div>';
                break;
        }
    }
    
    // Get all users with subscriptions
    $users = $wpdb->get_results("
        SELECT u.ID, u.user_email, u.display_name, 
               s.subscription_type, s.status, s.start_date, s.end_date, s.trial_end_date
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}spg_subscriptions s ON u.ID = s.user_id AND s.status = 'active'
        ORDER BY s.subscription_type DESC, u.display_name ASC
    ");
    ?>
    
    <div class="wrap">
        <h1>User Management</h1>
        
        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>User Subscriptions</h2>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td>
                            <?php if ($user->subscription_type): ?>
                                <span style="padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; 
                                    background: <?php echo $user->subscription_type === 'premium' ? '#ffb900' : '#0073aa'; ?>; 
                                    color: <?php echo $user->subscription_type === 'premium' ? '#333' : 'white'; ?>;">
                                    <?php echo ucfirst($user->subscription_type); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #666;">Free</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user->status): ?>
                                <span style="color: #46b450;">Active</span>
                            <?php else: ?>
                                <span style="color: #666;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user->start_date ? date('Y-m-d', strtotime($user->start_date)) : '-'; ?></td>
                        <td>
                            <?php 
                            $end_date = $user->subscription_type === 'trial' ? $user->trial_end_date : $user->end_date;
                            echo $end_date ? date('Y-m-d', strtotime($end_date)) : '-';
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <?php if (!$user->subscription_type || $user->subscription_type === 'free'): ?>
                                    <a href="<?php echo admin_url('admin.php?page=spg-user-management&action=grant_trial&user_id=' . $user->ID); ?>" class="button button-small">Grant Trial</a>
                                    <a href="<?php echo admin_url('admin.php?page=spg-user-management&action=upgrade_premium&user_id=' . $user->ID); ?>" class="button button-small button-primary">Upgrade to Premium</a>
                                <?php elseif ($user->subscription_type === 'trial'): ?>
                                    <a href="<?php echo admin_url('admin.php?page=spg-user-management&action=upgrade_premium&user_id=' . $user->ID); ?>" class="button button-small button-primary">Upgrade to Premium</a>
                                    <a href="<?php echo admin_url('admin.php?page=spg-user-management&action=revoke_access&user_id=' . $user->ID); ?>" class="button button-small" onclick="return confirm('Revoke access for this user?')">Revoke</a>
                                <?php else: ?>
                                    <a href="<?php echo admin_url('admin.php?page=spg-user-management&action=revoke_access&user_id=' . $user->ID); ?>" class="button button-small" onclick="return confirm('Revoke premium access?')">Revoke</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
            <?php
            $stats = $wpdb->get_results("
                SELECT subscription_type, COUNT(*) as count 
                FROM {$wpdb->prefix}spg_subscriptions 
                WHERE status = 'active'
                GROUP BY subscription_type
            ");
            
            $total_active = array_sum(array_column($stats, 'count'));
            ?>
            
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #0073aa;"><?php echo $total_active; ?></div>
                <div>Active Subscriptions</div>
            </div>
            
            <?php foreach ($stats as $stat): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: 
                    <?php echo $stat->subscription_type === 'premium' ? '#ffb900' : 
                           ($stat->subscription_type === 'trial' ? '#0073aa' : '#46b450'); ?>;">
                    <?php echo $stat->count; ?>
                </div>
                <div><?php echo ucfirst($stat->subscription_type); ?> Users</div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Revenue Statistics -->
        <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Revenue Statistics</h3>
            
            <?php
            $revenue = $wpdb->get_row("
                SELECT 
                    SUM(payment_amount) as total_revenue,
                    COUNT(*) as total_payments
                FROM {$wpdb->prefix}spg_payments 
                WHERE status = 'completed'
            ");
            
            $monthly_revenue = $wpdb->get_row("
                SELECT 
                    SUM(payment_amount) as monthly_revenue,
                    COUNT(*) as monthly_payments
                FROM {$wpdb->prefix}spg_payments 
                WHERE status = 'completed'
                AND MONTH(created_at) = MONTH(NOW())
                AND YEAR(created_at) = YEAR(NOW())
            ");
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #46b450;">
                        $<?php echo number_format($revenue->total_revenue ?? 0, 2); ?>
                    </div>
                    <div>Total Revenue</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #0073aa;">
                        $<?php echo number_format($monthly_revenue->monthly_revenue ?? 0, 2); ?>
                    </div>
                    <div>This Month</div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #ffb900;">
                        <?php echo $revenue->total_payments ?? 0; ?>
                    </div>
                    <div>Total Payments</div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Include upgrade system
require_once SPG_PLUGIN_DIR . 'includes/upgrade.php';

// Update premium active status based on license
function spg_check_premium_status() {
    $license_status = get_option('spg_license_status', 'inactive');
    $license_expiry = get_option('spg_license_expiry');
    
    if ($license_status === 'active' && $license_expiry) {
        $expiry_timestamp = strtotime($license_expiry);
        if ($expiry_timestamp > time()) {
            define('SPG_PREMIUM_ACTIVE', true);
            return;
        }
    }
    
    define('SPG_PREMIUM_ACTIVE', false);
}
add_action('init', 'spg_check_premium_status');

// Add upgrade notice to admin bar
add_action('admin_bar_menu', 'spg_add_upgrade_admin_bar', 100);
function spg_add_upgrade_admin_bar($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $license_status = get_option('spg_license_status', 'inactive');
    $days_left = 0;
    
    if ($license_status === 'active') {
        $expiry_date = get_option('spg_license_expiry');
        if ($expiry_date) {
            $expiry_timestamp = strtotime($expiry_date);
            $current_timestamp = time();
            $days_left = max(0, floor(($expiry_timestamp - $current_timestamp) / (60 * 60 * 24)));
        }
    }
    
    $title = '★ Paper Generator';
    if ($license_status !== 'active') {
        $title .= ' (Free)';
    } elseif ($days_left <= 7) {
        $title .= ' (Expiring: ' . $days_left . 'd)';
    }
    
    $admin_bar->add_menu(array(
        'id'    => 'spg-upgrade-menu',
        'title' => $title,
        'href'  => admin_url('admin.php?page=spg-upgrade'),
        'meta'  => array(
            'title' => __('Manage License & Upgrade', 'school-paper-generator'),
            'class' => $license_status === 'active' ? 'spg-premium-active' : 'spg-free-version'
        ),
    ));
}

// Add CSS for admin bar
add_action('admin_head', 'spg_admin_bar_styles');
add_action('wp_head', 'spg_admin_bar_styles');
function spg_admin_bar_styles() {
    ?>
    <style>
    #wpadminbar .spg-premium-active > .ab-item {
        background: linear-gradient(135deg, #ffb900 0%, #ff8c00 100%) !important;
        color: #333 !important;
        font-weight: bold !important;
    }
    
    #wpadminbar .spg-free-version > .ab-item {
        background: #0073aa !important;
        color: white !important;
    }
    
    #wpadminbar .spg-premium-active > .ab-item:hover {
        background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%) !important;
    }
    
    #wpadminbar .spg-free-version > .ab-item:hover {
        background: #005a87 !important;
    }
    </style>
    <?php
}



// ============ PAPER GENERATION SHORTCODE ============

add_shortcode('spg_generate_paper_form', 'spg_generate_paper_form_shortcode');
function spg_generate_paper_form_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return spg_login_required_message();
    }
    
    // Check user capabilities (teachers/admins)
    if (!current_user_can('edit_posts')) {
        return spg_permission_required_message();
    }
    
    // Enqueue scripts and styles
    wp_enqueue_script('spg-paper-generator-frontend', SPG_PLUGIN_URL . 'assets/js/spg-frontend.js', array('jquery'), SPG_VERSION, true);
    wp_add_inline_style('spg-frontend', spg_frontend_styles());
    
    // Localize script for AJAX
    wp_localize_script('spg-paper-generator-frontend', 'spg_frontend_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('spg_frontend_nonce')
    ));
    
    ob_start();
    ?>
    
    <div class="spg-paper-generator-frontend">
        <!-- Progress Steps -->
        <div class="spg-progress-steps">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Paper Details</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Select Questions</div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Preview & Generate</div>
            </div>
        </div>
        
        <!-- Step 1: Paper Details -->
        <div class="spg-step active" id="step-1">
            <h3>Step 1: Paper Details</h3>
            
            <div class="spg-form-group">
                <label for="paper_title">Paper Title *</label>
                <input type="text" id="paper_title" name="paper_title" required placeholder="e.g., Midterm Exam - Mathematics">
            </div>
            
            <div class="spg-form-row">
                <div class="spg-form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required placeholder="e.g., Mathematics">
                </div>
                
                <div class="spg-form-group">
                    <label for="class_level">Class Level *</label>
                    <input type="text" id="class_level" name="class_level" required placeholder="e.g., Grade 10">
                </div>
            </div>
            
            <div class="spg-form-row">
                <div class="spg-form-group">
                    <label for="total_marks">Total Marks</label>
                    <input type="number" id="total_marks" name="total_marks" min="1" placeholder="Will be calculated">
                </div>
                
                <div class="spg-form-group">
                    <label for="time_allowed">Time Allowed</label>
                    <input type="text" id="time_allowed" name="time_allowed" placeholder="e.g., 2 hours">
                </div>
            </div>
            
            <div class="spg-form-group">
                <label for="instructions">Instructions</label>
                <textarea id="instructions" name="instructions" rows="3" placeholder="Instructions for students..."></textarea>
            </div>
            
            <div class="spg-form-actions">
                <button class="spg-btn spg-btn-primary" onclick="spg_next_step(2)">Next: Select Questions</button>
            </div>
        </div>
        
        <!-- Step 2: Question Selection -->
        <div class="spg-step" id="step-2">
            <h3>Step 2: Select Questions</h3>
            
            <!-- Question Requirements -->
            <div class="spg-question-requirements">
                <h4>Set Question Requirements</h4>
                
                <div class="spg-requirement-row">
                    <div class="spg-requirement-item">
                        <label>MCQ Questions</label>
                        <div class="spg-requirement-controls">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('mcq', -1)">-</button>
                            <input type="number" id="mcq_count" value="10" min="0" max="50" onchange="loadQuestionsByType()">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('mcq', 1)">+</button>
                            <span class="spg-question-mark">(1 mark each)</span>
                        </div>
                    </div>
                    
                    <div class="spg-requirement-item">
                        <label>Short Answer Questions</label>
                        <div class="spg-requirement-controls">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('short', -1)">-</button>
                            <input type="number" id="short_count" value="5" min="0" max="20" onchange="loadQuestionsByType()">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('short', 1)">+</button>
                            <input type="number" id="short_marks" value="2" min="1" max="10" style="width: 60px;" onchange="calculateTotalMarks()">
                            <span>marks each</span>
                        </div>
                    </div>
                    
                    <div class="spg-requirement-item">
                        <label>Long Answer Questions</label>
                        <div class="spg-requirement-controls">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('long', -1)">-</button>
                            <input type="number" id="long_count" value="3" min="0" max="10" onchange="loadQuestionsByType()">
                            <button type="button" class="spg-btn-count" onclick="updateQuestionCount('long', 1)">+</button>
                            <input type="number" id="long_marks" value="5" min="1" max="20" style="width: 60px;" onchange="calculateTotalMarks()">
                            <span>marks each</span>
                        </div>
                    </div>
                </div>
                
                <div class="spg-total-marks-display">
                    <strong>Total Marks: <span id="calculated_total_marks">25</span></strong>
                </div>
            </div>
            
            <!-- Question Selection Area -->
            <div class="spg-question-selection">
                <h4>Select Questions</h4>
                
                <!-- MCQ Questions -->
                <div class="spg-question-type-section" id="mcq-section">
                    <h5>MCQ Questions <span class="spg-count-badge">Selected: <span id="mcq_selected_count">0</span>/<span id="mcq_required_count">10</span></span></h5>
                    <div class="spg-questions-container" id="mcq-questions-container">
                        <!-- MCQ questions will be loaded here -->
                        <div class="spg-loading">Loading MCQ questions...</div>
                    </div>
                </div>
                
                <!-- Short Answer Questions -->
                <div class="spg-question-type-section" id="short-section">
                    <h5>Short Answer Questions <span class="spg-count-badge">Selected: <span id="short_selected_count">0</span>/<span id="short_required_count">5</span></span></h5>
                    <div class="spg-questions-container" id="short-questions-container">
                        <!-- Short answer questions will be loaded here -->
                        <div class="spg-loading">Loading short answer questions...</div>
                    </div>
                </div>
                
                <!-- Long Answer Questions -->
                <div class="spg-question-type-section" id="long-section">
                    <h5>Long Answer Questions <span class="spg-count-badge">Selected: <span id="long_selected_count">0</span>/<span id="long_required_count">3</span></span></h5>
                    <div class="spg-questions-container" id="long-questions-container">
                        <!-- Long answer questions will be loaded here -->
                        <div class="spg-loading">Loading long answer questions...</div>
                    </div>
                </div>
            </div>
            
            <div class="spg-form-actions">
                <button class="spg-btn" onclick="spg_prev_step(1)">Back</button>
                <button class="spg-btn spg-btn-primary" onclick="spg_next_step(3)">Next: Preview & Generate</button>
            </div>
        </div>
        
        <!-- Step 3: Preview & Generate -->
        <div class="spg-step" id="step-3">
            <h3>Step 3: Preview & Generate Paper</h3>
            
            <div class="spg-paper-preview" id="paper-preview">
                <!-- Paper preview will be loaded here -->
                <div class="spg-loading">Generating preview...</div>
            </div>
            
            <div class="spg-form-actions">
                <button class="spg-btn" onclick="spg_prev_step(2)">Back</button>
                <button class="spg-btn spg-btn-success" onclick="generatePaper()">
                    <span class="dashicons dashicons-media-document"></span> Generate Paper
                </button>
                <button class="spg-btn spg-btn-secondary" onclick="downloadPaper()">
                    <span class="dashicons dashicons-download"></span> Download PDF
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Global variables
    let selectedQuestions = {
        mcq: [],
        short: [],
        long: []
    };
    
    function spg_next_step(step) {
        // Hide all steps
        document.querySelectorAll('.spg-step').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.spg-progress-steps .step').forEach(s => s.classList.remove('active'));
        
        // Show target step
        document.getElementById('step-' + step).classList.add('active');
        document.querySelector('.spg-progress-steps .step[data-step="' + step + '"]').classList.add('active');
        
        // Load data for step
        if (step === 2) {
            loadQuestionsByType();
        } else if (step === 3) {
            generatePreview();
        }
    }
    
    function spg_prev_step(step) {
        spg_next_step(step);
    }
    
    function updateQuestionCount(type, change) {
        const input = document.getElementById(type + '_count');
        let value = parseInt(input.value) + change;
        value = Math.max(0, Math.min(value, parseInt(input.max)));
        input.value = value;
        loadQuestionsByType();
        calculateTotalMarks();
    }
    
    function calculateTotalMarks() {
        const mcqCount = parseInt(document.getElementById('mcq_count').value);
        const shortCount = parseInt(document.getElementById('short_count').value);
        const shortMarks = parseInt(document.getElementById('short_marks').value);
        const longCount = parseInt(document.getElementById('long_count').value);
        const longMarks = parseInt(document.getElementById('long_marks').value);
        
        const total = (mcqCount * 1) + (shortCount * shortMarks) + (longCount * longMarks);
        document.getElementById('calculated_total_marks').textContent = total;
        
        // Update total marks field if empty
        const totalMarksField = document.getElementById('total_marks');
        if (!totalMarksField.value || totalMarksField.value === '0') {
            totalMarksField.value = total;
        }
    }
    
    function loadQuestionsByType() {
        const subject = document.getElementById('subject').value;
        const classLevel = document.getElementById('class_level').value;
        
        if (!subject || !classLevel) {
            alert('Please enter subject and class level first.');
            spg_prev_step(1);
            return;
        }
        
        // Update required counts
        document.getElementById('mcq_required_count').textContent = document.getElementById('mcq_count').value;
        document.getElementById('short_required_count').textContent = document.getElementById('short_count').value;
        document.getElementById('long_required_count').textContent = document.getElementById('long_count').value;
        
        // Load questions for each type
        ['mcq', 'short', 'long'].forEach(type => {
            loadQuestions(type, subject, classLevel);
        });
        
        calculateTotalMarks();
    }
    
    function loadQuestions(type, subject, classLevel) {
        const container = document.getElementById(type + '-questions-container');
        container.innerHTML = '<div class="spg-loading">Loading ' + type + ' questions...</div>';
        
        jQuery.ajax({
            url: spg_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_get_questions_frontend',
                nonce: spg_frontend_ajax.nonce,
                question_type: type,
                subject: subject,
                class_level: classLevel,
                limit: 20
            },
            success: function(response) {
                if (response.success) {
                    displayQuestions(type, response.data);
                } else {
                    container.innerHTML = '<div class="spg-error">No questions found. Please add questions in admin panel.</div>';
                }
            }
        });
    }
    
    function displayQuestions(type, questions) {
        const container = document.getElementById(type + '-questions-container');
        const requiredCount = parseInt(document.getElementById(type + '_count').value);
        
        if (questions.length === 0) {
            container.innerHTML = '<div class="spg-no-questions">No ' + type + ' questions available for this subject/class.</div>';
            return;
        }
        
        let html = '<div class="spg-questions-list">';
        questions.forEach(question => {
            const isSelected = selectedQuestions[type].includes(question.id);
            html += `
                <div class="spg-question-item ${isSelected ? 'selected' : ''}" data-id="${question.id}" data-type="${type}">
                    <div class="spg-question-checkbox">
                        <input type="checkbox" id="q_${question.id}" ${isSelected ? 'checked' : ''} 
                               onchange="toggleQuestionSelection('${type}', ${question.id}, ${question.marks})">
                    </div>
                    <div class="spg-question-content">
                        <label for="q_${question.id}">
                            <div class="spg-question-text">${question.question_text.substring(0, 150)}${question.question_text.length > 150 ? '...' : ''}</div>
                            <div class="spg-question-meta">
                                <span class="spg-meta-item">${question.marks} marks</span>
                                <span class="spg-meta-item">${question.difficulty}</span>
                            </div>
                        </label>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
        updateSelectedCounts();
    }
    
    function toggleQuestionSelection(type, questionId, marks) {
        const index = selectedQuestions[type].indexOf(questionId);
        const requiredCount = parseInt(document.getElementById(type + '_count').value);
        const currentCount = selectedQuestions[type].length;
        
        if (index === -1) {
            // Add if not exceeding required count
            if (currentCount < requiredCount) {
                selectedQuestions[type].push(questionId);
            } else {
                alert('You can only select ' + requiredCount + ' ' + type + ' questions.');
                document.getElementById('q_' + questionId).checked = false;
                return;
            }
        } else {
            // Remove
            selectedQuestions[type].splice(index, 1);
        }
        
        // Update UI
        const questionItem = document.querySelector(`.spg-question-item[data-id="${questionId}"]`);
        if (questionItem) {
            questionItem.classList.toggle('selected');
        }
        
        updateSelectedCounts();
    }
    
    function updateSelectedCounts() {
        document.getElementById('mcq_selected_count').textContent = selectedQuestions.mcq.length;
        document.getElementById('short_selected_count').textContent = selectedQuestions.short.length;
        document.getElementById('long_selected_count').textContent = selectedQuestions.long.length;
    }
    
    function generatePreview() {
        const paperData = getPaperData();
        const previewContainer = document.getElementById('paper-preview');
        
        previewContainer.innerHTML = '<div class="spg-loading">Generating preview...</div>';
        
        jQuery.ajax({
            url: spg_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_generate_preview',
                nonce: spg_frontend_ajax.nonce,
                paper_data: JSON.stringify(paperData)
            },
            success: function(response) {
                if (response.success) {
                    previewContainer.innerHTML = response.data.html;
                }
            }
        });
    }
    
    function getPaperData() {
        return {
            title: document.getElementById('paper_title').value,
            subject: document.getElementById('subject').value,
            class_level: document.getElementById('class_level').value,
            total_marks: document.getElementById('total_marks').value || document.getElementById('calculated_total_marks').textContent,
            time_allowed: document.getElementById('time_allowed').value,
            instructions: document.getElementById('instructions').value,
            question_requirements: {
                mcq: {
                    count: document.getElementById('mcq_count').value,
                    marks_each: 1
                },
                short: {
                    count: document.getElementById('short_count').value,
                    marks_each: document.getElementById('short_marks').value
                },
                long: {
                    count: document.getElementById('long_count').value,
                    marks_each: document.getElementById('long_marks').value
                }
            },
            selected_questions: selectedQuestions
        };
    }
    
    function generatePaper() {
        const paperData = getPaperData();
        
        // Validate
        if (!validatePaperData(paperData)) {
            return;
        }
        
        jQuery.ajax({
            url: spg_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_generate_paper_frontend',
                nonce: spg_frontend_ajax.nonce,
                paper_data: JSON.stringify(paperData)
            },
            success: function(response) {
                if (response.success) {
                    alert('Paper generated successfully! Paper ID: ' + response.data.paper_id);
                    // Show download link
                    const previewContainer = document.getElementById('paper-preview');
                    previewContainer.innerHTML += `
                        <div class="spg-success-message">
                            <h4>✅ Paper Generated Successfully!</h4>
                            <p>Paper ID: ${response.data.paper_id}</p>
                            <div class="spg-success-actions">
                                <a href="${response.data.view_url}" target="_blank" class="spg-btn">View Paper</a>
                                <a href="${response.data.download_url}" class="spg-btn spg-btn-primary">Download PDF</a>
                                <button class="spg-btn" onclick="resetForm()">Create Another Paper</button>
                            </div>
                        </div>
                    `;
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
    
    function validatePaperData(paperData) {
        if (!paperData.title || !paperData.subject || !paperData.class_level) {
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Check if enough questions are selected
        if (selectedQuestions.mcq.length < paperData.question_requirements.mcq.count ||
            selectedQuestions.short.length < paperData.question_requirements.short.count ||
            selectedQuestions.long.length < paperData.question_requirements.long.count) {
            alert('Please select the required number of questions for each type.');
            return false;
        }
        
        return true;
    }
    
    function downloadPaper() {
        alert('PDF download requires premium version. Click "Generate Paper" to create and view the paper.');
    }
    
    function resetForm() {
        // Reset form to step 1
        document.getElementById('paper_title').value = '';
        document.getElementById('subject').value = '';
        document.getElementById('class_level').value = '';
        document.getElementById('total_marks').value = '';
        document.getElementById('time_allowed').value = '';
        document.getElementById('instructions').value = '';
        
        // Reset question counts
        document.getElementById('mcq_count').value = '10';
        document.getElementById('short_count').value = '5';
        document.getElementById('short_marks').value = '2';
        document.getElementById('long_count').value = '3';
        document.getElementById('long_marks').value = '5';
        
        // Reset selected questions
        selectedQuestions = { mcq: [], short: [], long: [] };
        
        // Go to step 1
        spg_next_step(1);
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotalMarks();
    });
    </script>
    
    <?php
    return ob_get_clean();
}

// Helper functions
function spg_login_required_message() {
    return '<div class="spg-login-required" style="background: #fff; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #2c3e50;">Login Required</h3>
                <p style="color: #666; margin-bottom: 20px;">Please login to generate exam papers.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="button button-primary" style="padding: 10px 20px; text-decoration: none;">Login Now</a>
            </div>';
}

function spg_permission_required_message() {
    return '<div class="spg-permission-required" style="background: #fff; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="color: #2c3e50;">Permission Required</h3>
                <p style="color: #666;">Only teachers and administrators can generate exam papers.</p>
                <p style="color: #999; font-size: 14px; margin-top: 10px;">Contact your administrator for access.</p>
            </div>';
}

function spg_frontend_styles() {
    return '
    <style>
    /* Paper Generator Frontend Styles */
    .spg-paper-generator-frontend {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    /* Progress Steps */
    .spg-progress-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
    }
    
    .spg-progress-steps:before {
        content: "";
        position: absolute;
        top: 20px;
        left: 10%;
        right: 10%;
        height: 2px;
        background: #e0e0e0;
        z-index: 1;
    }
    
    .spg-progress-steps .step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .spg-progress-steps .step-number {
        width: 40px;
        height: 40px;
        background: #f5f5f5;
        color: #666;
        border-radius: 50%;
        line-height: 40px;
        margin: 0 auto 10px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .spg-progress-steps .step.active .step-number {
        background: #0073aa;
        color: white;
    }
    
    .spg-progress-steps .step-label {
        font-weight: bold;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .spg-progress-steps .step.active .step-label {
        color: #0073aa;
    }
    
    /* Steps */
    .spg-step {
        display: none;
        animation: fadeIn 0.5s ease;
    }
    
    .spg-step.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Form Styles */
    .spg-form-group {
        margin-bottom: 20px;
    }
    
    .spg-form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }
    
    .spg-form-group input,
    .spg-form-group select,
    .spg-form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .spg-form-group input:focus,
    .spg-form-group textarea:focus {
        border-color: #0073aa;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
        outline: none;
    }
    
    .spg-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    /* Question Requirements */
    .spg-question-requirements {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .spg-requirement-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .spg-requirement-item {
        padding: 15px;
        background: white;
        border-radius: 5px;
        border: 1px solid #e0e0e0;
    }
    
    .spg-requirement-item label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
        color: #333;
    }
    
    .spg-requirement-controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .spg-btn-count {
        width: 30px;
        height: 30px;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    
    .spg-requirement-controls input[type="number"] {
        width: 60px;
        padding: 5px;
        text-align: center;
    }
    
    .spg-question-mark {
        color: #666;
        font-size: 14px;
    }
    
    .spg-total-marks-display {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
        font-size: 18px;
        color: #333;
    }
    
    /* Question Selection */
    .spg-question-type-section {
        margin-bottom: 30px;
    }
    
    .spg-question-type-section h5 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .spg-count-badge {
        background: #f0f8ff;
        color: #0073aa;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 14px;
    }
    
    .spg-questions-container {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        padding: 10px;
        background: white;
    }
    
    .spg-questions-list {
        display: grid;
        gap: 10px;
    }
    
    .spg-question-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .spg-question-item:hover {
        background: #f8f9fa;
        border-color: #0073aa;
    }
    
    .spg-question-item.selected {
        background: #f0f8ff;
        border-color: #0073aa;
    }
    
    .spg-question-checkbox input {
        width: 18px;
        height: 18px;
    }
    
    .spg-question-content {
        flex: 1;
    }
    
    .spg-question-text {
        color: #333;
        line-height: 1.4;
    }
    
    .spg-question-meta {
        display: flex;
        gap: 10px;
        margin-top: 5px;
        font-size: 12px;
        color: #666;
    }
    
    .spg-meta-item {
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
    }
    
    /* Paper Preview */
    .spg-paper-preview {
        background: white;
        padding: 30px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    /* Buttons */
    .spg-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        margin: 0 5px;
    }
    
    .spg-btn-primary {
        background: #0073aa;
        color: white;
    }
    
    .spg-btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .spg-btn-success {
        background: #28a745;
        color: white;
    }
    
    .spg-form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }
    
    /* Loading and Messages */
    .spg-loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    
    .spg-error {
        background: #fff5f5;
        color: #dc3545;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
    }
    
    .spg-success-message {
        background: #d4edda;
        color: #155724;
        padding: 20px;
        border-radius: 5px;
        margin-top: 20px;
    }
    
    .spg-success-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .spg-form-row {
            grid-template-columns: 1fr;
        }
        
        .spg-requirement-row {
            grid-template-columns: 1fr;
        }
        
        .spg-form-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .spg-btn {
            width: 100%;
            margin: 5px 0;
        }
    }
    </style>';
}


