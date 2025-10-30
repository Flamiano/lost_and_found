
This document provides a step-by-step guide to get the **Lost and Found** project running on your local machine using **XAMPP**.

PHP, MySQL, TailwindCSS, Node
-----

## 1\. Start Your Local Server (XAMPP)

1.  **Open** the **XAMPP Control Panel**.
2.  Click **Start** next to the following modules:
      * **Apache** (This is your web server for PHP).
      * **MySQL** (This is your database server).

-----

## 2\. Configure Your Database

1.  **Access phpMyAdmin:** Open your web browser and navigate to `http://localhost/phpmyadmin`.
2.  **Create the Database:** Create a new database named **`lostandfound`** (or the name specified in the project's configuration).
3.  **Import Data:**
      * Select the newly created `lostandfound` database.
      * Go to the **Import** tab.
      * Upload and import the included SQL file (e.g., `lostandfound.sql`) to populate the database schema and initial data.

-----

## 3\. Install Dependencies (Composer & npm)

Open your **Terminal** or **Command Prompt** and navigate to your project's root directory:

```bash
cd C:\xampp\htdocs\lostandfound
```

### 3.1. PHP Dependencies (Composer)

Run the following command to download and install all required PHP libraries defined in your `composer.json` file:

```bash
composer install
```

### 3.2. Frontend Dependencies (npm)

Assuming your project uses frontend packages (CSS, JavaScript, build tools) and has a `package.json` file, run:

```bash
npm install
```

-----

## 3\. 🌐 Access the Project

Once all steps are complete, open your web browser and navigate to the project's local URL:

```
http://localhost/lostandfound
```

