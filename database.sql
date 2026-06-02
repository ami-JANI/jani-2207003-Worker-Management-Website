-- ============================================================
--  WorkForce Manager — Full Database Schema
--  Run this in phpMyAdmin > SQL tab (select your DB first)
-- ============================================================

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS workers;

-- ── WORKERS (auth fields added) ───────────────────────────────
CREATE TABLE workers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)  NOT NULL,
    profession   VARCHAR(100)  NOT NULL,
    skill        VARCHAR(200)  DEFAULT '',
    experience   INT           DEFAULT 0,
    location     VARCHAR(100)  DEFAULT '',
    phone        VARCHAR(30)   UNIQUE,
    email        VARCHAR(150)  UNIQUE,
    photo        VARCHAR(255)  DEFAULT '',
    rating       DECIMAL(3,1)  DEFAULT 0,
    rating_count INT           DEFAULT 0,
    availability VARCHAR(20)   DEFAULT 'available',
    hourly_rate  DECIMAL(10,2) DEFAULT 0.00,
    password     VARCHAR(255)  NOT NULL,
    approved     TINYINT(1)    DEFAULT 0,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ── USERS ─────────────────────────────────────────────────────
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(30)  UNIQUE,
    email      VARCHAR(150) UNIQUE,
    location   VARCHAR(100) DEFAULT '',
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── ADMINS ────────────────────────────────────────────────────
CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── BOOKINGS ──────────────────────────────────────────────────
CREATE TABLE bookings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    worker_id   INT NOT NULL,
    status      ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    booked_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

-- ── RATINGS ───────────────────────────────────────────────────
CREATE TABLE ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    user_id    INT NOT NULL,
    worker_id  INT NOT NULL,
    stars      TINYINT NOT NULL CHECK (stars BETWEEN 1 AND 5),
    rated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (worker_id)  REFERENCES workers(id)  ON DELETE CASCADE
);

-- ── NOTIFICATIONS ─────────────────────────────────────────────
CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    target_role ENUM('admin','worker','user') NOT NULL,
    target_id   INT          NOT NULL,
    message     VARCHAR(500) NOT NULL,
    link        VARCHAR(300) DEFAULT '',
    is_read     TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);