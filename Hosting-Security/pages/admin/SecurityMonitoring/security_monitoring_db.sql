
-- =============================================
-- ملف قاعدة البيانات الكامل - security_monitoring_db
-- =============================================

-- Drop database if exists and create new one

USE security_monitoring_db;


-- =============================================
-- إدراج بيانات الموظفين في جدول users
-- كلمة المرور للجميع: Staff@123
-- =============================================
INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `department`, `unit_id`, `is_active`, `can_manage`) VALUES
-- قسم الاستضافة
('ahmed.hosting', 'ahmed.hosting@xcyper.com', '$2y$10$YourHashedPasswordHere', 'أحمد محمد الجهني', 'hosting', 'hosting', 1, 1, 1),
('saeed.hosting', 'saeed.hosting@xcyper.com', '$2y$10$YourHashedPasswordHere', 'سعيد علي الغامدي', 'hosting', 'hosting', 1, 1, 0),

-- قسم التخزين
('noura.storage', 'noura.storage@xcyper.com', '$2y$10$YourHashedPasswordHere', 'نورة عبدالله الشمري', 'storage', 'storage', 2, 1, 1),
('fahad.storage', 'fahad.storage@xcyper.com', '$2y$10$YourHashedPasswordHere', 'فهد خالد الدوسري', 'storage', 'storage', 2, 1, 0),

-- قسم الحماية
('aziz.security', 'aziz.security@xcyper.com', '$2y$10$YourHashedPasswordHere', 'عبدالعزيز محمد العتيبي', 'security', 'security', 3, 1, 1),
('faisal.security', 'faisal.security@xcyper.com', '$2y$10$YourHashedPasswordHere', 'فيصل عبدالرحمن الحربي', 'security', 'security', 3, 1, 0),

-- قسم اختبار الاختراق
('turki.pentest', 'turki.pentest@xcyper.com', '$2y$10$YourHashedPasswordHere', 'تركي سعد القحطاني', 'pentest', 'pentest', 4, 1, 1),
('haya.pentest', 'haya.pentest@xcyper.com', '$2y$10$YourHashedPasswordHere', 'هيا عبدالله المطيري', 'pentest', 'pentest', 4, 1, 0),

-- قسم التوثيق
('sara.docs', 'sara.docs@xcyper.com', '$2y$10$YourHashedPasswordHere', 'سارة محمد العنزي', 'documentation', 'documentation', 5, 1, 1),
('nouf.docs', 'nouf.docs@xcyper.com', '$2y$10$YourHashedPasswordHere', 'نوف بندر السبيعي', 'technical_writer', 'documentation', 5, 1, 0),

-- الإدارة العامة
('khalid.admin', 'khalid.admin@xcyper.com', '$2y$10$YourHashedPasswordHere', 'خالد ابراهيم الحارثي', 'admin', 'management', 6, 1, 1),
('lama.manager', 'lama.manager@xcyper.com', '$2y$10$YourHashedPasswordHere', 'لمى سعد الدوسري', 'manager', 'management', 6, 1, 1),
('nasser.analyst', 'nasser.analyst@xcyper.com', '$2y$10$YourHashedPasswordHere', 'ناصر عبدالله القحطاني', 'analyst', 'management', 6, 1, 0);

