# Task Tracker Pro

A comprehensive web-based task and visit management system built with PHP and MySQL. Track your tasks, manage visits, and boost your productivity with an intuitive interface.

![Task Tracker Pro](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)

## 📋 Table of Contents

- [Features](#features)
- [Screenshots](#screenshots)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Technologies Used](#technologies-used)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### Task Management
- ✅ **Create, Edit, and Delete Tasks** - Full CRUD operations for task management
- 📊 **Task Categorization** - Organize tasks by project, priority, and status
- 🎯 **Priority Levels** - Low, Medium, High, and Critical priority settings
- 📅 **Deadline Tracking** - Set start dates and deadlines with visual indicators
- 📈 **Progress Tracking** - Monitor task completion with percentage-based progress
- 🔄 **Status Management** - Track tasks through Not Started, In Progress, Completed, and On Hold states
- 🔍 **Advanced Search & Filter** - Search tasks and filter by status, priority, or project
- 📊 **Multiple Views** - Switch between Table and Kanban board views
- 📥 **Export Functionality** - Export tasks to PDF or Excel formats

### Visit Management
- 📍 **Visit Tracking** - Record and manage site visits or appointments
- ⏰ **Time Logging** - Track visit duration with from/to time stamps
- 🏢 **Venue Management** - Record visit locations
- 📝 **Visit Reasons** - Document the purpose of each visit
- 📅 **Date Filtering** - Filter visits by date range
- 📥 **Export Visits** - Export visit records to PDF or Excel

### User Management
- 🔐 **Secure Authentication** - User registration and login system
- 👤 **User Sessions** - Secure session management
- 🔒 **Password Protection** - Encrypted password storage
- 👥 **Multi-user Support** - Each user has their own tasks and visits

### Dashboard & Analytics
- 📊 **Statistics Dashboard** - View total tasks, completion rates, and visit counts
- 🎨 **Modern UI** - Clean, responsive design with gradient themes
- 📱 **Responsive Design** - Works seamlessly on desktop, tablet, and mobile
- 🌙 **Professional Styling** - Modern glassmorphism effects and smooth animations

## 📸 Screenshots

*Coming soon - Add screenshots of your application here*

## 🔧 Requirements

- **Web Server**: Apache (XAMPP, WAMP, or similar)
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Browser**: Modern web browser (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### 1. Clone or Download the Repository

```bash
git clone https://github.com/yourusername/Task_tracker.git
```

Or download and extract the ZIP file to your web server's document root.

### 2. Move to Web Server Directory

For XAMPP users:
```bash
# Move the project to htdocs
mv Task_tracker C:/xampp/htdocs/
```

For WAMP users:
```bash
# Move the project to www
mv Task_tracker C:/wamp64/www/
```

### 3. Install Dependencies

Navigate to the project directory and install Composer dependencies:

```bash
cd C:/xampp/htdocs/Task_tracker
composer install
```

## 🗄️ Database Setup

### 1. Create Database

Open phpMyAdmin (usually at `http://localhost/phpmyadmin`) and create a new database:

```sql
CREATE DATABASE task_tracker;
```

### 2. Create Tables

Run the following SQL queries to create the required tables:

#### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Tasks Table
```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    project VARCHAR(100) NOT NULL,
    task_description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    start_date DATE NOT NULL,
    deadline DATE NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed', 'On Hold') DEFAULT 'Not Started',
    completion INT DEFAULT 0,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Visits Table
```sql
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visit_date DATE NOT NULL,
    time_from TIME NOT NULL,
    time_to TIME NOT NULL,
    venue VARCHAR(200) NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 3. Configure Database Connection

Edit `config.php` and update the database credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'task_tracker');
```

## 📖 Usage

### 1. Start Your Web Server

For XAMPP users:
- Start Apache and MySQL from the XAMPP Control Panel

For WAMP users:
- Start WAMP server

### 2. Access the Application

Open your web browser and navigate to:
```
http://localhost/Task_tracker/
```

### 3. Register an Account

1. Click on "Register" or navigate to `http://localhost/Task_tracker/register.php`
2. Fill in your details:
   - Username
   - Email
   - Password
3. Click "Register"

### 4. Login

1. Navigate to `http://localhost/Task_tracker/login.php`
2. Enter your credentials
3. Click "Login"

### 5. Manage Tasks

**Add a Task:**
1. Click the "+ Add New Task" button
2. Fill in the task details:
   - Date
   - Project name
   - Task description
   - Priority level
   - Start date and deadline
   - Status
   - Completion percentage
   - Comments (optional)
3. Click "Save Task"

**Edit a Task:**
- Click the "Edit" button on any task
- Modify the details
- Click "Save Task"

**Delete a Task:**
- Click the "Delete" button on any task
- Confirm the deletion

**Filter Tasks:**
- Use the tabs to filter by status (All, Not Started, In Progress, Completed, On Hold, Overdue)
- Use the search box to find specific tasks
- Use the sort dropdown to organize by deadline, priority, project, or status

**Switch Views:**
- Click "📋 Table" for table view
- Click "📊 Kanban" for kanban board view

**Export Tasks:**
- Click the "📥 Export" button
- Choose PDF or Excel format

### 6. Manage Visits

**Add a Visit:**
1. Click on the "Visits" tab in the header
2. Click "+ Add New Visit"
3. Fill in the visit details:
   - Visit date
   - Time from/to
   - Venue
   - Reason for visit
4. Click "Save Visit"

**Edit/Delete Visits:**
- Use the Edit or Delete buttons on each visit card

**Export Visits:**
- Click the "📥 Export" button in the Visits section
- Choose PDF or Excel format

## 📁 Project Structure

```
Task_tracker/
├── config.php              # Database configuration and session management
├── index.php               # Main dashboard (tasks and visits)
├── login.php               # User login page
├── register.php            # User registration page
├── logout.php              # Logout handler
├── export_tasks.php        # Task export functionality (PDF/Excel)
├── export_visits.php       # Visit export functionality (PDF/Excel)
├── composer.json           # Composer dependencies
├── composer.lock           # Composer lock file
├── fpdf/                   # FPDF library for PDF generation
├── vendor/                 # Composer vendor directory
└── README.md              # This file
```

## 🛠️ Technologies Used

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **FPDF** - PDF generation library

### Frontend
- **HTML5** - Structure
- **CSS3** - Styling with modern features (gradients, glassmorphism, animations)
- **JavaScript (ES6+)** - Interactive functionality
- **Google Fonts (Inter)** - Typography

### Design Features
- Responsive grid layouts
- Gradient backgrounds
- Glassmorphism effects
- Smooth animations and transitions
- Modern card-based UI
- Interactive hover effects

## 🎨 Key Features Explained

### Dashboard Statistics
The dashboard displays real-time statistics:
- Total tasks count
- Completed tasks with completion rate
- In-progress tasks
- Overdue tasks with warnings

### Task Priority System
- **Low** - Blue badge
- **Medium** - Orange badge
- **High** - Red badge
- **Critical** - Purple badge

### Task Status Workflow
1. **Not Started** - Gray badge
2. **In Progress** - Blue badge (with progress bar)
3. **On Hold** - Orange badge
4. **Completed** - Green badge

### Deadline Indicators
- ⚠️ Warning (3 days or less until deadline)
- 🔴 Danger (past deadline)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👤 Author

**Amisha Prathyanga**

## 🙏 Acknowledgments

- FPDF library for PDF generation
- Google Fonts for the Inter font family
- The PHP and MySQL communities

## 📞 Support

For support, please open an issue in the GitHub repository or contact the author.

---

**Made with ❤️ by Amisha Prathyanga**
