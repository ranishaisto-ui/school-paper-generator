<?php
if (!defined('ABSPATH')) exit;

// Get paper generator instance
$paper_generator = SPG_Paper_Generator::get_instance();

// Get papers
$papers = $paper_generator->get_papers();
$subjects = spg_get_subjects();
$class_levels = spg_get_class_levels();

// Get filter values
$current_subject = !empty($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
$current_class = !empty($_GET['class']) ? sanitize_text_field($_GET['class']) : '';
$current_search = !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
?>

<div class="wrap spg-generated-papers">
    <div class="spg-header">
        <h1><i class="fas fa-archive"></i> <?php _e('Generated Papers', 'school-paper-generator'); ?></h1>
        <p class="description"><?php _e('Manage all your generated exam papers', 'school-paper-generator'); ?></p>
    </div>
    
    <div class="spg-papers-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(count($papers)); ?></h3>
                <p><?php _e('Total Papers', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(array_reduce($papers, function($carry, $paper) {
                    return $carry + ($paper['status'] === 'published' ? 1 : 0);
                }, 0)); ?></h3>
                <p><?php _e('Published', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-pencil-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(array_reduce($papers, function($carry, $paper) {
                    return $carry + ($paper['status'] === 'draft' ? 1 : 0);
                }, 0)); ?></h3>
                <p><?php _e('Drafts', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-download"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(array_reduce($papers, function($carry, $paper) {
                    return $carry + (isset($paper['download_count']) ? $paper['download_count'] : 0);
                }, 0)); ?></h3>
                <p><?php _e('Total Downloads', 'school-paper-generator'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="spg-papers-actions">
        <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button button-primary">
            <i class="fas fa-plus"></i> <?php _e('Create New Paper', 'school-paper-generator'); ?>
        </a>
        
        <button type="button" class="button button-secondary" id="bulk-export-btn" style="display: none;">
            <i class="fas fa-download"></i> <?php _e('Export Selected', 'school-paper-generator'); ?>
        </button>
        
        <button type="button" class="button button-danger" id="bulk-delete-btn" style="display: none;">
            <i class="fas fa-trash"></i> <?php _e('Delete Selected', 'school-paper-generator'); ?>
        </button>
    </div>
    
    <div class="spg-papers-filters">
        <form method="get" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="spg-generated-papers">
            
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
                
                <div class="filter-group filter-search">
                    <label for="filter-search"><?php _e('Search:', 'school-paper-generator'); ?></label>
                    <input type="text" name="search" id="filter-search" 
                           value="<?php echo esc_attr($current_search); ?>" 
                           placeholder="<?php esc_attr_e('Search papers...', 'school-paper-generator'); ?>">
                    <button type="submit" class="button">
                        <i class="fas fa-search"></i> <?php _e('Search', 'school-paper-generator'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="spg-papers-table">
        <form id="papers-form" method="post">
            <?php wp_nonce_field('spg_bulk_action', 'spg_nonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="select-all-papers">
                        </th>
                        <th><?php _e('Paper Title', 'school-paper-generator'); ?></th>
                        <th width="100"><?php _e('Subject', 'school-paper-generator'); ?></th>
                        <th width="80"><?php _e('Class', 'school-paper-generator'); ?></th>
                        <th width="80"><?php _e('Marks', 'school-paper-generator'); ?></th>
                        <th width="120"><?php _e('Status', 'school-paper-generator'); ?></th>
                        <th width="120"><?php _e('Created', 'school-paper-generator'); ?></th>
                        <th width="150"><?php _e('Actions', 'school-paper-generator'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($papers)): ?>
                    <tr>
                        <td colspan="8" class="no-papers">
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <h4><?php _e('No Papers Generated Yet', 'school-paper-generator'); ?></h4>
                                <p><?php _e('Create your first exam paper to get started.', 'school-paper-generator'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button button-primary">
                                    <?php _e('Create Paper', 'school-paper-generator'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($papers as $paper): ?>
                    <tr class="paper-row" data-id="<?php echo $paper['id']; ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="paper_ids[]" value="<?php echo $paper['id']; ?>" class="paper-checkbox">
                        </th>
                        <td class="paper-title">
                            <a href="<?php echo admin_url('admin.php?page=spg-create-paper&paper_id=' . $paper['id']); ?>" class="row-title">
                                <?php echo esc_html($paper['title']); ?>
                            </a>
                            <?php if ($paper['paper_code']): ?>
                            <div class="paper-code">
                                <small><?php echo esc_html($paper['paper_code']); ?></small>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($paper['subject']); ?></td>
                        <td><?php echo esc_html($paper['class_level']); ?></td>
                        <td><?php echo esc_html($paper['total_marks']); ?></td>
                        <td>
                            <span class="paper-status status-<?php echo esc_attr($paper['status']); ?>">
                                <?php 
                                $status_labels = array(
                                    'draft' => __('Draft', 'school-paper-generator'),
                                    'published' => __('Published', 'school-paper-generator'),
                                    'archived' => __('Archived', 'school-paper-generator')
                                );
                                echo $status_labels[$paper['status']] ?? $paper['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($paper['created_at'])); ?></td>
                        <td class="paper-actions">
                            <a href="<?php echo admin_url('admin.php?page=spg-create-paper&paper_id=' . $paper['id']); ?>" 
                               class="button button-small" title="<?php esc_attr_e('Edit', 'school-paper-generator'); ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <a href="<?php echo admin_url('admin-ajax.php?action=spg_export_paper&format=pdf&paper_id=' . $paper['id']); ?>" 
                               class="button button-small" title="<?php esc_attr_e('Download PDF', 'school-paper-generator'); ?>" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                            
                            <button type="button" class="button button-small duplicate-paper" 
                                    data-id="<?php echo $paper['id']; ?>"
                                    title="<?php esc_attr_e('Duplicate', 'school-paper-generator'); ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                            
                            <button type="button" class="button button-small delete-paper" 
                                    data-id="<?php echo $paper['id']; ?>"
                                    title="<?php esc_attr_e('Delete', 'school-paper-generator'); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            
                            <div class="dropdown">
                                <button type="button" class="button button-small dropdown-toggle" title="<?php esc_attr_e('More', 'school-paper-generator'); ?>">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#" class="preview-paper" data-id="<?php echo $paper['id']; ?>">
                                        <i class="fas fa-eye"></i> <?php _e('Preview', 'school-paper-generator'); ?>
                                    </a>
                                    <a href="#" class="share-paper" data-id="<?php echo $paper['id']; ?>">
                                        <i class="fas fa-share"></i> <?php _e('Share', 'school-paper-generator'); ?>
                                    </a>
                                    <?php if ($paper['status'] === 'draft'): ?>
                                    <a href="#" class="publish-paper" data-id="<?php echo $paper['id']; ?>">
                                        <i class="fas fa-check"></i> <?php _e('Publish', 'school-paper-generator'); ?>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($paper['status'] === 'published'): ?>
                                    <a href="#" class="archive-paper" data-id="<?php echo $paper['id']; ?>">
                                        <i class="fas fa-archive"></i> <?php _e('Archive', 'school-paper-generator'); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <?php if (!empty($papers)): ?>
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
                <select name="action" id="bulk-action-selector-bottom">
                    <option value="">Bulk Actions</option>
                    <option value="export">Export</option>
                    <option value="delete">Delete</option>
                    <option value="publish">Publish</option>
                    <option value="archive">Archive</option>
                </select>
                <input type="submit" class="button action" id="doaction-bottom" value="Apply">
            </div>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', count($papers)), number_format_i18n(count($papers))); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Preview Paper Modal -->
<div id="preview-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content" style="max-width: 1000px;">
        <div class="spg-modal-header">
            <h3><?php _e('Paper Preview', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body" id="preview-content">
            <!-- Preview content will be loaded here -->
        </div>
        <div class="spg-modal-footer">
            <button type="button" class="button button-primary" id="print-preview">
                <i class="fas fa-print"></i> <?php _e('Print', 'school-paper-generator'); ?>
            </button>
            <button type="button" class="button" id="download-preview">
                <i class="fas fa-download"></i> <?php _e('Download PDF', 'school-paper-generator'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Share Paper Modal -->
<div id="share-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content" style="max-width: 500px;">
        <div class="spg-modal-header">
            <h3><?php _e('Share Paper', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <div class="share-options">
                <div class="share-option">
                    <h4><?php _e('Direct Link', 'school-paper-generator'); ?></h4>
                    <div class="input-group">
                        <input type="text" id="share-link" readonly>
                        <button type="button" class="button" id="copy-link">
                            <i class="fas fa-copy"></i> <?php _e('Copy', 'school-paper-generator'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="share-option">
                    <h4><?php _e('Embed Code', 'school-paper-generator'); ?></h4>
                    <div class="input-group">
                        <textarea id="embed-code" rows="3" readonly></textarea>
                        <button type="button" class="button" id="copy-embed">
                            <i class="fas fa-copy"></i> <?php _e('Copy', 'school-paper-generator'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="share-option">
                    <h4><?php _e('Share via Email', 'school-paper-generator'); ?></h4>
                    <form id="email-share-form">
                        <div class="form-group">
                            <input type="email" id="share-email" placeholder="<?php esc_attr_e('Enter email address', 'school-paper-generator'); ?>">
                        </div>
                        <button type="submit" class="button button-primary">
                            <i class="fas fa-paper-plane"></i> <?php _e('Send', 'school-paper-generator'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Papers management functionality
    const SPG_PapersManager = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Select all papers
            $('#select-all-papers').on('change', this.toggleSelectAll);
            
            // Bulk action buttons
            $('#bulk-export-btn').on('click', this.bulkExport);
            $('#bulk-delete-btn').on('click', this.bulkDelete);
            $('#doaction-bottom').on('click', this.handleBulkAction);
            
            // Individual paper actions
            $('.duplicate-paper').on('click', this.duplicatePaper);
            $('.delete-paper').on('click', this.deletePaper);
            $('.preview-paper').on('click', this.previewPaper);
            $('.share-paper').on('click', this.sharePaper);
            $('.publish-paper').on('click', this.publishPaper);
            $('.archive-paper').on('click', this.archivePaper);
            
            // Modal actions
            $('.spg-modal-close').on('click', this.closeModal);
            $('#print-preview').on('click', this.printPreview);
            $('#download-preview').on('click', this.downloadPreview);
            $('#copy-link').on('click', this.copyToClipboard);
            $('#copy-embed').on('click', this.copyToClipboard);
            $('#email-share-form').on('submit', this.sendEmail);
            
            // Dropdown menus
            $('.dropdown-toggle').on('click', function(e) {
                e.stopPropagation();
                $(this).siblings('.dropdown-menu').toggle();
            });
            
            $(document).on('click', function() {
                $('.dropdown-menu').hide();
            });
        },
        
        toggleSelectAll: function() {
            const isChecked = $(this).is(':checked');
            $('.paper-checkbox').prop('checked', isChecked);
            SPG_PapersManager.toggleBulkButtons();
        },
        
        toggleBulkButtons: function() {
            const checkedCount = $('.paper-checkbox:checked').length;
            if (checkedCount > 0) {
                $('#bulk-export-btn, #bulk-delete-btn').show();
                $('#bulk-export-btn').text('Export (' + checkedCount + ')');
                $('#bulk-delete-btn').text('Delete (' + checkedCount + ')');
            } else {
                $('#bulk-export-btn, #bulk-delete-btn').hide();
            }
        },
        
        bulkExport: function() {
            const paperIds = [];
            $('.paper-checkbox:checked').each(function() {
                paperIds.push($(this).val());
            });
            
            if (paperIds.length === 0) return;
            
            // Export each paper in a new tab
            paperIds.forEach(function(paperId) {
                window.open(spg_ajax.ajax_url + '?action=spg_export_paper&format=pdf&paper_id=' + paperId, '_blank');
            });
        },
        
        bulkDelete: function() {
            const paperIds = [];
            $('.paper-checkbox:checked').each(function() {
                paperIds.push($(this).val());
            });
            
            if (paperIds.length === 0) return;
            
            if (!confirm('Are you sure you want to delete ' + paperIds.length + ' papers?')) {
                return;
            }
            
            SPG_PapersManager.deletePapers(paperIds);
        },
        
        deletePapers: function(paperIds) {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_bulk_delete_papers',
                    nonce: spg_ajax.nonce,
                    paper_ids: paperIds
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
        
        duplicatePaper: function() {
            const paperId = $(this).data('id');
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_duplicate_paper',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId
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
        
        deletePaper: function() {
            const paperId = $(this).data('id');
            
            if (!confirm('Are you sure you want to delete this paper?')) {
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_delete_paper',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId
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
        
        previewPaper: function(e) {
            e.preventDefault();
            const paperId = $(this).data('id');
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_get_paper',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId
                },
                success: function(response) {
                    if (response.success) {
                        SPG_PapersManager.showPreview(response.data);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        showPreview: function(paper) {
            let html = '<div class="paper-preview">';
            html += '<div class="preview-header">';
            html += '<h2>' + paper.title + '</h2>';
            html += '<div class="paper-meta">';
            html += '<span><strong>' + spg_ajax.text.subject + ':</strong> ' + paper.subject + '</span>';
            html += '<span><strong>' + spg_ajax.text.class + ':</strong> ' + paper.class_level + '</span>';
            html += '<span><strong>' + spg_ajax.text.marks + ':</strong> ' + paper.total_marks + '</span>';
            html += '</div>';
            html += '</div>';
            
            if (paper.questions && paper.questions.length > 0) {
                html += '<div class="preview-questions">';
                paper.questions.forEach(function(question, index) {
                    html += SPG_PapersManager.getQuestionPreviewHTML(question, index + 1);
                });
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#preview-content').html(html);
            $('#preview-modal').show();
            
            // Store paper ID for download
            $('#download-preview').data('id', paper.id);
        },
        
        getQuestionPreviewHTML: function(question, number) {
            let html = '<div class="preview-question">';
            html += '<div class="question-number">Q' + number + '.</div>';
            html += '<div class="question-text">' + question.text + '</div>';
            
            if (question.type === 'mcq' && question.options) {
                html += '<div class="question-options">';
                question.options.forEach(function(option, idx) {
                    const letter = String.fromCharCode(65 + idx);
                    html += '<div class="option"><span class="option-letter">' + letter + '.</span> ' + option + '</div>';
                });
                html += '</div>';
            }
            
            html += '<div class="question-marks">[' + question.marks + ' marks]</div>';
            html += '</div>';
            
            return html;
        },
        
        sharePaper: function(e) {
            e.preventDefault();
            const paperId = $(this).data('id');
            
            // Generate share link
            const shareLink = window.location.origin + '/?spg_paper=' + paperId;
            const embedCode = '<iframe src="' + shareLink + '" width="100%" height="600"></iframe>';
            
            $('#share-link').val(shareLink);
            $('#embed-code').val(embedCode);
            $('#email-share-form').data('id', paperId);
            
            $('#share-modal').show();
        },
        
        publishPaper: function(e) {
            e.preventDefault();
            const paperId = $(this).data('id');
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_update_paper_status',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId,
                    status: 'published'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        archivePaper: function(e) {
            e.preventDefault();
            const paperId = $(this).data('id');
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_update_paper_status',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId,
                    status: 'archived'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        printPreview: function() {
            window.print();
        },
        
        downloadPreview: function() {
            const paperId = $(this).data('id');
            if (paperId) {
                window.open(spg_ajax.ajax_url + '?action=spg_export_paper&format=pdf&paper_id=' + paperId, '_blank');
            }
        },
        
        copyToClipboard: function() {
            const $input = $(this).siblings('input, textarea');
            $input.select();
            document.execCommand('copy');
            
            // Show copied message
            const originalText = $(this).html();
            $(this).html('<i class="fas fa-check"></i> Copied');
            setTimeout(() => {
                $(this).html(originalText);
            }, 2000);
        },
        
        sendEmail: function(e) {
            e.preventDefault();
            const paperId = $(this).data('id');
            const email = $('#share-email').val();
            
            if (!email) {
                alert('Please enter an email address');
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_share_via_email',
                    nonce: spg_ajax.nonce,
                    paper_id: paperId,
                    email: email
                },
                success: function(response) {
                    if (response.success) {
                        alert('Paper shared successfully!');
                        $('#share-modal').hide();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        closeModal: function() {
            $(this).closest('.spg-modal').hide();
        },
        
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-bottom').val();
            if (!action) return;
            
            const paperIds = [];
            $('.paper-checkbox:checked').each(function() {
                paperIds.push($(this).val());
            });
            
            if (paperIds.length === 0) {
                alert('Please select papers');
                return;
            }
            
            switch (action) {
                case 'export':
                    SPG_PapersManager.bulkExport();
                    break;
                case 'delete':
                    SPG_PapersManager.bulkDelete();
                    break;
                case 'publish':
                    SPG_PapersManager.updatePapersStatus(paperIds, 'published');
                    break;
                case 'archive':
                    SPG_PapersManager.updatePapersStatus(paperIds, 'archived');
                    break;
            }
        },
        
        updatePapersStatus: function(paperIds, status) {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_bulk_update_paper_status',
                    nonce: spg_ajax.nonce,
                    paper_ids: paperIds,
                    status: status
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
        }
    };
    
    // Initialize papers manager
    SPG_PapersManager.init();
    
    // Toggle bulk buttons on checkbox change
    $('.paper-checkbox').on('change', function() {
        SPG_PapersManager.toggleBulkButtons();
    });
});
</script>

<style>
.spg-generated-papers {
    max-width: 1400px;
}

.spg-papers-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 25px 0;
}

.spg-papers-actions {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.spg-papers-filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    gap: 15px;
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

.paper-title .row-title {
    font-weight: 500;
    color: #2271b1;
    text-decoration: none;
}

.paper-title .row-title:hover {
    color: #135e96;
}

.paper-code {
    margin-top: 5px;
    color: #666;
    font-size: 0.9em;
}

.paper-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.status-draft { background: #fff3e0; color: #f57c00; }
.status-published { background: #e8f5e8; color: #388e3c; }
.status-archived { background: #f5f5f5; color: #757575; }

.paper-actions {
    display: flex;
    gap: 5px;
}

.paper-actions .button-small {
    padding: 2px 8px;
    font-size: 0.9em;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 0;
    min-width: 150px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: none;
    z-index: 100;
}

.dropdown-menu a {
    display: block;
    padding: 8px 15px;
    color: #333;
    text-decoration: none;
    font-size: 0.9em;
}

.dropdown-menu a:hover {
    background: #f5f5f5;
}

.dropdown-menu a i {
    margin-right: 8px;
    width: 16px;
    text-align: center;
}

.no-papers {
    text-align: center;
    padding: 60px 20px;
}

.empty-state {
    max-width: 400px;
    margin: 0 auto;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
    color: #999;
}

.empty-state h4 {
    margin: 0 0 10px 0;
    color: #666;
}

.empty-state p {
    margin: 0 0 20px 0;
    color: #999;
}

/* Preview modal styles */
.paper-preview {
    padding: 20px;
}

.preview-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #333;
}

.preview-header h2 {
    margin: 0 0 15px 0;
    color: #333;
}

.paper-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.paper-meta span {
    color: #666;
}

.preview-questions {
    margin-top: 20px;
}

.preview-question {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px dashed #ddd;
    page-break-inside: avoid;
}

.question-number {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 10px;
    color: #333;
}

.question-text {
    margin-bottom: 15px;
    line-height: 1.6;
}

.question-options {
    margin-left: 20px;
    margin-bottom: 15px;
}

.option {
    margin: 5px 0;
    line-height: 1.5;
}

.option-letter {
    font-weight: bold;
    margin-right: 10px;
}

.question-marks {
    font-weight: bold;
    color: #1976d2;
    text-align: right;
}

.spg-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Share modal styles */
.share-option {
    margin-bottom: 25px;
}

.share-option h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.input-group {
    display: flex;
    gap: 10px;
}

.input-group input,
.input-group textarea {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.input-group textarea {
    resize: vertical;
    min-height: 80px;
}

.input-group button {
    white-space: nowrap;
}

#email-share-form {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

#email-share-form .form-group {
    flex: 1;
    margin: 0;
}

#share-email {
    width: 100%;
}

@media print {
    .spg-modal-header,
    .spg-modal-footer,
    .question-marks {
        display: none !important;
    }
    
    .paper-preview {
        padding: 0;
    }
    
    .preview-header {
        text-align: left;
        border-bottom: none;
        margin-bottom: 20px;
    }
    
    .paper-meta {
        justify-content: flex-start;
        gap: 15px;
    }
    
    .preview-question {
        border-bottom: 1px solid #000;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }
}

@media (max-width: 768px) {
    .spg-papers-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: 100%;
    }
    
    .paper-actions {
        flex-wrap: wrap;
    }
}
</style>