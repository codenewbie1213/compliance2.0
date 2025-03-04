Product Requirements Document: Action Plan Management Application
1. Introduction
The Action Plan Management Application is a web-based tool designed to help users capture, manage, and track action plans efficiently. It allows users to create action plans, assign them to individuals (including management staff with email alerts), update statuses, add comments, and attach files. The application features a dashboard with analytics to monitor progress, including pending, actioned, overdue, and user-specific metrics, along with search and filter capabilities. Built using simple PHP, this tool provides a lightweight yet effective solution for action plan management.

2. Objectives
Enable users to create and assign action plans with clear ownership.
Allow interaction with action plans, including status updates, comments, and file attachments.
Provide a dashboard with analytics on pending, actioned, and overdue action plans, plus user-specific performance insights.
Send email notifications to management staff when assigned tasks and reminders for due dates.
Offer search and filter options for easy access to action plans.
Ensure simplicity and usability with a straightforward PHP implementation.
3. Features
User Authentication: Secure login system for registered users.
Action Plan Creation: Create action plans with names, descriptions, assignees, and optional due dates.
Action Plan Interaction: Assignees can update statuses, add comments, and attach files.
Dashboard Analytics: Displays counts of pending, actioned, and overdue action plans, plus user-specific metrics.
Search and Filter: Search and sort action plans by name, status, assignee, or due date.
Email Notifications: Alerts for management staff on task assignment and reminders for due dates.
File Attachments: Upload documents or images to action plans.
4. Functional Requirements
4.1 User Management
Users register with a username, password, and email.
Users log in and out securely.
Users are flagged as management staff (optional).
4.2 Action Plan Management
Create action plans with:
Name
Description
Assignee (selected from users)
Due date (optional)
View all action plans, with separate lists for "My Action Plans" (assigned to the user) and "Action Plans I Created."
Assignees can:
Update status ("Pending," "In Progress," "Completed")
Add comments to document actions taken
Attach files (e.g., documents, images)
Search and filter action plans by name, status, assignee, due date, or creator.
4.3 Dashboard
Shows:
Pending Action Plans: Count of plans with "Pending" status.
Actioned Action Plans: Count of plans with "In Progress" or "Completed" status.
Overdue Action Plans: Count of plans past due date with status not "Completed."
User-Specific Metrics: Number of action plans completed on time vs. overdue for the logged-in user.
Includes sections:
"My Action Plans" (assigned to the user)
"Action Plans I Created" (created by the user)
4.4 Notifications and Reminders
Email notification sent to management staff when assigned an action plan, detailing the task.
Email or dashboard reminders for assignees when a due date is approaching or overdue.
5. Non-Functional Requirements
Performance: Fast page loads for small to medium user bases.
Security:
Hash passwords before storage.
Restrict updates to assignees only.
Validate file uploads for type and size.
Usability: Simple, intuitive interface.
Technology: Built with simple PHP, no complex frameworks.
6. User Stories
As a user, I want to register and log in to manage action plans.
As a user, I want to create an action plan and assign it to someone.
As a user, I want management staff to receive an email when I assign them a task.
As an assignee, I want to see my assigned action plans.
As an assignee, I want to update the status and comment on my action plans.
As an assignee, I want to attach files to my action plans.
As a user, I want to search and filter action plans easily.
As a user, I want a dashboard showing pending, actioned, and overdue action plans.
As an assignee, I want reminders for due dates via email or dashboard.
7. Technical Stack
Backend: Simple PHP with MySQL for data storage.
Frontend: HTML, CSS (Bootstrap optional), minimal JavaScript.
Email: PHP mail() function for notifications and reminders.
File Uploads: PHP file handling with validation.
8. Database Schema
Users Table
Field	Type	Description
user_id	INT	Primary key, auto-increment
username	VARCHAR(50)	Unique username
password	VARCHAR(255)	Hashed password
email	VARCHAR(100)	User email
is_management_staff	BOOLEAN	Management staff flag (default: FALSE)
Action Plans Table
Field	Type	Description
action_plan_id	INT	Primary key, auto-increment
name	VARCHAR(100)	Action plan name
description	TEXT	Action plan description
due_date	DATE	Optional due date
creator_id	INT	Foreign key to users(user_id)
assignee_id	INT	Foreign key to users(user_id)
status	ENUM	"Pending," "In Progress," "Completed" (default: "Pending")
created_at	TIMESTAMP	Creation timestamp
updated_at	TIMESTAMP	Last update timestamp
Comments Table
Field	Type	Description
comment_id	INT	Primary key, auto-increment
action_plan_id	INT	Foreign key to action_plans(action_plan_id)
user_id	INT	Foreign key to users(user_id)
comment_text	TEXT	Comment content
created_at	TIMESTAMP	Comment timestamp
Attachments Table
Field	Type	Description
attachment_id	INT	Primary key, auto-increment
action_plan_id	INT	Foreign key to action_plans(action_plan_id)
user_id	INT	Foreign key to users(user_id)
file_path	VARCHAR(255)	Path to uploaded file
file_name	VARCHAR(100)	Original file name
uploaded_at	TIMESTAMP	Upload timestamp
9. Security Considerations
Passwords: Use password_hash() and password_verify().
Sessions: PHP sessions for user tracking and logout.
Access Control: Only assignees can update action plans.
Input Validation: Prevent SQL injection and XSS.
File Uploads: Restrict to safe types/sizes, store securely.
10. Future Enhancements
Support multiple assignees per action plan.
Add in-app notification center.
Enable data export for reporting.
Enhance analytics with trends or workload insights.