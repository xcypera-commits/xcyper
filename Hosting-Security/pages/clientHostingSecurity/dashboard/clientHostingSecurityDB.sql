-- =============================================
-- استخدام قاعدة البيانات الرئيسية
-- =============================================
USE security_monitoring_db;

-- =============================================
-- بيانات افتراضية لجدول العملاء
-- كلمة المرور لجميع الحسابات: Test@123
-- =============================================
INSERT INTO `client_clients` (
    `client_code`, 
    `full_name`, 
    `email`, 
    `phone`, 
    `company_name`, 
    `tax_number`,
    `address`,
    `city`,
    `country`,
    `password_hash`, 
    `balance`, 
    `credit_limit`, 
    `status`,
    `email_verified`,
    `phone_verified`
) VALUES
('CL-0015', 'أحمد محمد العلي', 'ahmed@example.com', '0501234567', 'شركة التقنية المتطورة', '123456789', 'الرياض - حي النخيل', 'الرياض', 'السعودية', '$2y$10$YourHashedPasswordHere', 15000.00, 50000.00, 'active', 1, 1),
('CL-0016', 'سارة عبدالله القحطاني', 'sara@example.com', '0552345678', 'مؤسسة الأمان الرقمي', '234567890', 'جدة - شارع التحلية', 'جدة', 'السعودية', '$2y$10$YourHashedPasswordHere', 22000.00, 40000.00, 'active', 1, 1),
('CL-0031', 'محمد عبدالله العمري', 'mohammed@example.com', '0533456789', 'شركة البيانات الآمنة', '345678901', 'الدمام - حي الشاطئ', 'الدمام', 'السعودية', '$2y$10$YourHashedPasswordHere', 8500.00, 30000.00, 'active', 1, 0),
('CL-0040', 'نورة سعد الدوسري', 'noura@example.com', '0564567890', 'مؤسسة التجارة الإلكترونية', '456789012', 'الخبر - العقربية', 'الخبر', 'السعودية', '$2y$10$YourHashedPasswordHere', 33000.00, 60000.00, 'active', 1, 1),
('CL-0055', 'فهد خالد القحطاني', 'fahad@example.com', '0545678901', 'شركة الحلول المتكاملة', '567890123', 'مكة - العزيزية', 'مكة', 'السعودية', '$2y$10$YourHashedPasswordHere', 12700.00, 25000.00, 'suspended', 1, 0);
/*

-- جدول المجلدات
CREATE TABLE client_folders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    folder_name VARCHAR(255) NOT NULL,
    parent_path VARCHAR(500) DEFAULT '/',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE
);

-- تحديث جدول الملفات
ALTER TABLE client_files ADD COLUMN folder_path VARCHAR(500) DEFAULT '/';
/*

-- =============================================
-- إدراج بيانات افتراضية للنسخ الاحتياطية
-- =============================================

-- أولاً: نتأكد من وجود مواقع استضافة
-- (يفضل تشغيل هذا الجزء إذا كانت المواقع غير موجودة)

INSERT IGNORE INTO hosting_sites (id, client_id, project_id, plan_id, site_name, status) VALUES
(1, 1, 1, 2, 'متجر الإلكتروني', 'active'),
(2, 1, 2, 1, 'موقع الشركة', 'active'),
(3, 2, 3, 3, 'منصة تعليمية', 'active'),
(4, 2, 4, 2, 'منتدى النقاش', 'active'),
(5, 1, 1, 1, 'مدونة شخصية', 'active');

-- =============================================
-- نسخ احتياطية كاملة (Full Backups)
-- =============================================
INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, started_at, completed_at, created_at) VALUES
-- موقع 1: متجر إلكتروني - نسخ كاملة
(1, 'full', 2048, '/backups/site1/eshop_full_20250120.zip', 'completed', '2025-01-20 02:00:00', '2025-01-20 02:45:30', '2025-01-20 02:45:30'),
(1, 'full', 2050, '/backups/site1/eshop_full_20250119.zip', 'completed', '2025-01-19 02:00:00', '2025-01-19 02:44:15', '2025-01-19 02:44:15'),
(1, 'full', 2035, '/backups/site1/eshop_full_20250118.zip', 'completed', '2025-01-18 02:00:00', '2025-01-18 02:43:45', '2025-01-18 02:43:45'),
(1, 'full', 2028, '/backups/site1/eshop_full_20250117.zip', 'completed', '2025-01-17 02:00:00', '2025-01-17 02:42:20', '2025-01-17 02:42:20'),
(1, 'full', 2020, '/backups/site1/eshop_full_20250116.zip', 'completed', '2025-01-16 02:00:00', '2025-01-16 02:41:10', '2025-01-16 02:41:10'),

-- موقع 2: موقع الشركة - نسخ كاملة
(2, 'full', 512, '/backups/site2/company_full_20250120.zip', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:20:45', '2025-01-20 03:20:45'),
(2, 'full', 508, '/backups/site2/company_full_20250119.zip', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:19:30', '2025-01-19 03:19:30'),
(2, 'full', 505, '/backups/site2/company_full_20250118.zip', 'completed', '2025-01-18 03:00:00', '2025-01-18 03:18:15', '2025-01-18 03:18:15'),
(2, 'full', 503, '/backups/site2/company_full_20250117.zip', 'completed', '2025-01-17 03:00:00', '2025-01-17 03:17:40', '2025-01-17 03:17:40'),

-- موقع 3: منصة تعليمية - نسخ كاملة
(3, 'full', 8192, '/backups/site3/lms_full_20250120.zip', 'completed', '2025-01-20 01:00:00', '2025-01-20 02:30:15', '2025-01-20 02:30:15'),
(3, 'full', 8185, '/backups/site3/lms_full_20250119.zip', 'completed', '2025-01-19 01:00:00', '2025-01-19 02:28:45', '2025-01-19 02:28:45'),
(3, 'full', 8178, '/backups/site3/lms_full_20250118.zip', 'completed', '2025-01-18 01:00:00', '2025-01-18 02:27:30', '2025-01-18 02:27:30'),
(3, 'full', 8170, '/backups/site3/lms_full_20250117.zip', 'completed', '2025-01-17 01:00:00', '2025-01-17 02:26:15', '2025-01-17 02:26:15'),
(3, 'full', 8165, '/backups/site3/lms_full_20250116.zip', 'completed', '2025-01-16 01:00:00', '2025-01-16 02:25:00', '2025-01-16 02:25:00'),

-- موقع 4: منتدى النقاش - نسخ كاملة
(4, 'full', 3072, '/backups/site4/forum_full_20250120.zip', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:50:30', '2025-01-20 04:50:30'),
(4, 'full', 3065, '/backups/site4/forum_full_20250119.zip', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:48:45', '2025-01-19 04:48:45'),
(4, 'full', 3058, '/backups/site4/forum_full_20250118.zip', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:47:15', '2025-01-18 04:47:15'),

-- موقع 5: مدونة شخصية - نسخ كاملة
(5, 'full', 256, '/backups/site5/blog_full_20250120.zip', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:12:20', '2025-01-20 05:12:20'),
(5, 'full', 252, '/backups/site5/blog_full_20250119.zip', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:11:45', '2025-01-19 05:11:45'),
(5, 'full', 250, '/backups/site5/blog_full_20250118.zip', 'completed', '2025-01-18 05:00:00', '2025-01-18 05:10:30', '2025-01-18 05:10:30');

-- =============================================
-- نسخ احتياطية لقواعد البيانات (Database Backups)
-- =============================================
INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, started_at, completed_at, created_at) VALUES
-- موقع 1: قواعد بيانات
(1, 'database', 512, '/backups/site1/eshop_db_20250120.sql', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:15:30', '2025-01-20 03:15:30'),
(1, 'database', 508, '/backups/site1/eshop_db_20250119.sql', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:14:45', '2025-01-19 03:14:45'),
(1, 'database', 505, '/backups/site1/eshop_db_20250118.sql', 'completed', '2025-01-18 03:00:00', '2025-01-18 03:14:15', '2025-01-18 03:14:15'),
(1, 'database', 503, '/backups/site1/eshop_db_20250117.sql', 'completed', '2025-01-17 03:00:00', '2025-01-17 03:13:40', '2025-01-17 03:13:40'),

-- موقع 2: قواعد بيانات
(2, 'database', 128, '/backups/site2/company_db_20250120.sql', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:08:20', '2025-01-20 04:08:20'),
(2, 'database', 126, '/backups/site2/company_db_20250119.sql', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:07:45', '2025-01-19 04:07:45'),
(2, 'database', 125, '/backups/site2/company_db_20250118.sql', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:07:15', '2025-01-18 04:07:15'),

-- موقع 3: قواعد بيانات
(3, 'database', 2048, '/backups/site3/lms_db_20250120.sql', 'completed', '2025-01-20 02:00:00', '2025-01-20 02:45:30', '2025-01-20 02:45:30'),
(3, 'database', 2040, '/backups/site3/lms_db_20250119.sql', 'completed', '2025-01-19 02:00:00', '2025-01-19 02:44:15', '2025-01-19 02:44:15'),
(3, 'database', 2035, '/backups/site3/lms_db_20250118.sql', 'completed', '2025-01-18 02:00:00', '2025-01-18 02:43:45', '2025-01-18 02:43:45'),

-- موقع 4: قواعد بيانات
(4, 'database', 768, '/backups/site4/forum_db_20250120.sql', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:22:30', '2025-01-20 05:22:30'),
(4, 'database', 765, '/backups/site4/forum_db_20250119.sql', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:21:45', '2025-01-19 05:21:45'),
(4, 'database', 762, '/backups/site4/forum_db_20250118.sql', 'completed', '2025-01-18 05:00:00', '2025-01-18 05:21:15', '2025-01-18 05:21:15'),

-- موقع 5: قواعد بيانات
(5, 'database', 64, '/backups/site5/blog_db_20250120.sql', 'completed', '2025-01-20 06:00:00', '2025-01-20 06:04:30', '2025-01-20 06:04:30'),
(5, 'database', 63, '/backups/site5/blog_db_20250119.sql', 'completed', '2025-01-19 06:00:00', '2025-01-19 06:04:15', '2025-01-19 06:04:15');

-- =============================================
-- نسخ احتياطية للملفات فقط (Files Backups)
-- =============================================
INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, started_at, completed_at, created_at) VALUES
-- موقع 1: ملفات
(1, 'files', 1536, '/backups/site1/eshop_files_20250120.zip', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:30:15', '2025-01-20 04:30:15'),
(1, 'files', 1532, '/backups/site1/eshop_files_20250119.zip', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:29:45', '2025-01-19 04:29:45'),
(1, 'files', 1530, '/backups/site1/eshop_files_20250118.zip', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:29:15', '2025-01-18 04:29:15'),

-- موقع 2: ملفات
(2, 'files', 384, '/backups/site2/company_files_20250120.zip', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:12:30', '2025-01-20 05:12:30'),
(2, 'files', 382, '/backups/site2/company_files_20250119.zip', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:12:15', '2025-01-19 05:12:15'),

-- موقع 3: ملفات
(3, 'files', 6144, '/backups/site3/lms_files_20250120.zip', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:45:30', '2025-01-20 03:45:30'),
(3, 'files', 6138, '/backups/site3/lms_files_20250119.zip', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:44:45', '2025-01-19 03:44:45'),

-- موقع 4: ملفات
(4, 'files', 2304, '/backups/site4/forum_files_20250120.zip', 'completed', '2025-01-20 06:00:00', '2025-01-20 06:28:30', '2025-01-20 06:28:30'),
(4, 'files', 2300, '/backups/site4/forum_files_20250119.zip', 'completed', '2025-01-19 06:00:00', '2025-01-19 06:27:45', '2025-01-19 06:27:45');

-- =============================================
-- نسخ قيد التقدم أو فاشلة
-- =============================================
INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, started_at, completed_at, created_at) VALUES
-- نسخ قيد التقدم
(1, 'full', 1024, '/backups/site1/eshop_full_20250121_in_progress.zip', 'in_progress', '2025-01-21 02:00:00', NULL, '2025-01-21 02:00:00'),
(3, 'database', 1024, '/backups/site3/lms_db_20250121_in_progress.sql', 'in_progress', '2025-01-21 02:00:00', NULL, '2025-01-21 02:00:00'),

-- نسخ فاشلة
(2, 'full', 0, '/backups/site2/company_full_20250115_failed.zip', 'failed', '2025-01-15 03:00:00', '2025-01-15 03:05:30', '2025-01-15 03:05:30'),
(4, 'database', 0, '/backups/site4/forum_db_20250114_failed.sql', 'failed', '2025-01-14 05:00:00', '2025-01-14 05:03:15', '2025-01-14 05:03:15'),

-- نسخ معلقة
(5, 'full', 0, '/backups/site5/blog_full_20250121_pending.zip', 'pending', NULL, NULL, '2025-01-21 02:00:00'),
(3, 'files', 0, '/backups/site3/lms_files_20250121_pending.zip', 'pending', NULL, NULL, '2025-01-21 02:00:00');

-- =============================================
-- نسخ أسبوعية قديمة (للأرشيف)
-- =============================================
INSERT INTO hosting_backups (site_id, backup_type, backup_size, file_path, status, started_at, completed_at, created_at) VALUES
(1, 'full', 2010, '/backups/site1/eshop_full_20250110.zip', 'completed', '2025-01-10 02:00:00', '2025-01-10 02:40:15', '2025-01-10 02:40:15'),
(1, 'full', 2005, '/backups/site1/eshop_full_20250103.zip', 'completed', '2025-01-03 02:00:00', '2025-01-03 02:39:30', '2025-01-03 02:39:30'),
(2, 'full', 500, '/backups/site2/company_full_20250110.zip', 'completed', '2025-01-10 03:00:00', '2025-01-10 03:16:45', '2025-01-10 03:16:45'),
(2, 'full', 498, '/backups/site2/company_full_20250103.zip', 'completed', '2025-01-03 03:00:00', '2025-01-03 03:16:15', '2025-01-03 03:16:15'),
(3, 'full', 8150, '/backups/site3/lms_full_20250110.zip', 'completed', '2025-01-10 01:00:00', '2025-01-10 02:22:30', '2025-01-10 02:22:30'),
(3, 'full', 8140, '/backups/site3/lms_full_20250103.zip', 'completed', '2025-01-03 01:00:00', '2025-01-03 02:21:15', '2025-01-03 02:21:15');

-- =============================================
-- تحديث hosting_sites بآخر نسخة احتياطية
-- (اختياري: لتحديث حقل last_backup في جدول المواقع)
-- =============================================
UPDATE hosting_sites SET last_backup = '2025-01-20 02:45:30' WHERE id = 1;
UPDATE hosting_sites SET last_backup = '2025-01-20 03:20:45' WHERE id = 2;
UPDATE hosting_sites SET last_backup = '2025-01-20 02:30:15' WHERE id = 3;
UPDATE hosting_sites SET last_backup = '2025-01-20 04:50:30' WHERE id = 4;
UPDATE hosting_sites SET last_backup = '2025-01-20 05:12:20' WHERE id = 5;
/*
-- =============================================
-- إدراج بيانات افتراضية لـ hosting_security_logs
-- =============================================

-- أولاً: نتأكد أن عندنا مواقع استضافة (hosting_sites)
INSERT IGNORE INTO hosting_sites (id, client_id, project_id, plan_id, site_name, status) VALUES
(1, 1, 1, 1, 'متجر الإلكتروني', 'active'),
(2, 1, 2, 2, 'موقع الشركة', 'active'),
(3, 1, 1, 1, 'لوحة التحكم', 'active'),
(4, 2, 3, 1, 'منصة تعليمية', 'active'),
(5, 2, 3, 3, 'منتدى النقاش', 'active');

-- =============================================
-- بيانات سجل الأمان (آخر 30 يوم)
-- =============================================
INSERT INTO hosting_security_logs (site_id, event_type, severity, ip_address, description, created_at) VALUES
-- أحداث اليوم
(1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح من لوحة التحكم', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح - جلسة جديدة', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(2, 'logout', 'info', '45.67.89.123', 'تسجيل خروج من لوحة التحكم', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات النظام: wp-config.php', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'failed_login', 'warning', '45.67.89.123', 'محاولة دخول فاشلة - كلمة مرور خاطئة (3 مرات)', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- الأمس
(2, 'failed_login', 'warning', '89.123.45.67', 'محاولات دخول فاشلة متعددة من نفس IP', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'failed_login', 'critical', '89.123.45.67', 'هجوم تخمين كلمات مرور - تم حظر IP', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح من جهاز معروف', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات ملف حساس', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح - مستخدم جديد', DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- قبل 3 أيام
(5, 'attack_detected', 'critical', '45.67.89.123', 'هجوم SQL Injection تم التصدي له', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'attack_detected', 'critical', '45.67.89.123', 'محاولة حقن قاعدة بيانات', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات القالب', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- قبل أسبوع
(1, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف ملف مشبوه في المجلد العام', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 'malware_detected', 'critical', '45.67.89.123', 'برمجية خبيثة في ملف index.php', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 'file_change', 'warning', '45.67.89.123', 'تغيير في ملفات النظام', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(2, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(4, 'failed_login', 'warning', '112.34.56.78', 'محاولة دخول فاشلة', DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- قبل 10 أيام
(2, 'attack_detected', 'critical', '45.67.89.123', 'هجوم XSS على نموذج البحث', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'attack_detected', 'critical', '45.67.89.123', 'محاولة تنفيذ سكريبت ضار', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 'file_change', 'info', '103.45.67.89', 'تحديث آمن للملفات', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات مجلد', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- قبل أسبوعين
(3, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف ثغرة أمنية في الإضافة', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(3, 'file_change', 'warning', '45.67.89.123', 'تغيير في ملفات الإضافات', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(4, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(2, 'failed_login', 'info', '89.123.45.67', 'محاولة دخول فاشلة منسية', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(1, 'logout', 'info', '192.168.1.100', 'تسجيل خروج', DATE_SUB(NOW(), INTERVAL 14 DAY)),

-- قبل 20 يوم
(4, 'attack_detected', 'critical', '45.67.89.123', 'هجوم DDoS تم التصدي له', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(4, 'attack_detected', 'critical', '45.67.89.123', 'هجوم تخمين عنيف', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(5, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات النظام', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 20 DAY)),

-- قبل 25 يوم
(1, 'failed_login', 'warning', '45.67.89.123', 'محاولة دخول فاشلة', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(2, 'permission_change', 'info', '103.45.67.89', 'تحديث صلاحيات المجلدات', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(4, 'logout', 'info', '78.90.12.34', 'تسجيل خروج', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(5, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', DATE_SUB(NOW(), INTERVAL 25 DAY));

-- =============================================
-- بيانات إضافية: أحداث معلوماتية (info)
-- =============================================
INSERT INTO hosting_security_logs (site_id, event_type, severity, ip_address, description, created_at) VALUES
(1, 'login', 'info', '192.168.1.100', 'دخول روتيني للوحة التحكم', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
(1, 'login', 'info', '192.168.1.100', 'جلسة عمل جديدة', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(2, 'login', 'info', '78.90.12.34', 'دخول مساء', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'file_change', 'info', '103.45.67.89', 'نسخ احتياطي تلقائي', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'login', 'info', '78.90.12.34', 'دخول صباحي', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(5, 'logout', 'info', '78.90.12.34', 'خروج من النظام', DATE_SUB(NOW(), INTERVAL 6 DAY));

-- =============================================
-- بيانات إضافية: أحداث تحذيرية (warning)
-- =============================================
INSERT INTO hosting_security_logs (site_id, event_type, severity, ip_address, description, created_at) VALUES
(2, 'failed_login', 'warning', '89.123.45.67', 'محاولة دخول من دولة غير معتادة', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات ملف مهم', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'file_change', 'warning', '103.45.67.89', 'تغيير غير متوقع في الملفات', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(5, 'failed_login', 'warning', '45.67.89.123', 'محاولات دخول متعددة', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'file_change', 'warning', '45.67.89.123', 'تعديل ملف خارج ساعات العمل', DATE_SUB(NOW(), INTERVAL 7 DAY));

-- =============================================
-- بيانات إضافية: أحداث حرجة (critical)
-- =============================================
INSERT INTO hosting_security_logs (site_id, event_type, severity, ip_address, description, created_at) VALUES
(1, 'attack_detected', 'critical', '45.67.89.123', 'هجوم من نوع RFI', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف backdoor في النظام', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, 'attack_detected', 'critical', '45.67.89.123', 'هجوم حجب خدمة (DoS)', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(4, 'malware_detected', 'critical', '45.67.89.123', 'برمجية فدية محتملة', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(5, 'attack_detected', 'critical', '45.67.89.123', 'هجوم على قاعدة البيانات', DATE_SUB(NOW(), INTERVAL 18 DAY));
/*
-- =============================================
-- تعطيل التحقق من المفاتيح الأجنبية مؤقتاً
-- =============================================
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- حذف البيانات الموجودة
-- =============================================
DELETE FROM hosting_backups;
DELETE FROM hosting_security_logs;
DELETE FROM hosting_support_requests;
DELETE FROM hosting_databases;
DELETE FROM hosting_ftp_accounts;
DELETE FROM hosting_access_logs;
DELETE FROM hosting_stats;
DELETE FROM hosting_sites;
DELETE FROM client_domains;
DELETE FROM hosting_plans;
DELETE FROM client_activity_log;
DELETE FROM client_notifications;
DELETE FROM client_ticket_replies;
DELETE FROM client_support_tickets;
DELETE FROM client_reports;
DELETE FROM client_payments;
DELETE FROM client_invoices;
DELETE FROM client_contracts;
DELETE FROM client_files;
DELETE FROM client_projects;
DELETE FROM client_service_requests;
DELETE FROM client_settings;
DELETE FROM client_attachments;
DELETE FROM client_stats;
DELETE FROM client_clients;

-- =============================================
-- إعادة تعيين عدادات AUTO_INCREMENT
-- =============================================
ALTER TABLE client_clients AUTO_INCREMENT = 1;
ALTER TABLE client_projects AUTO_INCREMENT = 1;
ALTER TABLE client_files AUTO_INCREMENT = 1;
ALTER TABLE client_contracts AUTO_INCREMENT = 1;
ALTER TABLE client_invoices AUTO_INCREMENT = 1;
ALTER TABLE client_payments AUTO_INCREMENT = 1;
ALTER TABLE client_reports AUTO_INCREMENT = 1;
ALTER TABLE client_support_tickets AUTO_INCREMENT = 1;
ALTER TABLE client_ticket_replies AUTO_INCREMENT = 1;
ALTER TABLE client_notifications AUTO_INCREMENT = 1;
ALTER TABLE client_activity_log AUTO_INCREMENT = 1;
ALTER TABLE client_service_requests AUTO_INCREMENT = 1;
ALTER TABLE client_settings AUTO_INCREMENT = 1;
ALTER TABLE client_attachments AUTO_INCREMENT = 1;
ALTER TABLE client_stats AUTO_INCREMENT = 1;
ALTER TABLE hosting_plans AUTO_INCREMENT = 1;
ALTER TABLE client_domains AUTO_INCREMENT = 1;
ALTER TABLE hosting_sites AUTO_INCREMENT = 1;
ALTER TABLE hosting_stats AUTO_INCREMENT = 1;
ALTER TABLE hosting_access_logs AUTO_INCREMENT = 1;
ALTER TABLE hosting_ftp_accounts AUTO_INCREMENT = 1;
ALTER TABLE hosting_databases AUTO_INCREMENT = 1;
ALTER TABLE hosting_support_requests AUTO_INCREMENT = 1;
ALTER TABLE hosting_security_logs AUTO_INCREMENT = 1;
ALTER TABLE hosting_backups AUTO_INCREMENT = 1;

-- =============================================
-- 1. العملاء (client_clients) - 10 سجلات
-- =============================================
INSERT INTO client_clients (client_code, full_name, email, phone, company_name, tax_number, address, city, password_hash, balance, credit_limit, status, email_verified, phone_verified, last_login) VALUES
('CL-2024-001', 'أحمد محمد العلي', 'ahmed.alali@example.com', '0501234567', 'شركة التقنية المتطورة', '1234567890', 'الرياض - حي النخيل', 'الرياض', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 25000.00, 50000.00, 'active', TRUE, TRUE, '2024-03-15 10:30:00'),
('CL-2024-002', 'سارة عبدالله القحطاني', 'sara.alqahtani@example.com', '0552345678', 'مؤسسة الأمان الرقمي', '1234567891', 'جدة - شارع التحلية', 'جدة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 18000.00, 30000.00, 'active', TRUE, TRUE, '2024-03-14 09:15:00'),
('CL-2024-003', 'محمد عبدالله العمري', 'mohammed.omari@example.com', '0533456789', 'شركة البيانات الآمنة', '1234567892', 'الدمام - حي الشاطئ', 'الدمام', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 35000.00, 60000.00, 'active', TRUE, TRUE, '2024-03-15 11:45:00'),
('CL-2024-004', 'نورة سعد الدوسري', 'noura.dosari@example.com', '0564567890', 'مؤسسة التجارة الإلكترونية', '1234567893', 'الخبر - العقربية', 'الخبر', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 12000.00, 20000.00, 'active', TRUE, FALSE, '2024-03-13 14:20:00'),
('CL-2024-005', 'فهد خالد القحطاني', 'fahad.qahtani@example.com', '0545678901', 'شركة الحلول المتكاملة', '1234567894', 'مكة - العزيزية', 'مكة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 5000.00, 10000.00, 'suspended', TRUE, FALSE, '2024-03-10 08:30:00'),
('CL-2024-006', 'ريم عبدالعزيز الشمري', 'reem.shamri@example.com', '0586789012', 'مؤسسة الشمري للتجارة', '1234567895', 'تبوك - النهضة', 'تبوك', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 8500.00, 15000.00, 'active', TRUE, TRUE, '2024-03-14 16:10:00'),
('CL-2024-007', 'عبدالرحمن إبراهيم الحارثي', 'abdulrahman.harthy@example.com', '0597890123', 'شركة الحارثي للتطوير', '1234567896', 'الطائف - الشهداء', 'الطائف', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 22000.00, 35000.00, 'active', TRUE, FALSE, '2024-03-12 13:40:00'),
('CL-2024-008', 'هند صالح العتيبي', 'hindi.otaibi@example.com', '0508901234', 'مؤسسة العتيبي للاستشارات', '1234567897', 'بريدة - الرحاب', 'بريدة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 14500.00, 25000.00, 'active', FALSE, FALSE, NULL),
('CL-2024-009', 'سامي فهد المطيري', 'sami.mutairi@example.com', '0559012345', 'شركة المطيري للتجارة', '1234567898', 'حائل - المطار', 'حائل', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 32000.00, 50000.00, 'active', TRUE, TRUE, '2024-03-11 10:15:00'),
('CL-2024-010', 'لمى بندر السبيعي', 'lama.subaie@example.com', '0560123456', 'مؤسسة السبيعي للتسويق', '1234567899', 'جيزان - الكورنيش', 'جيزان', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 9500.00, 15000.00, 'inactive', TRUE, FALSE, '2024-03-05 09:30:00');

-- =============================================
-- 2. مشاريع العميل (client_projects) - 10 سجلات
-- =============================================
INSERT INTO client_projects (client_id, project_code, project_name, project_type, description, status, stage, priority, start_date, deadline, progress, budget, paid_amount, manager_name) VALUES
(1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'متجر إلكتروني متكامل مع نظام إدارة محتوى', 'in_progress', 4, 'high', '2024-01-15', '2024-04-15', 65, 15000.00, 7500.00, 'أحمد العلي'),
(1, 'PRJ-STOR-001', 'نظام تخزين العملاء', 'storage', 'نظام تخزين سحابي لبيانات العملاء', 'completed', 6, 'medium', '2023-11-10', '2024-01-10', 100, 8000.00, 8000.00, 'سارة الأحمد'),
(1, 'PRJ-DEV-001', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال لأنظمة iOS و Android', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', 35, 35000.00, 15000.00, 'محمد العنزي'),
(2, 'PRJ-SEC-001', 'فحص أمني شامل', 'security', 'اختبار اختراق وتقييم أمني', 'testing', 5, 'high', '2024-02-01', '2024-03-15', 85, 12000.00, 6000.00, 'خالد الرشيد'),
(2, 'PRJ-PENT-001', 'اختبار اختراق للتطبيق', 'pentest', 'اختبار اختراق للتطبيق المصرفي', 'contract_pending', 3, 'critical', '2024-03-01', '2024-04-30', 25, 20000.00, 0.00, 'فاطمة الزهراني'),
(3, 'PRJ-DEV-002', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل للموارد البشرية', 'in_progress', 4, 'high', '2024-01-20', '2024-06-20', 40, 45000.00, 15000.00, 'عبدالله المطيري'),
(3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'medium', '2024-03-10', '2024-04-10', 20, 8000.00, 2000.00, 'ريم القحطاني'),
(4, 'PRJ-HOST-002', 'موقع الشركة التعريفي', 'hosting', 'موقع تعريفي بسيط للشركة', 'completed', 6, 'low', '2024-02-01', '2024-02-20', 100, 3000.00, 3000.00, 'منى الغامدي'),
(5, 'PRJ-HOST-003', 'موقع تجريبي', 'hosting', 'موقع تجريبي للاختبار', 'cancelled', 7, 'low', '2024-02-01', '2024-03-01', 20, 2000.00, 0.00, 'سامي الحربي'),
(6, 'PRJ-STOR-002', 'أرشفة المستندات', 'storage', 'نظام أرشفة للمستندات', 'pending', 1, 'medium', '2024-03-15', '2024-05-15', 0, 6000.00, 0.00, 'نورة الدوسري');

-- =============================================
-- 3. ملفات العميل (client_files) - 10 سجلات
-- =============================================
INSERT INTO client_files (client_id, project_id, file_name, file_path, file_type, file_size, folder_path, description, download_count) VALUES
(1, 1, 'المتطلبات_الفنية.pdf', '/uploads/client/1/project1/requirements.pdf', 'pdf', 2450000, '/project1', 'وثيقة متطلبات المشروع', 15),
(1, 1, 'شعار_الشركة.png', '/uploads/client/1/project1/logo.png', 'png', 450000, '/project1/images', 'شعار الشركة', 23),
(1, 3, 'تصميم_التطبيق.fig', '/uploads/client/1/project3/design.fig', 'fig', 8900000, '/project3/design', 'ملف تصميم التطبيق', 8),
(2, 4, 'تقرير_الفحص_الأولي.pdf', '/uploads/client/2/project4/initial-report.pdf', 'pdf', 1850000, '/project4', 'تقرير الفحص الأمني الأولي', 12),
(2, 5, 'نطاق_الاختبار.docx', '/uploads/client/2/project5/scope.docx', 'docx', 560000, '/project5', 'نطاق اختبار الاختراق', 5),
(3, 6, 'هيكل_قاعدة_البيانات.sql', '/uploads/client/3/project6/db-schema.sql', 'sql', 125000, '/project6', 'هيكل قاعدة البيانات', 7),
(3, 7, 'أسئلة_الاستشارة.pdf', '/uploads/client/3/project7/questions.pdf', 'pdf', 450000, '/project7', 'أسئلة استشارية', 4),
(4, 8, 'صور_الشركة.zip', '/uploads/client/4/project8/images.zip', 'zip', 12500000, '/project8', 'صور الشركة', 18),
(5, 9, 'عقد_الإلغاء.pdf', '/uploads/client/5/project9/cancellation.pdf', 'pdf', 120000, '/project9', 'عقد إلغاء المشروع', 3),
(6, 10, 'مسودة_الأرشفة.docx', '/uploads/client/6/project10/draft.docx', 'docx', 340000, '/project10', 'مسودة نظام الأرشفة', 2);

-- =============================================
-- 4. عقود العميل (client_contracts) - 10 سجلات
-- =============================================
INSERT INTO client_contracts (contract_code, client_id, project_id, contract_type, title, status, signed_by_client, signed_by_company, signed_at, start_date, end_date, value, file_path) VALUES
('CON-HOST-001', 1, 1, 'hosting', 'عقد استضافة موقع التجارة الإلكترونية', 'active', 1, 1, '2024-01-20 10:30:00', '2024-01-20', '2025-01-20', 15000.00, '/contracts/contract-001.pdf'),
('CON-STOR-001', 1, 2, 'storage', 'عقد تخزين البيانات السحابي', 'expired', 1, 1, '2023-11-15 14:20:00', '2023-11-15', '2024-01-15', 8000.00, '/contracts/contract-002.pdf'),
('CON-DEV-001', 1, 3, 'service', 'عقد تطوير تطبيق الجوال', 'active', 1, 1, '2024-02-20 11:30:00', '2024-02-20', '2024-05-30', 35000.00, '/contracts/contract-003.pdf'),
('CON-SEC-001', 2, 4, 'security', 'عقد الفحص الأمني', 'signed', 1, 0, '2024-02-05 09:15:00', '2024-02-05', '2024-03-15', 12000.00, '/contracts/contract-004.pdf'),
('CON-PENT-001', 2, 5, 'service', 'عقد اختبار الاختراق', 'under_review', 0, 0, NULL, NULL, NULL, 20000.00, NULL),
('CON-DEV-002', 3, 6, 'service', 'عقد تطوير نظام الموارد البشرية', 'active', 1, 1, '2024-01-25 11:00:00', '2024-01-25', '2024-06-20', 45000.00, '/contracts/contract-005.pdf'),
('CON-CONS-001', 3, 7, 'service', 'عقد الاستشارات التقنية', 'signed', 1, 1, '2024-03-12 10:00:00', '2024-03-12', '2024-04-10', 8000.00, '/contracts/contract-006.pdf'),
('CON-HOST-002', 4, 8, 'hosting', 'عقد استضافة موقع الشركة', 'expired', 1, 1, '2024-01-10 13:45:00', '2024-01-10', '2024-02-28', 3000.00, '/contracts/contract-007.pdf'),
('CON-HOST-003', 5, 9, 'hosting', 'عقد استضافة موقع تجريبي', 'cancelled', 0, 0, NULL, NULL, NULL, 2000.00, NULL),
('CON-STOR-002', 6, 10, 'storage', 'عقد أرشفة المستندات', 'draft', 0, 0, NULL, NULL, NULL, 6000.00, NULL);

-- =============================================
-- 5. فواتير العميل (client_invoices) - 10 سجلات
-- =============================================
INSERT INTO client_invoices (invoice_code, client_id, project_id, contract_id, invoice_type, title, amount, tax_amount, paid_amount, status, issue_date, due_date, paid_date) VALUES
('INV-2024-001', 1, 1, 1, 'monthly', 'فاتورة استضافة - يناير 2024', 1250.00, 187.50, 1437.50, 'paid', '2024-01-01', '2024-01-15', '2024-01-10'),
('INV-2024-002', 1, 1, 1, 'monthly', 'فاتورة استضافة - فبراير 2024', 1250.00, 187.50, 0.00, 'pending', '2024-02-01', '2024-02-15', NULL),
('INV-2024-003', 1, 2, 2, 'one_time', 'فاتورة التخزين السنوية', 8000.00, 1200.00, 9200.00, 'paid', '2023-11-01', '2023-11-15', '2023-11-10'),
('INV-2024-004', 1, 3, 3, 'one_time', 'فاتورة تطوير التطبيق - دفعة أولى', 15000.00, 2250.00, 17250.00, 'paid', '2024-02-20', '2024-03-05', '2024-02-28'),
('INV-2024-005', 2, 4, 4, 'one_time', 'فاتورة الفحص الأمني - دفعة أولى', 6000.00, 900.00, 6900.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-10'),
('INV-2024-006', 2, 4, 4, 'one_time', 'فاتورة الفحص الأمني - دفعة ثانية', 6000.00, 900.00, 0.00, 'pending', '2024-03-01', '2024-03-15', NULL),
('INV-2024-007', 3, 6, 6, 'monthly', 'فاتورة التطوير - يناير 2024', 5000.00, 750.00, 5750.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-05'),
('INV-2024-008', 3, 6, 6, 'monthly', 'فاتورة التطوير - فبراير 2024', 5000.00, 750.00, 5750.00, 'paid', '2024-03-01', '2024-03-15', '2024-03-05'),
('INV-2024-009', 4, 8, 8, 'one_time', 'فاتورة استضافة موقع الشركة', 3000.00, 450.00, 3450.00, 'paid', '2024-01-01', '2024-01-15', '2024-01-12'),
('INV-2024-010', 6, 10, 10, 'one_time', 'فاتورة أرشفة المستندات - دفعة أولى', 3000.00, 450.00, 0.00, 'sent', '2024-03-15', '2024-03-30', NULL);

-- =============================================
-- 6. مدفوعات العميل (client_payments) - 10 سجلات
-- =============================================
INSERT INTO client_payments (payment_code, client_id, invoice_id, amount, payment_method, status, payment_date, transaction_id) VALUES
('PAY-2024-001', 1, 1, 1437.50, 'card', 'completed', '2024-01-10 14:30:00', 'TXN123456789'),
('PAY-2024-002', 1, 3, 9200.00, 'bank_transfer', 'completed', '2023-11-10 09:15:00', 'TRF987654321'),
('PAY-2024-003', 1, 4, 17250.00, 'bank_transfer', 'completed', '2024-02-28 11:20:00', 'TRF456789123'),
('PAY-2024-004', 2, 5, 6900.00, 'card', 'completed', '2024-02-10 11:20:00', 'TXN789123456'),
('PAY-2024-005', 3, 7, 5750.00, 'bank_transfer', 'completed', '2024-02-05 13:45:00', 'TRF321654987'),
('PAY-2024-006', 3, 8, 5750.00, 'card', 'completed', '2024-03-05 10:00:00', 'TXN654987321'),
('PAY-2024-007', 4, 9, 3450.00, 'cash', 'completed', '2024-01-12 12:30:00', NULL),
('PAY-2024-008', 1, 2, 1437.50, 'bank_transfer', 'pending', NULL, NULL),
('PAY-2024-009', 2, 6, 6900.00, 'card', 'pending', NULL, NULL),
('PAY-2024-010', 6, 10, 3450.00, 'bank_transfer', 'pending', NULL, NULL);

-- =============================================
-- 7. تقارير العميل (client_reports) - 10 سجلات
-- =============================================
INSERT INTO client_reports (report_code, client_id, project_id, report_type, title, file_path, status, generated_at) VALUES
('RPT-PROG-001', 1, 1, 'progress', 'تقرير تقدم المشروع - يناير 2024', '/reports/progress-jan-2024.pdf', 'ready', '2024-02-01 09:00:00'),
('RPT-PROG-002', 1, 1, 'progress', 'تقرير تقدم المشروع - فبراير 2024', '/reports/progress-feb-2024.pdf', 'ready', '2024-03-01 10:30:00'),
('RPT-DEV-001', 1, 3, 'progress', 'تقرير تطور تطبيق الجوال - الأسبوع 4', '/reports/app-progress-week4.pdf', 'ready', '2024-03-15 14:00:00'),
('RPT-SEC-001', 2, 4, 'security', 'تقرير الثغرات الأمنية المكتشفة', '/reports/security-vulnerabilities.pdf', 'ready', '2024-02-20 11:15:00'),
('RPT-SEC-002', 2, 4, 'security', 'تقرير الفحص الأمني - المرحلة الأولى', '/reports/security-phase1.pdf', 'ready', '2024-03-01 13:30:00'),
('RPT-PERF-001', 3, 6, 'performance', 'تقرير أداء النظام', '/reports/performance-report.pdf', 'ready', '2024-02-28 09:45:00'),
('RPT-AUDIT-001', 3, 6, 'audit', 'تقرير تدقيق المتطلبات', '/reports/audit-requirements.pdf', 'ready', '2024-03-10 15:20:00'),
('RPT-HOST-001', 4, 8, 'summary', 'تقرير إحصائيات الموقع', '/reports/site-stats.pdf', 'ready', '2024-02-15 08:30:00'),
('RPT-STOR-001', 6, 10, 'backup', 'تقرير حالة النسخ الاحتياطي', '/reports/backup-status.pdf', 'generating', NULL),
('RPT-SUM-001', 5, 9, 'summary', 'ملخص إلغاء المشروع', '/reports/cancellation-summary.pdf', 'sent', '2024-03-05 12:00:00');

-- =============================================
-- 8. تذاكر الدعم (client_support_tickets) - 10 سجلات
-- =============================================
INSERT INTO client_support_tickets (ticket_code, client_id, project_id, subject, message, priority, status, category) VALUES
('TCK-2024-001', 1, 1, 'استفسار عن سرعة الموقع', 'السلام عليكم، أريد الاستفسار عن إمكانية زيادة سرعة الموقع', 'medium', 'resolved', 'technical'),
('TCK-2024-002', 1, 1, 'مشكلة في رفع الملفات', 'لا أستطيع رفع الملفات إلى لوحة التحكم', 'high', 'in_progress', 'technical'),
('TCK-2024-003', 1, 3, 'استفسار عن موعد التسليم', 'متى الموعد المتوقع لتسليم التطبيق؟', 'medium', 'waiting', 'general'),
('TCK-2024-004', 2, 4, 'استفسار عن الفاتورة', 'هل يمكن توضيح بنود الفاتورة رقم INV-2024-006؟', 'low', 'closed', 'billing'),
('TCK-2024-005', 2, 5, 'تأخير في المشروع', 'نحتاج تمديد الموعد النهائي للمشروع', 'high', 'open', 'general'),
('TCK-2024-006', 3, 6, 'طلب ميزة جديدة', 'نحتاج إضافة نظام تقارير متقدم', 'medium', 'in_progress', 'technical'),
('TCK-2024-007', 3, 7, 'استفسار عن الاستشارة', 'هل يمكن إضافة جلسة استشارية إضافية؟', 'low', 'resolved', 'general'),
('TCK-2024-008', 4, 8, 'مشكلة في تسجيل الدخول', 'لا أستطيع تسجيل الدخول للوحة التحكم', 'urgent', 'open', 'technical'),
('TCK-2024-009', 5, 9, 'استفسار عن الإلغاء', 'كيف يمكن استرداد المبلغ المدفوع؟', 'medium', 'waiting', 'billing'),
('TCK-2024-010', 6, 10, 'استفسار عن الأرشفة', 'هل يدعم النظام أرشفة الملفات الكبيرة؟', 'low', 'resolved', 'technical');

-- =============================================
-- 9. ردود التذاكر (client_ticket_replies) - 10 سجلات
-- =============================================
INSERT INTO client_ticket_replies (ticket_id, user_id, is_staff, message) VALUES
(1, 1, FALSE, 'وعليكم السلام، نعم يمكن زيادة السرعة. الرجاء تحديد الخطة المناسبة'),
(1, 2, TRUE, 'تمت زيادة السرعة إلى 100 Mbps، يرجى التأكد من الأداء'),
(2, 1, FALSE, 'تظهر رسالة خطأ عند رفع الملفات'),
(2, 2, TRUE, 'تم حل المشكلة، كان هناك خطأ في الصلاحيات'),
(3, 2, TRUE, 'الموعد المتوقع للتسليم هو 30 مايو 2024'),
(4, 2, TRUE, 'الفاتورة تشمل رسوم التطوير الشهرية وخدمات إضافية'),
(5, 2, TRUE, 'تم تحويل طلبكم للإدارة للنظر في التمديد'),
(6, 2, TRUE, 'سنقوم بإضافة نظام التقارير في الإصدار القادم'),
(7, 1, FALSE, 'نعم، نرغب في إضافة جلسة استشارية إضافية'),
(8, 2, TRUE, 'تم إعادة تعيين كلمة المرور، يرجى التحقق من بريدك الإلكتروني');

-- =============================================
-- 10. إشعارات العميل (client_notifications) - 10 سجلات
-- =============================================
INSERT INTO client_notifications (client_id, type, title, message, link, is_read) VALUES
(1, 'success', 'تم دفع الفاتورة', 'تم استلام دفعتك بنجاح بقيمة 1437.50 ر.س', '/billing', TRUE),
(1, 'info', 'تقرير جديد', 'تم إنشاء تقرير تقدم المشروع لشهر فبراير', '/reports', FALSE),
(1, 'warning', 'فاتورة مستحقة', 'لديك فاتورة مستحقة الدفع بقيمة 1437.50 ر.س', '/billing', FALSE),
(2, 'info', 'رد على التذكرة', 'تم الرد على تذكرتك رقم TCK-2024-004', '/support', TRUE),
(2, 'success', 'اكتمال الفحص', 'اكتمل الفحص الأمني للمشروع', '/projects', TRUE),
(3, 'info', 'تحديث المشروع', 'تم تحديث حالة مشروع نظام الموارد البشرية', '/projects', FALSE),
(4, 'warning', 'تنبيه أمني', 'تم اكتشاف محاولة دخول غير مصرح بها', '/security', FALSE),
(4, 'success', 'تم التفعيل', 'تم تفعيل موقعك بنجاح', '/hosting', TRUE),
(5, 'info', 'تأكيد الإلغاء', 'تم تأكيد إلغاء المشروع', '/projects', TRUE),
(6, 'info', 'مشروع جديد', 'تم إنشاء مشروع أرشفة المستندات', '/projects', FALSE);

-- =============================================
-- 11. سجل نشاطات العميل (client_activity_log) - 10 سجلات
-- =============================================
INSERT INTO client_activity_log (client_id, activity_type, target_type, target_id, description, ip_address, created_at) VALUES
(1, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'view', 'project', 1, 'عرض تفاصيل المشروع', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'download', 'report', 1, 'تحميل تقرير', '192.168.1.100', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.101', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(2, 'view', 'invoice', 6, 'عرض الفاتورة', '192.168.1.101', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(3, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.102', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'upload', 'file', 6, 'رفع ملف', '192.168.1.102', DATE_SUB(NOW(), INTERVAL 23 HOUR)),
(4, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.103', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 'view', 'hosting', 8, 'عرض معلومات الاستضافة', '192.168.1.103', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.104', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- =============================================
-- 12. طلبات الخدمة (client_service_requests) - 10 سجلات
-- =============================================
INSERT INTO client_service_requests (request_code, client_id, service_type, project_name, description, budget, deadline, status) VALUES
('REQ-2024-001', 7, 'hosting', 'موقع شركة جديد', 'نحتاج موقع تعريفي للشركة', 5000.00, '2024-05-01', 'pending'),
('REQ-2024-002', 8, 'development', 'تطبيق جوال', 'تطوير تطبيق جوال للمبيعات', 25000.00, '2024-07-01', 'under_review'),
('REQ-2024-003', 9, 'security', 'فحص أمني', 'فحص أمني شامل للأنظمة', 15000.00, '2024-06-01', 'approved'),
('REQ-2024-004', 10, 'storage', 'توسعة التخزين', 'نحتاج زيادة مساحة التخزين', 3000.00, '2024-04-15', 'pending'),
('REQ-2024-005', 1, 'pentest', 'اختبار اختراق', 'اختبار اختراق للموقع', 10000.00, '2024-05-15', 'under_review'),
('REQ-2024-006', 2, 'consultation', 'استشارة تقنية', 'استشارة حول تحسين الأداء', 4000.00, '2024-04-30', 'approved'),
('REQ-2024-007', 3, 'development', 'تطوير نظام محاسبة', 'نظام محاسبة متكامل', 35000.00, '2024-08-01', 'pending'),
('REQ-2024-008', 4, 'hosting', 'ترقية استضافة', 'ترقية خطة الاستضافة', 2000.00, '2024-04-20', 'approved'),
('REQ-2024-009', 5, 'security', 'تدقيق أمني', 'تدقيق أمني للموقع', 8000.00, '2024-05-30', 'rejected'),
('REQ-2024-010', 6, 'storage', 'أرشفة إضافية', 'إضافة مساحة أرشفة', 1500.00, '2024-04-25', 'pending');

-- =============================================
-- 13. إعدادات العميل (client_settings) - 10 سجلات
-- =============================================
INSERT INTO client_settings (client_id, language, notifications_email, notifications_sms, notifications_browser, theme, timezone) VALUES
(1, 'ar', TRUE, TRUE, TRUE, 'dark', 'Asia/Riyadh'),
(2, 'ar', TRUE, FALSE, TRUE, 'dark', 'Asia/Riyadh'),
(3, 'ar', TRUE, TRUE, FALSE, 'dark', 'Asia/Riyadh'),
(4, 'ar', TRUE, FALSE, TRUE, 'light', 'Asia/Riyadh'),
(5, 'ar', FALSE, FALSE, TRUE, 'dark', 'Asia/Riyadh'),
(6, 'ar', TRUE, TRUE, TRUE, 'dark', 'Asia/Riyadh'),
(7, 'ar', TRUE, FALSE, TRUE, 'dark', 'Asia/Riyadh'),
(8, 'ar', TRUE, FALSE, FALSE, 'light', 'Asia/Riyadh'),
(9, 'ar', TRUE, TRUE, TRUE, 'dark', 'Asia/Riyadh'),
(10, 'ar', TRUE, FALSE, TRUE, 'dark', 'Asia/Riyadh');

-- =============================================
-- 14. المرفقات (client_attachments) - 10 سجلات
-- =============================================
INSERT INTO client_attachments (client_id, target_type, target_id, file_name, file_path, file_size, file_type) VALUES
(1, 'ticket', 2, 'خطأ_الرفع.png', '/attachments/ticket2/error.png', 245000, 'png'),
(1, 'project', 1, 'ملاحظات_إضافية.pdf', '/attachments/project1/notes.pdf', 450000, 'pdf'),
(2, 'contract', 4, 'تعديلات_العقد.docx', '/attachments/contract4/amendments.docx', 125000, 'docx'),
(2, 'ticket', 4, 'صورة_الفاتورة.jpg', '/attachments/ticket4/invoice.jpg', 780000, 'jpg'),
(3, 'report', 7, 'ملف_التدقيق.xlsx', '/attachments/report7/audit.xlsx', 560000, 'xlsx'),
(3, 'project', 6, 'مخطط_قاعدة_البيانات.png', '/attachments/project6/db-diagram.png', 890000, 'png'),
(4, 'ticket', 8, 'شرح_المشكلة.txt', '/attachments/ticket8/description.txt', 12000, 'txt'),
(5, 'contract', 9, 'طلب_الإلغاء.pdf', '/attachments/contract9/cancellation.pdf', 340000, 'pdf'),
(6, 'project', 10, 'متطلبات_الأرشفة.docx', '/attachments/project10/requirements.docx', 230000, 'docx'),
(7, 'request', 1, 'وصف_المشروع.pdf', '/attachments/request1/project-desc.pdf', 180000, 'pdf');

-- =============================================
-- 15. إحصائيات العميل (client_stats) - 10 سجلات
-- =============================================
INSERT INTO client_stats (client_id, stat_date, projects_count, active_projects, completed_projects, files_count, files_size, invoices_total, invoices_paid, tickets_count, open_tickets) VALUES
(1, '2024-01-31', 3, 2, 1, 8, 11800000, 23000, 26250, 2, 0),
(1, '2024-02-29', 3, 2, 1, 12, 16300000, 24250, 26250, 3, 1),
(1, '2024-03-20', 3, 2, 1, 15, 19800000, 25500, 26250, 4, 1),
(2, '2024-02-29', 2, 2, 0, 5, 2410000, 12000, 6900, 2, 1),
(2, '2024-03-20', 2, 2, 0, 7, 3260000, 18000, 6900, 3, 2),
(3, '2024-02-29', 2, 2, 0, 4, 1850000, 5000, 5750, 2, 1),
(3, '2024-03-20', 2, 2, 0, 6, 2700000, 10000, 11500, 3, 1),
(4, '2024-02-29', 1, 0, 1, 2, 12500000, 3000, 3450, 1, 0),
(4, '2024-03-20', 1, 0, 1, 3, 12800000, 3000, 3450, 2, 1),
(5, '2024-03-20', 1, 0, 0, 1, 120000, 2000, 0, 1, 0);

-- =============================================
-- 16. خطط الاستضافة (hosting_plans) - 5 سجلات
-- =============================================
INSERT INTO hosting_plans (plan_code, plan_name, plan_type, price_monthly, price_yearly, disk_space, bandwidth, domains_limit, databases_limit, emails_limit, backup_type, features, is_popular) VALUES
('PLAN-BASIC-001', 'الخطة الأساسية', 'basic', 99, 990, 10240, 102400, 1, 5, 10, 'weekly', 'شهادة SSL مجانية، لوحة تحكم، نطاق مجاني للسنة الأولى', 0),
('PLAN-ADV-001', 'الخطة المتقدمة', 'advanced', 199, 1990, 51200, 512000, 5, 20, 50, 'daily', 'شهادة SSL مجانية، لوحة تحكم متقدمة، نطاق مجاني، نسخ احتياطي يومي، دعم فوري', 1),
('PLAN-PRO-001', 'الخطة الاحترافية', 'professional', 299, 2990, 102400, 1048576, 0, 0, 0, 'realtime', 'شهادة SSL متقدمة، لوحة تحكم مخصصة، نطاق مجاني، نسخ احتياطي فوري، دعم 24/7، تسريع CDN، IP مخصص', 0),
('PLAN-BUS-001', 'خطة الأعمال', 'custom', 499, 4990, 204800, 2097152, 0, 0, 0, 'realtime', 'جميع مزايا الخطة الاحترافية + خادم مخصص + دعم VIP', 0),
('PLAN-ECOMM-001', 'خطة المتاجر', 'custom', 399, 3990, 153600, 1572864, 10, 50, 100, 'daily', 'مخصصة للمتاجر الإلكترونية، شهادة SSL متقدمة، دعم فوري، أدوات تحسين محركات البحث', 0);

-- =============================================
-- 17. نطاقات العملاء (client_domains) - 10 سجلات
-- =============================================
INSERT INTO client_domains (client_id, project_id, domain_name, domain_type, registration_date, expiry_date, status, ssl_status) VALUES
(1, 1, 'ecommerce-store.com', 'primary', '2024-01-15', '2025-01-15', 'active', 'active'),
(1, 3, 'mobileapp.com', 'primary', '2024-02-20', '2025-02-20', 'active', 'pending'),
(2, 4, 'security-scan.net', 'primary', '2024-02-01', '2025-02-01', 'active', 'active'),
(2, 5, 'pentest-lab.com', 'secondary', '2024-03-01', '2025-03-01', 'pending', 'none'),
(3, 6, 'hr-system.org', 'primary', '2024-01-20', '2025-01-20', 'active', 'active'),
(4, 8, 'corporate-site.com', 'primary', '2024-01-05', '2025-01-05', 'active', 'active'),
(5, 9, 'test-project.com', 'primary', '2024-02-01', '2024-03-01', 'expired', 'expired'),
(6, 10, 'archive-system.com', 'parked', '2024-03-15', '2025-03-15', 'pending', 'none'),
(7, NULL, 'new-company.com', 'primary', '2024-03-10', '2025-03-10', 'active', 'pending'),
(8, NULL, 'consulting.sa', 'primary', '2024-03-01', '2025-03-01', 'active', 'active');

-- =============================================
-- 18. مواقع الاستضافة (hosting_sites) - 10 سجلات
-- =============================================
INSERT INTO hosting_sites (client_id, project_id, plan_id, domain_id, site_name, document_root, php_version, database_name, ftp_username, status, setup_status, activated_at, expires_at) VALUES
(1, 1, 2, 1, 'موقع التجارة الإلكترونية', '/home/client1/ecommerce-store.com', '8.1', 'db_ecommerce', 'ftp_ecommerce', 'active', 'completed', '2024-01-20 10:30:00', '2025-01-20'),
(1, 3, 2, 2, 'تطبيق الجوال', '/home/client1/mobileapp.com', '8.0', 'db_mobileapp', 'ftp_mobile', 'active', 'completed', '2024-02-25 14:20:00', '2025-02-25'),
(2, 4, 1, 3, 'موقع الفحص الأمني', '/home/client2/security-scan.net', '7.4', 'db_security', 'ftp_security', 'active', 'completed', '2024-02-10 09:15:00', '2025-02-10'),
(2, 5, 2, 4, 'بوابة اختبار الاختراق', '/home/client2/pentest-lab.com', '8.1', NULL, 'ftp_pentest', 'pending', 'in_progress', NULL, NULL),
(3, 6, 3, 5, 'نظام الموارد البشرية', '/home/client3/hr-system.org', '8.1', 'db_hr', 'ftp_hr', 'active', 'completed', '2024-01-25 11:45:00', '2025-01-25'),
(4, 8, 1, 6, 'موقع الشركة', '/home/client4/corporate-site.com', '7.4', 'db_corporate', 'ftp_corporate', 'active', 'completed', '2024-01-10 13:30:00', '2025-01-10'),
(5, 9, 1, 7, 'موقع تجريبي', '/home/client5/test-project.com', '7.4', NULL, 'ftp_test', 'suspended', 'failed', NULL, NULL),
(6, 10, 1, 8, 'نظام الأرشفة', '/home/client6/archive-system.com', '8.1', NULL, 'ftp_archive', 'pending', 'pending', NULL, NULL),
(7, NULL, 2, 9, 'موقع شركة جديد', '/home/client7/new-company.com', '8.1', 'db_newcompany', 'ftp_newcompany', 'active', 'completed', '2024-03-12 09:00:00', '2025-03-12'),
(8, NULL, 1, 10, 'موقع استشارات', '/home/client8/consulting.sa', '7.4', 'db_consulting', 'ftp_consulting', 'active', 'completed', '2024-03-05 15:30:00', '2025-03-05');

-- =============================================
-- 19. إحصائيات المواقع (hosting_stats) - 10 سجلات
-- =============================================
INSERT INTO hosting_stats (site_id, disk_usage, bandwidth_usage, databases_count, emails_count, daily_visitors, monthly_visitors, cpu_usage, memory_usage, stat_date) VALUES
(1, 2450, 15200, 3, 8, 1250, 37500, 32.5, 41.2, CURDATE()),
(1, 2500, 15800, 3, 8, 1300, 39000, 33.1, 42.0, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 2550, 16300, 3, 8, 1350, 40500, 33.8, 42.5, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(2, 890, 5600, 1, 2, 450, 13500, 18.3, 22.7, CURDATE()),
(3, 560, 3200, 2, 4, 320, 9600, 12.8, 18.5, CURDATE()),
(5, 1850, 12400, 4, 12, 980, 29400, 28.9, 35.6, CURDATE()),
(6, 780, 4300, 2, 5, 560, 16800, 15.6, 20.3, CURDATE()),
(9, 320, 2100, 1, 3, 120, 3600, 8.5, 12.4, CURDATE()),
(10, 450, 2800, 1, 3, 180, 5400, 10.2, 14.8, CURDATE());

-- =============================================
-- 20. حسابات FTP (hosting_ftp_accounts) - 8 سجلات
-- =============================================
INSERT INTO hosting_ftp_accounts (site_id, username, password_hash, home_directory, permissions) VALUES
(1, 'ecommerce_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/ecommerce-store.com', 'full'),
(2, 'mobile_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/mobileapp.com', 'full'),
(3, 'security_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client2/security-scan.net', 'full'),
(4, 'pentest_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client2/pentest-lab.com', 'write'),
(5, 'hr_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client3/hr-system.org', 'full'),
(6, 'corporate_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client4/corporate-site.com', 'full'),
(9, 'newcompany_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client7/new-company.com', 'full'),
(10, 'consulting_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client8/consulting.sa', 'full');

-- =============================================
-- 21. قواعد البيانات (hosting_databases) - 10 سجلات
-- =============================================
INSERT INTO hosting_databases (site_id, db_name, db_user, db_password, db_type, db_size) VALUES
(1, 'db_ecommerce', 'user_ecommerce', 'pass123456', 'mysql', 1850),
(1, 'db_ecommerce_logs', 'user_ecommerce_logs', 'log789012', 'mysql', 420),
(1, 'db_ecommerce_cache', 'user_ecommerce_cache', 'cache345678', 'mysql', 180),
(2, 'db_mobileapp', 'user_mobile', 'pass789012', 'mysql', 560),
(3, 'db_security', 'user_security', 'pass345678', 'mysql', 380),
(5, 'db_hr', 'user_hr', 'pass567890', 'mysql', 1240),
(5, 'db_hr_reports', 'user_hr_reports', 'report123456', 'mysql', 320),
(6, 'db_corporate', 'user_corporate', 'pass123789', 'mysql', 490),
(9, 'db_newcompany', 'user_newcompany', 'newpass123', 'mysql', 210),
(10, 'db_consulting', 'user_consulting', 'consult456', 'mysql', 280);

-- =============================================
-- إعادة تفعيل التحقق من المفاتيح الأجنبية
-- =============================================
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- التحقق من البيانات
-- =============================================
SELECT '✅ تم إضافة البيانات التجريبية بنجاح' as 'النتيجة';

SELECT 
    (SELECT COUNT(*) FROM client_clients) as 'العملاء',
    (SELECT COUNT(*) FROM client_projects) as 'المشاريع',
    (SELECT COUNT(*) FROM client_contracts) as 'العقود',
    (SELECT COUNT(*) FROM client_invoices) as 'الفواتير',
    (SELECT COUNT(*) FROM client_support_tickets) as 'التذاكر',
    (SELECT COUNT(*) FROM client_reports) as 'التقارير',
    (SELECT COUNT(*) FROM hosting_sites) as 'مواقع الاستضافة';
/*

DELETE FROM client_projects;
-- إضافة 10 مشاريع جديدة
INSERT INTO client_projects (
    client_id, 
    project_code, 
    project_name, 
    project_type, 
    description, 
    status, 
    stage, 
    priority, 
    start_date, 
    deadline, 
    progress, 
    budget, 
    paid_amount, 
    manager_name,
    created_at
) VALUES 
-- مشروع 1
(1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'متجر إلكتروني متكامل مع نظام إدارة محتوى', 'in_progress', 4, 'high', '2024-01-15', '2024-04-15', 65, 15000.00, 7500.00, 'أحمد العلي', NOW()),

-- مشروع 2
(1, 'PRJ-STOR-001', 'نظام تخزين العملاء', 'storage', 'نظام تخزين سحابي لبيانات العملاء', 'completed', 6, 'medium', '2023-11-10', '2024-01-10', 100, 8000.00, 8000.00, 'سارة الأحمد', NOW()),

-- مشروع 3
(1, 'PRJ-DEV-001', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال لأنظمة iOS و Android', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', 35, 35000.00, 15000.00, 'محمد العنزي', NOW()),

-- مشروع 4
(1, 'PRJ-SEC-001', 'تدقيق أمني للمتجر', 'security', 'تدقيق أمني شامل لموقع التجارة الإلكترونية', 'testing', 5, 'critical', '2024-03-01', '2024-04-15', 90, 12000.00, 8000.00, 'خالد الرشيد', NOW()),

-- مشروع 5
(2, 'PRJ-HOST-002', 'بوابة الخدمات الإلكترونية', 'hosting', 'استضافة بوابة الخدمات الحكومية', 'completed', 6, 'high', '2023-12-10', '2024-02-28', 100, 25000.00, 25000.00, 'أحمد العلي', NOW()),

-- مشروع 6
(2, 'PRJ-STOR-002', 'أرشفة المستندات الرسمية', 'storage', 'نظام أرشفة إلكتروني للمستندات الرسمية', 'contract_pending', 3, 'medium', '2024-03-15', '2024-06-15', 0, 15000.00, 0.00, 'نورة الدوسري', NOW()),

-- مشروع 7
(2, 'PRJ-PENT-001', 'اختبار اختراق', 'pentest', 'اختبار اختراق للتطبيق المصرفي', 'in_progress', 4, 'critical', '2024-02-20', '2024-04-20', 45, 30000.00, 10000.00, 'عبدالله المطيري', NOW()),

-- مشروع 8
(3, 'PRJ-DEV-002', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل لإدارة الموارد البشرية', 'pending', 1, 'medium', '2024-04-01', '2024-08-01', 0, 45000.00, 0.00, 'فاطمة الزهراني', NOW()),

-- مشروع 9
(3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'low', '2024-03-10', '2024-04-10', 20, 8000.00, 2000.00, 'سامي الحربي', NOW()),

-- مشروع 10
(4, 'PRJ-HOST-003', 'موقع الشركة التعريفي', 'hosting', 'موقع تعريفي بسيط للشركة', 'completed', 6, 'low', '2024-02-01', '2024-02-20', 100, 3000.00, 3000.00, 'منى الغامدي', NOW());

/*
DELETE FROM client_projects;
-- =============================================
-- 2. إضافة المزيد من المشاريع
-- =============================================
INSERT INTO client_projects (client_id, project_code, project_name, project_type, description, status, stage, priority, start_date, deadline, progress, budget, paid_amount, manager_name) VALUES
(1, 'PRJ-DEV-002', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال للمتجر الإلكتروني لأنظمة iOS و Android', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', 35, 35000.00, 15000.00, 'سارة الأحمد'),
(1, 'PRJ-SEC-002', 'تدقيق أمني شامل', 'security', 'تدقيق أمني شامل لجميع أنظمة الشركة', 'testing', 5, 'critical', '2024-03-01', '2024-04-15', 90, 18000.00, 12000.00, 'خالد الرشيد'),
(2, 'PRJ-HOST-003', 'بوابة الخدمات الإلكترونية', 'hosting', 'استضافة بوابة الخدمات الحكومية', 'completed', 6, 'high', '2023-12-10', '2024-02-28', 100, 25000.00, 25000.00, 'أحمد العلي'),
(2, 'PRJ-STOR-003', 'أرشفة المستندات الرسمية', 'storage', 'نظام أرشفة إلكتروني للمستندات الرسمية', 'contract_pending', 3, 'medium', '2024-03-15', '2024-06-15', 0, 15000.00, 0.00, 'نورة الدوسري'),
(3, 'PRJ-DEV-003', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل لإدارة الموارد البشرية', 'pending', 1, 'medium', '2024-04-01', '2024-08-01', 0, 45000.00, 0.00, 'محمد العنزي'),
(3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'low', '2024-03-10', '2024-04-10', 20, 8000.00, 2000.00, 'فاطمة الزهراني'),
(4, 'PRJ-HOST-004', 'موقع المؤتمر السنوي', 'hosting', 'استضافة موقع المؤتمر السنوي للشركة', 'completed', 6, 'medium', '2024-01-05', '2024-02-28', 100, 5000.00, 5000.00, 'سامي الحربي'),
(4, 'PRJ-PENT-002', 'اختبار اختراق التطبيق المصرفي', 'pentest', 'اختبار اختراق لتطبيق الخدمات المصرفية', 'in_progress', 4, 'critical', '2024-02-20', '2024-04-20', 60, 30000.00, 15000.00, 'عبدالله المطيري'),
(5, 'PRJ-STOR-004', 'تخزين سحابي للفيديو', 'storage', 'منصة تخزين سحابي لمقاطع الفيديو', 'cancelled', 7, 'low', '2024-01-20', '2024-03-20', 30, 20000.00, 5000.00, 'ريم القحطاني'),


/*
-- أولاً: حذف البيانات الموجودة للبدء من جديد
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM hosting_sites;
DELETE FROM client_domains;
DELETE FROM hosting_plans;
DELETE FROM client_projects;
DELETE FROM client_clients WHERE id > 5;
SET FOREIGN_KEY_CHECKS = 1;



INSERT INTO client_projects (id, client_id, project_code, project_name, project_type, status, stage, start_date, created_at) VALUES
(1, 1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'active', 4, '2024-01-15', NOW()),
(2, 1, 'PRJ-DEV-001', 'تطبيق الجوال', 'development', 'in_progress', 4, '2024-02-20', NOW()),
(3, 2, 'PRJ-SEC-001', 'موقع الفحص الأمني', 'security', 'active', 5, '2024-02-01', NOW()),
(4, 2, 'PRJ-PENT-001', 'بوابة اختبار الاختراق', 'pentest', 'pending', 3, '2024-03-01', NOW()),
(5, 3, 'PRJ-DEV-002', 'نظام الموارد البشرية', 'development', 'active', 4, '2024-01-20', NOW()),

 INSERT INTO hosting_plans (id, plan_code, plan_name, plan_type, price_monthly, price_yearly, disk_space, bandwidth, domains_limit, databases_limit, emails_limit, is_popular) VALUES
(1, 'PLAN-BASIC-001', 'الخطة الأساسية', 'basic', 99, 990, 10240, 102400, 1, 5, 10, 0),
(2, 'PLAN-ADV-001', 'الخطة المتقدمة', 'advanced', 199, 1990, 51200, 512000, 5, 20, 50, 1),
(3, 'PLAN-PRO-001', 'الخطة الاحترافية', 'professional', 299, 2990, 102400, 1048576, 0, 0, 0, 0);

INSERT INTO client_domains (id, client_id, project_id, domain_name, domain_type, registration_date, expiry_date, status, ssl_status) VALUES
(1, 1, 1, 'ecommerce.com', 'primary', '2024-01-15', '2025-01-15', 'active', 'active'),
(2, 1, 8, 'mobileapp.com', 'primary', '2024-02-20', '2025-02-20', 'active', 'pending'),
(3, 2, 3, 'security-scan.com', 'primary', '2024-02-01', '2025-02-01', 'active', 'active'),
(4, 2, 4, 'pentest.com', 'secondary', '2024-03-01', '2025-03-01', 'pending', 'none'),
(5, 3, 5, 'hr-system.com', 'primary', '2024-01-20', '2025-01-20', 'active', 'active'),
(6, 4, 6, 'corporate-site.com', 'primary', '2024-01-05', '2025-01-05', 'active', 'active'),
(7, 4, 7, 'conference.com', 'parked', '2024-01-10', '2024-12-31', 'expired', 'expired');


INSERT INTO hosting_sites (client_id, project_id, plan_id, domain_id, site_name, document_root, php_version, status, setup_status, activated_at, expires_at) VALUES
(1, 1, 2, 1, 'موقع التجارة الإلكترونية', '/home/client1/ecommerce.com', '8.1', 'active', 'completed', '2024-01-20 10:30:00', '2025-01-20'),
(1, 8, 2, 2, 'تطبيق الجوال', '/home/client1/mobileapp.com', '8.0', 'active', 'completed', '2024-02-25 14:20:00', '2025-02-25'),
(2, 3, 1, 3, 'موقع الفحص الأمني', '/home/client2/security-scan.com', '7.4', 'active', 'completed', '2024-02-10 09:15:00', '2025-02-10'),
(2, 4, 2, 4, 'بوابة اختبار الاختراق', '/home/client2/pentest.com', '8.1', 'pending', 'in_progress', NULL, NULL),
(3, 5, 3, 5, 'نظام الموارد البشرية', '/home/client3/hr-system.com', '8.1', 'active', 'completed', '2024-01-25 11:45:00', '2025-01-25'),
(4, 6, 1, 6, 'موقع الشركة', '/home/client4/corporate-site.com', '7.4', 'active', 'completed', '2024-01-10 13:30:00', '2025-01-10'),
(4, 7, 1, 7, 'موقع المؤتمر', '/home/client4/conference.com', '7.4', 'suspended', 'failed', NULL, NULL);

-- التحقق من أن كل شيء تم بشكل صحيح
SELECT 
    h.id, 
    h.site_name, 
    d.domain_name, 
    p.project_name,
    hp.plan_name,
    h.status
FROM hosting_sites h
LEFT JOIN client_domains d ON h.domain_id = d.id
LEFT JOIN client_projects p ON h.project_id = p.id
LEFT JOIN hosting_plans hp ON h.plan_id = hp.id;


/*
-- إضافة مواقع استضافة
INSERT INTO hosting_sites (client_id, project_id, plan_id, domain_id, site_name, document_root, php_version, database_name, database_user, database_password, ftp_username, ftp_password, status, setup_status, activated_at, expires_at) VALUES
(1, 1, 2, 1, 'موقع التجارة الإلكترونية', '/home/client1/ecommerce.com', '8.1', 'db_ecommerce', 'user_ecommerce', 'pass123456', 'ftp_ecommerce', 'ftp123456', 'active', 'completed', '2024-01-20 10:30:00', '2025-01-20'),
(1, 1, 2, 2, 'تطبيق الجوال', '/home/client1/mobileapp.com', '8.0', 'db_mobileapp', 'user_mobile', 'pass789012', 'ftp_mobile', 'ftp789012', 'active', 'completed', '2024-02-25 14:20:00', '2025-02-25'),
(2, 3, 1, 3, 'موقع الفحص الأمني', '/home/client2/security-scan.com', '7.4', 'db_security', 'user_security', 'pass345678', 'ftp_security', 'ftp345678', 'active', 'completed', '2024-02-10 09:15:00', '2025-02-10'),
(2, 4, 2, 4, 'بوابة اختبار الاختراق', '/home/client2/pentest.com', '8.1', NULL, NULL, NULL, 'ftp_pentest', 'ftp901234', 'pending', 'in_progress', NULL, NULL),
(3, 5, 3, 5, 'نظام الموارد البشرية', '/home/client3/hr-system.com', '8.1', 'db_hr', 'user_hr', 'pass567890', 'ftp_hr', 'ftp567890', 'active', 'completed', '2024-01-25 11:45:00', '2025-01-25'),
(4, 6, 1, 6, 'موقع الشركة', '/home/client4/corporate-site.com', '7.4', 'db_corporate', 'user_corporate', 'pass123789', 'ftp_corporate', 'ftp123789', 'active', 'completed', '2024-01-10 13:30:00', '2025-01-10'),
(4, 7, 1, 7, 'موقع المؤتمر', '/home/client4/conference.com', '7.4', NULL, NULL, NULL, 'ftp_conference', 'ftp456789', 'suspended', 'failed', NULL, NULL);

-- إضافة إحصائيات
INSERT INTO hosting_stats (site_id, disk_usage, bandwidth_usage, databases_count, emails_count, daily_visitors, monthly_visitors, cpu_usage, memory_usage, stat_date) VALUES
(1, 2450, 15200, 3, 8, 1250, 37500, 32.5, 41.2, CURDATE()),
(2, 890, 5600, 1, 2, 450, 13500, 18.3, 22.7, CURDATE()),
(3, 560, 3200, 2, 4, 320, 9600, 12.8, 18.5, CURDATE()),
(4, 120, 450, 0, 0, 0, 0, 0, 0, CURDATE()),
(5, 1850, 12400, 4, 12, 980, 29400, 28.9, 35.6, CURDATE()),
(6, 780, 4300, 2, 5, 560, 16800, 15.6, 20.3, CURDATE());

-- إضافة حسابات FTP
INSERT INTO hosting_ftp_accounts (site_id, username, password_hash, home_directory) VALUES
(1, 'ecommerce_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/ecommerce.com'),
(2, 'mobile_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/mobileapp.com'),
(3, 'security_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client2/security-scan.com'),
(5, 'hr_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client3/hr-system.com'),
(6, 'corporate_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client4/corporate-site.com');

-- إضافة قواعد بيانات
INSERT INTO hosting_databases (site_id, db_name, db_user, db_password, db_type, db_size) VALUES
(1, 'db_ecommerce', 'user_ecommerce', 'pass123456', 'mysql', 1850),
(1, 'db_ecommerce_logs', 'user_ecommerce_logs', 'log789012', 'mysql', 420),
(2, 'db_mobileapp', 'user_mobile', 'pass789012', 'mysql', 560),
(3, 'db_security', 'user_security', 'pass345678', 'mysql', 380),
(5, 'db_hr', 'user_hr', 'pass567890', 'mysql', 1240),
(5, 'db_hr_reports', 'user_hr_reports', 'report123456', 'mysql', 320),
(6, 'db_corporate', 'user_corporate', 'pass123789', 'mysql', 490);
/*
-- =============================================
-- جداول استضافة المواقع
-- =============================================

-- إضافة نطاقات للعملاء
INSERT INTO client_domains (client_id, project_id, domain_name, domain_type, registration_date, expiry_date, status, ssl_status) VALUES
(1, 1, 'ecommerce.com', 'primary', '2024-01-15', '2025-01-15', 'active', 'active'),
(1, 1, 'mobileapp.com', 'primary', '2024-02-20', '2025-02-20', 'active', 'pending'),
(2, 3, 'security-scan.com', 'primary', '2024-02-01', '2025-02-01', 'active', 'active'),
(2, 4, 'pentest.com', 'secondary', '2024-03-01', '2025-03-01', 'pending', 'none'),
(3, 5, 'hr-system.com', 'primary', '2024-01-20', '2025-01-20', 'active', 'active'),
(4, 1, 'corporate-site.com', 'primary', '2024-01-05', '2025-01-05', 'active', 'active'),
(4, 7, 'conference.com', 'parked', '2024-01-10', '2024-12-31', 'expired', 'expired');


-- =============================================
-- 📥 البيانات التجريبية
-- =============================================

-- خطط الاستضافة
INSERT INTO hosting_plans (plan_code, plan_name, plan_type, price_monthly, price_yearly, disk_space, bandwidth, domains_limit, databases_limit, emails_limit, backup_type, features, is_popular) VALUES
('PLAN-BASIC-001', 'الخطة الأساسية', 'basic', 99, 990, 10240, 102400, 1, 5, 10, 'weekly', 'شهادة SSL مجانية, لوحة تحكم, نطاق مجاني للسنة الأولى', FALSE),
('PLAN-ADV-001', 'الخطة المتقدمة', 'advanced', 199, 1990, 51200, 512000, 5, 20, 50, 'daily', 'شهادة SSL مجانية, لوحة تحكم متقدمة, نطاق مجاني, نسخ احتياطي يومي, دعم فوري', TRUE),
('PLAN-PRO-001', 'الخطة الاحترافية', 'professional', 299, 2990, 102400, 1048576, 0, 0, 0, 'realtime', 'شهادة SSL متقدمة, لوحة تحكم مخصصة, نطاق مجاني, نسخ احتياطي فوري, دعم 24/7, تسريع CDN, IP مخصص', FALSE);

/*
-- =============================================
-- 2. إضافة المزيد من المشاريع
-- =============================================
INSERT INTO client_projects (client_id, project_code, project_name, project_type, description, status, stage, priority, start_date, deadline, progress, budget, paid_amount, manager_name) VALUES
(1, 'PRJ-DEV-002', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال للمتجر الإلكتروني لأنظمة iOS و Android', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', 35, 35000.00, 15000.00, 'سارة الأحمد'),
(1, 'PRJ-SEC-002', 'تدقيق أمني شامل', 'security', 'تدقيق أمني شامل لجميع أنظمة الشركة', 'testing', 5, 'critical', '2024-03-01', '2024-04-15', 90, 18000.00, 12000.00, 'خالد الرشيد'),
(2, 'PRJ-HOST-003', 'بوابة الخدمات الإلكترونية', 'hosting', 'استضافة بوابة الخدمات الحكومية', 'completed', 6, 'high', '2023-12-10', '2024-02-28', 100, 25000.00, 25000.00, 'أحمد العلي'),
(2, 'PRJ-STOR-003', 'أرشفة المستندات الرسمية', 'storage', 'نظام أرشفة إلكتروني للمستندات الرسمية', 'contract_pending', 3, 'medium', '2024-03-15', '2024-06-15', 0, 15000.00, 0.00, 'نورة الدوسري'),
(3, 'PRJ-DEV-003', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل لإدارة الموارد البشرية', 'pending', 1, 'medium', '2024-04-01', '2024-08-01', 0, 45000.00, 0.00, 'محمد العنزي'),
(3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'low', '2024-03-10', '2024-04-10', 20, 8000.00, 2000.00, 'فاطمة الزهراني'),
(4, 'PRJ-HOST-004', 'موقع المؤتمر السنوي', 'hosting', 'استضافة موقع المؤتمر السنوي للشركة', 'completed', 6, 'medium', '2024-01-05', '2024-02-28', 100, 5000.00, 5000.00, 'سامي الحربي'),
(4, 'PRJ-PENT-002', 'اختبار اختراق التطبيق المصرفي', 'pentest', 'اختبار اختراق لتطبيق الخدمات المصرفية', 'in_progress', 4, 'critical', '2024-02-20', '2024-04-20', 60, 30000.00, 15000.00, 'عبدالله المطيري'),
(5, 'PRJ-STOR-004', 'تخزين سحابي للفيديو', 'storage', 'منصة تخزين سحابي لمقاطع الفيديو', 'cancelled', 7, 'low', '2024-01-20', '2024-03-20', 30, 20000.00, 5000.00, 'ريم القحطاني'),

-- =============================================
-- 3. إضافة المزيد من الملفات
-- =============================================
INSERT INTO client_files (client_id, project_id, file_name, file_path, file_type, file_size, folder_path, description, download_count) VALUES
(1, 8, 'متطلبات_تطبيق_الجوال.pdf', '/uploads/client/1/project8/requirements.pdf', 'pdf', 3200000, '/project8', 'وثيقة متطلبات تطبيق الجوال', 12),
(1, 8, 'تصميم_الواجهات.fig', '/uploads/client/1/project8/design.fig', 'fig', 8900000, '/project8/design', 'ملف تصميم واجهات المستخدم', 5),
(1, 9, 'تقرير_التدقيق_الأولي.pdf', '/uploads/client/1/project9/audit-initial.pdf', 'pdf', 2100000, '/project9', 'تقرير التدقيق الأمني الأولي', 8),
(2, 10, 'شعار_الجهة.png', '/uploads/client/2/project10/logo.png', 'png', 350000, '/project10', 'شعار الجهة الحكومية', 15),
(2, 11, 'عقد_التخزين.docx', '/uploads/client/2/project11/contract.docx', 'docx', 560000, '/project11', 'مسودة عقد التخزين', 3),
(3, 12, 'دراسة_الجدوى.pdf', '/uploads/client/3/project12/feasibility.pdf', 'pdf', 1800000, '/project12', 'دراسة جدوى المشروع', 6),
(3, 13, 'أسئلة_الاستشارة.docx', '/uploads/client/3/project13/questions.docx', 'docx', 450000, '/project13', 'أسئلة للاستشارة التقنية', 4),
(4, 14, 'ملفات_المؤتمر.zip', '/uploads/client/4/project14/conference.zip', 'zip', 15000000, '/project14', 'جميع ملفات المؤتمر', 25),
(4, 15, 'تقرير_الاختبار.pdf', '/uploads/client/4/project15/test-report.pdf', 'pdf', 4200000, '/project15', 'تقرير اختبار الاختراق', 10),
(5, 16, 'إلغاء_المشروع.pdf', '/uploads/client/5/project16/cancellation.pdf', 'pdf', 120000, '/project16', 'خطاب إلغاء المشروع', 2);

-- =============================================
-- 4. إضافة المزيد من العقود
-- =============================================
INSERT INTO client_contracts (contract_code, client_id, project_id, contract_type, title, status, signed_by_client, signed_by_company, signed_at, start_date, end_date, value, file_path) VALUES
('CON-DEV-002', 1, 8, 'service', 'عقد تطوير تطبيق الجوال', 'active', TRUE, TRUE, '2024-02-20 11:30:00', '2024-02-20', '2024-05-30', 35000.00, '/contracts/contract-005.pdf'),
('CON-SEC-002', 1, 9, 'security', 'عقد التدقيق الأمني الشامل', 'signed', TRUE, FALSE, '2024-03-05 09:15:00', '2024-03-05', '2024-04-15', 18000.00, '/contracts/contract-006.pdf'),
('CON-HOST-003', 2, 10, 'hosting', 'عقد استضافة البوابة الإلكترونية', 'expired', TRUE, TRUE, '2023-12-15 14:20:00', '2023-12-15', '2024-02-28', 25000.00, '/contracts/contract-007.pdf'),
('CON-STOR-003', 2, 11, 'storage', 'عقد أرشفة المستندات', 'under_review', FALSE, FALSE, NULL, NULL, NULL, 15000.00, NULL),
('CON-DEV-003', 3, 12, 'service', 'عقد تطوير نظام الموارد البشرية', 'draft', FALSE, FALSE, NULL, NULL, NULL, 45000.00, NULL),
('CON-CONS-001', 3, 13, 'service', 'عقد الاستشارات التقنية', 'signed', TRUE, TRUE, '2024-03-12 10:00:00', '2024-03-12', '2024-04-10', 8000.00, '/contracts/contract-008.pdf'),
('CON-HOST-004', 4, 14, 'hosting', 'عقد استضافة موقع المؤتمر', 'expired', TRUE, TRUE, '2024-01-10 13:45:00', '2024-01-10', '2024-02-28', 5000.00, '/contracts/contract-009.pdf'),
('CON-PENT-002', 4, 15, 'service', 'عقد اختبار الاختراق', 'active', TRUE, TRUE, '2024-02-25 16:30:00', '2024-02-25', '2024-04-20', 30000.00, '/contracts/contract-010.pdf');

-- =============================================
-- 5. إضافة المزيد من الفواتير
-- =============================================
INSERT INTO client_invoices (invoice_code, client_id, project_id, contract_id, invoice_type, title, amount, tax_amount, paid_amount, status, issue_date, due_date, paid_date) VALUES
('INV-2024-009', 1, 8, 6, 'one_time', 'فاتورة تطوير التطبيق - دفعة أولى', 15000.00, 2250.00, 17250.00, 'paid', '2024-02-20', '2024-03-05', '2024-02-28'),
('INV-2024-010', 1, 8, 6, 'one_time', 'فاتورة تطوير التطبيق - دفعة ثانية', 15000.00, 2250.00, 0.00, 'pending', '2024-03-20', '2024-04-05', NULL),
('INV-2024-011', 1, 9, 7, 'one_time', 'فاتورة التدقيق الأمني - دفعة أولى', 12000.00, 1800.00, 13800.00, 'paid', '2024-03-05', '2024-03-20', '2024-03-15'),
('INV-2024-012', 1, 9, 7, 'one_time', 'فاتورة التدقيق الأمني - دفعة ثانية', 6000.00, 900.00, 0.00, 'pending', '2024-04-01', '2024-04-15', NULL),
('INV-2024-013', 2, 10, 8, 'monthly', 'فاتورة استضافة - ديسمبر 2023', 2500.00, 375.00, 2875.00, 'paid', '2023-12-01', '2023-12-15', '2023-12-10'),
('INV-2024-014', 2, 10, 8, 'monthly', 'فاتورة استضافة - يناير 2024', 2500.00, 375.00, 2875.00, 'paid', '2024-01-01', '2024-01-15', '2024-01-08'),
('INV-2024-015', 2, 10, 8, 'monthly', 'فاتورة استضافة - فبراير 2024', 2500.00, 375.00, 2875.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-12'),
('INV-2024-016', 3, 13, 10, 'one_time', 'فاتورة الاستشارات التقنية', 8000.00, 1200.00, 9200.00, 'paid', '2024-03-12', '2024-03-26', '2024-03-20'),
('INV-2024-017', 4, 14, 11, 'one_time', 'فاتورة استضافة المؤتمر', 5000.00, 750.00, 5750.00, 'paid', '2024-01-10', '2024-01-24', '2024-01-18'),
('INV-2024-018', 4, 15, 12, 'one_time', 'فاتورة اختبار الاختراق - دفعة أولى', 15000.00, 2250.00, 17250.00, 'paid', '2024-02-25', '2024-03-10', '2024-03-05'),
('INV-2024-019', 4, 15, 12, 'one_time', 'فاتورة اختبار الاختراق - دفعة ثانية', 15000.00, 2250.00, 0.00, 'pending', '2024-03-25', '2024-04-10', NULL),
('INV-2024-020', 5, 16, NULL, 'one_time', 'فاتورة إلغاء المشروع', 5000.00, 750.00, 0.00, 'cancelled', '2024-02-01', '2024-02-15', NULL);

-- =============================================
-- 6. إضافة المزيد من المدفوعات
-- =============================================
INSERT INTO client_payments (payment_code, client_id, invoice_id, amount, payment_method, status, payment_date, transaction_id) VALUES
('PAY-2024-007', 1, 9, 17250.00, 'bank_transfer', 'completed', '2024-02-28 11:20:00', 'TRF123789'),
('PAY-2024-008', 1, 11, 13800.00, 'card', 'completed', '2024-03-15 09:45:00', 'TXN456123'),
('PAY-2024-009', 2, 13, 2875.00, 'bank_transfer', 'completed', '2023-12-10 14:30:00', 'TRF789456'),
('PAY-2024-010', 2, 14, 2875.00, 'bank_transfer', 'completed', '2024-01-08 10:15:00', 'TRF456789'),
('PAY-2024-011', 2, 15, 2875.00, 'card', 'completed', '2024-02-12 16:20:00', 'TXN789123'),
('PAY-2024-012', 3, 16, 9200.00, 'bank_transfer', 'completed', '2024-03-20 13:40:00', 'TRF147258'),
('PAY-2024-013', 4, 17, 5750.00, 'card', 'completed', '2024-01-18 12:30:00', 'TXN369258'),
('PAY-2024-014', 4, 18, 17250.00, 'bank_transfer', 'completed', '2024-03-05 15:50:00', 'TRF258369');

-- =============================================
-- 7. إضافة المزيد من التقارير
-- =============================================
INSERT INTO client_reports (report_code, client_id, project_id, report_type, title, file_path, status, generated_at) VALUES
('RPT-PROG-002', 1, 8, 'progress', 'تقرير تقدم تطوير التطبيق - الأسبوع 4', '/reports/app-progress-week4.pdf', 'ready', '2024-03-15 10:00:00'),
('RPT-SEC-002', 1, 9, 'security', 'تقرير الثغرات الأمنية المكتشفة', '/reports/security-vulnerabilities.pdf', 'ready', '2024-03-20 14:30:00'),
('RPT-PERF-002', 2, 10, 'performance', 'تقرير أداء البوابة الإلكترونية', '/reports/portal-performance.pdf', 'ready', '2024-02-28 09:15:00'),
('RPT-BACK-002', 2, 11, 'backup', 'تقرير حالة النسخ الاحتياطي', '/reports/backup-status.pdf', 'generating', NULL),
('RPT-AUDIT-001', 3, 12, 'audit', 'تقرير تدقيق المتطلبات', '/reports/requirements-audit.pdf', 'ready', '2024-03-10 11:45:00'),
('RPT-SUM-002', 4, 14, 'summary', 'ملخص أداء المؤتمر', '/reports/conference-summary.pdf', 'ready', '2024-03-01 08:30:00'),
('RPT-PENT-001', 4, 15, 'security', 'تقرير اختبار الاختراق - مرحلي', '/reports/pentest-interim.pdf', 'ready', '2024-03-15 16:00:00');

-- =============================================
-- 8. إضافة المزيد من تذاكر الدعم
-- =============================================
INSERT INTO client_support_tickets (ticket_code, client_id, project_id, subject, message, priority, status, category) VALUES
('TCK-2024-006', 1, 8, 'استفسار عن واجهة المستخدم', 'هل يمكن تعديل ألوان واجهة المستخدم؟', 'medium', 'resolved', 'technical'),
('TCK-2024-007', 1, 8, 'مشكلة في قاعدة البيانات', 'قاعدة البيانات لا تستجيب بشكل جيد', 'high', 'in_progress', 'technical'),
('TCK-2024-008', 1, 9, 'نتائج التدقيق الأمني', 'متى ستصدر نتائج التدقيق الأمني الكاملة؟', 'low', 'closed', 'general'),
('TCK-2024-009', 2, 10, 'طلاء زيادة سعة الاستضافة', 'نحتاج زيادة سعة الاستضافة للموقع', 'medium', 'open', 'technical'),
('TCK-2024-010', 2, 11, 'استفسار عن عقد التخزين', 'هل يمكن تعديل بنود العقد؟', 'low', 'waiting', 'general'),
('TCK-2024-011', 3, 12, 'طلب إضافة ميزة جديدة', 'نحتاج إضافة نظام تقارير متقدم', 'high', 'in_progress', 'technical'),
('TCK-2024-012', 4, 15, 'استفسار عن تقرير الاختبار', 'لم أفهم بعض النتائج في التقرير', 'medium', 'resolved', 'technical'),
('TCK-2024-013', 5, 16, 'استفسار عن إلغاء المشروع', 'كيف يمكن استرداد المبلغ المدفوع؟', 'urgent', 'open', 'billing');

-- =============================================
-- 9. إضافة ردود على التذاكر
-- =============================================
INSERT INTO client_ticket_replies (ticket_id, user_id, is_staff, message) VALUES
(6, 1, FALSE, 'نعم، يمكن تعديل الألوان. ما هي الألوان المفضلة لديك؟'),
(6, 2, TRUE, 'تم تعديل الألوان حسب طلبك، يرجى المراجعة'),
(7, 1, FALSE, 'المشكلة مستمرة حتى الآن'),
(7, 2, TRUE, 'قمنا بتحسين أداء قاعدة البيانات، هل المشكلة ما زالت قائمة؟'),
(7, 1, FALSE, 'تم حل المشكلة، شكراً لكم'),
(8, 2, TRUE, 'سيتم إصدار النتائج الكاملة خلال يومين'),
(9, 2, TRUE, 'تمت زيادة السعة إلى 50GB، يرجى التأكد من الأداء'),
(11, 2, TRUE, 'سنقوم بإضافة نظام التقارير في الإصدار القادم'),
(12, 1, FALSE, 'يمكنك تحديد النقاط غير الواضحة'),
(12, 2, TRUE, 'تم توضيح النتائج في تقرير منفصل');

-- =============================================
-- 10. إضافة المزيد من الإشعارات
-- =============================================
INSERT INTO client_notifications (client_id, type, title, message, link, is_read) VALUES
(1, 'success', 'تم تحديث المشروع', 'تم تحديث حالة مشروع تطبيق الجوال', '/projects?view=8', FALSE),
(1, 'info', 'تقرير جديد', 'تم إنشاء تقرير الثغرات الأمنية', '/reports', TRUE),
(1, 'warning', 'فاتورة مستحقة', 'لديك فاتورة مستحقة الدفع بقيمة 17250 ر.س', '/billing', FALSE),
(2, 'success', 'تمت زيادة السعة', 'تمت زيادة سعة استضافة موقعك', '/projects?view=10', TRUE),
(2, 'info', 'رد على التذكرة', 'تم الرد على تذكرتك رقم 9', '/support', FALSE),
(3, 'info', 'تقرير جاهز', 'تقرير تدقيق المتطلبات جاهز للتحميل', '/reports', TRUE),
(4, 'warning', 'فاتورة قادمة', 'سيتم إصدار فاتورة جديدة قريباً', '/billing', FALSE),
(4, 'success', 'تم تحديث التقرير', 'تم تحديث تقرير اختبار الاختراق', '/projects?view=15', TRUE);

-- =============================================
-- 11. إضافة المزيد من النشاطات
-- =============================================
INSERT INTO client_activity_log (client_id, activity_type, target_type, target_id, description, ip_address) VALUES
(1, 'login', NULL, NULL, 'تسجيل دخول من جهاز جديد', '192.168.1.120'),
(1, 'view', 'project', 8, 'عرض تفاصيل مشروع تطبيق الجوال', '192.168.1.120'),
(1, 'download', 'report', 5, 'تحميل تقرير الثغرات الأمنية', '192.168.1.120'),
(2, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.121'),
(2, 'view', 'invoice', 15, 'عرض الفاتورة', '192.168.1.121'),
(2, 'ticket', 'ticket', 9, 'فتح تذكرة دعم جديدة', '192.168.1.121'),
(3, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.122'),
(3, 'view', 'report', 6, 'عرض تقرير التدقيق', '192.168.1.122'),
(4, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.123'),
(4, 'payment', 'invoice', 18, 'إجراء دفعة', '192.168.1.123'),
(1, 'logout', NULL, NULL, 'تسجيل خروج', '192.168.1.120');

-- =============================================
-- 12. إضافة المزيد من طلبات الخدمة
-- =============================================
INSERT INTO client_service_requests (request_code, client_id, service_type, project_name, description, budget, deadline, status) VALUES
('REQ-2024-006', 1, 'development', 'تطوير نظام محاسبة', 'نظام محاسبة متكامل مع الفروع', 40000.00, '2024-07-01', 'under_review'),
('REQ-2024-007', 2, 'storage', 'تخزين نسخ احتياطية', 'تخزين سحابي للنسخ الاحتياطية اليومية', 10000.00, '2024-05-15', 'approved'),
('REQ-2024-008', 3, 'security', 'فحص أمني للتطبيقات', 'فحص أمني شامل لجميع تطبيقات الشركة', 25000.00, '2024-06-01', 'pending'),
('REQ-2024-009', 4, 'hosting', 'استضافة منصة تعليمية', 'استضافة منصة تعليمية إلكترونية', 15000.00, '2024-05-20', 'under_review'),
('REQ-2024-010', 6, 'consultation', 'استشارة تحسين الأداء', 'استشارة لتحسين أداء الأنظمة الحالية', 5000.00, '2024-04-30', 'rejected');

-- =============================================
-- 13. إضافة إحصائيات إضافية
-- =============================================
INSERT INTO client_stats (client_id, stat_date, projects_count, active_projects, completed_projects, files_count, files_size, invoices_total, invoices_paid, tickets_count, open_tickets) VALUES
(1, '2024-02-29', 3, 2, 1, 8, 12500000, 53000, 31050, 3, 1),
(1, '2024-03-20', 3, 3, 0, 12, 18700000, 68000, 31050, 4, 2),
(2, '2024-01-31', 2, 1, 1, 4, 1850000, 25000, 25000, 2, 0),
(2, '2024-02-29', 2, 1, 1, 5, 2300000, 27500, 25000, 2, 0),
(2, '2024-03-20', 3, 2, 1, 7, 2950000, 42500, 25000, 3, 1),
(3, '2024-02-29', 1, 1, 0, 2, 1800000, 0, 0, 1, 1),
(3, '2024-03-20', 2, 2, 0, 4, 2700000, 8000, 9200, 2, 1),
(4, '2024-02-29', 2, 2, 0, 6, 21000000, 20000, 23000, 2, 1),
(4, '2024-03-20', 2, 2, 0, 8, 25600000, 50000, 23000, 3, 1);

-- =============================================
-- عرض إحصائيات البيانات المضافة
-- =============================================
SELECT '✅ تم إضافة بيانات افتراضية إضافية بنجاح' as 'النتيجة';

SELECT 
    (SELECT COUNT(*) FROM client_clients) as 'إجمالي العملاء',
    (SELECT COUNT(*) FROM client_projects) as 'إجمالي المشاريع',
    (SELECT COUNT(*) FROM client_files) as 'إجمالي الملفات',
    (SELECT COUNT(*) FROM client_contracts) as 'إجمالي العقود',
    (SELECT COUNT(*) FROM client_invoices) as 'إجمالي الفواتير',
    (SELECT COUNT(*) FROM client_payments) as 'إجمالي المدفوعات',
    (SELECT COUNT(*) FROM client_reports) as 'إجمالي التقارير',
    (SELECT COUNT(*) FROM client_support_tickets) as 'إجمالي التذاكر';

/*
-- =============================================
-- بيانات افتراضية إضافية لوحدة العميل
-- =============================================

-- =============================================
-- 1. إضافة المزيد من العملاء
-- =============================================
INSERT INTO client_clients (client_code, full_name, email, phone, company_name, tax_number, city, password_hash, balance, status) VALUES
('CL-2024-006', 'خالد العتيبي', 'khalid@example.com', '0555555555', 'مؤسسة خالد للتجارة', '1234567895', 'الرياض', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 35000.00, 'active'),
('CL-2024-007', 'ريم السبيعي', 'reem@example.com', '0566666666', 'شركة ريم للحلول التقنية', '1234567896', 'جدة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 12000.00, 'active'),
('CL-2024-008', 'عبدالله الغامدي', 'abdullah@example.com', '0577777777', 'مؤسسة الغامدي للمقاولات', '1234567897', 'الدمام', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 5000.00, 'inactive'),
('CL-2024-009', 'نوف الشمري', 'nouf@example.com', '0588888888', 'شركة الشمري للاستشارات', '1234567898', 'الخبر', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 28000.00, 'active'),
('CL-2024-010', 'فيصل الدوسري', 'faisal@example.com', '0599999999', 'مؤسسة فيصل للتطوير', '1234567899', 'مكة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 0.00, 'suspended');

/*

-- 7. جدول تقارير العميل (client_reports) - النسخة الصحيحة
CREATE TABLE IF NOT EXISTS client_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_code VARCHAR(50) UNIQUE NOT NULL,  -- 👈 هذا الحقل كان ناقصاً
    client_id INT NOT NULL,
    project_id INT,
    report_type ENUM('progress', 'security', 'performance', 'backup', 'audit', 'summary') DEFAULT 'progress',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500),
    file_size INT,
    format ENUM('pdf', 'excel', 'html', 'docx') DEFAULT 'pdf',
    status ENUM('generating', 'ready', 'sent', 'archived') DEFAULT 'generating',
    generated_at DATETIME,
    sent_at DATETIME,
    viewed_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_code (report_code)  -- 👈 إضافة Index للحقل الجديد
);

-- 7. تقارير العميل (client_reports)
INSERT INTO client_reports (report_code, client_id, project_id, report_type, title, file_path, status, generated_at) VALUES
('RPT-PROG-001', 1, 1, 'progress', 'تقرير تقدم المشروع - يناير 2024', '/reports/progress-jan.pdf', 'ready', '2024-02-01 09:00:00'),
('RPT-SEC-001', 2, 3, 'security', 'تقرير أمني أولي', '/reports/security-initial.pdf', 'ready', '2024-02-15 14:30:00'),
('RPT-PERF-001', 1, 2, 'performance', 'تقرير أداء التخزين', '/reports/storage-performance.pdf', 'ready', '2024-01-20 11:15:00'),
('RPT-SUM-001', 3, 5, 'summary', 'ملخص المشروع - فبراير 2024', '/reports/project-summary.pdf', 'generating', NULL),
('RPT-BACK-001', 1, 2, 'backup', 'تقرير النسخ الاحتياطي', '/reports/backup-report.pdf', 'ready', '2024-02-10 08:45:00');

-- 8. تذاكر الدعم (client_support_tickets)
INSERT INTO client_support_tickets (ticket_code, client_id, project_id, subject, message, priority, status, category) VALUES
('TCK-2024-001', 1, 1, 'استفسار عن سرعة الموقع', 'السلام عليكم، أريد الاستفسار عن إمكانية زيادة سرعة الموقع', 'medium', 'resolved', 'technical'),
('TCK-2024-002', 1, 1, 'مشكلة في رفع الملفات', 'لا أستطيع رفع الملفات إلى لوحة التحكم', 'high', 'in_progress', 'technical'),
('TCK-2024-003', 2, 3, 'استفسار عن الفاتورة', 'هل يمكن توضيح بنود الفاتورة؟', 'low', 'closed', 'billing'),
('TCK-2024-004', 3, 5, 'طلب ميزة جديدة', 'نحتاج إضافة ميزة جديدة للمشروع', 'medium', 'open', 'technical'),
('TCK-2024-005', 4, 6, 'استفسار عن التجديد', 'متى موعد تجديد الخدمة؟', 'low', 'waiting', 'general');

-- 9. ردود التذاكر (client_ticket_replies)
INSERT INTO client_ticket_replies (ticket_id, user_id, is_staff, message) VALUES
(1, 1, FALSE, 'وعليكم السلام، نعم يمكن زيادة السرعة. الرجاء تحديد الخطة المناسبة'),
(1, 2, TRUE, 'تمت زيادة السرعة إلى 100 Mbps، يرجى التأكد من الأداء'),
(2, 1, FALSE, 'تظهر رسالة خطأ عند رفع الملفات'),
(2, 2, TRUE, 'تم حل المشكلة، كان هناك خطأ في الصلاحيات'),
(3, 2, TRUE, 'الفاتورة تشمل رسوم الاستضافة الشهرية وخدمات إضافية');

-- 10. إشعارات العميل (client_notifications)
INSERT INTO client_notifications (client_id, type, title, message, link, is_read) VALUES
(1, 'success', 'تم دفع الفاتورة', 'تم استلام دفعتك بنجاح', '/billing', TRUE),
(1, 'info', 'تقرير جديد', 'تم إنشاء تقرير تقدم المشروع', '/reports', FALSE),
(2, 'warning', 'فاتورة مستحقة', 'لديك فاتورة مستحقة الدفع', '/billing', FALSE),
(2, 'info', 'رد على التذكرة', 'تم الرد على تذكرتك', '/support', TRUE),
(3, 'success', 'تم تحديث المشروع', 'تم تحديث حالة المشروع', '/projects', FALSE);

-- 11. سجل نشاطات العميل (client_activity_log)
INSERT INTO client_activity_log (client_id, activity_type, target_type, target_id, description, ip_address) VALUES
(1, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.100'),
(1, 'view', 'project', 1, 'عرض تفاصيل المشروع', '192.168.1.100'),
(1, 'download', 'report', 1, 'تحميل تقرير', '192.168.1.100'),
(2, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.101'),
(2, 'view', 'invoice', 5, 'عرض الفاتورة', '192.168.1.101'),
(3, 'upload', 'file', 5, 'رفع ملف', '192.168.1.102'),
(1, 'payment', 'invoice', 1, 'إجراء دفعة', '192.168.1.100'),
(2, 'ticket', 'ticket', 3, 'فتح تذكرة دعم', '192.168.1.101');

-- 12. طلبات الخدمة (client_service_requests)
INSERT INTO client_service_requests (request_code, client_id, service_type, project_name, description, budget, deadline, status) VALUES
('REQ-2024-001', 5, 'hosting', 'موقع شركة جديد', 'نحتاج موقع تعريفي للشركة', 5000.00, '2024-04-01', 'pending'),
('REQ-2024-002', 2, 'security', 'فحص أمني إضافي', 'نريد فحص أمني شامل للتطبيق', 15000.00, '2024-05-01', 'under_review'),
('REQ-2024-003', 3, 'storage', 'توسعة التخزين', 'نحتاج زيادة مساحة التخزين', 3000.00, '2024-03-20', 'approved'),
('REQ-2024-004', 1, 'pentest', 'اختبار اختراق', 'اختبار اختراق للموقع', 10000.00, '2024-04-15', 'pending'),
('REQ-2024-005', 4, 'consultation', 'استشارة تقنية', 'استشارة حول تطوير النظام', 2000.00, '2024-03-25', 'rejected');

-- 13. إعدادات العميل (client_settings)
INSERT INTO client_settings (client_id, language, notifications_email, notifications_sms, notifications_browser, theme) VALUES
(1, 'ar', TRUE, FALSE, TRUE, 'dark'),
(2, 'ar', TRUE, TRUE, TRUE, 'dark'),
(3, 'ar', TRUE, FALSE, TRUE, 'dark'),
(4, 'ar', TRUE, TRUE, FALSE, 'dark'),
(5, 'ar', TRUE, FALSE, TRUE, 'dark');

-- 14. المرفقات (client_attachments)
INSERT INTO client_attachments (client_id, target_type, target_id, file_name, file_path, file_size, file_type) VALUES
(1, 'ticket', 2, 'خطأ_الرفع.png', '/attachments/ticket2/error.png', 245000, 'png'),
(1, 'project', 1, 'ملاحظات_إضافية.pdf', '/attachments/project1/notes.pdf', 450000, 'pdf'),
(2, 'contract', 3, 'تعديلات_العقد.docx', '/attachments/contract3/amendments.docx', 125000, 'docx'),
(3, 'ticket', 4, 'مواصفات_الميزة.pdf', '/attachments/ticket4/feature-spec.pdf', 780000, 'pdf');

-- 15. إحصائيات العميل (client_stats)
INSERT INTO client_stats (client_id, stat_date, projects_count, active_projects, completed_projects, files_count, files_size, invoices_total, invoices_paid, tickets_count, open_tickets) VALUES
(1, '2024-01-31', 2, 1, 1, 8, 4500000, 15000, 10637.50, 2, 1),
(1, '2024-02-29', 2, 1, 1, 10, 5200000, 16250, 10637.50, 3, 1),
(2, '2024-01-31', 1, 1, 0, 3, 1850000, 6000, 6900.00, 1, 0),
(2, '2024-02-29', 2, 2, 0, 5, 2700000, 18000, 6900.00, 2, 1),
(3, '2024-01-31', 1, 1, 0, 2, 890000, 5000, 5750.00, 1, 0),
(3, '2024-02-29', 1, 1, 0, 4, 1450000, 10000, 11500.00, 2, 1),
(4, '2024-01-31', 1, 0, 1, 0, 0, 3000, 3450.00, 1, 1);

-- =============================================
-- عرض إحصائيات البيانات المضافة
-- =============================================
-- =============================================
-- عرض إحصائيات البيانات المضافة
-- =============================================
SELECT '✅ تم إنشاء جميع جداول وحدة العميل بنجاح' as 'نتيجة التثبيت';

SELECT 
    (SELECT COUNT(*) FROM client_clients) as 'العملاء',
    (SELECT COUNT(*) FROM client_projects) as 'المشاريع',
    (SELECT COUNT(*) FROM client_files) as 'الملفات',
    (SELECT COUNT(*) FROM client_contracts) as 'العقود',
    (SELECT COUNT(*) FROM client_invoices) as 'الفواتير',
    (SELECT COUNT(*) FROM client_support_tickets) as 'التذاكر',
    (SELECT COUNT(*) FROM client_reports) as 'التقارير',
    (SELECT COUNT(*) FROM client_activity_log) as 'النشاطات';

    -- عرض أول 5 عملاء
SELECT id, client_code, full_name, email, company_name FROM client_clients LIMIT 5;

-- عرض أول 5 مشاريع
SELECT id, project_code, project_name, client_id, status FROM client_projects LIMIT 5;

-- عرض أول 5 فواتير
SELECT id, invoice_code, client_id, amount, status FROM client_invoices LIMIT 5;

-- عرض أول 5 تذاكر دعم
SELECT id, ticket_code, client_id, subject, status FROM client_support_tickets LIMIT 5;

-- عرض أول 5 تقارير
SELECT id, report_code, client_id, title, status FROM client_reports LIMIT 5;

/*

-- =============================================
-- 1. جدول العملاء (client_clients)
-- =============================================
CREATE TABLE IF NOT EXISTS client_clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_code VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(255),
    tax_number VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'السعودية',
    password_hash VARCHAR(255) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_code (client_code),
    INDEX idx_email (email)
);

-- =============================================
-- 2. جدول مشاريع العميل (client_projects)
-- =============================================
CREATE TABLE IF NOT EXISTS client_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    project_code VARCHAR(50) UNIQUE NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_type ENUM('hosting', 'storage', 'security', 'pentest', 'consultation', 'development') DEFAULT 'hosting',
    description TEXT,
    status ENUM('pending', 'under_study', 'contract_pending', 'in_progress', 'testing', 'completed', 'cancelled') DEFAULT 'pending',
    stage INT DEFAULT 1 COMMENT '1:الطلب, 2:الدراسة, 3:العقد, 4:التنفيذ, 5:الفحص, 6:التسليم, 7:الدعم',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    start_date DATE,
    deadline DATE,
    completion_date DATE,
    progress INT DEFAULT 0,
    budget DECIMAL(15,2),
    paid_amount DECIMAL(15,2) DEFAULT 0.00,
    manager_name VARCHAR(255),
    manager_phone VARCHAR(20),
    technical_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_code (project_code)
);

-- =============================================
-- 3. جدول ملفات العميل (client_files)
-- =============================================
CREATE TABLE IF NOT EXISTS client_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    project_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100),
    folder_path VARCHAR(500) DEFAULT '/',
    description TEXT,
    uploaded_by INT,
    version VARCHAR(20) DEFAULT '1.0',
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_project (project_id)
);

-- =============================================
-- 4. جدول عقود العميل (client_contracts)
-- =============================================
CREATE TABLE IF NOT EXISTS client_contracts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contract_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    project_id INT,
    contract_type ENUM('hosting', 'storage', 'security', 'service') DEFAULT 'service',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500),
    file_size INT,
    status ENUM('draft', 'sent', 'under_review', 'signed', 'active', 'expired', 'cancelled') DEFAULT 'draft',
    signed_by_client BOOLEAN DEFAULT FALSE,
    signed_by_company BOOLEAN DEFAULT FALSE,
    signed_at DATETIME,
    start_date DATE,
    end_date DATE,
    value DECIMAL(15,2),
    payment_terms TEXT,
    special_terms TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_code (contract_code)
);

-- =============================================
-- 5. جدول فواتير العميل (client_invoices)
-- =============================================
CREATE TABLE IF NOT EXISTS client_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    project_id INT,
    contract_id INT,
    invoice_type ENUM('monthly', 'quarterly', 'yearly', 'one_time', 'penalty') DEFAULT 'monthly',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) GENERATED ALWAYS AS (amount + tax_amount) STORED,
    paid_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('draft', 'sent', 'pending', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    issue_date DATE,
    due_date DATE,
    paid_date DATE,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'cheque') DEFAULT NULL,
    payment_reference VARCHAR(255),
    file_path VARCHAR(500),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (contract_id) REFERENCES client_contracts(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_code (invoice_code),
    INDEX idx_due_date (due_date)
);

-- =============================================
-- 6. جدول مدفوعات العميل (client_payments)
-- =============================================
CREATE TABLE IF NOT EXISTS client_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    invoice_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'cheque') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_date DATETIME,
    reference_number VARCHAR(255),
    notes TEXT,
    receipt_path VARCHAR(500),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES client_invoices(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_code (payment_code)
);

-- =============================================
-- 7. جدول تقارير العميل (client_reports)
-- =============================================
CREATE TABLE IF NOT EXISTS client_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    project_id INT,
    report_type ENUM('progress', 'security', 'performance', 'backup', 'audit', 'summary') DEFAULT 'progress',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500),
    file_size INT,
    format ENUM('pdf', 'excel', 'html', 'docx') DEFAULT 'pdf',
    status ENUM('generating', 'ready', 'sent', 'archived') DEFAULT 'generating',
    generated_at DATETIME,
    sent_at DATETIME,
    viewed_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status),
    INDEX idx_code (report_code)
);

-- =============================================
-- 8. جدول تذاكر الدعم (client_support_tickets)
-- =============================================
CREATE TABLE IF NOT EXISTS client_support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    project_id INT,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting', 'resolved', 'closed') DEFAULT 'open',
    category ENUM('technical', 'billing', 'sales', 'general') DEFAULT 'general',
    attachments TEXT,
    assigned_to INT,
    resolved_at DATETIME,
    closed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_code (ticket_code)
);

-- =============================================
-- 9. جدول ردود التذاكر (client_ticket_replies)
-- =============================================
CREATE TABLE IF NOT EXISTS client_ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT,
    is_staff BOOLEAN DEFAULT FALSE,
    message TEXT NOT NULL,
    attachments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES client_support_tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
);

-- =============================================
-- 10. جدول إشعارات العميل (client_notifications)
-- =============================================
CREATE TABLE IF NOT EXISTS client_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_read (is_read)
);

-- =============================================
-- 11. جدول سجل نشاطات العميل (client_activity_log)
-- =============================================
CREATE TABLE IF NOT EXISTS client_activity_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    activity_type ENUM('login', 'logout', 'view', 'download', 'upload', 'payment', 'ticket', 'contract', 'report') NOT NULL,
    target_type ENUM('project', 'file', 'contract', 'invoice', 'ticket', 'report') DEFAULT NULL,
    target_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at)
);

-- =============================================
-- 12. جدول طلبات الخدمة (client_service_requests)
-- =============================================
CREATE TABLE IF NOT EXISTS client_service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_code VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,
    service_type ENUM('hosting', 'storage', 'security', 'pentest', 'consultation', 'development') NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    budget DECIMAL(15,2),
    deadline DATE,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'converted') DEFAULT 'pending',
    admin_notes TEXT,
    converted_to_project INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_code (request_code)
);

-- =============================================
-- 13. جدول إعدادات العميل (client_settings)
-- =============================================
CREATE TABLE IF NOT EXISTS client_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL UNIQUE,
    language VARCHAR(10) DEFAULT 'ar',
    notifications_email BOOLEAN DEFAULT TRUE,
    notifications_sms BOOLEAN DEFAULT FALSE,
    notifications_browser BOOLEAN DEFAULT TRUE,
    theme VARCHAR(20) DEFAULT 'dark',
    timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE
);

-- =============================================
-- 14. جدول المرفقات (client_attachments)
-- =============================================
CREATE TABLE IF NOT EXISTS client_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    target_type ENUM('project', 'contract', 'invoice', 'ticket', 'report') NOT NULL,
    target_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_target (target_type, target_id)
);

-- =============================================
-- 15. جدول إحصائيات العميل (client_stats)
-- =============================================
CREATE TABLE IF NOT EXISTS client_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    stat_date DATE NOT NULL,
    projects_count INT DEFAULT 0,
    active_projects INT DEFAULT 0,
    completed_projects INT DEFAULT 0,
    files_count INT DEFAULT 0,
    files_size BIGINT DEFAULT 0,
    invoices_total DECIMAL(15,2) DEFAULT 0,
    invoices_paid DECIMAL(15,2) DEFAULT 0,
    tickets_count INT DEFAULT 0,
    open_tickets INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_client_date (client_id, stat_date),
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE
);

-- =============================================
-- 1. جدول خطط الاستضافة (hosting_plans)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_code VARCHAR(50) UNIQUE NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    plan_type ENUM('basic', 'advanced', 'professional', 'custom') DEFAULT 'basic',
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NOT NULL,
    disk_space INT NOT NULL COMMENT 'بالميجابايت',
    bandwidth INT NOT NULL COMMENT 'بالميجابايت',
    domains_limit INT DEFAULT 1,
    databases_limit INT DEFAULT 5,
    emails_limit INT DEFAULT 10,
    subdomains_limit INT DEFAULT 5,
    ftp_accounts INT DEFAULT 1,
    backup_type ENUM('none', 'weekly', 'daily', 'realtime') DEFAULT 'weekly',
    backup_retention INT DEFAULT 7 COMMENT 'أيام',
    ssl_certificate BOOLEAN DEFAULT TRUE,
    dedicated_ip BOOLEAN DEFAULT FALSE,
    priority_support BOOLEAN DEFAULT FALSE,
    features TEXT,
    description TEXT,
    is_popular BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (plan_type),
    INDEX idx_active (is_active)
);

-- =============================================
-- 2. جدول نطاقات العملاء (client_domains)
-- =============================================
CREATE TABLE IF NOT EXISTS client_domains (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    project_id INT,
    domain_name VARCHAR(255) NOT NULL,
    domain_type ENUM('primary', 'secondary', 'parked', 'subdomain') DEFAULT 'primary',
    registration_date DATE,
    expiry_date DATE,
    auto_renew BOOLEAN DEFAULT TRUE,
    registrar VARCHAR(100),
    dns_provider VARCHAR(100),
    nameserver1 VARCHAR(255),
    nameserver2 VARCHAR(255),
    nameserver3 VARCHAR(255),
    ip_address VARCHAR(45),
    status ENUM('active', 'pending', 'expired', 'cancelled') DEFAULT 'active',
    ssl_status ENUM('none', 'pending', 'active', 'expired') DEFAULT 'none',
    ssl_issuer VARCHAR(100),
    ssl_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_domain (domain_name),
    INDEX idx_status (status)
);

-- =============================================
-- 3. جدول مواقع الاستضافة (hosting_sites)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    project_id INT NOT NULL,
    plan_id INT NOT NULL,
    domain_id INT,
    site_name VARCHAR(255) NOT NULL,
    site_path VARCHAR(500),
    document_root VARCHAR(500),
    php_version ENUM('5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2') DEFAULT '8.1',
    database_name VARCHAR(100),
    database_user VARCHAR(100),
    database_password VARCHAR(255),
    ftp_username VARCHAR(100),
    ftp_password VARCHAR(255),
    ftp_home VARCHAR(500),
    status ENUM('pending', 'active', 'suspended', 'expired') DEFAULT 'pending',
    setup_status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activated_at DATETIME,
    expires_at DATE,
    suspended_at DATETIME,
    last_backup DATETIME,
    last_accessed DATETIME,
    notes TEXT,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES client_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES hosting_plans(id),
    FOREIGN KEY (domain_id) REFERENCES client_domains(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status)
);

-- =============================================
-- 4. جدول إحصائيات المواقع (hosting_stats)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    disk_usage INT DEFAULT 0 COMMENT 'بالميجابايت',
    bandwidth_usage INT DEFAULT 0 COMMENT 'بالميجابايت',
    inodes_usage INT DEFAULT 0,
    databases_count INT DEFAULT 0,
    emails_count INT DEFAULT 0,
    subdomains_count INT DEFAULT 0,
    ftp_accounts_count INT DEFAULT 0,
    daily_visitors INT DEFAULT 0,
    monthly_visitors INT DEFAULT 0,
    cpu_usage DECIMAL(5,2) DEFAULT 0,
    memory_usage DECIMAL(5,2) DEFAULT 0,
    stat_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    INDEX idx_date (stat_date)
);

-- =============================================
-- 5. جدول سجلات الزوار (hosting_access_logs)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_access_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    ip_address VARCHAR(45),
    request_method VARCHAR(10),
    request_uri VARCHAR(500),
    http_referer VARCHAR(500),
    user_agent TEXT,
    response_code INT,
    response_time INT COMMENT 'بالملي ثانية',
    bytes_sent INT,
    accessed_at DATETIME,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    INDEX idx_accessed (accessed_at)
);

-- =============================================
-- 6. جدول بيانات FTP (hosting_ftp_accounts)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_ftp_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    home_directory VARCHAR(500),
    permissions ENUM('read', 'write', 'execute', 'full') DEFAULT 'full',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    UNIQUE KEY unique_username (username)
);

-- =============================================
-- 7. جدول قواعد البيانات (hosting_databases)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_databases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    db_name VARCHAR(100) NOT NULL,
    db_user VARCHAR(100) NOT NULL,
    db_password VARCHAR(255) NOT NULL,
    db_host VARCHAR(100) DEFAULT 'localhost',
    db_type ENUM('mysql', 'postgresql', 'mongodb') DEFAULT 'mysql',
    db_version VARCHAR(20),
    db_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_backup DATETIME,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    UNIQUE KEY unique_db (site_id, db_name)
);

-- =============================================
-- 8. جدول طلبات الدعم الفني (hosting_support_requests)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_support_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    client_id INT NOT NULL,
    request_type ENUM('technical', 'billing', 'upgrade', 'downgrade', 'cancellation') DEFAULT 'technical',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'waiting', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES client_clients(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    INDEX idx_client (client_id),
    INDEX idx_status (status)
);

-- =============================================
-- 9. جدول سجلات الأمان (hosting_security_logs)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_security_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    event_type ENUM('login', 'logout', 'failed_login', 'file_change', 'permission_change', 'malware_detected', 'attack_detected') NOT NULL,
    severity ENUM('info', 'warning', 'critical') DEFAULT 'info',
    ip_address VARCHAR(45),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    INDEX idx_type (event_type),
    INDEX idx_created (created_at)
);

-- =============================================
-- 10. جدول النسخ الاحتياطية (hosting_backups)
-- =============================================
CREATE TABLE IF NOT EXISTS hosting_backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    backup_type ENUM('full', 'database', 'files') DEFAULT 'full',
    backup_size INT COMMENT 'بالميجابايت',
    file_path VARCHAR(500),
    status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    started_at DATETIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES hosting_sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id),
    INDEX idx_status (status)
);


-- =============================================
-- 📥 البيانات التجريبية
-- =============================================

-- 1. العملاء (client_clients)
INSERT INTO client_clients (client_code, full_name, email, phone, company_name, tax_number, city, password_hash, balance, status) VALUES
('CL-2024-001', 'أحمد محمد', 'ahmed@example.com', '0501234567', 'شركة التقنية المتطورة', '1234567890', 'الرياض', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 15000.00, 'active'),
('CL-2024-002', 'سارة عبدالله', 'sara@example.com', '0551234567', 'مؤسسة الأمان', '1234567891', 'جدة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 5000.00, 'active'),
('CL-2024-003', 'محمد العمري', 'mohammed@example.com', '0531234567', 'شركة البيانات الآمنة', '1234567892', 'الدمام', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 25000.00, 'active'),
('CL-2024-004', 'نورة الدوسري', 'noura@example.com', '0561234567', 'مؤسسة التجارة الإلكترونية', '1234567893', 'الخبر', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 8000.00, 'active'),
('CL-2024-005', 'فهد القحطاني', 'fahad@example.com', '0541234567', 'شركة الحلول المتكاملة', '1234567894', 'مكة', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 12000.00, 'suspended');

-- 2. مشاريع العميل (client_projects)
INSERT INTO client_projects (client_id, project_code, project_name, project_type, description, status, stage, priority, start_date, deadline, progress, budget, paid_amount, manager_name) VALUES
(1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'متجر إلكتروني متكامل مع نظام إدارة محتوى', 'in_progress', 4, 'high', '2024-01-15', '2024-04-15', 65, 15000.00, 7500.00, 'أحمد العلي'),
(1, 'PRJ-STOR-001', 'تخزين بيانات العملاء', 'storage', 'نظام تخزين سحابي للبيانات', 'completed', 6, 'medium', '2023-11-10', '2024-01-10', 100, 8000.00, 8000.00, 'سارة الأحمد'),
(2, 'PRJ-SEC-001', 'فحص أمني شامل', 'security', 'اختبار اختراق وتقييم أمني', 'testing', 5, 'high', '2024-02-01', '2024-03-15', 85, 12000.00, 6000.00, 'خالد الرشيد'),
(2, 'PRJ-PENT-001', 'اختبار اختراق', 'pentest', 'اختبار اختراق للتطبيق المصرفي', 'contract_pending', 3, 'critical', '2024-03-01', '2024-04-30', 25, 20000.00, 0.00, 'فاطمة الزهراني'),
(3, 'PRJ-DEV-001', 'تطوير بوابة إلكترونية', 'development', 'تطوير بوابة خدمات حكومية', 'in_progress', 4, 'high', '2024-01-20', '2024-06-20', 40, 45000.00, 15000.00, 'عبدالله المطيري'),
(4, 'PRJ-HOST-002', 'موقع الشركة', 'hosting', 'موقع تعريف للشركة', 'completed', 6, 'low', '2024-01-05', '2024-02-05', 100, 3000.00, 3000.00, 'منى الغامدي'),
(5, 'PRJ-STOR-002', 'أرشفة المستندات', 'storage', 'نظام أرشفة للمستندات', 'pending', 1, 'medium', '2024-03-10', '2024-05-10', 0, 6000.00, 0.00, 'ريم القحطاني');

-- 3. ملفات العميل (client_files)
INSERT INTO client_files (client_id, project_id, file_name, file_path, file_type, file_size, folder_path, description) VALUES
(1, 1, 'المتطلبات.pdf', '/uploads/client/1/project1/requirements.pdf', 'pdf', 2450000, '/project1', 'وثيقة متطلبات المشروع'),
(1, 1, 'شعار الشركة.png', '/uploads/client/1/project1/logo.png', 'png', 450000, '/project1/images', 'شعار الشركة'),
(1, 1, 'مخطط قاعدة البيانات.sql', '/uploads/client/1/project1/schema.sql', 'sql', 125000, '/project1/db', 'مخطط قاعدة البيانات'),
(2, 3, 'تقرير أولي.pdf', '/uploads/client/2/project3/initial-report.pdf', 'pdf', 1850000, '/project3', 'تقرير الفحص الأولي'),
(3, 5, 'المواصفات.docx', '/uploads/client/3/project5/specs.docx', 'docx', 890000, '/project5', 'مواصفات المشروع'),
(1, 2, 'بيانات تجريبية.xlsx', '/uploads/client/1/project2/sample-data.xlsx', 'xlsx', 560000, '/project2', 'بيانات تجريبية للاختبار');

-- 4. عقود العميل (client_contracts)
INSERT INTO client_contracts (contract_code, client_id, project_id, contract_type, title, status, signed_by_client, signed_by_company, signed_at, start_date, end_date, value, file_path) VALUES
('CON-HOST-001', 1, 1, 'hosting', 'عقد استضافة موقع التجارة الإلكترونية', 'active', TRUE, TRUE, '2024-01-20 10:30:00', '2024-01-20', '2025-01-20', 15000.00, '/contracts/contract-001.pdf'),
('CON-STOR-001', 1, 2, 'storage', 'عقد تخزين البيانات السحابي', 'expired', TRUE, TRUE, '2023-11-15 14:20:00', '2023-11-15', '2024-01-15', 8000.00, '/contracts/contract-002.pdf'),
('CON-SEC-001', 2, 3, 'security', 'عقد الفحص الأمني', 'signed', TRUE, FALSE, '2024-02-05 09:15:00', '2024-02-05', '2024-03-15', 12000.00, '/contracts/contract-003.pdf'),
('CON-PENT-001', 2, 4, 'service', 'عقد اختبار الاختراق', 'under_review', FALSE, FALSE, NULL, NULL, NULL, 20000.00, NULL),
('CON-DEV-001', 3, 5, 'service', 'عقد تطوير البوابة الإلكترونية', 'active', TRUE, TRUE, '2024-01-25 11:00:00', '2024-01-25', '2024-06-20', 45000.00, '/contracts/contract-004.pdf');

-- 5. فواتير العميل (client_invoices)
INSERT INTO client_invoices (invoice_code, client_id, project_id, contract_id, invoice_type, title, amount, tax_amount, paid_amount, status, issue_date, due_date, paid_date) VALUES
('INV-2024-001', 1, 1, 1, 'monthly', 'فاتورة استضافة - يناير 2024', 1250.00, 187.50, 1437.50, 'paid', '2024-01-01', '2024-01-15', '2024-01-10'),
('INV-2024-002', 1, 1, 1, 'monthly', 'فاتورة استضافة - فبراير 2024', 1250.00, 187.50, 0.00, 'pending', '2024-02-01', '2024-02-15', NULL),
('INV-2024-003', 1, 2, 2, 'one_time', 'فاتورة التخزين السنوية', 8000.00, 1200.00, 9200.00, 'paid', '2023-11-01', '2023-11-15', '2023-11-10'),
('INV-2024-004', 2, 3, 3, 'one_time', 'فاتورة الفحص الأمني - دفعة أولى', 6000.00, 900.00, 6900.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-10'),
('INV-2024-005', 2, 3, 3, 'one_time', 'فاتورة الفحص الأمني - دفعة ثانية', 6000.00, 900.00, 0.00, 'pending', '2024-03-01', '2024-03-15', NULL),
('INV-2024-006', 3, 5, 5, 'monthly', 'فاتورة التطوير - يناير 2024', 5000.00, 750.00, 5750.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-05'),
('INV-2024-007', 3, 5, 5, 'monthly', 'فاتورة التطوير - فبراير 2024', 5000.00, 750.00, 5750.00, 'paid', '2024-03-01', '2024-03-15', '2024-03-05'),
('INV-2024-008', 4, 6, NULL, 'one_time', 'فاتورة استضافة موقع الشركة', 3000.00, 450.00, 3450.00, 'paid', '2024-01-01', '2024-01-15', '2024-01-12');

-- 6. مدفوعات العميل (client_payments)
INSERT INTO client_payments (payment_code, client_id, invoice_id, amount, payment_method, status, payment_date, transaction_id) VALUES
('PAY-2024-001', 1, 1, 1437.50, 'card', 'completed', '2024-01-10 14:30:00', 'TXN123456'),
('PAY-2024-002', 1, 3, 9200.00, 'bank_transfer', 'completed', '2023-11-10 09:15:00', 'TRF789012'),
('PAY-2024-003', 2, 4, 6900.00, 'card', 'completed', '2024-02-10 11:20:00', 'TXN345678'),
('PAY-2024-004', 3, 6, 5750.00, 'bank_transfer', 'completed', '2024-02-05 13:45:00', 'TRF901234'),
('PAY-2024-005', 3, 7, 5750.00, 'card', 'completed', '2024-03-05 10:00:00', 'TXN567890'),
('PAY-2024-006', 4, 8, 3450.00, 'cash', 'completed', '2024-01-12 12:30:00', NULL);


client-unit/
│
├── index.php                               # الصفحة الرئيسية
│
├── config/
│   └── database.php                        # اتصال قاعدة البيانات
│
├── includes/
│   ├── functions.php                        # دوال مساعدة عامة
│   ├── auth.php                              # التحقق من المستخدم
│   └── client_functions.php                   # دوال خاصة بالعميل
│
└── pages/
    ├── dashboard.php                          # اللوحة الرئيسية
    ├── projects.php                            # مشاريعي
    ├── upload.php                               # رفع ملفات
    ├── contracts.php                            # العقود والموافقات
    ├── billing.php                               # الفواتير والمدفوعات
    ├── reports.php                                # التقارير والنتائج
    ├── security.php                               # الأمان والفحص
    └── support.php                                # الدعم والملاحظات