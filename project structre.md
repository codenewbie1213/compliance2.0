Metadata
Project Name: Action Plan Management Application
Description: A web-based application to manage action plans, allowing users to create, assign, update, and track action plans with features like email notifications, file attachments, and dashboard analytics.
Technology Stack:
Backend: PHP (simple, no frameworks)
Frontend: HTML, CSS (use Bootstrap ), minimal JavaScript
Database: MySQL
Email: PHP mail() function
File Uploads: PHP file handling
Architecture
The application follows a simple MVC-like structure:

Models: Handle database interactions.
Views: HTML templates for user interface.
Controllers: PHP scripts to handle logic and user requests.
Assets: CSS, JavaScript, and uploaded files.
Database Schema
Users Table
user_id (INT, Primary Key, Auto Increment)
username (VARCHAR(50), Unique)
password (VARCHAR(255), hashed)
email (VARCHAR(100))
is_management_staff (BOOLEAN, default: FALSE)
Action Plans Table
action_plan_id (INT, Primary Key, Auto Increment)
name (VARCHAR(100))
description (TEXT)
due_date (DATE, optional)
creator_id (INT, Foreign Key to users.user_id)
assignee_id (INT, Foreign Key to users.user_id)
status (ENUM: "Pending", "In Progress", "Completed", default: "Pending")
created_at (TIMESTAMP, default: current)
updated_at (TIMESTAMP, default: current, updates on change)
Comments Table
comment_id (INT, Primary Key, Auto Increment)
action_plan_id (INT, Foreign Key to action_plans.action_plan_id)
user_id (INT, Foreign Key to users.user_id)
comment_text (TEXT)
created_at (TIMESTAMP, default: current)
Attachments Table
attachment_id (INT, Primary Key, Auto Increment)
action_plan_id (INT, Foreign Key to action_plans.action_plan_id)
user_id (INT, Foreign Key to users.user_id)
file_path (VARCHAR(255))
file_name (VARCHAR(100))
uploaded_at (TIMESTAMP, default: current)