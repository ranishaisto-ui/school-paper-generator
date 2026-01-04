<?php
// Premium features placeholder
// This file will be included if premium version is detected

function spg_premium_features_init() {
    // Check if premium is active
    if (!get_option('spg_premium_active')) {
        return;
    }
    
    // Add premium menu items
    add_action('admin_menu', 'spg_add_premium_menus');
    
    // Add premium features
    add_filter('spg_export_options', 'spg_add_premium_export_options');
    add_action('spg_paper_header', 'spg_add_school_logo');
}

function spg_add_premium_menus() {
    add_submenu_page(
        'spg-dashboard',
        'Premium Features',
        '<span style="color: #ffb900;">★ Premium</span>',
        'manage_options',
        'spg-premium',
        'spg_premium_page'
    );
}

function spg_premium_page() {
    ?>
    <div class="wrap">
        <h1>Premium Features</h1>
        
        <div class="spg-form-container">
            <h2 style="color: #ffb900;">★ Premium Features Active</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Unlimited Questions</h3>
                    <p>No limits on question bank size.</p>
                </div>
                
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Multiple Export Formats</h3>
                    <p>Export as Word, Excel, and PDF.</p>
                </div>
                
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>School Logo</h3>
                    <p>Add your school logo to papers.</p>
                </div>
                
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Bulk Operations</h3>
                    <p>Import/export questions in bulk.</p>
                </div>
                
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Advanced Analytics</h3>
                    <p>Detailed usage statistics.</p>
                </div>
                
                <div style="border: 2px solid #46b450; border-radius: 8px; padding: 20px; text-align: center;">
                    <h3>Priority Support</h3>
                    <p>Dedicated support team.</p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function spg_add_premium_export_options($options) {
    $premium_options = array(
        'word' => 'Microsoft Word (.docx)',
        'excel' => 'Microsoft Excel (.xlsx)',
        'html' => 'HTML Document'
    );
    
    return array_merge($options, $premium_options);
}

function spg_add_school_logo($paper_id) {
    $logo_url = get_option('spg_school_logo');
    
    if ($logo_url) {
        echo '<div class="school-logo" style="text-align: center; margin: 20px 0;">';
        echo '<img src="' . esc_url($logo_url) . '" alt="School Logo" style="max-height: 80px;">';
        echo '</div>';
    }
}

// Initialize premium features
add_action('plugins_loaded', 'spg_premium_features_init');