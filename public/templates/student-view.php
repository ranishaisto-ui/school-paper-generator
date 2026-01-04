<?php
if (!defined('ABSPATH')) exit;

// Only allow logged-in users
if (!is_user_logged_in()) {
    echo '<div class="spg-login-required">';
    echo '<h3>' . __('Login Required', 'school-paper-generator') . '</h3>';
    echo '<p>' . __('Please login to access the question bank.', 'school-paper-generator') . '</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="button button-primary">' . __('Login', 'school-paper-generator') . '</a>';
    echo '</div>';
    return;
}

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_name = $current_user->display_name;

// Get question bank
$question_bank = SPG_Question_Bank::get_instance();
$subjects = spg_get_subjects();
$class_levels = spg_get_class_levels();

// Get filters
$current_subject = !empty($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
$current_class = !empty($_GET['class']) ? sanitize_text_field($_GET['class']) : '';
$current_type = !empty($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$current_difficulty = !empty($_GET['difficulty']) ? sanitize_text_field($_GET['difficulty']) : '';
$current_search = !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get questions
$filters = array();
if ($current_subject) $filters['subject'] = $current_subject;
if ($current_class) $filters['class_level'] = $current_class;
if ($current_type) $filters['question_type'] = $current_type;
if ($current_difficulty) $filters['difficulty'] = $current_difficulty;
if ($current_search) $filters['search'] = $current_search;

$questions = $question_bank->get_filtered_questions($filters);
?>

<div class="spg-student-view">
    <div class="student-header">
        <div class="welcome-message">
            <h1><i class="fas fa-graduation-cap"></i> <?php _e('Question Bank', 'school-paper-generator'); ?></h1>
            <p class="welcome-text"><?php printf(__('Welcome, %s! Practice questions and improve your knowledge.', 'school-paper-generator'), $user_name); ?></p>
        </div>
        
        <div class="student-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format(count($questions)); ?></h3>
                    <p><?php _e('Questions Available', 'school-paper-generator'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 id="answered-count">0</h3>
                    <p><?php _e('Questions Answered', 'school-paper-generator'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3 id="correct-count">0</h3>
                    <p><?php _e('Correct Answers', 'school-paper-generator'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="student-filters">
        <form id="student-filter-form" method="get">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="student-subject"><?php _e('Subject:', 'school-paper-generator'); ?></label>
                    <select id="student-subject" name="subject">
                        <option value=""><?php _e('All Subjects', 'school-paper-generator'); ?></option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo esc_attr($subject); ?>" <?php selected($current_subject, $subject); ?>>
                            <?php echo esc_html($subject); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="student-class"><?php _e('Class:', 'school-paper-generator'); ?></label>
                    <select id="student-class" name="class">
                        <option value=""><?php _e('All Classes', 'school-paper-generator'); ?></option>
                        <?php foreach ($class_levels as $class): ?>
                        <option value="<?php echo esc_attr($class); ?>" <?php selected($current_class, $class); ?>>
                            <?php echo esc_html($class); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="student-type"><?php _e('Type:', 'school-paper-generator'); ?></label>
                    <select id="student-type" name="type">
                        <option value=""><?php _e('All Types', 'school-paper-generator'); ?></option>
                        <option value="mcq" <?php selected($current_type, 'mcq'); ?>><?php _e('Multiple Choice', 'school-paper-generator'); ?></option>
                        <option value="short" <?php selected($current_type, 'short'); ?>><?php _e('Short Answer', 'school-paper-generator'); ?></option>
                        <option value="long" <?php selected($current_type, 'long'); ?>><?php _e('Long Answer', 'school-paper-generator'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="student-difficulty"><?php _e('Difficulty:', 'school-paper-generator'); ?></label>
                    <select id="student-difficulty" name="difficulty">
                        <option value=""><?php _e('All Levels', 'school-paper-generator'); ?></option>
                        <option value="easy" <?php selected($current_difficulty, 'easy'); ?>><?php _e('Easy', 'school-paper-generator'); ?></option>
                        <option value="medium" <?php selected($current_difficulty, 'medium'); ?>><?php _e('Medium', 'school-paper-generator'); ?></option>
                        <option value="hard" <?php selected($current_difficulty, 'hard'); ?>><?php _e('Hard', 'school-paper-generator'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group search-group">
                    <label for="student-search"><?php _e('Search:', 'school-paper-generator'); ?></label>
                    <input type="text" id="student-search" name="search" 
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
                    <a href="?" class="button">
                        <?php _e('Clear Filters', 'school-paper-generator'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="student-actions">
        <div class="practice-options">
            <button type="button" class="button button-primary" id="start-practice">
                <i class="fas fa-play-circle"></i> <?php _e('Start Practice Session', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button" id="take-test">
                <i class="fas fa-clipboard-check"></i> <?php _e('Take a Test', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button" id="review-answers">
                <i class="fas fa-history"></i> <?php _e('Review Answers', 'school-paper-generator'); ?>
            </button>
        </div>
        
        <div class="practice-settings">
            <div class="setting-group">
                <label for="questions-per-session">
                    <?php _e('Questions per session:', 'school-paper-generator'); ?>
                </label>
                <select id="questions-per-session">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="shuffle-questions" checked>
                    <?php _e('Shuffle questions', 'school-paper-generator'); ?>
                </label>
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" id="show-answers" checked>
                    <?php _e('Show answers immediately', 'school-paper-generator'); ?>
                </label>
            </div>
        </div>
    </div>
    
    <div class="questions-container">
        <?php if (empty($questions)): ?>
        <div class="no-questions">
            <i class="fas fa-search"></i>
            <h3><?php _e('No Questions Found', 'school-paper-generator'); ?></h3>
            <p><?php _e('Try changing your filters or search terms.', 'school-paper-generator'); ?></p>
        </div>
        <?php else: ?>
        
        <div class="questions-grid" id="questions-grid">
            <?php foreach ($questions as $question): ?>
            <div class="question-card" data-id="<?php echo $question['id']; ?>" data-type="<?php echo $question['question_type']; ?>">
                <div class="question-header">
                    <span class="question-type"><?php echo spg_get_question_type_label($question['question_type']); ?></span>
                    <span class="question-marks"><?php echo $question['marks']; ?> <?php _e('marks', 'school-paper-generator'); ?></span>
                </div>
                
                <div class="question-content">
                    <div class="question-text">
                        <?php echo esc_html(wp_trim_words($question['question_text'], 30, '...')); ?>
                    </div>
                    
                    <div class="question-meta">
                        <span class="meta-item">
                            <i class="fas fa-book"></i> <?php echo esc_html($question['subject']); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-graduation-cap"></i> <?php echo esc_html($question['class_level']); ?>
                        </span>
                        <span class="meta-item difficulty-<?php echo $question['difficulty']; ?>">
                            <i class="fas fa-chart-line"></i> <?php echo spg_get_difficulty_label($question['difficulty']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="question-actions">
                    <button type="button" class="button button-small practice-question">
                        <i class="fas fa-play"></i> <?php _e('Practice', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button button-small view-question">
                        <i class="fas fa-eye"></i> <?php _e('View', 'school-paper-generator'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<!-- Practice Modal -->
<div id="practice-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content practice-modal">
        <div class="spg-modal-header">
            <h3><?php _e('Practice Question', 'school-paper-generator'); ?></h3>
            <div class="practice-stats">
                <span class="stat" id="current-question">1/10</span>
                <span class="stat" id="score">Score: 0</span>
                <span class="stat" id="time-spent">Time: 0:00</span>
            </div>
        </div>
        <div class="spg-modal-body">
            <div class="practice-question-container" id="practice-question-container">
                <!-- Question content will be loaded here -->
            </div>
            
            <div class="practice-answer-container" id="practice-answer-container" style="display: none;">
                <!-- Answer content will be loaded here -->
            </div>
        </div>
        <div class="spg-modal-footer">
            <button type="button" class="button button-secondary" id="prev-question">
                <i class="fas fa-arrow-left"></i> <?php _e('Previous', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="check-answer">
                <i class="fas fa-check"></i> <?php _e('Check Answer', 'school-paper-generator'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="next-question">
                <?php _e('Next', 'school-paper-generator'); ?> <i class="fas fa-arrow-right"></i>
            </button>
            
            <button type="button" class="button button-danger" id="end-practice">
                <i class="fas fa-stop"></i> <?php _e('End Practice', 'school-paper-generator'); ?>
            </button>
        </div>
    </div>
</div>

<!-- View Question Modal -->
<div id="view-question-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Question Details', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body" id="view-question-content">
            <!-- Question details will be loaded here -->
        </div>
    </div>
</div>

<!-- Test Settings Modal -->
<div id="test-settings-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Test Settings', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <form id="test-settings-form">
                <div class="form-group">
                    <label for="test-questions-count"><?php _e('Number of Questions', 'school-paper-generator'); ?></label>
                    <select id="test-questions-count" name="questions_count" required>
                        <option value="10">10 Questions</option>
                        <option value="20" selected>20 Questions</option>
                        <option value="30">30 Questions</option>
                        <option value="50">50 Questions</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="test-time-limit"><?php _e('Time Limit', 'school-paper-generator'); ?></label>
                    <select id="test-time-limit" name="time_limit">
                        <option value="0">No time limit</option>
                        <option value="30">30 minutes</option>
                        <option value="60" selected>1 hour</option>
                        <option value="90">1.5 hours</option>
                        <option value="120">2 hours</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="shuffle_questions" value="1" checked>
                        <?php _e('Shuffle questions', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="shuffle_options" value="1">
                        <?php _e('Shuffle MCQ options', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="show_progress" value="1" checked>
                        <?php _e('Show progress during test', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-play"></i> <?php _e('Start Test', 'school-paper-generator'); ?>
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
    // Student view functionality
    const SPG_StudentView = {
        currentQuestions: [],
        currentQuestionIndex: 0,
        practiceQuestions: [],
        userAnswers: {},
        score: 0,
        startTime: null,
        timerInterval: null,
        
        init: function() {
            this.bindEvents();
            this.loadUserStats();
        },
        
        bindEvents: function() {
            // Start practice
            $('#start-practice').on('click', this.startPractice);
            $('#take-test').on('click', this.openTestSettings);
            $('#review-answers').on('click', this.reviewAnswers);
            
            // Practice modal
            $('.practice-question').on('click', this.openPracticeModal);
            $('.view-question').on('click', this.openViewModal);
            
            // Practice controls
            $('#check-answer').on('click', this.checkAnswer);
            $('#next-question').on('click', this.nextQuestion);
            $('#prev-question').on('click', this.prevQuestion);
            $('#end-practice').on('click', this.endPractice);
            
            // Modals
            $('.spg-modal-close').on('click', this.closeModal);
            $('#test-settings-form').on('submit', this.startTest);
        },
        
        loadUserStats: function() {
            // Load user statistics from localStorage or server
            const stats = localStorage.getItem('spg_user_stats_' + <?php echo $user_id; ?>) || '{}';
            const data = JSON.parse(stats);
            
            let answered = 0;
            let correct = 0;
            
            if (data.questions) {
                answered = Object.keys(data.questions).length;
                correct = Object.values(data.questions).filter(answer => answer.correct).length;
            }
            
            $('#answered-count').text(answered);
            $('#correct-count').text(correct);
        },
        
        startPractice: function() {
            const questionCount = $('#questions-per-session').val();
            const shuffle = $('#shuffle-questions').is(':checked');
            
            // Get filtered questions
            const filters = SPG_StudentView.getCurrentFilters();
            filters.limit = parseInt(questionCount);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_practice_questions',
                    nonce: spg_ajax.nonce,
                    filters: filters,
                    shuffle: shuffle
                },
                success: function(response) {
                    if (response.success) {
                        SPG_StudentView.practiceQuestions = response.data.questions;
                        SPG_StudentView.startPracticeSession();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        getCurrentFilters: function() {
            return {
                subject: $('#student-subject').val(),
                class_level: $('#student-class').val(),
                question_type: $('#student-type').val(),
                difficulty: $('#student-difficulty').val(),
                search: $('#student-search').val()
            };
        },
        
        startPracticeSession: function() {
            if (SPG_StudentView.practiceQuestions.length === 0) {
                alert('No questions found for practice.');
                return;
            }
            
            SPG_StudentView.currentQuestionIndex = 0;
            SPG_StudentView.score = 0;
            SPG_StudentView.userAnswers = {};
            SPG_StudentView.startTime = new Date();
            
            // Start timer
            SPG_StudentView.startTimer();
            
            // Show practice modal
            $('#practice-modal').show();
            
            // Load first question
            SPG_StudentView.loadPracticeQuestion();
        },
        
        startTimer: function() {
            let seconds = 0;
            
            SPG_StudentView.timerInterval = setInterval(function() {
                seconds++;
                const minutes = Math.floor(seconds / 60);
                const secs = seconds % 60;
                $('#time-spent').text('Time: ' + minutes + ':' + (secs < 10 ? '0' : '') + secs);
            }, 1000);
        },
        
        loadPracticeQuestion: function() {
            if (SPG_StudentView.currentQuestionIndex >= SPG_StudentView.practiceQuestions.length) {
                SPG_StudentView.endPractice();
                return;
            }
            
            const question = SPG_StudentView.practiceQuestions[SPG_StudentView.currentQuestionIndex];
            const showAnswers = $('#show-answers').is(':checked');
            
            // Update stats
            $('#current-question').text((SPG_StudentView.currentQuestionIndex + 1) + '/' + SPG_StudentView.practiceQuestions.length);
            $('#score').text('Score: ' + SPG_StudentView.score);
            
            // Load question HTML
            SPG_StudentView.loadQuestionHTML(question, 'practice', showAnswers);
            
            // Show/hide buttons
            $('#prev-question').toggle(SPG_StudentView.currentQuestionIndex > 0);
            $('#check-answer').show();
            $('#next-question').hide();
            $('#practice-answer-container').hide();
            $('#practice-question-container').show();
        },
        
        loadQuestionHTML: function(question, mode, showAnswers = false) {
            let html = '<div class="practice-question-view" data-id="' + question.id + '">';
            
            html += '<div class="question-header">';
            html += '<span class="question-type">' + question.type_label + '</span>';
            html += '<span class="question-marks">' + question.marks + ' marks</span>';
            html += '<span class="question-difficulty">' + question.difficulty_label + '</span>';
            html += '</div>';
            
            html += '<div class="question-text">' + question.question_text + '</div>';
            
            if (question.question_type === 'mcq' && question.options) {
                html += '<div class="question-options">';
                question.options.forEach(function(option, index) {
                    const letter = String.fromCharCode(65 + index);
                    html += '<div class="option">';
                    html += '<input type="radio" id="opt-' + index + '" name="answer" value="' + option + '">';
                    html += '<label for="opt-' + index + '">';
                    html += '<span class="option-letter">' + letter + '.</span>';
                    html += '<span class="option-text">' + option + '</span>';
                    html += '</label>';
                    html += '</div>';
                });
                html += '</div>';
            }
            
            if (question.question_type === 'true_false') {
                html += '<div class="question-options">';
                html += '<div class="option"><input type="radio" id="true" name="answer" value="True"><label for="true">True</label></div>';
                html += '<div class="option"><input type="radio" id="false" name="answer" value="False"><label for="false">False</label></div>';
                html += '</div>';
            }
            
            if (question.question_type === 'short' || question.question_type === 'long') {
                const rows = question.question_type === 'short' ? 3 : 6;
                html += '<div class="answer-area">';
                html += '<textarea class="answer-input" rows="' + rows + '" placeholder="Write your answer here..."></textarea>';
                html += '</div>';
            }
            
            html += '</div>';
            
            if (mode === 'practice') {
                $('#practice-question-container').html(html);
                
                if (showAnswers && question.correct_answer) {
                    let answerHtml = '<div class="correct-answer">';
                    answerHtml += '<h4>Correct Answer:</h4>';
                    answerHtml += '<p>' + question.correct_answer + '</p>';
                    
                    if (question.explanation) {
                        answerHtml += '<div class="explanation">';
                        answerHtml += '<h4>Explanation:</h4>';
                        answerHtml += '<p>' + question.explanation + '</p>';
                        answerHtml += '</div>';
                    }
                    
                    answerHtml += '</div>';
                    
                    $('#practice-answer-container').html(answerHtml);
                }
            } else if (mode === 'view') {
                $('#view-question-content').html(html);
            }
        },
        
        checkAnswer: function() {
            const question = SPG_StudentView.practiceQuestions[SPG_StudentView.currentQuestionIndex];
            const $container = $('#practice-question-container');
            let userAnswer = '';
            
            // Get user's answer
            if (question.question_type === 'mcq' || question.question_type === 'true_false') {
                const selected = $container.find('input[name="answer"]:checked');
                if (selected.length === 0) {
                    alert('Please select an answer first.');
                    return;
                }
                userAnswer = selected.val();
            } else {
                userAnswer = $container.find('.answer-input').val().trim();
                if (!userAnswer) {
                    alert('Please write your answer first.');
                    return;
                }
            }
            
            // Check if answer is correct
            const isCorrect = (userAnswer === question.correct_answer);
            
            // Update score
            if (isCorrect) {
                SPG_StudentView.score += parseInt(question.marks);
            }
            
            // Save user answer
            SPG_StudentView.userAnswers[question.id] = {
                answer: userAnswer,
                correct: isCorrect,
                timestamp: new Date().toISOString()
            };
            
            // Save to localStorage
            SPG_StudentView.saveUserAnswer(question.id, userAnswer, isCorrect);
            
            // Show answer feedback
            let feedbackHtml = '<div class="answer-feedback ' + (isCorrect ? 'correct' : 'incorrect') + '">';
            feedbackHtml += '<h4>' + (isCorrect ? 'Correct!' : 'Incorrect') + '</h4>';
            
            if (!isCorrect && question.correct_answer) {
                feedbackHtml += '<p><strong>Correct Answer:</strong> ' + question.correct_answer + '</p>';
            }
            
            if (question.explanation) {
                feedbackHtml += '<div class="explanation">';
                feedbackHtml += '<p><strong>Explanation:</strong> ' + question.explanation + '</p>';
                feedbackHtml += '</div>';
            }
            
            feedbackHtml += '</div>';
            
            $('#practice-answer-container').html(feedbackHtml);
            $('#practice-answer-container').show();
            
            // Update UI
            $('#check-answer').hide();
            $('#next-question').show();
            $('#score').text('Score: ' + SPG_StudentView.score);
        },
        
        saveUserAnswer: function(questionId, answer, isCorrect) {
            // Load existing stats
            const stats = localStorage.getItem('spg_user_stats_' + <?php echo $user_id; ?>) || '{}';
            const data = JSON.parse(stats);
            
            if (!data.questions) {
                data.questions = {};
            }
            
            // Save answer
            data.questions[questionId] = {
                answer: answer,
                correct: isCorrect,
                timestamp: new Date().toISOString()
            };
            
            // Update totals
            data.total_answered = Object.keys(data.questions).length;
            data.total_correct = Object.values(data.questions).filter(q => q.correct).length;
            data.last_practice = new Date().toISOString();
            
            // Save back to localStorage
            localStorage.setItem('spg_user_stats_' + <?php echo $user_id; ?>, JSON.stringify(data));
            
            // Update UI
            $('#answered-count').text(data.total_answered);
            $('#correct-count').text(data.total_correct);
        },
        
        nextQuestion: function() {
            SPG_StudentView.currentQuestionIndex++;
            SPG_StudentView.loadPracticeQuestion();
        },
        
        prevQuestion: function() {
            if (SPG_StudentView.currentQuestionIndex > 0) {
                SPG_StudentView.currentQuestionIndex--;
                SPG_StudentView.loadPracticeQuestion();
            }
        },
        
        endPractice: function() {
            if (SPG_StudentView.timerInterval) {
                clearInterval(SPG_StudentView.timerInterval);
            }
            
            // Calculate results
            const totalQuestions = SPG_StudentView.practiceQuestions.length;
            const totalMarks = SPG_StudentView.practiceQuestions.reduce((sum, q) => sum + parseInt(q.marks), 0);
            const percentage = totalMarks > 0 ? Math.round((SPG_StudentView.score / totalMarks) * 100) : 0;
            
            // Show results
            let resultsHtml = '<div class="practice-results">';
            resultsHtml += '<h3>Practice Session Complete!</h3>';
            resultsHtml += '<div class="results-stats">';
            resultsHtml += '<div class="stat"><span class="label">Questions:</span> <span class="value">' + totalQuestions + '</span></div>';
            resultsHtml += '<div class="stat"><span class="label">Score:</span> <span class="value">' + SPG_StudentView.score + '/' + totalMarks + '</span></div>';
            resultsHtml += '<div class="stat"><span class="label">Percentage:</span> <span class="value">' + percentage + '%</span></div>';
            resultsHtml += '</div>';
            
            // Show question-by-question results
            resultsHtml += '<div class="detailed-results">';
            resultsHtml += '<h4>Detailed Results:</h4>';
            
            SPG_StudentView.practiceQuestions.forEach(function(question, index) {
                const userAnswer = SPG_StudentView.userAnswers[question.id];
                const isCorrect = userAnswer ? userAnswer.correct : false;
                
                resultsHtml += '<div class="result-item ' + (isCorrect ? 'correct' : 'incorrect') + '">';
                resultsHtml += '<span class="question-num">Q' + (index + 1) + ':</span>';
                resultsHtml += '<span class="question-text">' + question.question_text.substring(0, 50) + '...</span>';
                resultsHtml += '<span class="result-status">' + (isCorrect ? '?' : '?') + '</span>';
                resultsHtml += '</div>';
            });
            
            resultsHtml += '</div>';
            resultsHtml += '</div>';
            
            $('#practice-question-container').html(resultsHtml);
            $('#practice-answer-container').hide();
            $('#check-answer').hide();
            $('#next-question').hide();
            $('#prev-question').hide();
        },
        
        openPracticeModal: function() {
            const questionId = $(this).closest('.question-card').data('id');
            
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
                        SPG_StudentView.loadQuestionHTML(response.data, 'practice', true);
                        $('#practice-modal').show();
                        
                        // Set up single question practice
                        SPG_StudentView.practiceQuestions = [response.data];
                        SPG_StudentView.currentQuestionIndex = 0;
                        SPG_StudentView.score = 0;
                        
                        // Update UI
                        $('#current-question').text('1/1');
                        $('#score').text('Score: 0');
                        $('#prev-question').hide();
                        $('#next-question').hide();
                        $('#end-practice').show().text('Close');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        openViewModal: function() {
            const questionId = $(this).closest('.question-card').data('id');
            
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
                        SPG_StudentView.loadQuestionHTML(response.data, 'view');
                        $('#view-question-modal').show();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        openTestSettings: function() {
            $('#test-settings-modal').show();
        },
        
        startTest: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const settings = {};
            
            formData.forEach(function(item) {
                settings[item.name] = item.value;
            });
            
            // Get questions for test
            const filters = SPG_StudentView.getCurrentFilters();
            filters.limit = parseInt(settings.questions_count);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_practice_questions',
                    nonce: spg_ajax.nonce,
                    filters: filters,
                    shuffle: settings.shuffle_questions === '1'
                },
                success: function(response) {
                    if (response.success) {
                        $('#test-settings-modal').hide();
                        
                        // Start test
                        SPG_StudentView.startTestSession(response.data.questions, settings);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        startTestSession: function(questions, settings) {
            // Redirect to test page or show test interface
            const testUrl = '?spg_test=1&questions=' + questions.map(q => q.id).join(',') + 
                          '&time=' + settings.time_limit + 
                          '&shuffle_options=' + (settings.shuffle_options === '1' ? 1 : 0);
            
            window.location.href = testUrl;
        },
        
        reviewAnswers: function() {
            // Load user's answer history
            const stats = localStorage.getItem('spg_user_stats_' + <?php echo $user_id; ?>) || '{}';
            const data = JSON.parse(stats);
            
            if (!data.questions || Object.keys(data.questions).length === 0) {
                alert('You haven\'t answered any questions yet.');
                return;
            }
            
            // Show review interface
            // This would typically show a list of answered questions with results
            alert('Review feature coming soon!');
        },
        
        closeModal: function() {
            $(this).closest('.spg-modal').hide();
        }
    };
    
    // Initialize student view
    SPG_StudentView.init();
});
</script>

<style>
.spg-student-view {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.student-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
}

.welcome-message h1 {
    margin: 0 0 10px 0;
    font-size: 2em;
    display: flex;
    align-items: center;
    gap: 15px;
}

.welcome-text {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.student-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.stat-card {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    backdrop-filter: blur(10px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 20px;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.8em;
}

.stat-content p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.9em;
}

.student-filters {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-group {
    flex: 2;
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.search-group input {
    flex: 1;
}

.student-actions {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.practice-options {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.practice-settings {
    display: flex;
    gap: 30px;
    align-items: center;
    flex-wrap: wrap;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.setting-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.setting-group select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.questions-container {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.questions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.question-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.question-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.question-type {
    font-size: 0.8em;
    font-weight: bold;
    padding: 4px 10px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 12px;
}

.question-marks {
    font-size: 0.9em;
    font-weight: bold;
    color: #666;
}

.question-content {
    flex: 1;
    margin-bottom: 15px;
}

.question-text {
    line-height: 1.5;
    margin-bottom: 15px;
    color: #333;
}

.question-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.85em;
    color: #666;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-item i {
    color: #999;
}

.difficulty-easy { color: #4CAF50; }
.difficulty-medium { color: #FF9800; }
.difficulty-hard { color: #f44336; }

.question-actions {
    display: flex;
    gap: 10px;
}

.no-questions {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-questions i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.no-questions h3 {
    margin: 0 0 10px 0;
    color: #666;
}

/* Practice modal styles */
.practice-modal {
    max-width: 800px;
    width: 90%;
}

.practice-stats {
    display: flex;
    gap: 20px;
    align-items: center;
}

.practice-stats .stat {
    padding: 5px 10px;
    background: #f5f5f5;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: 500;
}

.practice-question-view {
    padding: 20px;
}

.practice-question-view .question-header {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.practice-question-view .question-text {
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 25px;
}

.question-options {
    margin: 20px 0;
}

.option {
    margin: 10px 0;
    padding: 12px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.option:hover {
    background: #f8f9fa;
    border-color: #667eea;
}

.option input[type="radio"] {
    display: none;
}

.option label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    width: 100%;
}

.option-letter {
    font-weight: bold;
    color: #667eea;
    min-width: 20px;
}

.answer-area {
    margin: 20px 0;
}

.answer-input {
    width: 100%;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.5;
    resize: vertical;
    min-height: 100px;
}

.answer-feedback {
    padding: 20px;
    border-radius: 6px;
    margin: 20px 0;
}

.answer-feedback.correct {
    background: #e8f5e8;
    border: 1px solid #4CAF50;
}

.answer-feedback.incorrect {
    background: #ffebee;
    border: 1px solid #f44336;
}

.answer-feedback h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.explanation {
    margin-top: 15px;
    padding: 15px;
    background: rgba(255,255,255,0.5);
    border-radius: 4px;
}

.practice-results {
    text-align: center;
    padding: 30px;
}

.results-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.results-stats .stat {
    text-align: center;
}

.results-stats .label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.results-stats .value {
    display: block;
    font-size: 1.8em;
    font-weight: bold;
    color: #333;
}

.detailed-results {
    margin-top: 30px;
    text-align: left;
}

.detailed-results h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.result-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    margin-bottom: 10px;
    border-radius: 6px;
}

.result-item.correct {
    background: #e8f5e8;
}

.result-item.incorrect {
    background: #ffebee;
}

.question-num {
    font-weight: bold;
    min-width: 40px;
}

.question-text {
    flex: 1;
    font-size: 0.9em;
    color: #666;
}

.result-status {
    font-weight: bold;
    font-size: 1.2em;
}

.result-item.correct .result-status {
    color: #4CAF50;
}

.result-item.incorrect .result-status {
    color: #f44336;
}

/* Test settings modal */
#test-settings-form .form-group {
    margin-bottom: 20px;
}

#test-settings-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

#test-settings-form select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .student-stats {
        grid-template-columns: 1fr;
    }
    
    .questions-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .practice-stats {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .practice-options,
    .practice-settings {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>