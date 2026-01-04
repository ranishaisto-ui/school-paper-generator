<?php
if (!defined('ABSPATH')) exit;

// Get school information
$school_info = spg_get_school_info();
$trial_days = spg_get_trial_days_remaining();
$question_count = spg_get_current_question_count();
$question_limit = spg_get_trial_question_limit();
?>

<div class="wrap spg-school-settings">
    <div class="spg-header">
        <h1><i class="fas fa-school"></i> <?php _e('School Settings', 'school-paper-generator'); ?></h1>
        <p class="description"><?php _e('Configure your school information and plugin settings', 'school-paper-generator'); ?></p>
    </div>
    
    <div class="spg-settings-container">
        <div class="spg-settings-main">
            <div class="settings-card">
                <h3><i class="fas fa-info-circle"></i> <?php _e('School Information', 'school-paper-generator'); ?></h3>
                
                <form id="school-settings-form">
                    <div class="form-group">
                        <label for="school-name"><?php _e('School Name *', 'school-paper-generator'); ?></label>
                        <input type="text" id="school-name" name="school_name" 
                               value="<?php echo esc_attr($school_info['name']); ?>"
                               placeholder="<?php esc_attr_e('Enter school name', 'school-paper-generator'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="school-address"><?php _e('School Address', 'school-paper-generator'); ?></label>
                        <textarea id="school-address" name="school_address" rows="3"><?php 
                            echo esc_textarea($school_info['address']); 
                        ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="school-contact"><?php _e('Contact Number', 'school-paper-generator'); ?></label>
                            <input type="text" id="school-contact" name="school_contact" 
                                   value="<?php echo esc_attr($school_info['contact']); ?>"
                                   placeholder="<?php esc_attr_e('e.g., +1 234 567 8900', 'school-paper-generator'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="school-email"><?php _e('Email Address', 'school-paper-generator'); ?></label>
                            <input type="email" id="school-email" name="school_email" 
                                   value="<?php echo esc_attr($school_info['email']); ?>"
                                   placeholder="<?php esc_attr_e('contact@school.edu', 'school-paper-generator'); ?>">
                        </div>
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
                        <p class="description"><?php _e('Recommended size: 200x80 pixels, JPG or PNG format', 'school-paper-generator'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'school-paper-generator'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="settings-card">
                <h3><i class="fas fa-cog"></i> <?php _e('Paper Settings', 'school-paper-generator'); ?></h3>
                
                <form id="paper-settings-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="default-marks"><?php _e('Default Total Marks', 'school-paper-generator'); ?></label>
                            <input type="number" id="default-marks" name="default_marks" 
                                   value="<?php echo esc_attr(get_option('spg_default_marks', 100)); ?>"
                                   min="1" max="500">
                        </div>
                        
                        <div class="form-group">
                            <label for="default-duration"><?php _e('Default Time Duration', 'school-paper-generator'); ?></label>
                            <input type="text" id="default-duration" name="default_duration" 
                                   value="<?php echo esc_attr(get_option('spg_default_duration', '3 hours')); ?>"
                                   placeholder="<?php esc_attr_e('e.g., 3 hours', 'school-paper-generator'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="default-instructions"><?php _e('Default Instructions', 'school-paper-generator'); ?></label>
                        <textarea id="default-instructions" name="default_instructions" rows="4"><?php 
                            echo esc_textarea(get_option('spg_default_instructions', implode("\n", spg_get_default_instructions()))); 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="shuffle_by_default" value="1" 
                                <?php checked(get_option('spg_shuffle_by_default', '1'), '1'); ?>>
                            <?php _e('Shuffle questions by default', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="shuffle_options_by_default" value="1"
                                <?php checked(get_option('spg_shuffle_options_by_default', '1'), '1'); ?>>
                            <?php _e('Shuffle MCQ options by default', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'school-paper-generator'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="settings-card">
                <h3><i class="fas fa-download"></i> <?php _e('Export Settings', 'school-paper-generator'); ?></h3>
                
                <form id="export-settings-form">
                    <div class="form-group">
                        <label for="default-export-format"><?php _e('Default Export Format', 'school-paper-generator'); ?></label>
                        <select id="default-export-format" name="default_export_format">
                            <option value="pdf" <?php selected(get_option('spg_default_export_format', 'pdf'), 'pdf'); ?>>
                                <?php _e('PDF Document', 'school-paper-generator'); ?>
                            </option>
                            <?php if (spg_is_premium_active()): ?>
                            <option value="docx" <?php selected(get_option('spg_default_export_format'), 'docx'); ?>>
                                <?php _e('Microsoft Word', 'school-paper-generator'); ?>
                            </option>
                            <option value="xlsx" <?php selected(get_option('spg_default_export_format'), 'xlsx'); ?>>
                                <?php _e('Microsoft Excel', 'school-paper-generator'); ?>
                            </option>
                            <option value="html" <?php selected(get_option('spg_default_export_format'), 'html'); ?>>
                                <?php _e('HTML Web Page', 'school-paper-generator'); ?>
                            </option>
                            <?php endif; ?>
                        </select>
                        <?php if (!spg_is_premium_active()): ?>
                        <p class="description premium-note">
                            <span class="premium-badge">PREMIUM</span>
                            <?php _e('Multiple export formats available in premium version', 'school-paper-generator'); ?>
                            <a href="https://yourwebsite.com/upgrade" class="button button-small"><?php _e('Upgrade Now', 'school-paper-generator'); ?></a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_answer_key" value="1"
                                <?php checked(get_option('spg_include_answer_key', '1'), '1'); ?>>
                            <?php _e('Include answer key in exports', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_school_info" value="1"
                                <?php checked(get_option('spg_include_school_info', '1'), '1'); ?>>
                            <?php _e('Include school information in exports', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="auto_generate_code" value="1"
                                <?php checked(get_option('spg_auto_generate_code', '1'), '1'); ?>>
                            <?php _e('Auto-generate paper codes', 'school-paper-generator'); ?>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'school-paper-generator'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="spg-settings-sidebar">
            <?php if (!spg_is_premium_active()): ?>
            <div class="premium-teaser">
                <h3><i class="fas fa-crown"></i> <?php _e('Upgrade to Premium', 'school-paper-generator'); ?></h3>
                
                <div class="trial-info">
                    <h4><?php _e('Trial Status', 'school-paper-generator'); ?></h4>
                    <div class="trial-stats">
                        <div class="stat">
                            <span class="stat-label"><?php _e('Days Remaining:', 'school-paper-generator'); ?></span>
                            <span class="stat-value <?php echo $trial_days <= 7 ? 'warning' : ''; ?>">
                                <?php echo $trial_days > 0 ? $trial_days : __('Expired', 'school-paper-generator'); ?>
                            </span>
                        </div>
                        <div class="stat">
                            <span class="stat-label"><?php _e('Questions Used:', 'school-paper-generator'); ?></span>
                            <span class="stat-value">
                                <?php echo $question_count . '/' . $question_limit; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="premium-features">
                    <h4><?php _e('Premium Features', 'school-paper-generator'); ?></h4>
                    <ul>
                        <li><i class="fas fa-check"></i> <?php _e('Unlimited question bank', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('School logo on every paper', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('Multiple export formats', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('Advanced question randomization', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('Multiple paper templates', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('Bulk import/export', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('Priority support', 'school-paper-generator'); ?></li>
                        <li><i class="fas fa-check"></i> <?php _e('No trial limitations', 'school-paper-generator'); ?></li>
                    </ul>
                </div>
                
                <div class="upgrade-cta">
                    <a href="https://yourwebsite.com/upgrade" class="button button-primary button-large" target="_blank">
                        <i class="fas fa-rocket"></i> <?php _e('Upgrade to Premium', 'school-paper-generator'); ?>
                    </a>
                    <p class="upgrade-note"><?php _e('One-time payment. Lifetime updates and support.', 'school-paper-generator'); ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="premium-status">
                <h3><i class="fas fa-crown"></i> <?php _e('Premium Version Active', 'school-paper-generator'); ?></h3>
                <div class="premium-badge active">
                    <i class="fas fa-check"></i> <?php _e('PREMIUM', 'school-paper-generator'); ?>
                </div>
                <p><?php _e('All premium features are unlocked and active.', 'school-paper-generator'); ?></p>
                
                <div class="premium-support">
                    <h4><?php _e('Premium Support', 'school-paper-generator'); ?></h4>
                    <p><?php _e('Get priority support for any issues:', 'school-paper-generator'); ?></p>
                    <a href="https://yourwebsite.com/support" class="button" target="_blank">
                        <i class="fas fa-headset"></i> <?php _e('Contact Support', 'school-paper-generator'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="settings-card">
                <h3><i class="fas fa-tools"></i> <?php _e('Tools', 'school-paper-generator'); ?></h3>
                
                <div class="tools-list">
                    <button type="button" class="button button-secondary" id="clear-cache-btn">
                        <i class="fas fa-broom"></i> <?php _e('Clear Cache', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="export-settings-btn">
                        <i class="fas fa-file-export"></i> <?php _e('Export Settings', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="import-settings-btn">
                        <i class="fas fa-file-import"></i> <?php _e('Import Settings', 'school-paper-generator'); ?>
                    </button>
                    
                    <button type="button" class="button button-danger" id="reset-settings-btn">
                        <i class="fas fa-redo"></i> <?php _e('Reset Settings', 'school-paper-generator'); ?>
                    </button>
                </div>
                
                <div class="danger-zone">
                    <h4><?php _e('Danger Zone', 'school-paper-generator'); ?></h4>
                    <p><?php _e('These actions cannot be undone:', 'school-paper-generator'); ?></p>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="delete-data-confirm">
                            <?php _e('Delete all data on uninstall', 'school-paper-generator'); ?>
                        </label>
                        <p class="description"><?php _e('When plugin is uninstalled, all data will be permanently deleted.', 'school-paper-generator'); ?></p>
                    </div>
                    
                    <button type="button" class="button button-danger" id="delete-all-data-btn" disabled>
                        <i class="fas fa-trash"></i> <?php _e('Delete All Data', 'school-paper-generator'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Settings Modal -->
<div id="import-settings-modal" class="spg-modal" style="display: none;">
    <div class="spg-modal-content">
        <div class="spg-modal-header">
            <h3><?php _e('Import Settings', 'school-paper-generator'); ?></h3>
            <button type="button" class="spg-modal-close">&times;</button>
        </div>
        <div class="spg-modal-body">
            <form id="import-settings-form">
                <div class="form-group">
                    <label for="import-settings-file"><?php _e('Select Settings File', 'school-paper-generator'); ?></label>
                    <input type="file" id="import-settings-file" name="settings_file" accept=".json" required>
                    <p class="description"><?php _e('Select a JSON file exported from School Paper Generator', 'school-paper-generator'); ?></p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="overwrite_existing" value="1">
                        <?php _e('Overwrite existing settings', 'school-paper-generator'); ?>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-file-import"></i> <?php _e('Import Settings', 'school-paper-generator'); ?>
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
    // School settings functionality
    const SPG_SettingsManager = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Save forms
            $('#school-settings-form').on('submit', this.saveSchoolSettings);
            $('#paper-settings-form').on('submit', this.savePaperSettings);
            $('#export-settings-form').on('submit', this.saveExportSettings);
            
            // Logo upload
            $('#upload-logo-btn').on('click', this.uploadLogo);
            $(document).on('click', '.remove-logo', this.removeLogo);
            
            // Tools
            $('#clear-cache-btn').on('click', this.clearCache);
            $('#export-settings-btn').on('click', this.exportSettings);
            $('#import-settings-btn').on('click', this.openImportModal);
            $('#reset-settings-btn').on('click', this.resetSettings);
            
            // Danger zone
            $('#delete-data-confirm').on('change', this.toggleDeleteButton);
            $('#delete-all-data-btn').on('click', this.deleteAllData);
            
            // Modal
            $('.spg-modal-close').on('click', this.closeModal);
            $('#import-settings-form').on('submit', this.importSettings);
        },
        
        saveSchoolSettings: function(e) {
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
                    action: 'spg_save_settings',
                    nonce: spg_ajax.nonce,
                    settings: data
                },
                success: function(response) {
                    if (response.success) {
                        alert('School settings saved successfully!');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        savePaperSettings: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const data = {};
            
            formData.forEach(function(item) {
                const key = 'spg_' + item.name;
                data[key] = item.value === '' ? '' : item.value;
            });
            
            // Handle checkboxes
            $(this).find('input[type="checkbox"]').each(function() {
                const key = 'spg_' + $(this).attr('name');
                data[key] = $(this).is(':checked') ? '1' : '0';
            });
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_save_settings',
                    nonce: spg_ajax.nonce,
                    settings: data
                },
                success: function(response) {
                    if (response.success) {
                        alert('Paper settings saved successfully!');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        saveExportSettings: function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            const data = {};
            
            formData.forEach(function(item) {
                const key = 'spg_' + item.name;
                data[key] = item.value;
            });
            
            // Handle checkboxes
            $(this).find('input[type="checkbox"]').each(function() {
                const key = 'spg_' + $(this).attr('name');
                data[key] = $(this).is(':checked') ? '1' : '0';
            });
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_save_settings',
                    nonce: spg_ajax.nonce,
                    settings: data
                },
                success: function(response) {
                    if (response.success) {
                        alert('Export settings saved successfully!');
                    } else {
                        alert(response.data.message);
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
                
                $.ajax({
                    url: spg_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'spg_upload_logo',
                        nonce: spg_ajax.nonce,
                        logo_url: attachment.url
                    },
                    success: function(response) {
                        if (response.success) {
                            const html = `
                            <div class="logo-preview">
                                <img src="${attachment.url}" alt="School Logo">
                                <button type="button" class="remove-logo" title="Remove Logo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>`;
                            
                            $('#logo-upload-area').html(html);
                            $('#school-logo-url').val(attachment.url);
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
            
            frame.open();
        },
        
        removeLogo: function() {
            if (!confirm('Are you sure you want to remove the school logo?')) {
                return;
            }
            
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
            $('#upload-logo-btn').on('click', SPG_SettingsManager.uploadLogo);
        },
        
        clearCache: function() {
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_clear_cache',
                    nonce: spg_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        exportSettings: function() {
            window.open(spg_ajax.ajax_url + '?action=spg_export_settings&nonce=' + spg_ajax.nonce, '_blank');
        },
        
        openImportModal: function() {
            $('#import-settings-modal').show();
        },
        
        closeModal: function() {
            $(this).closest('.spg-modal').hide();
        },
        
        importSettings: function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'spg_import_settings');
            formData.append('nonce', spg_ajax.nonce);
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Settings imported successfully!');
                        $('#import-settings-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        resetSettings: function() {
            if (!confirm('Are you sure you want to reset all settings to default? This cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_reset_settings',
                    nonce: spg_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Settings reset successfully!');
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        },
        
        toggleDeleteButton: function() {
            $('#delete-all-data-btn').prop('disabled', !$(this).is(':checked'));
        },
        
        deleteAllData: function() {
            if (!confirm('WARNING: This will delete ALL questions, papers, and settings. This action cannot be undone. Are you absolutely sure?')) {
                return;
            }
            
            if (!confirm('This is your final warning. Click OK to permanently delete all data.')) {
                return;
            }
            
            $.ajax({
                url: spg_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'spg_delete_all_data',
                    nonce: spg_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('All data deleted successfully!');
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    };
    
    // Initialize settings manager
    SPG_SettingsManager.init();
});
</script>

<style>
.spg-school-settings {
    max-width: 1400px;
}

.spg-settings-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
    margin-top: 20px;
}

.spg-settings-main {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.settings-card {
    background: white;
    border-radius: 8px;
    padding: 25px;
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

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.form-actions {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.logo-upload-area {
    border: 2px dashed #ddd;
    padding: 25px;
    text-align: center;
    border-radius: 4px;
    margin-bottom: 10px;
}

.logo-preview {
    position: relative;
    display: inline-block;
}

.logo-preview img {
    max-height: 100px;
    max-width: 200px;
    border-radius: 4px;
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
    color: #666;
}

.logo-placeholder {
    color: #999;
}

.logo-placeholder i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.logo-placeholder p {
    margin: 0 0 15px 0;
    color: #666;
}

.premium-feature {
    margin-top: 15px;
    padding: 15px;
    background: #fff3e0;
    border-radius: 4px;
    text-align: center;
}

.premium-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
    margin-bottom: 10px;
}

.premium-note {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #ff9800;
}

/* Sidebar styles */
.spg-settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.premium-teaser {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.premium-teaser h3 {
    margin-top: 0;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.trial-info {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.trial-info h4 {
    margin: 0 0 15px 0;
    color: white;
    opacity: 0.9;
}

.trial-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat {
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.9em;
    opacity: 0.8;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
}

.stat-value.warning {
    color: #ffeb3b;
}

.premium-features {
    margin: 25px 0;
}

.premium-features h4 {
    margin: 0 0 15px 0;
    color: white;
    opacity: 0.9;
}

.premium-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.premium-features li {
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    gap: 10px;
}

.premium-features li:last-child {
    border-bottom: none;
}

.premium-features li i {
    color: #4CAF50;
}

.upgrade-cta {
    text-align: center;
}

.upgrade-cta .button-large {
    padding: 12px 25px;
    font-size: 1.1em;
    font-weight: bold;
    background: white;
    color: #f5576c;
    border: none;
    width: 100%;
    margin-bottom: 10px;
}

.upgrade-cta .button-large:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
}

.upgrade-note {
    font-size: 0.9em;
    opacity: 0.9;
    margin: 0;
}

.premium-status {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    color: white;
    border-radius: 8px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.premium-badge.active {
    background: white;
    color: #4CAF50;
    padding: 8px 20px;
    border-radius: 20px;
    display: inline-block;
    margin: 15px 0;
    font-size: 1.1em;
}

.premium-support {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.premium-support h4 {
    margin: 0 0 10px 0;
    color: white;
}

.premium-support p {
    margin: 0 0 15px 0;
    opacity: 0.9;
}

.tools-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 25px;
}

.danger-zone {
    padding: 20px;
    background: #fff5f5;
    border-radius: 6px;
    border: 1px solid #ffcdd2;
}

.danger-zone h4 {
    margin: 0 0 10px 0;
    color: #d32f2f;
}

.danger-zone p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 0.9em;
}

#delete-all-data-btn {
    width: 100%;
    margin-top: 10px;
}

@media (max-width: 1024px) {
    .spg-settings-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .trial-stats {
        grid-template-columns: 1fr;
    }
}
</style>