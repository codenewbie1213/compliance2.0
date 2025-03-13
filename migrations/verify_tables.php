<?php
require_once __DIR__ . '/../config/database.php';

// Create action_plans table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS action_plans (
    action_plan_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATE DEFAULT NULL,
    creator_id INT NOT NULL,
    assignee_id INT DEFAULT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (action_plan_id),
    FOREIGN KEY (creator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql) === TRUE) {
    echo "Action plans table verified/created successfully\n";
} else {
    echo "Error with action_plans table: " . $conn->error . "\n";
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    is_management_staff BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql) === TRUE) {
    echo "Users table verified/created successfully\n";
} else {
    echo "Error with users table: " . $conn->error . "\n";
}

$conn->close(); 