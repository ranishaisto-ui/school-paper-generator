<?php
if (!defined('ABSPATH')) exit;

// Get paper ID from URL or shortcode attribute
$paper_id = !empty($_GET['paper_id']) ? intval($_GET['paper_id']) : 0;
if (empty($paper_id) && !empty($atts['id'])) {
    $paper_id = intval($atts['id']);
}

// Get paper data
$paper_generator = SPG_Paper_Generator::get_instance();
$paper = $paper_generator->get_paper($paper_id);

if (!$paper) {
    echo '<div class="spg-paper-not-found">';
    echo '<h3>' . __('Paper Not Found', 'school-paper-generator') . '</h3>';
    echo '<p>' . __('The requested exam paper could not be found.', 'school-paper-generator') . '</p>';
    echo '</div>';
    return;
}

// Get display mode
$mode = !empty($_GET['mode']) ? sanitize_text_field($_GET['mode']) : 'view';
$show_answers = !empty($_GET['show_answers']) ? true : (!empty($atts['show_answers']) ? $atts['show_answers'] : false);
$is_print_mode = ($mode === 'print');
$is_student_mode = ($mode === 'student');

// Get school info
$school_info = spg_get_school_info();

// Paper questions
$questions = !empty($paper['questions']) ? $paper['questions'] : array();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($paper['title']); ?> - <?php echo esc_html($school_info['name']); ?></title>
    
    <?php if ($is_print_mode): ?>
    <link rel="stylesheet" href="<?php echo SPG_PLUGIN_URL . 'public/assets/css/print.css'; ?>">
    <?php else: ?>
    <link rel="stylesheet" href="<?php echo SPG_PLUGIN_URL . 'public/assets/css/paper-style.css'; ?>">
    <?php endif; ?>
    
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { font-size: 12pt; }
        }
    </style>
    
    <?php wp_head(); ?>
