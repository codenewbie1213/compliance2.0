<?php
require_once __DIR__ . '/../config/database.php';

// Check if the column already exists
$sql = "SHOW COLUMNS FROM action_plans LIKE 'creator_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Add creator_id column with foreign key constraint
    $sql = "ALTER TABLE action_plans 
            ADD creator_id INT NOT NULL,
            ADD FOREIGN KEY (creator_id) REFERENCES users(user_id)";
            
    if ($conn->query($sql) === TRUE) {
        echo "Column 'creator_id' added successfully\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column 'creator_id' already exists\n";
}

$conn->close(); 