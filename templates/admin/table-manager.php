<?php
/**
 * Table manager template
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Process form submissions
$message = '';
$message_type = '';

if (isset($_POST['create_table']) && wp_verify_nonce($_POST['_wpnonce'], 'custom_crud_create_table')) {
    // Process table creation
    $table_name = sanitize_text_field($_POST['table_name']);
    
    // Ensure table name has the WP prefix
    global $wpdb;
    if (strpos($table_name, $wpdb->prefix) !== 0) {
        $table_name = $wpdb->prefix . $table_name;
    }
    
    // Collect field definitions
    $fields = array();
    
    for ($i = 0; $i < count($_POST['field_name']); $i++) {
        if (!empty($_POST['field_name'][$i])) {
            $fields[] = array(
                'name' => sanitize_key($_POST['field_name'][$i]),
                'type' => sanitize_text_field($_POST['field_type'][$i]),
                'length' => sanitize_text_field($_POST['field_length'][$i]),
                'null' => isset($_POST['field_null'][$i]),
                'default' => sanitize_text_field($_POST['field_default'][$i]),
                'extra' => sanitize_text_field($_POST['field_extra'][$i]),
                'primary' => isset($_POST['field_primary'][$i])
            );
        }
    }
    
    // Create the table
    $result = $table_manager->create_table($table_name, $fields);
    
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $message_type = 'error';
    } else {
        $message = __('Table created successfully!', 'custom-table-crud');
        $message_type = 'success';
    }
}

// Check if we need to confirm table deletion
$delete_table = isset($_GET['delete_table']) ? sanitize_text_field($_GET['delete_table']) : '';
$delete_confirmed = isset($_GET['confirm_delete']) && $_GET['confirm_delete'] === 'yes';
$delete_nonce_valid = isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_table_' . $delete_table);

if ($delete_table && $delete_confirmed && $delete_nonce_valid) {
    $result = $table_manager->delete_table($delete_table);
    
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $message_type = 'error';
    } else {
        $message = __('Table deleted successfully!', 'custom-table-crud');
        $message_type = 'success';
    }
    
    // Redirect to remove query args
    wp_redirect(remove_query_arg(array('delete_table', 'confirm_delete', '_wpnonce')));
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Table Manager', 'custom-table-crud'); ?></h1>
    
    <?php if ($message): ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url(add_query_arg('tab', 'list')); ?>" class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Tables', 'custom-table-crud'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="nav-tab <?php echo $active_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Create New Table', 'custom-table-crud'); ?>
        </a>
        <?php if ($active_tab === 'edit' && !empty($selected_table)): ?>
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'edit', 'table' => $selected_table))); ?>" class="nav-tab nav-tab-active">
                <?php echo esc_html(sprintf(__('Edit: %s', 'custom-table-crud'), $selected_table)); ?>
            </a>
        <?php endif; ?>
    </h2>
    
    <?php if ($active_tab === 'list'): ?>
        <!-- Display list of tables -->
        <div class="table-list-container">
            <h2>
                <?php esc_html_e('Available Database Tables', 'custom-table-crud'); ?>
                    <button type="button" id="refresh-tables" class="button" style="margin-left: 10px;" title="Refresh Tables">
                        🔄
                    </button>
            </h2>

            
            <?php 
            // Get tables including WordPress core tables
            $tables = $table_manager->get_tables(true);
            
            if (empty($tables)): 
            ?>
                <p><?php esc_html_e('No tables found in your database.', 'custom-table-crud'); ?></p>
                <p>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'create')); ?>" class="button button-primary">
                        <?php esc_html_e('Create a New Table', 'custom-table-crud'); ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Table Name', 'custom-table-crud'); ?></th>
                                <th><?php esc_html_e('Records', 'custom-table-crud'); ?></th>
                                <th><?php esc_html_e('Actions', 'custom-table-crud'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            global $wpdb;
                            foreach ($tables as $table): 
                                $record_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                            ?>
                                <tr>
                                    <td><?php echo esc_html($table); ?></td>
                                    <td><?php echo esc_html($record_count); ?></td>
                                    <td class="actions">
                                        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'edit', 'table' => $table))); ?>" class="button button-small">
                                            <?php esc_html_e('Edit Structure', 'custom-table-crud'); ?>
                                        </a>
                                        
                                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'custom_crud_shortcode', 'table' => $table))); ?>" class="button button-small">
                                            <?php esc_html_e('Generate Shortcode', 'custom-table-crud'); ?>
                                        </a>
                                        
                                        <?php if (!$delete_table || $delete_table !== $table): ?>
                                            <a href="<?php echo esc_url(add_query_arg(array('delete_table' => $table, '_wpnonce' => wp_create_nonce('delete_table_' . $table)))); ?>" class="button button-small button-link-delete">
                                                <?php esc_html_e('Delete', 'custom-table-crud'); ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="delete-confirmation">
                                                <span class="warning"><?php esc_html_e('Are you sure?', 'custom-table-crud'); ?></span>
                                                
                                                <a href="<?php echo esc_url(add_query_arg(array('delete_table' => $table, 'confirm_delete' => 'yes', '_wpnonce' => wp_create_nonce('delete_table_' . $table)))); ?>" class="button button-small button-link-delete">
                                                    <?php esc_html_e('Yes, Delete', 'custom-table-crud'); ?>
                                                </a>
                                                
                                                <a href="<?php echo esc_url(remove_query_arg('delete_table')); ?>" class="button button-small">
                                                    <?php esc_html_e('Cancel', 'custom-table-crud'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($active_tab === 'create'): ?>
        <!-- Display table creation form -->
        <div class="table-create-container">
            <h3><?php esc_html_e('Create a New Table', 'custom-table-crud'); ?></h3>
            
            <form method="post" id="create-table-form">
                <?php wp_nonce_field('custom_crud_create_table'); ?>
                
                <div class="form-field">
                    <label for="table_name">
                        <strong><?php esc_html_e('Table Name:', 'custom-table-crud'); ?></strong>
                    </label>
                    
                    <div class="table-name-input-wrapper">
                        <span class="prefix"><?php echo esc_html($wpdb->prefix); ?></span>
                        <input type="text" id="table_name" name="table_name" required>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Table name will be prefixed with your WordPress database prefix.', 'custom-table-crud'); ?>
                    </p>
                </div>
                
                <div class="form-field">
                    <h4><?php esc_html_e('Table Fields', 'custom-table-crud'); ?></h4>
                    
                    <div id="fields-container">
                        <!-- Default ID field -->
                        <div class="field-row">
                            <div class="field-cell">
                                <input type="text" name="field_name[]" value="id" placeholder="<?php esc_attr_e('Field Name', 'custom-table-crud'); ?>" required>
                            </div>
                            
                            <div class="field-cell">
                                <select name="field_type[]">
                                    <option value="int" selected><?php esc_html_e('Integer', 'custom-table-crud'); ?></option>
                                    <option value="varchar"><?php esc_html_e('Text (VARCHAR)', 'custom-table-crud'); ?></option>
                                    <option value="text"><?php esc_html_e('Text (TEXT)', 'custom-table-crud'); ?></option>
                                    <option value="date"><?php esc_html_e('Date', 'custom-table-crud'); ?></option>
                                    <option value="datetime"><?php esc_html_e('DateTime', 'custom-table-crud'); ?></option>
                                    <option value="decimal"><?php esc_html_e('Decimal', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell field-length">
                                <input type="text" name="field_length[]" value="11" placeholder="<?php esc_attr_e('Length', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell field-options">
                                <label>
                                    <input type="checkbox" name="field_primary[]" checked>
                                    <?php esc_html_e('Primary', 'custom-table-crud'); ?>
                                </label>
                                
                                <label>
                                    <input type="checkbox" name="field_null[]">
                                    <?php esc_html_e('Allow NULL', 'custom-table-crud'); ?>
                                </label>
                            </div>
                            
                            <div class="field-cell">
                                <input type="text" name="field_default[]" placeholder="<?php esc_attr_e('Default Value', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell">
                                <select name="field_extra[]">
                                    <option value=""><?php esc_html_e('None', 'custom-table-crud'); ?></option>
                                    <option value="auto_increment" selected><?php esc_html_e('AUTO_INCREMENT', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell">
                                <button type="button" class="button remove-field" disabled>
                                    <?php esc_html_e('Remove', 'custom-table-crud'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Additional field row template (for JavaScript cloning) -->
                        <div class="field-row field-template" style="display: none;">
                            <div class="field-cell">
                                <input type="text" name="field_name[]" placeholder="<?php esc_attr_e('Field Name', 'custom-table-crud'); ?>" required>
                            </div>
                            
                            <div class="field-cell">
                                <select name="field_type[]">
                                    <option value="int"><?php esc_html_e('Integer', 'custom-table-crud'); ?></option>
                                    <option value="varchar" selected><?php esc_html_e('Text (VARCHAR)', 'custom-table-crud'); ?></option>
                                    <option value="text"><?php esc_html_e('Text (TEXT)', 'custom-table-crud'); ?></option>
                                    <option value="date"><?php esc_html_e('Date', 'custom-table-crud'); ?></option>
                                    <option value="datetime"><?php esc_html_e('DateTime', 'custom-table-crud'); ?></option>
                                    <option value="decimal"><?php esc_html_e('Decimal', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell field-length">
                                <input type="text" name="field_length[]" value="255" placeholder="<?php esc_attr_e('Length', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell field-options">
                                <label>
                                    <input type="checkbox" name="field_primary[]">
                                    <?php esc_html_e('Primary', 'custom-table-crud'); ?>
                                </label>
                                
                                <label>
                                    <input type="checkbox" name="field_null[]">
                                    <?php esc_html_e('Allow NULL', 'custom-table-crud'); ?>
                                </label>
                            </div>
                            
                            <div class="field-cell">
                                <input type="text" name="field_default[]" placeholder="<?php esc_attr_e('Default Value', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell">
                                <select name="field_extra[]">
                                    <option value=""><?php esc_html_e('None', 'custom-table-crud'); ?></option>
                                    <option value="auto_increment"><?php esc_html_e('AUTO_INCREMENT', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell">
                                <button type="button" class="button remove-field">
                                    <?php esc_html_e('Remove', 'custom-table-crud'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="field-actions">
                        <button type="button" class="button add-field">
                            <?php esc_html_e('Add Field', 'custom-table-crud'); ?>
                        </button>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="create_table" class="button button-primary" value="<?php esc_attr_e('Create Table', 'custom-table-crud'); ?>">
                </p>
            </form>
        </div>
        
    <?php elseif ($active_tab === 'edit' && !empty($selected_table)): ?>
        <!-- Display table edit form -->
        <div class="table-edit-container">
            <h3><?php echo esc_html(sprintf(__('Edit Table: %s', 'custom-table-crud'), $selected_table)); ?></h3>
            
            <?php
            // Get table structure
            $structure = $table_manager->get_table_structure($selected_table);
            
            if (empty($structure)): 
            ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('Could not retrieve table structure.', 'custom-table-crud'); ?></p>
                </div>
            <?php else: ?>
                
                <!-- Display current structure -->
                <div class="current-structure">
                    <h4><?php esc_html_e('Current Table Structure', 'custom-table-crud'); ?></h4>
                    
                    <div class="table-responsive">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Field', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Type', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Length', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Allow NULL', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Default', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Extra', 'custom-table-crud'); ?></th>
                                    <th><?php esc_html_e('Key', 'custom-table-crud'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($structure as $field): ?>
                                    <tr>
                                        <td><?php echo esc_html($field['name']); ?></td>
                                        <td><?php echo esc_html($field['type']); ?></td>
                                        <td><?php echo esc_html($field['length']); ?></td>
                                        <td><?php echo $field['null'] ? '✓' : '✗'; ?></td>
                                        <td><?php echo esc_html($field['default']); ?></td>
                                        <td><?php echo esc_html($field['extra']); ?></td>
                                        <td><?php echo esc_html($field['key']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Add New Column Form -->
                <div class="add-column-form">
                    <h4><?php esc_html_e('Add New Column', 'custom-table-crud'); ?></h4>
                    
                    <form method="post" id="add-column-form">
                        <?php wp_nonce_field('custom_crud_modify_table'); ?>
                        <input type="hidden" name="table_name" value="<?php echo esc_attr($selected_table); ?>">
                        <input type="hidden" name="operation" value="add_column">
                        
                        <div class="field-row">
                            <div class="field-cell">
                                <label for="new_field_name"><?php esc_html_e('Field Name:', 'custom-table-crud'); ?></label>
                                <input type="text" id="new_field_name" name="field_name" required>
                            </div>
                            
                            <div class="field-cell">
                                <label for="new_field_type"><?php esc_html_e('Type:', 'custom-table-crud'); ?></label>
                                <select id="new_field_type" name="field_type">
                                    <option value="int"><?php esc_html_e('Integer', 'custom-table-crud'); ?></option>
                                    <option value="varchar" selected><?php esc_html_e('Text (VARCHAR)', 'custom-table-crud'); ?></option>
                                    <option value="text"><?php esc_html_e('Text (TEXT)', 'custom-table-crud'); ?></option>
                                    <option value="date"><?php esc_html_e('Date', 'custom-table-crud'); ?></option>
                                    <option value="datetime"><?php esc_html_e('DateTime', 'custom-table-crud'); ?></option>
                                    <option value="decimal"><?php esc_html_e('Decimal', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell field-length">
                                <label for="new_field_length"><?php esc_html_e('Length:', 'custom-table-crud'); ?></label>
                                <input type="text" id="new_field_length" name="field_length" value="255">
                            </div>
                            
                            <div class="field-cell field-options">
                                <label>
                                    <input type="checkbox" name="field_null">
                                    <?php esc_html_e('Allow NULL', 'custom-table-crud'); ?>
                                </label>
                            </div>
                            
                            <div class="field-cell">
                                <label for="new_field_default"><?php esc_html_e('Default:', 'custom-table-crud'); ?></label>
                                <input type="text" id="new_field_default" name="field_default" placeholder="<?php esc_attr_e('Default Value', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell">
                                <input type="submit" name="add_column" class="button button-primary" value="<?php esc_attr_e('Add Column', 'custom-table-crud'); ?>">
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Modify Column Form -->
                <div class="modify-column-form">
                    <h4><?php esc_html_e('Modify Column', 'custom-table-crud'); ?></h4>
                    
                    <form method="post" id="modify-column-form">
                        <?php wp_nonce_field('custom_crud_modify_table'); ?>
                        <input type="hidden" name="table_name" value="<?php echo esc_attr($selected_table); ?>">
                        <input type="hidden" name="operation" value="modify_column">
                        
                        <div class="field-row">
                            <div class="field-cell">
                                <label for="modify_field_name"><?php esc_html_e('Field:', 'custom-table-crud'); ?></label>
                                <select id="modify_field_name" name="field_name">
                                    <?php foreach ($structure as $field): ?>
                                        <option value="<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="field-cell">
                                <label for="modify_field_type"><?php esc_html_e('Type:', 'custom-table-crud'); ?></label>
                                <select id="modify_field_type" name="field_type">
                                    <option value="int"><?php esc_html_e('Integer', 'custom-table-crud'); ?></option>
                                    <option value="varchar" selected><?php esc_html_e('Text (VARCHAR)', 'custom-table-crud'); ?></option>
                                    <option value="text"><?php esc_html_e('Text (TEXT)', 'custom-table-crud'); ?></option>
                                    <option value="date"><?php esc_html_e('Date', 'custom-table-crud'); ?></option>
                                    <option value="datetime"><?php esc_html_e('DateTime', 'custom-table-crud'); ?></option>
                                    <option value="decimal"><?php esc_html_e('Decimal', 'custom-table-crud'); ?></option>
                                </select>
                            </div>
                            
                            <div class="field-cell field-length">
                                <label for="modify_field_length"><?php esc_html_e('Length:', 'custom-table-crud'); ?></label>
                                <input type="text" id="modify_field_length" name="field_length" value="255">
                            </div>
                            
                            <div class="field-cell field-options">
                                <label>
                                    <input type="checkbox" name="field_null">
                                    <?php esc_html_e('Allow NULL', 'custom-table-crud'); ?>
                                </label>
                            </div>
                            
                            <div class="field-cell">
                                <label for="modify_field_default"><?php esc_html_e('Default:', 'custom-table-crud'); ?></label>
                                <input type="text" id="modify_field_default" name="field_default" placeholder="<?php esc_attr_e('Default Value', 'custom-table-crud'); ?>">
                            </div>
                            
                            <div class="field-cell">
                                <input type="submit" name="modify_column" class="button button-primary" value="<?php esc_attr_e('Modify Column', 'custom-table-crud'); ?>">
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Delete Column Form -->
                <div class="delete-column-form">
                    <h4><?php esc_html_e('Delete Column', 'custom-table-crud'); ?></h4>
                    
                    <form method="post" id="delete-column-form">
                        <?php wp_nonce_field('custom_crud_modify_table'); ?>
                        <input type="hidden" name="table_name" value="<?php echo esc_attr($selected_table); ?>">
                        <input type="hidden" name="operation" value="delete_column">
                        
                        <div class="field-row">
                            <div class="field-cell">
                                <label for="delete_field_name"><?php esc_html_e('Field:', 'custom-table-crud'); ?></label>
                                <select id="delete_field_name" name="field_name">
                                    <?php foreach ($structure as $field): ?>
                                        <?php if (!$field['is_primary']): ?>
                                            <option value="<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['name']); ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="field-cell">
                                <input type="submit" name="delete_column" class="button button-link-delete" value="<?php esc_attr_e('Delete Column', 'custom-table-crud'); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this column? This action cannot be undone!', 'custom-table-crud'); ?>');">
                            </div>
                        </div>
                    </form>
                </div>
                
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>