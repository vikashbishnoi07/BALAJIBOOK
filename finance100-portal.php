<?php
/**
 * Plugin Name: Finance100 Customer Portal
 * Plugin URI:  https://example.com/finance100
 * Description: Secure role-based finance portal with 100-day payment schedules and private customer documents.
 * Version:     1.0.0
 * Author:      Finance100
 * Text Domain: finance100
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('F100_VERSION', '1.0.0');
define('F100_FILE', __FILE__);
define('F100_PATH', plugin_dir_path(__FILE__));
define('F100_URL', plugin_dir_url(__FILE__));

require_once F100_PATH . 'includes/class-f100-activator.php';
require_once F100_PATH . 'includes/class-f100-admin.php';
require_once F100_PATH . 'includes/class-f100-portal.php';

register_activation_hook(__FILE__, array('F100_Activator', 'activate'));

function f100_boot_plugin() {
    F100_Admin::init();
    F100_Portal::init();
}
add_action('plugins_loaded', 'f100_boot_plugin');

