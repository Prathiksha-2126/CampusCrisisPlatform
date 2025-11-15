-- Campus Crisis Platform Database Schema
-- Run this in phpMyAdmin to create the database and tables

CREATE DATABASE IF NOT EXISTS campus_crisis;
USE campus_crisis;

-- Users table for authentication and user management
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Issues table for crisis reports
CREATE TABLE issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('power', 'water', 'medical', 'food', 'transport', 'other') NOT NULL,
    location VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    contact_info VARCHAR(150) NOT NULL,
    status ENUM('Reported', 'Investigating', 'In Progress', 'Resolved', 'Delayed') DEFAULT 'Reported',
    severity ENUM('green', 'yellow', 'red') DEFAULT 'yellow',
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE forum_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Alerts table for system-wide notifications
CREATE TABLE alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category ENUM('power', 'water', 'medical', 'food', 'transport', 'other') NOT NULL,
    severity ENUM('green', 'yellow', 'red') NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Emergency contacts table
CREATE TABLE emergency_contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Student', 'john@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Priya Sharma', 'priya@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO issues (category, location, description, contact_info, status, severity) VALUES
('power', 'Hostel A Block', 'Complete power outage in the entire block since 2 PM', 'Rahul - rahul@campus.edu', 'Investigating', 'red'),
('water', 'Main Canteen', 'Water supply disrupted, affecting food preparation', 'Canteen Staff - canteen@campus.edu', 'In Progress', 'yellow'),
('medical', 'Sports Complex', 'First aid kit missing from the sports complex', 'Sports Coordinator - sports@campus.edu', 'Reported', 'yellow');

INSERT INTO forum_posts (user_name, message, is_approved) VALUES
('Rahul (Hostel B)', 'Any update on power backup? My laptop battery is dying.', 1),
('Anita (Canteen Staff)', 'Food supply truck expected by 5 PM.', 1),
('Student Rep', 'Thank you!', 1);

INSERT INTO alerts (title, category, severity, status, location, description) VALUES
('Power Outage - Hostel A', 'power', 'red', 'Investigating', 'Hostel A Block', 'Complete power failure affecting 200+ students'),
('Water Supply Issue', 'water', 'yellow', 'In Progress', 'Main Campus', 'Reduced water pressure in multiple buildings'),
('Medical Supplies Low', 'medical', 'yellow', 'Reported', 'Health Center', 'Running low on basic medical supplies');

INSERT INTO emergency_contacts (name, role, phone, email) VALUES
('Campus Security', 'Security', '1001', 'security@campus.edu'),
('Health Center', 'Medical', '1002', 'health@campus.edu'),
('Maintenance', 'Technical', '1003', 'maintenance@campus.edu'),
('Administration', 'Admin', '1004', 'admin@campus.edu');
