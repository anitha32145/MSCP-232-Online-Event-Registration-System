-- =====================================================
-- Online Event Registration System
-- Database script (MySQL / MariaDB, run in phpMyAdmin)
-- File: database/event_registration.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS event_registration
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE event_registration;

-- Drop tables in reverse order so this script can be run again safely
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin;

-- -----------------------------------------------------
-- Table: admin
-- -----------------------------------------------------
CREATE TABLE admin (
    admin_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: users
-- -----------------------------------------------------
CREATE TABLE users (
    user_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: events
-- -----------------------------------------------------
CREATE TABLE events (
    event_id INT NOT NULL AUTO_INCREMENT,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(150) NOT NULL,
    capacity INT NOT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (event_id),
    CONSTRAINT chk_events_capacity CHECK (capacity > 0),
    CONSTRAINT chk_events_fee CHECK (fee >= 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: registrations
-- attendance_status is added so the Attendance Report
-- in the approved synopsis has data to show.
-- -----------------------------------------------------
CREATE TABLE registrations (
    registration_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    registration_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Registered',
    attendance_status VARCHAR(20) NOT NULL DEFAULT 'Not Marked',
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_user_event (user_id, event_id),
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id)
        REFERENCES events (event_id) ON DELETE CASCADE,
    CONSTRAINT chk_reg_status CHECK (status IN ('Registered', 'Cancelled')),
    CONSTRAINT chk_reg_attendance CHECK (attendance_status IN ('Not Marked', 'Present', 'Absent'))
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: payments
-- -----------------------------------------------------
CREATE TABLE payments (
    payment_id INT NOT NULL AUTO_INCREMENT,
    registration_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    payment_method VARCHAR(30) NOT NULL,
    PRIMARY KEY (payment_id),
    UNIQUE KEY uq_payment_registration (registration_id),
    CONSTRAINT fk_pay_registration FOREIGN KEY (registration_id)
        REFERENCES registrations (registration_id) ON DELETE CASCADE,
    CONSTRAINT chk_pay_amount CHECK (amount >= 0),
    CONSTRAINT chk_pay_status CHECK (payment_status IN ('Pending', 'Paid', 'Failed', 'Free'))
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table: notifications
-- -----------------------------------------------------
CREATE TABLE notifications (
    notification_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    notification_date DATE NOT NULL DEFAULT (CURDATE()),
    PRIMARY KEY (notification_id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id)
        REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Sample data: events
-- -----------------------------------------------------
INSERT INTO events (event_name, event_date, event_time, venue, capacity, fee) VALUES
('PHP Programming Workshop', '2026-12-15', '10:00:00', 'Computer Lab 1, Main Block', 40, 200.00),
('Annual Tech Seminar', '2026-12-20', '09:30:00', 'Seminar Hall A', 150, 0.00),
('Web Development Bootcamp', '2027-01-10', '10:00:00', 'Computer Lab 2, Main Block', 30, 500.00),
('Cultural Fest 2027', '2027-01-25', '16:00:00', 'Open Air Auditorium', 300, 100.00),
('Career Guidance Session', '2027-02-05', '11:00:00', 'Conference Room 3', 60, 0.00);