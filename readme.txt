=== Custom Table CRUD ===
Contributors: affigabmag  
Tags: tables, custom tables, database, admin ui, shortcode, database management, crud, wordpress plugin  
Requires at least: 5.0  
Tested up to: 6.7  
Requires PHP: 7.4  
Stable tag: 2.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

A flexible shortcode-based plugin that lets you manage custom WordPress database tables from the frontend or generate shortcode via admin dashboard. Supports adding, editing, deleting, searching, sorting, and pagination for any manually defined table.

== Description ==

Custom Table CRUD is a lightweight yet powerful WordPress plugin designed for managing custom database tables via shortcodes and admin UI.  
Select your table, define the fields, and generate a shortcode for use on any page or post.

Features include:
* Frontend forms for adding and editing records
* Live search and sortable columns
* Pagination for large data sets
* Admin dashboard to select table, fields, types, and generate shortcode
* Configurable to manage any custom table
* Responsive design that works on all devices
* Highly customizable display options
* Security-focused with data validation and sanitization

= Plugin Structure =

```
custom-table-crud/
├── assets/
│   ├── css/               - Style files
│   └── js/                - JavaScript files
├── includes/              - PHP class files
│   ├── class-plugin-core.php
│   ├── class-admin.php
│   ├── class-shortcode.php
│   ├── class-table-manager.php
│   └── class-ajax-handler.php
├── templates/             - Template files
│   ├── admin/             - Admin UI templates
│   └── frontend/          - Frontend display templates
├── custom-table-crud.php  - Main plugin file
└── uninstall.php          - Clean uninstall script
```

= How It Works =

1. The plugin scans your WordPress database for tables
2. You select which table to manage
3. Configure which fields to display and how to display them
4. Generate a shortcode that can be used on any post or page
5. Use the shortcode to display and manage the table data

= Field Types =

The plugin supports multiple field types including:
* Text
* Number
* Date
* Datetime
* Textarea
* Email
* URL
* Telephone
* Password

Each field can also be set as read-only if needed.

= Display Options =

You can customize the display by showing or hiding:
* Data entry form
* Data table
* Search box
* Pagination controls
* Record count

== Installation ==

1. Upload the plugin folder (`custom-table-crud/`) to the `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress Dashboard.
3. Create your custom database table(s) manually.
4. Go to the **Custom Crud** admin menu to generate shortcodes.
5. Use the generated shortcode on any page or post to manage the table.

== Frequently Asked Questions ==

= Does this plugin create database tables? =
No. You must create your tables manually using PHPMyAdmin or a SQL tool.

= Can I manage multiple tables? =
Yes. Use the admin panel to generate a shortcode for each table.

= What kind of fields are supported? =
Text, number, textarea, date, datetime, email, URL, tel, and password inputs.

= Can I hide specific UI elements? =
Yes. You can show or hide the form, table, search box, pagination, and record count.

= Is the plugin responsive? =
Yes. The frontend UI is designed to work on all screen sizes from mobile to desktop.

= Can I make some fields read-only? =
Yes. Fields can be set as read-only in the shortcode generator.

= Can I sort the data by columns? =
Yes. All columns support sorting by clicking on the column header.

== Screenshots ==

1. Admin Dashboard - Table Selection
2. Shortcode Generator
3. Frontend Display with Form and Table
4. Mobile Responsive View

== Changelog ==

= 2.0.0 =
* Complete code restructuring for better organization and maintainability
* Enhanced security with improved data validation and sanitization
* Better adherence to WordPress coding standards
* Improved user interface for both admin and frontend
* Added more display customization options
* Optimized database queries
* Enhanced error handling and user feedback

= 1.5.0 =
* Security update: Added proper escaping to outputs
* Security update: Added prepared SQL statements 
* Security update: Added nonce verification for forms
* Fixed bug with pagination on certain table structures
* Updated UI for better accessibility

= 1.4.0 =
* Major update: added admin dashboard and dynamic shortcode generation.
* New: Admin dashboard for shortcode generation
* New: Field type selector for each column
* New: Clipboard copy button
* Fix: Refactored shortcode parsing
* Improved UX and styling

= 1.0.0 =
* Initial public release

== Upgrade Notice ==

= 2.0.0 =
Major update with improved code structure, security enhancements, and better user interface. All users should update.

= 1.5.0 =
Security update: Better SQL handling and fixing escaping issues. All users should update immediately.

= 1.4.0 =
Major update: added admin dashboard and dynamic shortcode generation.

== License ==

This plugin is released under the GPLv2 or later license.