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
        
        // Add new AJAX handlers for table creation and management
        add_action('wp_ajax_create_table', array($this, 'create_table'));
        add_action('wp_ajax_delete_table', array($this, 'delete_table'));
        add_action('wp_ajax_get_table_structure', array($this, 'get_table_structure'));
        add_action('wp_ajax_modify_table', array($this, 'modify_table'));
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
    
    /**
     * Handle AJAX table creation
     * 
     * @return void Sends JSON response
     */
    public function create_table() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_crud_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'custom-table-crud')
            ));
        }
        
        // Get parameters
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();
        
        if (empty($table_name) || empty($fields)) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters', 'custom-table-crud')
            ));
        }
        
        // Ensure table name has the WP prefix
        global $wpdb;
        if (strpos($table_name, $wpdb->prefix) !== 0) {
            $table_name = $wpdb->prefix . $table_name;
        }
        
        // Check if table already exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if ($table_exists) {
            wp_send_json_error(array(
                'message' => __('A table with this name already exists', 'custom-table-crud')
            ));
        }
        
        // Create instance of Table_Manager
        $table_manager = new Table_Manager();
        
        // Create the table
        $result = $table_manager->create_table($table_name, $fields);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Table created successfully', 'custom-table-crud'),
            'table_name' => $table_name
        ));
    }
    
    /**
     * Handle AJAX table deletion
     * 
     * @return void Sends JSON response
     */
    public function delete_table() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_crud_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'custom-table-crud')
            ));
        }
        
        // Get parameters
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        
        if (empty($table_name)) {
            wp_send_json_error(array(
                'message' => __('No table specified', 'custom-table-crud')
            ));
        }
        
        // Create instance of Table_Manager
        $table_manager = new Table_Manager();
        
        // Delete the table
        $result = $table_manager->delete_table($table_name);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Table deleted successfully', 'custom-table-crud')
        ));
    }
    
    /**
     * Get table structure for editing
     * 
     * @return void Sends JSON response
     */
    public function get_table_structure() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_crud_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'custom-table-crud')
            ));
        }
        
        // Get parameters
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        
        if (empty($table_name)) {
            wp_send_json_error(array(
                'message' => __('No table specified', 'custom-table-crud')
            ));
        }
        
        global $wpdb;
        
        // Get columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
        
        if (empty($columns)) {
            wp_send_json_error(array(
                'message' => __('No columns found or table does not exist', 'custom-table-crud')
            ));
        }
        
        $fields = array();
        
        foreach ($columns as $column) {
            $field = array(
                'name' => $column->Field,
                'type' => $this->get_field_type_from_sql($column->Type),
                'length' => $this->get_field_length_from_sql($column->Type),
                'null' => ($column->Null === 'YES'),
                'default' => $column->Default,
                'extra' => $column->Extra,
                'key' => $column->Key
            );
            
            $fields[] = $field;
        }
        
        wp_send_json_success(array(
            'fields' => $fields,
            'message' => __('Table structure retrieved successfully', 'custom-table-crud')
        ));
    }
    
    /**
     * Handle AJAX table modification
     * 
     * @return void Sends JSON response
     */
    public function modify_table() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_crud_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'custom-table-crud')
            ));
        }
        
        // Get parameters
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();
        $operations = isset($_POST['operations']) ? $_POST['operations'] : array();
        
        if (empty($table_name) || (empty($fields) && empty($operations))) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters', 'custom-table-crud')
            ));
        }
        
        // Create instance of Table_Manager
        $table_manager = new Table_Manager();
        
        // Modify the table
        $result = $table_manager->modify_table($table_name, $fields, $operations);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Table modified successfully', 'custom-table-crud')
        ));
    }
    
    /**
     * Helper function to get field type from SQL definition
     * 
     * @param string $sql_type SQL type definition
     * @return string Field type
     */
    private function get_field_type_from_sql($sql_type) {
        $sql_type = strtolower($sql_type);
        
        if (strpos($sql_type, 'int') !== false) {
            return 'int';
        } elseif (strpos($sql_type, 'varchar') !== false) {
            return 'varchar';
        } elseif (strpos($sql_type, 'text') !== false) {
            return 'text';
        } elseif (strpos($sql_type, 'date') !== false) {
            return 'date';
        } elseif (strpos($sql_type, 'datetime') !== false) {
            return 'datetime';
        } elseif (strpos($sql_type, 'decimal') !== false || strpos($sql_type, 'float') !== false || strpos($sql_type, 'double') !== false) {
            return 'decimal';
        } else {
            return 'text';
        }
    }
    
    /**
     * Helper function to get field length from SQL definition
     * 
     * @param string $sql_type SQL type definition
     * @return string Field length
     */
    private function get_field_length_from_sql($sql_type) {
        if (preg_match('/\(([^)]+)\)/', $sql_type, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
}