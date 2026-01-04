<?php
if (!defined('ABSPATH')) exit;

/**
 * Helper function to check if premium features are available
 */
function spg_is_premium_active() {
    return apply_filters('spg_is_premium', false);
}

/**
 * Get plugin settings
 */
function spg_get_setting($key, $default = '') {
    $settings = get_option('spg_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Update plugin settings
 */
function spg_update_setting($key, $value) {
    $settings = get_option('spg_settings', array());
    $settings[$key] = $value;
    update_option('spg_settings', $settings);
}

/**
 * Get school information
 */
function spg_get_school_info() {
    return array(
        'name' => get_option('spg_school_name', get_bloginfo('name')),
        'logo' => get_option('spg_school_logo', ''),
        'address' => get_option('spg_school_address', ''),
        'contact' => get_option('spg_school_contact', ''),
        'email' => get_option('spg_school_email', '')
    );
}

/**
 * Get available subjects
 */
function spg_get_subjects() {
    global $wpdb;
    
    $subjects = $wpdb->get_col("
        SELECT DISTINCT subject 
        FROM {$wpdb->prefix}spg_questions 
        WHERE status = 'active' 
        ORDER BY subject
    ");
    
    if (empty($subjects)) {
        $subjects = array(
            'Mathematics',
            'Science',
            'English',
            'Social Studies',
            'Physics',
            'Chemistry',
            'Biology',
            'History',
            'Geography',
            'Computer Science'
        );
    }
    
    return apply_filters('spg_subjects', $subjects);
}

/**
 * Get available class levels
 */
function spg_get_class_levels() {
    global $wpdb;
    
    $levels = $wpdb->get_col("
        SELECT DISTINCT class_level 
        FROM {$wpdb->prefix}spg_questions 
        WHERE status = 'active' 
        ORDER BY class_level
    ");
    
    if (empty($levels)) {
        $levels = array();
        for ($i = 1; $i <= 12; $i++) {
            $levels[] = sprintf(__('Class %d', 'school-paper-generator'), $i);
        }
    }
    
    return apply_filters('spg_class_levels', $levels);
}

/**
 * Get question type label
 */
function spg_get_question_type_label($type) {
    $types = array(
        'mcq' => __('Multiple Choice', 'school-paper-generator'),
        'short' => __('Short Answer', 'school-paper-generator'),
        'long' => __('Long Answer', 'school-paper-generator'),
        'true_false' => __('True/False', 'school-paper-generator')
    );
    
    return isset($types[$type]) ? $types[$type] : ucfirst($type);
}

/**
 * Get difficulty label
 */
function spg_get_difficulty_label($difficulty) {
    $levels = array(
        'easy' => __('Easy', 'school-paper-generator'),
        'medium' => __('Medium', 'school-paper-generator'),
        'hard' => __('Hard', 'school-paper-generator')
    );
    
    return isset($levels[$difficulty]) ? $levels[$difficulty] : ucfirst($difficulty);
}

/**
 * Get question count by type
 */
function spg_get_question_counts() {
    global $wpdb;
    
    $counts = $wpdb->get_results("
        SELECT 
            question_type,
            COUNT(*) as count,
            SUM(marks) as total_marks
        FROM {$wpdb->prefix}spg_questions 
        WHERE status = 'active'
        GROUP BY question_type
    ", ARRAY_A);
    
    $result = array();
    foreach ($counts as $row) {
        $result[$row['question_type']] = array(
            'count' => intval($row['count']),
            'total_marks' => intval($row['total_marks'])
        );
    }
    
    return $result;
}

/**
 * Generate random paper code
 */
function spg_generate_paper_code() {
    $prefix = 'PAPER';
    $date = date('Ymd');
    $random = substr(strtoupper(md5(uniqid())), 0, 6);
    
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * Format marks with label
 */
function spg_format_marks($marks, $singular = 'mark', $plural = 'marks') {
    $label = ($marks == 1) ? $singular : $plural;
    return sprintf('%d %s', $marks, $label);
}

/**
 * Get paper instructions template
 */
function spg_get_default_instructions() {
    $instructions = array(
        __('All questions are compulsory.', 'school-paper-generator'),
        __('Write your answers neatly and legibly.', 'school-paper-generator'),
        __('Use black or blue pen only.', 'school-paper-generator'),
        __('Read questions carefully before answering.', 'school-paper-generator'),
        __('Check your answers before submitting.', 'school-paper-generator')
    );
    
    return apply_filters('spg_default_instructions', $instructions);
}

/**
 * Log activity
 */
function spg_log_activity($action, $details = '', $user_id = null) {
    global $wpdb;
    
    $user_id = $user_id ?: get_current_user_id();
    
    $wpdb->insert(
        $wpdb->prefix . 'spg_logs',
        array(
            'action' => sanitize_text_field($action),
            'user_id' => intval($user_id),
            'details' => wp_json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => current_time('mysql')
        )
    );
}

/**
 * Check if trial period has expired
 */
function spg_is_trial_expired() {
    if (spg_is_premium_active()) {
        return false;
    }
    
    $installed_date = get_option('spg_installed_date', 0);
    if (!$installed_date) {
        return false;
    }
    
    $trial_days = defined('SPG_TRIAL_DAYS') ? SPG_TRIAL_DAYS : 30;
    $expiry_date = $installed_date + ($trial_days * DAY_IN_SECONDS);
    
    return current_time('timestamp') > $expiry_date;
}

/**
 * Get trial days remaining
 */
function spg_get_trial_days_remaining() {
    if (spg_is_premium_active()) {
        return -1; // Premium active
    }
    
    $installed_date = get_option('spg_installed_date', 0);
    if (!$installed_date) {
        return 0;
    }
    
    $trial_days = defined('SPG_TRIAL_DAYS') ? SPG_TRIAL_DAYS : 30;
    $expiry_date = $installed_date + ($trial_days * DAY_IN_SECONDS);
    $remaining = $expiry_date - current_time('timestamp');
    
    return max(0, ceil($remaining / DAY_IN_SECONDS));
}

/**
 * Get question limit for trial
 */
function spg_get_trial_question_limit() {
    return defined('SPG_MAX_TRIAL_QUESTIONS') ? SPG_MAX_TRIAL_QUESTIONS : 50;
}

/**
 * Get current question count
 */
function spg_get_current_question_count() {
    global $wpdb;
    
    return $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->prefix}spg_questions 
        WHERE status = 'active'
    ");
}

/**
 * Display admin notice for trial version
 */
function spg_display_trial_notice() {
    if (spg_is_premium_active() || !current_user_can('manage_options')) {
        return;
    }
    
    $days_remaining = spg_get_trial_days_remaining();
    $question_count = spg_get_current_question_count();
    $question_limit = spg_get_trial_question_limit();
    
    if ($days_remaining > 0) {
        $message = sprintf(
            __('You have %d days remaining in your School Paper Generator trial. %d/%d questions used.', 'school-paper-generator'),
            $days_remaining,
            $question_count,
            $question_limit
        );
        $class = 'notice-info';
    } else {
        $message = __('Your School Paper Generator trial has expired. Please upgrade to continue using all features.', 'school-paper-generator');
        $class = 'notice-error';
    }
    
    ?>
    <div class="notice <?php echo $class; ?>">
        <p>
            <strong><?php _e('School Paper Generator Trial', 'school-paper-generator'); ?>:</strong> 
            <?php echo $message; ?>
            <a href="https://yourwebsite.com/upgrade" class="button button-small" style="margin-left: 10px;">
                <?php _e('Upgrade Now', 'school-paper-generator'); ?>
            </a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'spg_display_trial_notice');

/**
 * Get paper template options
 */
function spg_get_paper_templates() {
    $templates = array(
        'standard' => array(
            'name' => __('Standard Template', 'school-paper-generator'),
            'description' => __('Basic paper format with sections', 'school-paper-generator'),
            'premium' => false
        ),
        'modern' => array(
            'name' => __('Modern Template', 'school-paper-generator'),
            'description' => __('Contemporary design with better typography', 'school-paper-generator'),
            'premium' => true
        ),
        'compact' => array(
            'name' => __('Compact Template', 'school-paper-generator'),
            'description' => __('Space-saving design for shorter papers', 'school-paper-generator'),
            'premium' => true
        ),
        'elegant' => array(
            'name' => __('Elegant Template', 'school-paper-generator'),
            'description' => __('Professional design with enhanced styling', 'school-paper-generator'),
            'premium' => true
        )
    );
    
    return apply_filters('spg_paper_templates', $templates);
}

/**
 * Get export formats
 */
function spg_get_export_formats() {
    $formats = array(
        'pdf' => array(
            'name' => __('PDF Document', 'school-paper-generator'),
            'icon' => 'far fa-file-pdf',
            'premium' => false
        ),
        'docx' => array(
            'name' => __('Microsoft Word', 'school-paper-generator'),
            'icon' => 'far fa-file-word',
            'premium' => true
        ),
        'xlsx' => array(
            'name' => __('Microsoft Excel', 'school-paper-generator'),
            'icon' => 'far fa-file-excel',
            'premium' => true
        ),
        'html' => array(
            'name' => __('HTML Web Page', 'school-paper-generator'),
            'icon' => 'far fa-file-code',
            'premium' => true
        )
    );
    
    return apply_filters('spg_export_formats', $formats);
}

/**
 * Sanitize question text for display
 */
function spg_sanitize_question_display($text) {
    $text = wp_kses_post($text);
    $text = force_balance_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * Generate answer sheet for MCQ questions
 */
function spg_generate_answer_sheet($questions) {
    if (empty($questions)) {
        return '';
    }
    
    $mcq_questions = array_filter($questions, function($q) {
        return $q['type'] === 'mcq' || $q['type'] === 'true_false';
    });
    
    if (empty($mcq_questions)) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="spg-answer-sheet">
        <h3><?php _e('Answer Sheet', 'school-paper-generator'); ?></h3>
        <table class="spg-answer-table">
            <thead>
                <tr>
                    <th><?php _e('Question', 'school-paper-generator'); ?></th>
                    <th><?php _e('Correct Answer', 'school-paper-generator'); ?></th>
                    <th><?php _e('Explanation', 'school-paper-generator'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mcq_questions as $index => $question): ?>
                <tr>
                    <td>Q<?php echo $index + 1; ?></td>
                    <td><strong><?php echo esc_html($question['correct_answer']); ?></strong></td>
                    <td><?php echo !empty($question['explanation']) ? esc_html($question['explanation']) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get paper statistics
 */
function spg_get_paper_stats($paper_id) {
    global $wpdb;
    
    $paper = $wpdb->get_row($wpdb->prepare("
        SELECT p.*, 
               COUNT(pq.id) as question_count,
               SUM(q.marks) as total_marks
        FROM {$wpdb->prefix}spg_papers p
        LEFT JOIN {$wpdb->prefix}spg_paper_questions pq ON p.id = pq.paper_id
        LEFT JOIN {$wpdb->prefix}spg_questions q ON pq.question_id = q.id
        WHERE p.id = %d
        GROUP BY p.id
    ", $paper_id));
    
    if (!$paper) {
        return false;
    }
    
    $question_types = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_type, COUNT(*) as count, SUM(q.marks) as marks
        FROM {$wpdb->prefix}spg_paper_questions pq
        JOIN {$wpdb->prefix}spg_questions q ON pq.question_id = q.id
        WHERE pq.paper_id = %d
        GROUP BY q.question_type
    ", $paper_id), ARRAY_A);
    
    $type_stats = array();
    foreach ($question_types as $type) {
        $type_stats[$type['question_type']] = array(
            'count' => intval($type['count']),
            'marks' => intval($type['marks'])
        );
    }
    
    return array(
        'paper' => $paper,
        'type_stats' => $type_stats,
        'created_by' => get_userdata($paper->created_by)->display_name ?? 'Unknown',
        'created_date' => date_i18n(get_option('date_format'), strtotime($paper->created_at))
    );
}
?>