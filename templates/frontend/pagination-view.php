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

        <?php if ($total_pages > 1): ?>
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
