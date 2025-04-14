<?php
/**
 * Table template for displaying records
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="wp-books-table">
    <thead>
        <tr>
            <?php foreach ($columns as $col => $meta): 
                $label = isset($meta['label']) ? $meta['label'] : $col;
                $new_order = ($order_by === $col && $order_dir === 'ASC') ? 'desc' : 'asc';
            ?>
                <th>
                    <a href="<?php echo esc_url(add_query_arg(['orderby' => $col, 'order' => $new_order])); ?>">
                        <?php echo esc_html($label); ?>
                        <?php if ($order_by === $col): ?>
                            <span class="sort-indicator <?php echo esc_attr(strtolower($order_dir)); ?>"></span>
                        <?php endif; ?>
                    </a>
                </th>
            <?php endforeach; ?>
            <th><?php esc_html_e('Actions', 'custom-table-crud'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <?php echo $this->render_table_row($row, $columns, $primary_key); ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo count($columns) + 1; ?>">
                    <?php esc_html_e('No records found.', 'custom-table-crud'); ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>