# Campus Crisis Platform - Complete Technical Documentation

## Executive Summary

**Campus Crisis Platform** is a comprehensive web-based emergency management system designed for campus-wide crisis coordination and resource management. It enables students and staff to report issues, receive real-time alerts, participate in community discussions, and allows administrators to manage crisis response with centralized oversight.

---

## 1. TECH STACK OVERVIEW

### Frontend Architecture
- **HTML5**: Semantic markup for all pages (index.html, dashboard.html, admin.html, forum.html, report.html, map.html)
- **CSS3**: Modern styling with CSS Variables, Grid, Flexbox (styles.css)
- **JavaScript (Vanilla)**: No frameworks - pure ES6+ with async/await for API communication
- **Font Libraries**: 
  - Google Fonts (Poppins 600, Inter 400/500/600)
  - Font Awesome 6.5.0 (icon library)

### Backend Architecture
- **Server**: Apache (via XAMPP)
- **Language**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Architecture Pattern**: RESTful API endpoints

### Database
- **DBMS**: MySQL (via XAMPP)
- **Database Name**: `campus_crisis`
- **Tables**: users, issues, forum_posts, alerts, emergency_contacts, resources

### Dependencies
- **PDO (PHP Data Objects)**: Database abstraction layer for secure queries
- **password_hash()**: PHP native password hashing (bcrypt)
- **JSON**: All API communication via JSON payloads

---

## 2. DETAILED PROCESS WORKFLOW

### 2.1 User Authentication Flow

#### Registration Process (signup.php)
```
User Input (index.html form)
    ↓
auth/signup.php (POST)
    ├─ Validate input (name, email, password)
    ├─ Check email uniqueness
    ├─ Hash password with PASSWORD_DEFAULT (bcrypt)
    ├─ Insert new user record in `users` table
    ├─ Create PHP session ($_SESSION)
    └─ Return success → Redirect to dashboard.html

Database Update:
    users table: NEW ROW
    - user_id (auto-increment)
    - name, email, hashed_password
    - role (default: 'student')
    - created_at (CURRENT_TIMESTAMP)
```

#### Login Process (login.php)
```
User Input (index.html form)
    ↓
auth/login.php (POST)
    ├─ Validate email & password input
    ├─ Query: SELECT user from database by email
    ├─ Verify password using password_verify()
    ├─ Create PHP session with user_id, name, email, role
    └─ Return success + user object → Redirect to dashboard.html
```

### 2.2 Issue Reporting Flow

#### Report an Issue (report.html → add_issue.php)
```
User fills form: category, location, description, contact_info
    ↓
form submission event in main.js
    ↓
POST to api/add_issue.php with JSON payload
    ├─ Security Check: Verify user is logged in ($_SESSION['user_id'])
    ├─ Validation: All required fields present
    ├─ Content Filtering: Check for blocked keywords
    │   (abuse, spam, violence, harassment, etc.)
    ├─ Category Validation: power|water|medical|food|transport|other
    ├─ Severity Default: 'yellow' (can be customized)
    ├─ Insert into `issues` table with status 'Reported'
    ├─ IMMEDIATE POSTING: Insert into `alerts` table with is_approved=1
    │   (appears immediately on dashboard without admin review)
    └─ Return success message

Database Updates:
    issues table: NEW ROW
    - category, location, description, contact_info
    - status: 'Reported' (changes via admin actions)
    - severity: 'yellow'
    - created_at (CURRENT_TIMESTAMP)
    
    alerts table: NEW ROW (mirrors issues)
    - title: "{Category} Issue - {Location}"
    - is_approved: 1 (shows immediately)
    - created_at (CURRENT_TIMESTAMP)

Dashboard Effect:
    → Issue card appears in real-time on dashboard.html
    → Color-coded by severity (red/yellow/green)
    → Shows in admin table immediately
```

### 2.3 Dashboard & Alerts Display

