<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_PDF_Security {

    public static function get_secure_dir() {
        $upload_dir = wp_upload_dir();
        $secure_dir = $upload_dir['basedir'] . '/wp-pdf-registration-secure/';
        return $secure_dir;
    }

    public static function init_secure_directory() {
        $secure_dir = self::get_secure_dir();

        if (!file_exists($secure_dir)) {
            wp_mkdir_p($secure_dir);
        }

        // Protect directory with .htaccess
        $htaccess_file = $secure_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Protect directory with empty index.php
        $index_file = $secure_dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n// Silence is golden.");
        }
    }

    public static function generate_secure_token($post_id, $email) {
        $secret = wp_salt('nonce');
        return hash_hmac('sha256', $post_id . '|' . $email . '|' . time(), $secret);
    }

    public static function verify_secure_token($post_id, $token) {
        $saved_token = get_post_meta($post_id, '_pdf_reg_access_token', true);
        if (!$saved_token) {
            return false;
        }
        return hash_equals($saved_token, $token);
    }
}
