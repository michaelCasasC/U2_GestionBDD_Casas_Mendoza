# Lab Requests App (PHP + SQL Server)

This is a minimal but functional PHP application that implements:
- Roles: `student`, `professor`, `admin`
- Ability for students to create lab requests (CRUD)
- Professors can view, accept, or reject requests
- Admin can view everything
- **Auditing**: A centralized `audit_logs` table records logins and all CRUD actions:
  - Who performed the action (user id + username + role)
  - When (timestamp)
  - What action (LOGIN, CREATE, UPDATE, DELETE, ACCEPT, REJECT)
  - Affected table and affected row id (if applicable)
  - Additional details (JSON)

## Contents
- `sql/setup.sql` — SQL Server script to create the database schema and seed data.
- `src/` — PHP application files.
- `styles/` — CSS.

## Requirements
- SQL Server instance (local or remote).
- PHP 8+ with the **sqlsrv** or **PDO_SQLSRV** extension enabled. (On Linux you can use the Microsoft drivers or FreeTDS + dblib with PDO.)
- A web server (Apache, Nginx, or PHP built-in server).

## Quick setup

1. Run the SQL script `sql/setup.sql` on your SQL Server to create the database `LabRequestsDB` and seed sample users.
   - You can run it with SQL Server Management Studio or `sqlcmd`.

2. Update database connection in `src/config.php`:
```php
<?php
define('DB_DSN', 'sqlsrv:Server=YOUR_SERVER_NAME;Database=LabRequestsDB');
define('DB_USER', 'sa');
define('DB_PASS', 'YourStrong!Passw0rd');
```

3. Put the `src/` files in your web root or run PHP's built-in server for testing:
```bash
cd src
php -S 0.0.0.0:8080
# then open http://localhost:8080 in your browser
```

4. Login with seeded users:
- Admin: `admin@example.com` / `Admin123!`
- Professor: `prof1@example.com` / `Prof123!`
- Student: `student1@example.com` / `Stud123!`

## Notes on auditing
- Every successful login writes a `LOGIN` entry in `audit_logs`.
- CRUD operations on `lab_requests` and user management actions write `CREATE`, `UPDATE`, `DELETE`, `ACCEPT`, `REJECT` events.
- The `audit_logs.details` column stores a JSON string with extra context.

## Files of interest
- `src/db.php` — Database connection (PDO).
- `src/actions.php` — Action handlers that create audit log entries.
- `sql/setup.sql` — Schema + sample data.

If you want any extra features (email notifications, nicer UI, pagination or filtering), tell me which and I'll add them.
