-- =========================================================
-- StudentHub database script (phpMyAdmin > SQL tab > Go)
-- Creates TWO databases:
--   1. studenthub : accounts, feedback, contact messages
--   2. event      : events list + event registrations (used by Event.php)
-- Safe to run more than once (IF NOT EXISTS / INSERT IGNORE).
-- =========================================================

-- ---------------------------------------------------------
-- 1. ACCOUNTS DATABASE
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- Used by register.php (and later login.php)
-- role includes 'admin' because Login.html offers it; register.php only
-- allows student/faculty, so admins must be added manually.
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    role        ENUM('student','faculty','admin') NOT NULL,
    user_id     VARCHAR(30)  NOT NULL UNIQUE,
    department  VARCHAR(100) NOT NULL,
    password    VARCHAR(255) NOT NULL,          -- stores password_hash() output
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- For Feedback.html (fields: name, email, message)
CREATE TABLE IF NOT EXISTS feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- For Contact.html (fields: name, email, message)
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 2. EVENTS DATABASE (matches the INSERT in Event.php)
-- ---------------------------------------------------------
CREATE DATABASE IF NOT EXISTS event
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE event;

-- List of events (same three shown on events.html)
CREATE TABLE IF NOT EXISTS events (
    event_id     VARCHAR(20)  PRIMARY KEY,
    event_name   VARCHAR(150) NOT NULL,
    event_date   DATE         NOT NULL,
    event_venue  VARCHAR(150) NOT NULL DEFAULT 'TBA',
    event_type   VARCHAR(50)  NOT NULL DEFAULT 'TBA',
    coordinator  VARCHAR(100) NOT NULL DEFAULT 'TBA'
);

-- Used by Event.php: student_name, student_email, event_id, event_name,
-- event_date, event_venue, event_type, coordinator
CREATE TABLE IF NOT EXISTS registrations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    student_name   VARCHAR(100) NOT NULL,
    student_email  VARCHAR(100) NOT NULL,
    event_id       VARCHAR(20)  NOT NULL,
    event_name     VARCHAR(150) NOT NULL,
    event_date     DATE         NOT NULL,
    event_venue    VARCHAR(150),
    event_type     VARCHAR(50),
    coordinator    VARCHAR(100),
    registered_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_registration_per_event (student_email, event_id)  -- no double sign-up
);

-- Sample data: only the name and date shown on events.html
INSERT IGNORE INTO events (event_id, event_name, event_date) VALUES
    ('EVT001', 'Cultural Fest 2026',          '2026-09-12'),
    ('EVT002', 'Hackathon',                   '2026-09-15'),
    ('EVT003', 'Workshop on Web Development', '2026-09-20');
