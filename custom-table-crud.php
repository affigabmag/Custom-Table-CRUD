<?php
<?php
/**
 * Plugin Name: Custom Table CRUD
 * Description: Manage custom database tables in WordPress using a flexible CRUD shortcode system with search, pagination, and editing.
 * Version: 1.0
 * Author: affigabmag
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

 //example:
add_shortcode('wp_books_manager', 'wp_books_manager_shortcode');
add_shortcode('wp_warranties_manager', 'wp_warranties_manager_shortcode');

//init with table data
function wp_books_manager_shortcode() {
    global $wpdb;
    return generic_table_manager_shortcode([
        'table_name'  => $wpdb->prefix . 'books',
        'primary_key' => 'id',
        'columns'     => [
            'name'        => 'Book Name',
            'price'       => 'Price',
            'description' => 'Description'
        ]
    ]);
}

function wp_warranties_manager_shortcode() {
    global $wpdb;
    return generic_table_manager_shortcode([
        'table_name'  => $wpdb->prefix . 'warranties',
        'primary_key' => 'id',
        'columns'     => [
            'ProductName'     => 'Product Name',
            'DateOfPurchase'  => 'Date Of Purchase',
            'Notes'           => 'Notes'
        ]
    ]);
}

function generic_table_manager_shortcode($config) {
    global $wpdb;

    $table_name  = $config['table_name'];
    $primary_key = $config['primary_key'];
    $columns     = $config['columns'];
    $editing     = false;
    $edit_data   = null;

    if (isset($_GET['delete_record'])) {
        $wpdb->delete($table_name, [$primary_key => intval($_GET['delete_record'])]);
        wp_redirect(remove_query_arg(['delete_record', 'edit_record', 'added', 'updated']));
        exit;
    }

    if (!empty($_POST)) {
        $data   = [];
        $format = [];

        foreach ($columns as $field => $label) {
            $raw = $_POST[$field] ?? '';
            $value = $field === 'notes' ? sanitize_textarea_field($raw) : sanitize_text_field($raw);
            if ($value === '') {
                echo '<p style="color:red;">⚠️ All fields are required.</p>';
                return;
            }
            $data[$field] = $value;
            $format[] = is_numeric($value) ? '%f' : '%s';
        }

        if (isset($_POST['update_record'])) {
            $wpdb->update($table_name, $data, [$primary_key => intval($_POST['record_id'])], $format, ['%d']);
            wp_redirect(add_query_arg('updated', '1', remove_query_arg(['edit_record'])));
        } else {
            $wpdb->insert($table_name, $data, $format);
            wp_redirect(add_query_arg('added', '1', $_SERVER['REQUEST_URI']));
        }
        exit;
    }

    if (isset($_GET['edit_record'])) {
        $editing = true;
        $edit_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE $primary_key = %d", intval($_GET['edit_record'])));
    }

    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $order_by = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $columns) ? $_GET['orderby'] : $primary_key;
    $order_dir = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

    $query = "SELECT * FROM $table_name";
    if ($search_term) {
        $where = array_map(fn($col) => "$col LIKE '%$search_term%'", array_keys($columns));
        $query .= " WHERE " . implode(' OR ', $where);
    }
    $query .= " ORDER BY $order_by $order_dir";

    $per_page = 5;
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($page - 1) * $per_page;
    $total = $wpdb->get_var(str_replace('SELECT *', 'SELECT COUNT(*)', $query));
    $query .= " LIMIT $offset, $per_page";
    $rows = $wpdb->get_results($query);

    ob_start();

    echo '<style>
    .table-wrapper { overflow-x: auto; margin-top: 20px; }
    .wp-books-table { width: auto; border-collapse: collapse; table-layout: auto; }
    .wp-books-table th, .wp-books-table td {
        border: 1px solid #ccc;
        padding: 10px;
        vertical-align: top;
        text-align: left;
        word-break: break-word;
    }
    .wp-books-table th {
        background-color: #f9f9f9;
        font-weight: bold;
        white-space: nowrap;
    }
    textarea { width: 100%; }
    </style>';

    if (isset($_GET['added'])) echo '<p style="color:green;">✅ Record added successfully!</p>';
    if (isset($_GET['updated'])) echo '<p style="color:green;">✅ Record updated successfully!</p>';

    // echo '<input type="text" name="search" value="' . esc_attr($search_term) . '" placeholder="Search...">';
    // echo '<input type="submit" value="Search">';
    if ($search_term) {
        echo ' <a href="' . esc_url(remove_query_arg('search')) . '">Clear</a>';
    }
    echo '</form>';

    echo '<form method="post" style="margin-top:20px;">';
    if ($editing && $edit_data) {
        echo '<input type="hidden" name="record_id" value="' . esc_attr($edit_data->$primary_key) . '">';
    }

    foreach ($columns as $field => $label) {
        echo '<p><label>' . esc_html($label) . ':</label><br>';
        if ($field === 'notes') {
            echo '<textarea name="' . esc_attr($field) . '" rows="3">' . ($editing && property_exists($edit_data, $field) ? esc_textarea($edit_data->$field) : '') . '</textarea>';
        } else {
            echo '<input type="text" name="' . esc_attr($field) . '" required value="' . ($editing && property_exists($edit_data, $field) ? esc_attr($edit_data->$field) : '') . '">';
        }
        echo '</p>';
    }

    echo '<p><input type="submit" name="' . ($editing ? 'update_record' : 'add_record') . '" value="' . ($editing ? 'Update' : 'Add') . '">';
    if ($editing) {
        echo ' <a href="' . esc_url(remove_query_arg('edit_record')) . '">Cancel</a>';
    }
    echo '</p></form>';

    echo '<div class="table-wrapper">';

    // Table header info bar
    echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
    
    // Left: record count
    echo '<div><strong>🔍 Showing ' . count($rows) . ' record' . (count($rows) !== 1 ? 's' : '') . '</strong></div>';
    
    // Right: search box
    echo '<form method="get" style="margin: 0;">';
    echo '<input type="text" name="search" value="' . esc_attr($search_term) . '" placeholder="Search..." />';
    echo '<input type="submit" value="Search" />';
    if ($search_term) {
        echo ' <a href="' . esc_url(remove_query_arg('search')) . '">Clear</a>';
    }
    echo '</form>';
    
    echo '</div>'; // end flex
    
    echo '<table class="wp-books-table">';

    foreach (array_merge([$primary_key => 'ID'], $columns) as $col => $label) {
        $new_order = ($order_by === $col && $order_dir === 'ASC') ? 'desc' : 'asc';
        echo '<th><a href="' . esc_url(add_query_arg(['orderby' => $col, 'order' => $new_order])) . '">' . esc_html($label) . '</a></th>';
    }
    echo '<th>Actions</th></tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_merge([$primary_key], array_keys($columns)) as $field) {
            echo '<td>';
            if (property_exists($row, $field) && $row->$field !== null && $row->$field !== '') {
                $value = esc_html($row->$field);
                echo ($field === 'notes') ? nl2br($value) : $value;
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '<td><a href="' . esc_url(add_query_arg('edit_record', $row->$primary_key)) . '">Edit</a> | ';
        echo '<a href="' . esc_url(add_query_arg('delete_record', $row->$primary_key)) . '" onclick="return confirm(\'Delete this record?\');">Delete</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';

    if ($total > $per_page) {
        echo '<div style="margin-top: 15px;">';
        echo 'Page ' . $page . ' of ' . ceil($total / $per_page) . ' ';
        if ($page > 1) {
            echo '<a href="' . esc_url(add_query_arg('paged', $page - 1)) . '">&laquo; Prev</a> ';
        }
        if ($page < ceil($total / $per_page)) {
            echo '<a href="' . esc_url(add_query_arg('paged', $page + 1)) . '">Next &raquo;</a>';
        }
        echo '</div>';
    }

    return ob_get_clean();
}
