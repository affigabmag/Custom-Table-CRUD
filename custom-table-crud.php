<?php
/**
 * Plugin Name: Custom Table CRUD with Debug + Pagination Fix
 * Description: CRUD for custom DB tables with working pagination inside shortcodes.
 * Version: 1.3
 * Author: affigabmag
 */

add_shortcode('wp_books_manager', 'wp_books_manager_shortcode');
add_shortcode('wp_warranties_manager', 'wp_warranties_manager_shortcode');

function wp_books_manager_shortcode() {
    return generic_table_manager_shortcode([
        'table_name'  => 'app_books',
        'primary_key' => 'id',
        'columns'     => [
            'bookname'    => ['label' => 'Book Name', 'type' => 'text'],
            'price'       => ['label' => 'Price', 'type' => 'number'],
            'description' => ['label' => 'Description', 'type' => 'textarea']
        ]
    ]);
}

function wp_warranties_manager_shortcode() {
    return generic_table_manager_shortcode([
        'table_name'  => 'app_warranties',
        'primary_key' => 'id',
        'columns'     => [
            'ProductName'     => ['label' => 'Product Name', 'type' => 'text'],
            'DateOfPurchase'  => ['label' => 'Date of Purchase', 'type' => 'date'],
            'Notes'           => ['label' => 'Notes', 'type' => 'textarea']
        ]
    ]);
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

        foreach ($columns as $field => $meta) {
            $value = sanitize_text_field($_POST[$field] ?? '');
            if ($value === '') {
                echo '<p style="color:red;">⚠️ All fields are required.</p>';
                return;
            }
            $data[$field] = $value;
            $format[] = (is_numeric($value) && strpos($value, '.') !== false) ? '%f' : '%s';
        }

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

    $per_page = 5;
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
        $value = $editing && isset($edit_data->$field) ? $edit_data->$field : '';

        echo '<p><label>' . esc_html($label) . ':</label><br>';
        if ($type === 'textarea') {
            echo '<textarea name="' . esc_attr($field) . '" rows="3">' . esc_textarea($value) . '</textarea>';
        } else {
            $step = $type === 'number' ? ' step="any"' : '';
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" required' . $step . '>';
        }
        echo '</p>';
    }

    echo '<p><input type="submit" name="' . ($editing ? 'update_record' : 'add_record') . '" value="' . ($editing ? 'Update' : 'Add') . '">';
    if ($editing) echo ' <a href="' . esc_url(remove_query_arg('edit_record')) . '">Cancel</a>';
    echo '</p></form>';

    echo '<form method="get" style="margin-top:10px;">';
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
        echo '<tr>';
        foreach (array_merge([$primary_key], array_keys($columns)) as $field) {
            echo '<td>' . (isset($row->$field) ? nl2br(esc_html($row->$field)) : '') . '</td>';
        }
        echo '<td><a href="' . esc_url(add_query_arg('edit_record', $row->$primary_key)) . '">Edit</a> | ';
        echo '<a href="' . esc_url(add_query_arg('delete_record', $row->$primary_key)) . '" onclick="return confirm(\'Are you sure?\');">Delete</a></td>';
        echo '</tr>';
    }
    echo '</table>';

    if ($total > $per_page) {
        echo render_pagination_controls($page, $total, $per_page);
    }

    return ob_get_clean();
}