#### Real-Time Alert Rendering (dashboard.html → main.js)
```
Page Load Event:
    ↓
DOMContentLoaded triggers
    ├─ renderDashboard() function executes
    └─ setupDashboardAutoRefresh() enables 30-sec intervals

renderDashboard() Function:
    ├─ FETCH GET api/get_issues.php
    ├─ Parse JSON response with issues[] array
    ├─ Calculate KPI statistics:
    │   - Urgent Alerts (severity='red')
    │   - Active Issues (status != 'Resolved')
    │   - Resolved Today (status='Resolved' AND created_at=TODAY)
    ├─ For each issue, generate HTML card with:
    │   - Category badge
    │   - Title & Location
    │   - Status indicator
    │   - Description text
    │   - Click handler → showIssueDetails() modal
    ├─ Sort by created_at DESC (newest first)
    └─ Render to #alertsGrid container

Auto-Refresh Mechanism:
    ├─ Every 30 seconds: setInterval(renderDashboard, 30000)
    ├─ Cross-tab broadcast via localStorage
    │   (if one tab updates, all tabs refresh)
    └─ Manual refresh button available in hero-actions

API Endpoint: api/get_issues.php
    Response Format:
    {
        "success": true,
        "issues": [
            {
                "id": "issue_1",
                "title": "Power Issue - Hostel A Block",
                "category": "power",
                "location": "Hostel A Block",
                "description": "Complete power outage...",
                "status": "Investigating",
                "severity": "red",
                "time": "Nov 14, 2:30 PM"
            }
        ],
        "stats": {
            "total": 5,
            "urgent": 2,
            "active": 4,
            "resolved_today": 1
        }
    }
```

### 2.4 Admin Status Management

#### Update Issue Status (admin.html → main.js)
```
Admin sees dropdown in alert table
    ↓
Select new status from: Reporting|Investigating|In Progress|Resolved|Delayed
    ↓
POST to api/update_issue_status.php with:
{
    "issue_id": 1,
    "status": "Investigating"
}
    ├─ Update `issues` table: SET status = ?
    ├─ Update `alerts` table: SET status = ?
    ├─ Flash green background (visual feedback)
    ├─ Update severity badge based on new status
    └─ Trigger renderDashboard() to show updated status

Status-to-Severity Mapping:
    - Reported → yellow
    - Investigating → red
    - In Progress → red
    - Resolved → green
    - Delayed → yellow

Dashboard Sync:
    → Issue card updates in real-time
    → All tabs receive update via cross-tab broadcast
```

### 2.5 Admin Delete Issue

#### Delete Functionality
```
Admin clicks delete button (trash icon)
    ↓
Confirmation dialog: "Delete this issue permanently?"
    ↓
POST to api/delete_issue.php with:
{
    "issue_id": 1
}
    ├─ Delete from `issues` table
    ├─ Delete from `alerts` table
    ├─ Remove row from admin table
    ├─ Trigger dashboard refresh
    └─ Return success message

Effect:
    → Issue disappears from dashboard
    → Issue disappears from admin table
    → KPI statistics update
```

### 2.6 Community Forum Flow

#### Post Submission (forum.html → add_post.php)
```
User fills forum form: author name, message text
    ↓
form submission event in main.js
    ↓
POST to api/add_post.php with JSON:
{
    "user_name": "Priya from Hostel B",
    "message": "Any update on power backup?"
}
    ├─ Input validation (name + message required)
    ├─ Content filtering (blocked keywords check)
    ├─ MODERATION: Insert with is_approved=0 (pending)
    ├─ Insert into `forum_posts` table
    └─ Return: "Post submitted for admin approval"

Database Update:
    forum_posts table: NEW ROW
    - user_name, message
    - is_approved: 0 (NOT YET VISIBLE)
    - created_at (CURRENT_TIMESTAMP)

User Experience:
    → Alert: "Post submitted! Will appear after admin approval"
    → Form clears
    → Post NOT visible yet
```

