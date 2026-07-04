-- =============================================
-- استخدام قاعدة البيانات الرئيسية
-- =============================================
USE security_monitoring_db;



-- تقرير استخدام حقيقي من البيانات الموجودة
INSERT INTO cloud_reports (
    report_code, report_name, report_type, period, date_from, date_to, format, summary, created_by, created_at
)
SELECT 
    CONCAT('RPT-', YEAR(NOW()), '-', LPAD((SELECT COUNT(*) + 1 FROM cloud_reports), 3, '0')) as report_code,
    CONCAT('تقرير استخدام ', DATE_FORMAT(NOW(), '%Y-%m-%d')) as report_name,
    'usage' as report_type,
    'daily' as period,
    CURDATE() as date_from,
    CURDATE() as date_to,
    'pdf' as format,
    CONCAT('إجمالي الملفات: ', (SELECT COUNT(*) FROM cloud_files), ' | حجم التخزين: ', ROUND(SUM(file_size) / (1024*1024*1024), 2), ' GB') as summary,
    1 as created_by,
    NOW() as created_at
FROM cloud_files;


/*
-- =============================================
-- إضافة بيانات تجريبية لجدول التقارير (cloud_reports)
-- =============================================

INSERT INTO cloud_reports (
    report_code, 
    report_name, 
    report_type, 
    period, 
    date_from, 
    date_to, 
    format, 
    summary, 
    created_by, 
    created_at
) VALUES 
-- تقارير شهرية
('RPT-2024-001', 'تقرير استخدام التخزين - يناير 2024', 'usage', 'monthly', '2024-01-01', '2024-01-31', 'pdf', 'تقرير شامل عن استخدام التخزين لشهر يناير، يشمل إحصائيات الملفات والمجلدات وحجم التخزين المستخدم', 1, '2024-02-01 10:30:00'),
('RPT-2024-002', 'تقرير أداء الخوادم - يناير 2024', 'performance', 'monthly', '2024-01-01', '2024-01-31', 'pdf', 'تحليل أداء الخوادم الرئيسية واستخدام الموارد خلال شهر يناير', 2, '2024-02-02 14:15:00'),
('RPT-2024-003', 'تقرير أمني - يناير 2024', 'security', 'monthly', '2024-01-01', '2024-01-31', 'pdf', 'تقرير عن التحديثات الأمنية والثغرات المكتشفة خلال شهر يناير', 1, '2024-02-03 09:45:00'),
('RPT-2024-004', 'تقرير استخدام التخزين - فبراير 2024', 'usage', 'monthly', '2024-02-01', '2024-02-29', 'pdf', 'تقرير استخدام التخزين لشهر فبراير مع تحليل النمو', 3, '2024-03-01 11:20:00'),
('RPT-2024-005', 'تقرير أداء الخوادم - فبراير 2024', 'performance', 'monthly', '2024-02-01', '2024-02-29', 'pdf', 'أداء الخوادم واستجابة الخدمات خلال شهر فبراير', 2, '2024-03-02 16:30:00'),

-- تقارير أسبوعية
('RPT-2024-006', 'تقرير أسبوعي - الأسبوع الأول مارس 2024', 'usage', 'weekly', '2024-03-01', '2024-03-07', 'pdf', 'تقرير أسبوعي عن استخدام التخزين للأسبوع الأول من مارس', 1, '2024-03-08 08:00:00'),
('RPT-2024-007', 'تقرير أسبوعي - الأسبوع الثاني مارس 2024', 'performance', 'weekly', '2024-03-08', '2024-03-14', 'excel', 'تقرير أداء الخوادم للأسبوع الثاني', 2, '2024-03-15 13:45:00'),
('RPT-2024-008', 'تقرير أسبوعي - الأسبوع الثالث مارس 2024', 'security', 'weekly', '2024-03-15', '2024-03-21', 'pdf', 'تقرير أمني أسبوعي عن التحديثات والثغرات', 3, '2024-03-22 10:15:00'),
('RPT-2024-009', 'تقرير أسبوعي - الأسبوع الرابع مارس 2024', 'backup', 'weekly', '2024-03-22', '2024-03-28', 'pdf', 'تقرير عن حالة النسخ الاحتياطية الأسبوعية', 1, '2024-03-29 12:30:00'),

-- تقارير يومية
('RPT-2024-010', 'تقرير يومي - 2024-03-15', 'usage', 'daily', '2024-03-15', '2024-03-15', 'pdf', 'تقرير يومي مفصل عن استخدام التخزين', 2, '2024-03-15 23:59:00'),
('RPT-2024-011', 'تقرير يومي - 2024-03-16', 'performance', 'daily', '2024-03-16', '2024-03-16', 'pdf', 'تقرير أداء الخوادم ليوم 16 مارس', 1, '2024-03-16 23:59:00'),
('RPT-2024-012', 'تقرير يومي - 2024-03-17', 'security', 'daily', '2024-03-17', '2024-03-17', 'pdf', 'تقرير أمني يومي', 3, '2024-03-17 23:59:00'),

-- تقارير ربع سنوية
('RPT-2024-013', 'تقرير الربع الأول 2024', 'usage', 'quarterly', '2024-01-01', '2024-03-31', 'pdf', 'تقرير شامل للربع الأول من العام', 1, '2024-04-01 09:00:00'),
('RPT-2024-014', 'تقرير أداء الربع الأول', 'performance', 'quarterly', '2024-01-01', '2024-03-31', 'excel', 'تحليل أداء الخوادم خلال الربع الأول', 2, '2024-04-02 14:30:00'),
('RPT-2024-015', 'تقرير أمني - الربع الأول', 'security', 'quarterly', '2024-01-01', '2024-03-31', 'pdf', 'تقرير أمني ربع سنوي', 3, '2024-04-03 11:15:00'),

-- تقارير سنوية
('RPT-2024-016', 'تقرير سنوي 2023', 'audit', 'yearly', '2023-01-01', '2023-12-31', 'pdf', 'تقرير تدقيق سنوي شامل', 1, '2024-01-15 10:00:00'),
('RPT-2024-017', 'تقرير استخدام 2023', 'usage', 'yearly', '2023-01-01', '2023-12-31', 'pdf', 'تحليل استخدام التخزين لعام 2023', 2, '2024-01-16 13:20:00'),
('RPT-2024-018', 'تقرير أمني 2023', 'security', 'yearly', '2023-01-01', '2023-12-31', 'pdf', 'تقرير أمني سنوي', 3, '2024-01-17 15:45:00'),

-- تقارير مخصصة
('RPT-2024-019', 'تقرير خاص - مشروع التجارة الإلكترونية', 'custom', 'custom', '2024-02-01', '2024-02-15', 'pdf', 'تقرير مخصص عن مشروع التجارة الإلكترونية', 2, '2024-02-16 09:30:00'),
('RPT-2024-020', 'تحليل قاعدة البيانات', 'audit', 'custom', '2024-03-01', '2024-03-10', 'excel', 'تحليل أداء قاعدة البيانات', 1, '2024-03-11 16:00:00');

-- =============================================
-- عرض البيانات المضافة
-- =============================================
SELECT '✅ تم إضافة 20 تقرير تجريبي' as message;
SELECT COUNT(*) as 'عدد التقارير المضافة' FROM cloud_reports;
/*





/*
-- إضافة بيانات تجريبية للتنبيهات
INSERT INTO cloud_storage_alerts (server_id, alert_type, severity, title, message, threshold, current_value, is_resolved, created_at) VALUES
(1, 'warning', 'medium', 'مساحة تخزين منخفضة', 'سيرفر الويب الرئيسي وصل لـ 85% من سعة التخزين', 80, 85, 0, NOW()),
(2, 'critical', 'high', 'مساحة حرجة', 'سيرفر قاعدة البيانات وصل لـ 95% من سعة التخزين', 90, 95, 0, NOW()),
(3, 'info', 'low', 'نمو سريع', 'زيادة كبيرة في حجم الملفات على سيرفر النسخ الاحتياطي', NULL, NULL, 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- إضافة بيانات تجريبية لأنواع الملفات
INSERT INTO cloud_file_types_stats (server_id, file_extension, files_count, total_size_mb, percentage, recorded_at) VALUES
(1, 'pdf', 1250, 2560, 15.5, CURDATE()),
(1, 'jpg', 3450, 5120, 31.0, CURDATE()),
(1, 'png', 2340, 3840, 23.3, CURDATE()),
(1, 'mp4', 120, 10240, 62.0, CURDATE()),
(1, 'docx', 890, 1280, 7.8, CURDATE()),
(1, 'zip', 450, 5120, 31.0, CURDATE());

-- إضافة بيانات مراقبة للرسم البياني
INSERT INTO cloud_storage_monitoring (server_id, total_space_gb, used_space_gb, used_percent, files_count, folders_count, daily_growth_mb, check_time) VALUES
(1, 500, 325, 65.0, 12450, 345, 256, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 500, 328, 65.6, 12500, 346, 245, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 500, 332, 66.4, 12580, 347, 278, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 500, 335, 67.0, 12650, 348, 290, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 500, 338, 67.6, 12720, 349, 265, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 500, 342, 68.4, 12800, 350, 310, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 500, 345, 69.0, 12850, 351, 280, NOW());
/*
-- إذا كان الجدول فاضي، أضيفي بيانات تجريبية
INSERT INTO cloud_security_updates (
    update_code, update_name, package_name, current_version, 
    available_version, severity, description, cve_id, 
    server_id, status, created_at
) VALUES 
('SEC-2024-001', 'تحديث OpenSSL', 'openssl', '1.1.1t', '3.0.8', 'critical', 'ثغرة أمنية حرجة في OpenSSL تسمح بتنفيذ تعليمات برمجية عن بعد', 'CVE-2024-1234', 1, 'pending', NOW()),
('SEC-2024-002', 'تحديث Apache', 'apache2', '2.4.52', '2.4.58', 'high', 'ثغرة في Apache تسمح بتجاوز المصادقة', 'CVE-2024-5678', 1, 'pending', NOW()),
('SEC-2024-003', 'تحديث MySQL', 'mysql-server', '8.0.32', '8.0.36', 'medium', 'تحسينات أمنية وأداء لقاعدة البيانات', NULL, 2, 'applied', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('SEC-2024-004', 'تحديث PHP', 'php8.1', '8.1.20', '8.1.27', 'high', 'إصلاح ثغرات أمنية متعددة في PHP', 'CVE-2024-9012', 1, 'scheduled', DATE_SUB(NOW(), INTERVAL 2 DAY));
/*
-- إضافة مجلدات رئيسية
INSERT INTO cloud_files (file_name, file_path, file_type, file_size, folder_path, is_folder, created_at) VALUES
('المستندات', '/documents', NULL, 0, '/', 1, NOW()),
('الصور', '/images', NULL, 0, '/', 1, NOW()),
('الفيديوهات', '/videos', NULL, 0, '/', 1, NOW()),
('النسخ الاحتياطية', '/backups', NULL, 0, '/', 1, NOW());

-- إضافة ملفات في المجلد الرئيسي
INSERT INTO cloud_files (file_name, file_path, file_type, file_size, folder_path, is_folder, download_count, created_at) VALUES
('تقرير_المبيعات.pdf', '/report.pdf', 'pdf', 2450000, '/', 0, 125, NOW()),
('صورة_المنتج.jpg', '/product.jpg', 'jpg', 890000, '/', 0, 67, NOW()),
('ملف_تكوين.json', '/config.json', 'json', 4500, '/', 0, 23, NOW()),
('دليل_الاستخدام.pdf', '/manual.pdf', 'pdf', 1850000, '/', 0, 89, NOW());

-- إضافة ملفات في مجلد المستندات
INSERT INTO cloud_files (file_name, file_path, file_type, file_size, folder_path, is_folder, download_count, created_at) VALUES
('عقد_تطوير.docx', '/documents/contract.docx', 'docx', 560000, '/documents', 0, 34, NOW()),
('مواصفات_المشروع.pdf', '/documents/specs.pdf', 'pdf', 3200000, '/documents', 0, 56, NOW()),
('ملاحظات_الاجتماع.txt', '/documents/notes.txt', 'txt', 12000, '/documents', 0, 12, NOW());

-- إضافة ملفات في مجلد الصور
INSERT INTO cloud_files (file_name, file_path, file_type, file_size, folder_path, is_folder, download_count, created_at) VALUES
('شعار_الشركة.png', '/images/logo.png', 'png', 245000, '/images', 0, 234, NOW()),
('خلفية_الموقع.jpg', '/images/background.jpg', 'jpg', 1850000, '/images', 0, 167, NOW()),
('أيقونة.png', '/images/icon.png', 'png', 45000, '/images', 0, 89, NOW());

/*
-- =============================================
-- 1. جدول الخوادم (cloud_servers)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_name VARCHAR(255) NOT NULL,
    server_code VARCHAR(50) UNIQUE NOT NULL,
    server_type ENUM('web', 'database', 'backup', 'storage', 'mail', 'dns') DEFAULT 'web',
    ip_address VARCHAR(45),
    hostname VARCHAR(255),
    os VARCHAR(100),
    cpu_cores INT DEFAULT 1,
    ram_gb INT DEFAULT 4,
    storage_gb INT DEFAULT 100,
    storage_used_gb INT DEFAULT 0,
    status ENUM('online', 'offline', 'maintenance', 'warning', 'provisioning') DEFAULT 'online',
    location VARCHAR(255),
    provider VARCHAR(255),
    monthly_cost DECIMAL(10,2),
    purchase_date DATE,
    last_reboot DATETIME,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (server_type),
    INDEX idx_code (server_code)
);

-- =============================================
-- 2. جدول المشاريع المستضافة (cloud_projects)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(50) UNIQUE NOT NULL,
    domain VARCHAR(255),
    server_id INT,
    project_type ENUM('website', 'application', 'database', 'storage', 'email') DEFAULT 'website',
    framework VARCHAR(100),
    language VARCHAR(50),
    git_repo VARCHAR(500),
    deploy_path VARCHAR(500),
    env_file TEXT,
    status ENUM('active', 'inactive', 'suspended', 'maintenance', 'deploying') DEFAULT 'active',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    backup_enabled BOOLEAN DEFAULT TRUE,
    monitoring_enabled BOOLEAN DEFAULT TRUE,
    client_name VARCHAR(255),
    client_email VARCHAR(255),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (project_type),
    INDEX idx_code (project_code)
);

-- =============================================
-- 3. جدول الملفات والمجلدات (cloud_files)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100),
    folder_path VARCHAR(500) DEFAULT '/',
    project_id INT,
    server_id INT,
    is_folder BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    uploaded_by INT,
    version VARCHAR(20) DEFAULT '1.0',
    permissions VARCHAR(9) DEFAULT '644',
    owner VARCHAR(100),
    group_owner VARCHAR(100),
    checksum VARCHAR(64),
    last_accessed DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cloud_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE SET NULL,
    INDEX idx_project (project_id),
    INDEX idx_folder (folder_path),
    INDEX idx_type (file_type),
    INDEX idx_server (server_id)
);

-- =============================================
-- 4. جدول عمليات النشر (cloud_deployments)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_deployments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    deployment_code VARCHAR(50) UNIQUE NOT NULL,
    project_id INT NOT NULL,
    deployment_type ENUM('full', 'incremental', 'quick', 'rollback') DEFAULT 'full',
    environment ENUM('development', 'staging', 'production', 'testing') DEFAULT 'development',
    status ENUM('pending', 'in_progress', 'success', 'failed', 'rolled_back', 'cancelled') DEFAULT 'pending',
    version VARCHAR(50),
    commit_hash VARCHAR(100),
    branch VARCHAR(100),
    files_count INT DEFAULT 0,
    size_mb DECIMAL(10,2) DEFAULT 0,
    started_at DATETIME,
    completed_at DATETIME,
    deployed_by INT,
    logs TEXT,
    error_log TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cloud_projects(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_environment (environment),
    INDEX idx_code (deployment_code)
);

-- =============================================
-- 5. جدول النسخ الاحتياطي (cloud_backups)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_code VARCHAR(50) UNIQUE NOT NULL,
    backup_name VARCHAR(255) NOT NULL,
    project_id INT,
    server_id INT,
    backup_type ENUM('full', 'incremental', 'differential', 'mirror', 'snapshot') DEFAULT 'full',
    size_mb DECIMAL(10,2) DEFAULT 0,
    files_count INT DEFAULT 0,
    destination ENUM('local', 'remote', 'both', 'cloud') DEFAULT 'local',
    storage_path VARCHAR(500),
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'restoring') DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME,
    retention_days INT DEFAULT 30,
    is_automated BOOLEAN DEFAULT FALSE,
    created_by INT,
    restored_at DATETIME,
    restored_by INT,
    restored_to VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cloud_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (backup_type),
    INDEX idx_code (backup_code)
);

-- =============================================
-- 6. جدول جداول النسخ الاحتياطي (cloud_backup_schedules)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_backup_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_name VARCHAR(255) NOT NULL,
    project_id INT,
    server_id INT,
    backup_type ENUM('full', 'incremental', 'differential') DEFAULT 'full',
    frequency ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly') DEFAULT 'daily',
    scheduled_time TIME,
    scheduled_day VARCHAR(20),
    scheduled_date INT,
    destination ENUM('local', 'remote', 'both', 'cloud') DEFAULT 'local',
    retention_days INT DEFAULT 30,
    is_active BOOLEAN DEFAULT TRUE,
    last_run DATETIME,
    next_run DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cloud_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE SET NULL,
    INDEX idx_active (is_active),
    INDEX idx_frequency (frequency)
);

-- =============================================
-- 7. جدول مراقبة التخزين (cloud_storage_monitoring)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_storage_monitoring (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    total_space_gb DECIMAL(10,2),
    used_space_gb DECIMAL(10,2),
    free_space_gb DECIMAL(10,2),
    used_percent DECIMAL(5,2),
    files_count INT,
    folders_count INT,
    inodes_used INT,
    inodes_total INT,
    daily_growth_mb DECIMAL(10,2),
    weekly_growth_mb DECIMAL(10,2),
    monthly_growth_mb DECIMAL(10,2),
    check_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    INDEX idx_server (server_id),
    INDEX idx_created (created_at)
);

-- =============================================
-- 8. جدول أنواع الملفات (cloud_file_types_stats)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_file_types_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    file_extension VARCHAR(20),
    files_count INT,
    total_size_mb DECIMAL(10,2),
    percentage DECIMAL(5,2),
    recorded_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    INDEX idx_server (server_id),
    INDEX idx_extension (file_extension)
);

-- =============================================
-- 9. جدول تنبيهات التخزين (cloud_storage_alerts)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_storage_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    alert_type ENUM('critical', 'warning', 'info', 'success') DEFAULT 'info',
    severity ENUM('high', 'medium', 'low') DEFAULT 'medium',
    title VARCHAR(255),
    message TEXT,
    threshold INT,
    current_value INT,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at DATETIME,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    INDEX idx_resolved (is_resolved),
    INDEX idx_type (alert_type),
    INDEX idx_severity (severity)
);

-- =============================================
-- 10. جدول التحديثات الأمنية (cloud_security_updates)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_security_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    update_code VARCHAR(50) UNIQUE NOT NULL,
    update_name VARCHAR(255) NOT NULL,
    package_name VARCHAR(255),
    current_version VARCHAR(50),
    available_version VARCHAR(50),
    severity ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    description TEXT,
    cve_id VARCHAR(50),
    server_id INT,
    project_id INT,
    status ENUM('pending', 'applied', 'scheduled', 'failed', 'skipped') DEFAULT 'pending',
    applied_at DATETIME,
    scheduled_for DATETIME,
    applied_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES cloud_projects(id) ON DELETE CASCADE,
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_code (update_code)
);

-- =============================================
-- 11. جدول خدمات الخوادم (cloud_server_services)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_server_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    service_name VARCHAR(100),
    display_name VARCHAR(255),
    status ENUM('running', 'stopped', 'failed', 'starting', 'stopping', 'restarting') DEFAULT 'running',
    port INT,
    pid INT,
    cpu_usage DECIMAL(5,2),
    memory_usage_mb INT,
    started_at DATETIME,
    config_file VARCHAR(500),
    version VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    INDEX idx_server (server_id),
    INDEX idx_status (status)
);

-- =============================================
-- 12. جدول التقارير (cloud_reports)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_code VARCHAR(50) UNIQUE NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('usage', 'performance', 'security', 'backup', 'cost', 'audit', 'custom') DEFAULT 'usage',
    period ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom') DEFAULT 'monthly',
    date_from DATE,
    date_to DATE,
    file_path VARCHAR(500),
    file_size INT,
    format ENUM('pdf', 'excel', 'html', 'csv', 'json') DEFAULT 'pdf',
    summary TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (report_type),
    INDEX idx_period (period),
    INDEX idx_code (report_code)
);

-- =============================================
-- 13. جدول سجل النشاطات (cloud_activity_log)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_activity_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    activity_type ENUM('create', 'update', 'delete', 'upload', 'download', 'deploy', 'backup', 'restore', 'security', 'reboot', 'start', 'stop') NOT NULL,
    target_type ENUM('server', 'project', 'file', 'backup', 'deployment', 'update', 'service', 'report') NOT NULL,
    target_id INT,
    target_name VARCHAR(255),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id),
    INDEX idx_target (target_type, target_id)
);

-- =============================================
-- 14. جدول إحصائيات الخوادم (cloud_server_stats)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_server_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server_id INT NOT NULL,
    cpu_usage_percent DECIMAL(5,2),
    ram_usage_percent DECIMAL(5,2),
    ram_used_mb INT,
    ram_total_mb INT,
    disk_io_read_mb DECIMAL(10,2),
    disk_io_write_mb DECIMAL(10,2),
    network_in_mb DECIMAL(10,2),
    network_out_mb DECIMAL(10,2),
    uptime_seconds INT,
    load_average_1min DECIMAL(5,2),
    load_average_5min DECIMAL(5,2),
    load_average_15min DECIMAL(5,2),
    process_count INT,
    recorded_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES cloud_servers(id) ON DELETE CASCADE,
    INDEX idx_server (server_id),
    INDEX idx_recorded (recorded_at)
);

-- =============================================
-- 15. جدول إعدادات النظام (cloud_settings)
-- =============================================
CREATE TABLE IF NOT EXISTS cloud_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'array') DEFAULT 'text',
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 📥 البيانات التجريبية
-- =============================================

-- 1. الخوادم (cloud_servers)
INSERT INTO cloud_servers (server_name, server_code, server_type, ip_address, hostname, os, cpu_cores, ram_gb, storage_gb, storage_used_gb, status, location, provider, monthly_cost, purchase_date, last_reboot, notes, created_by) VALUES
('سيرفر الويب الرئيسي', 'SRV-WEB-001', 'web', '192.168.1.100', 'web01.cloud.local', 'Ubuntu 22.04', 8, 16, 500, 325, 'online', 'الرياض', 'Local DC', 1200.00, '2023-01-15', '2024-01-15 03:00:00', 'يستضيف مواقع الويب الرئيسية', 1),
('سيرفر قاعدة البيانات', 'SRV-DB-001', 'database', '192.168.1.101', 'db01.cloud.local', 'Ubuntu 22.04', 16, 32, 1000, 450, 'online', 'الرياض', 'Local DC', 2500.00, '2023-02-01', '2024-01-14 02:30:00', 'قواعد بيانات MySQL و PostgreSQL', 1),
('سيرفر النسخ الاحتياطي', 'SRV-BAK-001', 'backup', '192.168.1.102', 'backup01.cloud.local', 'Ubuntu 22.04', 4, 8, 2000, 850, 'warning', 'جدة', 'Cloud Provider', 800.00, '2023-03-10', '2024-01-10 04:00:00', 'يحتاج تنظيف', 2),
('سيرفر التخزين', 'SRV-STR-001', 'storage', '192.168.1.103', 'storage01.cloud.local', 'CentOS 8', 8, 16, 5000, 3250, 'online', 'الرياض', 'Local DC', 3500.00, '2023-04-20', '2024-01-13 01:00:00', 'تخزين الملفات والوسائط', 2),
('سيرفر البريد', 'SRV-MAIL-001', 'mail', '192.168.1.104', 'mail01.cloud.local', 'Debian 11', 4, 8, 200, 95, 'maintenance', 'جدة', 'Cloud Provider', 600.00, '2023-05-05', '2024-01-12 05:00:00', 'تحت الصيانة', 3);

-- 2. المشاريع المستضافة (cloud_projects)
INSERT INTO cloud_projects (project_name, project_code, domain, server_id, project_type, framework, language, git_repo, deploy_path, status, priority, backup_enabled, client_name, client_email, notes, created_by) VALUES
('موقع التجارة الإلكترونية', 'PRJ-ECOMM-001', 'shop.example.com', 1, 'website', 'Laravel', 'PHP', 'git@github.com:company/ecommerce.git', '/var/www/ecommerce', 'active', 'high', TRUE, 'شركة التجارة', 'client1@example.com', 'متجر رئيسي', 2),
('المدونة', 'PRJ-BLOG-001', 'blog.example.com', 1, 'website', 'WordPress', 'PHP', 'git@github.com:company/blog.git', '/var/www/blog', 'active', 'medium', TRUE, 'شركة المحتوى', 'client2@example.com', 'مدونة الشركة', 2),
('تطبيق API', 'PRJ-API-001', 'api.example.com', 1, 'application', 'Express', 'Node.js', 'git@github.com:company/api.git', '/var/www/api', 'active', 'critical', TRUE, 'التطبيقات الذكية', 'client3@example.com', 'API رئيسي', 3),
('قاعدة بيانات العملاء', 'PRJ-DB-001', NULL, 2, 'database', NULL, 'MySQL', NULL, NULL, 'active', 'high', TRUE, 'قسم تقنية', 'it@example.com', 'بيانات العملاء', 3),
('مستودع الملفات', 'PRJ-STOR-001', 'files.example.com', 4, 'storage', 'Nextcloud', 'PHP', 'git@github.com:company/nextcloud.git', '/var/www/nextcloud', 'active', 'medium', TRUE, 'الشركة', 'admin@example.com', 'مستودع داخلي', 2);

-- 3. الملفات والمجلدات (cloud_files)
INSERT INTO cloud_files (file_name, file_path, file_type, file_size, folder_path, project_id, server_id, is_folder, download_count, permissions, owner, group_owner, version) VALUES
('index.html', '/var/www/ecommerce/public/index.html', 'html', 45, '/ecommerce/public', 1, 1, FALSE, 1245, '644', 'www-data', 'www-data', '1.0'),
('app.js', '/var/www/ecommerce/resources/js/app.js', 'js', 120, '/ecommerce/resources/js', 1, 1, FALSE, 3560, '644', 'www-data', 'www-data', '2.1'),
('banner.jpg', '/var/www/ecommerce/public/images/banner.jpg', 'jpg', 2450, '/ecommerce/public/images', 1, 1, FALSE, 890, '644', 'www-data', 'www-data', '1.0'),
('style.css', '/var/www/ecommerce/public/css/style.css', 'css', 78, '/ecommerce/public/css', 1, 1, FALSE, 2340, '644', 'www-data', 'www-data', '1.5'),
('wp-config.php', '/var/www/blog/wp-config.php', 'php', 12, '/blog', 2, 1, FALSE, 560, '640', 'www-data', 'www-data', '1.0'),
('database.sql', '/backups/database.sql', 'sql', 15420, '/backups', NULL, 3, FALSE, 45, '600', 'root', 'root', '2024-01-15'),
('المجلد الرئيسي', '/sites', NULL, 0, '/', NULL, 1, TRUE, 0, '755', 'www-data', 'www-data', NULL),
('صور', '/images', NULL, 0, '/', NULL, 1, TRUE, 0, '755', 'www-data', 'www-data', NULL),
('سكربتات', '/scripts', NULL, 0, '/', NULL, 1, TRUE, 0, '755', 'www-data', 'www-data', NULL),
('نسخ احتياطية', '/backups', NULL, 0, '/', NULL, 3, TRUE, 0, '700', 'root', 'root', NULL);

-- 4. عمليات النشر (cloud_deployments)
INSERT INTO cloud_deployments (deployment_code, project_id, deployment_type, environment, status, version, commit_hash, branch, files_count, size_mb, started_at, completed_at, deployed_by, logs) VALUES
('DEP-2024-001', 1, 'full', 'production', 'success', '2.1.0', 'abc123def456', 'main', 124, 45.6, '2024-01-15 10:30:00', '2024-01-15 10:32:15', 2, 'نشر ناجح'),
('DEP-2024-002', 1, 'incremental', 'staging', 'success', '2.1.1', 'def789ghi012', 'develop', 15, 2.3, '2024-01-14 14:20:00', '2024-01-14 14:21:30', 2, 'تحديثات طفيفة'),
('DEP-2024-003', 2, 'full', 'production', 'success', '1.3.2', 'jkl345mno678', 'main', 85, 28.4, '2024-01-13 11:00:00', '2024-01-13 11:02:45', 3, 'نشر المدونة'),
('DEP-2024-004', 3, 'quick', 'development', 'failed', '3.0.1', 'pqr901stu234', 'feature/api', 8, 1.2, '2024-01-12 16:45:00', '2024-01-12 16:45:30', 3, 'فشل في الاتصال'),
('DEP-2024-005', 1, 'full', 'production', 'rolled_back', '2.0.9', 'vwx567yza890', 'main', 124, 45.6, '2024-01-10 09:15:00', '2024-01-10 09:17:00', 2, 'تم التراجع عن النشر');

-- 5. النسخ الاحتياطي (cloud_backups)
INSERT INTO cloud_backups (backup_code, backup_name, project_id, server_id, backup_type, size_mb, files_count, destination, storage_path, status, started_at, completed_at, retention_days, is_automated, created_by) VALUES
('BAK-2024-001', 'نسخة احتياطية يومية 2024-01-15', 1, 1, 'incremental', 156, 1240, 'local', '/backups/ecommerce/2024-01-15', 'completed', '2024-01-15 02:00:00', '2024-01-15 02:15:30', 30, TRUE, 2),
('BAK-2024-002', 'نسخة احتياطية أسبوعية', 2, 1, 'full', 850, 5120, 'both', '/backups/blog/weekly-2024-02', 'completed', '2024-01-14 02:00:00', '2024-01-14 02:45:20', 90, TRUE, 2),
('BAK-2024-003', 'نسخة احتياطية قاعدة البيانات', NULL, 2, 'full', 2450, 1, 'remote', 's3://backups/db-2024-01-13', 'completed', '2024-01-13 03:00:00', '2024-01-13 03:10:45', 30, TRUE, 3),
('BAK-2024-004', 'نسخة شهرية', 1, 1, 'full', 1250, 8560, 'both', '/backups/ecommerce/monthly-jan', 'completed', '2024-01-01 02:00:00', '2024-01-01 03:20:00', 365, TRUE, 2),
('BAK-2024-005', 'نسخة اختبارية', 3, 1, 'full', 45, 320, 'local', '/backups/api/test', 'failed', '2024-01-12 04:00:00', NULL, 7, FALSE, 3);

-- 6. جداول النسخ الاحتياطي (cloud_backup_schedules)
INSERT INTO cloud_backup_schedules (schedule_name, project_id, server_id, backup_type, frequency, scheduled_time, scheduled_day, destination, retention_days, is_active, last_run, next_run, created_by) VALUES
('نسخ يومي - موقع التجارة', 1, 1, 'incremental', 'daily', '02:00:00', NULL, 'local', 30, TRUE, '2024-01-15 02:00:00', '2024-01-16 02:00:00', 2),
('نسخ أسبوعي - المدونة', 2, 1, 'full', 'weekly', '02:00:00', 'Sunday', 'both', 90, TRUE, '2024-01-14 02:00:00', '2024-01-21 02:00:00', 2),
('نسخ قاعدة البيانات', NULL, 2, 'full', 'daily', '03:00:00', NULL, 'remote', 30, TRUE, '2024-01-15 03:00:00', '2024-01-16 03:00:00', 3),
('نسخ شهري - شامل', 1, 1, 'full', 'monthly', '02:00:00', '1', 'both', 365, TRUE, '2024-01-01 02:00:00', '2024-02-01 02:00:00', 2),
('نسخ اختباري', 3, 1, 'full', 'weekly', '04:00:00', 'Friday', 'local', 7, FALSE, NULL, '2024-01-19 04:00:00', 3);

-- 7. مراقبة التخزين (cloud_storage_monitoring)
INSERT INTO cloud_storage_monitoring (server_id, total_space_gb, used_space_gb, free_space_gb, used_percent, files_count, folders_count, daily_growth_mb, check_time) VALUES
(1, 500, 325, 175, 65.0, 12450, 345, 256, NOW()),
(2, 1000, 450, 550, 45.0, 230, 45, 124, NOW()),
(3, 2000, 850, 1150, 42.5, 15670, 890, 512, NOW()),
(4, 5000, 3250, 1750, 65.0, 45670, 2340, 1024, NOW()),
(5, 200, 95, 105, 47.5, 2340, 156, 45, NOW());

-- 8. تنبيهات التخزين (cloud_storage_alerts)
INSERT INTO cloud_storage_alerts (server_id, alert_type, severity, title, message, threshold, current_value, is_resolved, created_at) VALUES
(3, 'warning', 'medium', 'مساحة تخزين منخفضة', 'سيرفر النسخ الاحتياطي وصل لـ 85%', 80, 85, FALSE, '2024-01-15 08:30:00'),
(2, 'info', 'low', 'نمو سريع في قاعدة البيانات', 'زيادة 10% في حجم قاعدة البيانات هذا الأسبوع', NULL, NULL, FALSE, '2024-01-14 14:20:00'),
(1, 'success', 'low', 'تم تنظيف السيرفر', 'تم تحرير 25 جيجابايت', NULL, NULL, TRUE, '2024-01-13 11:00:00'),
(4, 'critical', 'high', 'مساحة حرجة', 'السيرفر الرئيسي وصل لـ 90%', 90, 92, TRUE, '2024-01-12 09:15:00'),
(5, 'warning', 'medium', 'خدمة البريد متوقفة', 'سيرفر البريد يحتاج إعادة تشغيل', NULL, NULL, TRUE, '2024-01-11 16:45:00');

-- 9. خدمات الخوادم (cloud_server_services)
INSERT INTO cloud_server_services (server_id, service_name, display_name, status, port, pid, cpu_usage, memory_usage_mb, started_at, version) VALUES
(1, 'nginx', 'Nginx Web Server', 'running', 80, 1234, 2.5, 128, '2024-01-15 03:00:00', '1.24.0'),
(1, 'php8.1-fpm', 'PHP-FPM', 'running', 9000, 5678, 5.2, 256, '2024-01-15 03:00:00', '8.1.27'),
(2, 'mysql', 'MySQL Database', 'running', 3306, 9012, 15.8, 1024, '2024-01-15 03:00:00', '8.0.35'),
(2, 'redis', 'Redis Cache', 'running', 6379, 3456, 1.2, 64, '2024-01-15 03:00:00', '7.2.4'),
(3, 'rsync', 'Rsync Backup', 'running', 873, 7890, 0.5, 32, '2024-01-15 03:00:00', '3.2.7'),
(4, 'nfs', 'NFS Server', 'running', 2049, 2345, 1.8, 96, '2024-01-15 03:00:00', '2.6.2'),
(5, 'postfix', 'Postfix Mail', 'stopped', 25, NULL, 0, 0, '2024-01-12 05:00:00', '3.7.6');

-- 10. إعدادات النظام (cloud_settings)
INSERT INTO cloud_settings (setting_key, setting_value, setting_type, description, updated_by) VALUES
('site_name', 'نظام الاستضافة السحابي', 'text', 'اسم الموقع', 1),
('default_backup_retention', '30', 'number', 'فترة الاحتفاظ الافتراضية للنسخ الاحتياطي (يوم)', 1),
('backup_enabled', 'true', 'boolean', 'تفعيل النسخ الاحتياطي التلقائي', 1),
('monitoring_enabled', 'true', 'boolean', 'تفعيل المراقبة', 1),
('alert_email', 'alerts@example.com', 'text', 'البريد الإلكتروني للتنبيهات', 1),
('storage_threshold_warning', '80', 'number', 'حد التحذير للتخزين (%)', 1),
('storage_threshold_critical', '90', 'number', 'حد الخطر للتخزين (%)', 1),
('auto_cleanup_enabled', 'true', 'boolean', 'تفعيل التنظيف التلقائي', 1),
('max_upload_size', '1024', 'number', 'الحد الأقصى لحجم الرفع (MB)', 1),
('allowed_file_types', '["jpg","png","pdf","docx","txt","php","html","css","js"]', 'json', 'أنواع الملفات المسموح بها', 1);

/*
cloud-unit/
│
├── index.php                               # الصفحة الرئيسية
│
├── config/
│   └── database.php                        # اتصال قاعدة البيانات
│
├── includes/
│   ├── functions.php                        # دوال مساعدة عامة
│   ├── auth.php                              # التحقق من المستخدم
│   └── cloud_functions.php                   # دوال خاصة بالوحدة
│
└── pages/
    ├── dashboard.php                          # لوحة التحكم الرئيسية
    ├── files.php                               # إدارة الملفات والمجلدات
    ├── deployment.php                          # عمليات النشر والرفع
    ├── backup.php                               # النسخ الاحتياطي والمزامنة
    ├── servers.php                              # إعدادات الخوادم
    ├── monitoring.php                           # مراقبة استخدام التخزين
    ├── security.php                             # التحديثات الأمنية
    └── reports.php                              # تقارير النظام

    