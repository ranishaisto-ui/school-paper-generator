jQuery(document).ready(function($) {
    
    // Initialize paper builder
    let SPG_PaperBuilder = {
        
        selectedQuestions: [],
        paperConfig: {},
        
        init: function() {
            this.initSortable();
            this.initFilters();
            this.bindEvents();
            this.loadInitialQuestions();
        },
        
        initSortable: function() {
            // Make questions list sortable
            $('.spg-questions-list').sortable({
                connectWith: '.spg-paper-questions',
                placeholder: 'question-placeholder',
                start: function(e, ui) {
                    ui.item.addClass('dragging');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('dragging');
                    SPG_PaperBuilder.updatePaperPreview();
                }
            });
            
            // Make paper preview area sortable
            $('.spg-paper-questions').sortable({
                connectWith: '.spg-questions-list',
                placeholder: 'question-placeholder',
                update: function() {
                    SPG_PaperBuilder.updatePaperPreview();
                }
            });
        },
        
        initFilters: function() {
            // Initialize Select2 for multi-select filters
            $('.spg-multi-select').select2({
                placeholder: 'Select...',
                allowClear: true
            });
            
            // Subject filter change
            $('#filter-subject').on('change', function() {
                SPG_PaperBuilder.loadQuestions();
            });
            
            // Class level filter change
            $('#filter-class').on('change', function() {
                SPG_PaperBuilder.loadQuestions();
            });
            
            // Question type filter
            $('.filter-type').on('click', function() {
                $('.filter-type').removeClass('active');
                $(this).addClass('active');
                SPG_PaperBuilder.loadQuestions();
            });
            
            // Search input with debounce
            let searchTimeout;
            $('#filter-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    SPG_PaperBuilder.loadQuestions();
                }, 500);
            });
        },
        
        bindEvents: function() {
            // Generate paper button
            $('#generate-paper').on('click', function(e) {
                e.preventDefault();
                SPG_PaperBuilder.generatePaper();
            });
            
            // Save paper button
            $('#save-paper').on('click', function(e) {
                e.preventDefault();
                SPG_PaperBuilder.savePaper();
            });
            
            // Export buttons
            $('.export-format').on('click', function(e) {
                e.preventDefault();
                let format = $(this).data('format');
                SPG_PaperBuilder.exportPaper(format);
            });
            
            // Add question manually
            $('#add-question-btn').on('click', function() {
                SPG_PaperBuilder.openQuestionModal();
            });
            
            // School logo upload
            $('#upload-school-logo').on('click', function(e) {
                e.preventDefault();
                SPG_PaperBuilder.uploadLogo();
            });
            
            // Update paper settings
            $('.paper-setting').on('change', function() {
                SPG_PaperBuilder.updatePaperConfig();
            });
        },
        
        loadInitialQuestions: function() {
            let filters = this.getFilters();
            this.loadQuestions(filters);
        },
        
        loadQuestions: function(filters = null) {
            filters = filters || this.getFilters();
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_questions',
                    nonce: spg_ajax.nonce,
                    filters: filters
                },
                beforeSend: function() {
                    $('.spg-questions-list').html('<div class="loading">Loading questions...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        SPG_PaperBuilder.renderQuestions(response.data);
                    } else {
                        alert(response.data.message || 'Error loading questions');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        },
        
        getFilters: function() {
            return {
                subject: $('#filter-subject').val(),
                class_level: $('#filter-class').val(),
                question_type: $('.filter-type.active').data('type') || '',
                difficulty: $('#filter-difficulty').val(),
                search: $('#filter-search').val(),
                limit: 50
            };
        },
        
        renderQuestions: function(questions) {
            let html = '';
            
            if (questions.length === 0) {
                html = '<div class="no-questions">No questions found. Try changing filters.</div>';
            } else {
                questions.forEach(function(question) {
                    html += SPG_PaperBuilder.getQuestionHTML(question);
                });
            }
            
            $('.spg-questions-list').html(html);
            this.makeQuestionsDraggable();
        },
        
        getQuestionHTML: function(question) {
            let badgeClass = 'badge-' + question.question_type;
            let badgeText = question.question_type.toUpperCase();
            
            return `
            <div class="spg-question-item" data-id="${question.id}" data-type="${question.question_type}">
                <div class="question-type-badge ${badgeClass}">${badgeText}</div>
                <div class="question-text">${question.question_text.substring(0, 150)}...</div>
                <div class="question-meta">
                    <span class="subject">${question.subject}</span> | 
                    <span class="class">${question.class_level}</span>
                </div>
                <div class="question-marks">${question.marks} marks</div>
            </div>`;
        },
        
        makeQuestionsDraggable: function() {
            $('.spg-question-item').draggable({
                connectToSortable: '.spg-paper-questions',
                helper: 'clone',
                revert: 'invalid',
                start: function() {
                    $(this).addClass('dragging');
                },
                stop: function() {
                    $(this).removeClass('dragging');
                }
            });
        },
        
        generatePaper: function() {
            let config = this.getPaperConfig();
            
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
                        SPG_PaperBuilder.renderGeneratedPaper(response.data.questions);
                        $('#save-paper').prop('disabled', false);
                    } else {
                        alert(response.data.message || 'Error generating paper');
                    }
                },
                complete: function() {
                    $('#generate-paper').prop('disabled', false).text('Generate Paper');
                }
            });
        },
        
        getPaperConfig: function() {
            return {
                subject: $('#paper-subject').val(),
                class_level: $('#paper-class').val(),
                total_marks: $('#total-marks').val(),
                time_duration: $('#time-duration').val(),
                sections: this.getPaperSections(),
                shuffle_questions: $('#shuffle-questions').is(':checked'),
                shuffle_options: $('#shuffle-options').is(':checked'),
                difficulty: $('#paper-difficulty').val()
            };
        },
        
        getPaperSections: function() {
            let sections = [];
            
            $('.paper-section').each(function() {
                let section = {
                    type: $(this).data('type'),
                    count: $(this).find('.section-count').val(),
                    marks_per: $(this).find('.section-marks').val()
                };
                sections.push(section);
            });
            
            return sections;
        },
        
        renderGeneratedPaper: function(questions) {
            let html = '<div class="paper-questions-list">';
            
            questions.forEach(function(question, index) {
                html += SPG_PaperBuilder.getQuestionPreviewHTML(question, index + 1);
            });
            
            html += '</div>';
            $('.spg-paper-questions').html(html);
            this.updatePaperStats(questions);
        },
        
        getQuestionPreviewHTML: function(question, number) {
            let optionsHtml = '';
            
            if (question.options && question.type === 'mcq') {
                optionsHtml = '<div class="options-preview">';
                question.options.forEach(function(option, optIndex) {
                    let isCorrect = option === question.correct_answer;
                    let optionClass = isCorrect ? 'option-preview correct' : 'option-preview';
                    let optionLetter = String.fromCharCode(65 + optIndex);
                    
                    optionsHtml += `
                    <div class="${optionClass}">
                        <strong>${optionLetter}.</strong> ${option}
                        ${isCorrect ? '<span class="correct-indicator">?</span>' : ''}
                    </div>`;
                });
                optionsHtml += '</div>';
            }
            
            return `
            <div class="question-preview" data-id="${question.id}" data-type="${question.type}">
                <div class="question-number">Q${number}. <span class="marks">[${question.marks} marks]</span></div>
                <div class="question-text-preview">${question.text}</div>
                ${optionsHtml}
                <div class="question-actions">
                    <button class="button-small remove-question" data-id="${question.id}">
                        <i class="fas fa-times"></i> Remove
                    </button>
                    <button class="button-small edit-question" data-id="${question.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>`;
        },
        
        updatePaperStats: function(questions) {
            let totalQuestions = questions.length;
            let totalMarks = questions.reduce((sum, q) => sum + parseInt(q.marks), 0);
            let mcqCount = questions.filter(q => q.type === 'mcq').length;
            let shortCount = questions.filter(q => q.type === 'short').length;
            let longCount = questions.filter(q => q.type === 'long').length;
            
            $('#total-questions').text(totalQuestions);
            $('#total-marks-display').text(totalMarks);
            $('#mcq-count').text(mcqCount);
            $('#short-count').text(shortCount);
            $('#long-count').text(longCount);
        },
        
        savePaper: function() {
            let paperData = this.getPaperData();
            
            if (!paperData.title) {
                alert('Please enter paper title');
                return;
            }
            
            if (paperData.questions.length === 0) {
                alert('Please add questions to the paper');
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
                        window.location.href = response.data.redirect;
                    } else {
                        alert(response.data.message || 'Error saving paper');
                    }
                },
                complete: function() {
                    $('#save-paper').prop('disabled', false).text('Save Paper');
                }
            });
        },
        
        getPaperData: function() {
            let questions = [];
            
            $('.question-preview').each(function(index) {
                let questionId = $(this).data('id');
                let question = {
                    id: questionId,
                    order: index + 1,
                    marks: $(this).find('.marks').text().match(/\d+/)[0],
                    section: $(this).closest('.section-questions').data('section') || 'main'
                };
                questions.push(question);
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
            let paperData = this.getPaperData();
            
            // Check if paper has been saved
            if (!paperData.id && format !== 'preview') {
                alert('Please save the paper before exporting');
                return;
            }
            
            let url = spg_ajax.ajax_url + '?action=spg_export_paper&format=' + format;
            
            if (paperData.id) {
                url += '&paper_id=' + paperData.id;
            } else {
                // For preview, send data via POST
                this.exportPaperPreview(format, paperData);
                return;
            }
            
            window.open(url, '_blank');
        },
        
        exportPaperPreview: function(format, paperData) {
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
                    let url = window.URL.createObjectURL(blob);
                    let a = document.createElement('a');
                    a.href = url;
                    a.download = 'paper-preview.' + format;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            });
        },
        
        uploadLogo: function() {
            let frame = wp.media({
                title: 'Select School Logo',
                button: {
                    text: 'Use this logo'
                },
                multiple: false
            });
            
            frame.on('select', function() {
                let attachment = frame.state().get('selection').first().toJSON();
                $('#school-logo-url').val(attachment.url);
                $('#school-logo-preview').attr('src', attachment.url).show();
            });
            
            frame.open();
        },
        
        openQuestionModal: function() {
            // Open modal for adding new question
            $('#add-question-modal').show();
        }
    };
    
    // Initialize paper builder
    SPG_PaperBuilder.init();
    
});