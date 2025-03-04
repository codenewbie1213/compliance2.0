Build a web-based Action Plan Management Application using simple PHP, following the requirements and structure outlined below. The application should allow users to create, assign, update, and track action plans, with features including user authentication, dashboard analytics, email notifications for management staff, file attachments, search and filter options, and due date reminders.

Technology Stack
Backend: Simple PHP (no frameworks)
Database: MySQL
Frontend: HTML, CSS (Use Bootstrap  for responsiveness), minimal JavaScript
Email: PHP mail() function for notifications and reminders
File Uploads: PHP file handling with validation
Database Schema
Create the following tables in MySQL:

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
Application Features
1. User Authentication
Registration: Allow users to sign up with a username, password, email, and an option to mark themselves as management staff.
Login: Secure login using password_hash() and password_verify().
Logout: Destroy session and redirect to the login page.
2. Action Plan Management
Create: Form to create an action plan with name, description, assignee (selected from users), and optional due date.
View: Display action plan details, including comments and attachments.
Update: Allow assignees to update the status ("Pending", "In Progress", "Completed") and add comments.
Delete: Allow creators to delete action plans.
List: Show all action plans with separate sections for "My Action Plans" (assigned to the user) and "Action Plans I Created."
3. Dashboard Analytics
Display counts of:
Pending action plans
Actioned action plans ("In Progress" or "Completed")
Overdue action plans (past due date and not "Completed")
Show user-specific metrics, such as the number of action plans completed on time vs. overdue.
4. Email Notifications
Send an email to management staff when they are assigned an action plan, including task details.
Send reminders for due dates (e.g., 1 day before the due date and when overdue).
5. Commenting System
Allow assignees to add comments to action plans, stored in the Comments table.
6. File Attachments
Enable file uploads (e.g., PDFs, images) for action plans.
Store files in an uploads/ directory and metadata in the Attachments table.
Validate file types (e.g., .pdf, .jpg, .png) and enforce a size limit (e.g., 5MB).
7. Search and Filter
Provide search functionality to find action plans by name.
Add filter options for status, assignee, and due date.
8. Due Date Reminders
Send email reminders to assignees for approaching due dates and overdue action plans.
Optionally, display reminders on the dashboard.
Security Considerations
Use password_hash() for storing passwords and password_verify() for authentication.
Implement prepared statements (MySQLi or PDO) to prevent SQL injection.
Sanitize all user inputs using htmlspecialchars() to prevent XSS attacks.
Restrict file uploads to safe types and sizes.
Enforce access control: only assignees can update action plans, and only creators can delete them.
Application Structure
Organize the project with the following directory structure:

text
Wrap
Copy
action-plan-app/
├── config/
│   └── database.php         # Database connection settings
├── controllers/
│   ├── auth.php             # Handles login, registration, logout
│   ├── action_plans.php     # CRUD operations for action plans
│   ├── dashboard.php        # Dashboard analytics logic
│   ├── comments.php         # Commenting functionality
│   ├── attachments.php      # File upload handling
│   └── notifications.php    # Email notifications and reminders
├── models/
│   ├── User.php             # User model for database interactions
│   ├── ActionPlan.php       # ActionPlan model for database interactions
│   ├── Comment.php          # Comment model for database interactions
│   └── Attachment.php       # Attachment model for database interactions
├── views/
│   ├── layouts/
│   │   └── main.php         # Main layout template with header/footer includes
│   ├── auth/
│   │   ├── login.php        # Login form
│   │   └── register.php     # Registration form
│   ├── action_plans/
│   │   ├── index.php        # List all action plans
│   │   ├── create.php       # Form to create an action plan
│   │   ├── view.php         # View details of a single action plan
│   │   └── edit.php         # Form to edit an action plan
│   ├── dashboard/
│   │   └── index.php        # Dashboard with analytics display
│   └── includes/
│       ├── header.php       # Header template
│       └── footer.php       # Footer template
├── assets/
│   ├── css/
│   │   └── style.css        # Custom CSS styles
│   └── js/
│       └── script.js        # Minimal JavaScript for interactivity
├── uploads/                 # Directory for storing file attachments
└── index.php                # Application entry point (routes requests)
Additional Instructions
Use PHP sessions to manage user authentication and maintain login state.
Implement a simple routing mechanism in index.php to handle different URLs and direct requests to the appropriate controllers.
Ensure the application is user-friendly with a clean, intuitive interface.
Add basic error handling and user feedback (e.g., success messages, validation errors).
Future Enhancements (Optional)
Support multiple assignees per action plan.
Add an in-app notification center for alerts and reminders.
Enable data export functionality (e.g., CSV) for reporting.
Enhance analytics with trends or workload distribution insights.