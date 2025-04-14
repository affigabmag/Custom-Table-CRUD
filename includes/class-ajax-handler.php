<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX requests for the plugin
 * 
 * @since 2.0.0
 */
class Ajax_Handler {
    /**
     * Constructor - register AJAX hooks
     */
    public function __construct() {
        add_action('wp_ajax_get_table_fields', array($this, 'get_table_fields'));
        add_action('wp_ajax_delete_record', array($this, 'delete_record'));
    }
    
    /**
     * Get table fields and return as HTML
     * 
     * @return void Outputs HTML and exits
     */
    public function get_table_fields() {
        // Check if nonce exists first
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Security check failed: Missing nonce', 'custom-table-crud')
            ));
        }
        
        // Verify the nonce with less strict check
        if (!wp_verify_nonce($_POST['nonce'], 'custom_crud_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed: Invalid nonce', 'custom-table-crud')
            ));
        }
        
        // Get table name
        $table = sanitize_text_field($_POST['table'] ?? '');
        if (empty($table)) {
            wp_send_json_error(array(
                'message' => __('No table specified', 'custom-table-crud')
            ));
        }
        
        global $wpdb;
        
        // Direct SQL query for SHOW COLUMNS - avoiding prepared statement for this case
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table}");
        
        if (empty($columns)) {
            wp_send_json_error(array(
                'message' => __('No columns found or table does not exist', 'custom-table-crud')
            ));
        }
        
        ob_start();
        
        foreach ($columns as $col) {
            $name = esc_attr($col->Field);
            
            // Determine appropriate input type based on column type
            $type = 'text';
            if (strpos($col->Type, 'int') !== false || strpos($col->Type, 'float') !== false || strpos($col->Type, 'decimal') !== false) {
                $type = 'number';
            } elseif (strpos($col->Type, 'date') !== false) {
                $type = 'date';
            } elseif (strpos($col->Type, 'text') !== false || strpos($col->Type, 'longtext') !== false) {
                $type = 'textarea';
            }
            
            echo '<div class="field-wrapper">';
            echo '<label><input type="checkbox" class="field-checkbox" value="' . esc_attr($name) . '" checked> ' . esc_html($name) . '</label>';
            
            echo '<input type="text" name="displayname_' . esc_attr($name) . '" value="' . esc_attr($name) . '" placeholder="' . esc_attr__('Display Name', 'custom-table-crud') . '">';
            
            echo '<select name="type_' . esc_attr($name) . '">';
            $types = ['text', 'number', 'date', 'datetime', 'textarea', 'email', 'url', 'tel', 'password'];
            foreach ($types as $t) {
                $selected = ($type === $t) ? ' selected' : '';
                echo '<option value="' . esc_attr($t) . '"' . $selected . '>' . esc_html($t) . '</option>';
            }
            echo '</select>';
            
            echo '<label class="readonly-label">';
            echo '<input type="checkbox" name="readonly_' . esc_attr($name) . '" class="readonly-checkbox">';
            echo esc_html__('Read Only', 'custom-table-crud');
            echo '</label>';
            
            echo '</div>';
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'message' => __('Fields loaded successfully', 'custom-table-crud')
        ));
    }
    
    /**
     * Handle AJAX record deletion
     * 
     * @return void Sends JSON response
     */
    public function delete_record() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_record_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'custom-table-crud')
            ));
        }
        
        // Get parameters
        $table = sanitize_text_field($_POST['table'] ?? '');
        $primary_key = sanitize_text_field($_POST['primary_key'] ?? 'id');
        $record_id = intval($_POST['record_id'] ?? 0);
        
        if (empty($table) || empty($record_id)) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters', 'custom-table-crud')
            ));
        }
        
        global $wpdb;
        
        // Delete the record
        $result = $wpdb->delete(
            $table,
            [$primary_key => $record_id],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => __('Error deleting record', 'custom-table-crud')
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Record deleted successfully', 'custom-table-crud')
        ));
    }
}