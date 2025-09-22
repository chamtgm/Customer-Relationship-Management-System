# ABB Robotics Customer Relationship Management System (COMP1044_CW_G1)

A comprehensive web-based Customer Relationship Management (CRM) system built for ABB Robotics to manage customer relationships, leads, interactions, and reminders. This project was developed as part of the COMP1044 coursework.

## 🚀 Features

### Core Functionality
- **User Authentication**: Secure login and registration system with role-based access control
- **Dashboard**: Interactive home page with charts and statistics showing customer/lead data
- **Customer Management**: Full CRUD operations for customer records
- **Lead Management**: Track and manage potential customers
- **Interaction Tracking**: Record and monitor customer interactions
- **Reminder System**: Set and manage reminders with notification badges
- **Search Functionality**: Global search across customers, leads, and interactions
- **Role-Based Access**: Different permissions for Admin and Sales Representative roles

### User Roles
- **Admin**: Full access to all features, can manage all customers, leads, and staff
- **Sales Representative**: Limited access to their assigned customers and leads

### Technical Features
- **Responsive Design**: Mobile-friendly interface using CSS flexbox and grid
- **Real-time Updates**: AJAX-powered reminder notifications
- **Data Visualization**: Chart.js integration for dashboard analytics
- **Security**: SQL injection prevention with prepared statements
- **Session Management**: Secure user session handling

## 📁 Project Structure

```
COMP1044_CW_G1/
├── COMP1044_CW_G1/
│   ├── COMP1044_SRC/
│   │   └── COMP1044_SRC/           # Main application source code
│   │       ├── *.php               # PHP application files
│   │       ├── *.css               # Stylesheet files
│   │       └── script.js           # JavaScript functionality
│   ├── COMP1044_ERD.pdf           # Entity Relationship Diagram
│   ├── COMP1044_WBS.pdf           # Work Breakdown Structure
│   ├── COMP1044_SRC.zip           # Compressed source code
│   └── comp1044_database.sql      # Database schema and sample data
└── README.md                      # This documentation file
```

## 🗃️ Database Schema

The system uses a MySQL database (`comp1044_database`) with the following main tables:

### Core Tables
- **staff**: User accounts with role assignments
- **role**: User role definitions (Admin, Sales Rep)
- **customer**: Customer information and records
- **lead**: Potential customer leads
- **interaction**: Customer interaction history
- **reminder**: Reminder types and settings
- **reminder_record**: Individual reminder instances

### Key Relationships
- Staff → Role (Many-to-One)
- Customer → Staff (Many-to-One) 
- Lead → Staff (Many-to-One)
- Interaction → Staff (Many-to-One)
- Reminder_Record → Staff (Many-to-One)
- Reminder_Record → Customer/Lead (Many-to-One)

### Sample Data
The database includes comprehensive sample data:
- **9 Staff Members**: 3 Admins and 6 Sales Representatives
- **20+ Customers**: Including companies like InnovaZ Solutions, TrinitySoft, KL Fashions
- **20+ Leads**: Potential customers with various status levels (New, Contacted, In Progress)
- **Multiple Interactions**: Sample customer communication records
- **Reminder Records**: Various reminder types and schedules

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Libraries**: 
  - Chart.js (Data visualization)
  - Font Awesome (Icons)
- **Server**: Apache/XAMPP (for local development)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.4+
- Apache Web Server
- XAMPP (recommended for local development)

## ⚙️ Installation & Setup

### 1. Environment Setup
```bash
# Install XAMPP or ensure you have PHP, MySQL, and Apache running
# Start Apache and MySQL services
```

### 2. Database Setup
```sql
-- Import the database schema
mysql -u root -p < comp1044_database.sql

-- Or use phpMyAdmin:
-- 1. Open phpMyAdmin in browser
-- 2. Create database 'comp1044_database'
-- 3. Import comp1044_database.sql file
```

### 3. Application Deployment
```bash
# Copy source files to web server directory
cp -r COMP1044_SRC/COMP1044_SRC/* /path/to/xampp/htdocs/crm/

# Or for XAMPP on Windows:
# Copy to C:\xampp\htdocs\crm\
```

### 4. Configuration
```php
// Update database connection settings in PHP files if needed
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "comp1044_database";
```

## 🚀 Usage

