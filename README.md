# Custom Table CRUD Plugin

A lightweight and flexible **WordPress plugin** for managing **custom database tables** with a front-end UI.  
Supports **Add, Edit, Delete, Search, Sort, Pagination** and works with **any table** you define.

---

## 🚀 Features

- 🔹 Generic UI for any custom DB table
- 🔹 Create front-end forms using shortcodes
- 🔹 Supports pagination, sorting, search
- 🔹 Editable from WordPress frontend
- 🔹 Lightweight & no external dependencies

---

## ⚙️ Installation

1. Clone or download this repo into your WordPress `wp-content/plugins/` directory  
2. Make sure your desired table already exists in the MySQL database  
3. Activate the plugin from the WordPress admin  
4. Define your shortcodes for each table (see below)  
5. Use the shortcodes in pages or posts

---

## 🧩 How to Register Tables

After creating a table manually in the database (e.g. `wp_books`, `wp_warranties`),  
add your shortcode definition inside the plugin file like this:

```php
add_shortcode('wp_books_manager', 'wp_books_manager_shortcode');
add_shortcode('wp_warranties_manager', 'wp_warranties_manager_shortcode');

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

function wp_warranties_manager_shortcode() {
    global $wpdb;
    return generic_table_manager_shortcode([
        'table_name'  => $wpdb->prefix . 'warranties',
        'primary_key' => 'id',
        'columns'     => [
            'ProductName'     => 'Product Name',
            'DateOfPurchase'  => 'Date Of Purchase',
            'Notes'           => 'Notes'
        ]
    ]);
}
