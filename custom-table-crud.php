<?php
/**
 * Plugin Name: Custom Table CRUD
 * Description: CRUD for custom DB tables with working pagination inside shortcodes.
 * Version: 2.1
 * Author: affigabmag
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-table-crud
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_TABLE_CRUD_VERSION', '2.0');
define('CUSTOM_TABLE_CRUD_PATH', plugin_dir_path(__FILE__));
define('CUSTOM_TABLE_CRUD_URL', plugin_dir_url(__FILE__));

// Autoloader function
function custom_table_crud_autoloader($class) {
    $prefix = 'CustomTableCRUD\\';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $class = str_replace($prefix, '', $class);
    $class = strtolower($class);
    $class = str_replace('_', '-', $class);
    $file = CUSTOM_TABLE_CRUD_PATH . 'includes/class-' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('custom_table_crud_autoloader');

// Initialize the plugin
function custom_table_crud_init() {
    $plugin = new CustomTableCRUD\Plugin_Core();
    $plugin->init();
}
add_action('plugins_loaded', 'custom_table_crud_init');

// Activation hook
register_activation_hook(__FILE__, 'CustomTableCRUD\Plugin_Core::activate');

// Deactivation hook
register_deactivation_hook(__FILE__, 'CustomTableCRUD\Plugin_Core::deactivate');