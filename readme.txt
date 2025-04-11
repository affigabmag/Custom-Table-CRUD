=== Custom Table CRUD ===
Contributors: affigabmag
Tags: tables, custom tables, database, admin ui, shortcode, wordpress plugin
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A flexible shortcode-based plugin that lets you manage custom WordPress database tables from the frontend. This plugin supports adding, editing, deleting, searching, sorting, and pagination for any table you define. Note: the plugin does not create database tables — you must create them manually.

== Description ==

WP Table Manager is a lightweight WordPress plugin designed for managing custom database tables via shortcodes. Configure the plugin to match your table’s structure and then use the provided shortcodes on your pages or posts to display an interactive data management interface.

Features include:
* Frontend forms for adding and editing records
* Live search and sortable columns
* Pagination for large data sets
* Configurable to manage any custom table

== Installation ==

1. Upload the plugin folder (`wp-table-manager/`) to the `/wp-content/plugins/` directory.
2. Activate the plugin from the WordPress Dashboard.
3. Create your custom database table(s) manually (see example SQL below).
4. Modify the plugin’s shortcode definitions to match your table structure.
5. Use the shortcode (e.g., `[wp_books_manager]`) on any page or post to manage the table.

Example:

add_shortcode('wp_books_manager', 'wp_books_manager_shortcode');

function wp_books_manager_shortcode() {
    global $wpdb;
    return generic_table_manager_shortcode([
        'table_name'  => $wpdb->prefix . 'books',
        'primary_key' => 'id',
        'columns'     => [
            'name'        => 'Book Name',
            'price'       => 'Price',
            'description' => 'Description'
        ]
    ]);
}

== Example SQL Table Definitions ==

CREATE TABLE wp_books (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  price FLOAT NOT NULL,
  description TEXT
);

CREATE TABLE wp_warranties (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ProductName VARCHAR(255) NOT NULL,
  DateOfPurchase DATE,
  Notes TEXT
);

== Frequently Asked Questions ==

= Does this plugin create database tables? =
No. You must create your tables manually using PHPMyAdmin or a SQL tool.

= Can I manage multiple tables? =
Yes. Define a shortcode for each table using the provided structure.

= What kind of fields are supported? =
Any MySQL-supported types — text, numbers, dates, etc.

== Screenshots ==

1. Record editing form
2. Table output with sorting, pagination, and actions
3. Shortcode integration in WordPress page

== Changelog ==

= 1.0 =
* Initial public release

== Upgrade Notice ==

= 1.0 =
First official version of WP Table Manager.

== License ==

This plugin is released under the MIT License. Use it freely in commercial or personal projects.
