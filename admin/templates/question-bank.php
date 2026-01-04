<?php
if (!defined('ABSPATH')) exit;

// Get question bank instance
$question_bank = SPG_Question_Bank::get_instance();
$subjects = spg_get_subjects();
$class_levels = spg_get_class_levels();
$question_counts = spg_get_question_counts();

// Get current filters
$current_subject = !empty($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
$current_class = !empty($_GET['class']) ? sanitize_text_field($_GET['class']) : '';
$current_type = !empty($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$current_difficulty = !empty($_GET['difficulty']) ? sanitize_text_field($_GET['difficulty']) : '';
$current_search = !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get questions based on filters
$filters = array();
if ($current_subject) $filters['subject'] = $current_subject;
if ($current_class) $filters['class_level'] = $current_class;
if ($current_type) $filters['question_type'] = $current_type;
if ($current_difficulty) $filters['difficulty'] = $current_difficulty;
if ($current_search) $filters['search'] = $current_search;

$questions = $question_bank->get_filtered_questions($filters);
$total_questions = $question_bank->get_total_questions();
?>

<div class="wrap spg-question-bank">
    <div class="spg-header">
        <h1><i class="fas fa-database"></i> <?php _e('Question Bank', 'school-paper-generator'); ?></h1>
        <p class="description"><?php _e('Manage all your questions in one place', 'school-paper-generator'); ?></p>
    </div>
    
    <?php if (!spg_is_premium_active()): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Trial Version', 'school-paper-generator'); ?>:</strong> 
            <?php 
            printf(
                __('You have used %d/%d questions. Upgrade for unlimited questions.', 'school-paper-generator'),
                $total_questions,
                spg_get_trial_question_limit()
            );
            ?>
            <a href="https://yourwebsite.com/upgrade" class="button button-small" style="margin-left: 10px;">
                <?php _e('Upgrade Now', 'school-paper-generator'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="spg-question-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_questions); ?></h3>
                <p><?php _e('Total Questions', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="far fa-dot-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo isset($question_counts['mcq']) ? $question_counts['mcq']['count'] : 0; ?></h3>
                <p><?php _e('MCQ Questions', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="far fa-comment"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo isset($question_counts['short']) ? $question_counts['short']['count'] : 0; ?></h3>
                <p><?php _e('Short Questions', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="far fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo isset($question_counts['long']) ? $question_counts['long']['count'] : 0; ?></h3>
                <p><?php _e('Long Questions', 'school-paper-generator'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="spg-question-actions">
        <div class="action-buttons">
            <button type="button" class="button button-primary" id="add-question-btn">
                <i class="fas fa-plus"></i> <?php _e('Add New Question', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="import-questions-btn">
                <i class="fas fa-file-import"></i> <?php _e('Import Questions', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="export-questions-btn">
                <i class="fas fa-file-export"></i> <?php _e('Export Questions', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button button-danger" id="bulk-delete-btn" style="display: none;">
                <i class="fas fa-trash"></i> <?php _e('Delete Selected', 'school-paper-generator'); ?>
            </button>
        </div>
    </div>
    
    <div class="spg-question-filters">
        <form method="get" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="spg-question-bank">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter-subject"><?php _e('Subject:', 'school-paper-generator'); ?></label>
                    <select name="subject" id="filter-subject" class="filter-select">
                        <option value=""><?php _e('All Subjects', 'school-paper-generator'); ?></option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo esc_attr($subject); ?>" <?php selected($current_subject, $subject); ?>>
                            <?php echo esc_html($subject); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter-class"><?php _e('Class:', 'school-paper-generator'); ?></label>
                    <select name="class" id="filter-class" class="filter-select">
                        <option value=""><?php _e('All Classes', 'school-paper-generator'); ?></option>
                        <?php foreach ($class_levels as $class): ?>
                        <option value="<?php echo esc_attr($class); ?>" <?php selected($current_class, $class); ?>>
                            <?php echo esc_html($class); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter-type"><?php _e('Type:', 'school-paper-generator'); ?></label>
                    <select name="type" id="filter-type" class="filter-select">
                        <option value=""><?php _e('All Types', 'school-paper-generator'); ?></option>
                        <option value="mcq" <?php selected($current_type, 'mcq'); ?>><?php _e('Multiple Choice', 'school-paper-generator'); ?></option>
                        <option value="short" <?php selected($current_type, 'short'); ?>><?php _e('Short Answer', 'school-paper-generator'); ?></option>
                        <option value="long" <?php selected($current_type, 'long'); ?>><?php _e('Long Answer', 'school-paper-generator'); ?></option>
                        <option value="true_false" <?php selected($current_type, 'true_false'); ?>><?php _e('True/False', 'school-paper-generator'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter-difficulty"><?php _e('Difficulty:', 'school-paper-generator'); ?></label>
                    <select name="difficulty" id="filter-difficulty" class="filter-select">
                        <option value=""><?php _e('All Levels', 'school-paper-generator'); ?></option>
                        <option value="easy" <?php selected($current_difficulty, 'easy'); ?>><?php _e('Easy', 'school-paper-generator'); ?></option>
                        <option value="medium" <?php selected($current_difficulty, 'medium'); ?>><?php _e('Medium', 'school-paper-generator'); ?></option>
                        <option value="hard" <?php selected($current_difficulty, 'hard'); ?>><?php _e('Hard', 'school-paper-generator'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group filter-search">
                    <label for="filter-search"><?php _e('Search:', 'school-paper-generator'); ?></label>
                    <input type="text" name="search" id="filter-search" 
                           value="<?php echo esc_attr($current_search); ?>" 
                           placeholder="<?php esc_attr_e('Search questions...', 'school-paper-generator'); ?>">
                    <button type="submit" class="button">
                        <i class="fas fa-search"></i> <?php _e('Search', 'school-paper-generator'); ?>
                    </button>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button button-primary">
                        <?php _e('Apply Filters', 'school-paper-generator'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=spg-question-bank'); ?>" class="button">
                        <?php _e('Clear Filters', 'school-paper-generator'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="spg-questions-table">
        <form id="questions-form" method="post">
            <?php wp_nonce_field('spg_bulk_action', 'spg_nonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="select-all-questions">
                        </th>
                        <th><?php _e('Question', 'school-paper-generator'); ?></th>
                        <th width="100"><?php _e('Type', 'school-paper-generator'); ?></th>
                        <th width="100"><?php _e('Subject', 'school-paper-generator'); ?></th>
                        <th width="80"><?php _e('Class', 'school-paper-generator'); ?></th>
                        <th width="80"><?php _e('Marks', 'school-paper-generator'); ?></th>
                        <th width="100"><?php _e('Difficulty', 'school-paper-generator'); ?></th>
                        <th width="150"><?php _e('Date Added', 'school-paper-generator'); ?></th>
                        <th width="120"><?php _e('Actions', 'school-paper-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                    <tr>
                        <td colspan="9" class="no-questions">
                            <p><?php _e('No questions found. Add your first question!', 'school-paper-generator'); ?></p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($questions as $question): ?>
                    <tr class="question-row" data-id="<?php echo $question['id']; ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="question_ids[]" value="<?php echo $question['id']; ?>" class="question-checkbox">
                        </th>
                        <td class="question-text">
                            <div class="question-text-truncate" title="<?php echo esc_attr($question['question_text']); ?>">
                                <?php echo esc_html(wp_trim_words($question['question_text'], 15, '...')); ?>
                            </div>
                            <?php if (!empty($question['topic'])): ?>
                            <div class="question-topic">
                                <small><?php echo esc_html($question['topic']); ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="question-type-badge badge-<?php echo esc_attr($question['question_type']); ?>">
                                <?php echo spg_get_question_type_label($question['question_type']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($question['subject']); ?></td>
                        <td><?php echo esc_html($question['class_level']); ?></td>
                        <td><?php echo esc_html($question['marks']); ?></td>
                        <td>
                            <span class="difficulty-badge difficulty-<?php echo esc_attr($question['difficulty']); ?>">
                                <?php echo spg_get_difficulty_label($question['difficulty']); ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($question['created_at'])); ?></td>
                        <td class="question-actions">
                            <button type="button" class="button button-small edit-question" 
                                    data-id="<?php echo $question['id']; ?>"
                                    title="<?php esc_attr_e('Edit Question', 'school-paper-generator'); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <button type="button" class="button button-small delete-question" 
                                    data-id="<?php echo $question['id']; ?>"
                                    title="<?php esc_attr_e('Delete Question', 'school-paper-generator'); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            
                            <button type="button" class="button button-small preview-question" 
                                    data-id="<?php echo $question['id']; ?>"
                                    title="<?php esc_attr_e('Preview Question', 'school-paper-generator'); ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <?php if (!empty($questions)): ?>
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
                <select name="action" id="bulk-action-selector-bottom">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" class="button action" id="doaction-bottom" value="Apply">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', count($questions)), number_format_i18n(count($questions))); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Question Modal -->
<div id="add-question-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Add New Question', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <form id="add-question-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="question-type"><?php _e('Question Type *', 'school-paper-generator'); ?></label>
                        <select name="question_type" id="question-type" required>
                            <option value=""><?php _e('Select Type', 'school-paper-generator'); ?></option>
                            <option value="mcq"><?php _e('Multiple Choice Question', 'school-paper-generator'); ?></option>
                            <option value="short"><?php _e('Short Answer', 'school-paper-generator'); ?></option>
                            <option value="long"><?php _e('Long Answer', 'school-paper-generator'); ?></option>
                            <option value="true_false"><?php _e('True/False', 'school-paper-generator'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="question-marks"><?php _e('Marks *', 'school-paper-generator'); ?></label>
                        <input type="number" name="marks" id="question-marks" 
                               min="1" max="100" value="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="question-subject"><?php _e('Subject *', 'school-paper-generator'); ?></label>
                        <select name="subject" id="question-subject" required>
                            <option value=""><?php _e('Select Subject', 'school-paper-generator'); ?></option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_subject" id="new-subject" 
                               placeholder="<?php esc_attr_e('Add new subject...', 'school-paper-generator'); ?>"
                               style="display: none; margin-top: 5px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="question-class"><?php _e('Class Level *', 'school-paper-generator'); ?></label>
                        <select name="class_level" id="question-class" required>
                            <option value=""><?php _e('Select Class', 'school-paper-generator'); ?></option>
                            <?php foreach ($class_levels as $class): ?>
                            <option value="<?php echo esc_attr($class); ?>"><?php echo esc_html($class); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="new_class" id="new-class" 
                               placeholder="<?php esc_attr_e('Add new class...', 'school-paper-generator'); ?>"
                               style="display: none; margin-top: 5px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="question-chapter"><?php _e('Chapter', 'school-paper-generator'); ?></label>
                        <input type="text" name="chapter" id="question-chapter">
                    </div>
                    
                    <div class="form-group">
                        <label for="question-topic"><?php _e('Topic', 'school-paper-generator'); ?></label>
                        <input type="text" name="topic" id="question-topic">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question-difficulty"><?php _e('Difficulty Level', 'school-paper-generator'); ?></label>
                    <select name="difficulty" id="question-difficulty">
                        <option value="medium"><?php _e('Medium', 'school-paper-generator'); ?></option>
                        <option value="easy"><?php _e('Easy', 'school-paper-generator'); ?></option>
                        <option value="hard"><?php _e('Hard', 'school-paper-generator'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="question-text"><?php _e('Question Text *', 'school-paper-generator'); ?></label>
                    <textarea name="question_text" id="question-text" rows="4" required 
                              placeholder="<?php esc_attr_e('Enter your question here...', 'school-paper-generator'); ?>"></textarea>
                </div>
                
                <!-- MCQ Options (shown only for MCQ type) -->
                <div id="mcq-options" style="display: none;">
                    <div class="form-group">
                        <label><?php _e('Options *', 'school-paper-generator'); ?></label>
                        <div id="options-container">
                            <div class="option-row">
                                <input type="text" name="options[]" placeholder="<?php esc_attr_e('Option A', 'school-paper-generator'); ?>" class="option-input">
                                <button type="button" class="button button-small remove-option" style="display: none;">-</button>
                            </div>
                            <div class="option-row">
                                <input type="text" name="options[]" placeholder="<?php esc_attr_e('Option B', 'school-paper-generator'); ?>" class="option-input">
                                <button type="button" class="button button-small remove-option">-</button>
                            </div>
                        </div>
                        <button type="button" class="button button-small" id="add-option">
                            <i class="fas fa-plus"></i> <?php _e('Add Option', 'school-paper-generator'); ?>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="correct-answer"><?php _e('Correct Answer *', 'school-paper-generator'); ?></label>
                        <select name="correct_answer" id="correct-answer" required>
                            <option value=""><?php _e('Select correct answer', 'school-paper-generator'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- True/False Options -->
                <div id="truefalse-options" style="display: none;">
                    <div class="form-group">
                        <label for="correct-answer-tf"><?php _e('Correct Answer *', 'school-paper-generator'); ?></label>
                        <select name="correct_answer" id="correct-answer-tf" required>
                            <option value=""><?php _e('Select correct answer', 'school-paper-generator'); ?></option>
                            <option value="True"><?php _e('True', 'school-paper-generator'); ?></option>
                            <option value="False"><?php _e('False', 'school-paper-generator'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Correct Answer for Short/Long questions -->
                <div id="answer-options" style="display: none;">
                    <div class="form-group">
                        <label for="correct-answer-text"><?php _e('Correct Answer (Optional)', 'school-paper-generator'); ?></label>
                        <textarea name="correct_answer" id="correct-answer-text" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question-explanation"><?php _e('Explanation (Optional)', 'school-paper-generator'); ?></label>
                    <textarea name="explanation" id="question-explanation" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Add Question', 'school-paper-generator'); ?>
                    </button>
                    <button type="button" class="button spg-modal-close">
                        <?php _e('Cancel', 'school-paper-generator'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Questions Modal -->
<div id="import-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Import Questions', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <div class="import-options">
                <div class="import-format">
                    <h4><?php _e('Import Format', 'school-paper-generator'); ?></h4>
                    <div class="format-options">
                        <label>
                            <input type="radio" name="import_format" value="csv" checked>
                            <span>CSV</span>
                        </label>
                        <label>
                            <input type="radio" name="import_format" value="json">
                            <span>JSON</span>
                        </label>
                        <label>
                            <input type="radio" name="import_format" value="xlsx">
                            <span>Excel</span>
                        </label>
                    </div>
                </div>
                
                <div class="import-instructions">
                    <h4><?php _e('Instructions', 'school-paper-generator'); ?></h4>
                    <p><?php _e('Download the template file to see the required format:', 'school-paper-generator'); ?></p>
                    <button type="button" class="button" id="download-template">
                        <i class="fas fa-download"></i> <?php _e('Download Template', 'school-paper-generator'); ?>
                    </button>
                </div>
            </div>
            
            <form id="import-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import-file"><?php _e('Select File *', 'school-paper-generator'); ?></label>
                    <input type="file" name="import_file" id="import-file" accept=".csv,.json,.xlsx,.xls" required>
                    <p class="description"><?php _e('Maximum file size: 5MB', 'school-paper-generator'); ?></p>
                </div>
                
                <div class="import-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-text">0%</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Import Questions', 'school-paper-generator'); ?>
                    </button>
                    <button type="button" class="button spg-modal-close">
                        <?php _e('Cancel', 'school-paper-generator'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Questions Modal -->
<div id="export-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Export Questions', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <div class="export-options">
                <div class="export-format">
                    <h4><?php _e('Export Format', 'school-paper-generator'); ?></h4>
                    <div class="format-options">
                        <label>
                            <input type="radio" name="export_format" value="csv" checked>
                            <span>CSV</span>
                        </label>
                        <label>
                            <input type="radio" name="export_format" value="json">
                            <span>JSON</span>
                        </label>
                        <label>
                            <input type="radio" name="export_format" value="xlsx">
                            <span>Excel</span>
                        </label>
                    </div>
                </div>
                
                <div class="export-filters">
                    <h4><?php _e('Filter Questions (Optional)', 'school-paper-generator'); ?></h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="export-subject"><?php _e('Subject:', 'school-paper-generator'); ?></label>
                            <select name="subject" id="export-subject">
                                <option value=""><?php _e('All Subjects', 'school-paper-generator'); ?></option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="export-class"><?php _e('Class:', 'school-paper-generator'); ?></label>
                            <select name="class_level" id="export-class">
                                <option value=""><?php _e('All Classes', 'school-paper-generator'); ?></option>
                                <?php foreach ($class_levels as $class): ?>
                                <option value="<?php echo esc_attr($class); ?>"><?php echo esc_html($class); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="export-type"><?php _e('Question Type:', 'school-paper-generator'); ?></label>
                            <select name="question_type" id="export-type">
                                <option value=""><?php _e('All Types', 'school-paper-generator'); ?></option>
                                <option value="mcq"><?php _e('Multiple Choice', 'school-paper-generator'); ?></option>
                                <option value="short"><?php _e('Short Answer', 'school-paper-generator'); ?></option>
                                <option value="long"><?php _e('Long Answer', 'school-paper-generator'); ?></option>
                                <option value="true_false"><?php _e('True/False', 'school-paper-generator'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="export-difficulty"><?php _e('Difficulty:', 'school-paper-generator'); ?></label>
                            <select name="difficulty" id="export-difficulty">
                                <option value=""><?php _e('All Levels', 'school-paper-generator'); ?></option>
                                <option value="easy"><?php _e('Easy', 'school-paper-generator'); ?></option>
                                <option value="medium"><?php _e('Medium', 'school-paper-generator'); ?></option>
                                <option value="hard"><?php _e('Hard', 'school-paper-generator'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="button button-primary" id="export-questions">
                    <?php _e('Export Questions', 'school-paper-generator'); ?>
                </button>
                <button type="button" class="button spg-modal-close">
                    <?php _e('Cancel', 'school-paper-generator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Question Modal -->
<div id="preview-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Question Preview', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body" id="preview-content">
            <!-- Preview content will be loaded here -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Question bank functionality
    const SPG_QuestionBank = {
        init: function() {
            this.bindEvents();
            this.initSelect2();
        },
        
        bindEvents: function() {
            // Add question button
            $('#add-question-btn').on('click', this.openAddQuestionModal);
            
            // Import/Export buttons
            $('#import-questions-btn').on('click', this.openImportModal);
            $('#export-questions-btn').on('click', this.openExportModal);
            
            // Bulk actions
            $('#select-all-questions').on('change', this.toggleSelectAll);
            $('#bulk-delete-btn').on('click', this.bulkDeleteQuestions);
            $('#doaction-bottom').on('click', this.handleBulkAction);
            
            // Individual question actions
            $('.edit-question').on('click', this.editQuestion);
            $('.delete-question').on('click', this.deleteQuestion);
            $('.preview-question').on('click', this.previewQuestion);
            
            // Modal close buttons
            $('.spg-modal-close').on('click', this.closeModal);
            
            // Question type change
            $('#question-type').on('change', this.toggleQuestionFields);
            
            // Add option button
            $('#add-option').on('click', this.addOption);
            
            // Subject/class add new
            $('#question-subject, #question-class').on('change', this.toggleNewField);
            
            // Form submissions
            $('#add-question-form').on('submit', this.submitQuestion);
            $('#import-form').on('submit', this.submitImport);
            $('#export-questions').on('click', this.submitExport);
            
            // Download template
            $('#download-template').on('click', this.downloadTemplate);
        },
        
        openAddQuestionModal: function() {
            $('#add-question-modal').show();
            $('#add-question-form')[0].reset();
            SPG_QuestionBank.toggleQuestionFields();
        },
        
        openImportModal: function() {
            $('#import-modal').show();
        },
        
        openExportModal: function() {
            $('#export-modal').show();
        },
        
        closeModal: function() {
            $(this).closest('.spg-modal').hide();
        },
        
        toggleSelectAll: function() {
            const isChecked = $(this).is(':checked');
            $('.question-checkbox').prop('checked', isChecked);
            SPG_QuestionBank.toggleBulkDeleteButton();
        },
        
        toggleBulkDeleteButton: function() {
            const checkedCount = $('.question-checkbox:checked').length;
            if (checkedCount > 0) {
                $('#bulk-delete-btn').show();
                $('#bulk-delete-btn').text('Delete Selected (' + checkedCount + ')');
            } else {
                $('#bulk-delete-btn').hide();
            }
        },
        
        bulkDeleteQuestions: function() {
            const questionIds = [];
            $('.question-checkbox:checked').each(function() {
                questionIds.push($(this).val());
            });
            
            if (questionIds.length === 0) {
                alert('Please select questions to delete');
                return;
            }
            
            if (!confirm('Are you sure you want to delete ' + questionIds.length + ' questions?')) {
                return;
            }
            
            SPG_QuestionBank.deleteQuestions(questionIds);
        },
        
        deleteQuestions: function(questionIds) {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_bulk_delete_questions',
                    nonce: spg_ajax.nonce,
                    question_ids: questionIds
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        editQuestion: function() {
            const questionId = $(this).data('id');
            // Load question data and open edit modal
            console.log('Edit question:', questionId);
            // Implement edit functionality
        },
        
        deleteQuestion: function() {
            const questionId = $(this).data('id');
            
            if (!confirm('Are you sure you want to delete this question?')) {
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_delete_question',
                    nonce: spg_ajax.nonce,
                    question_id: questionId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        previewQuestion: function() {
            const questionId = $(this).data('id');
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_question',
                    nonce: spg_ajax.nonce,
                    question_id: questionId
                },
                success: function(response) {
                    if (response.success) {
                        SPG_QuestionBank.displayPreview(response.data);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        displayPreview: function(question) {
            let html = '<div class="question-preview">';
            html += '<h4>Question Details</h4>';
            html += '<p><strong>Type:</strong> ' + question.question_type_label + '</p>';
            html += '<p><strong>Subject:</strong> ' + question.subject + '</p>';
            html += '<p><strong>Class:</strong> ' + question.class_level + '</p>';
            html += '<p><strong>Marks:</strong> ' + question.marks + '</p>';
            html += '<p><strong>Difficulty:</strong> ' + question.difficulty_label + '</p>';
            html += '<hr>';
            html += '<p><strong>Question:</strong></p>';
            html += '<div class="question-text">' + question.question_text + '</div>';
            
            if (question.question_type === 'mcq' && question.options) {
                html += '<p><strong>Options:</strong></p>';
                html += '<ol>';
                question.options.forEach(function(option, index) {
                    const letter = String.fromCharCode(65 + index);
                    html += '<li>' + option + (option === question.correct_answer ? ' (Correct)' : '') + '</li>';
                });
                html += '</ol>';
            }
            
            if (question.correct_answer && question.question_type !== 'mcq') {
                html += '<p><strong>Correct Answer:</strong></p>';
                html += '<div class="correct-answer">' + question.correct_answer + '</div>';
            }
            
            if (question.explanation) {
                html += '<p><strong>Explanation:</strong></p>';
                html += '<div class="explanation">' + question.explanation + '</div>';
            }
            
            html += '</div>';
            
            $('#preview-content').html(html);
            $('#preview-modal').show();
        },
        
        toggleQuestionFields: function() {
            const questionType = $('#question-type').val();
            
            // Hide all option sections
            $('#mcq-options, #truefalse-options, #answer-options').hide();
            
            // Show relevant section
            if (questionType === 'mcq') {
                $('#mcq-options').show();
                this.updateMCQOptions();
            } else if (questionType === 'true_false') {
                $('#truefalse-options').show();
            } else if (questionType === 'short' || questionType === 'long') {
                $('#answer-options').show();
            }
        },
        
        updateMCQOptions: function() {
            const $correctAnswerSelect = $('#correct-answer');
            $correctAnswerSelect.empty();
            $correctAnswerSelect.append('<option value="">Select correct answer</option>');
            
            $('.option-input').each(function(index) {
                const value = $(this).val();
                if (value) {
                    const letter = String.fromCharCode(65 + index);
                    $correctAnswerSelect.append('<option value="' + value + '">' + letter + '. ' + value + '</option>');
                }
            });
        },
        
        addOption: function() {
            const optionCount = $('.option-row').length;
            const letter = String.fromCharCode(65 + optionCount);
            
            const $optionRow = $('<div class="option-row">');
            $optionRow.append('<input type="text" name="options[]" placeholder="Option ' + letter + '" class="option-input">');
            
            const $removeBtn = $('<button type="button" class="button button-small remove-option">-</button>');
            $removeBtn.on('click', function() {
                $(this).closest('.option-row').remove();
                SPG_QuestionBank.updateMCQOptions();
            });
            $optionRow.append($removeBtn);
            
            $('#options-container').append($optionRow);
            SPG_QuestionBank.updateMCQOptions();
        },
        
        toggleNewField: function() {
            const $select = $(this);
            const $newInput = $select.siblings('input[type="text"]');
            
            if ($select.val() === '') {
                $newInput.show();
            } else {
                $newInput.hide();
            }
        },
        
        submitQuestion: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const data = {};
            
            formData.forEach(function(item) {
                if (item.name === 'options[]') {
                    if (!data.options) data.options = [];
                    data.options.push(item.value);
                } else {
                    data[item.name] = item.value;
                }
            });
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_add_question',
                    nonce: spg_ajax.nonce,
                    ...data
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#add-question-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        submitImport: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'spg_import_questions');
            formData.append('nonce', spg_ajax.nonce);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new XMLHttpRequest();
                    
                    // Upload progress
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            $('.progress-fill').css('width', percent + '%');
                            $('.progress-text').text(percent + '%');
                        }
                    });
                    
                    return xhr;
                },
                beforeSend: function() {
                    $('.import-progress').show();
                    $('.progress-fill').css('width', '0%');
                    $('.progress-text').text('0%');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $('#import-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                complete: function() {
                    $('.import-progress').hide();
                }
            });
        },
        
        submitExport: function() {
            const format = $('input[name="export_format"]:checked').val();
            const filters = {
                subject: $('#export-subject').val(),
                class_level: $('#export-class').val(),
                question_type: $('#export-type').val(),
                difficulty: $('#export-difficulty').val()
            };
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_export_questions',
                    nonce: spg_ajax.nonce,
                    format: format,
                    filters: filters
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.download_url, '_blank');
                        $('#export-modal').hide();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        downloadTemplate: function() {
            window.open(spg_ajax.ajax_url + '?action=spg_download_template&nonce=' + spg_ajax.nonce, '_blank');
        },
        
        initSelect2: function() {
            if ($.fn.select2) {
                $('.filter-select').select2({
                    width: '100%',
                    minimumResultsForSearch: 10
                });
            }
        },
        
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-bottom').val();
            if (action !== 'delete') return;
            
            SPG_QuestionBank.bulkDeleteQuestions();
        }
    };
    
    // Initialize question bank
    SPG_QuestionBank.init();
    
    // Toggle bulk delete button on checkbox change
    $('.question-checkbox').on('change', function() {
        SPG_QuestionBank.toggleBulkDeleteButton();
    });
    
    // Remove option button handler
    $(document).on('click', '.remove-option', function() {
        $(this).closest('.option-row').remove();
        SPG_QuestionBank.updateMCQOptions();
    });
    
    // Update MCQ options on input change
    $(document).on('input', '.option-input', function() {
        SPG_QuestionBank.updateMCQOptions();
    });
});
</script>

<style>
.spg-question-bank {
    max-width: 1400px;
}

.spg-question-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 20px;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.8em;
    color: #333;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.spg-question-actions {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.spg-question-filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-search {
    flex: 2;
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.filter-search input {
    flex: 1;
}

.filter-select,
.filter-search input {
    width: 100%;
    margin-top: 5px;
}

.question-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.badge-mcq { background: #e3f2fd; color: #1976d2; }
.badge-short { background: #f3e5f5; color: #7b1fa2; }
.badge-long { background: #e8f5e8; color: #388e3c; }
.badge-true_false { background: #fff3e0; color: #f57c00; }

.difficulty-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.difficulty-easy { background: #e8f5e8; color: #388e3c; }
.difficulty-medium { background: #fff3e0; color: #f57c00; }
.difficulty-hard { background: #ffebee; color: #d32f2f; }

.question-text-truncate {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.question-topic {
    margin-top: 5px;
    color: #666;
}

.question-actions {
    display: flex;
    gap: 5px;
}

.question-actions .button-small {
    padding: 2px 8px;
    font-size: 0.9em;
}

.no-questions {
    text-align: center;
    padding: 40px;
    color: #666;
}

/* Modal styles */
.spg-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.spg-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.spg-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spg-modal-header h3 {
    margin: 0;
    color: #333;
}

.spg-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.spg-modal-body {
    padding: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    flex: 1;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.option-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.option-row .option-input {
    flex: 1;
}

.import-options,
.export-options {
    margin-bottom: 30px;
}

.format-options {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.format-options label {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
}

.import-progress {
    margin: 20px 0;
}

.progress-bar {
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #4CAF50;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.question-preview {
    line-height: 1.6;
}

.question-preview h4 {
    margin-top: 0;
    color: #333;
}

.question-preview p {
    margin: 10px 0;
}

.question-preview .question-text {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.question-preview .correct-answer,
.question-preview .explanation {
    background: #f0f9ff;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
    border-left: 4px solid #2196F3;
}

@media (max-width: 768px) {
    .spg-question-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .spg-modal-content {
        width: 95%;
        margin: 10px;
    }
}
</style>