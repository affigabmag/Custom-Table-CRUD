/**
 * Custom Table CRUD JavaScript Functions
 */

// Load table fields via AJAX
function loadFields(tableName) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", ajaxurl);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById("field_select_container").innerHTML = xhr.responseText;
        }
    };
    const nonce = document.getElementById("custom_crud_nonce").value;
    xhr.send("action=get_table_fields&table=" + encodeURIComponent(tableName) + "&nonce=" + encodeURIComponent(nonce));
}

// Generate shortcode from form data
function generateShortcode() {
    const table = document.getElementById("table_view").value;
    const pagination = document.getElementById("pagination")?.value || 5;

    const showRecords = document.getElementById("showrecordscount").checked ? "true" : "false";

    const wrappers = document.querySelectorAll(".field-wrapper");
    let fieldIndex = 1;
    let fieldsText = "";
    
    wrappers.forEach(wrap => {
        const checkbox = wrap.querySelector("input[type=checkbox]");
        if (checkbox && checkbox.checked) {
            const fieldname = checkbox.value;
            const displayname = wrap.querySelector("input[name^=displayname_]").value || fieldname;
            const displaytype = wrap.querySelector("select[name^=type_]").value;
            const readonlyCheckbox = wrap.querySelector("input[name^=readonly_]");
            const readonly = readonlyCheckbox && readonlyCheckbox.checked ? ";readonly=true" : "";
            fieldsText += ` field${fieldIndex}="fieldname=${fieldname};displayname=${displayname};displaytype=${displaytype}${readonly}"`;
            fieldIndex++;
        }
    });
    
    

    const showRecordsCount = document.getElementById("showrecordscount")?.checked ? "true" : "false";
    const showForm = document.getElementById("show_form")?.checked ? "true" : "false";
    const showTable = document.getElementById("show_table")?.checked ? "true" : "false";
    const showSearch = document.getElementById("show_search")?.checked ? "true" : "false";
    const showPagination = document.getElementById("show_pagination")?.checked ? "true" : "false";
    const tableName = document.getElementById("table_view")?.value || "";

    let shortcode = `[wp_table_manager pagination="${pagination}" table_view="${tableName}" showrecordscount="${showRecordsCount}" showform="${showForm}" showtable="${showTable}" showsearch="${showSearch}" showpagination="${showPagination}"${fieldsText}]`;
    

    document.getElementById("shortcode_output").value = shortcode;

    const textarea = document.getElementById("shortcode_output");
    textarea.select();
    textarea.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand("copy");
    
    // Show "Copied!" message if it exists
    const msg = document.getElementById("copy-message");
    if (msg) {
        msg.style.display = "inline";
        setTimeout(() => { msg.style.display = "none"; }, 3000);
    }
    

    // textarea.value = shortcode;
    // textarea.select();
    // textarea.setSelectionRange(0, 99999);
    // document.execCommand("copy");
    
    // const msg = document.getElementById("copy-message");
    // msg.style.display = "inline";
    // setTimeout(() => { msg.style.display = "none"; }, 3000);
}

// Initialize event listeners when DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    // Check for selected table and automatically load fields
    const selectedTable = document.getElementById("table_view");
    if (selectedTable && selectedTable.value) {
        loadFields(selectedTable.value);
    }
});