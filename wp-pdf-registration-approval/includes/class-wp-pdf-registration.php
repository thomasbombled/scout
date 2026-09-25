<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_PDF_Registration {

    public static function activate() {
        self::register_custom_post_type();
        WP_PDF_Security::init_secure_directory();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function register_custom_post_type() {
        $labels = array(
            'name'               => 'Inscriptions PDF',
            'singular_name'      => 'Inscription PDF',
            'menu_name'          => 'Inscriptions PDF',
            'name_admin_bar'     => 'Inscription PDF',
            'add_new'            => 'Ajouter nouvelle',
            'add_new_item'       => 'Ajouter une inscription',
            'new_item'           => 'Nouvelle inscription',
            'edit_item'          => 'Éditer l\'inscription',
            'view_item'          => 'Voir l\'inscription',
            'all_items'          => 'Toutes les inscriptions',
            'search_items'       => 'Rechercher des inscriptions',
            'not_found'          => 'Aucune inscription trouvée',
            'not_found_in_trash' => 'Aucune inscription dans la corbeille'
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'menu_icon'           => 'dashicons-email-alt',
            'show_in_rest'        => false
        );

        register_post_type('pdf_registration', $args);
    }

    public function run() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        require_once WP_PDF_REG_PATH . 'includes/class-wp-pdf-form-handler.php';
        require_once WP_PDF_REG_PATH . 'includes/class-wp-pdf-admin.php';
        require_once WP_PDF_REG_PATH . 'includes/class-wp-pdf-mailer.php';

        $form_handler = new WP_PDF_Form_Handler();
        $form_handler->init();

        $admin = new WP_PDF_Admin();
        $admin->init();
    }

    public function init() {
        self::register_custom_post_type();
        WP_PDF_Security::init_secure_directory();
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'wp-pdf-reg-style',
            WP_PDF_REG_URL . 'assets/css/form-style.css',
            array(),
            WP_PDF_REG_VERSION
        );

        wp_enqueue_script(
            'wp-pdf-reg-script',
            WP_PDF_REG_URL . 'assets/js/form-script.js',
            array('jquery'),
            WP_PDF_REG_VERSION,
            true
        );

        wp_localize_script('wp-pdf-reg-script', 'wpPdfRegAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wp_pdf_reg_nonce')
        ));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'pdf_registration') !== false || strpos($hook, 'wp-pdf-registration-settings') !== false) {
            wp_enqueue_style('wp-pdf-admin-style', WP_PDF_REG_URL . 'assets/css/admin-style.css', array(), WP_PDF_REG_VERSION);
            wp_enqueue_media();
        }
    }
}
