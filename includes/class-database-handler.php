<?php
if (!defined('ABSPATH')) exit;

class SPG_Database_Handler {
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            'questions' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_questions (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                question_text LONGTEXT NOT NULL,
                question_type ENUM('mcq','short','long','true_false') NOT NULL,
                subject VARCHAR(100) NOT NULL,
                class_level VARCHAR(50) NOT NULL,
                chapter VARCHAR(100),
                topic VARCHAR(100),
                marks INT(11) DEFAULT 1,
                difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
                options LONGTEXT,
                correct_answer LONGTEXT,
                explanation LONGTEXT,
                created_by BIGINT(20) UNSIGNED,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status ENUM('active','inactive','draft') DEFAULT 'active',
                PRIMARY KEY (id),
                INDEX subject_idx (subject),
                INDEX class_level_idx (class_level),
                INDEX type_idx (question_type)
            ) $charset_collate;",
            
            'papers' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_papers (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                paper_title VARCHAR(255) NOT NULL,
                paper_code VARCHAR(50) UNIQUE,
                subject VARCHAR(100) NOT NULL,
                class_level VARCHAR(50) NOT NULL,
                total_marks INT(11) DEFAULT 100,
                time_duration VARCHAR(50),
                instructions TEXT,
                paper_data LONGTEXT NOT NULL,
                school_name VARCHAR(255),
                school_logo VARCHAR(500),
                school_address TEXT,
                created_by BIGINT(20) UNSIGNED,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status ENUM('draft','published','archived') DEFAULT 'draft',
                PRIMARY KEY (id),
                INDEX paper_code_idx (paper_code),
                INDEX subject_idx (subject)
            ) $charset_collate;",
            
            'paper_questions' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_paper_questions (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                paper_id BIGINT(20) UNSIGNED NOT NULL,
                question_id BIGINT(20) UNSIGNED NOT NULL,
                question_order INT(11) DEFAULT 0,
                section VARCHAR(50),
                marks INT(11),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY paper_question (paper_id, question_id),
                FOREIGN KEY (paper_id) REFERENCES {$wpdb->prefix}spg_papers(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES {$wpdb->prefix}spg_questions(id) ON DELETE CASCADE
            ) $charset_collate;",
            
            'settings' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_settings (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value LONGTEXT,
                setting_group VARCHAR(50) DEFAULT 'general',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX setting_key_idx (setting_key)
            ) $charset_collate;",
            
            'logs' => "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spg_logs (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                action VARCHAR(100) NOT NULL,
                user_id BIGINT(20) UNSIGNED,
                details TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX action_idx (action),
                INDEX user_idx (user_id)
            ) $charset_collate;"
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table) {
            dbDelta($table);
        }
        
        $this->insert_default_data();
    }
    
    private function insert_default_data() {
        global $wpdb;
        
        // Insert default subjects if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spg_questions");
        
        if ($count == 0) {
            $default_questions = array(
                array(
                    'question_text' => 'What is the capital of France?',
                    'question_type' => 'mcq',
                    'subject' => 'Geography',
                    'class_level' => 'Class 8',
                    'options' => json_encode(array('London', 'Berlin', 'Paris', 'Madrid')),
                    'correct_answer' => 'Paris',
                    'marks' => 1,
                    'difficulty' => 'easy'
                ),
                array(
                    'question_text' => 'Explain photosynthesis in plants.',
                    'question_type' => 'long',
                    'subject' => 'Biology',
                    'class_level' => 'Class 10',
                    'marks' => 5,
                    'difficulty' => 'medium'
                )
            );
            
            foreach ($default_questions as $question) {
                $wpdb->insert("{$wpdb->prefix}spg_questions", $question);
            }
        }
    }
    
    public function get_questions($filters = array()) {
        global $wpdb;
        
        $where = array('1=1');
        $params = array();
        
        if (!empty($filters['subject'])) {
            $where[] = "subject = %s";
            $params[] = $filters['subject'];
        }
        
        if (!empty($filters['class_level'])) {
            $where[] = "class_level = %s";
            $params[] = $filters['class_level'];
        }
        
        if (!empty($filters['question_type'])) {
            $where[] = "question_type = %s";
            $params[] = $filters['question_type'];
        }
        
        if (!empty($filters['difficulty'])) {
            $where[] = "difficulty = %s";
            $params[] = $filters['difficulty'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(question_text LIKE %s OR topic LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM {$wpdb->prefix}spg_questions WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
    }
    
    public function save_paper($data) {
        global $wpdb;
        
        $paper_data = array(
            'paper_title' => sanitize_text_field($data['title']),
            'paper_code' => $this->generate_paper_code(),
            'subject' => sanitize_text_field($data['subject']),
            'class_level' => sanitize_text_field($data['class_level']),
            'total_marks' => intval($data['total_marks']),
            'time_duration' => sanitize_text_field($data['time_duration']),
            'instructions' => wp_kses_post($data['instructions']),
            'paper_data' => json_encode($data['questions']),
            'school_name' => sanitize_text_field($data['school_name']),
            'school_logo' => esc_url_raw($data['school_logo']),
            'school_address' => sanitize_textarea_field($data['school_address']),
            'created_by' => get_current_user_id(),
            'status' => 'published'
        );
        
        $result = $wpdb->insert("{$wpdb->prefix}spg_papers", $paper_data);
        
        if ($result) {
            $paper_id = $wpdb->insert_id;
            
            // Save paper questions relationships
            foreach ($data['questions'] as $order => $question) {
                $wpdb->insert("{$wpdb->prefix}spg_paper_questions", array(
                    'paper_id' => $paper_id,
                    'question_id' => $question['id'],
                    'question_order' => $order,
                    'section' => $question['section'] ?? 'main',
                    'marks' => $question['marks'] ?? 1
                ));
            }
            
            return $paper_id;
        }
        
        return false;
    }
    
    private function generate_paper_code() {
        return 'PAPER-' . date('Ymd') . '-' . substr(md5(uniqid()), 0, 6);
    }
    
    public function log_action($action, $details = '', $user_id = null) {
        global $wpdb;
        
        $user_id = $user_id ?: get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $wpdb->insert("{$wpdb->prefix}spg_logs", array(
            'action' => $action,
            'user_id' => $user_id,
            'details' => $details,
            'ip_address' => $ip_address
        ));
    }
}
?>