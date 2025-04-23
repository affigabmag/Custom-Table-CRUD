<?php
/**
 * Form template for adding/editing records
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" class="custom-table-crud-form">
    <input type="hidden" name="form_type" value="data_form">
    <?php wp_nonce_field('crud_form_nonce', 'crud_nonce'); ?>
    
    <?php if ($editing && $edit_data): ?>
        <input type="hidden" name="record_id" value="<?php echo esc_attr($edit_data->$primary_key); ?>">
    <?php endif; ?>
    
    <?php foreach ($columns as $field => $meta): 
        if ($meta['type'] === 'key-value') {
            echo '<p>';
            echo '<label for="' . esc_attr($field) . '">' . esc_html($meta['label']) . '</label>';
            echo '<select name="' . esc_attr($field) . '" class="key-value-select">';
            echo '<option value="">-- Select --</option>';
            echo '</select>';
            echo '</p>';
            
            continue;
        }
        
        if ($meta['type'] === 'query' && isset($meta['query'])) {
            echo '<p>';
            echo '<label for="' . esc_attr($field) . '">' . esc_html($meta['label']) . '</label>';
            echo '<select name="' . esc_attr($field) . '" id="' . esc_attr($field) . '" class="query-select">';
            echo '<option value="">-- Select --</option>';
            echo '</select>';
            
            echo '<script>
            jQuery(document).ready(function($) {
                console.log("Initializing Select2 for field: ' . esc_attr($field) . '");
                console.log("Query: ' . esc_js($meta['query']) . '");
                console.log("AJAX URL: ' . admin_url('admin-ajax.php') . '");
                
                // Make a direct AJAX call to test
                $.ajax({
                    url: "' . admin_url('admin-ajax.php') . '",
                    type: "POST",
                    data: {
                        action: "load_query_results",
                        nonce: "' . wp_create_nonce('crud_form_nonce') . '",
                        query: "' . esc_js($meta['query']) . '"
                    },
                    success: function(response) {
                        console.log("Direct AJAX test response:", response);
                        if(response.success && response.data.results) {
                            // Add options to select
                            $.each(response.data.results, function(i, item) {
                                $("#' . esc_attr($field) . '").append(new Option(item.text, item.id));
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                    }
                });
                
                // Initialize Select2
                $("#' . esc_attr($field) . '").select2({
                    placeholder: "Search...",
                    allowClear: true,
                    width: "100%",
                    dropdownParent: $(".custom-table-crud-form")
                });
            });
            </script>';
            echo '</p>';
            
            continue;
        }
    
    
        $label = isset($meta['label']) ? $meta['label'] : $field;
        $type = isset($meta['type']) ? $meta['type'] : 'text';
        $readonly = isset($meta['readonly']) && $meta['readonly'] === 'true';
        $value = isset($_POST[$field]) ? $_POST[$field] : ($editing && isset($edit_data->$field) ? $edit_data->$field : '');
        $error = isset($_POST['form_type'], $_POST[$field]) && trim($_POST[$field]) === '';
    ?>
        <p>
            <label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?>:</label>
            
            <?php if ($readonly): ?>
                <input type="<?php echo esc_attr($type); ?>" 
                    name="<?php echo esc_attr($field); ?>" 
                    value="<?php echo esc_attr($value); ?>" 
                    readonly disabled>
                <input type="hidden" 
                    name="<?php echo esc_attr($field); ?>" 
                    value="<?php echo esc_attr($value); ?>">
            
            <?php elseif ($type === 'textarea'): ?>
                <textarea name="<?php echo esc_attr($field); ?>" 
                    rows="3" 
                    required><?php echo esc_textarea($value); ?></textarea>
            
            <?php elseif ($type === 'checkbox'): ?>
                <input type="checkbox" 
                    name="<?php echo esc_attr($field); ?>" 
                    value="1" 
                    <?php checked($value, '1'); ?>>

            <?php elseif ($type === 'key-value'): ?>
                <select name="<?php echo esc_attr($field); ?>" 
                        id="<?php echo esc_attr($field); ?>"
                        class="key-value-select" 
                        <?php echo $readonly ? 'disabled' : ''; ?>>
                    <option value=""><?php esc_html_e('Search...', 'custom-table-crud'); ?></option>
                </select>
                <script>
                    jQuery(document).ready(function($) {
                        $('#<?php echo esc_attr($field); ?>').select2({
                            width: '100%',
                            dropdownParent: $('.custom-table-crud-form')
                        });
                    });
                </script>
                
                <?php else: ?>
                    <?php 
                    $step = ($type === 'number') ? ' step="any"' : '';
                    $pattern = ($type === 'url') ? ' pattern="https?://.+"' : '';
                    $pattern = ($type === 'tel') ? ' pattern="(\+\d{1,3})?[-.\s]?\(?(\d{3})\)?[-.\s]?(\d{3})[-.\s]?(\d{4})"' : $pattern;
                    ?>
                    <input type="<?php echo esc_attr($type); ?>" 
                        name="<?php echo esc_attr($field); ?>" 
                        value="<?php echo esc_attr($value); ?>" 
                        required<?php echo $step . $pattern; ?>>
                <?php endif; ?>
            
            <?php if ($error): ?>
                <br><small class="error-message"><?php esc_html_e('This field is required.', 'custom-table-crud'); ?></small>
            <?php endif; ?>
        </p>
    <?php endforeach; ?>
    
    <div class="wp-books-toolbar">
        <input type="submit" 
            name="<?php echo $editing ? 'update_record' : 'add_record'; ?>" 
            value="<?php echo esc_attr($editing ? __('Update', 'custom-table-crud') : __('Add', 'custom-table-crud')); ?>" 
            class="crud-submit-btn">
        
        <?php if ($editing): ?>
            <a href="<?php echo esc_url(remove_query_arg(['edit_record', '_wpnonce'])); ?>" class="button-cancel">
                <?php esc_html_e('Cancel', 'custom-table-crud'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    jQuery(document).ready(function($) {
        $('.basic-select2').select2({
            dropdownParent: $('.custom-table-crud-form')
        });
    });
    </script>

</form>