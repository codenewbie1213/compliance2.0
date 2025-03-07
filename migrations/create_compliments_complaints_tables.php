<?php
require_once __DIR__ . '/../config/database.php';

// Drop existing compliments table
$sql = "DROP TABLE IF EXISTS compliments";
if ($conn->query($sql) === TRUE) {
    echo "Existing compliments table dropped successfully\n";
} else {
    echo "Error dropping compliments table: " . $conn->error . "\n";
}

// Drop existing complaints table
$sql = "DROP TABLE IF EXISTS complaints";
if ($conn->query($sql) === TRUE) {
    echo "Existing complaints table dropped successfully\n";
} else {
    echo "Error dropping complaints table: " . $conn->error . "\n";
}

// Create compliments table
$sql = "CREATE TABLE IF NOT EXISTS compliments (
    compliment_id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    about_user_id INT NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(user_id),
    FOREIGN KEY (about_user_id) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Compliments table created successfully\n";
} else {
    echo "Error creating compliments table: " . $conn->error . "\n";
}

// Create complaints table
$sql = "CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    action_plan_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(user_id),
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Complaints table created successfully\n";
} else {
    echo "Error creating complaints table: " . $conn->error . "\n";
}

$conn->close(); 