<?php
/**
 * Plugin Name: Custom Table CRUD with Debug + Pagination Fix
 * Description: CRUD for custom DB tables with working pagination inside shortcodes.
 * Version: 1.4
 * Author: affigabmag
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
    $timestamp = date('Y-m-d H:i:s');
    $log_data = "\n==== [$timestamp] ====\n";
    $log_data .= "[SHORTCODE ATTRIBUTES PARSED]\n" . print_r($atts, true);

    $columns = [];
    foreach ($atts as $key => $val) {
        if (preg_match('/^field\d+$/', $key)) {
            $col = [];
            $segments = explode(';', $val);
            foreach ($segments as $seg) {
                [$k, $v] = explode('=', $seg, 2) + [null, null];
                if ($k && $v) {
                    $col[trim($k)] = trim($v, " '");
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
        return '<div style="color:red;">⚠️ No valid fields defined in shortcode.</div>';
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

    echo '<div class="wrap">';
    echo '<h1>Custom Crud - Dashboard</h1>';
    echo '<p>Welcome to your custom CRUD dashboard.</p>';

    echo '<form method="post" onsubmit="generateShortcode(); return false;">';

    echo '<label for="table_view"><strong>Select Table:</strong></label><br>';
    echo '<select id="table_view" name="table_view" style="width: 300px;" onchange="loadFields(this.value)">';
    echo '<option value="">-- Select Table --</option>';
    foreach ($tables as $table) {
        echo '<option value="' . esc_attr($table) . '">' . esc_html($table) . '</option>';
    }
    echo '</select><br><br>';

    echo '<div id="field_select_container"></div>';

    echo '<br><label for="pagination"><strong>Pagination:</strong></label><br>';
    echo '<input type="number" id="pagination" name="pagination" value="5" style="width: 100px;"><br><br>';

    echo '<div style="display: flex; align-items: center; gap: 10px;">';
    echo '<button type="submit" class="button button-primary">Generate Shortcode</button>';
    echo '<span id="copy-message" style="color:green;display:none;">Copied to clipboard!</span>';
    echo '</div><br><br>';

    echo '<h2>Generated Shortcode</h2>';
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
        xhr.send("action=get_table_fields&table=" + encodeURIComponent(tableName));
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
    global $wpdb;
    $table = sanitize_text_field($_POST['table'] ?? '');
    if (!$table) {
        wp_die();
    }
    $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table`");
    foreach ($columns as $col) {
        $name = esc_attr($col->Field);
        $type = 'text';
        if (strpos($col->Type, 'int') !== false || strpos($col->Type, 'float') !== false) $type = 'number';
        elseif (strpos($col->Type, 'date') !== false) $type = 'date';
        elseif (strpos($col->Type, 'text') !== false) $type = 'textarea';

        echo '<div class="field-wrapper" style="margin-bottom:8px;">';
        echo '<label><input type="checkbox" class="field-checkbox" value="' . $name . '" checked> ' . $name . '</label><br>';
        echo 'Display Name: <input type="text" name="displayname_' . $name . '" value="' . $name . '" style="width:150px;"> ';
        echo 'Type: <select name="type_' . $name . '">' ;
        $types = ['text','number','date','datetime','textarea','email','url','tel','password'];
        foreach ($types as $t) {
            echo '<option value="' . $t . '"' . ($type == $t ? ' selected' : '') . '>' . $t . '</option>';
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
    $output .= '<td><a href="' . esc_url(add_query_arg('edit_record', $row->$primary_key)) . '">Edit</a> | ';
    $output .= '<a href="' . esc_url(add_query_arg('delete_record', $row->$primary_key)) . '" onclick="return confirm(\'Are you sure?\');">Delete</a></td>';
    $output .= '</tr>';
    return $output;
}

function render_pagination_controls($page, $total, $per_page) {
    $total_pages = ceil($total / $per_page);
    $output = '<form method="post" style="margin-top: 10px;">';
    $output .= '<input type="hidden" name="form_type" value="pagination">';

    if ($page > 1) {
        $output .= '<button type="submit" name="paged" value="' . ($page - 1) . '">&laquo; Prev</button> ';
    }

    $output .= 'Page ' . $page . ' of ' . $total_pages . ' ';

    if ($page < $total_pages) {
        $output .= '<button type="submit" name="paged" value="' . ($page + 1) . '">Next &raquo;</button>';
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

    if (isset($_GET['delete_record'])) {
        $wpdb->delete($table_name, [$primary_key => intval($_GET['delete_record'])]);
        wp_redirect(remove_query_arg(['delete_record', 'edit_record', 'added', 'updated']));
        exit;
    }

    if (!empty($_POST) && isset($_POST['form_type']) && $_POST['form_type'] === 'data_form') {
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
            echo '<p style="color:red;">⚠️ All fields are required.</p>';
        } else {
            if (isset($_POST['update_record'])) {
                $wpdb->update($table_name, $data, [$primary_key => intval($_POST['record_id'])], $format, ['%d']);
                wp_redirect(add_query_arg('updated', '1', remove_query_arg(['edit_record'])));
                exit;
            } else {
                $wpdb->insert($table_name, $data, $format);
                wp_redirect(add_query_arg('added', '1', $_SERVER['REQUEST_URI']));
                exit;
            }
        }
    }

    if (isset($_GET['edit_record'])) {
        $editing = true;
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE $primary_key = %d", intval($_GET['edit_record'])));
    }

    $search_term = $_GET['search'] ?? '';
    $order_by = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $columns) ? $_GET['orderby'] : $primary_key;
    $order_dir = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $query = "SELECT * FROM $table_name";
    if ($search_term) {
        $search_term = sanitize_text_field($search_term);
        $where = array_map(fn($col) => "$col LIKE '%$search_term%'", array_keys($columns));
        $query .= " WHERE " . implode(' OR ', $where);
    }
    $query .= " ORDER BY $order_by $order_dir";

    $page = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : (isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1);
    $offset = ($page - 1) * $per_page;

    $total = $wpdb->get_var(str_replace('SELECT *', 'SELECT COUNT(*)', $query));
    $query .= " LIMIT $offset, $per_page";
    $rows = $wpdb->get_results($query);

    ob_start();

    echo '<form method="post">';
    echo '<style>.wp-books-table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    .wp-books-table th, .wp-books-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .wp-books-table th { background: #f0f0f0; }</style>';

    if (isset($_GET['added'])) echo '<p style="color:green;">✅ Record added successfully!</p>';
    if (isset($_GET['updated'])) echo '<p style="color:green;">✅ Record updated successfully!</p>';

    echo '<input type="hidden" name="form_type" value="data_form">';
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
            echo '<br><small style="color:red;">This field is required.</small>';
        }
        echo '</p>';
    }

    echo '<p><input type="submit" name="' . ($editing ? 'update_record' : 'add_record') . '" value="' . ($editing ? 'Update' : 'Add') . '">';
    if ($editing) echo ' <a href="' . esc_url(remove_query_arg('edit_record')) . '">Cancel</a>';
    echo '</p></form>';

    echo '<form method="get" style="margin-top:10px;">';
    $total_text = "Records: $total";
    echo '<div style="margin: 10px 0; font-weight: bold;">' . $total_text . '</div>';

    echo '<input type="text" name="search" value="' . esc_attr($search_term) . '" placeholder="Search..." />';
    echo '<input type="submit" value="Search" />';
    if ($search_term) echo ' <a href="' . esc_url(remove_query_arg('search')) . '">Clear</a>';
    echo '</form>';

    echo '<table class="wp-books-table"><tr>';
    foreach (array_merge([$primary_key => 'ID'], $columns) as $col => $meta) {
        $label = is_array($meta) ? $meta['label'] : $meta;
        $new_order = ($order_by === $col && $order_dir === 'ASC') ? 'desc' : 'asc';
        echo '<th><a href="' . esc_url(add_query_arg(['orderby' => $col, 'order' => $new_order])) . '">' . esc_html($label) . '</a></th>';
    }
    echo '<th>Actions</th></tr>';

    foreach ($rows as $row) {
        echo render_table_row($row, $columns, $primary_key);
    }
    echo '</table>';

    if ($total > $per_page) {
        echo render_pagination_controls($page, $total, $per_page);
    }

    return ob_get_clean();
}
