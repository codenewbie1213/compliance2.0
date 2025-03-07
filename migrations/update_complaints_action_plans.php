<?php
require_once __DIR__ . '/../config/database.php';

// First, remove the existing foreign key constraint
$sql = "ALTER TABLE complaints DROP FOREIGN KEY complaints_ibfk_2";
if ($conn->query($sql) === TRUE) {
    echo "Removed existing foreign key constraint successfully\n";
} else {
    echo "Error removing foreign key constraint: " . $conn->error . "\n";
}

// Remove the action_plan_id column from complaints table
$sql = "ALTER TABLE complaints DROP COLUMN action_plan_id";
if ($conn->query($sql) === TRUE) {
    echo "Removed action_plan_id column successfully\n";
} else {
    echo "Error removing action_plan_id column: " . $conn->error . "\n";
}

// Create complaint_action_plans junction table
$sql = "CREATE TABLE IF NOT EXISTS complaint_action_plans (
    complaint_id INT NOT NULL,
    action_plan_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (complaint_id, action_plan_id),
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Complaint action plans junction table created successfully\n";
} else {
    echo "Error creating complaint action plans table: " . $conn->error . "\n";
}

$conn->close(); 