### 1. Starting the Application
1. Start XAMPP services (Apache and MySQL)
2. Navigate to `http://localhost/crm/LoginPage.php`
3. Use the following test credentials:

**Admin Account:**
- Username: `Admin 1`
- Password: `123`

**Sales Representative:**
- Username: `SalesRep 1`
- Password: `123`

*Note: Additional test accounts are available in the database with usernames like Admin 2, Admin 3, SalesRep 2, etc., all with password `123`*

### 2. Quick Start Guide
1. **Dashboard Overview**: After login, view customer/lead statistics and charts
2. **Add New Customer**: Navigate to Customers → Add New Customer
3. **Create Lead**: Go to Leads → Add New Lead
4. **Log Interaction**: Visit Interactions → Add New Interaction
5. **Set Reminder**: Use Reminders → Create Reminder
6. **Search**: Use the search bar in the header to find any record quickly

### 3. Troubleshooting
- **Database Connection Error**: Verify MySQL is running and credentials are correct
- **Page Not Found**: Ensure files are in the correct web directory (htdocs/crm/)
- **Login Issues**: Check username/password combination, ensure database is populated
- **Missing Features**: Verify you're logged in with appropriate role permissions

### 2. Main Application Flow
1. **Login**: Authenticate with username and password
2. **Dashboard**: View overview statistics and charts
3. **Customer Management**: Add, edit, delete customer records
4. **Lead Management**: Convert leads to customers
5. **Interactions**: Log customer communications
6. **Reminders**: Set follow-up reminders
7. **Search**: Find specific records quickly

## 📱 Key Pages & Functionality

### Core Pages
- **LoginPage.php**: User authentication and registration
- **HomePage.php**: Dashboard with charts and statistics
- **CustomerPage.php**: Customer management interface
- **LeadPage.php**: Lead tracking and management
- **Interactions.php**: Interaction history and logging
- **ReminderPage.php**: Reminder management system
- **Settings.php**: User account settings

### Utility Pages
- **Search.php**: Global search functionality
- **sidebar.php**: Navigation menu component
- **ManageEntity.php**: CRUD operations handler
- **VerificationPage.php**: Account verification

## 🎨 UI/UX Features

- **Modern Design**: Clean, professional interface
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Interactive Elements**: Hover effects and smooth transitions
- **Color Scheme**: Red and white branding matching ABB Robotics
- **Icon Integration**: Font Awesome icons throughout
- **Chart Visualizations**: Customer and lead statistics

## 🔒 Security Features

- **SQL Injection Protection**: Prepared statements throughout
- **Session Management**: Secure user session handling
- **Role-Based Access Control**: Different permissions per user role
- **Input Validation**: Server-side validation for all forms
- **Password Security**: Secure password handling (ready for hashing)

## 📊 Data Analytics

The dashboard provides:
- **Customer vs Lead Ratio**: Pie chart showing conversion rates
- **Monthly Statistics**: Bar charts for performance tracking
- **Reminder Analytics**: Upcoming and completed reminder counts
- **Staff Performance**: Individual sales representative metrics

## 🤝 Contributing

This is an academic project for COMP1044 coursework. For educational purposes:

1. Review the ERD (COMP1044_ERD.pdf) for database design
2. Check WBS (COMP1044_WBS.pdf) for project breakdown
3. Examine source code for implementation details

## 📚 Documentation

- **Entity Relationship Diagram**: `COMP1044_ERD.pdf`
- **Work Breakdown Structure**: `COMP1044_WBS.pdf`
- **Database Schema**: `comp1044_database.sql`
- **Source Code**: `COMP1044_SRC/COMP1044_SRC/`

## 🐛 Known Issues & Future Enhancements

### Current Limitations
- Passwords stored in plain text (should implement hashing)
- Limited file upload functionality
- Basic error handling in some areas

### Potential Improvements
- Implement password hashing (bcrypt)
- Add email notification system
- Enhanced reporting features
- Mobile app integration
- API endpoints for third-party integration

## 📝 License

This project is developed for educational purposes as part of COMP1044 coursework.

## 👥 Team Information

- **Course**: COMP1044 - Database Systems
- **Project**: Customer Relationship Management System
- **Company Theme**: ABB Robotics
- **Development Environment**: XAMPP, PHP, MySQL

---

For technical support or questions about this project, please refer to the course materials or contact the development team.
