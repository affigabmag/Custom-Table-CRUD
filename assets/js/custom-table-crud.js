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
            
            // Handle required fields (except checkboxes)
            $(this).find('input[required], textarea[required]').each(function() {
                if ($(this).attr('type') !== 'checkbox' && !$(this).val().trim()) {
                    $(this).addClass('error');
                    valid = false;
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // Handle unchecked checkboxes (ensure they have a value of 0)
            $(this).find('input[type="checkbox"]').each(function() {
                if (!$(this).is(':checked') && !$(this).siblings('input[type="hidden"][name="' + $(this).attr('name') + '"]').length) {
                    // Add a hidden field with value 0 for unchecked checkboxes
                    $(this).after('<input type="hidden" name="' + $(this).attr('name') + '" value="0">');
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