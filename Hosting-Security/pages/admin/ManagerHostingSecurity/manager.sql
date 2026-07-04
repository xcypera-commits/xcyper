

USE security_monitoring_db;

-- =============================================
-- 1. إنشاء جدول التدقيقات (system_audits)
-- =============================================
CREATE TABLE IF NOT EXISTS system_audits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    scope VARCHAR(255),
    status ENUM('scheduled', 'in-progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    auditor_id INT,
    created_by INT,
    scheduled_date DATE,
    completed_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_date)
);

-- =============================================
-- 2. إنشاء جدول نتائج التدقيق (audit_findings)
-- =============================================
CREATE TABLE IF NOT EXISTS audit_findings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    audit_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('security', 'performance', 'compliance', 'documentation', 'access', 'configuration', 'other') NOT NULL,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    status ENUM('open', 'in-progress', 'resolved') DEFAULT 'open',
    assigned_to INT,
    created_by INT,
    detected_date DATE,
    resolved_date DATE NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES system_audits(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    INDEX idx_category (category)
);

-- =============================================
-- 3. إضافة بيانات تجريبية للتدقيقات
-- =============================================
INSERT INTO system_audits (code, name, description, scope, status, auditor_id, created_by, scheduled_date) VALUES
('AUD-2024-001', 'تدقيق أمن المعلومات الربعي', 'تدقيق شامل لأنظمة أمن المعلومات', 'جميع الأنظمة', 'completed', 1, 1, '2024-01-15'),
('AUD-2024-002', 'تدقيق أداء النظام', 'مراجعة أداء الخوادم وقواعد البيانات', 'البنية التحتية', 'in-progress', 2, 1, '2024-02-01'),
('AUD-2024-003', 'تدقيق الامتثال للمعايير', 'التحقق من الامتثال لمعايير ISO 27001', 'جميع الأنظمة', 'scheduled', 3, 1, '2024-03-10'),
('AUD-2024-004', 'تدقيق صلاحيات الوصول', 'مراجعة صلاحيات المستخدمين', 'نظام المستخدمين', 'scheduled', 1, 1, '2024-03-15'),
('AUD-2024-005', 'تدقيق التوثيق الفني', 'مراجعة توثيق الأنظمة والإجراءات', 'وثائق النظام', 'in-progress', 4, 1, '2024-02-20');

-- =============================================
-- 4. إضافة بيانات تجريبية للنتائج
-- =============================================
INSERT INTO audit_findings (audit_id, title, description, category, severity, status, assigned_to, created_by, detected_date) VALUES
(1, 'كلمات مرور ضعيفة', 'تم اكتشاف 12 حساب بكلمات مرور ضعيفة', 'security', 'high', 'resolved', 3, 1, '2024-01-16'),
(1, 'نقص في التوثيق', 'إجراءات الأمان غير موثقة بشكل كامل', 'documentation', 'medium', 'resolved', 2, 1, '2024-01-16'),
(2, 'ارتفاع استخدام المعالج', 'خادم قاعدة البيانات يعمل باستمرار عند 85%', 'performance', 'high', 'in-progress', 3, 1, '2024-02-02'),
(2, 'مساحة تخزين منخفضة', 'مساحة التخزين أقل من 15%', 'performance', 'medium', 'open', 3, 1, '2024-02-02'),
(3, 'عدم تحديث السياسات', 'سياسات الأمن لم تراجع منذ 6 أشهر', 'compliance', 'medium', 'open', 2, 1, '2024-02-15'),
(5, 'توثيق غير مكتمل', 'بعض إجراءات التشغيل غير موثقة', 'documentation', 'low', 'open', 4, 1, '2024-02-21');