#### View Approved Posts (forum.html)
```
Page Load:
    ├─ setupForum() executes
    ├─ loadPosts() function calls GET api/get_posts.php
    └─ Fetches only is_approved=1 posts

API Response (get_posts.php):
{
    "success": true,
    "posts": [
        {
            "author": "Priya from Hostel B",
            "text": "Any update on power backup?",
            "time": "12 min ago",
            "comments": []
        }
    ]
}

Rendering:
    ├─ Generate avatar with author initials
    ├─ Display author name + time posted (relative time)
    ├─ Show post text
    └─ Sort by created_at DESC (newest first)
```

### 2.7 Forum Moderation (Admin)

#### Approve/Reject Posts
```
Admin navigates to admin.html
    ↓
loadPendingPosts() executes
    ├─ Fetches from api/list_pending_posts.php
    ├─ Shows all forum_posts where is_approved=0
    ├─ Displays count badge (red if pending, green if none)
    └─ Renders table with Approve/Reject buttons

Admin Actions:
    ├─ Click "Approve" button
    │   └─ POST api/approve_post.php { post_id, approve: true }
    │       ├─ UPDATE forum_posts: is_approved=1
    │       ├─ Post now visible in forum.html
    │       └─ Refresh pending list
    │
    └─ Click "Reject" button
        └─ POST api/approve_post.php { post_id, approve: false }
            ├─ DELETE from forum_posts (or flag as rejected)
            ├─ Post never visible
            └─ Refresh pending list
```

### 2.8 Admin Password Protection

#### Access Control (admin.html)
```
User navigates to admin.html
    ↓
setupAdminGuard() executes
    ├─ Check sessionStorage.getItem('ccp_admin_ok')
    ├─ If NOT set: 
    │   └─ prompt("Admin password:")
    │       ├─ User enters password
    │       ├─ Compare with 'admin123' (hardcoded)
    │       ├─ If correct:
    │       │   ├─ Set sessionStorage['ccp_admin_ok'] = '1'
    │       │   ├─ POST api/admin_login.php (server-side session)
    │       │   ├─ renderAdminTable() & loadAdminResources()
    │       │   └─ Display admin interface
    │       └─ If incorrect:
    │           ├─ Alert "Incorrect password"
    │           └─ Redirect to dashboard.html
    └─ If already set:
        └─ Load admin panel directly

Session Persistence:
    - Client-side: sessionStorage (lost when tab closes)
    - Server-side: $_SESSION['ccp_admin_ok'] (persists across requests)
```

### 2.9 Resource Management

#### View Resources (dashboard.html & admin.html)
```
Dashboard View:
    ├─ renderResourcesPanel() executes
    ├─ GET api/get_resources.php
    └─ Render resource cards with:
        - Resource name & category
        - Current status (Available|Low Stock|Out of Stock|Maintenance)
        - Quantity & unit
        - Last updated timestamp

Admin View:
    ├─ loadAdminResources() executes (admin only)
    ├─ GET api/get_resources.php
    └─ Render editable table with:
        - Status dropdown
        - Quantity input field
        - Unit input field
        - Available checkbox
        - Notes textarea
        - Save button

Update Resource:
    ├─ Admin modifies fields in table row
    ├─ Click "Save" button
    ├─ POST api/update_resource.php with:
    │   {
    │       "resource_id": 1,
    │       "status": "Available",
    │       "quantity": 50,
    │       "unit": "boxes",
    │       "is_available": true,
    │       "notes": "Just restocked",
    │       "updated_by": "admin"
    │   }
    ├─ UPDATE `resources` table
    ├─ Refresh both admin & dashboard panels
    └─ Alert: "Resource updated successfully!"
```

---

## 3. PROGRAM INTERCONNECTIONS

