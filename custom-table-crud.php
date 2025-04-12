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
    $timestamp = gmdate('Y-m-d H:i:s');
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
        return '<div style="color:red;">⚠️ ' . esc_html__('No valid fields defined in shortcode.', 'custom-table-crud') . '</div>';
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
        esc_html__('Custom Crud', 'custom-table-crud'),
        esc_html__('Custom Crud', 'custom-table-crud'),
        'manage_options',
        'custom_crud_dashboard',
        'custom_crud_dashboard_page',
        'dashicons-admin-generic'
    );
});

function custom_crud_dashboard_page() {
    // Check user capability
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Custom Crud - Dashboard', 'custom-table-crud') . '</h1>';
    echo '<p>' . esc_html__('Welcome to your custom CRUD dashboard.', 'custom-table-crud') . '</p>';

    echo '<form method="post" onsubmit="generateShortcode(); return false;">';
    wp_nonce_field('custom_crud_form', 'custom_crud_nonce');

    echo '<label for="table_view"><strong>' . esc_html__('Select Table:', 'custom-table-crud') . '</strong></label><br>';
    echo '<select id="table_view" name="table_view" style="width: 300px;" onchange="loadFields(this.value)">';
    echo '<option value="">' . esc_html__('-- Select Table --', 'custom-table-crud') . '</option>';
    foreach ($tables as $table) {
        echo '<option value="' . esc_attr($table) . '">' . esc_html($table) . '</option>';
    }
    echo '</select><br><br>';

    echo '<div id="field_select_container"></div>';

    echo '<br><label for="pagination"><strong>' . esc_html__('Pagination:', 'custom-table-crud') . '</strong></label><br>';
    echo '<input type="number" id="pagination" name="pagination" value="5" style="width: 100px;"><br><br>';

    echo '<div style="display: flex; align-items: center; gap: 10px;">';
    echo '<button type="submit" class="button button-primary">' . esc_html__('Generate Shortcode', 'custom-table-crud') . '</button>';
    echo '<span id="copy-message" style="color:green;display:none;">' . esc_html__('Copied to clipboard!', 'custom-table-crud') . '</span>';
    echo '</div><br><br>';

    echo '<h2>' . esc_html__('Generated Shortcode', 'custom-table-crud') . '</h2>';
    echo '<textarea id="shortcode_output" style="width:100%;height:120px;"></textarea>';

    echo '</form>';

    echo '<script>
    function loadFields(tableName) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", ajaxurl);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById("field_select_container").innerHTML = xhr.responseText;
            }
        };
        const nonce = document.getElementById("custom_crud_nonce").value;
        xhr.send("action=get_table_fields&table=" + encodeURIComponent(tableName) + "&nonce=" + encodeURIComponent(nonce));
    }

    function generateShortcode() {
        const table = document.getElementById("table_view").value;
        const pagination = document.getElementById("pagination").value;
        const wrappers = document.querySelectorAll(".field-wrapper");
        let fieldIndex = 1;
        let fieldsText = "";
        wrappers.forEach(wrap => {
            const checkbox = wrap.querySelector("input[type=checkbox]");
            if (checkbox && checkbox.checked) {
                const fieldname = checkbox.value;
                const displayname = wrap.querySelector("input[name^=displayname_]").value || fieldname;
                const displaytype = wrap.querySelector("select[name^=type_]").value;
                fieldsText += ` field${fieldIndex}="fieldname=${fieldname};displayname=${displayname};displaytype=${displaytype}"`;
                fieldIndex++;
            }
        });
        const shortcode = `[wp_table_manager pagination="${pagination}" table_view="${table}"${fieldsText}]`;
        const textarea = document.getElementById("shortcode_output");
        textarea.value = shortcode;
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        document.execCommand("copy");
        const msg = document.getElementById("copy-message");
        msg.style.display = "inline";
        setTimeout(() => { msg.style.display = "none"; }, 3000);
    }
    </script>';

    echo '</div>';
}

