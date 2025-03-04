Key Functionalities
1. User Authentication
Registration: Form for users to sign up with username, password, email, and management staff option.
Login: Secure login form using password_hash and password_verify.
Logout: Destroy session and redirect to login page.
2. Action Plan Management
Create: Form to input name, description, assignee (from users), and optional due date.
View: Display action plan details, including comments and attachments.
Update: Form for assignees to update status and details.
Delete: Allow creators to delete action plans.
3. Dashboard Analytics
Show counts of action plans by status (Pending, In Progress, Completed).
Display overdue action plans and user-specific stats (e.g., completed vs. overdue).
4. Email Notifications
Send email to management staff when assigned an action plan using PHP mail().
Send reminders for due dates (e.g., 1 day before, on overdue).
5. Commenting System
Allow assignees to add comments to action plans, stored in the Comments table.
6. File Attachments
Enable file uploads for action plans (e.g., PDFs, images).
Store files in uploads/ and metadata in the Attachments table.
Validate file types (e.g., .pdf, .jpg) and size (e.g., max 5MB).
7. Search and Filter
Search action plans by name, status, assignee, or due date.
Add filter dropdowns for status and assignee.
8. Reminders
Notify users via email or dashboard alerts for upcoming/overdue due dates.
Security Considerations
Use password_hash for passwords and password_verify for login.
Implement prepared statements with MySQLi or PDO to prevent SQL injection.
Validate and sanitize all user inputs (e.g., htmlspecialchars).
Restrict file uploads to safe types and enforce size limits.
Enforce access control: only assignees can edit, creators can delete.
Future Enhancements
Add support for multiple assignees per action plan.
Implement an in-app notification center.
Allow data export (e.g., CSV) for reporting.
Enhance analytics with trends or workload distribution.