<?php
if (!defined('ABSPATH')) exit;

// Get data for the form
$subjects = spg_get_subjects();
$class_levels = spg_get_class_levels();
$school_info = spg_get_school_info();

// Check if editing existing paper
$paper_id = !empty($_GET['paper_id']) ? intval($_GET['paper_id']) : 0;
$paper_data = array();

if ($paper_id) {
    $paper_generator = SPG_Paper_Generator::get_instance();
    $paper_data = $paper_generator->get_paper($paper_id);
    
    if (!$paper_data) {
        echo '<div class="notice notice-error"><p>' . __('Paper not found!', 'school-paper-generator') . '</p></div>';
        return;
    }
}
?>

<div class="wrap spg-create-paper">
    <div class="spg-header">
        <h1>
            <i class="fas fa-file-alt"></i> 
            <?php echo $paper_id ? __('Edit Paper', 'school-paper-generator') : __('Create New Paper', 'school-paper-generator'); ?>
        </h1>
        <p class="description"><?php _e('Create a professional exam paper for your school', 'school-paper-generator'); ?></p>
    </div>
    
    <div class="spg-paper-builder">
        <!-- Left Panel: Paper Settings -->
        <div class="spg-paper-settings">
            <div class="settings-card">
                <h3><i class="fas fa-cog"></i> <?php _e('Paper Settings', 'school-paper-generator'); ?></h3>
                
                <form id="paper-settings-form">
                    <div class="form-group">
                        <label for="paper-title"><?php _e('Paper Title *', 'school-paper-generator'); ?></label>
                        <input type="text" id="paper-title" name="title" 
                               value="<?php echo !empty($paper_data['title']) ? esc_attr($paper_data['title']) : ''; ?>"
                               placeholder="<?php esc_attr_e('e.g., Annual Examination 2024', 'school-paper-generator'); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="paper-subject"><?php _e('Subject *', 'school-paper-generator'); ?></label>
                            <select id="paper-subject" name="subject" required>
                                <option value=""><?php _e('Select Subject', 'school-paper-generator'); ?></option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo esc_attr($subject); ?>" 
                                    <?php selected(!empty($paper_data['subject']) ? $paper_data['subject'] : '', $subject); ?>>
                                    <?php echo esc_html($subject); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="paper-class"><?php _e('Class Level *', 'school-paper-generator'); ?></label>
                            <select id="paper-class" name="class_level" required>
                                <option value=""><?php _e('Select Class', 'school-paper-generator'); ?></option>
                                <?php foreach ($class_levels as $class): ?>
                                <option value="<?php echo esc_attr($class); ?>"
                                    <?php selected(!empty($paper_data['class_level']) ? $paper_data['class_level'] : '', $class); ?>>
                                    <?php echo esc_html($class); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="total-marks"><?php _e('Total Marks *', 'school-paper-generator'); ?></label>
                            <input type="number" id="total-marks" name="total_marks" 
                                   value="<?php echo !empty($paper_data['total_marks']) ? esc_attr($paper_data['total_marks']) : 100; ?>"
                                   min="1" max="500" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time-duration"><?php _e('Time Duration', 'school-paper-generator'); ?></label>
                            <input type="text" id="time-duration" name="time_duration" 
                                   value="<?php echo !empty($paper_data['time_duration']) ? esc_attr($paper_data['time_duration']) : '3 hours'; ?>"
                                   placeholder="<?php esc_attr_e('e.g., 3 hours', 'school-paper-generator'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="paper-instructions"><?php _e('General Instructions', 'school-paper-generator'); ?></label>
                        <textarea id="paper-instructions" name="instructions" rows="4"><?php 
                            echo !empty($paper_data['instructions']) ? esc_textarea($paper_data['instructions']) : implode("\n", spg_get_default_instructions());
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="shuffle-questions" name="shuffle_questions" value="1">
                            <?php _e('Shuffle Questions', 'school-paper-generator'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="shuffle-options" name="shuffle_options" value="1">
                            <?php _e('Shuffle MCQ Options', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <hr>
                    
                    <h4><?php _e('Question Distribution', 'school-paper-generator'); ?></h4>
                    <div class="question-distribution">
                        <div class="distribution-row">
                            <div class="dist-type">MCQ</div>
                            <div class="dist-count">
                                <input type="number" class="section-count" data-type="mcq" value="20" min="0">
                            </div>
                            <div class="dist-marks">
                                <input type="number" class="section-marks" data-type="mcq" value="1" min="1">
                            </div>
                            <div class="dist-total">20</div>
                        </div>
                        
                        <div class="distribution-row">
                            <div class="dist-type">Short</div>
                            <div class="dist-count">
                                <input type="number" class="section-count" data-type="short" value="10" min="0">
                            </div>
                            <div class="dist-marks">
                                <input type="number" class="section-marks" data-type="short" value="3" min="1">
                            </div>
                            <div class="dist-total">30</div>
                        </div>
                        
                        <div class="distribution-row">
                            <div class="dist-type">Long</div>
                            <div class="dist-count">
                                <input type="number" class="section-count" data-type="long" value="5" min="0">
                            </div>
                            <div class="dist-marks">
                                <input type="number" class="section-marks" data-type="long" value="10" min="1">
                            </div>
                            <div class="dist-total">50</div>
                        </div>
                        
                        <div class="distribution-total">
                            <div class="total-label"><?php _e('Total Marks:', 'school-paper-generator'); ?></div>
                            <div class="total-value" id="distribution-total">100</div>
                        </div>
                    </div>
                    
                    <button type="button" class="button button-primary" id="generate-paper">
                        <i class="fas fa-magic"></i> <?php _e('Generate Paper', 'school-paper-generator'); ?>
                    </button>
                </form>
            </div>
            
            <div class="settings-card">
                <h3><i class="fas fa-school"></i> <?php _e('School Information', 'school-paper-generator'); ?></h3>
                
                <div class="form-group">
                    <label for="school-name"><?php _e('School Name', 'school-paper-generator'); ?></label>
                    <input type="text" id="school-name" name="school_name" 
                           value="<?php echo esc_attr($school_info['name']); ?>"
                           placeholder="<?php esc_attr_e('Enter school name', 'school-paper-generator'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="school-address"><?php _e('School Address', 'school-paper-generator'); ?></label>
                    <textarea id="school-address" name="school_address" rows="3"><?php 
                        echo esc_textarea($school_info['address']); 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="school-logo"><?php _e('School Logo', 'school-paper-generator'); ?></label>
                    <div class="logo-upload-area" id="logo-upload-area">
                        <?php if (!empty($school_info['logo'])): ?>
                        <div class="logo-preview">
                            <img src="<?php echo esc_url($school_info['logo']); ?>" alt="School Logo">
                            <button type="button" class="remove-logo" title="<?php esc_attr_e('Remove Logo', 'school-paper-generator'); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="logo-placeholder">
                            <i class="fas fa-school"></i>
                            <p><?php _e('No logo uploaded', 'school-paper-generator'); ?></p>
                            <?php if (!spg_is_premium_active()): ?>
                            <div class="premium-feature">
                                <span class="premium-badge">PREMIUM</span>
                                <p><?php _e('School logo feature requires premium version', 'school-paper-generator'); ?></p>
                                <a href="https://yourwebsite.com/upgrade" class="button button-small">
                                    <?php _e('Upgrade Now', 'school-paper-generator'); ?>
                                </a>
                            </div>
                            <?php else: ?>
                            <button type="button" class="button" id="upload-logo-btn">
                                <i class="fas fa-upload"></i> <?php _e('Upload Logo', 'school-paper-generator'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="school-logo-url" name="school_logo" value="<?php echo esc_url($school_info['logo']); ?>">
                </div>
            </div>
        </div>
        
        <!-- Middle Panel: Question Selection -->
        <div class="spg-questions-panel">
            <div class="panel-header">
                <h3><i class="fas fa-question-circle"></i> <?php _e('Question Bank', 'school-paper-generator'); ?></h3>
                <div class="question-filters">
                    <input type="text" id="question-search" placeholder="<?php esc_attr_e('Search questions...', 'school-paper-generator'); ?>">
                    <select id="filter-difficulty">
                        <option value=""><?php _e('All Difficulty', 'school-paper-generator'); ?></option>
                        <option value="easy"><?php _e('Easy', 'school-paper-generator'); ?></option>
                        <option value="medium"><?php _e('Medium', 'school-paper-generator'); ?></option>
                        <option value="hard"><?php _e('Hard', 'school-paper-generator'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="question-list" id="question-list">
                <div class="loading-questions">
                    <i class="fas fa-spinner fa-spin"></i>
                    <?php _e('Loading questions...', 'school-paper-generator'); ?>
                </div>
            </div>
        </div>
        
        <!-- Right Panel: Paper Preview -->
        <div class="spg-paper-preview">
            <div class="preview-header">
                <h3><i class="fas fa-eye"></i> <?php _e('Paper Preview', 'school-paper-generator'); ?></h3>
                <div class="preview-actions">
                    <button type="button" class="button button-small" id="preview-refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="button button-small" id="preview-fullscreen">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            
            <div class="paper-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Questions:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="total-questions">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Marks:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="total-marks-display">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('MCQ:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="mcq-count">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Short:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="short-count">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Long:', 'school-paper-generator'); ?></span>
                    <span class="stat-value" id="long-count">0</span>
                </div>
            </div>
            
            <div class="paper-questions-container">
                <div class="paper-questions" id="paper-questions">
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h4><?php _e('No Questions Added', 'school-paper-generator'); ?></h4>
                        <p><?php _e('Add questions from the question bank or generate a paper automatically.', 'school-paper-generator'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="paper-actions">
                <button type="button" class="button button-primary" id="save-paper" disabled>
                    <i class="fas fa-save"></i> <?php _e('Save Paper', 'school-paper-generator'); ?>
                </button>
                
                <div class="export-dropdown">
                    <button type="button" class="button button-secondary" id="export-paper" disabled>
                        <i class="fas fa-download"></i> <?php _e('Export', 'school-paper-generator'); ?>
                    </button>
                    <div class="export-menu">
                        <a href="#" class="export-format" data-format="pdf">
                            <i class="far fa-file-pdf"></i> <?php _e('PDF', 'school-paper-generator'); ?>
                        </a>
                        <?php if (spg_is_premium_active()): ?>
                        <a href="#" class="export-format" data-format="docx">
                            <i class="far fa-file-word"></i> <?php _e('Word', 'school-paper-generator'); ?>
                        </a>
                        <a href="#" class="export-format" data-format="xlsx">
                            <i class="far fa-file-excel"></i> <?php _e('Excel', 'school-paper-generator'); ?>
                        </a>
                        <a href="#" class="export-format" data-format="html">
                            <i class="far fa-file-code"></i> <?php _e('HTML', 'school-paper-generator'); ?>
                        </a>
                        <?php else: ?>
                        <div class="premium-locked">
                            <span class="premium-badge">PREMIUM</span>
                            <p><?php _e('Multiple export formats available in premium', 'school-paper-generator'); ?></p>
                            <a href="https://yourwebsite.com/upgrade" class="button button-small">
                                <?php _e('Upgrade Now', 'school-paper-generator'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($paper_id): ?>
                <a href="<?php echo admin_url('admin.php?page=spg-generated-papers'); ?>" class="button">
                    <?php _e('Back to Papers', 'school-paper-generator'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Preview Modal -->
<div id="fullscreen-preview" class="spg-modal" style="display: none;">
    <div class="spg-modal-content fullscreen">
        <div class="spg-modal-header">
            <h3><?php _e('Paper Preview', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body" id="fullscreen-content">
            <!-- Fullscreen preview content will be loaded here -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Paper builder functionality
    const SPG_PaperBuilder = {
        paperQuestions: [],
        allQuestions: [],
        
        init: function() {
            this.loadQuestions();
            this.bindEvents();
            this.initSortable();
            this.updateDistributionTotal();
        },
        
        bindEvents: function() {
            // Generate paper button
            $('#generate-paper').on('click', this.generatePaper);
            
            // Save paper button
            $('#save-paper').on('click', this.savePaper);
            
            // Export buttons
            $('#export-paper').on('click', function() {
                $(this).siblings('.export-menu').toggle();
            });
            
            $('.export-format').on('click', function(e) {
                e.preventDefault();
                const format = $(this).data('format');
                SPG_PaperBuilder.exportPaper(format);
                $(this).closest('.export-menu').hide();
            });
            
            // Close export menu when clicking elsewhere
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.export-dropdown').length) {
                    $('.export-menu').hide();
                }
            });
            
            // Distribution calculations
            $('.section-count, .section-marks').on('input', this.updateDistributionRow);
            $('.section-count, .section-marks').on('input', this.updateDistributionTotal);
            
            // Question search
            $('#question-search').on('keyup', this.searchQuestions);
            $('#filter-difficulty').on('change', this.filterQuestions);
            
            // Preview actions
            $('#preview-refresh').on('click', this.updatePreview);
            $('#preview-fullscreen').on('click', this.showFullscreenPreview);
            
            // Logo upload
            $('#upload-logo-btn').on('click', this.uploadLogo);
            $('.remove-logo').on('click', this.removeLogo);
            
            // Modal close
            $('.spg-modal-close').on('click', function() {
                $(this).closest('.spg-modal').hide();
            });
        },
        
        initSortable: function() {
            // Make question list sortable
            $('#question-list').sortable({
                connectWith: '#paper-questions',
                helper: 'clone',
                revert: true,
                start: function(event, ui) {
                    ui.item.addClass('dragging');
                },
                stop: function(event, ui) {
                    ui.item.removeClass('dragging');
                }
            });
            
            // Make paper questions sortable
            $('#paper-questions').sortable({
                connectWith: '#question-list',
                placeholder: 'question-placeholder',
                update: function(event, ui) {
                    SPG_PaperBuilder.updatePaperQuestions();
                }
            });
        },
        
        loadQuestions: function() {
            const subject = $('#paper-subject').val();
            const classLevel = $('#paper-class').val();
            
            if (!subject || !classLevel) {
                $('#question-list').html('<div class="no-questions">Please select subject and class first</div>');
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_questions',
                    nonce: spg_ajax.nonce,
                    filters: {
                        subject: subject,
                        class_level: classLevel
                    }
                },
                beforeSend: function() {
                    $('#question-list').html('<div class="loading-questions"><i class="fas fa-spinner fa-spin"></i> Loading questions...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        SPG_PaperBuilder.allQuestions = response.data.questions;
                        SPG_PaperBuilder.renderQuestions();
                    } else {
                        $('#question-list').html('<div class="no-questions">' + response.data.message + '</div>');
                    }
                }
            });
        },
        
        renderQuestions: function() {
            if (SPG_PaperBuilder.allQuestions.length === 0) {
                $('#question-list').html('<div class="no-questions">No questions found for selected criteria</div>');
                return;
            }
            
            let html = '';
            SPG_PaperBuilder.allQuestions.forEach(function(question) {
                html += SPG_PaperBuilder.getQuestionHTML(question);
            });
            
            $('#question-list').html(html);
            
            // Make questions draggable
            $('.question-item').draggable({
                connectToSortable: '#paper-questions',
                helper: 'clone',
                revert: 'invalid'
            });
        },
        
        getQuestionHTML: function(question) {
            const typeLabel = spg_ajax.text[question.question_type] || question.question_type;
            const difficultyLabel = spg_ajax.text[question.difficulty] || question.difficulty;
            
            return `
            <div class="question-item" data-id="${question.id}" data-type="${question.question_type}" data-marks="${question.marks}">
                <div class="question-header">
                    <span class="question-type">${typeLabel}</span>
                    <span class="question-marks">${question.marks} marks</span>
                </div>
                <div class="question-text">${question.question_text.substring(0, 100)}...</div>
                <div class="question-meta">
                    <span class="question-difficulty difficulty-${question.difficulty}">${difficultyLabel}</span>
                </div>
                <div class="question-actions">
                    <button type="button" class="button button-small add-question" data-id="${question.id}">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>`;
        },
        
        searchQuestions: function() {
            const searchTerm = $(this).val().toLowerCase();
            
            if (!searchTerm) {
                $('.question-item').show();
                return;
            }
            
            $('.question-item').each(function() {
                const questionText = $(this).find('.question-text').text().toLowerCase();
                $(this).toggle(questionText.includes(searchTerm));
            });
        },
        
        filterQuestions: function() {
            const difficulty = $(this).val();
            
            if (!difficulty) {
                $('.question-item').show();
                return;
            }
            
            $('.question-item').each(function() {
                const questionDifficulty = $(this).find('.question-difficulty').hasClass('difficulty-' + difficulty);
                $(this).toggle(questionDifficulty);
            });
        },
        
        generatePaper: function() {
            const config = SPG_PaperBuilder.getGenerationConfig();
            
            if (!config.subject || !config.class_level) {
                alert('Please select subject and class level');
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_generate_paper',
                    nonce: spg_ajax.nonce,
                    config: config
                },
                beforeSend: function() {
                    $('#generate-paper').prop('disabled', true).text('Generating...');
                },
                success: function(response) {
                    if (response.success) {
                        SPG_PaperBuilder.paperQuestions = response.data.questions;
                        SPG_PaperBuilder.renderPaperQuestions();
                        $('#save-paper').prop('disabled', false);
                    } else {
                        alert(response.data.message);
                    }
                },
                complete: function() {
                    $('#generate-paper').prop('disabled', false).text('Generate Paper');
                }
            });
        },
        
        getGenerationConfig: function() {
            const sections = [];
            
            $('.distribution-row').each(function() {
                const type = $(this).find('.dist-type').text().toLowerCase();
                const count = $(this).find('.section-count').val();
                const marksPer = $(this).find('.section-marks').val();
                
                if (count > 0) {
                    sections.push({
                        type: type,
                        count: parseInt(count),
                        marks_per: parseInt(marksPer)
                    });
                }
            });
            
            return {
                subject: $('#paper-subject').val(),
                class_level: $('#paper-class').val(),
                total_marks: $('#total-marks').val(),
                time_duration: $('#time-duration').val(),
                sections: sections,
                shuffle_questions: $('#shuffle-questions').is(':checked'),
                shuffle_options: $('#shuffle-options').is(':checked'),
                difficulty: $('#filter-difficulty').val() || 'medium'
            };
        },
        
        renderPaperQuestions: function() {
            let html = '';
            
            if (SPG_PaperBuilder.paperQuestions.length === 0) {
                html = `
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h4>No Questions Added</h4>
                    <p>Add questions from the question bank or generate a paper automatically.</p>
                </div>`;
            } else {
                SPG_PaperBuilder.paperQuestions.forEach(function(question, index) {
                    html += SPG_PaperBuilder.getPaperQuestionHTML(question, index + 1);
                });
            }
            
            $('#paper-questions').html(html);
            SPG_PaperBuilder.updatePaperStats();
            SPG_PaperBuilder.bindQuestionActions();
        },
        
        getPaperQuestionHTML: function(question, number) {
            let optionsHtml = '';
            
            if (question.type === 'mcq' && question.options) {
                optionsHtml = '<div class="question-options">';
                question.options.forEach(function(option, idx) {
                    const letter = String.fromCharCode(65 + idx);
                    optionsHtml += `<div class="option"><span class="option-letter">${letter}.</span> ${option}</div>`;
                });
                optionsHtml += '</div>';
            }
            
            return `
            <div class="paper-question" data-id="${question.id}" data-type="${question.type}" data-marks="${question.marks}">
                <div class="question-number">Q${number}.</div>
                <div class="question-content">
                    <div class="question-text">${question.text}</div>
                    ${optionsHtml}
                </div>
                <div class="question-footer">
                    <span class="question-marks">[${question.marks} marks]</span>
                    <div class="question-actions">
                        <button type="button" class="button button-small remove-question" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        },
        
        bindQuestionActions: function() {
            $('.add-question').on('click', function() {
                const questionId = $(this).data('id');
                SPG_PaperBuilder.addQuestionToPaper(questionId);
            });
            
            $('.remove-question').on('click', function() {
                const $question = $(this).closest('.paper-question');
                const questionId = $question.data('id');
                SPG_PaperBuilder.removeQuestionFromPaper(questionId);
            });
        },
        
        addQuestionToPaper: function(questionId) {
            const question = SPG_PaperBuilder.allQuestions.find(q => q.id == questionId);
            
            if (question) {
                SPG_PaperBuilder.paperQuestions.push({
                    id: question.id,
                    type: question.question_type,
                    text: question.question_text,
                    marks: question.marks,
                    options: question.options ? JSON.parse(question.options) : null
                });
                
                SPG_PaperBuilder.renderPaperQuestions();
                $('#save-paper').prop('disabled', false);
            }
        },
        
        removeQuestionFromPaper: function(questionId) {
            const index = SPG_PaperBuilder.paperQuestions.findIndex(q => q.id == questionId);
            
            if (index > -1) {
                SPG_PaperBuilder.paperQuestions.splice(index, 1);
                SPG_PaperBuilder.renderPaperQuestions();
                
                if (SPG_PaperBuilder.paperQuestions.length === 0) {
                    $('#save-paper').prop('disabled', true);
                }
            }
        },
        
        updatePaperQuestions: function() {
            SPG_PaperBuilder.paperQuestions = [];
            
            $('#paper-questions .paper-question').each(function() {
                const questionId = $(this).data('id');
                const question = SPG_PaperBuilder.allQuestions.find(q => q.id == questionId);
                
                if (question) {
                    SPG_PaperBuilder.paperQuestions.push({
                        id: question.id,
                        type: question.question_type,
                        text: question.question_text,
                        marks: question.marks,
                        options: question.options ? JSON.parse(question.options) : null
                    });
                }
            });
            
            SPG_PaperBuilder.updatePaperStats();
            
            if (SPG_PaperBuilder.paperQuestions.length > 0) {
                $('#save-paper').prop('disabled', false);
            }
        },
        
        updatePaperStats: function() {
            const totalQuestions = SPG_PaperBuilder.paperQuestions.length;
            const totalMarks = SPG_PaperBuilder.paperQuestions.reduce((sum, q) => sum + parseInt(q.marks), 0);
            const mcqCount = SPG_PaperBuilder.paperQuestions.filter(q => q.type === 'mcq').length;
            const shortCount = SPG_PaperBuilder.paperQuestions.filter(q => q.type === 'short').length;
            const longCount = SPG_PaperBuilder.paperQuestions.filter(q => q.type === 'long').length;
            
            $('#total-questions').text(totalQuestions);
            $('#total-marks-display').text(totalMarks);
            $('#mcq-count').text(mcqCount);
            $('#short-count').text(shortCount);
            $('#long-count').text(longCount);
            
            // Update export button state
            $('#export-paper').prop('disabled', totalQuestions === 0);
        },
        
        updateDistributionRow: function() {
            const $row = $(this).closest('.distribution-row');
            const count = $row.find('.section-count').val();
            const marks = $row.find('.section-marks').val();
            const total = count * marks;
            
            $row.find('.dist-total').text(total);
        },
        
        updateDistributionTotal: function() {
            let total = 0;
            
            $('.distribution-row').each(function() {
                const rowTotal = parseInt($(this).find('.dist-total').text()) || 0;
                total += rowTotal;
            });
            
            $('#distribution-total').text(total);
            $('#total-marks').val(total);
        },
        
        savePaper: function() {
            const paperData = SPG_PaperBuilder.getPaperData();
            
            if (!paperData.title) {
                alert('Please enter paper title');
                return;
            }
            
            if (paperData.questions.length === 0) {
                alert('Paper must contain at least one question');
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_save_paper',
                    nonce: spg_ajax.nonce,
                    paper_data: paperData
                },
                beforeSend: function() {
                    $('#save-paper').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Paper saved successfully!');
                        window.location.href = response.data.paper_url;
                    } else {
                        alert(response.data.message);
                    }
                },
                complete: function() {
                    $('#save-paper').prop('disabled', false).text('Save Paper');
                }
            });
        },
        
        getPaperData: function() {
            const questions = [];
            
            SPG_PaperBuilder.paperQuestions.forEach(function(question, index) {
                questions.push({
                    id: question.id,
                    order: index + 1,
                    marks: question.marks,
                    type: question.type
                });
            });
            
            return {
                title: $('#paper-title').val(),
                subject: $('#paper-subject').val(),
                class_level: $('#paper-class').val(),
                total_marks: $('#total-marks').val(),
                time_duration: $('#time-duration').val(),
                instructions: $('#paper-instructions').val(),
                school_name: $('#school-name').val(),
                school_logo: $('#school-logo-url').val(),
                school_address: $('#school-address').val(),
                questions: questions
            };
        },
        
        exportPaper: function(format) {
            const paperData = SPG_PaperBuilder.getPaperData();
            
            if (paperData.questions.length === 0) {
                alert('Paper must contain at least one question');
                return;
            }
            
            // Check if paper has been saved
            const paperId = <?php echo $paper_id ?: '0'; ?>;
            
            let url = spg_ajax.ajax_url + '?action=spg_export_paper&format=' + format + '&nonce=' + spg_ajax.nonce;
            
            if (paperId > 0) {
                url += '&paper_id=' + paperId;
                window.open(url, '_blank');
            } else {
                // For unsaved papers, use AJAX
                $.ajax({
                    url: spg_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'spg_export_paper',
                        nonce: spg_ajax.nonce,
                        format: format,
                        paper_data: paperData
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob) {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'paper-' + Date.now() + '.' + format;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                });
            }
        },
        
        updatePreview: function() {
            // Refresh the preview
            SPG_PaperBuilder.renderPaperQuestions();
        },
        
        showFullscreenPreview: function() {
            const paperData = SPG_PaperBuilder.getPaperData();
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_paper_preview',
                    nonce: spg_ajax.nonce,
                    paper_data: paperData
                },
                success: function(response) {
                    if (response.success) {
                        $('#fullscreen-content').html(response.data.html);
                        $('#fullscreen-preview').show();
                    }
                }
            });
        },
        
        uploadLogo: function() {
            const frame = wp.media({
                title: 'Select School Logo',
                button: {
                    text: 'Use this logo'
                },
                multiple: false
            });
            
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $('#school-logo-url').val(attachment.url);
                
                const html = `
                <div class="logo-preview">
                    <img src="${attachment.url}" alt="School Logo">
                    <button type="button" class="remove-logo" title="Remove Logo">
                        <i class="fas fa-times"></i>
                    </button>
                </div>`;
                
                $('#logo-upload-area').html(html);
                $('.remove-logo').on('click', SPG_PaperBuilder.removeLogo);
            });
            
            frame.open();
        },
        
        removeLogo: function() {
            $('#school-logo-url').val('');
            
            const html = `
            <div class="logo-placeholder">
                <i class="fas fa-school"></i>
                <p>No logo uploaded</p>
                <button type="button" class="button" id="upload-logo-btn">
                    <i class="fas fa-upload"></i> Upload Logo
                </button>
            </div>`;
            
            $('#logo-upload-area').html(html);
            $('#upload-logo-btn').on('click', SPG_PaperBuilder.uploadLogo);
        }
    };
    
    // Initialize paper builder
    SPG_PaperBuilder.init();
    
    // Load questions when subject or class changes
    $('#paper-subject, #paper-class').on('change', function() {
        SPG_PaperBuilder.loadQuestions();
    });
});
</script>

<style>
.spg-create-paper {
    max-width: 1800px;
}

.spg-paper-builder {
    display: grid;
    grid-template-columns: 300px 400px 1fr;
    gap: 20px;
    margin-top: 20px;
}

.settings-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.settings-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-card h3 i {
    color: #667eea;
}

.form-group {
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
    min-height: 80px;
    resize: vertical;
}

.form-row {
    display: flex;
    gap: 10px;
}

.form-row .form-group {
    flex: 1;
}

.question-distribution {
    margin: 15px 0;
}

.distribution-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}

.dist-type {
    width: 60px;
    font-weight: 500;
}

.dist-count,
.dist-marks {
    width: 70px;
}

.dist-count input,
.dist-marks input {
    width: 100%;
    padding: 5px;
    text-align: center;
}

.dist-total {
    width: 50px;
    text-align: center;
    font-weight: bold;
}

.distribution-total {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #e3f2fd;
    border-radius: 4px;
    margin-top: 10px;
}

.total-label {
    font-weight: 500;
}

.total-value {
    font-weight: bold;
    color: #1976d2;
}

.logo-upload-area {
    border: 2px dashed #ddd;
    padding: 20px;
    text-align: center;
    border-radius: 4px;
}

.logo-preview {
    position: relative;
    display: inline-block;
}

.logo-preview img {
    max-height: 100px;
    max-width: 200px;
}

.remove-logo {
    position: absolute;
    top: -10px;
    right: -10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.logo-placeholder {
    color: #999;
}

.logo-placeholder i {
    font-size: 48px;
    margin-bottom: 10px;
}

.logo-placeholder p {
    margin: 10px 0;
}

.premium-feature {
    margin-top: 10px;
    padding: 10px;
    background: #fff3e0;
    border-radius: 4px;
}

.premium-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

/* Questions Panel */
.spg-questions-panel {
    background: white;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.panel-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
}

.panel-header h3 {
    margin: 0 0 10px 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.question-filters {
    display: flex;
    gap: 10px;
}

.question-filters input,
.question-filters select {
    flex: 1;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.question-list {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
    min-height: 600px;
}

.loading-questions {
    text-align: center;
    padding: 40px;
    color: #666;
}

.no-questions {
    text-align: center;
    padding: 40px;
    color: #666;
}

.question-item {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    cursor: move;
    transition: all 0.2s ease;
}

.question-item:hover {
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.question-type {
    font-size: 0.8em;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 12px;
    background: #e3f2fd;
    color: #1976d2;
}

.question-marks {
    font-size: 0.9em;
    font-weight: bold;
    color: #666;
}

.question-text {
    font-size: 0.9em;
    margin-bottom: 8px;
    color: #333;
}

.question-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-difficulty {
    font-size: 0.8em;
    padding: 2px 6px;
    border-radius: 10px;
}

.difficulty-easy { background: #e8f5e8; color: #388e3c; }
.difficulty-medium { background: #fff3e0; color: #f57c00; }
.difficulty-hard { background: #ffebee; color: #d32f2f; }

.question-actions {
    text-align: right;
}

.question-actions .button-small {
    padding: 2px 8px;
    font-size: 0.8em;
}

/* Paper Preview */
.spg-paper-preview {
    background: white;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.preview-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-header h3 {
    margin: 0;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preview-actions {
    display: flex;
    gap: 5px;
}

.paper-stats {
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 10px;
}

.stat-item {
    display: flex;
    gap: 5px;
}

.stat-label {
    color: #666;
}

.stat-value {
    font-weight: bold;
    color: #333;
}

.paper-questions-container {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.paper-questions {
    min-height: 600px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h4 {
    margin: 0 0 10px 0;
    color: #666;
}

.empty-state p {
    margin: 0;
    font-size: 0.9em;
}

.paper-question {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    page-break-inside: avoid;
}

.question-number {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 10px;
    color: #333;
}

.question-content {
    margin-bottom: 10px;
}

.question-text {
    margin-bottom: 10px;
    line-height: 1.5;
}

.question-options {
    margin-left: 20px;
}

.option {
    margin: 5px 0;
}

.option-letter {
    font-weight: bold;
    margin-right: 10px;
}

.question-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    border-top: 1px dashed #ddd;
}

.question-marks {
    font-weight: bold;
    color: #1976d2;
}

.paper-actions {
    padding: 15px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.export-dropdown {
    position: relative;
}

.export-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    min-width: 150px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
    z-index: 100;
}

.export-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
}

.export-menu a:hover {
    background: #f5f5f5;
}

.premium-locked {
    padding: 10px;
    text-align: center;
}

.premium-locked p {
    margin: 10px 0;
    font-size: 0.9em;
    color: #666;
}

/* Modal fullscreen */
.spg-modal-content.fullscreen {
    width: 90%;
    height: 90%;
    max-width: none;
}

.spg-modal-content.fullscreen .spg-modal-body {
    height: calc(100% - 60px);
    overflow-y: auto;
}

/* Responsive design */
@media (max-width: 1400px) {
    .spg-paper-builder {
        grid-template-columns: 300px 1fr;
    }
    
    .spg-paper-preview {
        grid-column: span 2;
    }
}

@media (max-width: 1024px) {
    .spg-paper-builder {
        grid-template-columns: 1fr;
    }
    
    .spg-paper-preview {
        grid-column: span 1;
    }
}
</style>