### 3.1 Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│  index.html ←→ auth/ (login.php, signup.php)                   │
│       ↓                                                           │
│  dashboard.html ←→ api/get_issues.php, api/get_resources.php    │
│       ↓                                                           │
│  report.html ←→ api/add_issue.php                               │
│       ↓                                                           │
│  forum.html ←→ api/get_posts.php, api/add_post.php              │
│       ↓                                                           │
│  admin.html ←→ api/admin_login.php                              │
│       │         api/update_issue_status.php                     │
│       │         api/delete_issue.php                            │
│       │         api/list_pending_posts.php                      │
│       │         api/approve_post.php                            │
│       │         api/update_resource.php                         │
│       ↓                                                           │
│  assets/js/main.js (all event handling & API calls)             │
└─────────────────────────────────────────────────────────────────┘
                           ↑
                    (Fetch API HTTP)
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│  config/database.php                                            │
│  ├─ Database class (PDO connection)                            │
│  ├─ getDBConnection() helper                                   │
│  ├─ sendJSONResponse() helper                                  │
│  ├─ sanitizeInput() helper                                     │
│  ├─ validateEmail() helper                                     │
│  └─ containsBlockedContent() helper (in add_issue.php)        │
│                                                                 │
│  PHP API Endpoints:                                            │
│  ├─ auth/login.php → SELECT users, password_verify()         │
│  ├─ auth/signup.php → INSERT users, password_hash()           │
│  ├─ api/add_issue.php → INSERT issues, INSERT alerts         │
│  ├─ api/get_issues.php → SELECT issues with filters          │
│  ├─ api/delete_issue.php → DELETE issues, DELETE alerts      │
│  ├─ api/update_issue_status.php → UPDATE issues, alerts      │
│  ├─ api/add_post.php → INSERT forum_posts (is_approved=0)   │
│  ├─ api/get_posts.php → SELECT forum_posts WHERE is_approved=1
│  ├─ api/list_pending_posts.php → SELECT forum_posts WHERE is_approved=0
│  ├─ api/approve_post.php → UPDATE forum_posts.is_approved   │
│  ├─ api/admin_login.php → SET $_SESSION['ccp_admin_ok']     │
│  └─ api/update_resource.php → UPDATE resources              │
└─────────────────────────────────────────────────────────────────┘
                           ↑
                      (SQL Queries)
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                     DATABASE LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│  MySQL Database: campus_crisis                                  │
│  ├─ users (authentication)                                      │
│  ├─ issues (crisis reports)                                     │
│  ├─ alerts (admin-managed alerts)                              │
│  ├─ forum_posts (community discussions)                        │
│  ├─ resources (resource inventory)                             │
│  └─ emergency_contacts (critical contacts)                     │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 JavaScript Module Organization (main.js)

The main.js file is organized into functional modules that communicate with specific PHP APIs:

```javascript
// MODULE 1: Authentication Module
├─ setupAuth()
│  ├─ mode toggle (login/signup)
│  └─ POST auth/login.php OR auth/signup.php
│
// MODULE 2: Dashboard Module
├─ renderDashboard()
│  ├─ GET api/get_issues.php
│  ├─ Update KPI statistics
│  ├─ Generate alert cards
│  └─ Setup click handlers
│
├─ setupDashboardAutoRefresh()
│  └─ Auto-refresh every 30 seconds
│
// MODULE 3: Report Module
├─ setupReport()
│  ├─ Form validation
│  └─ POST api/add_issue.php
│
// MODULE 4: Admin Module
├─ setupAdminGuard()
│  ├─ Password prompt
│  └─ POST api/admin_login.php
│
├─ renderAdminTable()
│  ├─ GET api/get_issues.php
│  ├─ Generate admin table
│  └─ Setup event listeners
│
├─ setupAdminEventListeners()
│  ├─ Delete button → POST api/delete_issue.php
│  └─ Status dropdown → POST api/update_issue_status.php
│
// MODULE 5: Forum Module
├─ setupForum()
│  ├─ loadPosts() → GET api/get_posts.php
│  ├─ Form submit → POST api/add_post.php
│  └─ Render posts
│
├─ loadPendingPosts()
│  └─ GET api/list_pending_posts.php
│
├─ approvePost(postId, approve)
│  └─ POST api/approve_post.php
│
// MODULE 6: Resource Module
├─ renderResourcesPanel()
│  └─ GET api/get_resources.php (dashboard)
│
├─ loadAdminResources()
│  └─ GET api/get_resources.php (admin editable)
│
├─ saveResource(resourceId)
│  └─ POST api/update_resource.php
│
// MODULE 7: Utilities
├─ setupModal() - Modal controls
├─ highlightActiveNav() - Active nav link
└─ setupCrossTabRefresh() - localStorage sync
```

