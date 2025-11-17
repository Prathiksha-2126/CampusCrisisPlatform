# 🚨 Campus Crisis Platform

A comprehensive web-based emergency management system designed for campus-wide crisis coordination and resource management. This platform enables students and staff to report issues, receive real-time alerts, participate in community discussions, and allows administrators to manage crisis response with centralized oversight.

---

## Features

### For Students & Staff
-  **User Authentication** - Secure login and registration system
-  **Real-Time Dashboard** - View all active campus issues with live updates
-  **Issue Reporting** - Report power outages, water issues, medical emergencies, and more
-  **Community Forum** - Share updates, ask for help, and coordinate resources
-  **Live Map** - Visual representation of campus issues
-  **Responsive Design** - Works seamlessly on desktop and mobile devices

### For Administrators
-  **Admin Panel** - Centralized management interface
-  **Issue Management** - Update status, assign severity, and track resolution
-  **Content Moderation** - Approve/reject forum posts before they go live
-  **Resource Management** - Track and update campus resources (generators, first aid kits, etc.)
-  **KPI Dashboard** - Monitor urgent alerts, active issues, and resolved cases

---
## Tech Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with CSS Variables, Grid, and Flexbox
- **JavaScript (Vanilla ES6+)** - No frameworks, pure JavaScript with async/await
- **Google Fonts** - Poppins & Inter font families
- **Font Awesome 6.5.0** - Icon library

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL/MariaDB** - Database management
- **Apache** - Web server (via XAMPP)
- **PDO** - Secure database abstraction layer
- **RESTful API** - JSON-based API endpoints

---

## Prerequisites

Before you begin, ensure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) (includes Apache, MySQL, and PHP)
- A modern web browser (Chrome, Firefox, Edge, etc.)
- Text editor or IDE (VS Code, Sublime Text, etc.)

---

## Installation & Setup

### Step 1: Clone or Download the Project

1. Download the project files or clone the repository
2. Extract the project folder to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\CampusCrisisPlatform
   ```

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** server
3. Start **MySQL** server

### Step 3: Create the Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `campus_crisis`
3. Import the database schema:
   - Click on the `campus_crisis` database
   - Go to the **Import** tab
   - Select `database/schema.sql` file
   - Click **Go**

### Step 4: Run Database Migrations (if needed)

If you're upgrading an existing database, run the migration script:

**Option 1: Using phpMyAdmin**
- Import `database/migrations/002_add_forum_post_moderation.sql`

**Option 2: Using MySQL Command Line**
```bash
cd C:\xampp\mysql\bin
mysql.exe -u root campus_crisis < C:\xampp\htdocs\CampusCrisisPlatform\database\migrations\002_add_forum_post_moderation.sql
```

### Step 5: Configure Database Connection (if needed)

The default configuration in `config/database.php` uses:
- **Host**: `localhost`
- **Database**: `campus_crisis`
- **Username**: `root`
- **Password**: `` (empty by default)

If your MySQL setup is different, edit `config/database.php` accordingly.

### Step 6: Access the Application

Open your web browser and navigate to:
```
http://localhost/CampusCrisisPlatform/
```

## Project Structure

```
CampusCrisisPlatform/
│
├── api/                    # Backend API endpoints
│   ├── add_issue.php      # Create new issue reports
│   ├── add_post.php       # Submit forum posts
│   ├── get_issues.php     # Fetch all issues
│   ├── get_posts.php      # Fetch approved forum posts
│   ├── update_issue_status.php
│   ├── delete_issue.php
│   ├── approve_post.php   # Admin: Approve/reject posts
│   ├── list_pending_posts.php
│   └── ...
│
├── assets/
│   ├── css/
│   │   └── styles.css     # Main stylesheet
│   └── js/
│       ├── main.js        # Core JavaScript functionality
│       └── map.js         # Map-related scripts
│
├── auth/                   # Authentication endpoints
│   ├── login.php
│   ├── signup.php
│   ├── logout.php
│   └── check_session.php
│
├── config/
│   └── database.php       # Database configuration & helpers
│
├── database/
│   ├── schema.sql         # Main database schema
│   ├── resources_table.sql
│   └── migrations/        # Database migration scripts
│
├── index.html             # Login/Signup page
├── dashboard.html         # Main dashboard
├── report.html            # Issue reporting form
├── forum.html             # Community forum
├── admin.html             # Admin panel
├── map.html               # Live map view
│
├── README.md              # This file
└── PROJECT_DOCUMENTATION.md  # Detailed technical documentation
```

---

## Usage Guide

### For Students

1. **Registration/Login**
   - Visit `http://localhost/CampusCrisisPlatform/`
   - Click "Sign Up" to create an account
   - Or log in with existing credentials

