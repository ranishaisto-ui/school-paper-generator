<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap spg-dashboard">
    <div class="spg-header">
        <h1><i class="fas fa-graduation-cap"></i> <?php _e('School Paper Generator', 'school-paper-generator'); ?></h1>
        <p class="description"><?php _e('Create professional exam papers for your school', 'school-paper-generator'); ?></p>
    </div>
    
    <?php if (!spg_is_premium_active()): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('Trial Version Active', 'school-paper-generator'); ?></strong> - 
            <?php 
            $installed_date = get_option('spg_installed_date');
            $days_used = floor((current_time('timestamp') - $installed_date) / DAY_IN_SECONDS);
            $days_left = SPG_TRIAL_DAYS - $days_used;
            
            if ($days_left > 0) {
                printf(__('You have %d days remaining in your trial.', 'school-paper-generator'), $days_left);
            } else {
                _e('Your trial period has ended. Please upgrade to continue.', 'school-paper-generator');
            }
            ?>
            <a href="https://yourwebsite.com/upgrade" class="button button-small"><?php _e('Upgrade Now', 'school-paper-generator'); ?></a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="spg-stats-row">
        <div class="spg-stat-card">
            <div class="stat-icon" style="background: #4CAF50;">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_questions); ?></h3>
                <p><?php _e('Total Questions', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="spg-stat-card">
            <div class="stat-icon" style="background: #2196F3;">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_papers); ?></h3>
                <p><?php _e('Generated Papers', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="spg-stat-card">
            <div class="stat-icon" style="background: #FF9800;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($subjects); ?></h3>
                <p><?php _e('Subjects', 'school-paper-generator'); ?></p>
            </div>
        </div>
        
        <div class="spg-stat-card">
            <div class="stat-icon" style="background: #9C27B0;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo count($class_levels); ?></h3>
                <p><?php _e('Class Levels', 'school-paper-generator'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="spg-quick-actions">
        <h2><?php _e('Quick Actions', 'school-paper-generator'); ?></h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=spg-create-paper'); ?>" class="button button-primary button-hero">
                <i class="fas fa-plus-circle"></i> <?php _e('Create New Paper', 'school-paper-generator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=spg-question-bank'); ?>" class="button button-secondary button-hero">
                <i class="fas fa-database"></i> <?php _e('Manage Question Bank', 'school-paper-generator'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=spg-import-export'); ?>" class="button button-secondary button-hero">
                <i class="fas fa-exchange-alt"></i> <?php _e('Import/Export', 'school-paper-generator'); ?>
            </a>
        </div>
    </div>
    
    <div class="spg-recent-papers">
        <h2><?php _e('Recently Generated Papers', 'school-paper-generator'); ?></h2>
        <?php if (!empty($recent_papers)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Paper Title', 'school-paper-generator'); ?></th>
                    <th><?php _e('Subject', 'school-paper-generator'); ?></th>
                    <th><?php _e('Class', 'school-paper-generator'); ?></th>
                    <th><?php _e('Total Marks', 'school-paper-generator'); ?></th>
                    <th><?php _e('Date', 'school-paper-generator'); ?></th>
                    <th><?php _e('Actions', 'school-paper-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_papers as $paper): ?>
                <tr>
                    <td><?php echo esc_html($paper->paper_title); ?></td>
                    <td><?php echo esc_html($paper->subject); ?></td>
                    <td><?php echo esc_html($paper->class_level); ?></td>
                    <td><?php echo $paper->total_marks; ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($paper->created_at)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=spg-create-paper&paper_id=' . $paper->id); ?>" 
                           class="button button-small">
                           <i class="fas fa-edit"></i> <?php _e('Edit', 'school-paper-generator'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=spg_export_paper&format=pdf&paper_id=' . $paper->id); ?>" 
                           class="button button-small" target="_blank">
                           <i class="fas fa-download"></i> <?php _e('PDF', 'school-paper-generator'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p><?php _e('No papers generated yet. Create your first paper!', 'school-paper-generator'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="spg-feature-grid">
        <div class="spg-feature-card">
            <h3><i class="fas fa-magic"></i> <?php _e('Smart Paper Generation', 'school-paper-generator'); ?></h3>
            <p><?php _e('Automatically generate papers based on subject, class, and difficulty level.', 'school-paper-generator'); ?></p>
        </div>
        
        <div class="spg-feature-card">
            <h3><i class="fas fa-random"></i> <?php _e('Question Randomization', 'school-paper-generator'); ?></h3>
            <p><?php _e('Shuffle questions and MCQ options to create unique papers every time.', 'school-paper-generator'); ?></p>
        </div>
        
        <div class="spg-feature-card">
            <h3><i class="fas fa-school"></i> <?php _e('School Branding', 'school-paper-generator'); ?></h3>
            <p><?php _e('Add your school logo, name, and address to all generated papers.', 'school-paper-generator'); ?></p>
        </div>
        
        <div class="spg-feature-card">
            <h3><i class="fas fa-file-export"></i> <?php _e('Multiple Formats', 'school-paper-generator'); ?></h3>
            <p><?php _e('Export papers in PDF, Word, and Excel formats for easy distribution.', 'school-paper-generator'); ?></p>
        </div>
    </div>
    
    <?php do_action('spg_admin_after_features'); ?>
</div>