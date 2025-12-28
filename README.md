
## 🌱 Smart Crop Planner – Authentication & Database Schema

### 📌 Project Description

The **Smart Crop Planner** is a web-based application designed to demonstrate a clean and modular backend structure for **farmer and admin registration and login**. This repository focuses on providing the **database schema and authentication workflow** required for the system to function.

⚠️ **Important Note:**
This repository **does not include any pre-created database, sample data, or login credentials**. It is intentionally kept minimal to allow users to understand and implement the system from scratch.

---

### 🗂️ What This Repository Provides

* SQL scripts to **create required tables** for:

  * Admin registration and login
  * Farmer (user) registration and login
  * Crop information management
  * Weather data storage
  * User search history
* PHP-based backend logic for handling registration and login
* A clean structure suitable for extension and customization

---

### 🛠️ Database & Authentication Setup

* Users must **create their own database** in MySQL
* Provided SQL scripts should be executed to **create the necessary tables**
* **No default users or passwords are included**
* To access the system:

  * First **register** as an Admin or Farmer
  * Then **log in** using the registered credentials

This approach ensures flexibility and avoids hardcoded or insecure login details.

---

### 🔐 Login & Registration Flow

1. Create a MySQL database (for example, `crop`)
2. Execute the provided **CREATE TABLE** SQL scripts
3. Register a new Admin or Farmer through the application
4. Log in using the newly created credentials

---

### 🎓 Intended Use

This project is suitable for:

* Academic mini projects
* Web technology & database labs
* Learning authentication workflows
* Understanding database schema design
* Backend PHP + MySQL practice

---

### 🚀 Scope for Enhancement

* Secure password hashing (bcrypt)
* Role-based access control
* API token–based authentication
* Integration with real-time weather APIs
* Deployment-ready database migrations

---

## ⚙️ Setup & Usage Guide

Follow the steps below to run the project locally:

### 1️⃣ Prerequisites

Make sure the following are installed on your system:

* PHP (8.x recommended)
* MySQL / MariaDB
* Apache Server (XAMPP / WAMP / LAMP)
* Web browser (Chrome, Firefox, etc.)

---

### 2️⃣ Project Setup

1. Download or clone this repository:

   ```bash
   git clone <repository-url>
   ```

2. Move the project folder to your web server directory:

   * XAMPP → `htdocs/`
   * WAMP → `www/`

3. Start **Apache** and **MySQL** from your server control panel.

---

### 3️⃣ Database Setup

1. Open **phpMyAdmin**
2. Create a new database (for example):

   ```
   crop
   ```
3. Select the created database
4. Open the SQL tab and execute the provided **CREATE TABLE SQL scripts** from the repository

⚠️ No tables are pre-filled with data. Only table structure is created.

---

### 4️⃣ Configure Database Connection

1. Open the database configuration file (e.g., `db.php`)
2. Update the database name, username, and password:

```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=crop;charset=utf8mb4",
    "root",
    ""
);
```

(Adjust credentials based on your local setup.)

---

### 5️⃣ Registration & Login

* The system **does not include default users**
* Users must **register first** using the registration forms
* After successful registration, users can log in using their own credentials

This ensures a clean and realistic authentication workflow.

---

### 6️⃣ Running the Application

1. Open your browser
2. Navigate to:

   ```
   http://localhost/<project-folder-name>/
   ```
3. Register as Admin or Farmer
4. Log in and explore the features

---

### 🧪 Notes

* This project is intended for **local development and learning**
* Password hashing and authentication logic can be extended for production use
* The schema is flexible and can be modified as needed

---

📝 **Conclusion**

The **Smart Crop Planner** project provides a simple and well-structured foundation for implementing user registration and login using a clearly defined database schema and backend authentication flow. By including only table creation SQL and core logic—without any pre-configured database or login credentials—the project encourages users to set up and understand the system from scratch, making it ideal for learning, academic projects, and future enhancements such as secure authentication and role-based access control. 

⭐ **If you find this project useful, don’t forget to give it a Star on GitHub!**



