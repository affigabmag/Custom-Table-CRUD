=== Custom Table CRUD ===
Contributors: affigabmag  
Tags: tables, custom tables, database, admin ui, shortcode, wordpress plugin  
Requires at least: 5.0  
Tested up to: 6.7  
Requires PHP: 7.4  
Stable tag: 1.4  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

A flexible shortcode-based plugin that lets you manage custom WordPress database tables from the frontend or generate shortcode via admin dashboard. Supports adding, editing, deleting, searching, sorting, and pagination for any manually defined table.

== Description ==

WP Table Manager is a lightweight WordPress plugin designed for managing custom database tables via shortcodes and admin UI.  
Select your table, define the fields, and generate a shortcode for use on any page or post.

Features include:
* Frontend forms for adding and editing records
* Live search and sortable columns
* Pagination for large data sets
* Admin dashboard to select table, fields, types, and generate shortcode
* Configurable to manage any custom table

== Installation ==

1. Upload the plugin folder (`wp-table-manager/`) to the `/wp-content/plugins/` directory.
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

== Changelog ==

= 1.4 =
* Major update: added admin dashboard and dynamic shortcode generation.
* New: Admin dashboard for shortcode generation
* New: Field type selector for each column
* New: Clipboard copy button
* Fix: Refactored shortcode parsing
* Improved UX and styling

= 1.0 =
* Initial public release

== License ==

This plugin is released under the GPLv2 or later license.

