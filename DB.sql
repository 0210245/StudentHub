-- =========================================================
-- StudentHub database script (phpMyAdmin > Import, or SQL tab > Go)
-- ONE database "studenthub" with 8 tables.
--
-- Entities (Practical 8 ER model):
--   users 1---* audit_logs        users 1---* remember_tokens
--   events 1---* registrations    students 1---* registrations (optional link)
--   feedback, contact_messages    (stand-alone form tables)
--
-- Safe to run more than once (IF NOT EXISTS / INSERT IGNORE).
--
-- ALREADY IMPORTED THE OLD SCRIPT? Run these once first (or drop the
-- "studenthub" and "event" databases and import this file fresh):
--   ALTER TABLE studenthub.users
--       ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active',
--       ADD COLUMN last_login DATETIME NULL;
--   DROP TABLE IF EXISTS event.registrations;   -- old design, replaced below
-- =========================================================

CREATE DATABASE IF NOT EXISTS studenthub
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;

-- ---------------------------------------------------------
-- 1. users : login accounts (student / faculty / admin)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    role        ENUM('student','faculty','admin') NOT NULL,
    user_id     VARCHAR(30)  NOT NULL UNIQUE,
    department  VARCHAR(100) NOT NULL,
    password    VARCHAR(255) NOT NULL,                 -- password_hash() output
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login  DATETIME NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 2. students : records managed by the admin (Practical 11)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    student_id    INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(15)  NULL,
    course        VARCHAR(50)  NOT NULL,
    department    VARCHAR(100) NOT NULL,
    year          TINYINT      NOT NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',  -- soft delete
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 3. events : campus events (Practical 12)
--    event_type doubles as the event category
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    event_id     VARCHAR(20)  PRIMARY KEY,
    event_name   VARCHAR(150) NOT NULL,
    event_date   DATE         NOT NULL,
    event_venue  VARCHAR(150) NOT NULL DEFAULT 'TBA',
    event_type   VARCHAR(50)  NOT NULL DEFAULT 'General',
    coordinator  VARCHAR(100) NOT NULL DEFAULT 'TBA',
    description  TEXT NULL,
    poster       VARCHAR(255) NULL,                     -- file name inside uploads/
    status       ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- 4. registrations : who registered for which event