add_action('wp_ajax_get_table_fields', function() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'custom_crud_form')) {
        wp_die(esc_html__('Security check failed', 'custom-table-crud'));
    }

    global $wpdb;
    $table = sanitize_text_field($_POST['table'] ?? '');
    if (!$table) {
        wp_die();
    }
    
    // For SHOW COLUMNS, we need to use esc_sql because %i doesn't work with SHOW COLUMNS
    $table_name = esc_sql($table);
    // Add translators comment for context
    // translators: %s is the table name
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    
    if (!$columns) {
        wp_die();
    }

    foreach ($columns as $col) {
        $name = esc_attr($col->Field);
        $type = 'text';
        if (strpos($col->Type, 'int') !== false || strpos($col->Type, 'float') !== false) $type = 'number';
        elseif (strpos($col->Type, 'date') !== false) $type = 'date';
        elseif (strpos($col->Type, 'text') !== false) $type = 'textarea';

        echo '<div class="field-wrapper" style="margin-bottom:8px;">';
        echo '<label><input type="checkbox" class="field-checkbox" value="' . esc_attr($name) . '" checked> ' . esc_html($name) . '</label><br>';
        echo esc_html__('Display Name:', 'custom-table-crud') . ' <input type="text" name="displayname_' . esc_attr($name) . '" value="' . esc_attr($name) . '" style="width:150px;"> ';
        echo esc_html__('Type:', 'custom-table-crud') . ' <select name="type_' . esc_attr($name) . '">' ;
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
        $output .= '<td>' . (isset($row->$field) ? nl2br(esc_html($row->$field)) : '') . '</td>';
    }
    $output .= '<td><a href="' . esc_url(add_query_arg(['edit_record' => $row->$primary_key, '_wpnonce' => wp_create_nonce('edit_record_' . $row->$primary_key)])) . '">' . esc_html__('Edit', 'custom-table-crud') . '</a> | ';
    // translators: This is the confirmation message when deleting a record
    $confirm_message = __('Are you sure?', 'custom-table-crud');
    $output .= '<a href="' . esc_url(add_query_arg(['delete_record' => $row->$primary_key, '_wpnonce' => wp_create_nonce('delete_record_' . $row->$primary_key)])) . '" onclick="return confirm(\'' . esc_js($confirm_message) . '\');">' . esc_html__('Delete', 'custom-table-crud') . '</a></td>';
    $output .= '</tr>';
    return $output;
}

