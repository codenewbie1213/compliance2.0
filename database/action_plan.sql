-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS action_plan;
USE action_plan;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    is_management_staff BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Action Plans table
CREATE TABLE IF NOT EXISTS action_plans (
    action_plan_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    creator_id INT NOT NULL,
    assignee_id INT,
    due_date DATE,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (action_plan_id),
    FOREIGN KEY (creator_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT NOT NULL AUTO_INCREMENT,
    action_plan_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (comment_id),
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Attachments table
CREATE TABLE IF NOT EXISTS attachments (
    attachment_id INT NOT NULL AUTO_INCREMENT,
    action_plan_id INT NOT NULL,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attachment_id),
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reminders table
CREATE TABLE IF NOT EXISTS reminders (
    reminder_id INT NOT NULL AUTO_INCREMENT,
    action_plan_id INT NOT NULL,
    user_id INT NOT NULL,
    reminder_date DATE NOT NULL,
    status ENUM('Pending', 'Sent', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (reminder_id),
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feedback table
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT NOT NULL AUTO_INCREMENT,
    action_plan_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (feedback_id),
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback (action_plan_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Log table
CREATE TABLE IF NOT EXISTS activity_log (
    log_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_plan_id INT,
    action VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (action_plan_id) REFERENCES action_plans(action_plan_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_action_plans_creator ON action_plans(creator_id);
CREATE INDEX idx_action_plans_assignee ON action_plans(assignee_id);
CREATE INDEX idx_action_plans_status ON action_plans(status);
CREATE INDEX idx_action_plans_due_date ON action_plans(due_date);
CREATE INDEX idx_comments_action_plan ON comments(action_plan_id);
CREATE INDEX idx_attachments_action_plan ON attachments(action_plan_id);
CREATE INDEX idx_reminders_action_plan ON reminders(action_plan_id);
CREATE INDEX idx_reminders_date ON reminders(reminder_date);
CREATE INDEX idx_feedback_action_plan ON feedback(action_plan_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_activity_log_user ON activity_log(user_id);
CREATE INDEX idx_activity_log_action_plan ON activity_log(action_plan_id);

-- Insert a default admin user (password: admin123)
INSERT INTO users (email, password, first_name, last_name, is_management_staff) 
VALUES ('admin@hadethealthcare.co.uk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', TRUE); 