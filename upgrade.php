<?php
/**
 * School Paper Generator - Upgrade System
 * Handles premium upgrades, license management, and feature unlocking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SPG_Upgrade_System {
    
    private $license_key;
    private $license_status;
    private $license_expiry;
    private $api_url = 'https://api.taleemguru.com/license/'; // Change to your API URL
    
    public function __construct() {
        $this->license_key = get_option('spg_license_key', '');
        $this->license_status = get_option('spg_license_status', 'inactive');
        $this->license_expiry = get_option('spg_license_expiry', '');
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Add upgrade menu
        add_action('admin_menu', [$this, 'add_upgrade_menu']);
        
        // License verification cron job
        add_action('spg_daily_license_check', [$this, 'check_license_status']);
        
        // AJAX handlers for upgrade process
        add_action('wp_ajax_spg_activate_license', [$this, 'ajax_activate_license']);
        add_action('wp_ajax_spg_deactivate_license', [$this, 'ajax_deactivate_license']);
        add_action('wp_ajax_spg_check_license', [$this, 'ajax_check_license']);
        add_action('wp_ajax_spg_process_upgrade', [$this, 'ajax_process_upgrade']);
        
        // Add admin notices
        add_action('admin_notices', [$this, 'show_license_notices']);
        
        // Filter to enable/disable premium features
        add_filter('spg_is_premium_active', [$this, 'check_premium_status']);
    }
    
    public function add_upgrade_menu() {
        add_submenu_page(
            'spg-dashboard',
            __('Upgrade to Premium', 'school-paper-generator'),
            '<span style="color: #ffb900; font-weight: bold;">★ ' . __('Upgrade', 'school-paper-generator') . '</span>',
            'manage_options',
            'spg-upgrade',
            [$this, 'render_upgrade_page']
        );
    }
    
    public function render_upgrade_page() {
        $current_plan = $this->get_current_plan();
        $license_status = $this->license_status;
        $days_until_expiry = $this->get_days_until_expiry();
        ?>
        <div class="wrap">
            <h1>★ <?php _e('Upgrade to Premium', 'school-paper-generator'); ?></h1>
            
            <?php if ($license_status === 'active'): ?>
                <div class="notice notice-success">
                    <p><strong>✅ <?php _e('Premium License Active', 'school-paper-generator'); ?></strong></p>
                    <p>
                        <?php 
                        printf(
                            __('Your %s license is active. Expires on: %s (%d days remaining)', 'school-paper-generator'),
                            ucfirst($current_plan),
                            $this->license_expiry,
                            $days_until_expiry
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin: 30px 0;">
                <!-- Pricing Plans -->
                <div>
                    <h2><?php _e('Choose Your Plan', 'school-paper-generator'); ?></h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0;">
                        <!-- Basic Plan -->
                        <div style="border: 2px solid #0073aa; border-radius: 10px; overflow: hidden; background: white;">
                            <div style="background: #0073aa; color: white; padding: 20px; text-align: center;">
                                <h3 style="margin: 0; color: white;"><?php _e('Basic', 'school-paper-generator'); ?></h3>
                                <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">$0</div>
                                <div style="font-size: 14px;"><?php _e('Free Forever', 'school-paper-generator'); ?></div>
                            </div>
                            <div style="padding: 20px;">
                                <ul style="margin: 0; padding: 0; list-style: none;">
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('50 Questions Limit', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Basic PDF Export', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('School Name on Papers', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">❌ <?php _e('No Multiple Export Formats', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">❌ <?php _e('No School Logo', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">❌ <?php _e('No Bulk Operations', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0;">❌ <?php _e('No Priority Support', 'school-paper-generator'); ?></li>
                                </ul>
                                <button class="button" style="width: 100%; margin-top: 20px; padding: 12px;" disabled>
                                    <?php _e('Current Plan', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Pro Plan -->
                        <div style="border: 3px solid #ffb900; border-radius: 10px; overflow: hidden; background: white; transform: scale(1.05); box-shadow: 0 10px 30px rgba(255, 184, 0, 0.2);">
                            <div style="background: linear-gradient(135deg, #ffb900 0%, #ff8c00 100%); color: #333; padding: 20px; text-align: center; position: relative;">
                                <div style="position: absolute; top: 10px; right: 10px; background: white; color: #ff8c00; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    <?php _e('MOST POPULAR', 'school-paper-generator'); ?>
                                </div>
                                <h3 style="margin: 0; color: #333;"><?php _e('Professional', 'school-paper-generator'); ?></h3>
                                <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">$49<span style="font-size: 16px; color: #666;">/year</span></div>
                                <div style="font-size: 14px; color: #333;"><?php _e('Billed annually', 'school-paper-generator'); ?></div>
                            </div>
                            <div style="padding: 20px;">
                                <ul style="margin: 0; padding: 0; list-style: none;">
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Unlimited Questions', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Multiple Export Formats', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('School Logo Support', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Bulk Import/Export', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Advanced Analytics', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Paper Templates', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0;">✅ <?php _e('Priority Email Support', 'school-paper-generator'); ?></li>
                                </ul>
                                <button class="button button-primary" style="width: 100%; margin-top: 20px; padding: 12px; background: linear-gradient(135deg, #ffb900 0%, #ff8c00 100%); border: none; font-weight: bold;" onclick="showCheckout('pro')">
                                    <?php _e('Upgrade to Pro', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Enterprise Plan -->
                        <div style="border: 2px solid #764ba2; border-radius: 10px; overflow: hidden; background: white;">
                            <div style="background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); color: white; padding: 20px; text-align: center;">
                                <h3 style="margin: 0; color: white;"><?php _e('Enterprise', 'school-paper-generator'); ?></h3>
                                <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">$199<span style="font-size: 16px; opacity: 0.9;">/year</span></div>
                                <div style="font-size: 14px;"><?php _e('For large institutions', 'school-paper-generator'); ?></div>
                            </div>
                            <div style="padding: 20px;">
                                <ul style="margin: 0; padding: 0; list-style: none;">
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Everything in Pro', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('White-label Branding', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Custom Development', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('API Access', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Multi-site License', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0; border-bottom: 1px solid #eee;">✅ <?php _e('Dedicated Support', 'school-paper-generator'); ?></li>
                                    <li style="padding: 8px 0;">✅ <?php _e('Training Sessions', 'school-paper-generator'); ?></li>
                                </ul>
                                <button class="button button-primary" style="width: 100%; margin-top: 20px; padding: 12px; background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); border: none; font-weight: bold;" onclick="showCheckout('enterprise')">
                                    <?php _e('Get Enterprise', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- License Management -->
                <div>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3><?php _e('License Management', 'school-paper-generator'); ?></h3>
                        
                        <?php if ($license_status === 'active'): ?>
                            <div style="margin: 20px 0;">
                                <p><strong><?php _e('License Key:', 'school-paper-generator'); ?></strong></p>
                                <div style="background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; word-break: break-all;">
                                    <?php echo esc_html($this->license_key); ?>
                                </div>
                                <button class="button" onclick="copyLicenseKey()" style="margin-top: 10px;">
                                    <?php _e('Copy License Key', 'school-paper-generator'); ?>
                                </button>
                            </div>
                            
                            <div style="margin: 20px 0;">
                                <button class="button button-secondary" onclick="checkLicenseStatus()">
                                    <?php _e('Check License Status', 'school-paper-generator'); ?>
                                </button>
                                <button class="button button-link-delete" onclick="deactivateLicense()" style="margin-left: 10px;">
                                    <?php _e('Deactivate License', 'school-paper-generator'); ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <div style="margin: 20px 0;">
                                <p><?php _e('Enter your license key to activate premium features:', 'school-paper-generator'); ?></p>
                                <input type="text" id="license-key-input" placeholder="XXXX-XXXX-XXXX-XXXX" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                <button class="button button-primary" onclick="activateLicense()" style="width: 100%;">
                                    <?php _e('Activate License', 'school-paper-generator'); ?>
                                </button>
                            </div>
                            
                            <div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 0 5px 5px 0;">
                                <p><strong><?php _e('Don\'t have a license key?', 'school-paper-generator'); ?></strong></p>
                                <p><?php _e('Purchase a license from our website:', 'school-paper-generator'); ?></p>
                                <a href="https://taleemguru.com/pricing" target="_blank" class="button" style="width: 100%; text-align: center;">
                                    <?php _e('Get License Key', 'school-paper-generator'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Feature Comparison -->
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
                        <h3><?php _e('Feature Comparison', 'school-paper-generator'); ?></h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                            <tr>
                                <th style="text-align: left; padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('Feature', 'school-paper-generator'); ?></th>
                                <th style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('Free', 'school-paper-generator'); ?></th>
                                <th style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('Pro', 'school-paper-generator'); ?></th>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('Question Limit', 'school-paper-generator'); ?></td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee;">50</td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee; color: #46b450; font-weight: bold;"><?php _e('Unlimited', 'school-paper-generator'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('Export Formats', 'school-paper-generator'); ?></td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee;">PDF</td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee; color: #46b450; font-weight: bold;">PDF, Word, Excel</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php _e('School Logo', 'school-paper-generator'); ?></td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee;">❌</td>
                                <td style="text-align: center; padding: 8px 0; border-bottom: 1px solid #eee; color: #46b450; font-weight: bold;">✅</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0;"><?php _e('Bulk Operations', 'school-paper-generator'); ?></td>
                                <td style="text-align: center; padding: 8px 0;">❌</td>
                                <td style="text-align: center; padding: 8px 0; color: #46b450; font-weight: bold;">✅</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Modal -->
            <div id="checkout-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;"><?php _e('Complete Your Purchase', 'school-paper-generator'); ?></h2>
                        <button onclick="hideCheckout()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                    </div>
                    
                    <div id="checkout-content">
                        <!-- Content will be loaded by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Checkout functions
        function showCheckout(plan) {
            var modal = document.getElementById('checkout-modal');
            var content = document.getElementById('checkout-content');
            
            var plans = {
                'pro': {
                    name: 'Professional Plan',
                    price: '$49/year',
                    features: [
                        'Unlimited Questions',
                        'Multiple Export Formats (PDF, Word, Excel)',
                        'School Logo Support',
                        'Bulk Import/Export',
                        'Advanced Analytics',
                        'Paper Templates',
                        'Priority Email Support'
                    ]
                },
                'enterprise': {
                    name: 'Enterprise Plan',
                    price: '$199/year',
                    features: [
                        'Everything in Professional',
                        'White-label Branding',
                        'Custom Development',
                        'API Access',
                        'Multi-site License',
                        'Dedicated Support',
                        'Training Sessions'
                    ]
                }
            };
            
            var selectedPlan = plans[plan];
            
            var html = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3>${selectedPlan.name}</h3>
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa; margin: 10px 0;">${selectedPlan.price}</div>
                </div>
                
                <div style="margin: 20px 0;">
                    <h4>Included Features:</h4>
                    <ul style="margin: 10px 0 20px 20px;">
                        ${selectedPlan.features.map(feature => `<li>${feature}</li>`).join('')}
                    </ul>
                </div>
                
                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email Address</label>
                    <input type="email" id="checkout-email" placeholder="your@email.com" style="width: 100%; padding: 10px; margin-bottom: 15px;">
                    
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Method</label>
                    <select id="payment-method" style="width: 100%; padding: 10px; margin-bottom: 20px;">
                        <option value="stripe">Credit/Debit Card (Stripe)</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>
                
                <button class="button button-primary" onclick="processPayment('${plan}')" style="width: 100%; padding: 12px; font-size: 16px; font-weight: bold;">
                    Complete Purchase
                </button>
                
                <p style="text-align: center; margin-top: 15px; font-size: 12px; color: #666;">
                    By completing your purchase, you agree to our Terms of Service and Privacy Policy.
                </p>
            `;
            
            content.innerHTML = html;
            modal.style.display = 'flex';
        }
        
        function hideCheckout() {
            document.getElementById('checkout-modal').style.display = 'none';
        }
        
        function processPayment(plan) {
            var email = document.getElementById('checkout-email').value;
            var paymentMethod = document.getElementById('payment-method').value;
            
            if (!email) {
                alert('Please enter your email address');
                return;
            }
            
            // In a real implementation, this would process the payment
            alert('Processing payment for ' + plan + ' plan...\n\nIn a real implementation, this would redirect to payment gateway.\n\nFor now, please visit our website to complete your purchase.');
            hideCheckout();
            
            // Redirect to actual payment page
            window.open('https://taleemguru.com/checkout?plan=' + plan, '_blank');
        }
        
        // License management functions
        function copyLicenseKey() {
            var licenseKey = '<?php echo esc_js($this->license_key); ?>';
            navigator.clipboard.writeText(licenseKey).then(function() {
                alert('License key copied to clipboard!');
            });
        }
        
        function activateLicense() {
            var licenseKey = document.getElementById('license-key-input').value;
            
            if (!licenseKey) {
                alert('Please enter your license key');
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spg_activate_license',
                    license_key: licenseKey,
                    _wpnonce: '<?php echo wp_create_nonce('spg_license_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to activate license');
                    }
                },
                error: function() {
                    alert('Error connecting to server. Please try again.');
                }
            });
        }
        
        function deactivateLicense() {
            if (!confirm('Are you sure you want to deactivate your license? This will disable all premium features.')) {
                return;
            }
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spg_deactivate_license',
                    _wpnonce: '<?php echo wp_create_nonce('spg_license_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to deactivate license');
                    }
                }
            });
        }
        
        function checkLicenseStatus() {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spg_check_license',
                    _wpnonce: '<?php echo wp_create_nonce('spg_license_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('License Status:\n\n' + 
                              'Status: ' + response.data.status + '\n' +
                              'Plan: ' + response.data.plan + '\n' +
                              'Expires: ' + response.data.expires + '\n' +
                              'Days Left: ' + response.data.days_left);
                    } else {
                        alert(response.data.message || 'Failed to check license status');
                    }
                }
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('checkout-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCheckout();
            }
        });
        </script>
        
        <style>
        #checkout-modal {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .feature-comparison tr:hover {
            background: #f5f5f5;
        }
        </style>
        <?php
    }
    
    public function ajax_activate_license() {
        check_ajax_referer('spg_license_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => 'License key is required'));
        }
        
        // Validate license key format
        if (!$this->validate_license_format($license_key)) {
            wp_send_json_error(array('message' => 'Invalid license key format'));
        }
        
        // Check if license is already active on another site
        if ($this->is_license_active_elsewhere($license_key)) {
            wp_send_json_error(array('message' => 'License is already active on another website'));
        }
        
        // In production, this would call your license server
        $validation_result = $this->validate_license_with_server($license_key);
        
        if ($validation_result['valid']) {
            // Save license data
            update_option('spg_license_key', $license_key);
            update_option('spg_license_status', 'active');
            update_option('spg_license_expiry', $validation_result['expiry_date']);
            update_option('spg_license_plan', $validation_result['plan']);
            update_option('spg_license_last_check', current_time('mysql'));
            
            // Enable premium features
            update_option('spg_premium_active', true);
            
            wp_send_json_success(array(
                'message' => 'License activated successfully! Premium features are now enabled.',
                'expiry' => $validation_result['expiry_date'],
                'plan' => $validation_result['plan']
            ));
        } else {
            wp_send_json_error(array(
                'message' => $validation_result['message'] || 'Invalid license key'
            ));
        }
    }
    
    public function ajax_deactivate_license() {
        check_ajax_referer('spg_license_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $license_key = get_option('spg_license_key');
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => 'No active license found'));
        }
        
        // Call server to deactivate
        $deactivation_result = $this->deactivate_license_on_server($license_key);
        
        if ($deactivation_result['success']) {
            // Remove license data
            delete_option('spg_license_key');
            delete_option('spg_license_status');
            delete_option('spg_license_expiry');
            delete_option('spg_license_plan');
            
            // Disable premium features
            update_option('spg_premium_active', false);
            
            wp_send_json_success(array(
                'message' => 'License deactivated successfully. Premium features have been disabled.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => $deactivation_result['message'] || 'Failed to deactivate license'
            ));
        }
    }
    
    public function ajax_check_license() {
        check_ajax_referer('spg_license_nonce', '_wpnonce');
        
        $license_key = get_option('spg_license_key');
        $license_status = get_option('spg_license_status', 'inactive');
        $license_expiry = get_option('spg_license_expiry');
        $license_plan = get_option('spg_license_plan', 'basic');
        
        $days_left = $this->get_days_until_expiry();
        
        wp_send_json_success(array(
            'status' => $license_status,
            'plan' => ucfirst($license_plan),
            'expires' => $license_expiry ? date('F j, Y', strtotime($license_expiry)) : 'N/A',
            'days_left' => $days_left
        ));
    }
    
    public function ajax_process_upgrade() {
        check_ajax_referer('spg_upgrade_nonce', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $plan = sanitize_text_field($_POST['plan']);
        $email = sanitize_email($_POST['email']);
        
        if (!in_array($plan, ['pro', 'enterprise'])) {
            wp_send_json_error(array('message' => 'Invalid plan selected'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
        }
        
        // In production, this would create an order and redirect to payment
        $order_data = array(
            'plan' => $plan,
            'email' => $email,
            'site_url' => site_url(),
            'timestamp' => current_time('mysql')
        );
        
        // Save order temporarily
        $order_id = wp_generate_password(12, false);
        set_transient('spg_upgrade_order_' . $order_id, $order_data, HOUR_IN_SECONDS);
        
        // Return payment URL
        $payment_url = add_query_arg(array(
            'order_id' => $order_id,
            'plan' => $plan
        ), 'https://taleemguru.com/checkout');
        
        wp_send_json_success(array(
            'message' => 'Redirecting to payment...',
            'redirect_url' => $payment_url,
            'order_id' => $order_id
        ));
    }
    
    public function show_license_notices() {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'spg-') === false) {
            return;
        }
        
        $license_status = get_option('spg_license_status', 'inactive');
        $days_left = $this->get_days_until_expiry();
        
        if ($license_status === 'active' && $days_left <= 7) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>⚠ <?php _e('License Expiring Soon', 'school-paper-generator'); ?></strong>
                    <?php 
                    printf(
                        __('Your premium license expires in %d days. %sRenew now%s to continue using premium features.', 'school-paper-generator'),
                        $days_left,
                        '<a href="' . admin_url('admin.php?page=spg-upgrade') . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        } elseif ($license_status === 'expired') {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>❌ <?php _e('License Expired', 'school-paper-generator'); ?></strong>
                    <?php 
                    printf(
                        __('Your premium license has expired. Premium features have been disabled. %sRenew now%s.', 'school-paper-generator'),
                        '<a href="' . admin_url('admin.php?page=spg-upgrade') . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        } elseif ($license_status === 'inactive') {
            global $wpdb;
            $question_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
            $question_limit = get_option('spg_question_limit', 50);
            
            if ($question_count >= $question_limit * 0.9) { // 90% of limit
                ?>
                <div class="notice notice-info">
                    <p>
                        <strong>📊 <?php _e('Question Limit Almost Reached', 'school-paper-generator'); ?></strong>
                        <?php 
                        printf(
                            __('You have used %d of %d questions. %sUpgrade to premium%s for unlimited questions and advanced features.', 'school-paper-generator'),
                            $question_count,
                            $question_limit,
                            '<a href="' . admin_url('admin.php?page=spg-upgrade') . '">',
                            '</a>'
                        );
                        ?>
                    </p>
                </div>
                <?php
            }
        }
    }
    
    public function check_premium_status($status) {
        $license_status = get_option('spg_license_status', 'inactive');
        $license_expiry = get_option('spg_license_expiry');
        
        if ($license_status === 'active' && $license_expiry) {
            $expiry_timestamp = strtotime($license_expiry);
            if ($expiry_timestamp > time()) {
                return true;
            } else {
                // License expired
                update_option('spg_license_status', 'expired');
                update_option('spg_premium_active', false);
                return false;
            }
        }
        
        return false;
    }
    
    private function validate_license_format($license_key) {
        // Format: XXXX-XXXX-XXXX-XXXX
        $pattern = '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i';
        return preg_match($pattern, $license_key);
    }
    
    private function validate_license_with_server($license_key) {
        // In production, this would make an API call to your license server
        // For demo purposes, we'll simulate validation
        
        // Simulate API call delay
        sleep(1);
        
        // Mock validation - In real implementation, remove this
        if (strpos($license_key, 'DEMO') !== false) {
            return array(
                'valid' => true,
                'plan' => 'pro',
                'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'message' => 'License validated successfully (Demo)'
            );
        }
        
        // Default mock response
        return array(
            'valid' => true,
            'plan' => 'pro',
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'message' => 'License validated successfully'
        );
    }
    
    private function deactivate_license_on_server($license_key) {
        // In production, this would make an API call
        sleep(1);
        
        return array(
            'success' => true,
            'message' => 'License deactivated successfully'
        );
    }
    
    private function is_license_active_elsewhere($license_key) {
        // In production, check with your license server
        return false;
    }
    
    private function get_current_plan() {
        $plan = get_option('spg_license_plan', 'basic');
        
        $plans = array(
            'basic' => __('Basic', 'school-paper-generator'),
            'pro' => __('Professional', 'school-paper-generator'),
            'enterprise' => __('Enterprise', 'school-paper-generator')
        );
        
        return isset($plans[$plan]) ? $plans[$plan] : $plans['basic'];
    }
    
    private function get_days_until_expiry() {
        $expiry_date = get_option('spg_license_expiry');
        
        if (!$expiry_date) {
            return 0;
        }
        
        $expiry_timestamp = strtotime($expiry_date);
        $current_timestamp = time();
        
        if ($expiry_timestamp <= $current_timestamp) {
            return 0;
        }
        
        $seconds_left = $expiry_timestamp - $current_timestamp;
        $days_left = floor($seconds_left / (60 * 60 * 24));
        
        return max(0, $days_left);
    }
    
    public function check_license_status() {
        $license_key = get_option('spg_license_key');
        
        if (empty($license_key)) {
            return;
        }
        
        // Call license server to check status
        $status = $this->validate_license_with_server($license_key);
        
        if (!$status['valid']) {
            update_option('spg_license_status', 'expired');
            update_option('spg_premium_active', false);
        } else {
            update_option('spg_license_expiry', $status['expiry_date']);
            update_option('spg_license_plan', $status['plan']);
            update_option('spg_license_last_check', current_time('mysql'));
        }
    }
}

// Initialize upgrade system
function spg_init_upgrade_system() {
    new SPG_Upgrade_System();
}
add_action('plugins_loaded', 'spg_init_upgrade_system');

// Schedule daily license check
function spg_schedule_license_check() {
    if (!wp_next_scheduled('spg_daily_license_check')) {
        wp_schedule_event(time(), 'daily', 'spg_daily_license_check');
    }
}
register_activation_hook(__FILE__, 'spg_schedule_license_check');

// Cleanup on deactivation
function spg_unschedule_license_check() {
    wp_clear_scheduled_hook('spg_daily_license_check');
}
register_deactivation_hook(__FILE__, 'spg_unschedule_license_check');