-- =============================================
-- 5. التحقق من الإضافة
-- =============================================
SELECT '✅ تم إنشاء جداول التدقيق بنجاح' as message;
SELECT CONCAT('📊 عدد التدقيقات: ', COUNT(*)) FROM system_audits;
SELECT CONCAT('📋 عدد النتائج: ', COUNT(*)) FROM audit_findings;
/*
-- =============================================
-- إنشاء جدول المشاريع (projects)
-- =============================================
USE security_monitoring_db;
/*
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_code VARCHAR(50) UNIQUE NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255),
    unit_id INT,
    status ENUM('documentation', 'testing', 'deployment', 'completed', 'delayed', 'archived') DEFAULT 'documentation',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    progress INT DEFAULT 0,
    start_date DATE,
    deadline DATE,
    manager_id INT,
    budget DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- إضافة بيانات تجريبية حقيقية
-- =============================================
INSERT INTO projects (project_code, project_name, client_name, unit_id, status, priority, progress, deadline, manager_id, budget) VALUES
('P-1019', 'بنك الأهلي - ترقية الأمان', 'بنك الأهلي', 4, 'testing', 'critical', 45, '2024-02-01', 4, 250000),
('P-1023', 'وزارة الصحة - توثيق النظام', 'وزارة الصحة', 1, 'documentation', 'high', 70, '2024-01-30', 2, 180000),
('P-1025', 'شركة الاتصالات - استضافة الموقع', 'شركة الاتصالات', 2, 'deployment', 'medium', 90, '2024-02-15', 3, 320000),
('P-1026', 'تطوير بوابة الدفع', 'بنك الرياض', 3, 'testing', 'high', 60, '2024-02-10', 1, 280000),
('P-1027', 'نظام إدارة المحتوى', 'وزارة التعليم', 2, 'deployment', 'medium', 85, '2024-01-25', 3, 150000),
('P-1028', 'اختبار اختراق شامل', 'شركة التأمين', 4, 'testing', 'critical', 30, '2024-02-20', 4, 200000);

-- =============================================
-- إنشاء جدول الوحدات إذا لم يكن موجوداً
-- =============================================
CREATE TABLE IF NOT EXISTS units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    head_name VARCHAR(255),
    head_id INT,
    employee_count INT DEFAULT 0,
    max_employees INT DEFAULT 10,
    budget DECIMAL(15,2) DEFAULT 0,
    color VARCHAR(20) DEFAULT 'blue',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);
*/
-- إضافة بيانات الوحدات
INSERT INTO units (name, code, head_name, employee_count, max_employees, budget) VALUES
('وحدة التوثيق', 'DOC', 'علي محمد', 5, 6, 120000),
('وحدة التخزين', 'STR', 'فاطمة أحمد', 12, 15, 850000),
('وحدة الحماية', 'SEC', 'خالد سعود', 7, 9, 600000),
('وحدة الاختبار', 'PEN', 'سارة القحطاني', 3, 5, 350000);

-- =============================================
-- إنشاء جدول طلبات الموارد
-- =============================================
CREATE TABLE IF NOT EXISTS resource_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    unit_id INT NOT NULL,
    requester_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    resource_type ENUM('equipment', 'software', 'personnel', 'training', 'other') NOT NULL,
    amount DECIMAL(15,2),
    status ENUM('pending', 'approved', 'rejected', 'in-progress') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date TIMESTAMP NULL,
    reviewed_by INT,
    notes TEXT,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO resource_requests (unit_id, requester_id, title, description, resource_type, amount, priority, status) VALUES
(2, 3, 'ترقية خادم قاعدة البيانات', 'شراء خادم جديد لقاعدة البيانات الرئيسية', 'equipment', 50000, 'high', 'pending'),
(4, 4, 'أداة اختبار اختراق جديدة', 'ترخيص سنوي لأداة اختبار متقدمة', 'software', 15000, 'medium', 'pending'),
(3, 1, 'تعيين محلل أمني', 'توظيف محلل أمني إضافي للفريق', 'personnel', 120000, 'high', 'pending'),
(1, 2, 'دورة توثيق فني', 'تدريب فريق التوثيق على أدوات جديدة', 'training', 8000, 'low', 'approved');

-- =============================================
-- إنشاء جدول الموافقات المعلقة
-- =============================================
CREATE TABLE IF NOT EXISTS pending_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('budget', 'hire', 'policy', 'project', 'other') NOT NULL,
    amount DECIMAL(15,2),
    requester_id INT,
    unit_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date TIMESTAMP NULL,
    reviewed_by INT,
    notes TEXT,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO pending_approvals (title, description, type, amount, requester_id, unit_id, priority) VALUES
('ترقية خادم', 'طلب ترقية خادم قاعدة البيانات الرئيسي', 'budget', 50000, 3, 2, 'high'),
('تعيين جديد', 'طلب تعيين محلل أمني جديد', 'hire', 120000, 1, 3, 'high');

-- =============================================
-- إنشاء جدول سجل النشاطات
-- =============================================
CREATE TABLE IF NOT EXISTS activity_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(100),
    target_type VARCHAR(50),
    target_id INT,
    description TEXT,
    unit_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
);

