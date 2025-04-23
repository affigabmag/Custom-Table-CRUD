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
        
        // Initialize Select2 for key-value and query fields
        $('.key-value-select, .query-select').select2({
            placeholder: 'Search...',
            allowClear: true,
            minimumInputLength: 0,
            width: '100%',
            dropdownParent: $('.custom-table-crud-form')
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
            
            // Validate phone number fields
            $(this).find('input[type="tel"]').each(function() {
                if ($(this).val().trim() && !validatePhoneNumber($(this))) {
                    valid = false;
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

        // Initialize query-type fields
        $('.query-select').each(function() {
            const $select = $(this);
            const query = $select.data('query');
            
            if (query) {
                $select.select2({
                    placeholder: 'Search...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('.custom-table-crud-form'),
                    ajax: {
                        url: ctcrudSettings.ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'load_query_results',
                                nonce: ctcrudSettings.nonce,
                                query: query,
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.success ? data.data.results : []
                            };
                        },
                        cache: true
                    }
                });
            }
        });
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    

    // Phone number validation function
    function validatePhoneNumber(input) {
        const value = input.val();
        const pattern = /^(\+\d{1,3})?[-.\s]?\(?(\d{3})\)?[-.\s]?(\d{3})[-.\s]?(\d{4})$/;
        
        if (!pattern.test(value)) {
            input.addClass('error');
            if (input.next('.error-message').length === 0) {
                input.after('<small class="error-message">Please enter a valid phone number (e.g., +1 123-456-7890)</small>');
            }
            return false;
        } else {
            input.removeClass('error');
            input.next('.error-message').remove();
            return true;
        }
    }

})(jQuery);