### 3.3 Database Relationship Model

```
USERS
├─ user_id (PK)
├─ name
├─ email (UNIQUE)
├─ password (hashed)
├─ role (enum: student|admin)
└─ created_at

    ↓ (user reports issue)
    
ISSUES
├─ issue_id (PK)
├─ category (enum: power|water|medical|food|transport|other)
├─ location
├─ description
├─ contact_info
├─ status (enum: Reported|Investigating|In Progress|Resolved|Delayed)
├─ severity (enum: green|yellow|red)
├─ image_path (optional)
├─ created_at
└─ updated_at

    ↓ (immediately synced to)
    
ALERTS
├─ alert_id (PK)
├─ title
├─ category (same as issues)
├─ severity (same as issues)
├─ status (synced from issues)
├─ location (same as issues)
├─ description (same as issues)
├─ is_approved (0|1) - for display
├─ created_at
└─ updated_at

FORUM_POSTS
├─ post_id (PK)
├─ user_name (not FK - anonymous community posts)
├─ message (text)
├─ is_approved (0|1) - moderation flag
└─ created_at

RESOURCES
├─ resource_id (PK)
├─ name
├─ category
├─ status (enum: Available|Low Stock|Out of Stock|Maintenance|Unavailable)
├─ quantity
├─ unit
├─ is_available (boolean)
├─ notes
├─ updated_by (admin name)
└─ last_updated (TIMESTAMP)

EMERGENCY_CONTACTS
├─ contact_id (PK)
├─ name
├─ role
├─ phone
├─ email
└─ created_at
```

---

## 4. COMPLETE PROJECT KNOWLEDGE

### 4.1 Security Mechanisms

#### Authentication Security
- **Password Hashing**: Uses PHP `password_hash()` with PASSWORD_DEFAULT (bcrypt)
- **Verification**: `password_verify()` compares input against stored hash
- **Sessions**: PHP $_SESSION for authenticated state
- **Access Control**: Login required for issue reporting (`if (!isset($_SESSION['user_id']))`)
- **Admin Gate**: Hardcoded password 'admin123' (Note: Should use proper auth in production)

#### Data Protection
- **Input Sanitization**: `sanitizeInput()` - trim, stripslashes, htmlspecialchars
- **Email Validation**: `validateEmail()` using FILTER_VALIDATE_EMAIL
- **Content Filtering**: `containsBlockedContent()` checks for ~20 blocked keywords
- **SQL Injection Prevention**: PDO prepared statements with parameterized queries
- **CORS Headers**: Cross-Origin Resource Sharing configured in all API endpoints

#### Content Moderation
- **Forum Posts**: Require admin approval before visibility (is_approved flag)
- **Keyword Filtering**: Blocks inappropriate content (abuse, spam, violence, threats)
- **Issue Reports**: Filtered for blocked content before posting

### 4.2 Core Features

#### 1. Crisis Reporting System
- Students/staff can report issues with: category, location, description, contact info
- Issues get color-coded severity: green (low), yellow (medium), red (critical)
- Status tracking: Reported → Investigating → In Progress → Resolved/Delayed
- Real-time dashboard display

