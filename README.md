# MSCP-232-Online-Event-Registration-System
# Online Event Registration System

An Online Event Registration System developed as part of the **IGNOU MCA (MCSP-232) Project**. This web-based application allows users to browse events, register for events, view their registration history and check payment status. Administrators can manage events, users, registrations, payments and generate reports.

## Project Information

- **Project Title:** Online Event Registration System
- **Course:** MCA – MCSP-232
- **Project Type:** Web-Based Application
- **Architecture:** Client–Server Architecture

## Technologies Used

| Component | Technology |
|---|---|
| Frontend | HTML5, CSS3 |
| CSS Framework | Bootstrap 5 |
| Client-Side Scripting | JavaScript |
| Backend | PHP |
| Database | MySQL |
| Web Server | Apache |
| Development Environment | XAMPP |
| Code Editor | Visual Studio Code |

## Features

### User Module

- User registration and login.
- User dashboard.
- Browse available events.
- View event details.
- Register for events.
- View registration confirmation.
- Check registration history.
- View payment status.
- Manage user profile.

### Admin Module

- Secure admin login.
- Admin dashboard.
- Manage users.
- Add, update and delete events.
- Manage event registrations.
- Manage participants.
- Update payment status.
- Generate reports.

### Reports Module

- User reports.
- Event reports.
- Payment reports.
- Attendance reports.

## Website Preview

### Home Page
![Home Page](<Screen Shots/01_home_visitor.png>)

### User Registration
![User Registration](<Screen Shots/02_register2.png>)
![User Registration](<Screen Shots/02_register.png>)

### User Dashboard
![User Dashboard](<Screen Shots/03_user_dashboard.png>)

### Events Page
![Events Page](<Screen Shots/04_events.png>)

### Event Details
![Event Details](<Screen Shots/05_event_details.png>)

### Registration Confirmation
![Registration Confirmation](<Screen Shots/06_confirmation.png>)

### Payment Status
![Payment Status](<Screen Shots/07_user_payment_status.png>)

### Admin Dashboard
![Admin Dashboard](<Screen Shots/10_admin_dashboard.png>)

### Admin User Management
![Admin User Management](<Screen Shots/11_admin_users.png>)

### Admin Event Management
![Admin Event Management](<Screen Shots/14_admin_events.png>)

### Reports
![Reports](<Screen Shots/17_report_users.png>)

## Project Structure

```text
event_registration/
│
├── admin/
│   ├── dashboard.php
│   ├── events.php
│   ├── event_form.php
│   ├── event_delete.php
│   ├── users.php
│   ├── user_delete.php
│   ├── registrations.php
│   ├── registration_action.php
│   ├── payments.php
│   ├── payment_update.php
│   ├── reports.php
│   ├── report_attendance.php
│   ├── report_events.php
│   ├── report_payments.php
│   └── report_users.php
│
├── user/
│   ├── dashboard.php
│   ├── events.php
│   ├── event_details.php
│   ├── register_event.php
│   ├── my_registrations.php
│   ├── payments.php
│   ├── profile.php
│   └── registration_confirmation.php
│
├── config/
│   └── db.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── functions.php
│   └── validation.php
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── js/
│   │       ├── login.js
│   │       └── validation.js
│
├── database/
│   └── event_registration.sql
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── 404.php
└── README.md
```

## System Requirements

- Windows, Linux or macOS.
- XAMPP or another PHP and MySQL development environment.
- PHP.
- MySQL database.
- Apache web server.
- Modern web browser.
- Visual Studio Code (recommended).

## Installation and Setup

### Step 1: Clone the Repository

Open your terminal and execute:

```bash
git clone https://github.com/anitha32145/MSCP-232-Online-Event-Registration-System.git
```

### Step 2: Move the Project

Copy the project folder into the XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\event_registration
```

### Step 3: Start XAMPP

Open the XAMPP Control Panel and start:

- Apache
- MySQL

### Step 4: Create the Database

1. Open your browser.
2. Visit `http://localhost/phpmyadmin/`.
3. Create or select the database required by the project.
4. Import the SQL file located at:

```text
database/event_registration.sql
```

### Step 5: Configure Database Connection

Open:

```text
config/db.php
```

Update the database connection details according to your local MySQL configuration.

### Step 6: Run the Application

Open your browser and visit:

```text
http://localhost/event_registration/
```

The application should now open locally.

## Database Tables

The application uses the following main database tables:

1. **Admin** – Stores administrator account information.
2. **Users** – Stores registered user details.
3. **Events** – Stores event information.
4. **Registrations** – Stores event registration details.
5. **Payments** – Stores payment information and status.
6. **Notifications** – Stores notification information.

## Security Features

- Password hashing and password verification.
- Prepared statements using PDO.
- CSRF token validation.
- Input validation.
- Output escaping to reduce XSS risks.
- Session-based authentication.
- Role-based access control for users and administrators.

## Screenshots

Screenshots of the application can be added to a `screenshots/` folder in this repository.

The following application screens can be included:

- Home Page
- User Registration
- User Dashboard
- Events Page
- Event Details
- Registration Confirmation
- User Payment Status
- My Registrations
- User Profile
- Admin Dashboard
- Admin User Management
- Admin Registration Management
- Admin Payment Management
- Admin Event Management
- Event Form
- Reports
- User Reports
- Event Reports
- Payment Reports
- Attendance Reports
- Login Page
- Database Tables

## Project Objective

The main objective of this project is to develop a web-based event registration platform that simplifies event management and registration. It provides an easy-to-use interface for users and administrative facilities for managing events, participants, registrations and payments.

## Future Enhancements

- Online payment gateway integration.
- Email and SMS notifications.
- QR-code-based event check-in.
- Downloadable event tickets.
- Advanced analytics and reporting.

## Author

**Anitha Karidi**

**Course:** IGNOU MCA  
**Project Code:** MCSP-232

**GitHub Repository:**  
https://github.com/anitha32145/MSCP-232-Online-Event-Registration-System

## License

This project is developed for academic and educational purposes as part of the IGNOU MCA curriculum.
