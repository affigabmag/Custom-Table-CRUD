/**
 * Custom Table CRUD Admin JavaScript
 * 
 * @package CustomTableCRUD
 */

(function($) {
    'use strict';
    
    /**
     * Load table fields via AJAX
     * 
     * @param {string} tableName The name of the database table
     */
    window.loadFields = function(tableName) {
        const container = document.getElementById("field_select_container");
        if (!container) return;
        
        container.innerHTML = '<p>' + ctcrudAdminSettings.i18n.loadingFields + '</p>';
        
        // Log AJAX request for debugging
        console.log('Loading fields for table:', tableName);
        console.log('AJAX settings:', {
            url: ctcrudAdminSettings.ajaxurl,
            nonce: ctcrudAdminSettings.nonce
        });
        
        $.ajax({
            url: ctcrudAdminSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_table_fields',
                table: tableName,
                nonce: ctcrudAdminSettings.nonce
            },
            success: function(response) {
                console.log('AJAX Response:', response);
                
                if (response.success) {
                    container.innerHTML = response.data.html;
                } else {
                    container.innerHTML = '<p class="error-message">' + 
                        (response.data && response.data.message ? response.data.message : ctcrudAdminSettings.i18n.errorLoadingFields) + 
                        '</p>';
                    
                    // Show additional error info if available
                    if (response.data && response.data.sql_error) {
                        container.innerHTML += '<p class="error-message">SQL Error: ' + response.data.sql_error + '</p>';
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                container.innerHTML = '<p class="error-message">' + ctcrudAdminSettings.i18n.errorLoadingFields + '</p>';
            }
        });
    };
    
    /**
     * Generate shortcode from form data
     */
    window.generateShortcode = function() {
        const tableView = document.getElementById("table_view").value;
        if (!tableView) {
            alert(ctcrudAdminSettings.i18n.noTableSelected);
            return;
        }
        
        const pagination = document.getElementById("pagination")?.value || 5;
        
        // Get UI options
        const showRecordsCount = document.getElementById("showrecordscount")?.checked ? "true" : "false";
        const showEdit = document.querySelector('input[name="showedit"]')?.checked ? "true" : "false";
        const showDelete = document.querySelector('input[name="showdelete"]')?.checked ? "true" : "false";
        const showForm = document.getElementById("show_form")?.checked ? "true" : "false";
        const showTable = document.getElementById("show_table")?.checked ? "true" : "false";
        const showSearch = document.getElementById("show_search")?.checked ? "true" : "false";
        const showPagination = document.getElementById("show_pagination")?.checked ? "true" : "false";
        const showActions = document.querySelector('input[name="showactions"]')?.checked ? "true" : "false";
        
        // Process fields
        const fieldWrappers = document.querySelectorAll(".field-wrapper");
        let fieldIndex = 1;
        let fieldsText = "";
        let hasFields = false;
        
        fieldWrappers.forEach(wrapper => {
            const checkbox = wrapper.querySelector("input[type=checkbox]");
            if (checkbox && checkbox.checked) {
                hasFields = true;
                const fieldname = checkbox.value;
                const displayname = wrapper.querySelector("input[name^=displayname_]").value || fieldname;
                const displaytype = wrapper.querySelector("select[name^=type_]").value;
                const readonlyCheckbox = wrapper.querySelector("input[name^=readonly_]");
                // Always include readonly with true/false value instead of conditionally adding it
                const readonly = ";readonly=" + (readonlyCheckbox && readonlyCheckbox.checked ? "true" : "false");
                
                if (displaytype === 'key-value' || displaytype === 'query') {
                    const query = wrapper.querySelector(".key-value-textarea")?.value || "";
                    fieldsText += ` field${fieldIndex}="fieldname=${fieldname};displayname=${displayname};displaytype=${displaytype};query=${query}${readonly}"`;
                } else {
                    fieldsText += ` field${fieldIndex}="fieldname=${fieldname};displayname=${displayname};displaytype=${displaytype}${readonly}"`;
                }
                fieldIndex++;
            }
        });
        
        if (!hasFields) {
            alert(ctcrudAdminSettings.i18n.noFieldsSelected);
            return;
        }
        
        // Build the shortcode
        let shortcode = `[wp_table_manager pagination="${pagination}" table_view="${tableView}" showrecordscount="${showRecordsCount}" showform="${showForm}" showtable="${showTable}" showsearch="${showSearch}" showpagination="${showPagination}" showedit="${showEdit}" showdelete="${showDelete}" showactions="${showActions}"${fieldsText}]`;
        
        // Set the shortcode in the textarea
        const textarea = document.getElementById("shortcode_output");
        if (textarea) {
            textarea.value = shortcode;
        }
        
        // Auto-copy to clipboard
        copyShortcode();
    };
    
    /**
     * Copy shortcode to clipboard
     */
    window.copyShortcode = function() {
        const textarea = document.getElementById("shortcode_output");
        if (!textarea || !textarea.value) return;
        
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        // Show "Copied!" message
        const msg = document.getElementById("copy-message");
        if (msg) {
            msg.style.display = "inline";
            setTimeout(() => { msg.style.display = "none"; }, 3000);
        }
    };
    
    /**
     * Initialize table manager functionality
     */
    function initTableManager() {
        // Handle field type change to show/hide appropriate length input
        $(document).on('change', 'select[name^="field_type"]', function() {
            const value = $(this).val();
            const lengthInput = $(this).closest('.field-row').find('input[name^="field_length"]');
            
            if (value === 'text') {
                lengthInput.val('').prop('disabled', true);
            } else {
                lengthInput.prop('disabled', false);
                
                // Set default length based on field type
                if (value === 'int') {
                    lengthInput.val('11');
                } else if (value === 'varchar') {
                    lengthInput.val('255');
                } else if (value === 'decimal') {
                    lengthInput.val('10,2');
                }
            }
        });
        
        // Add field button handler
        $('.add-field').on('click', function() {
            const template = $('.field-template').clone();
            template.removeClass('field-template').show();
            
            // Ensure primary key is unchecked for new fields
            template.find('input[name="field_primary[]"]').prop('checked', false);
            
            // Add to container
            $('#fields-container').append(template);
            
            // Update remove button status
            updateRemoveButtonStatus();
        });
        
        // Remove field button handler (using event delegation)
        $(document).on('click', '.remove-field', function() {
            $(this).closest('.field-row').remove();
            updateRemoveButtonStatus();
        });
        
        // Handle primary key selection (only one allowed)
        $(document).on('change', 'input[name="field_primary[]"]', function() {
            if ($(this).is(':checked')) {
                // Uncheck all other primary key checkboxes
                $('input[name="field_primary[]"]').not(this).prop('checked', false);
            }
        });
        
        // Handle AUTO_INCREMENT selection (only for primary key)
        $(document).on('change', 'select[name="field_extra[]"]', function() {
            const value = $(this).val();
            if (value === 'auto_increment') {
                const row = $(this).closest('.field-row');
                // Check the primary key checkbox
                row.find('input[name="field_primary[]"]').prop('checked', true).trigger('change');
                // Set type to int
                row.find('select[name="field_type[]"]').val('int').trigger('change');
            }
        });
        
        // Update remove button status on page load
        updateRemoveButtonStatus();
        
        // Confirm table deletion
        $(document).on('click', '.cancel-delete', function () {



            $(this).closest('.delete-confirmation').remove();
        });
        
        // Initialize edit field form
        $('#modify_field_name').on('change', function() {
            const fieldName = $(this).val();
            const structure = getTableStructure();
            
            // Find the selected field
            const field = structure.find(f => f.name === fieldName);
            if (field) {
                $('#modify_field_type').val(field.type || 'varchar');
                $('#modify_field_length').val(field.length || '');
                $('input[name="field_null"]').prop('checked', field.null);
                $('#modify_field_default').val(field.default || '');
            }
        });

        // Trigger change to populate initial values
        $('#modify_field_name').trigger('change');
    }
    
    /**
     * Update remove button status (disable if only one field remains)
     */
    function updateRemoveButtonStatus() {
        const visibleFields = $('.field-row:visible').length;
        $('.remove-field').prop('disabled', visibleFields <= 1);
    }
    
    /**
     * Get table structure from the current page
     * @returns {Array} Array of field objects
     */
    function getTableStructure() {
        const structure = [];
        
        $('.current-structure table tbody tr').each(function() {
            const cells = $(this).find('td');
            
            if (cells.length >= 7) {
                structure.push({
                    name: $(cells[0]).text(),
                    type: $(cells[1]).text(),
                    length: $(cells[2]).text(),
                    null: $(cells[3]).text() === '✓',
                    default: $(cells[4]).text(),
                    extra: $(cells[5]).text(),
                    key: $(cells[6]).text()
                });
            }
        });
        
        return structure;
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Check for selected table and automatically load fields
        const selectedTable = document.getElementById("table_view");
        if (selectedTable && selectedTable.value) {
            loadFields(selectedTable.value);
        }
        
        // Add click handler for table selection
        $('.table-seleget_table_fields ction-container tr').on('click', function() {
            const tableLink = $(this).find('a').attr('href');
            if (tableLink) {
                window.location.href = tableLink;
            }
        });
        
        // Initialize table manager functionality
        initTableManager();

        $(document).on('change', 'select[name^="type_"]', function() {
            const keyValueQuery = $(this).closest('.field-wrapper').find('.key-value-query');
            if ($(this).val() === 'key-value' || $(this).val() === 'query') {
                keyValueQuery.wrap('<div class="key-value-query-box"></div>').show();
            } else {
                keyValueQuery.hide();
            }
        });

        // Refresh tables button
        $('#refresh-tables').on('click', function () {
            location.reload();
        });

        $(document).on('click', '.cancel-delete', function () {
            $(this).closest('.delete-confirmation').remove();
        });
    });
    
})(jQuery);