/**
 * Custom Table CRUD Frontend JavaScript
 * 
 * @package CustomTableCRUD
 */

(function($) {
    'use strict';
    
    /**
     * Initialize the frontend functionality
     */
    function init() {
        // Add click event for delete buttons
        $('.action-delete').on('click', function(e) {
            const confirmMsg = $(this).data('confirm') || ctcrudSettings.i18n.confirmDelete;
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Enhance form validation
        $('.custom-table-crud-form').on('submit', function(e) {
            let valid = true;
            
            $(this).find('input[required], textarea[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('error');
                    valid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                $(this).find('.validation-message').remove();
                $(this).prepend('<div class="error-message validation-message">' + 
                    ctcrudSettings.i18n.pleaseCompleteAllFields + '</div>');
            }
        });
        
        // Clear validation errors when typing
        $('.custom-table-crud-form input, .custom-table-crud-form textarea').on('input', function() {
            $(this).removeClass('error');
            if ($(this).closest('form').find('.error').length === 0) {
                $(this).closest('form').find('.validation-message').remove();
            }
        });
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    
})(jQuery);