INSERT INTO activity_log (user_id, action_type, target_type, target_id, description, unit_id, ip_address) VALUES
(1, 'project_deployed', 'project', 3, 'المشروع P-1025 تم نشره بنجاح', 2, '192.168.1.100'),
(4, 'vulnerability_found', 'project', 1, 'ثغرة حرجة تم اكتشافها في P-1019', 4, '192.168.1.104'),
(2, 'documentation_completed', 'project', 2, 'تقرير توثيق مكتمل للمشروع P-1022', 1, '192.168.1.102'),
(3, 'security_alert', 'system', NULL, 'تم رصد محاولة وصول غير مصرح', 3, '192.168.1.103');

-- =============================================
-- إنشاء جدول مؤشرات الأداء
-- =============================================
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE NOT NULL,
    unit_id INT,
    productivity DECIMAL(5,2),
    quality DECIMAL(5,2),
    speed DECIMAL(5,2),
    employee_count INT,
    active_projects INT,
    completed_tasks INT,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);

INSERT INTO performance_metrics (metric_date, unit_id, productivity, quality, speed, employee_count, active_projects) VALUES
(CURDATE(), 1, 92, 95, 88, 5, 6),
(CURDATE(), 2, 85, 92, 85, 12, 15),
(CURDATE(), 3, 78, 85, 78, 7, 42),
(CURDATE(), 4, 88, 90, 85, 3, 5);

-- =============================================
-- إنشاء جدول حالة النظام
-- =============================================
CREATE TABLE IF NOT EXISTS system_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component VARCHAR(100) NOT NULL,
    status ENUM('active', 'warning', 'error', 'maintenance') DEFAULT 'active',
    health_percentage INT DEFAULT 100,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO system_status (component, status, health_percentage) VALUES
('جدار الحماية', 'active', 100),
('أنظمة الكشف', 'active', 95),
('النسخ الاحتياطي', 'active', 100);

-- =============================================
-- إنشاء جدول التهديدات الحية
-- =============================================
CREATE TABLE IF NOT EXISTS live_threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    threat_type ENUM('ddos', 'brute_force', 'sql_injection', 'xss', 'malware') NOT NULL,
    count INT DEFAULT 0,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT true
);

INSERT INTO live_threats (threat_type, count, severity) VALUES
('ddos', 3, 'critical'),
('brute_force', 5, 'high'),
('sql_injection', 4, 'medium');

-- =============================================
-- التحقق من البيانات
-- =============================================
SELECT '✅ تم إنشاء جميع الجداول بنجاح' as message;
SELECT CONCAT('📊 عدد المشاريع: ', COUNT(*)) FROM projects;
SELECT CONCAT('🏢 عدد الوحدات: ', COUNT(*)) FROM units;
SELECT CONCAT('📝 عدد طلبات الموارد: ', COUNT(*)) FROM resource_requests;
SELECT CONCAT('📋 عدد الموافقات: ', COUNT(*)) FROM pending_approvals;
SELECT CONCAT('📈 عدد النشاطات: ', COUNT(*)) FROM activity_log;

