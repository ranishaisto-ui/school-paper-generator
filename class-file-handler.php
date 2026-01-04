<?php
if (!defined('ABSPATH')) exit;

class SPG_File_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get_upload_dir() {
        $upload_dir = wp_upload_dir();
        $spg_dir = $upload_dir['basedir'] . '/school-paper-generator/';
        
        if (!file_exists($spg_dir)) {
            wp_mkdir_p($spg_dir);
        }
        
        return array(
            'path' => $spg_dir,
            'url' => $upload_dir['baseurl'] . '/school-paper-generator/'
        );
    }
    
    public function create_temp_file($content, $extension = 'tmp') {
        $upload_dir = $this->get_upload_dir();
        $temp_dir = $upload_dir['path'] . 'temp/';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $filename = uniqid('spg_') . '.' . $extension;
        $filepath = $temp_dir . $filename;
        
        if (file_put_contents($filepath, $content) !== false) {
            return array(
                'path' => $filepath,
                'url' => $upload_dir['url'] . 'temp/' . $filename,
                'filename' => $filename
            );
        }
        
        return false;
    }
    
    public function save_export_file($content, $filename, $subdir = '') {
        $upload_dir = $this->get_upload_dir();
        
        if ($subdir) {
            $save_dir = $upload_dir['path'] . $subdir . '/';
            if (!file_exists($save_dir)) {
                wp_mkdir_p($save_dir);
            }
        } else {
            $save_dir = $upload_dir['path'];
        }
        
        $filepath = $save_dir . $filename;
        
        if (file_put_contents($filepath, $content) !== false) {
            return array(
                'path' => $filepath,
                'url' => $upload_dir['url'] . ($subdir ? $subdir . '/' : '') . $filename,
                'filename' => $filename
            );
        }
        
        return false;
    }
    
    public function delete_file($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    public function cleanup_temp_files($older_than_hours = 24) {
        $upload_dir = $this->get_upload_dir();
        $temp_dir = $upload_dir['path'] . 'temp/';
        
        if (!file_exists($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . '*');
        $now = time();
        $cutoff = $older_than_hours * 3600;
        
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $cutoff) {
                unlink($file);
            }
        }
        
        // Clean up empty directories
        $this->remove_empty_directories($temp_dir);
    }
    
    private function remove_empty_directories($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        
        if (count($files) === 0) {
            rmdir($dir);
            return;
        }
        
        foreach ($files as $file) {
            $filepath = $dir . '/' . $file;
            if (is_dir($filepath)) {
                $this->remove_empty_directories($filepath);
            }
        }
        
        // Check again if directory is now empty
        $files = scandir($dir);
        $files = array_diff($files, array('.', '..'));
        
        if (count($files) === 0) {
            rmdir($dir);
        }
    }
    
    public function get_file_size($filepath) {
        if (!file_exists($filepath)) {
            return 0;
        }
        
        $bytes = filesize($filepath);
        
        // Convert to human readable format
        $units = array('B', 'KB', 'MB', 'GB');
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function get_file_extension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    public function is_allowed_file_type($filename, $allowed_types = array()) {
        $extension = $this->get_file_extension($filename);
        
        if (empty($allowed_types)) {
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'csv', 'json', 'xlsx', 'xls');
        }
        
        return in_array($extension, $allowed_types);
    }
    
    public function generate_unique_filename($original_filename, $directory = '') {
        $extension = $this->get_file_extension($original_filename);
        $basename = basename($original_filename, '.' . $extension);
        
        // Clean filename
        $basename = sanitize_file_name($basename);
        $basename = preg_replace('/[^a-zA-Z0-9-_]/', '', $basename);
        
        $counter = 1;
        $filename = $basename . '.' . $extension;
        
        if ($directory) {
            $upload_dir = $this->get_upload_dir();
            $directory = $upload_dir['path'] . $directory . '/';
            
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
            }
            
            while (file_exists($directory . $filename)) {
                $filename = $basename . '-' . $counter . '.' . $extension;
                $counter++;
            }
        }
        
        return $filename;
    }
    
    public function download_file($filepath, $filename = '') {
        if (!file_exists($filepath)) {
            return false;
        }
        
        if (empty($filename)) {
            $filename = basename($filepath);
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
}
?>