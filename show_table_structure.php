<?php
require_once __DIR__ . '/config/database.php';

$sql = "SHOW CREATE TABLE action_plans";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "Table Structure:\n";
    echo $row['Create Table'] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close(); 