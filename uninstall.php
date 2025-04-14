<?php
/**
 * Plugin uninstall file
 *
 * @package CustomTableCRUD
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options
delete_option('custom_table_crud_version');

// Clean up any transients
delete_transient('custom_table_crud_table_list');

// Remove debug logs if they exist
$debug_log = WP_CONTENT_DIR . '/plugins/custom-table-crud/debug/shortcode_debug.log';
if (file_exists($debug_log)) {
    @unlink($debug_log);
}

// Note: This plugin does not create any database tables, so no need to remove any