# Practical 8: MySQL Schema Design, ER Model & Database Connectivity

## 1. Problem Definition
Design a normalized relational database for the **StudentHub** portal containing students, events, registrations, users, audit logs, and remember tokens. Establish and verify database connectivity using both MySQLi and PHP PDO with prepared statements and stored procedures.

---

## 2. Entity-Relationship (ER) Model
### Entities & Attributes
1. **users** (Primary Key: `id`)
   - `id`, `full_name`, `email` (UNIQUE), `role` (ENUM: student, faculty, admin), `user_id` (UNIQUE), `department`, `password` (BCrypt hash), `status` (ENUM: active, inactive), `last_login`, `created_at`.
2. **students** (Primary Key: `student_id`)
   - `student_id`, `enrollment_no` (UNIQUE), `full_name`, `email` (UNIQUE), `phone`, `course`, `department`, `year`, `status` (ENUM: active, inactive for soft delete), `created_at`.
3. **events** (Primary Key: `event_id`)
   - `event_id`, `event_name`, `event_date`, `event_venue`, `event_type`, `coordinator`, `description`, `poster`, `status` (ENUM: open, closed), `created_at`.
4. **registrations** (Primary Key: `id`)
   - `id`, `event_id` (FK &rarr; events.event_id), `student_id` (FK &rarr; students.student_id), `student_name`, `student_email`, `registered_at`.
   - UNIQUE Constraint: `(student_email, event_id)` preventing duplicate registrations.
5. **audit_logs** (Primary Key: `id`)
   - `id`, `user_id` (FK &rarr; users.id), `action`, `details`, `ip_address`, `created_at`.
6. **remember_tokens** (Primary Key: `id`)
   - `id`, `user_id` (FK &rarr; users.id), `selector` (UNIQUE), `token_hash`, `expires_at`.

### Cardinality & Relationships
- `users` (1) &mdash;&mdash;&lt; (M) `audit_logs` (One user can have multiple audit log actions)
- `users` (1) &mdash;&mdash;&lt; (M) `remember_tokens` (One user can have remember-me tokens on multiple devices)
- `events` (1) &mdash;&mdash;&lt; (M) `registrations` (One event can have many registrations)
- `students` (1) &mdash;&mdash;&lt; (M) `registrations` (One student can register for multiple events)

---

## 3. Database Normalization (3NF)
- **1NF (First Normal Form)**: All attributes are atomic (no multi-valued columns or repeating groups). Primary keys uniquely identify each row.
- **2NF (Second Normal Form)**: The table is in 1NF and all non-key attributes are fully functionally dependent on the primary key (no partial dependencies).
- **3NF (Third Normal Form)**: The table is in 2NF and there are no transitive dependencies (non-key columns do not depend on other non-key columns). For example, event details (`event_name`, `event_date`) are stored in `events` table and only referenced by `event_id` foreign key in `registrations`.

---

## 4. Key Questions & Viva Answers
**Q1: What is the difference between MySQLi and PDO?**
- **MySQLi**: Procedural and Object-Oriented interface designed specifically for MySQL databases.
- **PDO (PHP Data Objects)**: A lightweight, consistent database abstraction layer that supports multiple databases (MySQL, PostgreSQL, SQLite, Oracle, etc.) using named parameters and exception handling.

**Q2: Why must we use Prepared Statements instead of direct query strings?**
- Prepared statements separate SQL logic from user data. The database compiles the SQL query template first, and then parameters are bound securely as values. This completely neutralizes **SQL Injection (SQLi)** attacks.

**Q3: What is the purpose of Stored Procedures?**
- Stored procedures are pre-compiled SQL statements stored in the database catalog. They reduce network latency by bundling complex logic (e.g., aggregation in `sp_event_registration_counts`), ensure consistent execution, and enhance security.
