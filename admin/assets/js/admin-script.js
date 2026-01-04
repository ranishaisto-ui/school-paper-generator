<?php
// Note: This is a JavaScript file, but I'm including it as PHP to show the code
// The actual file should be .js extension
?>

jQuery(document).ready(function($) {
    // Main admin script for School Paper Generator
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip({
        trigger: 'hover',
        placement: 'top'
    });
    
    // Confirm dialogs for destructive actions
    $('.confirm-action').on('click', function(e) {
        const message = $(this).data('confirm') || 'Are you sure you want to do this?';
        if (!confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    
    // Toggle advanced options
    $('.toggle-advanced').on('click', function(e) {
        e.preventDefault();
        const target = $(this).data('target');
        $(target).slideToggle();
        $(this).find('.toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });
    
    // Auto-generate paper code
    $('#generate-paper-code').on('click', function() {
        const prefix = 'PAPER';
        const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        const random = Math.random().toString(36).substring(2, 8).toUpperCase();
        $('#paper-code').val(prefix + '-' + date + '-' + random);
    });
    
    // Character counters for textareas
    $('.char-counter').each(function() {
        const $textarea = $(this);
        const $counter = $('<div class="char-counter-display"><span class="current">0</span>/<span class="max">' + $textarea.data('max') + '</span></div>');
        $textarea.after($counter);
        
        $textarea.on('input', function() {
            const length = $(this).val().length;
            const max = $(this).data('max');
            $counter.find('.current').text(length);
            
            if (length > max) {
                $counter.addClass('over-limit');
            } else {
                $counter.removeClass('over-limit');
            }
        }).trigger('input');
    });
    
    // Sortable tables
    $('.sortable-table').each(function() {
        const $table = $(this);
        const $header = $table.find('th.sortable');
        
        $header.on('click', function() {
            const column = $(this).index();
            const direction = $(this).hasClass('sort-asc') ? 'desc' : 'asc';
            
            // Clear other sort classes
            $header.removeClass('sort-asc sort-desc');
            $(this).addClass('sort-' + direction);
            
            // Sort table rows
            const $rows = $table.find('tbody tr').get();
            
            $rows.sort(function(a, b) {
                const aVal = $(a).find('td').eq(column).text().trim();
                const bVal = $(b).find('td').eq(column).text().trim();
                
                // Try to convert to numbers for numeric sorting
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }
                
                // String comparison
                return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            
            $.each($rows, function(index, row) {
                $table.find('tbody').append(row);
            });
        });
    });
    
    // Bulk actions
    $('.bulk-action-form').each(function() {
        const $form = $(this);
        const $selectAll = $form.find('.select-all');
        const $checkboxes = $form.find('.item-checkbox');
        const $bulkActions = $form.find('.bulk-actions');
        
        $selectAll.on('change', function() {
            $checkboxes.prop('checked', $(this).is(':checked'));
            updateBulkActions();
        });
        
        $checkboxes.on('change', updateBulkActions);
        
        function updateBulkActions() {
            const checkedCount = $checkboxes.filter(':checked').length;
            if (checkedCount > 0) {
                $bulkActions.show();
                $bulkActions.find('.selected-count').text(checkedCount);
            } else {
                $bulkActions.hide();
            }
        }
        
        // Initialize
        updateBulkActions();
    });
    
    // Date pickers
    $('.date-picker').datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: '-10:+10'
    });
    
    // Time pickers
    $('.time-picker').timepicker({
        timeFormat: 'HH:mm',
        interval: 15,
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });
    
    // File upload preview
    $('.file-upload-preview').each(function() {
        const $input = $(this).find('input[type="file"]');
        const $preview = $(this).find('.preview');
        const $remove = $(this).find('.remove-file');
        
        $input.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $preview.html('<img src="' + e.target.result + '" alt="Preview">');
                    $remove.show();
                };
                reader.readAsDataURL(file);
            }
        });
        
        $remove.on('click', function() {
            $input.val('');
            $preview.empty();
            $remove.hide();
        });
    });
    
    // Dynamic form fields
    $(document).on('click', '.add-form-field', function(e) {
        e.preventDefault();
        
        const $container = $(this).closest('.form-field-group').find('.form-fields-container');
        const prototype = $container.data('prototype');
        const index = $container.find('.form-field').length;
        
        let newField = prototype.replace(/__name__/g, index);
        newField = newField.replace(/__index__/g, index);
        
        $container.append(newField);
    });
    
    $(document).on('click', '.remove-form-field', function(e) {
        e.preventDefault();
        
        const $field = $(this).closest('.form-field');
        if ($field.siblings('.form-field').length > 0) {
            $field.remove();
        } else {
            alert('At least one field is required.');
        }
    });
    
    // Tab navigation
    $('.nav-tabs a').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this).attr('href');
        
        // Update tabs
        $(this).closest('.nav-tabs').find('a').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $(this).closest('.tab-container').find('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Expand/collapse sections
    $('.section-header').on('click', function() {
        $(this).toggleClass('collapsed');
        $(this).next('.section-content').slideToggle();
    });
    
    // Auto-save functionality
    let autoSaveTimer = null;
    $('.auto-save').on('input', function() {
        clearTimeout(autoSaveTimer);
        
        autoSaveTimer = setTimeout(function() {
            saveChanges();
        }, 2000); // Save after 2 seconds of inactivity
    });
    
    function saveChanges() {
        const $button = $('#save-button');
        const originalText = $button.html();
        
        $button.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $button.prop('disabled', true);
        
        // Simulate save (replace with actual AJAX call)
        setTimeout(function() {
            $button.html('<i class="fas fa-check"></i> ' + originalText);
            $button.prop('disabled', false);
            
            // Show saved notification
            showNotification('Changes saved successfully.', 'success');
        }, 1000);
    }
    
    // Notification system
    window.showNotification = function(message, type = 'info') {
        const $notification = $('<div class="spg-notification notification-' + type + '">' + 
                               '<div class="notification-content">' + 
                               '<i class="notification-icon"></i>' +
                               '<span class="notification-message">' + message + '</span>' +
                               '</div>' +
                               '<button class="notification-close">&times;</button>' +
                               '</div>');
        
        // Set icon based on type
        let icon = 'fas fa-info-circle';
        if (type === 'success') icon = 'fas fa-check-circle';
        if (type === 'warning') icon = 'fas fa-exclamation-triangle';
        if (type === 'error') icon = 'fas fa-times-circle';
        
        $notification.find('.notification-icon').addClass(icon);
        
        // Add to page
        $('body').append($notification);
        
        // Show with animation
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
        
        // Close button
        $notification.find('.notification-close').on('click', function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        });
    };
    
    // Initialize Select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            minimumResultsForSearch: 10
        });
    }
    
    // Color picker
    $('.color-picker').each(function() {
        $(this).minicolors({
            control: $(this).data('control') || 'hue',
            defaultValue: $(this).data('default-value') || '',
            format: $(this).data('format') || 'hex',
            keywords: $(this).data('keywords') || '',
            opacity: $(this).data('opacity') || false,
            position: $(this).data('position') || 'bottom left',
            theme: 'bootstrap'
        });
    });
    
    // Initialize chart if Chart.js is available
    if (typeof Chart !== 'undefined') {
        $('.spg-chart').each(function() {
            const $canvas = $(this).find('canvas');
            const config = $(this).data('chart-config');
            
            if (config && $canvas.length) {
                new Chart($canvas[0].getContext('2d'), config);
            }
        });
    }
    
    // Responsive table
    $('.responsive-table').each(function() {
        const $table = $(this);
        const $headers = $table.find('th');
        const $rows = $table.find('tbody tr');
        
        $rows.each(function() {
            const $cells = $(this).find('td');
            
            $cells.each(function(index) {
                if (index < $headers.length) {
                    $(this).attr('data-label', $headers.eq(index).text());
                }
            });
        });
    });
    
    // Copy to clipboard
    $('.copy-to-clipboard').on('click', function() {
        const text = $(this).data('clipboard-text') || $(this).text();
        const $temp = $('<textarea>');
        
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        const $btn = $(this);
        const originalText = $btn.html();
        
        $btn.html('<i class="fas fa-check"></i> Copied!');
        setTimeout(function() {
            $btn.html(originalText);
        }, 2000);
        
        showNotification('Copied to clipboard!', 'success');
    });
    
    // Lazy load images
    $('img.lazy').lazyload({
        effect: 'fadeIn',
        threshold: 200
    });
    
    // Handle form submissions
    $('.spg-ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submit = $form.find('button[type="submit"]');
        const originalText = $submit.html();
        
        // Show loading state
        $submit.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $submit.prop('disabled', true);
        
        // Get form data
        const formData = new FormData(this);
        
        // Add nonce if not present
        if (!formData.has('nonce')) {
            formData.append('nonce', spg_ajax.nonce);
        }
        
        // Send AJAX request
        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification(response.data.message || 'Action completed successfully!', 'success');
                    
                    // Call success callback if defined
                    if (typeof response.data.callback === 'function') {
                        response.data.callback();
                    }
                    
                    // Redirect if specified
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1500);
                    }
                    
                    // Reload if specified
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    // Show error message
                    showNotification(response.data.message || 'An error occurred. Please try again.', 'error');
                    
                    // Reset button
                    $submit.html(originalText);
                    $submit.prop('disabled', false);
                }
            },
            error: function() {
                showNotification('Network error. Please check your connection and try again.', 'error');
                $submit.html(originalText);
                $submit.prop('disabled', false);
            }
        });
    });
    
    // Handle search with debounce
    let searchTimeout;
    $('.live-search').on('input', function() {
        clearTimeout(searchTimeout);
        
        const $input = $(this);
        const searchTerm = $input.val().trim();
        
        searchTimeout = setTimeout(function() {
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                performLiveSearch(searchTerm, $input.data('target'));
            }
        }, 500);
    });
    
    function performLiveSearch(term, target) {
        $.ajax({
            url: spg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'spg_live_search',
                nonce: spg_ajax.nonce,
                term: term,
                target: target
            },
            success: function(response) {
                if (response.success) {
                    $(target).html(response.data.html);
                }
            }
        });
    }
    
    // Initialize all functionality
    $(window).on('load', function() {
        // Trigger any on-load actions
        $('.on-load').trigger('load');
    });
    
    // Export current page data
    window.exportPageData = function(format) {
        const data = {
            page: window.location.pathname,
            timestamp: new Date().toISOString(),
            filters: getCurrentFilters(),
            data: getCurrentPageData()
        };
        
        let content, mimeType, filename;
        
        switch (format) {
            case 'json':
                content = JSON.stringify(data, null, 2);
                mimeType = 'application/json';
                filename = 'export-' + Date.now() + '.json';
                break;
                
            case 'csv':
                content = convertToCSV(data);
                mimeType = 'text/csv';
                filename = 'export-' + Date.now() + '.csv';
                break;
                
            default:
                return;
        }
        
        downloadFile(content, filename, mimeType);
    };
    
    function getCurrentFilters() {
        const filters = {};
        $('.filter-input').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name && value) {
                filters[name] = value;
            }
        });
        return filters;
    }
    
    function getCurrentPageData() {
        // This function should be customized per page
        // For now, return basic page info
        return {
            title: document.title,
            url: window.location.href,
            content: $('.main-content').text().substring(0, 500)
        };
    }
    
    function convertToCSV(data) {
        const headers = Object.keys(data).join(',');
        const values = Object.values(data).map(val => 
            typeof val === 'string' ? '"' + val.replace(/"/g, '""') + '"' : val
        ).join(',');
        return headers + '\n' + values;
    }
    
    function downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
});