<?php
require_once __DIR__ . '/../config/database.php';

// First add the email column as nullable
$sql = "ALTER TABLE users 
        ADD COLUMN email VARCHAR(255) NULL AFTER username";

if ($conn->query($sql) === TRUE) {
    echo "Added email column to users table successfully\n";
} else {
    echo "Error adding email column: " . $conn->error . "\n";
}

// Update existing users to have an email based on their username
$sql = "UPDATE users SET email = CONCAT(username, '@example.com') WHERE email IS NULL";
if ($conn->query($sql) === TRUE) {
    echo "Updated existing users with default email successfully\n";
} else {
    echo "Error updating existing users: " . $conn->error . "\n";
}

// Now make email column unique and not null
$sql = "ALTER TABLE users 
        MODIFY email VARCHAR(255) NOT NULL,
        ADD UNIQUE (email)";

if ($conn->query($sql) === TRUE) {
    echo "Made email column unique and not null successfully\n";
} else {
    echo "Error modifying email column: " . $conn->error . "\n";
}

// Make username nullable
$sql = "ALTER TABLE users 
        MODIFY username VARCHAR(50) NULL";

if ($conn->query($sql) === TRUE) {
    echo "Made username column nullable successfully\n";
} else {
    echo "Error modifying username column: " . $conn->error . "\n";
}

$conn->close(); 