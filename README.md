# Custom Table CRUD Plugin

A flexible **WordPress plugin** that allows CRUD (Create, Read, Update, Delete) operations on **custom database tables** using dynamic shortcodes and a user-friendly admin dashboard.

---

## 🚀 Features

- 🔹 CRUD for any custom database table
- 🔹 Select any table from the database via dropdown
- 🔹 Choose and name which fields to include
- 🔹 Assign a type to each field (text, textarea, number, date, etc.)
- 🔹 Front-end forms powered by shortcodes
- 🔹 Admin dashboard panel for shortcode generation
- 🔹 Supports pagination, search, and sorting
- 🔹 Lightweight, no external dependencies

---

## ⚙️ Installation

1. Clone or download this repo into your WordPress `wp-content/plugins/` directory  
2. Ensure your desired table exists in the MySQL database  
3. Activate the plugin from the WordPress admin  
4. Navigate to **Custom Crud** in the admin menu  
5. Use the GUI to generate your shortcode
6. Paste the shortcode into any post or page

---

## 🧪 Supported Field Types

- `text` – Single-line text input
- `textarea` – Multi-line input
- `number` – Numeric input
- `date` – Date picker
- `datetime` – Date & time picker
- `email` – Email input
- `url` – URL input
- `tel` – Telephone input
- `password` – Password input

---

## ✨ Shortcode Format

The plugin dynamically creates shortcodes like:

```txt
[wp_table_manager pagination="6" table_view="your_table_name"
 field1="fieldname=your_column;displayname=Your Label;displaytype=text"
 field2="fieldname=another_column;displayname=Label 2;displaytype=number"]
```

Use the admin panel to generate this easily without writing code.

---

## 📋 Example Use Case

1. You create a table named `app_books` in your database.
2. Use the plugin dashboard to select that table.
3. Choose `bookname`, `price`, and `description` fields.
4. Assign display types (e.g., `text`, `number`, `textarea`).
5. Copy the generated shortcode and paste into a page.
6. Done! You have a frontend CRUD interface for books.

---

## 🙌 Author
Developed by **affigabmag**

---

## 📄 License
This project is licensed under the MIT License.

