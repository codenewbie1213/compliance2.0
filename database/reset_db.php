<?php
/**
 * Database Reset Script
 * This script will drop all existing tables and recreate them with fresh data
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'action_plan';
$username = 'root';
$password = '';

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    echo "Connected successfully\n";
    
    // Drop existing tables if they exist
    $dropTables = [
        "DROP TABLE IF EXISTS `attachments`",
        "DROP TABLE IF EXISTS `comments`",
        "DROP TABLE IF EXISTS `notifications`",
        "DROP TABLE IF EXISTS `reminders`",
        "DROP TABLE IF EXISTS `complaint_action_plans`",
        "DROP TABLE IF EXISTS `complaints`",
        "DROP TABLE IF EXISTS `compliments`",
        "DROP TABLE IF EXISTS `feedback`",
        "DROP TABLE IF EXISTS `activity_log`",
        "DROP TABLE IF EXISTS `action_plans`",
        "DROP TABLE IF EXISTS `users`"
    ];
    
    foreach ($dropTables as $sql) {
        $pdo->exec($sql);
        echo "Dropped table: " . substr($sql, 20) . "\n";
    }
    
    // Create Users table
    $sql = "CREATE TABLE `users` (
        `user_id` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `first_name` varchar(50) DEFAULT NULL,
        `last_name` varchar(50) DEFAULT NULL,
        `is_management_staff` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: users\n";
    
    // Create Action Plans table
    $sql = "CREATE TABLE `action_plans` (
        `action_plan_id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `creator_id` int(11) NOT NULL,
        `assignee_id` int(11) DEFAULT NULL,
        `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
        `due_date` date DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`action_plan_id`),
        KEY `creator_id` (`creator_id`),
        KEY `assignee_id` (`assignee_id`),
        CONSTRAINT `action_plans_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `action_plans_ibfk_2` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: action_plans\n";
    
    // Create Comments table
    $sql = "CREATE TABLE `comments` (
        `comment_id` int(11) NOT NULL AUTO_INCREMENT,
        `action_plan_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `comment_text` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`comment_id`),
        KEY `action_plan_id` (`action_plan_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`action_plan_id`) REFERENCES `action_plans` (`action_plan_id`) ON DELETE CASCADE,
        CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: comments\n";
    
    // Create Attachments table
    $sql = "CREATE TABLE `attachments` (
        `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
        `action_plan_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `file_name` varchar(255) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `file_type` varchar(100) NOT NULL,
        `file_size` int(11) NOT NULL,
        `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`attachment_id`),
        KEY `action_plan_id` (`action_plan_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`action_plan_id`) REFERENCES `action_plans` (`action_plan_id`) ON DELETE CASCADE,
        CONSTRAINT `attachments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: attachments\n";
    
    // Create Notifications table
    $sql = "CREATE TABLE `notifications` (
        `notification_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `action_plan_id` int(11) NOT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`notification_id`),
        KEY `user_id` (`user_id`),
        KEY `action_plan_id` (`action_plan_id`),
        CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`action_plan_id`) REFERENCES `action_plans` (`action_plan_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: notifications\n";
    
    // Create Activity Log table
    $sql = "CREATE TABLE `activity_log` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `action_plan_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `details` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`log_id`),
        KEY `user_id` (`user_id`),
        KEY `action_plan_id` (`action_plan_id`),
        CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`action_plan_id`) REFERENCES `action_plans` (`action_plan_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: activity_log\n";
    
    // Create Complaints table
    $sql = "CREATE TABLE `complaints` (
        `complaint_id` int(11) NOT NULL AUTO_INCREMENT,
        `from_user_id` int(11) NOT NULL,
        `about_user_id` int(11) NOT NULL,
        `from_name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `status` enum('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`complaint_id`),
        KEY `from_user_id` (`from_user_id`),
        KEY `about_user_id` (`about_user_id`),
        CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`about_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: complaints\n";

    // Create Complaint Action Plans table
    $sql = "CREATE TABLE `complaint_action_plans` (
        `complaint_id` int(11) NOT NULL,
        `action_plan_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`complaint_id`,`action_plan_id`),
        KEY `action_plan_id` (`action_plan_id`),
        CONSTRAINT `complaint_action_plans_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE,
        CONSTRAINT `complaint_action_plans_ibfk_2` FOREIGN KEY (`action_plan_id`) REFERENCES `action_plans` (`action_plan_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: complaint_action_plans\n";

    // Create Compliments table
    $sql = "CREATE TABLE `compliments` (
        `compliment_id` int(11) NOT NULL AUTO_INCREMENT,
        `from_user_id` int(11) NOT NULL,
        `about_user_id` int(11) NOT NULL,
        `from_name` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`compliment_id`),
        KEY `from_user_id` (`from_user_id`),
        KEY `about_user_id` (`about_user_id`),
        CONSTRAINT `compliments_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `compliments_ibfk_2` FOREIGN KEY (`about_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "Created table: compliments\n";
    
    // Insert default admin user (password: Admin@123)
    $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `is_management_staff`) 
            VALUES ('admin@example.com', '$adminPassword', 'Admin', 'User', 1)";
    $pdo->exec($sql);
    echo "Created default admin user (email: admin@example.com, password: Admin@123)\n";
    
    // Insert test users
    $users = [
        ['john.doe@example.com', 'John', 'Doe', 0],
        ['jane.smith@example.com', 'Jane', 'Smith', 1],
        ['bob.wilson@example.com', 'Bob', 'Wilson', 0]
    ];
    
    $password = password_hash('User@123', PASSWORD_DEFAULT);
    foreach ($users as $user) {
        $sql = "INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `is_management_staff`) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user[0], $password, $user[1], $user[2], $user[3]]);
    }
    echo "Created test users (password for all: User@123)\n";
    
    // Insert sample action plans
    $actionPlans = [
        [
            'name' => 'Website Redesign',
            'description' => 'Redesign the company website with modern UI/UX principles',
            'status' => 'In Progress',
            'due_date' => '2024-04-30'
        ],
        [
            'name' => 'Security Audit',
            'description' => 'Conduct comprehensive security audit of all systems',
            'status' => 'Pending',
            'due_date' => '2024-05-15'
        ],
        [
            'name' => 'Employee Training Program',
            'description' => 'Develop and implement new employee training program',
            'status' => 'Completed',
            'due_date' => '2024-03-31'
        ]
    ];
    
    foreach ($actionPlans as $plan) {
        $sql = "INSERT INTO `action_plans` (`name`, `description`, `creator_id`, `assignee_id`, `status`, `due_date`) 
                VALUES (?, ?, 1, 2, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plan['name'], $plan['description'], $plan['status'], $plan['due_date']]);
        
        $actionPlanId = $pdo->lastInsertId();
        
        // Add a sample comment
        $sql = "INSERT INTO `comments` (`action_plan_id`, `user_id`, `comment_text`) 
                VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$actionPlanId, 1, 'Initial comment for the action plan.']);
    }
    echo "Created sample action plans with comments\n";
    
    // Insert sample complaints
    $complaints = [
        [
            'description' => 'Service quality needs improvement',
            'status' => 'Pending'
        ],
        [
            'description' => 'Communication issues with staff',
            'status' => 'In Progress'
        ],
        [
            'description' => 'Delayed response to requests',
            'status' => 'Resolved'
        ]
    ];

    foreach ($complaints as $complaint) {
        $sql = "INSERT INTO `complaints` (`from_user_id`, `about_user_id`, `from_name`, `description`, `status`) 
                VALUES (1, 2, 'Admin User', ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$complaint['description'], $complaint['status']]);
        
        $complaintId = $pdo->lastInsertId();
        
        // Link the first action plan to this complaint if it's "In Progress" or "Resolved"
        if ($complaint['status'] !== 'Pending') {
            $sql = "INSERT INTO `complaint_action_plans` (`complaint_id`, `action_plan_id`) VALUES (?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$complaintId]);
        }
    }
    echo "Created sample complaints and complaint action plans\n";

    // Insert sample compliments
    $compliments = [
        [
            'description' => 'Excellent customer service provided',
        ],
        [
            'description' => 'Very helpful and professional staff',
        ],
        [
            'description' => 'Quick response to my inquiries',
        ]
    ];

    foreach ($compliments as $compliment) {
        $sql = "INSERT INTO `compliments` (`from_user_id`, `about_user_id`, `from_name`, `description`) 
                VALUES (1, 2, 'Admin User', ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$compliment['description']]);
    }
    echo "Created sample compliments\n";
    
    echo "\nDatabase reset and initialization completed successfully!\n";
    echo "You can now log in with:\n";
    echo "Admin account: admin@example.com / Admin@123\n";
    echo "Test account: john.doe@example.com / User@123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 