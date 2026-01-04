<?php
if (!defined('ABSPATH')) exit;

$subjects = spg_get_subjects();
$class_levels = spg_get_class_levels();
$question_counts = spg_get_question_counts();
?>

<div class="wrap spg-import-export">
    <div class="spg-header">
        <h1><i class="fas fa-exchange-alt"></i> <?php _e('Import & Export', 'school-paper-generator'); ?></h1>
        <p class="description"><?php _e('Import questions from external sources or export your data', 'school-paper-generator'); ?></p>
    </div>
    
    <div class="spg-import-export-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#import-tab" class="nav-tab nav-tab-active">
                <i class="fas fa-file-import"></i> <?php _e('Import', 'school-paper-generator'); ?>
            </a>
            <a href="#export-tab" class="nav-tab">
                <i class="fas fa-file-export"></i> <?php _e('Export', 'school-paper-generator'); ?>
            </a>
            <a href="#backup-tab" class="nav-tab">
                <i class="fas fa-database"></i> <?php _e('Backup', 'school-paper-generator'); ?>
            </a>
        </nav>
        
        <div class="tab-content">
            <!-- Import Tab -->
            <div id="import-tab" class="tab-pane active">
                <div class="import-sections">
                    <!-- Import Questions -->
                    <div class="import-section">
                        <h3><i class="fas fa-question-circle"></i> <?php _e('Import Questions', 'school-paper-generator'); ?></h3>
                        
                        <div class="import-options">
                            <div class="import-format">
                                <h4><?php _e('Import Format', 'school-paper-generator'); ?></h4>
                                <div class="format-buttons">
                                    <button type="button" class="format-btn active" data-format="csv">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </button>
                                    <button type="button" class="format-btn" data-format="json">
                                        <i class="fas fa-file-code"></i> JSON
                                    </button>
                                    <button type="button" class="format-btn" data-format="excel">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </button>
                                </div>
                            </div>
                            
                            <div class="import-instructions">
                                <h4><?php _e('Instructions', 'school-paper-generator'); ?></h4>
                                <ol>
                                    <li><?php _e('Download the template file for your chosen format', 'school-paper-generator'); ?></li>
                                    <li><?php _e('Fill in your questions following the template structure', 'school-paper-generator'); ?></li>
                                    <li><?php _e('Upload your file using the form below', 'school-paper-generator'); ?></li>
                                    <li><?php _e('Review and confirm the import', 'school-paper-generator'); ?></li>
                                </ol>
                                
                                <div class="template-download">
                                    <button type="button" class="button" id="download-template">
                                        <i class="fas fa-download"></i> <?php _e('Download Template', 'school-paper-generator'); ?>
                                    </button>
                                    <span class="template-format">CSV</span>
                                </div>
                            </div>
                        </div>
                        
                        <form id="import-questions-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="import-file"><?php _e('Select File *', 'school-paper-generator'); ?></label>
                                <input type="file" id="import-file" name="import_file" accept=".csv,.json,.xlsx,.xls" required>
                                <p class="description"><?php _e('Maximum file size: 5MB', 'school-paper-generator'); ?></p>
                            </div>
                            
                            <div class="import-options-advanced">
                                <h4><?php _e('Import Options', 'school-paper-generator'); ?></h4>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="import-subject"><?php _e('Default Subject', 'school-paper-generator'); ?></label>
                                        <select id="import-subject" name="default_subject">
                                            <option value=""><?php _e('Use from file', 'school-paper-generator'); ?></option>
                                            <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="import-class"><?php _e('Default Class', 'school-paper-generator'); ?></label>
                                        <select id="import-class" name="default_class">
                                            <option value=""><?php _e('Use from file', 'school-paper-generator'); ?></option>
                                            <?php foreach ($class_levels as $class): ?>
                                            <option value="<?php echo esc_attr($class); ?>"><?php echo esc_html($class); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                                        <?php _e('Skip duplicate questions', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="validate_import" value="1" checked>
                                        <?php _e('Validate before importing', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="import-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <div class="progress-stats">
                                    <span class="progress-text">0%</span>
                                    <span class="progress-count">0/0 questions</span>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-primary">
                                    <i class="fas fa-file-import"></i> <?php _e('Import Questions', 'school-paper-generator'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="preview-import">
                                    <i class="fas fa-eye"></i> <?php _e('Preview Import', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Import Papers -->
                    <div class="import-section">
                        <h3><i class="fas fa-file-alt"></i> <?php _e('Import Papers', 'school-paper-generator'); ?></h3>
                        <p><?php _e('Import previously exported papers in JSON format.', 'school-paper-generator'); ?></p>
                        
                        <form id="import-papers-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="import-papers-file"><?php _e('Select JSON File', 'school-paper-generator'); ?></label>
                                <input type="file" id="import-papers-file" name="papers_file" accept=".json" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-primary">
                                    <i class="fas fa-file-import"></i> <?php _e('Import Papers', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Export Tab -->
            <div id="export-tab" class="tab-pane">
                <div class="export-sections">
                    <!-- Export Questions -->
                    <div class="export-section">
                        <h3><i class="fas fa-question-circle"></i> <?php _e('Export Questions', 'school-paper-generator'); ?></h3>
                        
                        <div class="export-filters">
                            <h4><?php _e('Filter Questions', 'school-paper-generator'); ?></h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="export-subject"><?php _e('Subject:', 'school-paper-generator'); ?></label>
                                    <select id="export-subject" name="subject">
                                        <option value=""><?php _e('All Subjects', 'school-paper-generator'); ?></option>
                                        <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="export-class"><?php _e('Class:', 'school-paper-generator'); ?></label>
                                    <select id="export-class" name="class_level">
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
                                    <select id="export-type" name="question_type">
                                        <option value=""><?php _e('All Types', 'school-paper-generator'); ?></option>
                                        <option value="mcq"><?php _e('Multiple Choice', 'school-paper-generator'); ?></option>
                                        <option value="short"><?php _e('Short Answer', 'school-paper-generator'); ?></option>
                                        <option value="long"><?php _e('Long Answer', 'school-paper-generator'); ?></option>
                                        <option value="true_false"><?php _e('True/False', 'school-paper-generator'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="export-difficulty"><?php _e('Difficulty:', 'school-paper-generator'); ?></label>
                                    <select id="export-difficulty" name="difficulty">
                                        <option value=""><?php _e('All Levels', 'school-paper-generator'); ?></option>
                                        <option value="easy"><?php _e('Easy', 'school-paper-generator'); ?></option>
                                        <option value="medium"><?php _e('Medium', 'school-paper-generator'); ?></option>
                                        <option value="hard"><?php _e('Hard', 'school-paper-generator'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="export-date-from"><?php _e('Date Range:', 'school-paper-generator'); ?></label>
                                <div class="date-range">
                                    <input type="date" id="export-date-from" name="date_from" placeholder="From">
                                    <span>to</span>
                                    <input type="date" id="export-date-to" name="date_to" placeholder="To">
                                </div>
                            </div>
                            
                            <div class="export-summary">
                                <h4><?php _e('Export Summary', 'school-paper-generator'); ?></h4>
                                <div class="summary-stats">
                                    <div class="stat">
                                        <span class="stat-label"><?php _e('Total Questions:', 'school-paper-generator'); ?></span>
                                        <span class="stat-value" id="total-questions-count"><?php echo array_sum(array_column($question_counts, 'count')); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label"><?php _e('Filtered:', 'school-paper-generator'); ?></span>
                                        <span class="stat-value" id="filtered-questions-count">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="export-format">
                            <h4><?php _e('Export Format', 'school-paper-generator'); ?></h4>
                            <div class="format-options">
                                <div class="format-option">
                                    <input type="radio" id="format-csv" name="export_format" value="csv" checked>
                                    <label for="format-csv">
                                        <i class="fas fa-file-csv"></i>
                                        <span>CSV</span>
                                        <small><?php _e('Compatible with Excel', 'school-paper-generator'); ?></small>
                                    </label>
                                </div>
                                
                                <div class="format-option">
                                    <input type="radio" id="format-json" name="export_format" value="json">
                                    <label for="format-json">
                                        <i class="fas fa-file-code"></i>
                                        <span>JSON</span>
                                        <small><?php _e('Structured data', 'school-paper-generator'); ?></small>
                                    </label>
                                </div>
                                
                                <div class="format-option">
                                    <input type="radio" id="format-excel" name="export_format" value="xlsx">
                                    <label for="format-excel">
                                        <i class="fas fa-file-excel"></i>
                                        <span>Excel</span>
                                        <small><?php _e('Multiple sheets', 'school-paper-generator'); ?></small>
                                    </label>
                                </div>
                                
                                <?php if (spg_is_premium_active()): ?>
                                <div class="format-option">
                                    <input type="radio" id="format-word" name="export_format" value="docx">
                                    <label for="format-word">
                                        <i class="fas fa-file-word"></i>
                                        <span>Word</span>
                                        <small><?php _e('Editable document', 'school-paper-generator'); ?></small>
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!spg_is_premium_active()): ?>
                            <div class="premium-feature">
                                <span class="premium-badge">PREMIUM</span>
                                <p><?php _e('Word export and advanced formats available in premium version', 'school-paper-generator'); ?></p>
                                <a href="https://yourwebsite.com/upgrade" class="button button-small">
                                    <?php _e('Upgrade Now', 'school-paper-generator'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="export-options">
                            <h4><?php _e('Export Options', 'school-paper-generator'); ?></h4>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_answers" value="1" checked>
                                    <?php _e('Include correct answers', 'school-paper-generator'); ?>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_explanations" value="1">
                                    <?php _e('Include explanations', 'school-paper-generator'); ?>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_metadata" value="1">
                                    <?php _e('Include metadata', 'school-paper-generator'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="button button-primary" id="export-questions-btn">
                                <i class="fas fa-file-export"></i> <?php _e('Export Questions', 'school-paper-generator'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="preview-export">
                                <i class="fas fa-eye"></i> <?php _e('Preview Export', 'school-paper-generator'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Export Papers -->
                    <div class="export-section">
                        <h3><i class="fas fa-file-alt"></i> <?php _e('Export Papers', 'school-paper-generator'); ?></h3>
                        <p><?php _e('Export your generated papers in various formats.', 'school-paper-generator'); ?></p>
                        
                        <div class="export-papers-options">
                            <div class="form-group">
                                <label for="export-papers-format"><?php _e('Export Format:', 'school-paper-generator'); ?></label>
                                <select id="export-papers-format" name="papers_format">
                                    <option value="json">JSON</option>
                                    <option value="pdf">PDF</option>
                                    <?php if (spg_is_premium_active()): ?>
                                    <option value="zip">ZIP Archive</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_paper_questions" value="1" checked>
                                    <?php _e('Include questions in export', 'school-paper-generator'); ?>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="export_all_papers" value="1" checked>
                                    <?php _e('Export all papers', 'school-paper-generator'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="button button-primary" id="export-papers-btn">
                                <i class="fas fa-file-export"></i> <?php _e('Export Papers', 'school-paper-generator'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Backup Tab -->
            <div id="backup-tab" class="tab-pane">
                <div class="backup-sections">
                    <!-- Full Backup -->
                    <div class="backup-section">
                        <h3><i class="fas fa-database"></i> <?php _e('Full Database Backup', 'school-paper-generator'); ?></h3>
                        
                        <div class="backup-info">
                            <div class="backup-stats">
                                <div class="stat">
                                    <span class="stat-label"><?php _e('Questions:', 'school-paper-generator'); ?></span>
                                    <span class="stat-value"><?php echo array_sum(array_column($question_counts, 'count')); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label"><?php _e('Papers:', 'school-paper-generator'); ?></span>
                                    <span class="stat-value">0</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label"><?php _e('Last Backup:', 'school-paper-generator'); ?></span>
                                    <span class="stat-value">Never</span>
                                </div>
                            </div>
                            
                            <div class="backup-description">
                                <p><?php _e('Create a complete backup of all your questions, papers, and settings. This backup can be used to restore your data or migrate to another WordPress installation.', 'school-paper-generator'); ?></p>
                            </div>
                        </div>
                        
                        <div class="backup-options">
                            <h4><?php _e('Backup Options', 'school-paper-generator'); ?></h4>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="include_settings" value="1" checked>
                                    <?php _e('Include plugin settings', 'school-paper-generator'); ?>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="compress_backup" value="1" checked>
                                    <?php _e('Compress backup file', 'school-paper-generator'); ?>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label for="backup-description"><?php _e('Backup Description (Optional)', 'school-paper-generator'); ?></label>
                                <input type="text" id="backup-description" name="backup_description" 
                                       placeholder="<?php esc_attr_e('e.g., Monthly backup - June 2024', 'school-paper-generator'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="button button-primary" id="create-backup-btn">
                                <i class="fas fa-save"></i> <?php _e('Create Backup', 'school-paper-generator'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="schedule-backup-btn">
                                <i class="fas fa-clock"></i> <?php _e('Schedule Backup', 'school-paper-generator'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Restore Backup -->
                    <div class="backup-section">
                        <h3><i class="fas fa-history"></i> <?php _e('Restore from Backup', 'school-paper-generator'); ?></h3>
                        
                        <div class="backup-warning">
                            <div class="warning-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="warning-content">
                                <h4><?php _e('Warning', 'school-paper-generator'); ?></h4>
                                <p><?php _e('Restoring from a backup will replace all current data. Make sure you have a current backup before proceeding.', 'school-paper-generator'); ?></p>
                            </div>
                        </div>
                        
                        <form id="restore-backup-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="restore-file"><?php _e('Select Backup File', 'school-paper-generator'); ?></label>
                                <input type="file" id="restore-file" name="backup_file" accept=".json,.zip,.spgbackup" required>
                                <p class="description"><?php _e('Select a backup file created by School Paper Generator', 'school-paper-generator'); ?></p>
                            </div>
                            
                            <div class="restore-options">
                                <h4><?php _e('Restore Options', 'school-paper-generator'); ?></h4>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="restore_questions" value="1" checked>
                                        <?php _e('Restore questions', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="restore_papers" value="1" checked>
                                        <?php _e('Restore papers', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="restore_settings" value="1" checked>
                                        <?php _e('Restore settings', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="overwrite_existing" value="1">
                                        <?php _e('Overwrite existing data', 'school-paper-generator'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-danger" id="restore-backup-btn">
                                    <i class="fas fa-history"></i> <?php _e('Restore Backup', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Backup History -->
                    <div class="backup-section">
                        <h3><i class="fas fa-history"></i> <?php _e('Backup History', 'school-paper-generator'); ?></h3>
                        
                        <div class="backup-history">
                            <div class="no-backups">
                                <i class="fas fa-inbox"></i>
                                <p><?php _e('No backups found. Create your first backup to get started.', 'school-paper-generator'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Import Modal -->
<div id="preview-import-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content" style="max-width: 900px;">
        <div class="spg-modal-header">
            <h3><?php _e('Import Preview', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <div class="preview-stats">
                <div class="stat">
                    <span class="stat-label"><?php _e('Total Questions:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="preview-total">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label"><?php _e('Valid:', 'school-paper-generator'); ?></span>
                    <span class="stat-value valid" id="preview-valid">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label"><?php _e('Invalid:', 'school-paper-generator'); ?></span>
                    <span class="stat-value invalid" id="preview-invalid">0</span>
                </div>
                <div class="stat">
                    <span class="stat-label"><?php _e('Duplicates:', 'school-paper-generator'); ?></span>
                    <span class="stat-value duplicate" id="preview-duplicate">0</span>
                </div>
            </div>
            
            <div class="preview-table-container">
                <table class="preview-table" id="preview-table">
                    <thead>
                        <tr>
                            <th><?php _e('Status', 'school-paper-generator'); ?></th>
                            <th><?php _e('Question', 'school-paper-generator'); ?></th>
                            <th><?php _e('Type', 'school-paper-generator'); ?></th>
                            <th><?php _e('Subject', 'school-paper-generator'); ?></th>
                            <th><?php _e('Class', 'school-paper-generator'); ?></th>
                            <th><?php _e('Marks', 'school-paper-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Preview rows will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <div class="preview-actions">
                <button type="button" class="button button-primary" id="confirm-import">
                    <i class="fas fa-check"></i> <?php _e('Confirm Import', 'school-paper-generator'); ?>
                </button>
                <button type="button" class="button button-secondary spg-modal-close">
                    <?php _e('Cancel', 'school-paper-generator'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Export Modal -->
<div id="preview-export-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content" style="max-width: 900px;">
        <div class="spg-modal-header">
            <h3><?php _e('Export Preview', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <div class="preview-content" id="export-preview-content">
                <!-- Export preview will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Schedule Backup Modal -->
<div id="schedule-backup-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content" style="max-width: 500px;">
        <div class="spg-modal-header">
            <h3><?php _e('Schedule Backup', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <form id="schedule-backup-form">
                <div class="form-group">
                    <label for="schedule-frequency"><?php _e('Frequency', 'school-paper-generator'); ?></label>
                    <select id="schedule-frequency" name="frequency" required>
                        <option value="daily"><?php _e('Daily', 'school-paper-generator'); ?></option>
                        <option value="weekly"><?php _e('Weekly', 'school-paper-generator'); ?></option>
                        <option value="monthly"><?php _e('Monthly', 'school-paper-generator'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="schedule-time"><?php _e('Time', 'school-paper-generator'); ?></label>
                    <input type="time" id="schedule-time" name="time" value="02:00" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="keep_backups" value="1" checked>
                        <?php _e('Keep only last 5 backups', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="email_notification" value="1">
                        <?php _e('Email notification on backup', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-calendar-check"></i> <?php _e('Schedule Backup', 'school-paper-generator'); ?>
                    </button>
                    <button type="button" class="button spg-modal-close">
                        <?php _e('Cancel', 'school-paper-generator'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Import/Export functionality
    const SPG_ImportExport = {
        currentImportData: null,
        currentFormat: 'csv',
        
        init: function() {
            this.bindEvents();
            this.initTabs();
        },
        
        initTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this).attr('href');
                
                // Update tabs
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update content
                $('.tab-pane').removeClass('active');
                $(target).addClass('active');
            });
        },
        
        bindEvents: function() {
            // Import format buttons
            $('.format-btn').on('click', function() {
                $('.format-btn').removeClass('active');
                $(this).addClass('active');
                SPG_ImportExport.currentFormat = $(this).data('format');
                $('.template-format').text(SPG_ImportExport.currentFormat.toUpperCase());
            });
            
            // Download template
            $('#download-template').on('click', this.downloadTemplate);
            
            // Import forms
            $('#import-questions-form').on('submit', this.importQuestions);
            $('#import-papers-form').on('submit', this.importPapers);
            
            // Preview import
            $('#preview-import').on('click', this.previewImport);
            
            // Export
            $('.export-filters select, .export-filters input').on('change', this.updateExportSummary);
            $('#export-questions-btn').on('click', this.exportQuestions);
            $('#export-papers-btn').on('click', this.exportPapers);
            $('#preview-export').on('click', this.previewExport);
            
            // Backup
            $('#create-backup-btn').on('click', this.createBackup);
            $('#schedule-backup-btn').on('click', this.openScheduleModal);
            $('#restore-backup-form').on('submit', this.restoreBackup);
            
            // Modals
            $('.spg-modal-close').on('click', this.closeModal);
            $('#confirm-import').on('click', this.confirmImport);
            $('#schedule-backup-form').on('submit', this.scheduleBackup);
        },
        
        downloadTemplate: function() {
            const format = SPG_ImportExport.currentFormat;
            window.open(spg_ajax.ajax_url + '?action=spg_download_template&format=' + format + '&nonce=' + spg_ajax.nonce, '_blank');
        },
        
        importQuestions: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'spg_import_questions');
            formData.append('nonce', spg_ajax.nonce);
            formData.append('format', SPG_ImportExport.currentFormat);
            
            // Show progress
            $('.import-progress').show();
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new XMLHttpRequest();
                    
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            $('.progress-fill').css('width', percent + '%');
                            $('.progress-text').text(percent + '%');
                        }
                    });
                    
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        alert('Questions imported successfully! ' + response.data.imported + ' questions added.');
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
        
        previewImport: function() {
            const fileInput = $('#import-file')[0];
            
            if (!fileInput.files.length) {
                alert('Please select a file first');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'spg_preview_import');
            formData.append('nonce', spg_ajax.nonce);
            formData.append('import_file', fileInput.files[0]);
            formData.append('format', SPG_ImportExport.currentFormat);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        SPG_ImportExport.showImportPreview(response.data);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        showImportPreview: function(data) {
            SPG_ImportExport.currentImportData = data;
            
            // Update stats
            $('#preview-total').text(data.total);
            $('#preview-valid').text(data.valid);
            $('#preview-invalid').text(data.invalid);
            $('#preview-duplicate').text(data.duplicate);
            
            // Clear table
            const $tbody = $('#preview-table tbody');
            $tbody.empty();
            
            // Add rows
            if (data.questions && data.questions.length > 0) {
                data.questions.forEach(function(question) {
                    const statusClass = question.status === 'valid' ? 'status-valid' : 
                                      question.status === 'duplicate' ? 'status-duplicate' : 'status-invalid';
                    const statusText = question.status === 'valid' ? '?' : 
                                     question.status === 'duplicate' ? '?' : '?';
                    
                    const row = `
                    <tr class="${statusClass}">
                        <td><span class="status-icon">${statusText}</span></td>
                        <td>${question.question_text.substring(0, 50)}...</td>
                        <td>${question.question_type}</td>
                        <td>${question.subject}</td>
                        <td>${question.class_level}</td>
                        <td>${question.marks}</td>
                    </tr>`;
                    
                    $tbody.append(row);
                });
            }
            
            $('#preview-import-modal').show();
        },
        
        confirmImport: function() {
            if (!SPG_ImportExport.currentImportData) return;
            
            const fileInput = $('#import-file')[0];
            const formData = new FormData();
            
            formData.append('action', 'spg_confirm_import');
            formData.append('nonce', spg_ajax.nonce);
            formData.append('import_file', fileInput.files[0]);
            formData.append('format', SPG_ImportExport.currentFormat);
            formData.append('skip_duplicates', $('#import-questions-form input[name="skip_duplicates"]').is(':checked') ? 1 : 0);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Import completed! ' + response.data.imported + ' questions imported.');
                        $('#preview-import-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        importPapers: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'spg_import_papers');
            formData.append('nonce', spg_ajax.nonce);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Papers imported successfully! ' + response.data.imported + ' papers added.');
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        updateExportSummary: function() {
            const filters = SPG_ImportExport.getExportFilters();
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_question_count',
                    nonce: spg_ajax.nonce,
                    filters: filters
                },
                success: function(response) {
                    if (response.success) {
                        $('#filtered-questions-count').text(response.data.count);
                    }
                }
            });
        },
        
        getExportFilters: function() {
            return {
                subject: $('#export-subject').val(),
                class_level: $('#export-class').val(),
                question_type: $('#export-type').val(),
                difficulty: $('#export-difficulty').val(),
                date_from: $('#export-date-from').val(),
                date_to: $('#export-date-to').val()
            };
        },
        
        exportQuestions: function() {
            const filters = SPG_ImportExport.getExportFilters();
            const format = $('input[name="export_format"]:checked').val();
            const options = {
                include_answers: $('input[name="include_answers"]').is(':checked'),
                include_explanations: $('input[name="include_explanations"]').is(':checked'),
                include_metadata: $('input[name="include_metadata"]').is(':checked')
            };
            
            // Check premium features
            if (!spg_is_premium_active() && (format === 'docx' || format === 'xlsx')) {
                alert('This export format requires premium version. Please upgrade or choose CSV/JSON format.');
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_export_questions',
                    nonce: spg_ajax.nonce,
                    filters: filters,
                    format: format,
                    options: options
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.download_url, '_blank');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        previewExport: function() {
            const filters = SPG_ImportExport.getExportFilters();
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_preview_export',
                    nonce: spg_ajax.nonce,
                    filters: filters
                },
                success: function(response) {
                    if (response.success) {
                        SPG_ImportExport.showExportPreview(response.data);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        showExportPreview: function(data) {
            let html = '<div class="export-preview">';
            
            html += '<div class="preview-stats">';
            html += '<div class="stat"><span class="stat-label">Total Questions:</span> <span class="stat-value">' + data.total + '</span></div>';
            html += '<div class="stat"><span class="stat-label">Subjects:</span> <span class="stat-value">' + data.subjects.join(', ') + '</span></div>';
            html += '<div class="stat"><span class="stat-label">Classes:</span> <span class="stat-value">' + data.classes.join(', ') + '</span></div>';
            html += '</div>';
            
            if (data.questions && data.questions.length > 0) {
                html += '<div class="preview-questions">';
                html += '<h4>Sample Questions (first 5)</h4>';
                
                data.questions.slice(0, 5).forEach(function(question, index) {
                    html += '<div class="preview-question">';
                    html += '<div class="question-header">';
                    html += '<span class="question-number">' + (index + 1) + '.</span>';
                    html += '<span class="question-type">' + question.type + '</span>';
                    html += '<span class="question-marks">' + question.marks + ' marks</span>';
                    html += '</div>';
                    html += '<div class="question-text">' + question.text.substring(0, 100) + '...</div>';
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#export-preview-content').html(html);
            $('#preview-export-modal').show();
        },
        
        exportPapers: function() {
            const format = $('#export-papers-format').val();
            const options = {
                include_questions: $('input[name="include_paper_questions"]').is(':checked'),
                export_all: $('input[name="export_all_papers"]').is(':checked')
            };
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_export_papers',
                    nonce: spg_ajax.nonce,
                    format: format,
                    options: options
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.download_url, '_blank');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        createBackup: function() {
            const description = $('#backup-description').val();
            const options = {
                include_settings: $('input[name="include_settings"]').is(':checked'),
                compress: $('input[name="compress_backup"]').is(':checked')
            };
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_create_backup',
                    nonce: spg_ajax.nonce,
                    description: description,
                    options: options
                },
                success: function(response) {
                    if (response.success) {
                        alert('Backup created successfully!');
                        window.open(response.data.download_url, '_blank');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        openScheduleModal: function() {
            $('#schedule-backup-modal').show();
        },
        
        scheduleBackup: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const data = {};
            
            formData.forEach(function(item) {
                data[item.name] = item.value;
            });
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_schedule_backup',
                    nonce: spg_ajax.nonce,
                    schedule: data
                },
                success: function(response) {
                    if (response.success) {
                        alert('Backup scheduled successfully!');
                        $('#schedule-backup-modal').hide();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        restoreBackup: function(e) {
            e.preventDefault();
            
            if (!confirm('WARNING: This will replace all current data. Are you sure?')) {
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'spg_restore_backup');
            formData.append('nonce', spg_ajax.nonce);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Backup restored successfully!');
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        closeModal: function() {
            $(this).closest('.spg-modal').hide();
        }
    };
    
    // Initialize import/export
    SPG_ImportExport.init();
    
    // Initial export summary update
    SPG_ImportExport.updateExportSummary();
});
</script>

<style>
.spg-import-export {
    max-width: 1200px;
}

.nav-tab-wrapper {
    margin: 20px 0;
    border-bottom: 1px solid #ccc;
}

.nav-tab {
    padding: 10px 20px;
    font-size: 14px;
    text-decoration: none;
    border: 1px solid #ccc;
    border-bottom: none;
    background: #f5f5f5;
    margin-right: 5px;
    border-radius: 4px 4px 0 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.nav-tab.nav-tab-active {
    background: white;
    border-bottom: 1px solid white;
    margin-bottom: -1px;
    color: #2271b1;
}

.tab-content {
    background: white;
    border: 1px solid #ccc;
    border-top: none;
    padding: 30px;
    border-radius: 0 4px 4px 4px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.import-section,
.export-section,
.backup-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.import-section:last-child,
.export-section:last-child,
.backup-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.import-section h3,
.export-section h3,
.backup-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.import-section h3 i,
.export-section h3 i,
.backup-section h3 i {
    color: #667eea;
}

.import-options,
.export-filters,
.backup-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .import-options,
    .export-filters,
    .backup-info {
        grid-template-columns: 1fr;
    }
}

.import-format h4,
.import-instructions h4,
.export-filters h4,
.export-format h4,
.export-options h4,
.backup-options h4,
.restore-options h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.format-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.format-btn {
    padding: 10px 20px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.format-btn.active {
    border-color: #2271b1;
    background: #f0f7ff;
    color: #2271b1;
}

.import-instructions ol {
    margin: 15px 0;
    padding-left: 20px;
}

.import-instructions li {
    margin-bottom: 8px;
}

.template-download {
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.template-format {
    padding: 5px 10px;
    background: #f5f5f5;
    border-radius: 4px;
    font-weight: bold;
    color: #666;
}

.form-group {
    margin-bottom: 20px;
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
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input[type="file"] {
    padding: 8px;
    background: #f9f9f9;
}

.form-group input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

.form-group input[type="checkbox"] + label {
    display: inline;
    font-weight: normal;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.date-range {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-range span {
    color: #666;
}

.import-options-advanced,
.export-options,
.backup-options,
.restore-options {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    margin: 20px 0;
}

.import-progress {
    margin: 25px 0;
}

.progress-bar {
    height: 10px;
    background: #f0f0f0;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #4CAF50;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
}

.export-summary,
.backup-stats {
    background: #f0f7ff;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #2271b1;
}

.summary-stats,
.backup-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
}

.format-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.format-option input[type="radio"] {
    display: none;
}

.format-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
}

.format-option input[type="radio"]:checked + label {
    border-color: #2271b1;
    background: #f0f7ff;
    color: #2271b1;
}

.format-option label i {
    font-size: 32px;
    margin-bottom: 10px;
}

.format-option label span {
    font-weight: 500;
    margin-bottom: 5px;
}

.format-option label small {
    font-size: 0.8em;
    color: #666;
}

.premium-feature {
    background: #fff3e0;
    padding: 15px;
    border-radius: 4px;
    border-left: 4px solid #ff9800;
    margin-top: 15px;
    text-align: center;
}

.premium-feature p {
    margin: 10px 0;
    color: #666;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.backup-warning {
    display: flex;
    gap: 15px;
    background: #fff5f5;
    border: 1px solid #ffcdd2;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 25px;
}

.warning-icon {
    color: #d32f2f;
    font-size: 24px;
}

.warning-content h4 {
    margin: 0 0 10px 0;
    color: #d32f2f;
}

.warning-content p {
    margin: 0;
    color: #666;
}

.backup-description {
    margin-top: 15px;
    color: #666;
    line-height: 1.6;
}

.no-backups {
    text-align: center;
    padding: 40px;
    color: #999;
}

.no-backups i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-backups p {
    margin: 0;
}

/* Preview modal styles */
.preview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.preview-stats .stat-value.valid { color: #4CAF50; }
.preview-stats .stat-value.invalid { color: #f44336; }
.preview-stats .stat-value.duplicate { color: #ff9800; }

.preview-table-container {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 25px;
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
}

.preview-table th {
    background: #f5f5f5;
    padding: 12px 15px;
    text-align: left;
    font-weight: 500;
    color: #333;
    border-bottom: 2px solid #ddd;
    position: sticky;
    top: 0;
}

.preview-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

.preview-table tr.status-valid {
    background: #f8fff8;
}

.preview-table tr.status-invalid {
    background: #fff5f5;
}

.preview-table tr.status-duplicate {
    background: #fffbf0;
}

.status-icon {
    display: inline-block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    border-radius: 50%;
    font-weight: bold;
}

tr.status-valid .status-icon {
    background: #4CAF50;
    color: white;
}

tr.status-invalid .status-icon {
    background: #f44336;
    color: white;
}

tr.status-duplicate .status-icon {
    background: #ff9800;
    color: white;
}

.preview-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.export-preview {
    padding: 20px;
}

.preview-questions {
    margin-top: 25px;
}

.preview-questions h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.preview-question {
    margin-bottom: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
    border-left: 3px solid #667eea;
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.question-number {
    font-weight: bold;
    color: #333;
}

.question-type {
    font-size: 0.9em;
    padding: 3px 8px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
}

.question-marks {
    font-size: 0.9em;
    font-weight: bold;
    color: #666;
}

.question-text {
    line-height: 1.5;
    color: #555;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .nav-tab {
        padding: 8px 12px;
        font-size: 12px;
    }
    
    .format-buttons {
        flex-direction: column;
    }
    
    .format-btn {
        justify-content: center;
    }
}
</style>