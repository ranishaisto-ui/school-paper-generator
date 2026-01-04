
// School Paper Generator - Admin JavaScript
jQuery(document).ready(function ($) {

    // ============ DASHBOARD FUNCTIONALITY ============

    // Quick stats update
    function updateDashboardStats() {
        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_get_dashboard_stats',
                nonce: spg_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Update question count
                    $('.total-questions .count').text(response.data.total_questions);

                    // Update paper count
                    $('.total-papers .count').text(response.data.total_papers);

                    // Update trial days
                    $('.trial-days .count').text(response.data.days_left);

                    // Update progress bar
                    if (response.data.question_percentage) {
                        $('.question-progress .progress').css('width', response.data.question_percentage + '%');
                    }
                }
            }
        });
    }

    // ============ QUESTION BANK FUNCTIONALITY ============

    // Toggle MCQ options
    $('#question_type').on('change', function () {
        var type = $(this).val();

        if (type === 'mcq') {
            $('#mcq-options-row').show();
            $('#correct-answer-row').hide();
            updateMcqOptions();
        } else {
            $('#mcq-options-row').hide();
            $('#correct-answer-row').show();

            if (type === 'long') {
                $('#correct_answer').attr('rows', 6);
            } else {
                $('#correct_answer').attr('rows', 3);
            }
        }
    });

    // Add MCQ option
    $('.add-mcq-option').on('click', function () {
        var container = $('#mcq-options-container');
        var optionCount = container.find('.mcq-option-row').length;
        var optionLetter = String.fromCharCode(65 + optionCount);

        var html = '<div class="mcq-option-row" style="margin-bottom: 5px;">' +
            '<input type="text" name="mcq_options[]" placeholder="Option ' + optionLetter + '" style="width: 80%; padding: 5px;">' +
            '<button type="button" class="button button-small remove-option" style="margin-left: 5px;">Remove</button>' +
            '</div>';

        container.append(html);
        updateMcqOptions();
    });

    // Remove MCQ option
    $(document).on('click', '.remove-option', function () {
        if ($('.mcq-option-row').length > 2) {
            $(this).closest('.mcq-option-row').remove();
            updateMcqOptions();
        }
    });

    // Update MCQ options select
    function updateMcqOptions() {
        var select = $('#correct_option');
        var options = $('input[name="mcq_options[]"]');

        select.empty();

        options.each(function (index) {
            var letter = String.fromCharCode(65 + index);
            select.append($('<option>', {
                value: index,
                text: letter
            }));
        });
    }

    // Filter questions in question bank
    $('.question-filter').on('change', function () {
        var subject = $('#filter_subject').val();
        var classLevel = $('#filter_class').val();
        var type = $('#filter_type').val();

        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_filter_questions',
                subject: subject,
                class_level: classLevel,
                question_type: type,
                nonce: spg_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayFilteredQuestions(response.data.questions);
                }
            }
        });
    });

    // Delete question
    $(document).on('click', '.delete-question', function () {
        var questionId = $(this).data('id');
        var questionRow = $(this).closest('tr');

        if (confirm('Are you sure you want to delete this question?')) {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_delete_question',
                    question_id: questionId,
                    nonce: spg_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        questionRow.fadeOut(300, function () {
                            $(this).remove();
                        });
                    }
                }
            });
        }
    });

    // ============ CREATE PAPER FUNCTIONALITY ============

    // Filter questions for paper creation
    $('#filter-questions-btn').on('click', function () {
        var subject = $('#filter-subject').val();
        var classLevel = $('#filter-class').val();
        var type = $('#filter-type').val();

        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_filter_questions',
                subject: subject,
                class_level: classLevel,
                question_type: type,
                nonce: spg_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    displayPaperQuestions(response.data.questions);
                }
            }
        });
    });

    // Select/deselect questions for paper
    $(document).on('change', '.question-checkbox', function () {
        var questionId = $(this).val();
        var isChecked = $(this).is(':checked');

        if (isChecked) {
            addToSelectedQuestions(questionId);
        } else {
            removeFromSelectedQuestions(questionId);
        }

        updateTotalMarks();
    });

    function addToSelectedQuestions(questionId) {
        var questionItem = $('.question-item[data-id="' + questionId + '"]');
        var questionText = questionItem.find('.question-text').text();
        var questionMarks = questionItem.data('marks');
        var questionType = questionItem.data('type');

        var html = '<div class="selected-question" data-id="' + questionId + '">' +
            '<span class="question-type-badge ' + questionType + '">' + questionType.toUpperCase() + '</span>' +
            '<span class="question-text">' + questionText + '</span>' +
            '<span class="question-marks">(' + questionMarks + ' marks)</span>' +
            '<button type="button" class="remove-selected-question" data-id="' + questionId + '">×</button>' +
            '</div>';

        $('#selected-questions').append(html);
    }

    function removeFromSelectedQuestions(questionId) {
        $('.selected-question[data-id="' + questionId + '"]').remove();
        $('.question-checkbox[value="' + questionId + '"]').prop('checked', false);
    }

    // Remove selected question
    $(document).on('click', '.remove-selected-question', function () {
        var questionId = $(this).data('id');
        removeFromSelectedQuestions(questionId);
        updateTotalMarks();
    });

    // Calculate total marks
    function updateTotalMarks() {
        var totalMarks = 0;

        $('.selected-question').each(function () {
            var questionId = $(this).data('id');
            var questionItem = $('.question-item[data-id="' + questionId + '"]');
            var marks = parseInt(questionItem.data('marks')) || 0;
            totalMarks += marks;
        });

        $('#total-marks-display').text(totalMarks);
        $('#total_marks').val(totalMarks);
    }

    // ============ EXPORT FUNCTIONALITY ============

    // Print paper
    $('.print-paper').on('click', function () {
        var paperId = $(this).data('paper-id');
        window.open(spg_ajax.ajax_url + '?action=spg_print_paper&paper_id=' + paperId, '_blank');
    });

    // Export as PDF
    $('.export-pdf').on('click', function () {
        var paperId = $(this).data('paper-id');

        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_export_pdf',
                paper_id: paperId,
                nonce: spg_ajax.nonce
            },
            success: function (response) {
                if (response.success && response.data.url) {
                    // Download the PDF
                    var link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        });
    });

    // ============ PREMIUM FEATURES ============

    // Start free trial
    $('.start-trial').on('click', function () {
        if (confirm('Start your 30-day free trial? No credit card required.')) {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_start_trial',
                    nonce: spg_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert('Trial started successfully!');
                        location.reload();
                    }
                }
            });
        }
    });

    // Upgrade to premium
    $('.upgrade-premium').on('click', function () {
        var plan = $(this).data('plan');
        var amount = $(this).data('amount');

        showPaymentModal(plan, amount);
    });

    function showPaymentModal(plan, amount) {
        var modalHtml = '<div id="payment-modal" class="spg-modal">' +
            '<div class="modal-content">' +
            '<h3>Upgrade to Premium</h3>' +
            '<p>Plan: ' + plan + '</p>' +
            '<p>Amount: $' + amount + '</p>' +
            '<div class="payment-methods">' +
            '<label><input type="radio" name="payment_method" value="paypal" checked> PayPal</label>' +
            '<label><input type="radio" name="payment_method" value="stripe"> Credit Card (Stripe)</label>' +
            '<label><input type="radio" name="payment_method" value="bank"> Bank Transfer</label>' +
            '</div>' +
            '<div class="modal-actions">' +
            '<button class="button button-primary process-payment">Pay Now</button>' +
            '<button class="button cancel-payment">Cancel</button>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('body').append(modalHtml);

        // Process payment
        $('.process-payment').on('click', function () {
            var method = $('input[name="payment_method"]:checked').val();

            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_process_payment',
                    plan: plan,
                    amount: amount,
                    method: method,
                    nonce: spg_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('#payment-modal').remove();
                        alert('Payment successful! Your account has been upgraded.');
                        location.reload();
                    }
                }
            });
        });

        // Cancel payment
        $('.cancel-payment').on('click', function () {
            $('#payment-modal').remove();
        });
    }

    // ============ SETTINGS ============

    // Save settings
    $('#save-settings').on('click', function () {
        var schoolName = $('#school_name').val();
        var defaultSubjects = $('#default_subjects').val();
        var defaultClasses = $('#default_classes').val();

        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_save_settings',
                school_name: schoolName,
                default_subjects: defaultSubjects,
                default_classes: defaultClasses,
                nonce: spg_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('Settings saved successfully!');
                }
            }
        });
    });

    // ============ INITIALIZATION ============

    // Initialize MCQ options
    updateMcqOptions();

    // Auto-refresh dashboard every 5 minutes
    setInterval(updateDashboardStats, 300000);

    // Load initial data
    updateDashboardStats();
});