function render_pagination_controls($page, $total, $per_page) {
    $total_pages = ceil($total / $per_page);
    $output = '<form method="post" style="margin-top: 10px;">';
    $output .= '<input type="hidden" name="form_type" value="pagination">';
    $output .= wp_nonce_field('pagination_nonce', 'pagination_nonce', true, false);

    if ($page > 1) {
        // translators: Previous button for pagination
        $prev_text = __('Prev', 'custom-table-crud');
        $output .= '<button type="submit" name="paged" value="' . esc_attr($page - 1) . '">&laquo; ' . esc_html($prev_text) . '</button> ';
    }

    // translators: %1$s is the current page number, %2$s is the total number of pages
    $page_text = sprintf(__('Page %1$s of %2$s', 'custom-table-crud'), 
                         '<span>' . esc_html($page) . '</span>', 
                         '<span>' . esc_html($total_pages) . '</span>');
    $output .= $page_text . ' ';

    if ($page < $total_pages) {
        // translators: Next button for pagination
        $next_text = __('Next', 'custom-table-crud');
        $output .= '<button type="submit" name="paged" value="' . esc_attr($page + 1) . '">' . esc_html($next_text) . ' &raquo;</button>';
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
    $error_message = '';

    // Process delete action with nonce verification
    if (isset($_GET['delete_record'], $_GET['_wpnonce'])) {
        $record_id = intval($_GET['delete_record']);
        if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'delete_record_' . $record_id)) {
            $wpdb->delete(esc_sql($table_name), [$primary_key => $record_id], ['%d']);
            $redirect_url = remove_query_arg(['delete_record', 'edit_record', 'added', 'updated', '_wpnonce']);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    // Process form submissions with nonce verification
    if (!empty($_POST) && isset($_POST['form_type'], $_POST['crud_nonce'])) {
        if ($_POST['form_type'] === 'data_form' && wp_verify_nonce(sanitize_text_field($_POST['crud_nonce']), 'crud_form_nonce')) {
            $data = [];
            $format = [];
            $has_error = false;

            foreach ($columns as $field => $meta) {
                $raw = $_POST[$field] ?? '';
                $value = ($meta['type'] === 'textarea') ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
                $data[$field] = $value;
                $format[] = (is_numeric($value) && strpos($value, '.') !== false) ? '%f' : '%s';

                if ($value === '') {
                    $has_error = true;
                }
            }

            if ($has_error) {
                $error_message = '<p style="color:red;">⚠️ ' . esc_html__('All fields are required.', 'custom-table-crud') . '</p>';
            } else {
                if (isset($_POST['update_record'])) {
                    $record_id = intval($_POST['record_id']);
                    $wpdb->update(
                        esc_sql($table_name), 
                        $data, 
                        [$primary_key => $record_id], 
                        $format, 
                        ['%d']
                    );
                    $redirect_url = add_query_arg(
                        'updated', 
                        '1', 
                        remove_query_arg(['edit_record', '_wpnonce'])
                    );
                    wp_safe_redirect($redirect_url);
                    exit;
                } else {
                    $wpdb->insert(esc_sql($table_name), $data, $format);
                    $redirect_url = add_query_arg(
                        'added', 
                        '1', 
                        remove_query_arg('_wpnonce', $_SERVER['REQUEST_URI'])
                    );
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
        } else if ($_POST['form_type'] === 'pagination' && wp_verify_nonce(sanitize_text_field($_POST['pagination_nonce']), 'pagination_nonce')) {
            // Pagination form is valid, continue processing
        }
    }

    // Process edit with nonce verification
    if (isset($_GET['edit_record'], $_GET['_wpnonce'])) {
        $record_id = intval($_GET['edit_record']);
        if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'edit_record_' . $record_id)) {
            $editing = true;
            $edit_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . esc_sql($table_name) . " WHERE " . esc_sql($primary_key) . " = %d", 
                $record_id
            ));
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
            $search_clauses[] = $wpdb->prepare(esc_sql($col) . " LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }
        if (!empty($search_clauses)) {
            $query .= " WHERE " . implode(' OR ', $search_clauses);
        }
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
    
    echo '<style>.wp-books-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .wp-books-table th, .wp-books-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .wp-books-table th { background: #f0f0f0; }</style>';

    if (isset($_GET['added'])) echo '<p style="color:green;">✅ ' . esc_html__('Record added successfully!', 'custom-table-crud') . '</p>';
    if (isset($_GET['updated'])) echo '<p style="color:green;">✅ ' . esc_html__('Record updated successfully!', 'custom-table-crud') . '</p>';
    if (!empty($error_message)) echo $error_message;

    if ($editing && $edit_data) echo '<input type="hidden" name="record_id" value="' . esc_attr($edit_data->$primary_key) . '">';

    foreach ($columns as $field => $meta) {
        $label = $meta['label'];
        $type  = $meta['type'];
        $value = isset($_POST[$field]) ? $_POST[$field] : ($editing && isset($edit_data->$field) ? $edit_data->$field : '');
        $error = isset($_POST['form_type'], $_POST[$field]) && trim($_POST[$field]) === '';

        echo '<p><label>' . esc_html($label) . ':</label><br>';
        if ($type === 'textarea') {
            echo '<textarea name="' . esc_attr($field) . '" rows="3" required>' . esc_textarea($value) . '</textarea>';
        } else {
            $step = $type === 'number' ? ' step="any"' : '';
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" required' . $step . '>';
        }
        if ($error) {
            echo '<br><small style="color:red;">' . esc_html__('This field is required.', 'custom-table-crud') . '</small>';
        }
        echo '</p>';
    }

    echo '<p><input type="submit" name="' . ($editing ? 'update_record' : 'add_record') . '" value="' . esc_attr($editing ? __('Update', 'custom-table-crud') : __('Add', 'custom-table-crud')) . '">';
    if ($editing) echo ' <a href="' . esc_url(remove_query_arg(['edit_record', '_wpnonce'])) . '">' . esc_html__('Cancel', 'custom-table-crud') . '</a>';
    echo '</p></form>';

    // Search form
    echo '<form method="get" style="margin-top:10px;">';
    foreach ($_GET as $key => $value) {
        if ($key !== 'search' && $key !== 'paged') {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }
    
    $total_text = sprintf(
        // translators: %d is the number of records found
        esc_html__('Records: %d', 'custom-table-crud'), 
        $total
    );
    echo '<div style="margin: 10px 0; font-weight: bold;">' . $total_text . '</div>';

    echo '<input type="text" name="search" value="' . esc_attr($search_term) . '" placeholder="' . esc_attr__('Search...', 'custom-table-crud') . '" />';
    echo '<input type="submit" value="' . esc_attr__('Search', 'custom-table-crud') . '" />';
    if ($search_term) echo ' <a href="' . esc_url(remove_query_arg('search')) . '">' . esc_html__('Clear', 'custom-table-crud') . '</a>';
    echo '</form>';

    // Table display
    echo '<table class="wp-books-table"><tr>';
    foreach (array_merge([$primary_key => 'ID'], $columns) as $col => $meta) {
        $label = is_array($meta) ? $meta['label'] : $meta;
        $new_order = ($order_by === $col && $order_dir === 'ASC') ? 'desc' : 'asc';
        echo '<th><a href="' . esc_url(add_query_arg(['orderby' => $col, 'order' => $new_order])) . '">' . esc_html($label) . '</a></th>';
    }
    echo '<th>' . esc_html__('Actions', 'custom-table-crud') . '</th></tr>';

    if (!empty($rows)) {
        foreach ($rows as $row) {
            echo render_table_row($row, $columns, $primary_key);
        }
    } else {
        // translators: Text shown when no records are found
        $no_records_text = __('No records found.', 'custom-table-crud');
        echo '<tr><td colspan="' . (count($columns) + 2) . '">' . esc_html($no_records_text) . '</td></tr>';
    }
    
    echo '</table>';

    if ($total > $per_page) {
        echo render_pagination_controls($page, $total, $per_page);
    }

    return ob_get_clean();
}
