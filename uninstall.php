<?php
// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin data if option is set
if (get_option('spg_delete_data_on_uninstall') === 'yes') {
    global $wpdb;
    
    // Delete tables
    $tables = array(
        $wpdb->prefix . 'spg_questions',
        $wpdb->prefix . 'spg_papers',
        $wpdb->prefix . 'spg_paper_questions',
        $wpdb->prefix . 'spg_settings',
        $wpdb->prefix . 'spg_logs'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Delete options
    $options = array(
        'spg_version',
        'spg_installed_date',
        'spg_school_name',
        'spg_max_marks',
        'spg_time_duration',
        'spg_instructions',
        'spg_enable_trial',
        'spg_delete_data_on_uninstall'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'spg_%'");
    
    // Delete uploaded files
    $upload_dir = wp_upload_dir();
    $spg_dir = $upload_dir['basedir'] . '/school-paper-generator/';
    
    if (file_exists($spg_dir)) {
        $this->deleteDirectory($spg_dir);
    }
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteDirectory("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}
?>
