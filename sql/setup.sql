ALTER LOGIN sa ENABLE;
ALTER LOGIN sa WITH PASSWORD = 'Admin123!';

CREATE DATABASE LabRequestsDB;
GO

USE LabRequestsDB;
GO

-- TABLA roles
IF OBJECT_ID('roles', 'U') IS NOT NULL DROP TABLE roles;
CREATE TABLE roles (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(50) NOT NULL UNIQUE
);
GO

-- TABLA users (coincide con las consultas en index.php y joins)
IF OBJECT_ID('users', 'U') IS NOT NULL DROP TABLE users;
CREATE TABLE users (
    id INT IDENTITY(1,1) PRIMARY KEY,
    email NVARCHAR(255) NOT NULL UNIQUE,
    password_hash NVARCHAR(255) NOT NULL,
    full_name NVARCHAR(255) NOT NULL,
    role_id INT NOT NULL FOREIGN KEY REFERENCES roles(id)
);
GO

-- TABLA labs
IF OBJECT_ID('labs', 'U') IS NOT NULL DROP TABLE labs;
CREATE TABLE labs (
    id INT IDENTITY(1,1) PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    location NVARCHAR(255) NULL,
    capacity INT DEFAULT 0
);
GO

-- TABLA lab_requests (coincide con el uso en student.php, professor.php, admin.php, actions.php)
IF OBJECT_ID('lab_requests', 'U') IS NOT NULL DROP TABLE lab_requests;
CREATE TABLE lab_requests (
    id INT IDENTITY(1,1) PRIMARY KEY,
    student_id INT NOT NULL,          -- FK to users(id)
    lab_id INT NOT NULL,              -- FK to labs(id)
    requested_date DATE NOT NULL,
    requested_time NVARCHAR(20) NOT NULL,
    notes NVARCHAR(1000) NULL,
    status NVARCHAR(20) NOT NULL DEFAULT 'PENDING', -- PENDING / ACCEPTED / REJECTED / CANCELLED
    created_at DATETIME2 DEFAULT SYSUTCDATETIME(),
    updated_at DATETIME2 NULL,
    processed_by INT NULL,            -- user id of professor who processed
    CONSTRAINT FK_labrequests_student FOREIGN KEY (student_id) REFERENCES users(id),
    CONSTRAINT FK_labrequests_lab FOREIGN KEY (lab_id) REFERENCES labs(id),
    CONSTRAINT FK_labrequests_processedby FOREIGN KEY (processed_by) REFERENCES users(id)
);
GO

-- TABLA audit_logs (coincide con audit_log() in functions.php and admin.php)
IF OBJECT_ID('audit_logs', 'U') IS NOT NULL DROP TABLE audit_logs;
CREATE TABLE audit_logs (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NULL,
    user_email NVARCHAR(255) NULL,
    user_role NVARCHAR(50) NULL,
    action NVARCHAR(50) NOT NULL,
    target_table NVARCHAR(255) NULL,
    target_id NVARCHAR(255) NULL,
    details NVARCHAR(MAX) NULL,
    created_at DATETIME2 DEFAULT SYSUTCDATETIME()
);
GO

-- SEMILLAS: roles
INSERT INTO roles (name) VALUES ('student'), ('professor'), ('admin');
GO

-- SEMILLAS: labs de ejemplo
INSERT INTO labs (name, location, capacity) VALUES
('Computing Lab A', 'Building 1 - Room 101', 30),
('Physics Lab', 'Building 2 - Room 210', 20);
GO

INSERT INTO users (email, password_hash, full_name, role_id) VALUES
('admin@example.com', 'Admin123!', 'Administrator', (SELECT id FROM roles WHERE name='admin')),
('prof1@example.com', 'Prof123!', 'Prof One', (SELECT id FROM roles WHERE name='professor')),
('student1@example.com', 'Stud123!', 'Student One', (SELECT id FROM roles WHERE name='student'));
GO

-- Opcional: ejemplo de solicitudes (vacío por defecto)
-- INSERT INTO lab_requests (student_id, lab_id, requested_date, requested_time, notes) VALUES
-- ((SELECT id FROM users WHERE email='student1@example.com'), (SELECT id FROM labs WHERE name='Computing Lab A'), '2025-12-10', '09:00', 'Práctica de redes');
-- GO

-- Indice para acelerar búsquedas por estado/fecha (opcional)
CREATE INDEX IX_labrequests_status_created ON lab_requests (status, created_at);
GO


PRINT 'LabRequestsDB schema created. Remember to replace placeholder password_hash values using the PHP tool.';
GO


select * from users;


-- =============================================
-- CREACIÓN DE USUARIOS Y ASIGNACIÓN DE PERMISOS
-- =============================================

-- 1. Crear Logins (usuarios para SQL Server)
USE LabRequestsDB;
GO

-- Login para Administrador
CREATE LOGIN [LabAdmin] WITH PASSWORD = 'AdminSecurePass123!';
GO

-- Login para Profesor  
CREATE LOGIN [LabProf] WITH PASSWORD = 'ProfSecurePass123!';
GO

-- Login para Estudiante
CREATE LOGIN [LabStudent] WITH PASSWORD = 'StudentSecurePass123!';
GO

-- 2. Crear Usuarios en la base de datos y asignar permisos

-- Usuario Administrador
CREATE USER [LabAdmin] FOR LOGIN [LabAdmin];
GO
-- Dar todos los permisos (dueño de la base de datos)
ALTER ROLE [db_owner] ADD MEMBER [LabAdmin];
GO

-- Usuario Profesor
CREATE USER [LabProf] FOR LOGIN [LabProf];
GO
-- Permisos específicos para Profesor
GRANT SELECT ON roles TO LabProf;
GRANT SELECT ON users TO LabProf;
GRANT SELECT ON labs TO LabProf;
GRANT SELECT, UPDATE ON lab_requests TO LabProf;
GRANT SELECT ON audit_logs TO LabProf;
GRANT INSERT ON audit_logs TO LabProf;
GO

-- Usuario Estudiante
CREATE USER [LabStudent] FOR LOGIN [LabStudent];
GO
-- Permisos específicos para Estudiante
GRANT SELECT ON labs TO LabStudent;
GRANT SELECT, INSERT, UPDATE ON lab_requests TO LabStudent;
GRANT INSERT ON audit_logs TO LabStudent;
GO

-- 3. Crear Roles personalizados y asignar usuarios
-- Rol para Profesores
CREATE ROLE [ProfessorRole];
GO
GRANT SELECT ON roles TO ProfessorRole;
GRANT SELECT ON users TO ProfessorRole;
GRANT SELECT ON labs TO ProfessorRole;
GRANT SELECT, UPDATE ON lab_requests TO ProfessorRole;
GRANT SELECT, INSERT ON audit_logs TO ProfessorRole;
ALTER ROLE [ProfessorRole] ADD MEMBER [LabProf];
GO

-- Rol para Estudiantes
CREATE ROLE [StudentRole];
GO
GRANT SELECT ON labs TO StudentRole;
GRANT SELECT, INSERT, UPDATE ON lab_requests TO StudentRole;
GRANT INSERT ON audit_logs TO StudentRole;
ALTER ROLE [StudentRole] ADD MEMBER [LabStudent];
GO

