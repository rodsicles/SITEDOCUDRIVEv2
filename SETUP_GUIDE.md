# Employee Dashboard System

A comprehensive employee management dashboard built with Laravel, featuring role-based access control for Dean, Program Coordinator, and Faculty Employee roles.

## Features

### 🎯 Role-Based Access Control (RBAC)
- **Dean**: Full access to analytics, employee data, performance reports
- **Program Coordinator**: Task management, faculty account creation
- **Faculty Employee**: Task tracking, notifications, performance viewing

### 📊 Key Features
- ✅ Task Assignment & Tracking
- ✅ Performance Monitoring & Reports
- ✅ Document Management
- ✅ Notifications System
- ✅ Dashboard Analytics
- ✅ Activity Logging
- ✅ Modern & Responsive UI

## Color Scheme
- Primary Color: `#028a0f` (65% opacity variations)
- Secondary: White `#ffffff`
- Modern, clean, and professional design

## Installation & Setup

### 1. Database Setup
The MySQL database is already configured. Make sure WAMP is running.

### 2. Run Migrations
```bash
php artisan migrate
```

### 3. Seed Sample Data
```bash
php artisan db:seed
```

This creates 3 sample users:
- **Dean**: Username: `dean`, Password: `password123`
- **Coordinator**: Username: `coordinator`, Password: `password123`
- **Faculty**: Username: `faculty`, Password: `password123`

### 4. Build Assets
```bash
npm install
npm run dev
```

### 5. Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## Default Login Credentials

### Dean Account
- Username: `dean`
- Password: `password123`

### Program Coordinator Account
- Username: `coordinator`
- Password: `password123`

### Faculty Employee Account
- Username: `faculty`
- Password: `password123`

## Features by Role

### Dean Dashboard
- View all employees
- Access performance reports
- View comprehensive analytics
- Monitor all activities
- Access all documents

### Program Coordinator Dashboard
- Create and assign tasks to faculty
- Create faculty employee accounts
- Manage faculty members
- Upload documents
- Track task progress

### Faculty Employee Dashboard
- View assigned tasks
- Update task status
- Receive notifications
- View performance reviews
- Access documents
- View profile information

## Technology Stack
- **Backend**: Laravel 11
- **Database**: MySQL (WAMP)
- **Frontend**: Blade Templates, Vanilla JavaScript
- **Styling**: Custom CSS with animations
- **Icons**: Font Awesome 6.4.0

## Database Tables
- `roles` - User roles (Dean, Coordinator, Faculty)
- `users` - User accounts with authentication
- `employees` - Employee profiles
- `tasks` - Task assignments
- `performance_reports` - Performance evaluations
- `documents` - Document management
- `notifications` - User notifications
- `dashboard_logs` - Activity tracking

## Key Routes

### Authentication
- `GET /login` - Login page
- `POST /login` - Login submission
- `POST /logout` - Logout

### p Routes (Prefix: /dean)
- `/dashboard` - Main dashboard
- `/employees` - Employee list
- `/reports` - Performance reports
- `/analytics` - Data analytics
- `/documents` - Document library

### Coordinator Routes (Prefix: /coordinator)
- `/dashboard` - Main dashboard
- `/tasks` - Task management
- `/tasks/create` - Create new task
- `/faculty` - Faculty management
- `/faculty/create` - Add faculty member
- `/documents` - Document management

### Faculty Routes (Prefix: /faculty)
- `/dashboard` - Main dashboard
- `/tasks` - View assigned tasks
- `/notifications` - View notifications
- `/profile` - View profile
- `/documents` - Access documents

## File Upload
Documents are stored in: `public/uploads/documents/`

## Security Features
- Password hashing
- CSRF protection
- Role-based middleware
- Session management
- Activity logging

## Support
For any issues or questions, please contact the system administrator.

---

**Built with ❤️ using Laravel & Modern Web Technologies**
