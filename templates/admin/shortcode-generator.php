<?php
/**
 * Shortcode generator template
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Shortcode Generator', 'custom-table-crud'); ?></h1>
    
    <div class="shortcode-generator-container">
        <div class="table-selection">
            <h2><?php esc_html_e('Step 1: Select a Table', 'custom-table-crud'); ?></h2>
            
            <?php if (empty($tables)): ?>
                <p><?php esc_html_e('No tables found in your database.', 'custom-table-crud'); ?></p>
            <?php else: ?>
                <div class="table-selection-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Table Name', 'custom-table-crud'); ?></th>
                                <th><?php esc_html_e('Records', 'custom-table-crud'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tables as $table): 
                                $record_count = $wpdb->get_var("SELECT COUNT(*) FROM " . esc_sql($table));
                                $is_selected = ($selected_table === $table) ? ' class="selected"' : '';
                            ?>
                                <tr<?php echo $is_selected; ?>>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg('table', $table)); ?>" style="text-decoration: none; display: block;">
                                            <?php echo esc_html($table); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($record_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($selected_table)): ?>
            <form method="post" id="shortcode-generator-form" onsubmit="generateShortcode(); return false;">
                <?php 
                // Make sure we're using custom_crud_admin_nonce which matches what we check in Ajax_Handler
                wp_nonce_field('custom_crud_admin_nonce', 'custom_crud_nonce'); 
                ?>
                
                <input type="hidden" id="table_view" name="table_view" value="<?php echo esc_attr($selected_table); ?>">
                
                <h2><?php esc_html_e('Step 2: Configure Fields', 'custom-table-crud'); ?></h2>
                <h3><?php echo esc_html__('Selected Table:', 'custom-table-crud') . ' ' . esc_html($selected_table); ?></h3>
                
                <div id="field_select_container" class="field-selection">
                    <p><?php esc_html_e('Loading fields...', 'custom-table-crud'); ?></p>
                </div>
                
                <h2><?php esc_html_e('Step 3: Configure Display Options', 'custom-table-crud'); ?></h2>
                
                <div class="display-options">
                    <div class="option-item">
                        <label for="pagination">
                            <strong><?php esc_html_e('Records Per Page:', 'custom-table-crud'); ?></strong>
                        </label>
                        <input type="number" id="pagination" name="pagination" value="5" min="1" max="100">
                    </div>
                    
                    <div class="option-group">
                        <h4><?php esc_html_e('UI Elements to Display:', 'custom-table-crud'); ?></h4>
                        
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" id="show_form" checked> 
                                <?php esc_html_e('Entry Form', 'custom-table-crud'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" id="show_table" checked> 
                                <?php esc_html_e('Data Table', 'custom-table-crud'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" id="show_search" checked> 
                                <?php esc_html_e('Search Box', 'custom-table-crud'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" id="show_pagination" checked> 
                                <?php esc_html_e('Pagination', 'custom-table-crud'); ?>
                            </label>
                            
                            <label>
                                <input type="checkbox" id="showrecordscount" checked> 
                                <?php esc_html_e('Records Count', 'custom-table-crud'); ?>
                            </label>
                            
                            <label><input type="checkbox" name="showactions" checked> Actions Column</label>
                            <label><input type="checkbox" name="showedit" checked> Edit Row</label>
                            <label><input type="checkbox" name="showdelete" checked> Delete Row</label>
                            
                        </div>
                    </div>
                </div>
                
                <div class="shortcode-actions">
                    <button type="button" class="button button-primary" onclick="generateShortcode()">
                        <?php esc_html_e('Generate Shortcode', 'custom-table-crud'); ?>
                    </button>
                    <span id="copy-message"><?php esc_html_e('Copied to clipboard!', 'custom-table-crud'); ?></span>
                </div>
                
                <h2><?php esc_html_e('Step 4: Copy Your Shortcode', 'custom-table-crud'); ?></h2>
                <div class="shortcode-output-container">
                    <textarea id="shortcode_output" readonly></textarea>
                    <button type="button" class="button" onclick="copyShortcode()">
                        <?php esc_html_e('Copy to Clipboard', 'custom-table-crud'); ?>
                    </button>
                </div>
                
                <div class="shortcode-instructions">
                    <h3><?php esc_html_e('How to Use This Shortcode', 'custom-table-crud'); ?></h3>
                    <ol>
                        <li><?php esc_html_e('Copy the shortcode above', 'custom-table-crud'); ?></li>
                        <li><?php esc_html_e('Edit a page or post where you want to display the table', 'custom-table-crud'); ?></li>
                        <li><?php esc_html_e('Paste the shortcode into the editor', 'custom-table-crud'); ?></li>
                        <li><?php esc_html_e('Update or publish your page', 'custom-table-crud'); ?></li>
                    </ol>
                </div>
            </form>
            
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    loadFields("<?php echo esc_js($selected_table); ?>");
                });
            </script>
        <?php endif; ?>
    </div>
</div>