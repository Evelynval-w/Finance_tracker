# Finance_tracker
My first PHP project


# 💰 Personal Finance Tracker

A comprehensive web-based personal finance management system built with PHP, MySQL, HTML, CSS, and JavaScript. Track your income, expenses, and financial goals with beautiful charts and detailed reports.


## ✨ Features

### 🔐 **Authentication & Security**
- Secure user registration and login system
- Password hashing with bcrypt
- Session-based authentication
- User data isolation

### 📊 **Dashboard Overview**
- Monthly financial summary cards
- Interactive expense breakdown charts
- Recent transactions display
- Quick action buttons
- Responsive design for all devices

### 💳 **Transaction Management**
- Add, edit, and delete transactions
- Categorize income and expenses
- Advanced search and filtering
- Pagination for large datasets
- Bulk operations support

### 🏷️ **Category Management**
- Create custom categories
- Color-coded organization
- Income and expense separation
- Usage statistics tracking
- Prevent deletion of categories with transactions

### 📈 **Reports & Analytics**
- Multiple report types (weekly, monthly, yearly, custom)
- Interactive Chart.js visualizations
- Income vs expense comparisons
- Daily spending trends
- 6-month historical analysis
- Category breakdown with percentages
- Print and export functionality

### 🎨 **Modern UI/UX**
- Bootstrap 5 responsive design
- Font Awesome icons
- Custom CSS animations
- Mobile-friendly interface
- Dark mode support
- Professional styling

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Evelynval-w/Finance_tracker.git
   cd Finance_tracker

2. **Set up the database**
-- Create database
CREATE DATABASE finance_tracker;

-- Import the schema
mysql -u username -p finance_tracker < sql/database.sql

3. **Configure the application**

php// Edit config/database.php
private $host = 'localhost';
private $db_name = 'finance_tracker';
private $username = 'your_username';
private $password = 'your_password';


4. **Set up web server**

Point your web server to the project directory
Ensure PHP is enabled
Set appropriate file permissions


5. **Access the application**
http://localhost/personal-finance-tracker

📁 Project Structure

finance_tracker/

├── index.php                 # Landing page

├── login.php                 # User login

├── register.php              # User registration

├── dashboard.php             # Main dashboard

├── transactions.php          # Transaction management

├── categories.php            # Category management

├── reports.php               # Financial reports


├── logout.php                # Logout handler

├── config/
│   └── database.php          # Database configuration


├── includes/
│   ├── header.php            # Common header
│   ├── footer.php            # Common footer
│   └── auth.php              # Authentication functions


├── assets/
│   ├── css/
│   │   └── style.css         # Custom styles
│   └── js/
│       └── main.js           # JavaScript functionality

└── sql/
    └── database.sql          # Database schema



🛠️ Technology Stack

Backend: PHP 7.4+
Database: MySQL 5.7+
Frontend: HTML5, CSS3, JavaScript ES6
Styling: Bootstrap 5.3, Font Awesome 6.4
Charts: Chart.js 4.4
Server: Apache/Nginx

🎯 Usage Guide
Getting Started

Register for a new account
Login with your credentials
Set up categories for your income and expenses
Add transactions to start tracking
View reports to analyze your finances

Adding Transactions

Navigate to "Transactions" → "Add Transaction"
Select category (income or expense)
Enter amount and description
Set transaction date
Save the transaction

Generating Reports

Go to "Reports" section
Choose report type (weekly, monthly, yearly, custom)
Set date range if using custom
View interactive charts and tables
Print or export as needed

🔒 Security Features

Password Hashing: Bcrypt with salt
SQL Injection Protection: Prepared statements
XSS Prevention: Input sanitization
Session Security: Proper session management
Data Validation: Server and client-side validation

🐛 Troubleshooting
Common Issues
Charts not displaying:
javascript// Check if Chart.js is loaded
if (typeof Chart === 'undefined') {
    console.error('Chart.js not loaded');
}
Database connection errors:
php// Verify database credentials in config/database.php
// Check if MySQL service is running
// Ensure database exists
Permission issues:
bash# Set proper file permissions
chmod 755 -R /path/to/finance-tracker
chmod 644 config/database.php
🤝 Contributing

Fork the repository
Create a feature branch (git checkout -b feature/amazing-feature)
Commit your changes (git commit -m 'Add amazing feature')
Push to the branch (git push origin feature/amazing-feature)
Open a Pull Request

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.
🙏 Acknowledgments

Bootstrap for responsive UI components
Chart.js for beautiful charts
Font Awesome for icons
PHP for server-side functionality
MySQL for data storage

📞 Support
If you encounter any issues or have questions:

Check the troubleshooting section
Search existing issues
Create a new issue with detailed information
Contact: okoenemakuo04@outlook.com

🚀 Future Enhancements

 Budget tracking and alerts
 Multi-currency support
 Receipt photo uploads
 Recurring transactions
 Data export (CSV, PDF)
 Mobile app integration
 Two-factor authentication
 Goal setting and tracking
 Bank API integration
 Advanced analytics


Made with ❤️ by Okoene Makuochukwu
