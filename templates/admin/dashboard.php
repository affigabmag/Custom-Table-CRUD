<?php
/**
 * Admin dashboard template
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Custom CRUD - Dashboard', 'custom-table-crud'); ?></h1>
    
    <div class="dashboard-overview">
        <div class="card">
            <h2><?php esc_html_e('Plugin Overview', 'custom-table-crud'); ?></h2>
            <p><?php esc_html_e('Custom Table CRUD allows you to manage your database tables through shortcodes.', 'custom-table-crud'); ?></p>
            <p><?php esc_html_e('Use the Shortcode Generator to create a shortcode for your database tables.', 'custom-table-crud'); ?></p>
        </div>
        
        <div class="card">
            <h2><?php esc_html_e('Quick Start', 'custom-table-crud'); ?></h2>
            <ol>
                <li><?php esc_html_e('Go to the Table Manager to create a new table', 'custom-table-crud'); ?></li>
                <li><?php esc_html_e('Or use the Shortcode Generator to display an existing table', 'custom-table-crud'); ?></li>
                <li><?php esc_html_e('Configure which fields to display', 'custom-table-crud'); ?></li>
                <li><?php esc_html_e('Copy the generated shortcode', 'custom-table-crud'); ?></li>
                <li><?php esc_html_e('Paste the shortcode into any post or page', 'custom-table-crud'); ?></li>
            </ol>
            <div class="button-container">
                <a href="<?php echo esc_url(admin_url('admin.php?page=custom_crud_table_manager')); ?>" class="button button-primary">
                    <?php esc_html_e('Table Manager', 'custom-table-crud'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=custom_crud_shortcode')); ?>" class="button button-primary">
                    <?php esc_html_e('Shortcode Generator', 'custom-table-crud'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="dashboard-tables">
        <h2><?php esc_html_e('Available Database Tables', 'custom-table-crud'); ?></h2>
        
        <?php if (empty($tables)): ?>
            <p><?php esc_html_e('No tables found in your database.', 'custom-table-crud'); ?></p>
        <?php else: ?>
            <div class="table-selection-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Table Name', 'custom-table-crud'); ?></th>
                            <th><?php esc_html_e('Number of Records', 'custom-table-crud'); ?></th>
                            <th><?php esc_html_e('Actions', 'custom-table-crud'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): 
                            $record_count = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($table));
                        ?>
                            <tr>
                                <td><?php echo esc_html($table); ?></td>
                                <td><?php echo esc_html($record_count); ?></td>
                                <td class="actions">
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'custom_crud_shortcode', 'table' => $table])); ?>" class="button button-small">
                                        <?php esc_html_e('Generate Shortcode', 'custom-table-crud'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg(['page' => 'custom_crud_table_manager', 'tab' => 'edit', 'table' => $table])); ?>" class="button button-small">
                                        <?php esc_html_e('Edit Structure', 'custom-table-crud'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>