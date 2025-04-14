<?php
/**
 * Pagination template
 *
 * @package CustomTableCRUD
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$total_pages = ceil($total / $per_page);
?>

<div class="custom-table-crud-pagination">
    <form method="post">
        <input type="hidden" name="form_type" value="pagination">
        <?php wp_nonce_field('pagination_nonce', 'pagination_nonce'); ?>
        
        <div class="pagination-controls">
            <?php if ($page > 1): ?>
                <button type="submit" name="paged" value="<?php echo esc_attr($page - 1); ?>" class="page-prev">
                    &laquo; <?php esc_html_e('Previous', 'custom-table-crud'); ?>
                </button>
            <?php endif; ?>
            
            <span class="pagination-info">
                <?php echo esc_html(sprintf(__('Page %d of %d', 'custom-table-crud'), $page, $total_pages)); ?>
            </span>
            
            <?php if ($page < $total_pages): ?>
                <button type="submit" name="paged" value="<?php echo esc_attr($page + 1); ?>" class="page-next">
                    <?php esc_html_e('Next', 'custom-table-crud'); ?> &raquo;
                </button>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 2): ?>
            <div class="pagination-jump">
                <label for="jump-to-page"><?php esc_html_e('Go to page:', 'custom-table-crud'); ?></label>
                <select name="paged" id="jump-to-page" onchange="this.form.submit()">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <option value="<?php echo esc_attr($i); ?>" <?php selected($page, $i); ?>>
                            <?php echo esc_html($i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        <?php endif; ?>
    </form>
</div>