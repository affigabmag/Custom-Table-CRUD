# Custom Table CRUD

A WordPress plugin for CRUD operations on custom database tables with working pagination inside shortcodes.

## Directory Structure

```
custom-table-crud/
├── assets/
│   ├── css/
│   │   ├── custom-table-crud.css            (Frontend styles)
│   │   └── custom-table-crud-admin.css      (Admin styles)
│   └── js/
│       ├── custom-table-crud.js             (Frontend scripts)
│       └── custom-table-crud-admin.js       (Admin scripts)
├── includes/
│   ├── class-plugin-core.php                (Main plugin class)
│   ├── class-admin.php                      (Admin functionality)
│   ├── class-shortcode.php                  (Shortcode handling)
│   ├── class-table-manager.php              (Table rendering & CRUD)
│   └── class-ajax-handler.php               (AJAX processing)
├── templates/
│   ├── admin/
│   │   ├── dashboard.php                    (Admin dashboard)
│   │   └── shortcode-generator.php          (Shortcode builder UI)
│   └── frontend/
│       ├── form-view.php                    (Record form template)
│       ├── table-view.php                   (Data table template)
│       ├── search-view.php                  (Search form template)
│       └── pagination-view.php              (Pagination controls)
├── custom-table-crud.php                    (Main plugin file)
├── uninstall.php                            (Clean removal functionality)
├── README.md
└── readme.txt
```

## Features

- Create, Read, Update, and Delete operations for any database table
- Customizable field display types (text, number, date, textarea, email, url, etc.)
- Responsive frontend design works on all devices
- Shortcode-based implementation for easy embedding
- Built-in search functionality
- Column sorting
- Pagination
- User-friendly Admin UI for shortcode generation
- Highly customizable display options

## Installation

1. Upload the `custom-table-crud` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Custom Crud' menu in your WordPress admin panel

## Usage

1. Navigate to the Custom Crud dashboard in your WordPress admin menu
2. Select a table from the list of available tables
3. Configure the fields you want to display
4. Configure pagination and display options
5. Generate a shortcode to display the table data on your site
6. Use the generated shortcode in your posts or pages

## Shortcode Example

```
[wp_table_manager pagination="5" table_view="wp_posts" showrecordscount="true" showform="true" showtable="true" showsearch="true" showpagination="true" field1="fieldname=post_title;displayname=Title;displaytype=text" field2="fieldname=post_content;displayname=Content;displaytype=textarea"]
```

## Shortcode Parameters

- `pagination` - Number of records per page (default: 5)
- `table_view` - Name of the database table to display
- `showrecordscount` - Show/hide the record count (true/false)
- `showform` - Show/hide the data entry form (true/false)
- `showtable` - Show/hide the data table (true/false)
- `showsearch` - Show/hide the search box (true/false)
- `showpagination` - Show/hide the pagination controls (true/false)
- `field1`, `field2`, etc. - Field definitions in the format: `fieldname=column_name;displayname=Label;displaytype=input_type;readonly=true/false`

## Supported Field Types

- text
- number
- date
- datetime
- textarea
- email
- url
- tel
- password

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## License

This plugin is released under the GPLv2 or later license.

## Changelog

### 2.0.0
* Complete code restructuring for better organization and maintainability
* Enhanced security with improved data validation and sanitization
* Better adherence to WordPress coding standards
* Improved user interface for both admin and frontend
* Added more display customization options
* Optimized database queries
* Enhanced error handling and user feedback

### 1.5.0
* Security update: Added proper escaping to outputs
* Security update: Added prepared SQL statements 
* Security update: Added nonce verification for forms
* Fixed bug with pagination on certain table structures
* Updated UI for better accessibility

### 1.4.0
* Major update: added admin dashboard and dynamic shortcode generation
* New: Admin dashboard for shortcode generation
* New: Field type selector for each column
* New: Clipboard copy button
* Fix: Refactored shortcode parsing
* Improved UX and styling

### 1.0.0
* Initial public release