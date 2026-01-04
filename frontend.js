// School Paper Generator - Frontend JavaScript
jQuery(document).ready(function ($) {

    // ============ PAPER DISPLAY ============

    // Toggle answers in paper display
    $('.toggle-answers').on('click', function () {
        $('.correct-answer').toggle();
        $(this).text(function (i, text) {
            return text === 'Show Answers' ? 'Hide Answers' : 'Show Answers';
        });
    });

    // Print paper from frontend
    $('.print-paper-frontend').on('click', function () {
        window.print();
    });

    // ============ QUESTION BANK FILTERS ============

    // Filter questions in public question bank
    $('.spg-question-filter').on('change', function () {
        var subject = $('#spg-filter-subject').val();
        var classLevel = $('#spg-filter-class').val();
        var type = $('#spg-filter-type').val();

        $.ajax({
            url: spg_frontend_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_public_filter_questions',
                subject: subject,
                class_level: classLevel,
                question_type: type
            },
            success: function (response) {
                if (response.success) {
                    $('.spg-questions-container').html(response.data.html);
                }
            }
        });
    });

    // Toggle answer visibility in public question bank
    $(document).on('click', '.show-answer-btn', function () {
        var answerId = $(this).data('answer-id');
        $('#' + answerId).toggle();
        $(this).text(function (i, text) {
            return text === 'Show Answer' ? 'Hide Answer' : 'Show Answer';
        });
    });

    // ============ PREMIUM FEATURES ============

    // Start trial from frontend
    $('.start-trial-frontend').on('click', function (e) {
        e.preventDefault();

        if (!spg_frontend_ajax.is_logged_in) {
            alert('Please login to start your free trial.');
            window.location.href = spg_frontend_ajax.login_url;
            return;
        }

        if (confirm('Start your 30-day free trial? No credit card required.')) {
            $.ajax({
                url: spg_frontend_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_start_trial_frontend',
                    nonce: spg_frontend_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert('Trial started successfully! Redirecting to dashboard...');
                        window.location.href = spg_frontend_ajax.dashboard_url;
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });

    // Upgrade to premium from frontend
    $('.upgrade-premium-frontend').on('click', function (e) {
        e.preventDefault();

        if (!spg_frontend_ajax.is_logged_in) {
            alert('Please login to upgrade to premium.');
            window.location.href = spg_frontend_ajax.login_url;
            return;
        }

        var plan = $(this).data('plan');
        var amount = $(this).data('amount');

        // Redirect to premium page
        window.location.href = spg_frontend_ajax.premium_page + '&plan=' + plan;
    });

    // ============ SEARCH FUNCTIONALITY ============

    // Search questions
    $('#spg-search-questions').on('keyup', function () {
        var searchTerm = $(this).val().toLowerCase();

        $('.spg-question-item').each(function () {
            var questionText = $(this).find('.question-text').text().toLowerCase();

            if (questionText.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // ============ RESPONSIVE BEHAVIOR ============

    // Mobile menu toggle for premium features
    $('.spg-mobile-toggle').on('click', function () {
        $(this).siblings('.spg-mobile-menu').slideToggle();
    });

    // ============ PROGRESS BARS ============

    // Animate progress bars on scroll
    function animateProgressBars() {
        $('.spg-progress-bar').each(function () {
            var percent = $(this).data('percent');
            $(this).css('width', percent + '%');
        });
    }

    // Check if element is in viewport
    function isElementInViewport(el) {
        var rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Animate on scroll
    $(window).on('scroll', function () {
        $('.spg-progress-bar').each(function () {
            if (isElementInViewport(this)) {
                animateProgressBars();
            }
        });
    });

    // Initialize on page load
    animateProgressBars();
});