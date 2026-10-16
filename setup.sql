CREATE DATABASE IF NOT EXISTS seminar_security;
USE seminar_security;

-- Table for Login & Password Change Demo
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

INSERT INTO users (username, password) VALUES 
('admin', 'supersecret123'),
('student1', 'pass1234');

-- Table for Guestbook XSS Demo
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    message TEXT NOT NULL
);

INSERT INTO comments (name, message) VALUES 
('Alice', 'Great seminar so far!'),
('Bob', 'Excited to learn about web security.');
