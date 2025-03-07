<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings and establishes the connection.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');  // Changed to MAMP's default password
define('DB_NAME', 'action_plan');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Create database tables if they don't exist
function setupDatabase() {
    global $conn;
    
    // Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        is_management_staff BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating users table: " . $conn->error);
    }
    
    // Action Plans table
    $sql = "CREATE TABLE IF NOT EXISTS action_plans (
        action_plan_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        due_date DATE NULL,
        creator_id INT NOT NULL,
        assignee_id INT NULL,
        status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (creator_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (assignee_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating action_plans table: " . $conn->error);
    }
    
    // Comments table
    $sql = "CREATE TABLE IF NOT EXISTS comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        action_plan_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating comments table: " . $conn->error);
    }
    
    // Attachments table
    $sql = "CREATE TABLE IF NOT EXISTS attachments (
        attachment_id INT AUTO_INCREMENT PRIMARY KEY,
        action_plan_id INT NOT NULL,
        user_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(100) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating attachments table: " . $conn->error);
    }
    
    // Compliments table
    $sql = "CREATE TABLE IF NOT EXISTS compliments (
        compliment_id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        about_user_id INT NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (about_user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating compliments table: " . $conn->error);
    }
    
    // Complaints table
    $sql = "CREATE TABLE IF NOT EXISTS complaints (
        complaint_id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        description TEXT NOT NULL,
        status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
        action_plan_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating complaints table: " . $conn->error);
    }
}

// Initialize database
setupDatabase(); 