/*
-- manager.sql - أضف هذه الجداول إلى قاعدة البيانات الرئيسية
USE security_monitoring_db;

-- فقط الجداول الجديدة التي نحتاجها
CREATE TABLE IF NOT EXISTS units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    head_name VARCHAR(255),
    head_id INT,
    employee_count INT DEFAULT 0,
    max_employees INT DEFAULT 10,
    budget DECIMAL(15,2) DEFAULT 0,
    color VARCHAR(20) DEFAULT 'blue',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255),
    unit_id INT,
    status ENUM('documentation', 'testing', 'deployment', 'completed', 'delayed') DEFAULT 'documentation',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    progress INT DEFAULT 0,
    start_date DATE,
    deadline DATE,
    manager_id INT,
    budget DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- باقي الجداول من الملف السابق...


-- =============================================
-- manager_dashboard.sql - قاعدة بيانات لوحة المدير
-- =============================================

USE security_monitoring_db;

-- =============================================
-- 1. جدول الوحدات (units)
-- =============================================
CREATE TABLE IF NOT EXISTS units (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    head_name VARCHAR(255),
    head_id INT,
    employee_count INT DEFAULT 0,
    max_employees INT DEFAULT 10,
    budget DECIMAL(15,2) DEFAULT 0,
    color VARCHAR(20) DEFAULT 'blue',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO units (name, code, head_name, employee_count, max_employees, budget, color) VALUES
('وحدة التوثيق', 'DOC', 'علي محمد', 5, 6, 120000, 'blue'),
('وحدة التخزين', 'STR', 'فاطمة أحمد', 12, 15, 850000, 'purple'),
('وحدة الحماية', 'SEC', 'خالد سعود', 7, 9, 600000, 'green'),
('وحدة الاختبار', 'PEN', 'سارة القحطاني', 3, 5, 350000, 'yellow');

-- =============================================
-- 2. جدول المشاريع (projects)
-- =============================================
CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255),
    unit_id INT,
    status ENUM('documentation', 'testing', 'deployment', 'completed', 'delayed') DEFAULT 'documentation',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    progress INT DEFAULT 0,
    start_date DATE,
    deadline DATE,
    manager_id INT,
    budget DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO projects (code, name, client_name, unit_id, status, priority, progress, deadline, manager_id, budget) VALUES
('P-1019', 'بنك الأهلي - ترقية الأمان', 'بنك الأهلي', 4, 'testing', 'critical', 45, '2024-02-01', 4, 250000),
('P-1023', 'وزارة الصحة - توثيق النظام', 'وزارة الصحة', 1, 'documentation', 'high', 70, '2024-01-30', 2, 180000),
('P-1025', 'شركة الاتصالات - استضافة الموقع', 'شركة الاتصالات', 2, 'deployment', 'medium', 90, '2024-02-15', 3, 320000),
('P-1026', 'تطوير بوابة الدفع', 'بنك الرياض', 3, 'testing', 'high', 60, '2024-02-10', 1, 280000),
('P-1027', 'نظام إدارة المحتوى', 'وزارة التعليم', 2, 'deployment', 'medium', 85, '2024-01-25', 3, 150000),
('P-1028', 'اختبار اختراق شامل', 'شركة التأمين', 4, 'testing', 'critical', 30, '2024-02-20', 4, 200000);

-- =============================================
-- 3. جدول طلبات الموارد (resource_requests)
-- =============================================
CREATE TABLE IF NOT EXISTS resource_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    unit_id INT NOT NULL,
    requester_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    resource_type ENUM('equipment', 'software', 'personnel', 'training', 'other') NOT NULL,
    amount DECIMAL(15,2),
    status ENUM('pending', 'approved', 'rejected', 'in-progress') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date TIMESTAMP NULL,
    reviewed_by INT,
    notes TEXT,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO resource_requests (unit_id, requester_id, title, description, resource_type, amount, priority, status) VALUES
(2, 3, 'ترقية خادم قاعدة البيانات', 'شراء خادم جديد لقاعدة البيانات الرئيسية', 'equipment', 50000, 'high', 'pending'),
(4, 4, 'أداة اختبار اختراق جديدة', 'ترخيص سنوي لأداة اختبار متقدمة', 'software', 15000, 'medium', 'pending'),
(3, 1, 'تعيين محلل أمني', 'توظيف محلل أمني إضافي للفريق', 'personnel', 120000, 'high', 'pending'),
(1, 2, 'دورة توثيق فني', 'تدريب فريق التوثيق على أدوات جديدة', 'training', 8000, 'low', 'approved');

-- =============================================
-- 4. جدول الحوادث (incidents) - تحديث
-- =============================================
ALTER TABLE incidents ADD COLUMN IF NOT EXISTS unit_id INT AFTER assigned_to;
ALTER TABLE incidents ADD COLUMN IF NOT EXISTS resolution_time INT AFTER resolved_at;
ALTER TABLE incidents ADD FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL;

-- =============================================
-- 5. جدول سجل النشاطات (activity_log)
-- =============================================
CREATE TABLE IF NOT EXISTS activity_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(100),
    target_type VARCHAR(50),
    target_id INT,
    description TEXT,
    unit_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    INDEX idx_created (created_at),
    INDEX idx_type (action_type)
);

INSERT INTO activity_log (user_id, action_type, target_type, target_id, description, unit_id, ip_address) VALUES
(1, 'project_deployed', 'project', 3, 'المشروع P-1025 تم نشره بنجاح', 2, '192.168.1.100'),
(4, 'vulnerability_found', 'project', 1, 'ثغرة حرجة تم اكتشافها في P-1019', 4, '192.168.1.104'),
(2, 'documentation_completed', 'project', 2, 'تقرير توثيق مكتمل للمشروع P-1022', 1, '192.168.1.102'),
(3, 'security_alert', 'system', NULL, 'تم رصد محاولة وصول غير مصرح', 3, '192.168.1.103');

-- =============================================
-- 6. جدول مؤشرات الأداء (kpi_metrics)
-- =============================================
CREATE TABLE IF NOT EXISTS kpi_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE NOT NULL,
    unit_id INT,
    metric_name VARCHAR(100),
    metric_value DECIMAL(10,2),
    target_value DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_metric (metric_date, unit_id, metric_name)
);

INSERT INTO kpi_metrics (metric_date, unit_id, metric_name, metric_value, target_value) VALUES
(CURDATE(), 1, 'productivity', 92, 90),
(CURDATE(), 2, 'productivity', 85, 90),
(CURDATE(), 3, 'productivity', 78, 90),
(CURDATE(), 4, 'productivity', 88, 90),
(CURDATE(), 1, 'quality', 95, 95),
(CURDATE(), 2, 'quality', 92, 95),
(CURDATE(), 3, 'quality', 85, 95),
(CURDATE(), 4, 'quality', 90, 95);

-- =============================================
-- 7. جدول الموافقات المعلقة (pending_approvals)
-- =============================================
CREATE TABLE IF NOT EXISTS pending_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('budget', 'hire', 'policy', 'project', 'other') NOT NULL,
    amount DECIMAL(15,2),
    requester_id INT,
    unit_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_date TIMESTAMP NULL,
    reviewed_by INT,
    notes TEXT,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO pending_approvals (title, description, type, amount, requester_id, unit_id, priority) VALUES
('ترقية خادم', 'طلب ترقية خادم قاعدة البيانات الرئيسي', 'budget', 50000, 3, 2, 'high'),
('تعيين جديد', 'طلب تعيين محلل أمني جديد', 'hire', 120000, 1, 3, 'high');

-- =============================================
-- 8. جدول معايير الامتثال (compliance_standards)
-- =============================================
CREATE TABLE IF NOT EXISTS compliance_standards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    compliance_rate DECIMAL(5,2) DEFAULT 0,
    status ENUM('compliant', 'in-progress', 'non-compliant') DEFAULT 'in-progress',
    last_audit DATE,
    next_audit DATE,
    responsible_unit INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_unit) REFERENCES units(id) ON DELETE SET NULL
);

INSERT INTO compliance_standards (name, code, description, compliance_rate, status, last_audit, next_audit, responsible_unit) VALUES
('ISO 27001 - أمن المعلومات', 'ISO27001', 'معيار أمن المعلومات الدولي', 98, 'compliant', '2024-01-15', '2024-04-15', 3),
('PCI DSS - معاملات الدفع', 'PCI-DSS', 'معيار أمن بطاقات الدفع', 85, 'in-progress', '2024-01-20', '2024-04-20', 3),
('GDPR - حماية البيانات', 'GDPR', 'اللائحة العامة لحماية البيانات', 96, 'compliant', '2024-01-10', '2024-04-10', 1),
('SOX - الرقابة المالية', 'SOX', 'قانون ساربينز أوكسلي', 82, 'in-progress', '2024-01-05', '2024-04-05', 2);

-- =============================================
-- 9. جدول الانحرافات (violations)
-- =============================================
CREATE TABLE IF NOT EXISTS violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    standard_id INT,
    severity ENUM('critical', 'high', 'medium', 'low') NOT NULL,
    status ENUM('open', 'in-progress', 'resolved') DEFAULT 'open',
    detected_date DATE,
    resolved_date DATE NULL,
    assigned_to INT,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (standard_id) REFERENCES compliance_standards(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO violations (title, description, standard_id, severity, status, detected_date, assigned_to) VALUES
('عدم وجود تسجيل للوصول', 'خادم DB-02 لا يسجل محاولات الوصول', 1, 'critical', 'open', '2024-01-25', 1),
('كلمات مرور ضعيفة', '12 حساب بإعدادات أمان ضعيفة', 2, 'high', 'in-progress', '2024-01-23', 4),
('تحديثات أمنية متأخرة', '3 خوادم تحتاج لتحديثات أمنية', 1, 'medium', 'open', '2024-01-21', 3),
('توثيق غير مكتمل', 'توثيق عمليات المراقبة غير مكتمل', 3, 'medium', 'in-progress', '2024-01-18', 2);

-- =============================================
-- 10. جدول التقارير الأرشيفية (archived_reports)
-- =============================================
CREATE TABLE IF NOT EXISTS archived_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('security', 'performance', 'financial', 'audit', 'compliance', 'operational') NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    file_format ENUM('PDF', 'Excel', 'Word', 'CSV') DEFAULT 'PDF',
    unit_id INT,
    generated_by INT,
    report_date DATE,
    archive_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    retention_years INT DEFAULT 7,
    tags TEXT,
    description TEXT,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (report_type),
    INDEX idx_date (report_date)
);

INSERT INTO archived_reports (report_name, report_type, file_path, file_size, file_format, unit_id, generated_by, report_date) VALUES
('تقرير الأمان الشهري - يناير 2024', 'security', '/reports/2024/01/security_jan.pdf', 24500000, 'PDF', 3, 1, '2024-01-28'),
('تقرير أداء الخوادم - الأسبوع الثالث', 'performance', '/reports/2024/01/performance_w3.xlsx', 15200000, 'Excel', 2, 3, '2024-01-25'),
('تقرير تدقيق التوثيق الربعي', 'audit', '/reports/2024/01/audit_q1.docx', 8700000, 'Word', 1, 2, '2024-01-20');

-- =============================================
-- 11. جدول إحصائيات الأداء (performance_metrics)
-- =============================================
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_date DATE NOT NULL,
    unit_id INT,
    productivity DECIMAL(5,2),
    quality DECIMAL(5,2),
    speed DECIMAL(5,2),
    employee_count INT,
    active_projects INT,
    completed_tasks INT,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
);

INSERT INTO performance_metrics (metric_date, unit_id, productivity, quality, speed, employee_count, active_projects) VALUES
(CURDATE(), 1, 92, 95, 88, 5, 6),
(CURDATE(), 2, 85, 92, 85, 12, 15),
(CURDATE(), 3, 78, 85, 78, 7, 42),
(CURDATE(), 4, 88, 90, 85, 3, 5);

-- =============================================
-- 12. جدول التهديدات الأمنية الحية (live_threats)
-- =============================================
CREATE TABLE IF NOT EXISTS live_threats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    threat_type ENUM('ddos', 'brute_force', 'sql_injection', 'xss', 'malware') NOT NULL,
    count INT DEFAULT 0,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT true
);

INSERT INTO live_threats (threat_type, count, severity) VALUES
('ddos', 3, 'critical'),
('brute_force', 5, 'high'),
('sql_injection', 4, 'medium');

-- =============================================
-- 13. جدول حالة النظام (system_status)
-- =============================================
CREATE TABLE IF NOT EXISTS system_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    component VARCHAR(100) NOT NULL,
    status ENUM('active', 'warning', 'error', 'maintenance') DEFAULT 'active',
    health_percentage INT DEFAULT 100,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO system_status (component, status, health_percentage) VALUES
('جدار الحماية', 'active', 100),
('أنظمة الكشف', 'active', 95),
('النسخ الاحتياطي', 'active', 100);

-- =============================================
-- 14. تحديث جدول المستخدمين (users) - إضافة الصلاحيات
-- =============================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS unit_id INT AFTER department;
ALTER TABLE users ADD COLUMN IF NOT EXISTS can_manage BOOLEAN DEFAULT false;
ALTER TABLE users ADD FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL;

-- تحديث المستخدمين الحاليين
UPDATE users SET unit_id = 3 WHERE id = 1; -- أحمد العلي - وحدة الحماية
UPDATE users SET unit_id = 1 WHERE id = 2; -- سارة محمد - وحدة التوثيق
UPDATE users SET unit_id = 4 WHERE id = 3; -- خالد عمر - وحدة الاختبار
UPDATE users SET unit_id = 2 WHERE id = 4; -- نورا أحمد - وحدة التخزين
UPDATE users SET can_manage = true WHERE id = 5; -- فهد سعود - مدير

-- =============================================
-- 15. التحقق النهائي
-- =============================================
SELECT '✅ تم إنشاء جميع جداول المدير بنجاح' as message;
SELECT CONCAT('📊 عدد الوحدات: ', COUNT(*)) FROM units;
SELECT CONCAT('📋 عدد المشاريع: ', COUNT(*)) FROM projects;
SELECT CONCAT('⚠️ عدد الحوادث: ', COUNT(*)) FROM incidents;