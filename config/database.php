<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the Action Plan Management Application.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'action_plan_db');

// Create database connection
function getDbConnection() {
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
    
    return $conn;
}

// Create database tables if they don't exist
function setupDatabase() {
    $conn = getDbConnection();
    
    // Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
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
        assignee_id INT NOT NULL,
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
    
    $conn->close();
}

// Initialize database
setupDatabase(); 