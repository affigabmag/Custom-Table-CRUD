<?php
namespace CustomTableCRUD;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles the display and CRUD operations for database tables
 * 
 * @since 2.0.0
 */
class Table_Manager {
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
}