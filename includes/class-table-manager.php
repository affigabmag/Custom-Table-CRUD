<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the creation and management of database tables
 *
 * @since 2.0.0
 */
class Table_Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers if needed
    }
    
    /**
     * Create a new database table
     *
     * @param string $table_name Table name
     * @param array $fields Field definitions
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function create_table($table_name, $fields) {
        if (empty($table_name) || empty($fields)) {
            return new \WP_Error('invalid_params', __('Invalid parameters for table creation', 'custom-table-crud'));
        }
        
        global $wpdb;
        
        // Check if table already exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if ($table_exists) {
            return new \WP_Error('table_exists', __('Table already exists', 'custom-table-crud'));
        }
        
        // Build SQL for table creation
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (";
        
        $has_primary_key = false;
        
        foreach ($fields as $field) {
            // Sanitize field properties
            $field_name = sanitize_key($field['name']);
            $field_type = strtolower(sanitize_text_field($field['type']));
            $field_length = isset($field['length']) ? sanitize_text_field($field['length']) : '';
            $field_null = isset($field['null']) && $field['null'] ? 'NULL' : 'NOT NULL';
            $field_default = isset($field['default']) ? sanitize_text_field($field['default']) : '';
            $field_extra = isset($field['extra']) ? sanitize_text_field($field['extra']) : '';
            $field_primary = isset($field['primary']) && $field['primary'] ? true : false;
            
            // Build field SQL
            $sql .= "$field_name ";
            
            // Determine SQL type based on field type
            switch ($field_type) {
                case 'int':
                    $length = !empty($field_length) ? $field_length : '11';
                    $sql .= "INT($length)";
                    break;
                
                case 'varchar':
                    $length = !empty($field_length) ? $field_length : '255';
                    $sql .= "VARCHAR($length)";
                    break;
                
                case 'text':
                    $sql .= "TEXT";
                    break;
                
                case 'date':
                    $sql .= "DATE";
                    break;
                
                case 'datetime':
                    $sql .= "DATETIME";
                    break;
                
                case 'decimal':
                    $length = !empty($field_length) ? $field_length : '10,2';
                    $sql .= "DECIMAL($length)";
                    break;
                
                default:
                    $length = !empty($field_length) ? $field_length : '255';
                    $sql .= "VARCHAR($length)";
                    break;
            }
            
            // Add NULL/NOT NULL
            $sql .= " $field_null";
            
            // Add DEFAULT if specified
            if ($field_default !== '') {
                if ($field_type === 'int' || $field_type === 'decimal') {
                    $sql .= " DEFAULT $field_default";
                } else {
                    $sql .= " DEFAULT '$field_default'";
                }
            }
            
            // Add AUTO_INCREMENT if specified
            if ($field_extra === 'auto_increment') {
                $sql .= " AUTO_INCREMENT";
            }
            
            // Add PRIMARY KEY if this is the primary key
            if ($field_primary) {
                $has_primary_key = true;
            }
            
            $sql .= ", ";
        }
        
        // Add primary key if defined
        foreach ($fields as $field) {
            if (isset($field['primary']) && $field['primary']) {
                $sql .= "PRIMARY KEY  (" . sanitize_key($field['name']) . ")";
                break;
            }
        }
        
        // Remove trailing comma if no primary key was added
        if (!$has_primary_key) {
            $sql = rtrim($sql, ', ');
        }
        
        $sql .= ") $charset_collate;";
        
        // Create the table
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Check if table was created successfully
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return new \WP_Error('creation_failed', __('Table creation failed', 'custom-table-crud'));
        }
        
        return true;
    }
    
    /**
     * Delete a database table
     *
     * @param string $table_name Table name
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function delete_table($table_name) {
        if (empty($table_name)) {
            return new \WP_Error('invalid_params', __('Invalid table name', 'custom-table-crud'));
        }
        
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return new \WP_Error('table_not_exists', __('Table does not exist', 'custom-table-crud'));
        }
        
        // Delete the table
        $sql = "DROP TABLE $table_name";
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            return new \WP_Error('deletion_failed', __('Table deletion failed', 'custom-table-crud'));
        }
        
        return true;
    }
    
    /**
     * Modify an existing database table
     *
     * @param string $table_name Table name
     * @param array $fields New/modified field definitions
     * @param array $operations Operations to perform (add, modify, drop)
     * @return true|WP_Error True on success, WP_Error on failure
     */
    public function modify_table($table_name, $fields, $operations) {
        if (empty($table_name)) {
            return new \WP_Error('invalid_params', __('Invalid table name', 'custom-table-crud'));
        }
        
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return new \WP_Error('table_not_exists', __('Table does not exist', 'custom-table-crud'));
        }
        
        // Process operations
        $sql_statements = array();
        
        // First, handle column operations
        if (!empty($operations)) {
            foreach ($operations as $operation) {
                switch ($operation['type']) {
                    case 'add':
                        // Add column
                        $field = $this->get_field_by_name($fields, $operation['field']);
                        if ($field) {
                            $sql_statements[] = $this->generate_add_column_sql($table_name, $field);
                        }
                        break;
                    
                    case 'modify':
                        // Modify column
                        $field = $this->get_field_by_name($fields, $operation['field']);
                        if ($field) {
                            $sql_statements[] = $this->generate_modify_column_sql($table_name, $field);
                        }
                        break;
                    
                    case 'drop':
                        // Drop column
                        $sql_statements[] = $this->generate_drop_column_sql($table_name, $operation['field']);
                        break;
                }
            }
        }
        
        // Execute all SQL statements
        $success = true;
        $errors = array();
        
        foreach ($sql_statements as $sql) {
            $result = $wpdb->query($sql);
            if ($result === false) {
                $success = false;
                $errors[] = $wpdb->last_error;
            }
        }
        
        if (!$success) {
            return new \WP_Error('modification_failed', __('Table modification failed: ', 'custom-table-crud') . implode(', ', $errors));
        }
        
        return true;
    }
    
    /**
     * Process form submissions and render the table
     *
     * @param array $config Table configuration
     * @return string HTML output
     */
    public function render_table($config) {
        global $wpdb;
        
        // Extract configuration
        $table_name = $config['table_name'];
        $primary_key = $config['primary_key'];
        $columns = $config['columns'];
        $per_page = isset($config['pagination']) && intval($config['pagination']) > 0 ? intval($config['pagination']) : 5;
        $showform = isset($config['showform']) ? $config['showform'] : 'true';
        $showtable = isset($config['showtable']) ? $config['showtable'] : 'true';
        $showsearch = isset($config['showsearch']) ? $config['showsearch'] : 'true';
        $showpagination = isset($config['showpagination']) ? $config['showpagination'] : 'true';
        $showrecordscount = isset($config['showrecordscount']) ? $config['showrecordscount'] : 'true';
        
        $editing = false;
        $edit_data = null;
        $success_message = '';
        $error_message = '';
        
        // Process delete action with nonce verification
        if (isset($_GET['delete_record'], $_GET['_wpnonce'])) {
            $record_id = intval($_GET['delete_record']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_record_' . $record_id)) {
                $result = $wpdb->delete(
                    $table_name, 
                    [$primary_key => $record_id], 
                    ['%d']
                );
                
                if ($result !== false) {
                    $success_message = __('Record deleted successfully!', 'custom-table-crud');
                } else {
                    $error_message = __('Error deleting record.', 'custom-table-crud');
                }
                
                // Redirect to remove query args
                wp_redirect(remove_query_arg(['delete_record', 'edit_record', '_wpnonce']));
                exit;
            }
        }
        
        // Process form submissions with nonce verification
        if (!empty($_POST) && isset($_POST['form_type'], $_POST['crud_nonce'])) {
            if ($_POST['form_type'] === 'data_form' && wp_verify_nonce($_POST['crud_nonce'], 'crud_form_nonce')) {
                $data = [];
                $format = [];
                $has_error = false;
                
                foreach ($columns as $field => $meta) {
                    // Skip if field is not present in form data
                    if (!isset($_POST[$field])) {
                        continue;
                    }
                    
                    $raw = $_POST[$field];
                    
                    // Sanitize based on field type
                    if ($meta['type'] === 'textarea') {
                        $value = sanitize_textarea_field($raw);
                    } elseif ($meta['type'] === 'email') {
                        $value = sanitize_email($raw);
                    } elseif ($meta['type'] === 'url') {
                        $value = esc_url_raw($raw);
                    } elseif ($meta['type'] === 'number') {
                        $value = is_numeric($raw) ? $raw : '';
                    } else {
                        $value = sanitize_text_field($raw);
                    }
                    
                    $data[$field] = $value;
                    
                    // Determine format for wpdb
                    if ($meta['type'] === 'number') {
                        $format[] = strpos($value, '.') !== false ? '%f' : '%d';
                    } else {
                        $format[] = '%s';
                    }
                    
                    // Check for required fields
                    if ($value === '' && (!isset($meta['readonly']) || $meta['readonly'] !== 'true')) {
                        $has_error = true;
                    }
                }
                
                if ($has_error) {
                    $error_message = __('All fields are required.', 'custom-table-crud');
                } else {
                    if (isset($_POST['update_record'])) {
                        $record_id = intval($_POST['record_id']);
                        $result = $wpdb->update(
                            $table_name, 
                            $data, 
                            [$primary_key => $record_id], 
                            $format, 
                            ['%d']
                        );
                        
                        if ($result !== false) {
                            $success_message = __('Record updated successfully!', 'custom-table-crud');
                        } else {
                            $error_message = __('Error updating record.', 'custom-table-crud');
                        }
                        
                        // Redirect to remove query args
                        wp_redirect(add_query_arg(
                            ['updated' => '1'], 
                            remove_query_arg(['edit_record', '_wpnonce'])
                        ));
                        exit;
                    } else {
                        $result = $wpdb->insert($table_name, $data, $format);
                        
                        if ($result !== false) {
                            $success_message = __('Record added successfully!', 'custom-table-crud');
                        } else {
                            $error_message = __('Error adding record.', 'custom-table-crud');
                        }
                        
                        // Redirect to remove query args
                        wp_redirect(add_query_arg(
                            ['added' => '1'], 
                            remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI'])
                        ));
                        exit;
                    }
                }
            } elseif ($_POST['form_type'] === 'pagination' && wp_verify_nonce($_POST['pagination_nonce'], 'pagination_nonce')) {
                // Pagination form is valid, continue processing
            }
        }
        
        // Process edit action with nonce verification
        if (isset($_GET['edit_record'], $_GET['_wpnonce'])) {
            $record_id = intval($_GET['edit_record']);
            if (wp_verify_nonce($_GET['_wpnonce'], 'edit_record_' . $record_id)) {
                $editing = true;
                $edit_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE $primary_key = %d", 
                    $record_id
                ));
            }
        }
        
        // Handle search and sorting
        $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $order_by = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $columns) ? sanitize_key($_GET['orderby']) : $primary_key;
        $order_dir = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';
        
        // Build query with proper escaping
        $query = "SELECT * FROM " . $table_name;
        $search_clauses = [];
        
        if ($search_term) {
            foreach (array_keys($columns) as $col) {
                $search_clauses[] = $wpdb->prepare("$col LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
            }
            $query .= " WHERE " . implode(' OR ', $search_clauses);
        }
        
        $query .= " ORDER BY " . esc_sql($order_by) . " " . esc_sql($order_dir);
        
        // Handle pagination
        $page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : (isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1);
        $offset = ($page - 1) * $per_page;
        
        // Get total records for pagination
        $total_query = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
        $total = $wpdb->get_var($total_query);
        
        // Get paginated records
        $query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $per_page);
        $rows = $wpdb->get_results($query);
        
        // Debug query
        $debug_info = "";
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $debug_info .= "<div class='debug-info' style='background: #f7f7f7; padding: 10px; margin: 10px 0; border: 1px solid #ddd;'>";
            $debug_info .= "<h4>Debug Information</h4>";
            $debug_info .= "<p>Table: " . esc_html($table_name) . "</p>";
            $debug_info .= "<p>Query: " . esc_html($query) . "</p>";
            $debug_info .= "<p>Results: " . ($rows ? count($rows) : '0') . "</p>";
            if ($wpdb->last_error) {
                $debug_info .= "<p>Error: " . esc_html($wpdb->last_error) . "</p>";
            }
            $debug_info .= "</div>";
        }
        
        // Start output buffering to capture HTML
        ob_start();
        
        // Display debug info in admin only
        if (is_admin() && !empty($debug_info)) {
            echo $debug_info;
        }
        
        // Display messages
        if (isset($_GET['added'])) {
            echo '<p class="success-message">✅ ' . esc_html__('Record added successfully!', 'custom-table-crud') . '</p>';
        }
        
        if (isset($_GET['updated'])) {
            echo '<p class="success-message">✅ ' . esc_html__('Record updated successfully!', 'custom-table-crud') . '</p>';
        }
        
        if ($success_message) {
            echo '<p class="success-message">✅ ' . esc_html($success_message) . '</p>';
        }
        
        if ($error_message) {
            echo '<p class="error-message">⚠️ ' . esc_html($error_message) . '</p>';
        }
        
        // Include templates
        if ($showform !== 'false') {
            // Load form template
            include CUSTOM_TABLE_CRUD_PATH . 'templates/frontend/form-view.php';
        }
        
        if ($showsearch !== 'false') {
            // Load search template
            include CUSTOM_TABLE_CRUD_PATH . 'templates/frontend/search-view.php';
        }
        
        if ($showtable !== 'false') {
            // Load table template
            include CUSTOM_TABLE_CRUD_PATH . 'templates/frontend/table-view.php';
        }
        
        if ($showpagination !== 'false' && $total > $per_page) {
            // Load pagination template
            include CUSTOM_TABLE_CRUD_PATH . 'templates/frontend/pagination-view.php';
        }
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Render a single table row
     *
     * @param object $row Data row
     * @param array $columns Column definitions
     * @param string $primary_key Primary key field name
     * @return string HTML output
     */
    public function render_table_row($row, $columns, $primary_key) {
        $output = '<tr>';
        
        $display_fields = array_keys($columns);
        
        // Loop through selected fields
        foreach ($display_fields as $field) {
            $value = isset($row->$field) ? $row->$field : '';
            $type = isset($columns[$field]['type']) ? $columns[$field]['type'] : 'text';
            
            // Format output based on field type
            if ($type === 'url' && !empty($value)) {
                $value = '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>';
            } elseif ($type === 'textarea') {
                $value = nl2br(esc_html($value));
            } else {
                $value = esc_html($value);
            }
            
            $output .= '<td>' . $value . '</td>';
        }
        
        // Handle missing primary key safely
        $record_id = isset($row->$primary_key) ? $row->$primary_key : null;
        
        if ($record_id !== null) {
            $output .= '<td class="actions-column">';
            
            // Edit action
            $output .= '<a href="' . esc_url(add_query_arg([
                'edit_record' => $record_id,
                '_wpnonce' => wp_create_nonce('edit_record_' . $record_id)
            ])) . '" class="action-edit">' . esc_html__('Edit', 'custom-table-crud') . '</a>';
            
            // Delete action
            $output .= ' | <a href="' . esc_url(add_query_arg([
                'delete_record' => $record_id,
                '_wpnonce' => wp_create_nonce('delete_record_' . $record_id)
            ])) . '" class="action-delete" data-confirm="' . esc_attr__('Are you sure?', 'custom-table-crud') . '">' . 
                esc_html__('Delete', 'custom-table-crud') . '</a>';
            
            $output .= '</td>';
        } else {
            $output .= '<td><em>' . esc_html__('No Primary Key', 'custom-table-crud') . '</em></td>';
        }
        
        $output .= '</tr>';
        return $output;
    }
    
    /**
     * Get field definition by name from array of fields
     *
     * @param array $fields Array of field definitions
     * @param string $name Field name
     * @return array|null Field definition or null if not found
     */
    private function get_field_by_name($fields, $name) {
        foreach ($fields as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }
        return null;
    }
    
    /**
     * Generate SQL for adding a column
     *
     * @param string $table_name Table name
     * @param array $field Field definition
     * @return string SQL statement
     */
    private function generate_add_column_sql($table_name, $field) {
        // Sanitize field properties
        $field_name = sanitize_key($field['name']);
        $field_type = strtolower(sanitize_text_field($field['type']));
        $field_length = isset($field['length']) ? sanitize_text_field($field['length']) : '';
        $field_null = isset($field['null']) && $field['null'] ? 'NULL' : 'NOT NULL';
        $field_default = isset($field['default']) ? sanitize_text_field($field['default']) : '';
        $field_extra = isset($field['extra']) ? sanitize_text_field($field['extra']) : '';
        
        $sql = "ALTER TABLE $table_name ADD COLUMN $field_name ";
        
        // Determine SQL type based on field type
        switch ($field_type) {
            case 'int':
                $length = !empty($field_length) ? $field_length : '11';
                $sql .= "INT($length)";
                break;
            
            case 'varchar':
                $length = !empty($field_length) ? $field_length : '255';
                $sql .= "VARCHAR($length)";
                break;
            
            case 'text':
                $sql .= "TEXT";
                break;
            
            case 'date':
                $sql .= "DATE";
                break;
            
            case 'datetime':
                $sql .= "DATETIME";
                break;
            
            case 'decimal':
                $length = !empty($field_length) ? $field_length : '10,2';
                $sql .= "DECIMAL($length)";
                break;
            
            default:
                $length = !empty($field_length) ? $field_length : '255';
                $sql .= "VARCHAR($length)";
                break;
        }
        
        // Add NULL/NOT NULL
        $sql .= " $field_null";
        
        // Add DEFAULT if specified
        if ($field_default !== '') {
            if ($field_type === 'int' || $field_type === 'decimal') {
                $sql .= " DEFAULT $field_default";
            } else {
                $sql .= " DEFAULT '$field_default'";
            }
        }
        
        // Add AUTO_INCREMENT if specified
        if ($field_extra === 'auto_increment') {
            $sql .= " AUTO_INCREMENT";
        }
        
        return $sql;
    }
    
    /**
     * Generate SQL for modifying a column
     *
     * @param string $table_name Table name
     * @param array $field Field definition
     * @return string SQL statement
     */
    private function generate_modify_column_sql($table_name, $field) {
        // Sanitize field properties
        $field_name = sanitize_key($field['name']);
        $field_type = strtolower(sanitize_text_field($field['type']));
        $field_length = isset($field['length']) ? sanitize_text_field($field['length']) : '';
        $field_null = isset($field['null']) && $field['null'] ? 'NULL' : 'NOT NULL';
        $field_default = isset($field['default']) ? sanitize_text_field($field['default']) : '';
        $field_extra = isset($field['extra']) ? sanitize_text_field($field['extra']) : '';
        
        $sql = "ALTER TABLE $table_name MODIFY COLUMN $field_name ";
        
        // Determine SQL type based on field type
        switch ($field_type) {
            case 'int':
                $length = !empty($field_length) ? $field_length : '11';
                $sql .= "INT($length)";
                break;
            
            case 'varchar':
                $length = !empty($field_length) ? $field_length : '255';
                $sql .= "VARCHAR($length)";
                break;
            
            case 'text':
                $sql .= "TEXT";
                break;
            
            case 'date':
                $sql .= "DATE";
                break;
            
            case 'datetime':
                $sql .= "DATETIME";
                break;
            
            case 'decimal':
                $length = !empty($field_length) ? $field_length : '10,2';
                $sql .= "DECIMAL($length)";
                break;
            
            default:
                $length = !empty($field_length) ? $field_length : '255';
                $sql .= "VARCHAR($length)";
                break;
        }
        
        // Add NULL/NOT NULL
        $sql .= " $field_null";
        
        // Add DEFAULT if specified
        if ($field_default !== '') {
            if ($field_type === 'int' || $field_type === 'decimal') {
                $sql .= " DEFAULT $field_default";
            } else {
                $sql .= " DEFAULT '$field_default'";
            }
        }
        
        // Add AUTO_INCREMENT if specified
        if ($field_extra === 'auto_increment') {
            $sql .= " AUTO_INCREMENT";
        }
        
        return $sql;
    }
    
    /**
     * Generate SQL for dropping a column
     *
     * @param string $table_name Table name
     * @param string $field_name Field name
     * @return string SQL statement
     */
    private function generate_drop_column_sql($table_name, $field_name) {
        return "ALTER TABLE $table_name DROP COLUMN " . sanitize_key($field_name);
    }
    
    /**
     * Get available database tables
     *
     * @param bool $include_wp_tables Whether to include WordPress core tables
     * @return array Array of table names
     */
    public function get_tables($include_wp_tables = true) {
        global $wpdb;
        
        $tables = $wpdb->get_col("SHOW TABLES");
        
        if (!$include_wp_tables) {
            $wp_core_tables = array(
                $wpdb->prefix . 'commentmeta',
                $wpdb->prefix . 'comments',
                $wpdb->prefix . 'links',
                $wpdb->prefix . 'options',
                $wpdb->prefix . 'postmeta',
                $wpdb->prefix . 'posts',
                $wpdb->prefix . 'termmeta',
                $wpdb->prefix . 'terms',
                $wpdb->prefix . 'term_relationships',
                $wpdb->prefix . 'term_taxonomy',
                $wpdb->prefix . 'usermeta',
                $wpdb->prefix . 'users',
            );
            
            $tables = array_diff($tables, $wp_core_tables);
        }
        
        return $tables;
    }
    
    /**
     * Get table structure
     *
     * @param string $table_name Table name
     * @return array Table structure
     */
    public function get_table_structure($table_name) {
        global $wpdb;
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        
        if (empty($columns)) {
            return array();
        }
        
        $structure = array();
        
        foreach ($columns as $column) {
            $field = array(
                'name' => $column->Field,
                'type' => $this->get_field_type_from_sql($column->Type),
                'length' => $this->get_field_length_from_sql($column->Type),
                'null' => ($column->Null === 'YES'),
                'default' => $column->Default,
                'extra' => $column->Extra,
                'key' => $column->Key,
                'is_primary' => ($column->Key === 'PRI')
            );
            
            $structure[] = $field;
        }
        
        return $structure;
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
        } elseif (strpos($sql_type, 'date') !== false && strpos($sql_type, 'datetime') === false) {
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