<?php
/**
 * Plugin Name: Registration Approval & PDF Delivery
 * Plugin URI:  https://example.com/wp-pdf-registration-approval
 * Description: Un plugin WordPress compatible Divi 4 permettant de recevoir un document PDF par email après inscription sur la page d'accueil et validation par un administrateur.
 * Version:     1.1.0
 * Author:      Jules Software
 * Text Domain: wp-pdf-registration-approval
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('WP_PDF_REG_VERSION', '1.1.0');
define('WP_PDF_REG_PATH', plugin_dir_path(__FILE__));
define('WP_PDF_REG_URL', plugin_dir_url(__FILE__));

require_once WP_PDF_REG_PATH . 'includes/class-wp-pdf-security.php';
require_once WP_PDF_REG_PATH . 'includes/class-wp-pdf-registration.php';

function run_wp_pdf_registration_approval() {
    $plugin = new WP_PDF_Registration();
    $plugin->run();
}

register_activation_hook(__FILE__, array('WP_PDF_Registration', 'activate'));
register_deactivation_hook(__FILE__, array('WP_PDF_Registration', 'deactivate'));

run_wp_pdf_registration_approval();
