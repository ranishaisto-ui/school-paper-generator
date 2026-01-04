/**
 * School Paper Generator - Print Paper JavaScript
 * Handles printing and paper display functionality
 */

(function($) {
    'use strict';
    
    // Print Paper Object
    const SPG_PrintPaper = {
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.setupPrintStyles();
            this.loadUserPreferences();
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Print button
            $(document).on('click', '#print-paper, .print-paper-btn', this.printPaper);
            
            // Print specific section
            $(document).on('click', '.print-section-btn', this.printSection);
            
            // Toggle sections
            $(document).on('click', '.toggle-section-btn', this.toggleSection);
            
            // Toggle answers
            $(document).on('click', '.toggle-answers-btn', this.toggleAnswers);
            
            // Adjust font size
            $(document).on('click', '.font-size-btn', this.adjustFontSize);
            
            // Dark mode toggle
            $(document).on('click', '.dark-mode-toggle', this.toggleDarkMode);
            
            // Highlight questions
            $(document).on('click', '.highlight-question', this.highlightQuestion);
            
            // Timer functionality for exams
            if ($('#exam-timer').length) {
                this.startExamTimer();
            }
            
            // Save paper progress
            $(document).on('click', '#save-progress', this.saveProgress);
            
            // Load saved progress
            $(document).on('click', '#load-progress', this.loadProgress);
            
            // Clear all answers
            $(document).on('click', '#clear-answers', this.clearAnswers);
            
            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboardShortcuts);
            
            // Before print event
            $(window).on('beforeprint', this.beforePrint);
            
            // After print event
            $(window).on('afterprint', this.afterPrint);
        },
        
        // Setup print-specific styles
        setupPrintStyles: function() {
            // Add print styles dynamically
            const printStyles = `
                @media print {
                    .no-print,
                    .paper-actions,
                    .exam-timer,
                    .student-info-section,
                    .answer-sheet {
                        display: none !important;
                    }
                    
                    body {
                        font-size: 12pt !important;
                        line-height: 1.4 !important;
                        background: white !important;
                        color: black !important;
                    }
                    
                    .spg-paper-container {
                        width: 100% !important;
                        max-width: none !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        box-shadow: none !important;
                    }
                    
                    .paper-header {
                        text-align: left !important;
                        border-bottom: 2px solid #000 !important;
                        padding-bottom: 10px !important;
                        margin-bottom: 20px !important;
                    }
                    
                    .school-logo img {
                        max-height: 60px !important;
                    }
                    
                    .paper-title {
                        font-size: 16pt !important;
                    }
                    
                    .question {
                        page-break-inside: avoid !important;
                        break-inside: avoid !important;
                        margin-bottom: 20px !important;
                    }
                    
                    .question-options input[type="radio"],
                    .question-options input[type="checkbox"] {
                        display: none !important;
                    }
                    
                    .option-label {
                        display: block !important;
                        margin-left: 0 !important;
                    }
                    
                    .option-letter {
                        font-weight: bold !important;
                        margin-right: 8px !important;
                    }
                    
                    textarea {
                        border: 1px solid #ccc !important;
                        background: white !important;
                        min-height: 80px !important;
                    }
                    
                    .correct-answer {
                        border-left: 3px solid #4CAF50 !important;
                        background: #f8fff8 !important;
                        padding: 10px !important;
                        margin-top: 10px !important;
                    }
                    
                    .print-only {
                        display: block !important;
                    }
                    
                    /* Page breaks */
                    .page-break {
                        page-break-before: always !important;
                    }
                    
                    /* Footer for page numbers */
                    @page {
                        margin: 2cm;
                        
                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-size: 10pt;
                            color: #666;
                        }
                        
                        @bottom-left {
                            content: "Generated by School Paper Generator";
                            font-size: 10pt;
                            color: #666;
                        }
                    }
                    
                    /* First page special handling */
                    @page :first {
                        @bottom-right {
                            content: "";
                        }
                        
                        @bottom-left {
                            content: "";
                        }
                    }
                }
            `;
            
            // Add styles to document
            const styleSheet = document.createElement('style');
            styleSheet.type = 'text/css';
            styleSheet.textContent = printStyles;
            document.head.appendChild(styleSheet);
        },
        
        // Print paper functionality
        printPaper: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Show printing message
            $btn.html('<i class="fas fa-spinner fa-spin"></i> Preparing for print...');
            $btn.prop('disabled', true);
            
            // Store current scroll position
            const scrollPosition = $(window).scrollTop();
            
            // Show print dialog after a short delay
            setTimeout(function() {
                window.print();
                
                // Restore button state
                $btn.html(originalText);
                $btn.prop('disabled', false);
                
                // Restore scroll position
                $(window).scrollTop(scrollPosition);
                
                // Track print event
                SPG_PrintPaper.trackPrintEvent();
            }, 500);
        },
        
        // Print specific section
        printSection: function(e) {
            e.preventDefault();
            
            const sectionId = $(this).data('section');
            const $section = $('#' + sectionId);
            
            if ($section.length) {
                // Store original content
                const originalContent = $('body').html();
                
                // Create print view with only the section
                const printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>${document.title} - ${$section.find('h2, h3').first().text()}</title>
                        <style>
                            ${SPG_PrintPaper.getPrintStyles()}
                            @page { margin: 2cm; }
                            body { font-family: Arial, sans-serif; font-size: 12pt; }
                            .print-section { padding: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="print-section">
                            ${$section.html()}
                        </div>
                    </body>
                    </html>
                `;
                
                // Open print window
                const printWindow = window.open('', '_blank');
                printWindow.document.write(printContent);
                printWindow.document.close();
                
                // Print after content loads
                printWindow.onload = function() {
                    printWindow.print();
                    printWindow.close();
                };
            }
        },
        
        // Toggle section visibility
        toggleSection: function(e) {
            e.preventDefault();
            
            const sectionId = $(this).data('section');
            const $section = $('#' + sectionId);
            const $icon = $(this).find('i');
            
            $section.slideToggle(300, function() {
                if ($section.is(':visible')) {
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    $(this).attr('aria-expanded', 'true');
                } else {
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    $(this).attr('aria-expanded', 'false');
                }
            });
            
            // Save section state
            SPG_PrintPaper.saveSectionState(sectionId, $section.is(':visible'));
        },
        
        // Toggle answer visibility
        toggleAnswers: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $answers = $('.correct-answer, .answer-explanation');
            const $icon = $btn.find('i');
            
            $answers.slideToggle(300, function() {
                if ($answers.is(':visible')) {
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    $btn.find('.btn-text').text('Hide Answers');
                } else {
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    $btn.find('.btn-text').text('Show Answers');
                }
            });
            
            // Save preference
            localStorage.setItem('spg_show_answers', $answers.is(':visible'));
        },
        
        // Adjust font size
        adjustFontSize: function(e) {
            e.preventDefault();
            
            const action = $(this).data('action');
            const $container = $('.spg-paper-container');
            let currentSize = parseFloat($container.css('font-size'));
            
            if (action === 'increase' && currentSize < 20) {
                currentSize += 1;
            } else if (action === 'decrease' && currentSize > 12) {
                currentSize -= 1;
            } else if (action === 'reset') {
                currentSize = 16;
            }
            
            $container.css('font-size', currentSize + 'px');
            
            // Save preference
            localStorage.setItem('spg_font_size', currentSize);
            
            // Update display
            $('#current-font-size').text(currentSize + 'px');
        },
        
        // Toggle dark mode
        toggleDarkMode: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $body = $('body');
            const $icon = $btn.find('i');
            
            $body.toggleClass('dark-mode');
            
            if ($body.hasClass('dark-mode')) {
                $icon.removeClass('fa-moon').addClass('fa-sun');
                $btn.find('.btn-text').text('Light Mode');
            } else {
                $icon.removeClass('fa-sun').addClass('fa-moon');
                $btn.find('.btn-text').text('Dark Mode');
            }
            
            // Save preference
            localStorage.setItem('spg_dark_mode', $body.hasClass('dark-mode'));
        },
        
        // Highlight question
        highlightQuestion: function(e) {
            e.preventDefault();
            
            const questionId = $(this).closest('.question').data('id');
            const $question = $(this).closest('.question');
            
            $question.toggleClass('highlighted');
            
            // Save highlighted state
            const highlighted = JSON.parse(localStorage.getItem('spg_highlighted_questions') || '[]');
            const index = highlighted.indexOf(questionId);
            
            if ($question.hasClass('highlighted')) {
                if (index === -1) {
                    highlighted.push(questionId);
                }
            } else {
                if (index > -1) {
                    highlighted.splice(index, 1);
                }
            }
            
            localStorage.setItem('spg_highlighted_questions', JSON.stringify(highlighted));
        },
        
        // Start exam timer
        startExamTimer: function() {
            const $timer = $('#exam-timer');
            const timeLimit = $timer.data('time-limit'); // in minutes
            
            if (!timeLimit) return;
            
            let secondsRemaining = timeLimit * 60;
            let timerInterval;
            
            function updateTimerDisplay() {
                const hours = Math.floor(secondsRemaining / 3600);
                const minutes = Math.floor((secondsRemaining % 3600) / 60);
                const seconds = secondsRemaining % 60;
                
                let display = '';
                if (hours > 0) {
                    display += hours + ':';
                }
                display += (minutes < 10 ? '0' : '') + minutes + ':';
                display += (seconds < 10 ? '0' : '') + seconds;
                
                $timer.find('.timer-value').text(display);
                
                // Update progress bar
                const percentage = ((timeLimit * 60 - secondsRemaining) / (timeLimit * 60)) * 100;
                $timer.find('.progress-bar').css('width', percentage + '%');
                
                // Change color based on time remaining
                if (secondsRemaining < 300) { // 5 minutes
                    $timer.addClass('warning');
                }
                if (secondsRemaining < 60) { // 1 minute
                    $timer.addClass('danger');
                }
            }
            
            function startTimer() {
                timerInterval = setInterval(function() {
                    secondsRemaining--;
                    updateTimerDisplay();
                    
                    if (secondsRemaining <= 0) {
                        clearInterval(timerInterval);
                        SPG_PrintPaper.timeUp();
                    }
                }, 1000);
            }
            
            // Pause/resume timer
            $timer.find('.pause-timer').on('click', function() {
                const $btn = $(this);
                const $icon = $btn.find('i');
                
                if ($btn.hasClass('paused')) {
                    $btn.removeClass('paused');
                    $icon.removeClass('fa-play').addClass('fa-pause');
                    startTimer();
                } else {
                    $btn.addClass('paused');
                    $icon.removeClass('fa-pause').addClass('fa-play');
                    clearInterval(timerInterval);
                }
            });
            
            // Start timer
            updateTimerDisplay();
            startTimer();
        },
        
        // Time's up handler
        timeUp: function() {
            // Show time's up message
            const message = `
                <div class="time-up-alert">
                    <div class="alert-content">
                        <i class="fas fa-clock"></i>
                        <h3>Time's Up!</h3>
                        <p>Your exam time has ended. The exam will be automatically submitted.</p>
                        <button class="button button-primary" id="submit-now">Submit Now</button>
                    </div>
                </div>
            `;
            
            $('body').append(message);
            
            // Auto-submit after 10 seconds
            setTimeout(function() {
                $('#submit-now').click();
            }, 10000);
            
            // Submit now button
            $('#submit-now').on('click', function() {
                SPG_PrintPaper.submitExam();
            });
        },
        
        // Submit exam
        submitExam: function() {
            // Collect answers
            const answers = {};
            $('.question').each(function() {
                const questionId = $(this).data('id');
                const questionType = $(this).data('type');
                let answer = '';
                
                if (questionType === 'mcq' || questionType === 'true_false') {
                    const selected = $(this).find('input[type="radio"]:checked');
                    if (selected.length) {
                        answer = selected.val();
                    }
                } else if (questionType === 'short' || questionType === 'long') {
                    answer = $(this).find('textarea').val();
                }
                
                answers[questionId] = {
                    answer: answer,
                    timestamp: new Date().toISOString()
                };
            });
            
            // Submit via AJAX
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_submit_exam',
                    nonce: spg_ajax.nonce,
                    paper_id: $('#paper-id').val(),
                    answers: answers,
                    time_spent: $('#time-spent').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show results
                        SPG_PrintPaper.showExamResults(response.data);
                    } else {
                        alert('Error submitting exam: ' + response.data.message);
                    }
                }
            });
        },
        
        // Show exam results
        showExamResults: function(results) {
            const resultsHtml = `
                <div class="exam-results-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Exam Results</h3>
                        </div>
                        <div class="modal-body">
                            <div class="results-summary">
                                <div class="score-card">
                                    <h4>Your Score</h4>
                                    <div class="score">${results.score}/${results.total_marks}</div>
                                    <div class="percentage">${results.percentage}%</div>
                                </div>
                                
                                <div class="results-details">
                                    <h4>Details</h4>
                                    <div class="detail-row">
                                        <span>Total Questions:</span>
                                        <span>${results.total_questions}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Answered:</span>
                                        <span>${results.answered}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Correct:</span>
                                        <span>${results.correct}</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Time Taken:</span>
                                        <span>${results.time_taken}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-wise-results">
                                <h4>Question-wise Results</h4>
                                ${results.details.map((q, i) => `
                                    <div class="question-result ${q.correct ? 'correct' : 'incorrect'}">
                                        <span class="q-number">Q${i + 1}</span>
                                        <span class="q-status">${q.correct ? '?' : '?'}</span>
                                        <span class="q-marks">${q.marks_obtained}/${q.marks}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="button button-primary print-results">Print Results</button>
                            <button class="button close-results">Close</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(resultsHtml);
            
            // Bind print results button
            $('.print-results').on('click', function() {
                window.print();
            });
            
            // Bind close button
            $('.close-results').on('click', function() {
                $('.exam-results-modal').remove();
            });
        },
        
        // Save progress
        saveProgress: function(e) {
            e.preventDefault();
            
            const progress = {
                timestamp: new Date().toISOString(),
                answers: {},
                scrollPosition: $(window).scrollTop()
            };
            
            // Collect answers
            $('.question').each(function() {
                const questionId = $(this).data('id');
                const questionType = $(this).data('type');
                let answer = '';
                
                if (questionType === 'mcq' || questionType === 'true_false') {
                    const selected = $(this).find('input[type="radio"]:checked');
                    if (selected.length) {
                        answer = selected.val();
                    }
                } else if (questionType === 'short' || questionType === 'long') {
                    answer = $(this).find('textarea').val();
                }
                
                if (answer) {
                    progress.answers[questionId] = answer;
                }
            });
            
            // Save to localStorage
            localStorage.setItem('spg_exam_progress', JSON.stringify(progress));
            
            // Show saved message
            SPG_PrintPaper.showNotification('Progress saved successfully!', 'success');
        },
        
        // Load progress
        loadProgress: function(e) {
            e.preventDefault();
            
            const progress = JSON.parse(localStorage.getItem('spg_exam_progress') || '{}');
            
            if (!progress.answers || Object.keys(progress.answers).length === 0) {
                SPG_PrintPaper.showNotification('No saved progress found.', 'warning');
                return;
            }
            
            if (confirm('Load saved progress? This will overwrite current answers.')) {
                // Load answers
                $.each(progress.answers, function(questionId, answer) {
                    const $question = $('.question[data-id="' + questionId + '"]');
                    if ($question.length) {
                        const questionType = $question.data('type');
                        
                        if (questionType === 'mcq' || questionType === 'true_false') {
                            $question.find('input[type="radio"][value="' + answer + '"]').prop('checked', true);
                        } else if (questionType === 'short' || questionType === 'long') {
                            $question.find('textarea').val(answer);
                        }
                    }
                });
                
                // Restore scroll position
                if (progress.scrollPosition) {
                    $(window).scrollTop(progress.scrollPosition);
                }
                
                SPG_PrintPaper.showNotification('Progress loaded successfully!', 'success');
            }
        },
        
        // Clear all answers
        clearAnswers: function(e) {
            e.preventDefault();
            
            if (confirm('Clear all answers? This cannot be undone.')) {
                $('.question input[type="radio"]').prop('checked', false);
                $('.question textarea').val('');
                
                // Clear saved progress
                localStorage.removeItem('spg_exam_progress');
                
                SPG_PrintPaper.showNotification('All answers cleared.', 'info');
            }
        },
        
        // Handle keyboard shortcuts
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                $('#print-paper').click();
            }
            
            // Ctrl/Cmd + S for save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#save-progress').click();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                $('.spg-modal').hide();
            }
            
            // Space to toggle answers
            if (e.key === ' ' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
                e.preventDefault();
                $('.toggle-answers-btn').click();
            }
        },
        
        // Before print event
        beforePrint: function() {
            // Add print-specific classes
            $('body').addClass('printing');
            
            // Hide unnecessary elements
            $('.no-print').hide();
            
            // Show print message
            SPG_PrintPaper.showNotification('Preparing document for print...', 'info');
        },
        
        // After print event
        afterPrint: function() {
            // Remove print classes
            $('body').removeClass('printing');
            
            // Show hidden elements
            $('.no-print').show();
            
            // Show completion message
            SPG_PrintPaper.showNotification('Print completed successfully!', 'success');
        },
        
        // Track print event
        trackPrintEvent: function() {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_track_print',
                    nonce: spg_ajax.nonce,
                    paper_id: $('#paper-id').val()
                }
            });
        },
        
        // Save section state
        saveSectionState: function(sectionId, isVisible) {
            const sectionStates = JSON.parse(localStorage.getItem('spg_section_states') || '{}');
            sectionStates[sectionId] = isVisible;
            localStorage.setItem('spg_section_states', JSON.stringify(sectionStates));
        },
        
        // Load user preferences
        loadUserPreferences: function() {
            // Font size
            const fontSize = localStorage.getItem('spg_font_size');
            if (fontSize) {
                $('.spg-paper-container').css('font-size', fontSize + 'px');
                $('#current-font-size').text(fontSize + 'px');
            }
            
            // Dark mode
            if (localStorage.getItem('spg_dark_mode') === 'true') {
                $('body').addClass('dark-mode');
                $('.dark-mode-toggle i').removeClass('fa-moon').addClass('fa-sun');
                $('.dark-mode-toggle .btn-text').text('Light Mode');
            }
            
            // Show answers
            if (localStorage.getItem('spg_show_answers') === 'true') {
                $('.correct-answer, .answer-explanation').show();
                $('.toggle-answers-btn i').removeClass('fa-eye-slash').addClass('fa-eye');
                $('.toggle-answers-btn .btn-text').text('Hide Answers');
            }
            
            // Section states
            const sectionStates = JSON.parse(localStorage.getItem('spg_section_states') || '{}');
            $.each(sectionStates, function(sectionId, isVisible) {
                const $section = $('#' + sectionId);
                if ($section.length) {
                    $section.toggle(isVisible);
                    
                    const $btn = $('[data-section="' + sectionId + '"]');
                    if ($btn.length) {
                        const $icon = $btn.find('i');
                        if (isVisible) {
                            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                        } else {
                            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                        }
                    }
                }
            });
            
            // Highlighted questions
            const highlighted = JSON.parse(localStorage.getItem('spg_highlighted_questions') || '[]');
            highlighted.forEach(function(questionId) {
                $('.question[data-id="' + questionId + '"]').addClass('highlighted');
            });
        },
        
        // Get print styles
        getPrintStyles: function() {
            return `
                body {
                    font-family: "Times New Roman", serif;
                    font-size: 12pt;
                    line-height: 1.5;
                    margin: 0;
                    padding: 20px;
                    background: white !important;
                    color: black !important;
                }
                
                h1, h2, h3, h4 {
                    color: black !important;
                    page-break-after: avoid;
                }
                
                .paper-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }
                
                .school-logo img {
                    max-height: 80px;
                }
                
                .paper-title {
                    font-size: 16pt;
                    margin: 10px 0;
                }
                
                .question {
                    page-break-inside: avoid;
                    margin: 15px 0;
                }
                
                .question-number {
                    font-weight: bold;
                }
                
                .question-text {
                    margin: 10px 0;
                }
                
                .question-options {
                    margin-left: 20px;
                }
                
                .option {
                    margin: 5px 0;
                }
                
                .option-letter {
                    font-weight: bold;
                }
                
                textarea {
                    border: 1px solid #ccc;
                    background: white;
                    width: 100%;
                    min-height: 100px;
                    padding: 8px;
                    box-sizing: border-box;
                }
                
                .correct-answer {
                    border-left: 3px solid #4CAF50;
                    background: #f8fff8;
                    padding: 10px;
                    margin-top: 10px;
                    page-break-inside: avoid;
                }
                
                .page-break {
                    page-break-before: always;
                }
                
                @page {
                    margin: 2cm;
                    
                    @bottom-right {
                        content: "Page " counter(page);
                        font-size: 10pt;
                    }
                }
            `;
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.spg-notification').remove();
            
            const icons = {
                info: 'fas fa-info-circle',
                success: 'fas fa-check-circle',
                warning: 'fas fa-exclamation-triangle',
                error: 'fas fa-times-circle'
            };
            
            const notification = `
                <div class="spg-notification notification-${type}">
                    <div class="notification-content">
                        <i class="${icons[type]}"></i>
                        <span>${message}</span>
                    </div>
                    <button class="notification-close">&times;</button>
                </div>
            `;
            
            $('body').append(notification);
            
            const $notification = $('.spg-notification');
            $notification.addClass('show');
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
            
            // Close button
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SPG_PrintPaper.init();
    });
    
    // Expose to global scope
    window.SPG_PrintPaper = SPG_PrintPaper;
    
})(jQuery);