--    FK -> events (required), FK -> students (optional)
--    Event details are NOT copied here (3NF): they are read from events.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS registrations (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    event_id       VARCHAR(20)  NOT NULL,
    student_id     INT          NULL,
    student_name   VARCHAR(100) NOT NULL,
    student_email  VARCHAR(100) NOT NULL,
    registered_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_registration_per_event (student_email, event_id),   -- no double sign-up
    CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- 5. audit_logs : important admin actions (Practical 14)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    action      VARCHAR(60)  NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- 6. remember_tokens : "Remember me" (Practical 10)
--    Only a SHA-256 hash of the token is stored, never the token itself.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    selector    CHAR(18) NOT NULL UNIQUE,
    token_hash  CHAR(64) NOT NULL,
    expires_at  DATETIME NOT NULL,
    CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- 7 + 8. Form tables (Feedback.html and Contact.html)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL,
    message     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- SEED DATA
-- =========================================================

-- Test accounts (passwords are stored as bcrypt hashes, never plain text)
--   admin@studenthub.com    / Admin@123    (admin)
--   faculty@studenthub.com  / Faculty@123  (faculty)
--   student@studenthub.com  / Student@123  (student)
INSERT IGNORE INTO users (full_name, email, role, user_id, department, password) VALUES
 ('Admin User',   'admin@studenthub.com',   'admin',   'ADMIN001', 'Administration',
  '$2y$10$jyRh7nQ2S6Am56Mubk0v5.gUNe/BLQkzr307mJd8NJNR.KXC81yym'),
 ('Faculty User', 'faculty@studenthub.com', 'faculty', 'FAC001',   'Computer Engineering',
  '$2y$10$2b6tKGBmm2F6oDCgZmS3D.SopxKmPRp6Mi3rQi6V7ewpw6j//d65K'),
 ('Student User', 'student@studenthub.com', 'student', 'STU001',   'Computer Engineering',
  '$2y$10$GeHDSfiSuUBuQdVbLA46..lxl8mAe4mtUN8KDc.tCvLYu4qMm1qve');

-- The same six events that events.html shows
INSERT IGNORE INTO events (event_id, event_name, event_date, event_type, description) VALUES
 ('EVT001', 'Cultural Fest 2026',          '2026-09-12', 'Cultural',  'A celebration of music, dance and art across campus. Open to all students.'),
 ('EVT002', 'Hackathon',                   '2026-09-15', 'Technical', 'Build something amazing in 24 hours and compete for exciting prizes.'),
 ('EVT003', 'Workshop on Web Development', '2026-09-20', 'Workshop',  'Hands-on session covering HTML, CSS and JavaScript fundamentals.'),
 ('EVT004', 'Tech Quiz 2026',              '2026-11-08', 'Technical', 'Test your tech knowledge in a fast-paced team quiz.'),
 ('EVT005', 'Annual Sports Meet',          '2026-11-22', 'Sports',    'Track, field and team games. Show your sporting spirit.'),
 ('EVT006', 'Startup Pitch Day',           '2026-12-05', 'Workshop',  'Pitch your idea to mentors and get feedback.');

-- 20 sample students (needed for Practical 11)
INSERT IGNORE INTO students (enrollment_no, full_name, email, phone, course, department, year) VALUES
 ('D26CE101', 'Aarav Patel',     'aarav.patel@example.com',     '9876500001', 'B.Tech', 'Computer Engineering',         2),
 ('D26CE102', 'Diya Shah',       'diya.shah@example.com',       '9876500002', 'B.Tech', 'Computer Engineering',         2),
 ('D26CE103', 'Rohan Mehta',     'rohan.mehta@example.com',     '9876500003', 'B.Tech', 'Computer Engineering',         3),
 ('D26CE104', 'Isha Desai',      'isha.desai@example.com',      '9876500004', 'B.Tech', 'Computer Engineering',         1),
 ('D26IT201', 'Kabir Joshi',     'kabir.joshi@example.com',     '9876500005', 'B.Tech', 'Information Technology',       2),
 ('D26IT202', 'Meera Trivedi',   'meera.trivedi@example.com',   '9876500006', 'B.Tech', 'Information Technology',       3),
 ('D26IT203', 'Neel Parmar',     'neel.parmar@example.com',     '9876500007', 'B.Tech', 'Information Technology',       4),
 ('D26IT204', 'Sara Vyas',       'sara.vyas@example.com',       '9876500008', 'B.Tech', 'Information Technology',       1),
 ('D26EC301', 'Yash Solanki',    'yash.solanki@example.com',    '9876500009', 'B.Tech', 'Electronics & Communication',  2),
 ('D26EC302', 'Anaya Bhatt',     'anaya.bhatt@example.com',     '9876500010', 'B.Tech', 'Electronics & Communication',  3),
 ('D26EC303', 'Dev Chauhan',     'dev.chauhan@example.com',     '9876500011', 'B.Tech', 'Electronics & Communication',  4),
 ('D26ME401', 'Harsh Rathod',    'harsh.rathod@example.com',    '9876500012', 'B.Tech', 'Mechanical Engineering',       1),
 ('D26ME402', 'Pooja Gohil',     'pooja.gohil@example.com',     '9876500013', 'B.Tech', 'Mechanical Engineering',       2),
 ('D26CV501', 'Vivaan Thakkar',  'vivaan.thakkar@example.com',  '9876500014', 'B.Tech', 'Civil Engineering',            3),
 ('D26CV502', 'Riya Barot',      'riya.barot@example.com',      '9876500015', 'B.Tech', 'Civil Engineering',            4),
 ('D26MT601', 'Jay Pandya',      'jay.pandya@example.com',      '9876500016', 'M.Tech', 'Computer Engineering',         1),
 ('D26MT602', 'Nidhi Kapadia',   'nidhi.kapadia@example.com',   '9876500017', 'M.Tech', 'Computer Engineering',         2),
 ('D26BC701', 'Om Raval',        'om.raval@example.com',        '9876500018', 'BCA',    'Computer Applications',        1),
 ('D26BC702', 'Khushi Modi',     'khushi.modi@example.com',     '9876500019', 'BCA',    'Computer Applications',        2),
 ('D26BC703', 'Tanish Prajapati','tanish.prajapati@example.com','9876500020', 'BCA',    'Computer Applications',        3);

-- Two sample registrations
INSERT IGNORE INTO registrations (event_id, student_id, student_name, student_email) VALUES
 ('EVT002', 1, 'Aarav Patel', 'aarav.patel@example.com'),
 ('EVT002', 2, 'Diya Shah',   'diya.shah@example.com');

-- =========================================================
-- OPTIONAL (Practical 8 advanced): stored procedure
-- Run: CALL sp_event_registration_counts();
-- =========================================================
DROP PROCEDURE IF EXISTS sp_event_registration_counts;
DELIMITER $$
CREATE PROCEDURE sp_event_registration_counts()
BEGIN
    SELECT e.event_id, e.event_name, e.event_date, COUNT(r.id) AS total_registrations
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.event_id
    GROUP BY e.event_id, e.event_name, e.event_date
    ORDER BY e.event_date;
END$$
DELIMITER ;