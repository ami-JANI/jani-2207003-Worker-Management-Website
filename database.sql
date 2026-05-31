CREATE DATABASE worker_management;
USE worker_management;

CREATE TABLE workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    profession VARCHAR(100),
    skill VARCHAR(255),
    experience INT,
    location VARCHAR(100),
    phone VARCHAR(20),
    rating FLOAT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);