#### 2. Real-Time Alert Aggregation
- Issues table mirrors to alerts table for immediate visibility
- Auto-refresh dashboard every 30 seconds
- Cross-tab synchronization via localStorage
- KPI statistics: Urgent alerts, active issues, resolved today

#### 3. Admin Control Panel
- Password-protected interface
- Update issue status with dropdown
- Delete critical issues
- View full issue database with filters (search + status)
- Moderation of forum posts (approve/reject)
- Resource inventory management

#### 4. Community Forum
- Public posting interface (name + message)
- Content moderation (admin approval required)
- Real-time post display (approved only)
- Relative time formatting (e.g., "12 min ago")

#### 5. Resource Management
- Inventory tracking with quantity + units
- Status indicators (Available, Low Stock, Out of Stock, etc.)
- Admin can edit resources
- Resource availability displayed on dashboard
- Last updated timestamp tracking

#### 6. Live Map Integration
- Placeholder for geolocation visualization
- Can show issue locations on campus map

### 4.3 API Request/Response Examples

#### POST /auth/login.php
```json
REQUEST:
{
  "email": "john@campus.edu",
  "password": "password123"
}

RESPONSE (Success):
{
  "success": true,
  "message": "Login successful",
  "user": {
    "user_id": 1,
    "name": "John Student",
    "email": "john@campus.edu",
    "role": "student"
  }
}

RESPONSE (Failure):
{
  "success": false,
  "message": "Invalid email or password"
}
```

#### POST /api/add_issue.php
```json
REQUEST:
{
  "category": "power",
  "location": "Hostel A Block",
  "description": "Complete power outage in the entire block since 2 PM",
  "contact_info": "Rahul - rahul@campus.edu",
  "severity": "red"
}

RESPONSE (Success):
{
  "success": true,
  "message": "Issue reported successfully! It now appears on the dashboard.",
  "issue_id": 4
}

RESPONSE (Failure - Blocked Content):
{
  "success": false,
  "message": "Inappropriate content detected. Please revise and resubmit."
}
```

#### GET /api/get_issues.php?limit=50&status=Reported
```json
RESPONSE:
{
  "success": true,
  "issues": [
    {
      "id": "issue_1",
      "title": "Power Issue - Hostel A Block",
      "category": "power",
      "location": "Hostel A Block",
      "description": "Complete power outage...",
      "contact": "rahul@campus.edu",
      "status": "Investigating",
      "severity": "red",
      "time": "Nov 14, 2:30 PM"
    }
  ],
  "stats": {
    "total": 5,
    "urgent": 2,
    "active": 4,
    "resolved_today": 1
  }
}
```

#### POST /api/add_post.php
```json
REQUEST:
{
  "user_name": "Priya from Hostel B",
  "message": "Any update on power backup? My laptop battery is dying."
}

RESPONSE (Success):
{
  "success": true,
  "message": "Post submitted successfully! It will appear in the community after admin approval."
}
```

#### GET /api/get_posts.php
```json
RESPONSE:
{
  "success": true,
  "posts": [
    {
      "author": "Priya from Hostel B",
      "text": "Any update on power backup?",
      "time": "12 min ago",
      "comments": []
    }
  ]
}
```

### 4.4 File Organization & Responsibilities