2. **Report an Issue**
   - Navigate to **Report Issue** from the menu
   - Fill in the form (category, location, description, contact info)
   - Submit the report
   - The issue will appear immediately on the dashboard

3. **View Dashboard**
   - See all active campus issues
   - Filter by category or status
   - Click on any issue card to view details

4. **Community Forum**
   - Go to **Community** page
   - Post messages to share updates or ask for help
   - Note: Posts require admin approval before appearing publicly

### For Administrators

1. **Access Admin Panel**
   - Navigate to **Admin** page
   - Enter password: `admin123` (default)
   - Access granted for the session

2. **Manage Issues**
   - View all reported issues in a table
   - Update status using dropdown menus
   - Delete issues if needed
   - Changes reflect immediately on the dashboard

3. **Moderate Forum Posts**
   - View pending posts in the **Pending Forum Posts** section
   - Click **Approve** to make posts visible
   - Click **Reject** to remove inappropriate posts

4. **Manage Resources**
   - Update resource status, quantity, and availability
   - Add notes about resource locations or conditions
   - Changes sync to the dashboard resources panel

---

## API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/signup.php` - User registration
- `POST /auth/logout.php` - User logout

### Issues
- `GET /api/get_issues.php` - Fetch all issues
- `POST /api/add_issue.php` - Create new issue
- `POST /api/update_issue_status.php` - Update issue status
- `POST /api/delete_issue.php` - Delete issue

### Forum
- `GET /api/get_posts.php` - Fetch approved posts
- `POST /api/add_post.php` - Submit new post (requires moderation)
- `GET /api/list_pending_posts.php` - List pending posts (admin only)
- `POST /api/approve_post.php` - Approve/reject post (admin only)

### Resources
- `GET /api/get_resources.php` - Fetch all resources
- `POST /api/update_resource.php` - Update resource (admin only)

All API endpoints return JSON responses.

---

## Security Features

- **Password Hashing** - Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Prevention** - PDO prepared statements
- **XSS Protection** - Input sanitization with `htmlspecialchars()`
- **Content Filtering** - Keyword-based inappropriate content detection
- **Session Management** - PHP sessions for authentication
- **CORS Headers** - Configured for cross-origin requests

---

## Troubleshooting

### Database Connection Error
- Ensure MySQL is running in XAMPP Control Panel
- Verify database name is `campus_crisis`
- Check credentials in `config/database.php`

### Forum Posts Not Appearing
- Posts require admin approval before appearing
- Check the Admin panel → Pending Forum Posts section
- Ensure `is_approved` column exists in `forum_posts` table

### Issues Not Showing on Dashboard
- Check browser console for JavaScript errors
- Verify API endpoints are accessible
- Ensure `alerts` table has data with `is_approved = 1`

### 404 Errors on API Calls
- Ensure Apache is running
- Check file paths are correct
- Verify `.htaccess` is not blocking requests (if present)

---

## Future Enhancements

- [ ] Email notifications for issue updates
- [ ] Push notifications for urgent alerts
- [ ] Advanced search and filtering
- [ ] User profiles and avatars
- [ ] File uploads for issue reports
- [ ] Real-time chat functionality
- [ ] Mobile app (React Native/Flutter)
- [ ] Analytics dashboard
- [ ] Multi-language support

---

## 📝 Database Schema

### Main Tables
- **users** - User accounts and authentication
- **issues** - Reported campus issues
- **alerts** - Public-facing alerts (mirrors issues)
- **forum_posts** - Community forum messages
- **resources** - Campus resource inventory
- **emergency_contacts** - Emergency contact information

See `database/schema.sql` for complete schema definition.

---

## Contributing

This is a miniproject for educational purposes. Feel free to fork, modify, and enhance as needed!

---

## License

This project is created for educational purposes. Feel free to use and modify as needed.

---

## Acknowledgments

- Built with vanilla JavaScript (no frameworks)
- Uses Font Awesome for icons
- Google Fonts for typography
- XAMPP for local development environment

---

## Additional Documentation

For detailed technical documentation, see [PROJECT_DOCUMENTATION.md](PROJECT_DOCUMENTATION.md)

