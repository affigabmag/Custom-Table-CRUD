# Custom Table CRUD with Debug + Pagination Fix

A WordPress plugin for CRUD operations on custom database tables with working pagination inside shortcodes.

## File Structure

```
custom-table-crud/
├── css/
│   └── custom-table-crud.css
├── js/
│   └── custom-table-crud.js
├── custom-table-crud.php
└── README.md
```

## Installation

1. Upload the `custom-table-crud` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Custom Crud' menu in your WordPress admin panel

## Usage

1. Navigate to the Custom Crud dashboard in your WordPress admin menu
2. Select a table from the list of available tables
3. Configure the fields you want to display
4. Configure pagination options
5. Generate a shortcode to display the table data on your site
6. Use the generated shortcode in your posts or pages

## Shortcode Example

```
[wp_table_manager pagination="5" table_view="wp_posts" field1="fieldname=post_title;displayname=Title;displaytype=text" field2="fieldname=post_content;displayname=Content;displaytype=textarea"]
```

## Features

- CRUD operations for any database table
- Shortcode generator for easy implementation
- Customizable field display options
- Pagination support
- Search and sorting functionality
- Responsive design