```
CampusCrisisPlatform/
├─ index.html ........................... Login/Signup entry point
├─ dashboard.html ....................... Main alerts display with KPIs
├─ report.html .......................... Issue reporting form
├─ forum.html ........................... Community forum interface
├─ admin.html ........................... Admin control panel
├─ map.html ............................. Live map placeholder
│
├─ config/
│  └─ database.php ....................... PDO connection + helper functions
│
├─ auth/
│  ├─ login.php ......................... User authentication
│  ├─ signup.php ........................ User registration
│  ├─ logout.php ........................ Session termination
│  └─ check_session.php ................. Session verification
│
├─ api/
│  ├─ add_issue.php ..................... Create new issue report
│  ├─ get_issues.php .................... Fetch issues for dashboard/admin
│  ├─ update_issue_status.php ........... Admin status updates
│  ├─ delete_issue.php .................. Remove issues
│  ├─ add_post.php ...................... Submit forum post (pending approval)
│  ├─ get_posts.php ..................... Fetch approved posts
│  ├─ list_pending_posts.php ............ Admin: pending posts list
│  ├─ approve_post.php .................. Admin: approve/reject posts
│  ├─ admin_login.php ................... Admin authentication
│  ├─ get_resources.php ................. Fetch resource inventory
│  ├─ update_resource.php ............... Admin: update resources
│  ├─ get_alerts.php .................... Fetch alerts (wrapper)
│  ├─ me.php ............................ Get current user info
│  └─ admin/
│     └─ [future admin utilities]
│
├─ assets/
│  ├─ css/
│  │  └─ styles.css ..................... All styling (variables, grid, cards, etc.)
│  └─ js/
│     ├─ main.js ........................ All event handling, API calls, DOM rendering
│     └─ map.js ......................... Map integration (placeholder)
│
└─ database/
   ├─ schema.sql ........................ CREATE TABLE statements + sample data
   └─ resources_table.sql ............... Resources table definition
```

### 4.5 Workflow Sequences

#### Complete Crisis Resolution Workflow
```
1. CRISIS OCCURS
   └─ Student on campus
   
2. REPORT ISSUE
   └─ Fill report.html form
      └─ POST api/add_issue.php
         └─ INSERT issues table
         └─ INSERT alerts table (is_approved=1)

3. REAL-TIME NOTIFICATION
   └─ All users' dashboards auto-refresh every 30s
      └─ Issue card appears with RED severity badge
      └─ KPI: Urgent Alerts increments
      └─ Students can click for details

4. COMMUNITY COORDINATION
   └─ Students post updates to forum
      └─ POST api/add_post.php (is_approved=0)
      └─ Admin receives pending post notification
      
5. ADMIN RECEIVES ALERT
   └─ Admin logs in (password: admin123)
      └─ Views admin.html with issue table
      └─ Sees pending forum posts count

6. ADMIN ACTIONS
   ├─ Update status: Reported → Investigating
   │  └─ POST api/update_issue_status.php
   │  └─ All dashboards update in real-time
   │
   └─ Approve community post
      └─ POST api/approve_post.php
      └─ Post becomes visible to all

7. RESOURCE COORDINATION
   └─ Admin updates resource status (Low Stock → Available)
      └─ POST api/update_resource.php
      └─ Resource cards refresh on dashboard

8. RESOLUTION
   └─ Admin changes status to Resolved
      └─ Issue card turns GREEN
      └─ KPI: Resolved Today increments
      └─ Issue remains in database for record

9. DOCUMENTATION
   └─ Historic data preserved in database
      └─ Can filter/search resolved issues
      └─ Can review all posts for communication
```

### 4.6 Performance & Scalability Considerations

#### Current Limitations
- **Hardcoded Admin Password**: 'admin123' - should use proper auth
- **No Rate Limiting**: API endpoints accept unlimited requests
- **No Caching**: Each request hits database directly
- **30-Second Refresh**: Dashboard updates every 30 seconds (not real-time)
- **Single Server**: All data on local MySQL instance

#### Optimization Opportunities
1. **Add Redis caching** for frequently accessed issues
2. **Implement WebSocket** for true real-time updates
3. **Add database indexes** on frequently queried columns
4. **Implement pagination** for large datasets
5. **Add rate limiting** on API endpoints
6. **Use async database queries** for better concurrency

### 4.7 Deployment Requirements

