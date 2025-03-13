<?php
require_once __DIR__ . '/../config/database.php';

// First, check if the column exists with a different name
$sql = "SHOW COLUMNS FROM action_plans LIKE 'assigned_to'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // If 'assigned_to' exists, rename it to 'assignee_id'
    $sql = "ALTER TABLE action_plans CHANGE assigned_to assignee_id INT";
    if ($conn->query($sql) === TRUE) {
        echo "Column renamed from 'assigned_to' to 'assignee_id' successfully\n";
    } else {
        echo "Error renaming column: " . $conn->error . "\n";
    }
} else {
    // If 'assigned_to' doesn't exist, check if 'assignee_id' exists
    $sql = "SHOW COLUMNS FROM action_plans LIKE 'assignee_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        // If neither column exists, create 'assignee_id'
        $sql = "ALTER TABLE action_plans ADD assignee_id INT AFTER creator_id, 
                ADD FOREIGN KEY (assignee_id) REFERENCES users(user_id)";
        if ($conn->query($sql) === TRUE) {
            echo "Column 'assignee_id' added successfully\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    }
}

$conn->close(); 