<?php
/**
 * Plugin Name: Custom Table CRUD with Debug + Pagination Fix
 * Description: CRUD for custom DB tables with working pagination inside shortcodes.
 * Version: 1.5
 * Author: affigabmag
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-table-crud
 * Stable tag: 1.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Create necessary directories on plugin activation
register_activation_hook(__FILE__, 'custom_table_crud_activate');

function custom_table_crud_activate() {
    // Create css directory if it doesn't exist
    $css_dir = plugin_dir_path(__FILE__) . 'css';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    
    // Create js directory if it doesn't exist
    $js_dir = plugin_dir_path(__FILE__) . 'js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
}

// Enqueue CSS and JavaScript files
function custom_table_crud_enqueue_scripts() {
    wp_enqueue_style('custom-table-crud-style', plugin_dir_url(__FILE__) . 'css/custom-table-crud.css', array(), '1.5');
    wp_enqueue_script('custom-table-crud-script', plugin_dir_url(__FILE__) . 'js/custom-table-crud.js', array('jquery'), '1.5', true);
}
add_action('admin_enqueue_scripts', 'custom_table_crud_enqueue_scripts');
add_action('wp_enqueue_scripts', 'custom_table_crud_enqueue_scripts');

// Register the shortcode on init to allow dynamic attributes
function register_wp_table_manager_shortcode() {
    add_shortcode('wp_table_manager', 'handle_wp_table_manager_shortcode');
}
add_action('init', 'register_wp_table_manager_shortcode');

function handle_wp_table_manager_shortcode($atts = []) {
    $defaults = [
        'pagination' => 5,
        'table_view' => '',
    ];

    foreach ($atts as $key => $val) {
        if (preg_match('/^field\d+$/', $key)) {
            $defaults[$key] = '';
        }
    }

    $atts = shortcode_atts($defaults, $atts);

    $log_file = plugin_dir_path(__FILE__) . 'shortcode_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_data = "\n==== [$timestamp] ====\n";
    $log_data .= "[SHORTCODE ATTRIBUTES PARSED]\n" . print_r($atts, true);

    $columns = [];
    foreach ($atts as $key => $val) {
        if (preg_match('/^field\d+$/', $key)) {
            $col = [];
            $segments = explode(';', $val);
            foreach ($segments as $seg) {
                $parts = explode('=', $seg, 2);
                if (count($parts) === 2) {
                    $k = $parts[0];
                    $v = $parts[1];
                    if ($k && $v) {
                        $col[trim($k)] = trim($v, " '");
                    }
                }
            }
            if (!empty($col['fieldname']) && !empty($col['displayname']) && !empty($col['displaytype'])) {
                $columns[$col['fieldname']] = [
                    'label' => $col['displayname'],
                    'type'  => $col['displaytype']
                ];
            }
        }
    }

    $log_data .= "\n[PARSED COLUMNS]\n" . print_r($columns, true);
    file_put_contents($log_file, $log_data, FILE_APPEND);

    if (empty($columns)) {
        return '<div class="error-message">⚠️ No valid fields defined in shortcode.</div>';
    }

    return generic_table_manager_shortcode([
        'table_name'  => $atts['table_view'],
        'primary_key' => 'id',
        'columns'     => $columns,
        'pagination'  => intval($atts['pagination'])
    ]);
}

add_action('admin_menu', function() {
    add_menu_page(
        'Custom Crud',
        'Custom Crud',
        'manage_options',
        'custom_crud_dashboard',
        'custom_crud_dashboard_page',
        'dashicons-admin-generic'
    );
});

function custom_crud_dashboard_page() {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");
    $selected_table = isset($_GET['table']) ? sanitize_text_field($_GET['table']) : '';

    echo '<div class="wrap">';
    echo '<h1>Custom Crud - Dashboard</h1>';
    echo '<p>Welcome to your custom CRUD dashboard.</p>';

    // Display tables with record counts in a 2-column table with selection capability
    echo '<h2>Available Tables</h2>';
    echo '<div class="table-selection-container">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Table Name</th><th>Number of Records</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($tables as $table) {
        $record_count = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($table));
        $is_selected = ($selected_table === $table) ? ' class="selected"' : '';
        echo '<tr' . $is_selected . '>';
        echo '<td><a href="' . esc_url(add_query_arg('table', $table)) . '" style="text-decoration: none; display: block;">' . 
             esc_html($table) . '</a></td>';
        echo '<td>' . esc_html($record_count) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';

    // Form for shortcode generation
    echo '<form method="post" onsubmit="generateShortcode(); return false;">';
    wp_nonce_field('custom_crud_form', 'custom_crud_nonce');

    // Hidden field for selected table instead of dropdown
    if (!empty($selected_table)) {
        echo '<input type="hidden" id="table_view" name="table_view" value="' . esc_attr($selected_table) . '">';
        echo '<h3>Selected Table: ' . esc_html($selected_table) . '</h3>';
        echo '<div id="field_select_container"></div>';
        
        // Script for loading fields automatically moved to the enqueued JS file
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                loadFields("' . esc_js($selected_table) . '");
            });
        </script>';
    } else {
        echo '<p>Please select a table from the list above</p>';
    }

    echo '<br><label for="pagination"><strong>Pagination:</strong></label><br>';
    echo '<input type="number" id="pagination" name="pagination" value="5" style="width: 100px;"><br><br>';

    if (!empty($selected_table)) {
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        echo '<button type="submit" class="button button-primary">Generate Shortcode</button>';
        echo '<span id="copy-message">Copied to clipboard!</span>';
        echo '</div><br><br>';

        echo '<h2>Generated Shortcode</h2>';
        echo '<textarea id="shortcode_output"></textarea>';
    }

    echo '</form>';
    echo '</div>';
}

add_action('wp_ajax_get_table_fields', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_crud_form')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $table = sanitize_text_field($_POST['table'] ?? '');
    if (!$table) {
        wp_die();
    }
    
    // Use prepared statement for security
    $columns = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM %i", $table));
    
    if (!$columns) {
        wp_die();
    }

    foreach ($columns as $col) {
        $name = esc_attr($col->Field);
        $type = 'text';
        if (strpos($col->Type, 'int') !== false || strpos($col->Type, 'float') !== false) $type = 'number';
        elseif (strpos($col->Type, 'date') !== false) $type = 'date';
        elseif (strpos($col->Type, 'text') !== false) $type = 'textarea';

        echo '<div class="field-wrapper">';
        echo '<label><input type="checkbox" class="field-checkbox" value="' . esc_attr($name) . '" checked> ' . esc_html($name) . '</label><br>';
        echo 'Display Name: <input type="text" name="displayname_' . esc_attr($name) . '" value="' . esc_attr($name) . '" style="width:150px;"> ';
        echo 'Type: <select name="type_' . esc_attr($name) . '">' ;
        $types = ['text','number','date','datetime','textarea','email','url','tel','password'];
        foreach ($types as $t) {
            echo '<option value="' . esc_attr($t) . '"' . ($type == $t ? ' selected' : '') . '>' . esc_html($t) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }
    wp_die();
});

function render_table_row($row, $columns, $primary_key) {
    $output = '<tr>';
    foreach (array_merge([$primary_key], array_keys($columns)) as $field) {
        $output .= '<td>' . (isset($row->$field) ? nl2br(htmlspecialchars_decode(esc_html($row->$field))) : '') . '</td>';

    }
    $output .= '<td><a href="' . esc_url(add_query_arg(['edit_record' => $row->$primary_key, '_wpnonce' => wp_create_nonce('edit_record_' . $row->$primary_key)])) . '">Edit</a> | ';
    $output .= '<a href="' . esc_url(add_query_arg(['delete_record' => $row->$primary_key, '_wpnonce' => wp_create_nonce('delete_record_' . $row->$primary_key)])) . '" onclick="return confirm(\'Are you sure?\');">Delete</a></td>';
    $output .= '</tr>';
    return $output;
}

function render_pagination_controls($page, $total, $per_page) {
    $total_pages = ceil($total / $per_page);
    $output = '<form method="post" style="margin-top: 10px;">';
    $output .= '<input type="hidden" name="form_type" value="pagination">';
    $output .= wp_nonce_field('pagination_nonce', 'pagination_nonce', true, false);

    if ($page > 1) {
        $output .= '<button type="submit" name="paged" value="' . esc_attr($page - 1) . '">&laquo; ' . esc_html__('Prev', 'custom-crud') . '</button> ';
    }

    $output .= esc_html__('Page', 'custom-crud') . ' ' . esc_html($page) . ' ' . esc_html__('of', 'custom-crud') . ' ' . esc_html($total_pages) . ' ';

    if ($page < $total_pages) {
        $output .= '<button type="submit" name="paged" value="' . esc_attr($page + 1) . '">' . esc_html__('Next', 'custom-crud') . ' &raquo;</button>';
    }

    $output .= '</form>';
    return $output;
}

function generic_table_manager_shortcode($config) {
    global $wpdb;

    $table_name  = $config['table_name'];
    $primary_key = $config['primary_key'];
    $columns     = $config['columns'];
    $per_page    = isset($config['pagination']) && intval($config['pagination']) > 0 ? intval($config['pagination']) : 5;
    $editing     = false;
    $edit_data   = null;

    // Process delete action with nonce verification
    if (isset($_GET['delete_record'], $_GET['_wpnonce'])) {
        $record_id = intval($_GET['delete_record']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_record_' . $record_id)) {
            $wpdb->delete($table_name, [$primary_key => $record_id], ['%d']);
            wp_redirect(remove_query_arg(['delete_record', 'edit_record', 'added', 'updated', '_wpnonce']));
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
                $raw = $_POST[$field] ?? '';
                $value = wp_unslash(($meta['type'] === 'textarea') ? sanitize_textarea_field($raw) : sanitize_text_field($raw));

                $data[$field] = $value;
                $format[] = (is_numeric($value) && strpos($value, '.') !== false) ? '%f' : '%s';

                if ($value === '') {
                    $has_error = true;
                }
            }

            if ($has_error) {
                $error_message = '<p class="error-message">⚠️ ' . esc_html__('All fields are required.', 'custom-crud') . '</p>';
            } else {
                if (isset($_POST['update_record'])) {
                    $record_id = intval($_POST['record_id']);
                    $wpdb->update($table_name, $data, [$primary_key => $record_id], $format, ['%d']);
                    wp_redirect(add_query_arg('updated', '1', remove_query_arg(['edit_record', '_wpnonce'])));
                    exit;
                } else {
                    $wpdb->insert($table_name, $data, $format);
                    wp_redirect(add_query_arg('added', '1', remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI'])));
                    exit;
                }
            }
        } else if ($_POST['form_type'] === 'pagination' && wp_verify_nonce($_POST['pagination_nonce'], 'pagination_nonce')) {
            // Pagination form is valid, continue processing
        }
    }

    // Process edit with nonce verification
    if (isset($_GET['edit_record'], $_GET['_wpnonce'])) {
        $record_id = intval($_GET['edit_record']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'edit_record_' . $record_id)) {
            $editing = true;
            $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE $primary_key = %d", $record_id));
        }
    }

    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $order_by = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $columns) ? sanitize_key($_GET['orderby']) : $primary_key;
    $order_dir = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    // Build query with proper escaping
    $query = "SELECT * FROM " . esc_sql($table_name);
    $search_clauses = [];

    if ($search_term) {
        foreach (array_keys($columns) as $col) {
            $search_clauses[] = $wpdb->prepare("$col LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }
        $query .= " WHERE " . implode(' OR ', $search_clauses);
    }
    
    $query .= " ORDER BY " . esc_sql($order_by) . " " . esc_sql($order_dir);

    $page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : (isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1);
    $offset = ($page - 1) * $per_page;

    $total_query = str_replace('SELECT *', 'SELECT COUNT(*)', $query);
    $total = $wpdb->get_var($total_query);
    
    $query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $per_page);
    $rows = $wpdb->get_results($query);

    ob_start();

    echo '<form method="post">';
    echo '<input type="hidden" name="form_type" value="data_form">';
    echo wp_nonce_field('crud_form_nonce', 'crud_nonce', true, false);

    if (isset($_GET['added'])) echo '<p class="success-message">✅ ' . esc_html__('Record added successfully!', 'custom-crud') . '</p>';
    if (isset($_GET['updated'])) echo '<p class="success-message">✅ ' . esc_html__('Record updated successfully!', 'custom-crud') . '</p>';
    if (isset($error_message)) echo $error_message;

    if ($editing && $edit_data) echo '<input type="hidden" name="record_id" value="' . esc_attr($edit_data->$primary_key) . '">';

    foreach ($columns as $field => $meta) {
        $label = $meta['label'];
        $type  = $meta['type'];
        $value = $_POST[$field] ?? ($editing && isset($edit_data->$field) ? $edit_data->$field : '');
        $error = isset($_POST['form_type'], $_POST[$field]) && trim($_POST[$field]) === '';

        echo '<p><label>' . esc_html($label) . ':</label><br>';
        if ($type === 'textarea') {
            echo '<textarea name="' . esc_attr($field) . '" rows="3" required>' . esc_textarea($value) . '</textarea>';
        } else {
            $step = $type === 'number' ? ' step="any"' : '';
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" required' . $step . '>';
        }
        if ($error) {
            echo '<br><small class="error-message">' . esc_html__('This field is required.', 'custom-crud') . '</small>';
        }
        echo '</p>';
    }

    echo '<p><input type="submit" name="' . ($editing ? 'update_record' : 'add_record') . '" value="' . esc_attr($editing ? __('Update', 'custom-crud') : __('Add', 'custom-crud')) . '">';
    if ($editing) echo ' <a href="' . esc_url(remove_query_arg(['edit_record', '_wpnonce'])) . '">' . esc_html__('Cancel', 'custom-crud') . '</a>';
    echo '</p></form>';

    // Search form
    echo '<form method="get" style="margin-top:10px;">';
    foreach ($_GET as $key => $value) {
        if ($key !== 'search' && $key !== 'paged') {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }
    
    $total_text = sprintf(esc_html__('Records: %d', 'custom-crud'), $total);
    echo '<div style="margin: 10px 0; font-weight: bold;">' . esc_html($total_text) . '</div>';

    echo '<input type="text" name="search" value="' . esc_attr($search_term) . '" placeholder="' . esc_attr__('Search...', 'custom-crud') . '" />';
    echo '<input type="submit" value="' . esc_attr__('Search', 'custom-crud') . '" />';
    if ($search_term) echo ' <a href="' . esc_url(remove_query_arg('search')) . '">' . esc_html__('Clear', 'custom-crud') . '</a>';
    echo '</form>';

    // Table display
    echo '<table class="wp-books-table"><tr>';
    foreach (array_merge([$primary_key => 'ID'], $columns) as $col => $meta) {
        $label = is_array($meta) ? $meta['label'] : $meta;
        $new_order = ($order_by === $col && $order_dir === 'ASC') ? 'desc' : 'asc';
        echo '<th><a href="' . esc_url(add_query_arg(['orderby' => $col, 'order' => $new_order])) . '">' . esc_html($label) . '</a></th>';
    }
    echo '<th>' . esc_html__('Actions', 'custom-crud') . '</th></tr>';

    if (!empty($rows)) {
        foreach ($rows as $row) {
            echo render_table_row($row, $columns, $primary_key);
        }
    } else {
        echo '<tr><td colspan="' . (count($columns) + 2) . '">' . esc_html__('No records found.', 'custom-crud') . '</td></tr>';
    }
    
    echo '</table>';

    if ($total > $per_page) {
        echo render_pagination_controls($page, $total, $per_page);
    }

    return ob_get_clean();
}