/*
-- =============================================
-- 1. جدول المستخدمين (users)
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','analyst','viewer') NOT NULL,
    department VARCHAR(100),
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (id, username, email, password_hash, full_name, role, department) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', 'hash123', 'أحمد العلي', 'manager', 'وحدة الحماية'),
(2, 'sara.mohammed', 'sara.m@company.com', 'hash123', 'سارة محمد', 'analyst', 'وحدة الحماية'),
(3, 'khaled.omar', 'khaled.o@company.com', 'hash123', 'خالد عمر', 'analyst', 'وحدة الحماية'),
(4, 'nora.ahmed', 'nora.a@company.com', 'hash123', 'نورا أحمد', 'analyst', 'وحدة الحماية'),
(5, 'fahad.saud', 'fahad.s@company.com', 'hash123', 'فهد سعود', 'admin', 'الإدارة العليا');

-- =============================================
-- 2. جدول الخوادم (servers)
-- =============================================
CREATE TABLE servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('web','database','application','cache','loadbalancer') NOT NULL,
    status ENUM('online','warning','offline','maintenance') DEFAULT 'online',
    ip_address VARCHAR(45) NOT NULL,
    location VARCHAR(100),
    cpu_usage DECIMAL(5,2) DEFAULT 0,
    memory_usage DECIMAL(5,2) DEFAULT 0,
    storage_usage DECIMAL(5,2) DEFAULT 0,
    uptime INT DEFAULT 0,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO servers (id, name, type, status, ip_address, location, cpu_usage, memory_usage, storage_usage, uptime) VALUES
(1, 'Web-Server-01', 'web', 'online', '192.168.1.100', 'الرياض', 65.5, 45.2, 32.8, 518400),
(2, 'DB-Server-02', 'database', 'warning', '192.168.1.101', 'الرياض', 85.3, 78.6, 92.4, 432000),
(3, 'App-Server-03', 'application', 'online', '192.168.1.102', 'جدة', 45.8, 60.2, 40.1, 604800),
(4, 'Cache-Server-01', 'cache', 'online', '192.168.1.103', 'الرياض', 72.4, 55.3, 28.5, 345600),
(5, 'Web-Server-02', 'web', 'online', '192.168.1.104', 'جدة', 38.2, 42.8, 35.6, 259200),
(6, 'DB-Server-01', 'database', 'online', '192.168.1.105', 'الرياض', 52.7, 48.9, 55.3, 691200),
(7, 'LoadBalancer-01', 'loadbalancer', 'online', '192.168.1.106', 'الرياض', 25.4, 35.2, 22.1, 777600),
(8, 'App-Server-02', 'application', 'offline', '192.168.1.107', 'الرياض', 0, 0, 65.8, 0);

-- =============================================
-- 3. جدول التهديدات (threats) - الأهم لصفحتنا
-- =============================================
CREATE TABLE threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('ddos', 'brute_force', 'sql_injection', 'xss', 'malware', 'phishing') NOT NULL,
    source_ip VARCHAR(45),
    target_server_id INT,
    target_url TEXT,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    status ENUM('active', 'mitigated', 'blocked', 'investigating') DEFAULT 'active',
    description TEXT,
    attack_pattern TEXT,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP,
    mitigated_at TIMESTAMP NULL,
    mitigated_by INT,
    FOREIGN KEY (target_server_id) REFERENCES servers(id) ON DELETE SET NULL,
    FOREIGN KEY (mitigated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_last_seen (last_seen)
);

-- =============================================
-- 4. إضافة بيانات التهديدات (بيانات حقيقية)
-- =============================================
INSERT INTO threats (name, type, source_ip, target_server_id, target_url, severity, status, description, first_seen, last_seen) VALUES
('هجوم DDoS على خادم الويب', 'ddos', '45.123.67.89', 1, 'https://example.com', 'critical', 'active', 'هجوم حجب خدمة من عدة مصادر', DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW()),
('محاولات تخمين كلمات المرور', 'brute_force', '103.21.244.0', 5, 'https://example.com/login', 'high', 'active', 'محاولات دخول متكررة من 20 IP مختلف', DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW()),
('حقن SQL على صفحة المنتجات', 'sql_injection', '78.45.123.22', 3, 'https://example.com/products', 'critical', 'mitigated', 'محاولة حقن SQL لاكتشاف قاعدة البيانات', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR)),
('هجوم XSS على نموذج البحث', 'xss', '56.78.90.123', 1, 'https://example.com/search', 'medium', 'blocked', 'محاولة إدخال سكريبت ضار', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR)),
('محاولة رفع ملف ضار', 'malware', '112.67.34.56', 5, 'https://example.com/upload', 'high', 'mitigated', 'محاولة رفع ملف PHP ضار', DATE_SUB(NOW(), INTERVAL 7 HOUR), DATE_SUB(NOW(), INTERVAL 6 HOUR)),
('هجوم DDoS على API', 'ddos', '89.123.45.67', 3, 'https://api.example.com', 'critical', 'active', 'هجوم على واجهة البرمجة', DATE_SUB(NOW(), INTERVAL 4 HOUR), NOW()),
('محاولة تصيد احتيالي', 'phishing', '34.56.78.90', NULL, 'fake-login-page.com', 'medium', 'blocked', 'نطاق مشبوه يحاكي صفحة الدخول', DATE_SUB(NOW(), INTERVAL 8 HOUR), DATE_SUB(NOW(), INTERVAL 7 HOUR)),
('هجوم SQL على لوحة التحكم', 'sql_injection', '67.89.123.45', 2, 'https://admin.example.com', 'critical', 'investigating', 'محاولة استغلال ثغرة SQL في لوحة التحكم', DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW());

-- =============================================
-- 5. جدول التنبيهات (alerts)
-- =============================================
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('critical','warning','info') NOT NULL,
    severity ENUM('high','medium','low') NOT NULL,
    source VARCHAR(100),
    server_id INT,
    status ENUM('new','acknowledged','in-progress','resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    acknowledged_by INT,
    resolved_by INT,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO alerts (title, description, type, severity, source, server_id, status, created_at) VALUES
('ارتفاع استخدام المعالج', 'استخدام المعالج تجاوز 85%', 'critical', 'high', 'DB-Server-02', 2, 'new', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
('محاولة وصول غير مصرح', '5 محاولات فاشلة من IP 45.76.89.123', 'warning', 'medium', 'Web-Server-01', 1, 'acknowledged', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('مساحة تخزين منخفضة', 'المساحة أقل من 10%', 'critical', 'high', 'DB-Server-02', 2, 'in-progress', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('حركة شبكة غير طبيعية', 'زيادة 200% في حركة الشبكة', 'warning', 'medium', 'Network', NULL, 'new', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('انقطاع خدمة', 'خادم التطبيقات 02 غير مستجيب', 'critical', 'high', 'App-Server-02', 8, 'new', DATE_SUB(NOW(), INTERVAL 55 MINUTE));

-- =============================================
-- 6. جدول قواعد التنبيهات (alert_rules)
-- =============================================
CREATE TABLE alert_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    condition_type ENUM('cpu', 'memory', 'storage', 'login_attempts', 'network') NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    comparison_operator ENUM('>', '<', '>=', '<=', '=') NOT NULL,
    severity ENUM('critical', 'warning', 'info') NOT NULL,
    target_server_type VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO alert_rules (name, description, condition_type, threshold_value, comparison_operator, severity, target_server_type, created_by) VALUES
('ارتفاع CPU - خادم قاعدة بيانات', 'تنبيه عند ارتفاع استخدام المعالج فوق 85%', 'cpu', 85.00, '>', 'critical', 'database', 1),
('استخدام ذاكرة عالي', 'تنبيه عند استخدام الذاكرة فوق 90%', 'memory', 90.00, '>', 'critical', NULL, 1),
('مساحة تخزين منخفضة', 'تنبيه عند انخفاض المساحة تحت 15%', 'storage', 15.00, '<', 'warning', NULL, 2),
('محاولات دخول فاشلة', 'تنبيه عند 5 محاولات دخول فاشلة', 'login_attempts', 5.00, '>=', 'warning', NULL, 2);

-- =============================================
-- 7. جدول التقارير اليومية (daily_reports)
-- =============================================
CREATE TABLE daily_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    report_type ENUM('security', 'performance', 'network', 'incident', 'compliance') NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    statistics JSON,
    file_path VARCHAR(500),
    file_size INT,
    generated_by INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_daily_report (report_date, report_type)
);

INSERT INTO daily_reports (report_date, report_type, title, summary, statistics, generated_by) VALUES
(CURDATE(), 'security', 'تقرير الأمان اليومي', 'تقرير شامل لأحداث الأمان لليوم', '{"total_alerts":24,"critical":5,"warning":12,"info":8,"threats":8}', 1),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'security', 'تقرير الأمان ليوم أمس', 'تقرير أحداث الأمان ليوم أمس', '{"total_alerts":18,"critical":3,"warning":9,"info":6,"threats":5}', 1),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'performance', 'تقرير أداء الخوادم', 'تحليل أداء جميع الخوادم', '{"avg_cpu":48.5,"avg_memory":52.3,"avg_storage":46.2,"uptime":99.98}', 2);

-- =============================================
-- 8. جدول العملاء (clients)
-- =============================================
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    website VARCHAR(255),
    package_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO clients (company_name, contact_name, email, phone, website, package_type) VALUES
('شركة التقنية المتطورة', 'محمد العمري', 'info@tech-sa.com', '0555123456', 'tech-sa.com', 'enterprise'),
('مؤسسة الأمان الرقمي', 'أحمد الجهني', 'contact@digital-security.com', '0555234567', 'digital-security.com', 'professional'),
('متجر الإلكترونيات', 'سارة القحطاني', 'support@electronics-store.com', '0555345678', 'electronics-store.com', 'basic');

-- =============================================
-- 9. جدول مواقع العملاء (client_websites)
-- =============================================
CREATE TABLE client_websites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    server_id INT,
    status ENUM('active', 'suspended', 'maintenance') DEFAULT 'active',
    disk_usage BIGINT DEFAULT 0,
    bandwidth_usage BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_domain (domain)
);

INSERT INTO client_websites (client_id, domain, server_id, disk_usage, bandwidth_usage) VALUES
(1, 'tech-sa.com', 1, 1500000000, 5000000000),
(1, 'api.tech-sa.com', 3, 500000000, 2000000000),
(2, 'digital-security.com', 1, 800000000, 3000000000),
(3, 'electronics-store.com', 5, 2000000000, 8000000000);

-- =============================================
-- 10. جدول تقارير العملاء (client_reports)
-- =============================================
CREATE TABLE client_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    website_id INT,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    report_date DATE NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    statistics JSON,
    status ENUM('generated', 'sent', 'failed') DEFAULT 'generated',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES client_websites(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 11. جدول السياسات الأمنية (security_policies)
-- =============================================
CREATE TABLE security_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('password', 'access', 'backup', 'encryption', 'network', 'compliance') NOT NULL,
    priority ENUM('high', 'medium', 'low') NOT NULL,
    scope VARCHAR(255),
    compliance_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    version VARCHAR(20),
    content TEXT,
    created_by INT,
    approved_by INT,
    effective_date DATE,
    review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO security_policies (name, description, category, priority, scope, compliance_percentage, status, version, content, created_by, effective_date, review_date) VALUES
('سياسة كلمات المرور', 'تحدد متطلبات إنشاء كلمات المرور وتغييرها', 'password', 'high', 'all', 98.5, 'active', 'v2.1', 'كلمات المرور يجب أن تكون 8 أحرف على الأقل، تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
('سياسة الوصول', 'تحدد صلاحيات الوصول للأنظمة المختلفة', 'access', 'high', 'all', 95.2, 'active', 'v3.0', 'صلاحيات الوصول تعتمد على مبدأ least privilege', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH)),
('سياسة النسخ الاحتياطي', 'تحدد جدول وإجراءات النسخ الاحتياطي', 'backup', 'high', 'servers', 85.0, 'active', 'v1.5', 'نسخ احتياطي يومي كامل، نسخ أسبوعي لأرشفة', 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH));

-- =============================================
-- 12. جدول إحصائيات الأمان (security_statistics)
-- =============================================
CREATE TABLE security_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL,
    stat_hour TINYINT,
    total_attacks INT DEFAULT 0,
    blocked_attacks INT DEFAULT 0,
    ddos_attacks INT DEFAULT 0,
    brute_force_attacks INT DEFAULT 0,
    sql_injection_attacks INT DEFAULT 0,
    xss_attacks INT DEFAULT 0,
    avg_response_time DECIMAL(10,2),
    total_alerts INT DEFAULT 0,
    critical_alerts INT DEFAULT 0,
    warning_alerts INT DEFAULT 0,
    info_alerts INT DEFAULT 0,
    active_threats INT DEFAULT 0,
    mitigated_threats INT DEFAULT 0,
    system_uptime DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_hour (stat_date, stat_hour)
);

INSERT INTO security_statistics (stat_date, stat_hour, total_attacks, blocked_attacks, avg_response_time, system_uptime) VALUES
(CURDATE(), 0, 45, 43, 1.2, 99.99),
(CURDATE(), 1, 32, 31, 1.1, 99.99),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 0, 52, 49, 1.5, 99.98),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 0, 68, 64, 1.8, 99.97);

-- =============================================
-- 13. جدول السجلات (logs)
-- =============================================
CREATE TABLE logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    log_type ENUM('security', 'system', 'network', 'application') NOT NULL,
    level ENUM('error', 'warning', 'info', 'debug') NOT NULL,
    source VARCHAR(100),
    user_id INT,
    server_id INT,
    event_type VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
);

INSERT INTO logs (log_type, level, source, user_id, server_id, event_type, description, ip_address, created_at) VALUES
('security', 'warning', 'Firewall', NULL, 1, 'Access Denied', 'محاولة وصول مرفوضة من IP 45.123.67.89', '45.123.67.89', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('system', 'info', 'Backup', NULL, NULL, 'Backup Completed', 'اكتمال النسخ الاحتياطي اليومي', NULL, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
('security', 'error', 'IDS', NULL, 2, 'SQL Injection', 'محاولة حقن SQL مكتشفة', '78.45.123.22', DATE_SUB(NOW(), INTERVAL 40 MINUTE));

-- =============================================
-- 14. جدول الحوادث (incidents)
-- =============================================
CREATE TABLE incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('breach', 'outage', 'attack', 'data_loss', 'compliance') NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    status ENUM('open', 'in-progress', 'resolved', 'closed') DEFAULT 'open',
    impact TEXT,
    affected_servers JSON,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    assigned_to INT,
    created_by INT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO incidents (name, description, type, severity, status, impact, detected_at, assigned_to, created_by) VALUES
('هجوم DDoS', 'هجوم حجب خدمة على خوادم الويب', 'attack', 'critical', 'in-progress', 'تأثير على سرعة المواقع', DATE_SUB(NOW(), INTERVAL 2 HOUR), 1, 1),
('انقطاع خادم التطبيقات', 'خادم التطبيقات 02 توقف عن العمل', 'outage', 'high', 'open', 'خدمة التطبيقات غير متاحة', DATE_SUB(NOW(), INTERVAL 1 HOUR), 2, 3),
('محاولات اختراق متكررة', 'محاولات تخمين كلمات مرور من مصادر متعددة', 'attack', 'high', 'in-progress', 'زيادة محاولات الدخول الفاشلة', DATE_SUB(NOW(), INTERVAL 3 HOUR), 3, 1);

-- =============================================
-- 15. جدول أحداث الشبكة (network_events)
-- =============================================
CREATE TABLE network_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM('inbound', 'outbound') NOT NULL,
    bandwidth_used DECIMAL(10,2),
    protocol VARCHAR(10),
    source_ip VARCHAR(45),
    destination_ip VARCHAR(45),
    source_port INT,
    destination_port INT,
    connection_status ENUM('established', 'closed', 'timeout', 'blocked'),
    server_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
);

INSERT INTO network_events (event_type, bandwidth_used, protocol, source_ip, destination_ip, connection_status, server_id, created_at) VALUES
('inbound', 2.4, 'HTTPS', '45.123.67.89', '192.168.1.100', 'established', 1, NOW()),
('outbound', 1.8, 'HTTPS', '192.168.1.100', '8.8.8.8', 'established', 1, NOW()),
('inbound', 3.2, 'HTTP', '78.45.123.22', '192.168.1.102', 'blocked', 3, DATE_SUB(NOW(), INTERVAL 5 MINUTE));

-- =============================================
-- 16. جدول إحصائيات التقارير (report_statistics)
-- =============================================
CREATE TABLE report_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    total_alerts INT DEFAULT 0,
    total_threats INT DEFAULT 0,
    total_incidents INT DEFAULT 0,
    total_servers INT DEFAULT 0,
    avg_cpu DECIMAL(5,2) DEFAULT 0,
    avg_memory DECIMAL(5,2) DEFAULT 0,
    avg_storage DECIMAL(5,2) DEFAULT 0,
    uptime_percentage DECIMAL(5,2) DEFAULT 0,
    blocked_attacks INT DEFAULT 0,
    critical_events INT DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE
);

INSERT INTO report_statistics (report_id, total_alerts, total_threats, critical_events, uptime_percentage) VALUES
(1, 24, 8, 5, 99.98),
(2, 18, 5, 3, 99.99),
(3, 12, 3, 1, 99.97);

-- =============================================
-- التحقق النهائي
-- =============================================
SELECT '✅ تم إنشاء جميع الجداول بنجاح' as message;
SELECT CONCAT('📊 عدد المستخدمين: ', COUNT(*)) FROM users;
SELECT CONCAT('🖥️ عدد الخوادم: ', COUNT(*)) FROM servers;
SELECT CONCAT('⚠️ عدد التهديدات: ', COUNT(*)) FROM threats;
SELECT CONCAT('📈 عدد التقارير: ', COUNT(*)) FROM daily_reports;
/*
USE security_monitoring_db;

-- =============================================
-- 1. جدول التقارير اليومية المحفوظة
-- =============================================
CREATE TABLE IF NOT EXISTS daily_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    report_type ENUM('security', 'performance', 'network', 'incident', 'compliance') NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    statistics JSON,
    file_path VARCHAR(500),
    file_size INT,
    generated_by INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_daily_report (report_date, report_type),
    INDEX idx_date (report_date),
    INDEX idx_type (report_type)
);

-- =============================================
-- 2. جدول تفاصيل التقارير (الأحداث المرتبطة)
-- =============================================
CREATE TABLE IF NOT EXISTS report_details (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    event_type VARCHAR(50),
    event_id INT,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE,
    INDEX idx_report (report_id)
);

-- =============================================
-- 3. جدول إحصائيات التقارير
-- =============================================
CREATE TABLE IF NOT EXISTS report_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    total_alerts INT DEFAULT 0,
    total_threats INT DEFAULT 0,
    total_incidents INT DEFAULT 0,
    total_servers INT DEFAULT 0,
    avg_cpu DECIMAL(5,2) DEFAULT 0,
    avg_memory DECIMAL(5,2) DEFAULT 0,
    avg_storage DECIMAL(5,2) DEFAULT 0,
    uptime_percentage DECIMAL(5,2) DEFAULT 0,
    blocked_attacks INT DEFAULT 0,
    critical_events INT DEFAULT 0,
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE
);

-- =============================================
-- 4. إضافة بعض البيانات التجريبية للتقارير السابقة
-- =============================================
INSERT INTO daily_reports (report_date, report_type, title, summary, statistics, generated_by, status) VALUES
(CURDATE(), 'security', 'تقرير الأمان اليومي', 'تقرير شامل لأحداث الأمان لليوم', 
 '{"total_alerts":24,"critical":5,"warning":12,"info":8,"resolved":22,"threats_detected":8,"active_threats":3}', 1, 'published'),

(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'security', 'تقرير الأمان ليوم أمس', 
 'تقرير أحداث الأمان ليوم أمس', 
 '{"total_alerts":18,"critical":3,"warning":9,"info":6,"resolved":16,"threats_detected":5,"active_threats":2}', 1, 'published'),

(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'performance', 'تقرير أداء الخوادم', 
 'تحليل أداء جميع الخوادم', 
 '{"avg_cpu":48.5,"avg_memory":52.3,"avg_storage":46.2,"uptime":99.98,"servers_online":7,"servers_warning":1}', 2, 'published'),

(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'network', 'تقرير حركة الشبكة', 
 'تحليل حركة الشبكة والاتصالات', 
 '{"total_inbound":"16.8 Gbps","total_outbound":"12.6 Gbps","peak_traffic":"2.4 Gbps","avg_latency":15.3,"connections":15420}', 3, 'published');

-- إضافة الإحصائيات المرتبطة
INSERT INTO report_statistics (report_id, total_alerts, total_threats, total_incidents, total_servers, avg_cpu, avg_memory, avg_storage, uptime_percentage, blocked_attacks, critical_events)
VALUES 
(1, 24, 8, 2, 8, 48.5, 52.3, 46.2, 99.98, 156, 5),
(2, 18, 5, 1, 8, 45.2, 50.1, 44.8, 99.99, 98, 3),
(3, 12, 3, 0, 8, 52.7, 55.4, 48.9, 99.97, 67, 1),
(4, 8, 2, 0, 8, 38.4, 42.6, 40.2, 100.00, 43, 0);

-- =============================================
-- 5. التحقق
-- =============================================
SELECT '✅ تم إنشاء جداول التقارير اليومية بنجاح' as message;
SELECT COUNT(*) as reports_count FROM daily_reports;
SELECT * FROM daily_reports ORDER BY report_date DESC;
/*
USE security_monitoring_db;

-- =============================================
-- 1. التأكد من وجود المستخدمين
-- =============================================
INSERT IGNORE INTO users (id, username, email, password_hash, full_name, role, department) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', 'hash123', 'أحمد العلي', 'manager', 'وحدة الحماية'),
(2, 'sara.mohammed', 'sara.m@company.com', 'hash123', 'سارة محمد', 'analyst', 'وحدة الحماية'),
(3, 'khaled.omar', 'khaled.o@company.com', 'hash123', 'خالد عمر', 'analyst', 'وحدة الحماية'),
(4, 'nora.ahmed', 'nora.a@company.com', 'hash123', 'نورا أحمد', 'analyst', 'وحدة الحماية'),
(5, 'fahad.saud', 'fahad.s@company.com', 'hash123', 'فهد سعود', 'admin', 'الإدارة العليا');

-- =============================================
-- 2. إنشاء جدول السياسات الأمنية
-- =============================================
CREATE TABLE IF NOT EXISTS security_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('password', 'access', 'backup', 'encryption', 'network', 'compliance') NOT NULL,
    priority ENUM('high', 'medium', 'low') NOT NULL,
    scope VARCHAR(255),
    compliance_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('active', 'draft', 'archived') DEFAULT 'active',
    version VARCHAR(20),
    content TEXT,
    created_by INT,
    approved_by INT,
    effective_date DATE,
    review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 3. إضافة بيانات السياسات
-- =============================================
INSERT IGNORE INTO security_policies (name, description, category, priority, scope, compliance_percentage, status, version, content, created_by, effective_date, review_date) VALUES
('سياسة كلمات المرور', 'تحدد متطلبات إنشاء كلمات المرور وتغييرها', 'password', 'high', 'all', 98.5, 'active', 'v2.1', 'كلمات المرور يجب أن تكون 8 أحرف على الأقل، تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
('سياسة الوصول', 'تحدد صلاحيات الوصول للأنظمة المختلفة', 'access', 'high', 'all', 95.2, 'active', 'v3.0', 'صلاحيات الوصول تعتمد على مبدأ least privilege، مراجعة دورية كل 3 أشهر', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH)),
('سياسة النسخ الاحتياطي', 'تحدد جدول وإجراءات النسخ الاحتياطي', 'backup', 'high', 'servers', 85.0, 'active', 'v1.5', 'نسخ احتياطي يومي كامل، نسخ أسبوعي لأرشفة، اختبار استعادة شهري', 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH)),
('سياسة التشفير', 'تحدد معايير التشفير للبيانات', 'encryption', 'high', 'databases', 75.5, 'active', 'v2.0', 'تشفير AES-256 للبيانات المخزنة، TLS 1.3 للبيانات المنقولة', 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 4 MONTH)),
('سياسة أمان الشبكة', 'تحدد تكوينات جدار الحماية وأمان الشبكة', 'network', 'high', 'network', 92.0, 'active', 'v3.2', 'منع جميع المنافذ غير الضرورية، مراقبة حركة الشبكة 24/7', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 MONTH)),
('سياسة الامتثال', 'تتوافق مع معايير ISO 27001', 'compliance', 'high', 'all', 88.0, 'active', 'v1.0', 'تطبيق متطلبات ISO 27001 لأمن المعلومات', 4, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 8 MONTH)),
('سياسة الأجهزة المحمولة', 'استخدام الأجهزة المحمولة للوصول للأنظمة', 'access', 'medium', 'mobile', 90.0, 'draft', 'v0.9', 'تتطلب تطبيق إدارة الأجهزة المحمولة MDM', 2, NULL, NULL),
('سياسة الاحتفاظ بالسجلات', 'مدة الاحتفاظ بسجلات النظام', 'compliance', 'medium', 'logs', 95.0, 'active', 'v2.3', 'الاحتفاظ بسجلات الأمان لمدة سنتين، سجلات النظام لسنة واحدة', 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH));

-- =============================================
-- 4. إنشاء جدول العملاء إذا لم يكن موجوداً
-- =============================================
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    website VARCHAR(255),
    package_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO clients (company_name, contact_name, email, phone, website, package_type) VALUES
('شركة التقنية المتطورة', 'محمد العمري', 'info@tech-sa.com', '0555123456', 'tech-sa.com', 'enterprise'),
('مؤسسة الأمان الرقمي', 'أحمد الجهني', 'contact@digital-security.com', '0555234567', 'digital-security.com', 'professional'),
('متجر الإلكترونيات', 'سارة القحطاني', 'support@electronics-store.com', '0555345678', 'electronics-store.com', 'basic');

-- =============================================
-- 5. إنشاء جدول مواقع العملاء
-- =============================================
CREATE TABLE IF NOT EXISTS client_websites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    server_id INT,
    status ENUM('active', 'suspended', 'maintenance') DEFAULT 'active',
    disk_usage BIGINT DEFAULT 0,
    bandwidth_usage BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_domain (domain)
);

INSERT IGNORE INTO client_websites (client_id, domain, server_id, disk_usage, bandwidth_usage) VALUES
(1, 'tech-sa.com', 1, 1500000000, 5000000000),
(1, 'api.tech-sa.com', 3, 500000000, 2000000000),
(2, 'digital-security.com', 1, 800000000, 3000000000),
(3, 'electronics-store.com', 5, 2000000000, 8000000000);

-- =============================================
-- 6. إنشاء جدول تقارير العملاء
-- =============================================
CREATE TABLE IF NOT EXISTS client_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    website_id INT,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    report_date DATE NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    statistics JSON,
    status ENUM('generated', 'sent', 'failed') DEFAULT 'generated',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES client_websites(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 7. التحقق النهائي
-- =============================================
SELECT '✅ تم إنشاء جميع الجداول بنجاح' as message;
SELECT '📊 السياسات الأمنية:' as section, COUNT(*) as count FROM security_policies;
SELECT '👥 العملاء:' as section, COUNT(*) as count FROM clients;
SELECT '🌐 مواقع العملاء:' as section, COUNT(*) as count FROM client_websites;

/*
USE security_monitoring_db;

-- =============================================
-- 1. جدول العملاء (clients)
-- =============================================
CREATE TABLE IF NOT EXISTS clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    website VARCHAR(255),
    package_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 2. مواقع العملاء (client_websites)
-- =============================================
CREATE TABLE IF NOT EXISTS client_websites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    server_id INT,
    status ENUM('active', 'suspended', 'maintenance') DEFAULT 'active',
    disk_usage BIGINT DEFAULT 0,
    bandwidth_usage BIGINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_domain (domain)
);

-- =============================================
-- 3. تقارير العملاء (client_reports)
-- =============================================
CREATE TABLE IF NOT EXISTS client_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    website_id INT,
    report_type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    report_date DATE NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    statistics JSON,
    status ENUM('generated', 'sent', 'failed') DEFAULT 'generated',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES client_websites(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client_date (client_id, report_date),
    INDEX idx_website (website_id)
);

-- =============================================
-- 4. إضافة عمود client_id للمستخدمين (اختياري)
-- =============================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS client_id INT NULL AFTER department,
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;

-- =============================================
-- 5. بيانات تجريبية للعملاء
-- =============================================
INSERT INTO clients (company_name, contact_name, email, phone, website, package_type) VALUES
('شركة التقنية المتطورة', 'محمد العمري', 'info@tech-sa.com', '0555123456', 'tech-sa.com', 'enterprise'),
('مؤسسة الأمان الرقمي', 'أحمد الجهني', 'contact@digital-security.com', '0555234567', 'digital-security.com', 'professional'),
('متجر الإلكترونيات', 'سارة القحطاني', 'support@electronics-store.com', '0555345678', 'electronics-store.com', 'basic');

INSERT INTO client_websites (client_id, domain, server_id, disk_usage, bandwidth_usage) VALUES
(1, 'tech-sa.com', 1, 1500000000, 5000000000),
(1, 'api.tech-sa.com', 3, 500000000, 2000000000),
(2, 'digital-security.com', 1, 800000000, 3000000000),
(3, 'electronics-store.com', 5, 2000000000, 8000000000),
(3, 'admin.electronics-store.com', 3, 300000000, 1000000000);

-- ربط المستخدمين بالعملاء
UPDATE users SET client_id = 1 WHERE id = 2; -- سارة تتبع العميل 1
UPDATE users SET client_id = 2 WHERE id = 3; -- خالد تبع العميل 2
UPDATE users SET client_id = 3 WHERE id = 4; -- نورا تبع العميل 3

/*
-- =============================================
-- ملف قاعدة البيانات الكامل - security_monitoring_db
-- =============================================

-- يمكنك تشغيل هذا الملف كاملاً في phpMyAdmin
-- وسينشئ جميع الجداول المطلوبة مع البيانات التجريبية

-- =============================================
-- 1. المستخدمين (users)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','analyst','viewer') NOT NULL,
    department VARCHAR(100),
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO users (id, username, email, password_hash, full_name, role, department) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', 'hash123', 'أحمد العلي', 'manager', 'وحدة الحماية'),
(2, 'sara.mohammed', 'sara.m@company.com', 'hash123', 'سارة محمد', 'analyst', 'وحدة الحماية'),
(3, 'khaled.omar', 'khaled.o@company.com', 'hash123', 'خالد عمر', 'analyst', 'وحدة الحماية'),
(4, 'nora.ahmed', 'nora.a@company.com', 'hash123', 'نورا أحمد', 'analyst', 'وحدة الحماية'),
(5, 'fahad.saud', 'fahad.s@company.com', 'hash123', 'فهد سعود', 'admin', 'الإدارة العليا');

-- =============================================
-- 2. الخوادم (servers)
-- =============================================
CREATE TABLE IF NOT EXISTS servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('web','database','application','cache','loadbalancer') NOT NULL,
    status ENUM('online','warning','offline','maintenance') DEFAULT 'online',
    ip_address VARCHAR(45) NOT NULL,
    location VARCHAR(100),
    cpu_usage DECIMAL(5,2) DEFAULT 0,
    memory_usage DECIMAL(5,2) DEFAULT 0,
    storage_usage DECIMAL(5,2) DEFAULT 0,
    uptime INT DEFAULT 0,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO servers (id, name, type, status, ip_address, location, cpu_usage, memory_usage, storage_usage, uptime) VALUES
(1, 'Web-Server-01', 'web', 'online', '192.168.1.100', 'الرياض', 65.5, 45.2, 32.8, 518400),
(2, 'DB-Server-02', 'database', 'warning', '192.168.1.101', 'الرياض', 85.3, 78.6, 92.4, 432000),
(3, 'App-Server-03', 'application', 'online', '192.168.1.102', 'جدة', 45.8, 60.2, 40.1, 604800),
(4, 'Cache-Server-01', 'cache', 'online', '192.168.1.103', 'الرياض', 72.4, 55.3, 28.5, 345600),
(5, 'Web-Server-02', 'web', 'online', '192.168.1.104', 'جدة', 38.2, 42.8, 35.6, 259200),
(6, 'DB-Server-01', 'database', 'online', '192.168.1.105', 'الرياض', 52.7, 48.9, 55.3, 691200),
(7, 'LoadBalancer-01', 'loadbalancer', 'online', '192.168.1.106', 'الرياض', 25.4, 35.2, 22.1, 777600),
(8, 'App-Server-02', 'application', 'offline', '192.168.1.107', 'الرياض', 0, 0, 65.8, 0);

-- =============================================
-- 3. التنبيهات (alerts)
-- =============================================
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('critical','warning','info') NOT NULL,
    severity ENUM('high','medium','low') NOT NULL,
    source VARCHAR(100),
    server_id INT,
    status ENUM('new','acknowledged','in-progress','resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    acknowledged_by INT,
    resolved_by INT,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO alerts (title, description, type, severity, source, server_id, status, created_at) VALUES
('ارتفاع استخدام المعالج', 'استخدام المعالج تجاوز 85%', 'critical', 'high', 'DB-Server-02', 2, 'new', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
('محاولة وصول غير مصرح', '5 محاولات فاشلة من IP 45.76.89.123', 'warning', 'medium', 'Web-Server-01', 1, 'acknowledged', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('مساحة تخزين منخفضة', 'المساحة أقل من 10%', 'critical', 'high', 'DB-Server-02', 2, 'in-progress', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('حركة شبكة غير طبيعية', 'زيادة 200% في حركة الشبكة', 'warning', 'medium', 'Network', NULL, 'new', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('انقطاع خدمة', 'خادم التطبيقات 02 غير مستجيب', 'critical', 'high', 'App-Server-02', 8, 'new', DATE_SUB(NOW(), INTERVAL 55 MINUTE));

-- =============================================
-- 4. قواعد التنبيهات (alert_rules)
-- =============================================
CREATE TABLE IF NOT EXISTS alert_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    condition_type ENUM('cpu', 'memory', 'storage', 'login_attempts', 'network') NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    comparison_operator ENUM('>', '<', '>=', '<=', '=') NOT NULL,
    severity ENUM('critical', 'warning', 'info') NOT NULL,
    target_server_type VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO alert_rules (name, description, condition_type, threshold_value, comparison_operator, severity, target_server_type, created_by) VALUES
('ارتفاع CPU - خادم قاعدة بيانات', 'تنبيه عند ارتفاع استخدام المعالج فوق 85%', 'cpu', 85.00, '>', 'critical', 'database', 1),
('استخدام ذاكرة عالي', 'تنبيه عند استخدام الذاكرة فوق 90%', 'memory', 90.00, '>', 'critical', NULL, 1),
('مساحة تخزين منخفضة', 'تنبيه عند انخفاض المساحة تحت 15%', 'storage', 15.00, '<', 'warning', NULL, 2),
('محاولات دخول فاشلة', 'تنبيه عند 5 محاولات دخول فاشلة', 'login_attempts', 5.00, '>=', 'warning', NULL, 2);

-- =============================================
-- 5. التقارير (reports) - الجدول الجديد
-- =============================================
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('security', 'performance', 'network', 'incident', 'compliance') NOT NULL,
    period ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    file_path TEXT,
    summary TEXT,
    statistics JSON,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO reports (name, type, period, start_date, end_date, generated_at, generated_by, summary, statistics, status) VALUES
('تقرير الأمان اليومي', 'security', 'daily', CURDATE(), CURDATE(), NOW(), 1, 'تقرير أمان شامل لليوم', '{"total_alerts":24,"critical":5,"warning":12,"info":8,"resolved":22,"threats_detected":8,"active_threats":3}', 'published'),
('تقرير أداء الخوادم', 'performance', 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NOW(), 2, 'تحليل أداء الخوادم للأسبوع', '{"avg_cpu":48.5,"avg_memory":52.3,"avg_storage":46.2,"uptime":99.98,"servers_online":7,"servers_warning":1}', 'published'),
('تقرير حركة الشبكة', 'network', 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NOW(), 3, 'تحليل حركة الشبكة الأسبوعية', '{"total_inbound":"16.8 Gbps","total_outbound":"12.6 Gbps","peak_traffic":"2.4 Gbps","avg_latency":15.3}', 'published'),
('تقرير الحوادث الشهري', 'incident', 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), NOW(), 1, 'ملخص حوادث الأمان', '{"total_incidents":8,"open":3,"in_progress":2,"resolved":3,"critical":2,"high":3}', 'published');

-- =============================================
-- 6. إحصائيات الأمان (security_statistics)
-- =============================================
CREATE TABLE IF NOT EXISTS security_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL,
    stat_hour TINYINT,
    total_attacks INT DEFAULT 0,
    blocked_attacks INT DEFAULT 0,
    ddos_attacks INT DEFAULT 0,
    brute_force_attacks INT DEFAULT 0,
    sql_injection_attacks INT DEFAULT 0,
    xss_attacks INT DEFAULT 0,
    malware_attacks INT DEFAULT 0,
    phishing_attacks INT DEFAULT 0,
    avg_response_time DECIMAL(10,2),
    total_alerts INT DEFAULT 0,
    critical_alerts INT DEFAULT 0,
    warning_alerts INT DEFAULT 0,
    info_alerts INT DEFAULT 0,
    active_threats INT DEFAULT 0,
    mitigated_threats INT DEFAULT 0,
    system_uptime DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_hour (stat_date, stat_hour)
);

INSERT IGNORE INTO security_statistics (stat_date, stat_hour, total_attacks, blocked_attacks, avg_response_time, system_uptime) VALUES
(CURDATE(), 0, 45, 43, 1.2, 99.99),
(CURDATE(), 1, 32, 31, 1.1, 99.99),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 0, 52, 49, 1.5, 99.98),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 0, 68, 64, 1.8, 99.97);

-- =============================================
-- 7. التحقق النهائي
-- =============================================
SELECT '✅ تم إنشاء جميع الجداول بنجاح' as message;
SELECT '📊 التقارير:' as section, COUNT(*) as count FROM reports;
SELECT '📈 الإحصائيات:' as section, COUNT(*) as count FROM security_statistics;
/*
USE security_monitoring_db;

-- =============================================
-- إنشاء جدول التقارير (reports)
-- =============================================
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('security', 'performance', 'network', 'incident', 'compliance') NOT NULL,
    period ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    file_path TEXT,
    summary TEXT,
    statistics JSON,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_period (period),
    INDEX idx_dates (start_date, end_date)
);

-- =============================================
-- إضافة بيانات تجريبية للتقارير
-- =============================================
INSERT INTO reports (name, type, period, start_date, end_date, generated_at, generated_by, summary, statistics, status) VALUES
('تقرير الأمان اليومي', 'security', 'daily', CURDATE(), CURDATE(), NOW(), 1, 'تقرير أمان شامل لليوم', '{"total_alerts":24,"critical":5,"warning":12,"info":8,"resolved":22,"threats_detected":8,"active_threats":3}', 'published'),
('تقرير أداء الخوادم', 'performance', 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NOW(), 2, 'تحليل أداء الخوادم للأسبوع', '{"avg_cpu":48.5,"avg_memory":52.3,"avg_storage":46.2,"uptime":99.98,"servers_online":7,"servers_warning":1}', 'published'),
('تقرير حركة الشبكة', 'network', 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NOW(), 3, 'تحليل حركة الشبكة الأسبوعية', '{"total_inbound":"16.8 Gbps","total_outbound":"12.6 Gbps","peak_traffic":"2.4 Gbps","avg_latency":15.3}', 'published'),
('تقرير الحوادث الشهري', 'incident', 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), LAST_DAY(CURDATE()), NOW(), 1, 'ملخص حوادث الأمان لشهر الحالي', '{"total_incidents":8,"open":3,"in_progress":2,"resolved":3,"critical":2,"high":3}', 'published'),
('تقرير الامتثال ربع السنوي', 'compliance', 'quarterly', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), CURDATE(), NOW(), 4, 'تقييم الامتثال للمعايير', '{"overall_compliance":89.5,"password_policy":98.5,"access_policy":95.2,"backup_policy":85.0,"encryption_policy":75.5}', 'draft'),
('تقرير التهديدات اليومي', 'security', 'daily', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 2, 'تحليل التهديدات لليوم السابق', '{"total_threats":12,"ddos":2,"brute_force":6,"sql_injection":1,"xss":2,"malware":1,"blocked":10}', 'published'),
('تقرير أداء قاعدة البيانات', 'performance', 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), NOW(), 3, 'تحليل أداء قواعد البيانات', '{"avg_query_time":0.85,"slow_queries":23,"connections":156,"storage_used":"2.4 TB"}', 'published'),
('تقرير أمان التطبيقات', 'security', 'monthly', DATE_FORMAT(CURDATE(), '%Y-%m-01'), CURDATE(), NOW(), 1, 'تقييم أمان التطبيقات', '{"vulnerabilities_found":5,"critical":0,"high":2,"medium":2,"low":1,"patched":3}', 'published');



-- =============================================
-- إنشاء جدول إحصائيات الأمان (إذا لم يكن موجوداً)
-- =============================================
CREATE TABLE IF NOT EXISTS security_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL,
    stat_hour TINYINT,
    total_attacks INT DEFAULT 0,
    blocked_attacks INT DEFAULT 0,
    ddos_attacks INT DEFAULT 0,
    brute_force_attacks INT DEFAULT 0,
    sql_injection_attacks INT DEFAULT 0,
    xss_attacks INT DEFAULT 0,
    malware_attacks INT DEFAULT 0,
    phishing_attacks INT DEFAULT 0,
    avg_response_time DECIMAL(10,2),
    total_alerts INT DEFAULT 0,
    critical_alerts INT DEFAULT 0,
    warning_alerts INT DEFAULT 0,
    info_alerts INT DEFAULT 0,
    active_threats INT DEFAULT 0,
    mitigated_threats INT DEFAULT 0,
    system_uptime DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_hour (stat_date, stat_hour),
    INDEX idx_date (stat_date)
);

-- إضافة بيانات تجريبية لآخر 7 أيام
INSERT INTO security_statistics (stat_date, stat_hour, total_attacks, blocked_attacks, avg_response_time, system_uptime) VALUES
(CURDATE(), 0, 45, 43, 1.2, 99.99),
(CURDATE(), 1, 32, 31, 1.1, 99.99),
(CURDATE(), 2, 28, 27, 1.0, 100.00),
(CURDATE(), 3, 22, 22, 0.9, 100.00),
(CURDATE(), 4, 18, 18, 0.8, 100.00),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 0, 52, 49, 1.5, 99.98),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 0, 68, 64, 1.8, 99.97),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 0, 75, 70, 2.0, 99.96),
(DATE_SUB(CURDATE(), INTERVAL 4 DAY), 0, 82, 76, 2.1, 99.98),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 0, 95, 88, 2.3, 99.95),
(DATE_SUB(CURDATE(), INTERVAL 6 DAY), 0, 88, 82, 2.2, 99.97);

-- =============================================
-- ملف قاعدة البيانات الكامل - security_monitoring_db
-- =============================================

DROP DATABASE IF EXISTS security_monitoring_db;
CREATE DATABASE security_monitoring_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE security_monitoring_db;

-- ==================== المستخدمين ====================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','analyst','viewer') NOT NULL,
    department VARCHAR(100),
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (id, username, email, password_hash, full_name, role, department) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', 'hash123', 'أحمد العلي', 'manager', 'وحدة الحماية'),
(2, 'sara.mohammed', 'sara.m@company.com', 'hash123', 'سارة محمد', 'analyst', 'وحدة الحماية'),
(3, 'khaled.omar', 'khaled.o@company.com', 'hash123', 'خالد عمر', 'analyst', 'وحدة الحماية'),
(4, 'nora.ahmed', 'nora.a@company.com', 'hash123', 'نورا أحمد', 'analyst', 'وحدة الحماية'),
(5, 'fahad.saud', 'fahad.s@company.com', 'hash123', 'فهد سعود', 'admin', 'الإدارة العليا');

-- ==================== الخوادم ====================
CREATE TABLE servers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('web','database','application','cache','loadbalancer') NOT NULL,
    status ENUM('online','warning','offline','maintenance') DEFAULT 'online',
    ip_address VARCHAR(45) NOT NULL,
    location VARCHAR(100),
    cpu_usage DECIMAL(5,2) DEFAULT 0,
    memory_usage DECIMAL(5,2) DEFAULT 0,
    storage_usage DECIMAL(5,2) DEFAULT 0,
    uptime INT DEFAULT 0,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO servers (id, name, type, status, ip_address, location, cpu_usage, memory_usage, storage_usage, uptime) VALUES
(1, 'Web-Server-01', 'web', 'online', '192.168.1.100', 'الرياض', 65.5, 45.2, 32.8, 518400),
(2, 'DB-Server-02', 'database', 'warning', '192.168.1.101', 'الرياض', 85.3, 78.6, 92.4, 432000),
(3, 'App-Server-03', 'application', 'online', '192.168.1.102', 'جدة', 45.8, 60.2, 40.1, 604800),
(4, 'Cache-Server-01', 'cache', 'online', '192.168.1.103', 'الرياض', 72.4, 55.3, 28.5, 345600),
(5, 'Web-Server-02', 'web', 'online', '192.168.1.104', 'جدة', 38.2, 42.8, 35.6, 259200),
(6, 'DB-Server-01', 'database', 'online', '192.168.1.105', 'الرياض', 52.7, 48.9, 55.3, 691200),
(7, 'LoadBalancer-01', 'loadbalancer', 'online', '192.168.1.106', 'الرياض', 25.4, 35.2, 22.1, 777600),
(8, 'App-Server-02', 'application', 'offline', '192.168.1.107', 'الرياض', 0, 0, 65.8, 0);

-- ==================== التنبيهات ====================
CREATE TABLE alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('critical','warning','info') NOT NULL,
    severity ENUM('high','medium','low') NOT NULL,
    source VARCHAR(100),
    server_id INT,
    status ENUM('new','acknowledged','in-progress','resolved') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    acknowledged_by INT,
    resolved_by INT,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO alerts (title, description, type, severity, source, server_id, status, created_at) VALUES
('ارتفاع استخدام المعالج', 'استخدام المعالج تجاوز 85%', 'critical', 'high', 'DB-Server-02', 2, 'new', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
('محاولة وصول غير مصرح', '5 محاولات فاشلة من IP 45.76.89.123', 'warning', 'medium', 'Web-Server-01', 1, 'acknowledged', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('مساحة تخزين منخفضة', 'المساحة أقل من 10%', 'critical', 'high', 'DB-Server-02', 2, 'in-progress', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('حركة شبكة غير طبيعية', 'زيادة 200% في حركة الشبكة', 'warning', 'medium', 'Network', NULL, 'new', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('انقطاع خدمة', 'خادم التطبيقات 02 غير مستجيب', 'critical', 'high', 'App-Server-02', 8, 'new', DATE_SUB(NOW(), INTERVAL 55 MINUTE)),
('تحديث أمني متوفر', 'يتوفر تحديث أمني جديد', 'info', 'low', 'System', NULL, 'resolved', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('شهادة SSL تنتهي', 'الشهادة ستنتهي خلال 7 أيام', 'warning', 'medium', 'SSL Monitor', 1, 'acknowledged', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ==================== قواعد التنبيهات ====================
CREATE TABLE alert_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    condition_type ENUM('cpu', 'memory', 'storage', 'login_attempts', 'network') NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    comparison_operator ENUM('>', '<', '>=', '<=', '=') NOT NULL,
    severity ENUM('critical', 'warning', 'info') NOT NULL,
    target_server_type VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO alert_rules (name, description, condition_type, threshold_value, comparison_operator, severity, target_server_type, created_by) VALUES
('ارتفاع CPU - خادم قاعدة بيانات', 'تنبيه عند ارتفاع استخدام المعالج فوق 85%', 'cpu', 85.00, '>', 'critical', 'database', 1),
('استخدام ذاكرة عالي', 'تنبيه عند استخدام الذاكرة فوق 90%', 'memory', 90.00, '>', 'critical', NULL, 1),
('مساحة تخزين منخفضة', 'تنبيه عند انخفاض المساحة تحت 15%', 'storage', 15.00, '<', 'warning', NULL, 2),
('محاولات دخول فاشلة', 'تنبيه عند 5 محاولات دخول فاشلة', 'login_attempts', 5.00, '>=', 'warning', NULL, 2),
('حركة شبكة غير طبيعية', 'تنبيه عند زيادة حركة الشبكة 200%', 'network', 200.00, '>', 'warning', NULL, 1),
('CPU - خادم ويب', 'تنبيه عند استخدام المعالج فوق 80%', 'cpu', 80.00, '>', 'warning', 'web', 2);

-- ==================== التهديدات ====================
CREATE TABLE threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('ddos','brute_force','sql_injection','xss','malware','phishing') NOT NULL,
    source_ip VARCHAR(45),
    target_server_id INT,
    target_url TEXT,
    severity ENUM('critical','high','medium','low') NOT NULL,
    status ENUM('active','mitigated','blocked','investigating') DEFAULT 'active',
    description TEXT,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP,
    FOREIGN KEY (target_server_id) REFERENCES servers(id) ON DELETE SET NULL
);

INSERT INTO threats (name, type, source_ip, target_server_id, target_url, severity, status, description, first_seen, last_seen) VALUES
('هجوم DDoS', 'ddos', '45.123.67.89', 1, 'https://example.com', 'critical', 'active', 'هجوم حجب خدمة', DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW()),
('محاولات تخمين', 'brute_force', '103.21.244.0', 5, 'https://example.com/login', 'high', 'active', 'محاولات متكررة', DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW()),
('حقن SQL', 'sql_injection', '78.45.123.22', 3, 'https://example.com/products', 'critical', 'mitigated', 'محاولة حقن SQL', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- ==================== السجلات ====================
CREATE TABLE logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    log_type ENUM('security','system','network','application') NOT NULL,
    level ENUM('error','warning','info','debug') NOT NULL,
    source VARCHAR(100),
    user_id INT,
    server_id INT,
    event_type VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
);

INSERT INTO logs (log_type, level, source, user_id, server_id, event_type, description, ip_address, created_at) VALUES
('security', 'warning', 'Firewall', NULL, 1, 'Access Denied', 'محاولة وصول مرفوضة', '45.76.89.123', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('system', 'info', 'Backup', NULL, NULL, 'Backup Completed', 'اكتمال النسخ الاحتياطي', NULL, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
('security', 'error', 'IDS', NULL, 2, 'SQL Injection', 'محاولة حقن SQL', '78.45.123.22', DATE_SUB(NOW(), INTERVAL 40 MINUTE));

-- ==================== الحوادث ====================
CREATE TABLE incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('breach','outage','attack','data_loss','compliance') NOT NULL,
    severity ENUM('critical','high','medium','low') NOT NULL,
    status ENUM('open','in-progress','resolved','closed') DEFAULT 'open',
    impact TEXT,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    assigned_to INT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO incidents (name, description, type, severity, status, impact, detected_at, assigned_to) VALUES
('هجوم DDoS', 'هجوم حجب خدمة', 'attack', 'critical', 'in-progress', 'تأثير 40%', DATE_SUB(NOW(), INTERVAL 2 HOUR), 1),
('انقطاع خادم', 'خادم التطبيقات 02 توقف', 'outage', 'high', 'open', 'خدمة غير متاحة', DATE_SUB(NOW(), INTERVAL 1 HOUR), 2);

-- ==================== أحداث الشبكة ====================
CREATE TABLE network_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_type ENUM('inbound','outbound') NOT NULL,
    bandwidth_used DECIMAL(10,2),
    protocol VARCHAR(10),
    source_ip VARCHAR(45),
    destination_ip VARCHAR(45),
    connection_status ENUM('established','closed','timeout','blocked'),
    server_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL
);

INSERT INTO network_events (event_type, bandwidth_used, protocol, source_ip, destination_ip, connection_status, server_id, created_at) VALUES
('inbound', 2.4, 'HTTPS', '45.123.67.89', '192.168.1.100', 'established', 1, NOW()),
('outbound', 1.8, 'HTTPS', '192.168.1.100', '8.8.8.8', 'established', 1, NOW()),
('inbound', 3.2, 'HTTP', '78.45.123.22', '192.168.1.102', 'blocked', 3, DATE_SUB(NOW(), INTERVAL 5 MINUTE));

-- ==================== التحقق ====================
SELECT '✅ تم إنشاء قاعدة البيانات بنجاح' as message;
SELECT CONCAT('📊 عدد المستخدمين: ', COUNT(*)) FROM users;
SELECT CONCAT('🖥️ عدد الخوادم: ', COUNT(*)) FROM servers;
SELECT CONCAT('⚠️ عدد التنبيهات: ', COUNT(*)) FROM alerts;