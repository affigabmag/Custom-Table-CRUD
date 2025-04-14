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
        const showForm = document.getElementById("show_form")?.checked ? "true" : "false";
        const showTable = document.getElementById("show_table")?.checked ? "true" : "false";
        const showSearch = document.getElementById("show_search")?.checked ? "true" : "false";
        const showPagination = document.getElementById("show_pagination")?.checked ? "true" : "false";
        
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
                const readonly = readonlyCheckbox && readonlyCheckbox.checked ? ";readonly=true" : "";
                
                fieldsText += ` field${fieldIndex}="fieldname=${fieldname};displayname=${displayname};displaytype=${displaytype}${readonly}"`;
                fieldIndex++;
            }
        });
        
        if (!hasFields) {
            alert(ctcrudAdminSettings.i18n.noFieldsSelected);
            return;
        }
        
        // Build the shortcode
        let shortcode = `[wp_table_manager pagination="${pagination}" table_view="${tableView}" showrecordscount="${showRecordsCount}" showform="${showForm}" showtable="${showTable}" showsearch="${showSearch}" showpagination="${showPagination}"${fieldsText}]`;
        
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
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Check for selected table and automatically load fields
        const selectedTable = document.getElementById("table_view");
        if (selectedTable && selectedTable.value) {
            loadFields(selectedTable.value);
        }
        
        // Add click handler for table selection
        $('.table-selection-container tr').on('click', function() {
            const tableLink = $(this).find('a').attr('href');
            if (tableLink) {
                window.location.href = tableLink;
            }
        });
    });
    
})(jQuery);