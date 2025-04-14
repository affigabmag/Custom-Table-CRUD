<?php
/**
 * Search form template
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="get" class="custom-table-crud-search">
    <?php 
    // Preserve existing query parameters except search and pagination
    foreach ($_GET as $key => $value) {
        if ($key !== 'search' && $key !== 'paged') {
            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
        }
    }
    ?>
    
    <div class="wp-books-toolbar">
        <?php if ($showrecordscount !== 'false'): ?>
            <div class="records-count">
                <?php echo esc_html(sprintf(__('Records: %d', 'custom-table-crud'), $total)); ?>
            </div>
        <?php endif; ?>
        
        <div class="search-form">
            <input type="text" 
                name="search" 
                value="<?php echo esc_attr($search_term); ?>" 
                placeholder="<?php esc_attr_e('Search...', 'custom-table-crud'); ?>">
            
            <input type="submit" 
                value="<?php esc_attr_e('Search', 'custom-table-crud'); ?>">
            
            <?php if ($search_term): ?>
                <a class="clear-link" href="<?php echo esc_url(remove_query_arg('search')); ?>">
                    <?php esc_html_e('Clear', 'custom-table-crud'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</form>