#### Server Requirements
- Apache 2.4+
- PHP 7.4+ (with PDO MySQL extension)
- MySQL 5.7+ or MariaDB 10.3+
- XAMPP or similar LAMP stack

#### Database Setup
```bash
# Create database and import schema
mysql -u root campus_crisis < database/schema.sql
```

#### Configuration
- Database credentials in `config/database.php`
- Admin password (hardcoded) in `api/admin_login.php` and `assets/js/main.js`
- CORS headers pre-configured for localhost

#### Test Credentials
```
Student Account:
Email: john@campus.edu
Password: password (bcrypt hash provided in schema)

Admin Access:
Password: admin123
```

---

## 5. BEST PRACTICES & ARCHITECTURAL INSIGHTS

### 5.1 Design Patterns Used

1. **MVC-Lite Pattern**
   - Model: Database layer (config/database.php)
   - View: HTML pages + CSS styling
   - Controller: PHP API endpoints

2. **RESTful API Design**
   - Standard HTTP methods: GET (read), POST (write), DELETE (remove)
   - JSON payloads for request/response
   - Proper HTTP status codes (200, 400, 401, 404, 500)

3. **Separation of Concerns**
   - Database logic isolated in `config/database.php`
   - Frontend logic isolated in `assets/js/main.js`
   - Each API endpoint handles single responsibility

4. **Helper Functions**
   - Reusable functions: `sendJSONResponse()`, `sanitizeInput()`, `validateEmail()`
   - DRY principle: Don't Repeat Yourself

### 5.2 Frontend Best Practices

1. **No Framework**: Vanilla JS provides transparency and no bloat
2. **IIFE Module**: Main.js wrapped in IIFE to avoid global scope pollution
3. **DOM Selectors**: Cached selectors `$(selector)` and `$$(selector)` for performance
4. **Event Delegation**: Single listener for multiple elements (status dropdowns, delete buttons)
5. **Async/Await**: Modern promise handling instead of callbacks
6. **Error Handling**: Try-catch blocks and user feedback via alerts/modals

### 5.3 Backend Best Practices

1. **PDO Prepared Statements**: Prevents SQL injection attacks
2. **Password Hashing**: Uses PHP's native bcrypt via `password_hash()`
3. **Input Validation**: Multiple layers (type, length, format)
4. **CORS Headers**: Allows cross-origin requests safely
5. **Error Logging**: `error_log()` for debugging
6. **HTTP Status Codes**: Proper codes for different scenarios

### 5.4 Database Best Practices

1. **Normalization**: Separate tables for different entities
2. **Primary Keys**: Auto-incrementing IDs for each table
3. **Timestamps**: `created_at` and `updated_at` for audit trails
4. **Enums**: Constrained values (status, category, severity, role)
5. **Unique Constraints**: Email cannot duplicate in users table
6. **Sample Data**: Schema includes realistic test data

### 5.5 Security Best Practices Implemented

1. ✅ Password hashing with bcrypt
2. ✅ Session-based authentication
3. ✅ Input sanitization
4. ✅ SQL injection prevention (prepared statements)
5. ✅ Email validation
6. ✅ Content filtering
7. ✅ CORS headers configured
8. ⚠️ **NEEDS IMPROVEMENT**: Hardcoded admin password
9. ⚠️ **NEEDS IMPROVEMENT**: No HTTPS/SSL configured
10. ⚠️ **NEEDS IMPROVEMENT**: No CSRF token protection

---

## Summary

The Campus Crisis Platform is a well-architected crisis management system that demonstrates solid understanding of:
- Full-stack web development (Frontend → Backend → Database)
- User authentication and session management
- Real-time data synchronization
- Admin control and moderation
- Data validation and security
- Responsive UI design

The project successfully connects all components through a RESTful API architecture, with the frontend using vanilla JavaScript to fetch and render data from PHP APIs, which in turn query a MySQL database. The modular design allows for easy feature additions and maintenance.

