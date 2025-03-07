<?php
/**
 * Add first_name and last_name fields to users table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    // Add first_name and last_name columns
    $sql = "ALTER TABLE users 
            ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT '' AFTER username,
            ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT '' AFTER first_name";
    
    if (!$db->query($sql)) {
        throw new Exception("Error adding name columns: " . $db->error);
    }
    
    echo "Successfully added first_name and last_name columns to users table.\n";
    
    $db->close();
    
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
} 