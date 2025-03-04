action-plan-app/
├── config/
│   └── database.php         # Database connection settings (MySQL credentials)
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