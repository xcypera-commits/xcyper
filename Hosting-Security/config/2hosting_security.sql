-- ملف: database/schema.sql
CREATE DATABASE IF NOT EXISTS hosting_security;
USE hosting_security;

-- جدول المستخدمين
CREATE TABLE users_security; (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role VARCHAR(50) NOT NULL DEFAULT 'client',
    status ENUM('active', 'inactive', 'suspended', 'locked') DEFAULT 'active',
    failed_login_attempts INT DEFAULT 0,
    last_failed_login DATETIME,
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    login_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- جدول الجلسات
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(128) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- جدول التوكنات
CREATE TABLE auth_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    token_type ENUM('access', 'refresh') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_revoked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- جدول الصلاحيات
CREATE TABLE user_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted_by INT,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission),
    INDEX idx_user_id (user_id),
    INDEX idx_permission (permission)
);

-- جدول سجلات التدقيق
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    event_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
);

-- جدول التهديدات
CREATE TABLE security_threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    threat_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_id INT,
    details JSON,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('new', 'investigating', 'resolved', 'false_positive') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    resolved_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_threat_type (threat_type),
    INDEX idx_ip_address (ip_address),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- جدول الحوادث
CREATE TABLE security_incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('new', 'investigating', 'contained', 'resolved', 'closed') DEFAULT 'new',
    assigned_to INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_incident_type (incident_type),
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to)
);

-- audit_events
CREATE TABLE IF NOT EXISTS audit_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_category VARCHAR(50) NOT NULL,
    user_id INT UNSIGNED NULL,
    user_ip VARCHAR(45) NOT NULL,
    user_agent TEXT,
    resource_type VARCHAR(50) NULL,
    resource_id VARCHAR(100) NULL,
    action VARCHAR(100) NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    status ENUM('success', 'failure', 'pending') DEFAULT 'success',
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- audit_logins
CREATE TABLE IF NOT EXISTS audit_logins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    country_code CHAR(2),
    user_agent TEXT,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(100) NULL,
    mfa_used TINYINT(1) DEFAULT 0,
    session_id VARCHAR(100),
    login_duration INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- إضافة مستخدم مدير افتراضي
INSERT INTO users (username, email, password_hash, full_name, role) 
VALUES (
    'admin', 
    'admin@hosting-system.com',
    '$2y$12$YourHashedPasswordHere', -- استخدم SecurityUtils::generate_hash('your-password')
    'System Administrator',
    'hosting_security_manager'
);