</head>
<body class="spg-paper-display <?php echo $mode; ?>-mode">
    <div class="spg-paper-container">
        
        <!-- Paper Header -->
        <header class="paper-header">
            <?php if (!empty($school_info['logo']) && !$is_student_mode): ?>
            <div class="school-logo">
                <img src="<?php echo esc_url($school_info['logo']); ?>" alt="<?php echo esc_attr($school_info['name']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="school-info">
                <h1 class="school-name"><?php echo esc_html($school_info['name']); ?></h1>
                <?php if (!empty($school_info['address'])): ?>
                <p class="school-address"><?php echo nl2br(esc_html($school_info['address'])); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="paper-title-section">
                <h2 class="paper-title"><?php echo esc_html($paper['title']); ?></h2>
                
                <?php if (!empty($paper['paper_code'])): ?>
                <div class="paper-code"><?php _e('Paper Code:', 'school-paper-generator'); ?> <strong><?php echo esc_html($paper['paper_code']); ?></strong></div>
                <?php endif; ?>
            </div>
            
            <div class="paper-meta">
                <div class="meta-item">
                    <span class="meta-label"><?php _e('Subject:', 'school-paper-generator'); ?></span>
                    <span class="meta-value"><?php echo esc_html($paper['subject']); ?></span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label"><?php _e('Class:', 'school-paper-generator'); ?></span>
                    <span class="meta-value"><?php echo esc_html($paper['class_level']); ?></span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label"><?php _e('Total Marks:', 'school-paper-generator'); ?></span>
                    <span class="meta-value"><?php echo esc_html($paper['total_marks']); ?></span>
                </div>
                
                <?php if (!empty($paper['time_duration'])): ?>
                <div class="meta-item">
                    <span class="meta-label"><?php _e('Time:', 'school-paper-generator'); ?></span>
                    <span class="meta-value"><?php echo esc_html($paper['time_duration']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Student Information Section (for student mode) -->
        <?php if ($is_student_mode): ?>
        <div class="student-info-section no-print">
            <h3><?php _e('Student Information', 'school-paper-generator'); ?></h3>
            <form class="student-info-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="student-name"><?php _e('Name:', 'school-paper-generator'); ?></label>
                        <input type="text" id="student-name" name="student_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student-roll"><?php _e('Roll Number:', 'school-paper-generator'); ?></label>
                        <input type="text" id="student-roll" name="student_roll" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student-class"><?php _e('Class:', 'school-paper-generator'); ?></label>
                        <input type="text" id="student-class" name="student_class" value="<?php echo esc_attr($paper['class_level']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="student-section"><?php _e('Section:', 'school-paper-generator'); ?></label>
                        <input type="text" id="student-section" name="student_section">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="button" id="start-exam-btn">
                        <?php _e('Start Exam', 'school-paper-generator'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <?php if (!empty($paper['instructions'])): ?>
        <section class="paper-instructions">
            <h3><?php _e('General Instructions', 'school-paper-generator'); ?></h3>
            <div class="instructions-content">
                <?php echo wpautop($paper['instructions']); ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Paper Questions -->
        <main class="paper-questions">
            <?php if (empty($questions)): ?>
            <div class="no-questions">
                <p><?php _e('No questions found in this paper.', 'school-paper-generator'); ?></p>
            </div>
            <?php else: ?>
            
            <!-- Group questions by section/type -->
            <?php 
            $grouped_questions = array();
            foreach ($questions as $question) {
                $section = $question['section'] ?? $question['type'];
                if (!isset($grouped_questions[$section])) {
                    $grouped_questions[$section] = array();
                }
                $grouped_questions[$section][] = $question;
            }
            
            $question_number = 1;
            ?>
            
            <?php foreach ($grouped_questions as $section_name => $section_questions): ?>
            <section class="question-section">
                <h3 class="section-title">
                    <?php echo esc_html(ucfirst($section_name)); ?> 
                    <?php _e('Questions', 'school-paper-generator'); ?>
                </h3>
                
                <?php foreach ($section_questions as $question): ?>
                <div class="question" data-id="<?php echo $question['id']; ?>" data-type="<?php echo $question['type']; ?>">
                    <div class="question-header">
                        <span class="question-number">Q<?php echo $question_number; ?>.</span>
                        <span class="question-marks">[<?php echo $question['marks']; ?> <?php _e('marks', 'school-paper-generator'); ?>]</span>
                    </div>
                    
                    <div class="question-content">
                        <div class="question-text">
                            <?php echo wpautop($question['text']); ?>
                        </div>
                        
                        <?php if ($question['type'] === 'mcq' && !empty($question['options'])): ?>
                        <div class="question-options">
                            <?php foreach ($question['options'] as $index => $option): 
                                $letter = chr(65 + $index); // A, B, C, D
                            ?>
                            <div class="option">
                                <input type="radio" 
                                       id="q<?php echo $question_number; ?>-opt<?php echo $index; ?>" 
                                       name="q<?php echo $question_number; ?>" 
                                       value="<?php echo esc_attr($option); ?>"
                                       class="option-input">
                                <label for="q<?php echo $question_number; ?>-opt<?php echo $index; ?>" class="option-label">
                                    <span class="option-letter"><?php echo $letter; ?>.</span>
                                    <span class="option-text"><?php echo esc_html($option); ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($question['type'] === 'true_false'): ?>
                        <div class="question-options true-false">
                            <div class="option">
                                <input type="radio" 
                                       id="q<?php echo $question_number; ?>-true" 
                                       name="q<?php echo $question_number; ?>" 
                                       value="True"
                                       class="option-input">
                                <label for="q<?php echo $question_number; ?>-true" class="option-label">
                                    <span class="option-text"><?php _e('True', 'school-paper-generator'); ?></span>
                                </label>
                            </div>
                            <div class="option">
                                <input type="radio" 
                                       id="q<?php echo $question_number; ?>-false" 
                                       name="q<?php echo $question_number; ?>" 
                                       value="False"
                                       class="option-input">
                                <label for="q<?php echo $question_number; ?>-false" class="option-label">
                                    <span class="option-text"><?php _e('False', 'school-paper-generator'); ?></span>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($question['type'], array('short', 'long'))): ?>
                        <div class="answer-area">
                            <?php if ($question['type'] === 'short'): ?>
                            <textarea class="short-answer" 
                                      name="q<?php echo $question_number; ?>_answer" 
                                      rows="3" 
                                      placeholder="<?php esc_attr_e('Write your answer here...', 'school-paper-generator'); ?>"></textarea>
                            <?php else: ?>
                            <textarea class="long-answer" 
                                      name="q<?php echo $question_number; ?>_answer" 
                                      rows="6" 
                                      placeholder="<?php esc_attr_e('Write your detailed answer here...', 'school-paper-generator'); ?>"></textarea>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Answer display (for teachers or when show_answers is true) -->
                    <?php if ($show_answers && !empty($question['correct_answer'])): ?>
                    <div class="correct-answer">
                        <strong><?php _e('Correct Answer:', 'school-paper-generator'); ?></strong>
                        <p><?php echo esc_html($question['correct_answer']); ?></p>
                        
                        <?php if (!empty($question['explanation'])): ?>
                        <div class="answer-explanation">
                            <strong><?php _e('Explanation:', 'school-paper-generator'); ?></strong>
                            <p><?php echo esc_html($question['explanation']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $question_number++;
                endforeach; ?>
            </section>
            <?php endforeach; ?>
            
            <?php endif; ?>
        </main>
        
        <!-- Answer Sheet for MCQ (student mode) -->
        <?php if ($is_student_mode && !empty($questions)): ?>
        <section class="answer-sheet no-print">
            <h3><?php _e('Answer Sheet', 'school-paper-generator'); ?></h3>
            <div class="answer-grid">
                <?php 
                $mcq_count = 0;
                foreach ($questions as $index => $question) {
                    if ($question['type'] === 'mcq' || $question['type'] === 'true_false') {
                        $mcq_count++;
                        echo '<div class="answer-item">';
                        echo '<span class="answer-number">' . ($index + 1) . '</span>';
                        
                        for ($i = 0; $i < 4; $i++) {
                            $letter = chr(65 + $i);
                            echo '<label class="answer-option">';
                            echo '<input type="radio" name="answer_' . ($index + 1) . '" value="' . $letter . '">';
                            echo '<span>' . $letter . '</span>';
                            echo '</label>';
                        }
                        
                        echo '</div>';
                    }
                }
                
                if ($mcq_count === 0) {
                    echo '<p>' . __('No multiple choice questions in this paper.', 'school-paper-generator') . '</p>';
                }
                ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Paper Footer -->
        <footer class="paper-footer">
            <div class="footer-content">
                <?php if (!$is_student_mode): ?>
                <div class="paper-info">
                    <p><?php _e('Generated by School Paper Generator', 'school-paper-generator'); ?></p>
                    <p><?php _e('Date:', 'school-paper-generator'); ?> <?php echo date_i18n(get_option('date_format')); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="paper-actions no-print">
                    <?php if (!$is_student_mode): ?>
                    <button type="button" class="button button-primary" id="print-paper">
                        <i class="fas fa-print"></i> <?php _e('Print Paper', 'school-paper-generator'); ?>
                    </button>
                    
                    <a href="<?php echo admin_url('admin-ajax.php?action=spg_export_paper&format=pdf&paper_id=' . $paper_id); ?>" 
                       class="button button-secondary" target="_blank">
                        <i class="fas fa-download"></i> <?php _e('Download PDF', 'school-paper-generator'); ?>
                    </a>
                    
                    <?php if (spg_is_premium_active()): ?>
                    <div class="dropdown">
                        <button type="button" class="button button-secondary dropdown-toggle">
                            <i class="fas fa-file-export"></i> <?php _e('Export', 'school-paper-generator'); ?>
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?php echo admin_url('admin-ajax.php?action=spg_export_paper&format=docx&paper_id=' . $paper_id); ?>" 
                               class="dropdown-item" target="_blank">
                                <i class="fas fa-file-word"></i> <?php _e('Word Document', 'school-paper-generator'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin-ajax.php?action=spg_export_paper&format=html&paper_id=' . $paper_id); ?>" 
                               class="dropdown-item" target="_blank">
                                <i class="fas fa-file-code"></i> <?php _e('HTML', 'school-paper-generator'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (current_user_can('manage_options')): ?>
                    <a href="<?php echo admin_url('admin.php?page=spg-create-paper&paper_id=' . $paper_id); ?>" 
                       class="button button-secondary">
                        <i class="fas fa-edit"></i> <?php _e('Edit Paper', 'school-paper-generator'); ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <button type="button" class="button button-primary" id="submit-exam">
                        <i class="fas fa-paper-plane"></i> <?php _e('Submit Exam', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="save-progress">
                        <i class="fas fa-save"></i> <?php _e('Save Progress', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button" id="reset-answers">
                        <i class="fas fa-redo"></i> <?php _e('Reset Answers', 'school-paper-generator'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Timer for student mode -->
    <?php if ($is_student_mode && !empty($paper['time_duration'])): ?>
    <div class="exam-timer no-print">
        <div class="timer-display">
            <span class="timer-label"><?php _e('Time Remaining:', 'school-paper-generator'); ?></span>
            <span class="timer-value" id="timer"><?php echo esc_html($paper['time_duration']); ?></span>
        </div>
        <div class="timer-progress">
            <div class="progress-bar" id="timer-progress"></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Success Modal (student mode) -->
    <?php if ($is_student_mode): ?>
    <div id="success-modal" class="spg-modal" style="display: none;">
        <div class="spg-modal-content">
            <div class="spg-modal-header">
                <h3><?php _e('Exam Submitted Successfully!', 'school-paper-generator'); ?></h3>
            </div>
            <div class="spg-modal-body">
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <p><?php _e('Your exam has been submitted successfully. Thank you for completing the test.', 'school-paper-generator'); ?></p>
                </div>
                
                <div class="exam-summary">
                    <h4><?php _e('Exam Summary', 'school-paper-generator'); ?></h4>
                    <div class="summary-stats">
                        <div class="stat">
                            <span class="stat-label"><?php _e('Total Questions:', 'school-paper-generator'); ?></span>
                            <span class="stat-value" id="total-questions-stat">0</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label"><?php _e('Answered:', 'school-paper-generator'); ?></span>
                            <span class="stat-value" id="answered-stat">0</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label"><?php _e('Time Taken:', 'school-paper-generator'); ?></span>
                            <span class="stat-value" id="time-taken-stat">0:00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="spg-modal-footer">
                <button type="button" class="button button-primary" id="close-exam">
                    <?php _e('Close', 'school-paper-generator'); ?>
                </button>
                <button type="button" class="button" id="print-result">
                    <i class="fas fa-print"></i> <?php _e('Print Result', 'school-paper-generator'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_print_mode): ?>
    <script src="<?php echo SPG_PLUGIN_URL . 'public/assets/js/print-paper.js'; ?>"></script>
    <script>
    jQuery(document).ready(function($) {
        // Print paper
        $('#print-paper').on('click', function() {
            window.print();
        });
        
        <?php if ($is_student_mode): ?>
        // Student mode functionality
        let examStarted = false;
        let startTime = null;
        let timerInterval = null;
        let totalSeconds = 0;
        
        // Calculate total seconds from time duration
        const timeDuration = '<?php echo $paper["time_duration"]; ?>';
        if (timeDuration) {
            const match = timeDuration.match(/(\d+)\s*hours?/i);
            if (match) {
                totalSeconds = parseInt(match[1]) * 3600;
            }
        }
        
        // Start exam button
        $('#start-exam-btn').on('click', function() {
            const studentName = $('#student-name').val().trim();
            const studentRoll = $('#student-roll').val().trim();
            
            if (!studentName || !studentRoll) {
                alert('Please fill in your name and roll number.');
                return;
            }
            
            examStarted = true;
            startTime = new Date();
            
            // Hide student info form
            $('.student-info-section').slideUp();
            
            // Show timer
            $('.exam-timer').show();
            
            // Start timer
            startTimer();
            
            // Enable form inputs
            $('.question input, .question textarea').prop('disabled', false);
        });
        
        // Start timer
        function startTimer() {
            if (totalSeconds <= 0) return;
            
            let remainingSeconds = totalSeconds;
            
            timerInterval = setInterval(function() {
                remainingSeconds--;
                
                // Update timer display
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                
                $('#timer').text(
                    (hours > 0 ? hours + ':' : '') +
                    (minutes < 10 ? '0' : '') + minutes + ':' +
                    (seconds < 10 ? '0' : '') + seconds
                );
                
                // Update progress bar
                const progress = ((totalSeconds - remainingSeconds) / totalSeconds) * 100;
                $('#timer-progress').css('width', progress + '%');
                
                // Change color when time is running out
                if (remainingSeconds < 300) { // 5 minutes
                    $('#timer').addClass('warning');
                    $('#timer-progress').addClass('warning');
                }
                
                if (remainingSeconds < 60) { // 1 minute
                    $('#timer').addClass('danger');
                    $('#timer-progress').addClass('danger');
                }
                
                // Time's up
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    alert('Time is up! Automatically submitting your exam...');
                    submitExam();
                }
            }, 1000);
        }
        
        // Submit exam
        $('#submit-exam').on('click', function() {
            if (!confirm('Are you sure you want to submit your exam? You cannot change answers after submission.')) {
                return;
            }
            
            submitExam();
        });
        
        function submitExam() {
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            // Calculate time taken
            const endTime = new Date();
            const timeTaken = Math.floor((endTime - startTime) / 1000);
            const timeTakenFormatted = formatTime(timeTaken);
            
            // Collect answers
            const answers = {};
            let answeredCount = 0;
            let totalQuestions = <?php echo count($questions); ?>;
            
            $('.question').each(function() {
                const questionId = $(this).data('id');
                const questionType = $(this).data('type');
                let answer = '';
                
                if (questionType === 'mcq' || questionType === 'true_false') {
                    const selected = $(this).find('input[type="radio"]:checked');
                    if (selected.length > 0) {
                        answer = selected.val();
                        answeredCount++;
                    }
                } else if (questionType === 'short' || questionType === 'long') {
                    answer = $(this).find('textarea').val().trim();
                    if (answer) {
                        answeredCount++;
                    }
                }
                
                answers[questionId] = answer;
            });
            
            // Show success modal
            $('#total-questions-stat').text(totalQuestions);
            $('#answered-stat').text(answeredCount);
            $('#time-taken-stat').text(timeTakenFormatted);
            $('#success-modal').show();
            
            // Save results (you would typically send this to server)
            const examData = {
                student_name: $('#student-name').val(),
                student_roll: $('#student-roll').val(),
                paper_id: <?php echo $paper_id; ?>,
                start_time: startTime.toISOString(),
                end_time: endTime.toISOString(),
                time_taken: timeTaken,
                answers: answers,
                total_questions: totalQuestions,
                answered: answeredCount
            };
            
            // Here you would typically send examData to server via AJAX
            console.log('Exam submitted:', examData);
        }
        
        // Save progress
        $('#save-progress').on('click', function() {
            const answers = {};
            
            $('.question').each(function() {
                const questionId = $(this).data('id');
                const questionType = $(this).data('type');
                let answer = '';
                
                if (questionType === 'mcq' || questionType === 'true_false') {
                    const selected = $(this).find('input[type="radio"]:checked');
                    if (selected.length > 0) {
                        answer = selected.val();
                    }
                } else if (questionType === 'short' || questionType === 'long') {
                    answer = $(this).find('textarea').val().trim();
                }
                
                if (answer) {
                    answers[questionId] = answer;
                }
            });
            
            // Save to localStorage
            localStorage.setItem('spg_exam_<?php echo $paper_id; ?>', JSON.stringify(answers));
            
            // Show saved notification
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.html('<i class="fas fa-check"></i> Saved');
            setTimeout(() => {
                $btn.html(originalText);
            }, 2000);
        });
        
        // Reset answers
        $('#reset-answers').on('click', function() {
            if (!confirm('Are you sure you want to reset all your answers?')) {
                return;
            }
            
            $('.question input:checked').prop('checked', false);
            $('.question textarea').val('');
            
            // Clear localStorage
            localStorage.removeItem('spg_exam_<?php echo $paper_id; ?>');
        });
        
        // Load saved progress
        function loadSavedProgress() {
            const saved = localStorage.getItem('spg_exam_<?php echo $paper_id; ?>');
            if (saved) {
                try {
                    const answers = JSON.parse(saved);
                    
                    $('.question').each(function() {
                        const questionId = $(this).data('id');
                        if (answers[questionId]) {
                            const questionType = $(this).data('type');
                            const answer = answers[questionId];
                            
                            if (questionType === 'mcq' || questionType === 'true_false') {
                                $(this).find('input[type="radio"][value="' + answer + '"]').prop('checked', true);
                            } else if (questionType === 'short' || questionType === 'long') {
                                $(this).find('textarea').val(answer);
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error loading saved progress:', e);
                }
            }
        }
        
        // Close exam modal
        $('#close-exam').on('click', function() {
            $('#success-modal').hide();
            location.reload();
        });
        
        // Print result
        $('#print-result').on('click', function() {
            window.print();
        });
        
        // Helper function to format time
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            if (hours > 0) {
                return hours + ':' + (minutes < 10 ? '0' : '') + minutes + ':' + (secs < 10 ? '0' : '') + secs;
            } else {
                return minutes + ':' + (secs < 10 ? '0' : '') + secs;
            }
        }
        
        // Load saved progress on page load
        loadSavedProgress();
        <?php endif; ?>
        
        // Modal close functionality
        $('.spg-modal-close, .button[data-dismiss="modal"]').on('click', function() {
            $(this).closest('.spg-modal').hide();
        });
        
        // Close modal when clicking outside
        $(document).on('click', function(e) {
            if ($(e.target).hasClass('spg-modal')) {
                $(e.target).hide();
            }
        });
        
        // Dropdown menu
        $('.dropdown-toggle').on('click', function(e) {
            e.stopPropagation();
            $(this).siblings('.dropdown-menu').toggle();
        });
        
        $(document).on('click', function() {
            $('.dropdown-menu').hide();
        });
    });
    </script>
    <?php endif; ?>
    
    <?php wp_footer(); ?>
</body>
</html>