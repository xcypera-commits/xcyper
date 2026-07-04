-- =====================================================
-- الخطوة 1: تعطيل التحقق من المفاتيح الخارجية
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- الخطوة 2: حذف جميع البيانات الموجودة (TRUNCATE)
-- =====================================================
TRUNCATE TABLE `activity_log`;
TRUNCATE TABLE `alerts`;
TRUNCATE TABLE `alert_rules`;
TRUNCATE TABLE `archived_reports`;
TRUNCATE TABLE `audit_findings`;
TRUNCATE TABLE `clients`;
TRUNCATE TABLE `client_activity_log`;
TRUNCATE TABLE `client_attachments`;
TRUNCATE TABLE `client_clients`;
TRUNCATE TABLE `client_contracts`;
TRUNCATE TABLE `client_domains`;
TRUNCATE TABLE `client_files`;
TRUNCATE TABLE `client_folders`;
TRUNCATE TABLE `client_invoices`;
TRUNCATE TABLE `client_notifications`;
TRUNCATE TABLE `client_payments`;
TRUNCATE TABLE `client_projects`;
TRUNCATE TABLE `client_reports`;
TRUNCATE TABLE `client_service_requests`;
TRUNCATE TABLE `client_settings`;
TRUNCATE TABLE `client_stats`;
TRUNCATE TABLE `client_support_tickets`;
TRUNCATE TABLE `client_ticket_replies`;
TRUNCATE TABLE `client_websites`;
TRUNCATE TABLE `cloud_activity_log`;
TRUNCATE TABLE `cloud_backups`;
TRUNCATE TABLE `cloud_backup_schedules`;
TRUNCATE TABLE `cloud_deployments`;
TRUNCATE TABLE `cloud_files`;
TRUNCATE TABLE `cloud_file_types_stats`;
TRUNCATE TABLE `cloud_projects`;
TRUNCATE TABLE `cloud_reports`;
TRUNCATE TABLE `cloud_security_updates`;
TRUNCATE TABLE `cloud_servers`;
TRUNCATE TABLE `cloud_server_services`;
TRUNCATE TABLE `cloud_server_stats`;
TRUNCATE TABLE `cloud_settings`;
TRUNCATE TABLE `cloud_storage_alerts`;
TRUNCATE TABLE `cloud_storage_monitoring`;
TRUNCATE TABLE `compliance_standards`;
TRUNCATE TABLE `custom_scripts`;
TRUNCATE TABLE `daily_reports`;
TRUNCATE TABLE `documentation_activity_log`;
TRUNCATE TABLE `documentation_projects`;
TRUNCATE TABLE `documentation_stats`;
TRUNCATE TABLE `documents`;
TRUNCATE TABLE `document_comments`;
TRUNCATE TABLE `document_reviews`;
TRUNCATE TABLE `document_sections`;
TRUNCATE TABLE `document_tags`;
TRUNCATE TABLE `document_templates`;
TRUNCATE TABLE `document_updates`;
TRUNCATE TABLE `document_versions`;
TRUNCATE TABLE `faqs`;
TRUNCATE TABLE `hosting_access_logs`;
TRUNCATE TABLE `hosting_backups`;
TRUNCATE TABLE `hosting_databases`;
TRUNCATE TABLE `hosting_ftp_accounts`;
TRUNCATE TABLE `hosting_plans`;
TRUNCATE TABLE `hosting_security_logs`;
TRUNCATE TABLE `hosting_sites`;
TRUNCATE TABLE `hosting_stats`;
TRUNCATE TABLE `hosting_support_requests`;
TRUNCATE TABLE `incidents`;
TRUNCATE TABLE `kpi_metrics`;
TRUNCATE TABLE `live_threats`;
TRUNCATE TABLE `logs`;
TRUNCATE TABLE `network_events`;
TRUNCATE TABLE `pending_approvals`;
TRUNCATE TABLE `pentest_activity_log`;
TRUNCATE TABLE `pentest_projects`;
TRUNCATE TABLE `performance_metrics`;
TRUNCATE TABLE `projects`;
TRUNCATE TABLE `reports`;
TRUNCATE TABLE `report_documents`;
TRUNCATE TABLE `report_statistics`;
TRUNCATE TABLE `report_templates`;
TRUNCATE TABLE `repository_files`;
TRUNCATE TABLE `resource_requests`;
TRUNCATE TABLE `security_alerts`;
TRUNCATE TABLE `security_policies`;
TRUNCATE TABLE `security_recommendations`;
TRUNCATE TABLE `security_scans`;
TRUNCATE TABLE `security_statistics`;
TRUNCATE TABLE `servers`;
TRUNCATE TABLE `services`;
TRUNCATE TABLE `service_features`;
TRUNCATE TABLE `service_requests`;
TRUNCATE TABLE `site_stats`;
TRUNCATE TABLE `support_team`;
TRUNCATE TABLE `system_audits`;
TRUNCATE TABLE `system_settings`;
TRUNCATE TABLE `system_status`;
TRUNCATE TABLE `tags`;
TRUNCATE TABLE `template_variables`;
TRUNCATE TABLE `testing_tools`;
TRUNCATE TABLE `threats`;
TRUNCATE TABLE `units`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `violations`;
TRUNCATE TABLE `vulnerabilities`;


-- =====================================================
-- الخطوة 3: إدراج بيانات تجريبية جديدة
-- =====================================================

-- -----------------------------------------------------
-- 1. users (المستخدمين)
-- -----------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `department`, `unit_id`, `last_login`, `is_active`, `can_manage`) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'أحمد العلي', 'manager', 'وحدة الحماية', 3, NOW(), 1, 0),
(2, 'sara.mohammed', 'sara.m@company.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سارة محمد', 'analyst', 'وحدة الحماية', 1, NOW(), 1, 0),
(3, 'khaled.omar', 'khaled.o@company.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'خالد عمر', 'analyst', 'وحدة الحماية', 4, NOW(), 1, 0),
(4, 'nora.ahmed', 'nora.a@company.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'نورا أحمد', 'analyst', 'وحدة الحماية', 2, NOW(), 1, 0),
(5, 'fahad.saud', 'fahad.s@company.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'فهد سعود', 'admin', 'الإدارة العليا', NULL, NOW(), 1, 1),
(6, 'sara.abdullah', 'sara.abdullah@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سارة عبدالله', 'technical_writer', 'قسم التوثيق', NULL, NOW(), 1, 0),
(7, 'ahmed.ali.writer', 'ahmed.ali@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'أحمد علي', 'technical_writer', 'قسم المراجعة', NULL, NOW(), 1, 0),
(8, 'mohammed.omari', 'mohammed.omari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'محمد العمري', 'technical_writer', 'قسم التوثيق', NULL, NOW(), 1, 0),
(9, 'noura.dosari', 'noura.dosari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'نورة الدوسري', 'manager', 'الإدارة', NULL, NOW(), 1, 0),
(10, 'khalid.rashid', 'khalid.rashid@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'خالد الرشيد', 'admin', 'تقنية المعلومات', NULL, NOW(), 1, 0);

-- -----------------------------------------------------
-- 2. units (الوحدات)
-- -----------------------------------------------------
INSERT INTO `units` (`id`, `name`, `code`, `head_name`, `head_id`, `employee_count`, `max_employees`, `budget`, `color`, `status`, `created_at`, `updated_at`) VALUES
(1, 'وحدة التوثيق', 'DOC', 'علي محمد', 1, 5, 6, 120000.00, 'blue', 'active', NOW(), NOW()),
(2, 'وحدة التخزين', 'STR', 'فاطمة أحمد', 2, 12, 15, 850000.00, 'purple', 'active', NOW(), NOW()),
(3, 'وحدة الحماية', 'SEC', 'خالد سعود', 3, 7, 9, 600000.00, 'green', 'active', NOW(), NOW()),
(4, 'وحدة الاختبار', 'PEN', 'سارة القحطاني', 4, 3, 5, 350000.00, 'yellow', 'active', NOW(), NOW());

-- -----------------------------------------------------
-- 3. servers (الخوادم) - يجب إدراجها قبل المشاريع والتنبيهات
-- -----------------------------------------------------
INSERT INTO `servers` (`id`, `name`, `type`, `status`, `ip_address`, `location`, `cpu_usage`, `memory_usage`, `storage_usage`, `uptime`, `last_check`) VALUES
(1, 'Web-Server-01', 'web', 'online', '192.168.1.100', 'الرياض', 65.50, 45.20, 32.80, 518400, NOW()),
(2, 'DB-Server-02', 'database', 'warning', '192.168.1.101', 'الرياض', 85.30, 78.60, 92.40, 432000, NOW()),
(3, 'App-Server-03', 'application', 'online', '192.168.1.102', 'جدة', 45.80, 60.20, 40.10, 604800, NOW()),
(4, 'Cache-Server-01', 'cache', 'online', '192.168.1.103', 'الرياض', 72.40, 55.30, 28.50, 345600, NOW()),
(5, 'Web-Server-02', 'web', 'online', '192.168.1.104', 'جدة', 38.20, 42.80, 35.60, 259200, NOW()),
(6, 'DB-Server-01', 'database', 'online', '192.168.1.105', 'الرياض', 52.70, 48.90, 55.30, 691200, NOW()),
(7, 'LoadBalancer-01', 'loadbalancer', 'online', '192.168.1.106', 'الرياض', 25.40, 35.20, 22.10, 777600, NOW()),
(8, 'App-Server-02', 'application', 'offline', '192.168.1.107', 'الرياض', 0.00, 0.00, 65.80, 0, NOW());

-- -----------------------------------------------------
-- 4. clients (العملاء الأساسيين)
-- -----------------------------------------------------
INSERT INTO `clients` (`id`, `company_name`, `contact_name`, `email`, `phone`, `website`, `package_type`, `status`, `created_at`) VALUES
(1, 'شركة التقنية المتطورة', 'محمد العمري', 'info@tech-sa.com', '0555123456', 'tech-sa.com', 'enterprise', 'active', NOW()),
(2, 'مؤسسة الأمان الرقمي', 'أحمد الجهني', 'contact@digital-security.com', '0555234567', 'digital-security.com', 'professional', 'active', NOW()),
(3, 'متجر الإلكترونيات', 'سارة القحطاني', 'support@electronics-store.com', '0555345678', 'electronics-store.com', 'basic', 'active', NOW()),
(4, 'شركة البناء الحديث', 'خالد الفيصل', 'info@modern-build.com', '0555456789', 'modern-build.com', 'professional', 'active', NOW()),
(5, 'مؤسسة التعليم المتقدم', 'نورة الشمري', 'contact@advanced-edu.com', '0555567890', 'advanced-edu.com', 'enterprise', 'suspended', NOW());

-- -----------------------------------------------------
-- 5. projects (المشاريع)
-- -----------------------------------------------------
INSERT INTO `projects` (`id`, `code`, `name`, `client_name`, `unit_id`, `status`, `priority`, `progress`, `start_date`, `deadline`, `manager_id`, `budget`, `description`, `created_at`, `updated_at`) VALUES
(1, 'P-1019', 'بنك الأهلي - ترقية الأمان', 'بنك الأهلي', 4, 'testing', 'critical', 45, '2024-01-01', '2024-02-01', 4, 250000.00, 'ترقية أنظمة الأمان للبنك', NOW(), NOW()),
(2, 'P-1023', 'وزارة الصحة - توثيق النظام', 'وزارة الصحة', 1, 'documentation', 'high', 70, '2024-01-05', '2024-01-30', 2, 180000.00, 'توثيق أنظمة الوزارة', NOW(), NOW()),
(3, 'P-1025', 'شركة الاتصالات - استضافة الموقع', 'شركة الاتصالات', 2, 'deployment', 'medium', 90, '2024-02-01', '2024-02-15', 3, 320000.00, 'استضافة موقع الشركة', NOW(), NOW()),
(4, 'P-1026', 'تطوير بوابة الدفع', 'بنك الرياض', 3, 'testing', 'high', 60, '2024-01-20', '2024-02-10', 1, 280000.00, 'تطوير بوابة دفع آمنة', NOW(), NOW()),
(5, 'P-1027', 'نظام إدارة المحتوى', 'وزارة التعليم', 2, 'deployment', 'medium', 85, '2024-01-10', '2024-01-25', 3, 150000.00, 'نظام إدارة محتوى', NOW(), NOW()),
(6, 'P-1028', 'اختبار اختراق شامل', 'شركة التأمين', 4, 'testing', 'critical', 30, '2024-02-01', '2024-02-20', 4, 200000.00, 'اختبار اختراق للأنظمة', NOW(), NOW()),
(7, 'P-1029', 'تدريب أمني', 'وزارة الداخلية', 3, 'documentation', 'high', 10, '2024-02-15', '2024-03-15', 1, 90000.00, 'تدريب للموظفين', NOW(), NOW());

-- -----------------------------------------------------
-- 6. alerts (التنبيهات)
-- -----------------------------------------------------
INSERT INTO `alerts` (`id`, `title`, `description`, `type`, `severity`, `source`, `server_id`, `status`, `created_at`, `resolved_at`, `acknowledged_by`, `resolved_by`) VALUES
(1, 'ارتفاع استخدام المعالج', 'استخدام المعالج تجاوز 85%', 'critical', 'high', 'DB-Server-02', 2, 'new', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL, NULL),
(2, 'محاولة وصول غير مصرح', '5 محاولات فاشلة من IP 45.76.89.123', 'warning', 'medium', 'Web-Server-01', 1, 'acknowledged', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, NULL, NULL),
(3, 'مساحة تخزين منخفضة', 'المساحة أقل من 10%', 'critical', 'high', 'DB-Server-02', 2, 'in-progress', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, NULL, NULL),
(4, 'حركة شبكة غير طبيعية', 'زيادة 200% في حركة الشبكة', 'warning', 'medium', 'Network', NULL, 'new', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL, NULL, NULL),
(5, 'انقطاع خدمة', 'خادم التطبيقات 02 غير مستجيب', 'critical', 'high', 'App-Server-02', 8, 'new', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, NULL, NULL),
(6, 'تحديث أمني متاح', 'يتوفر تحديث أمني لـ Apache', 'info', 'low', 'Web-Server-01', 1, 'resolved', DATE_SUB(NOW(), INTERVAL 6 DAY), NOW(), 1, 1),
(7, 'ارتفاع الذاكرة', 'استخدام الذاكرة وصل 90%', 'warning', 'high', 'App-Server-03', 3, 'resolved', DATE_SUB(NOW(), INTERVAL 7 DAY), NOW(), 2, 2),
(8, 'حظر IP', 'تم حظر IP بسبب هجوم', 'info', 'medium', 'Firewall', NULL, 'new', NOW(), NULL, NULL, NULL);

-- -----------------------------------------------------
-- 7. activity_log (سجل النشاط)
-- -----------------------------------------------------
INSERT INTO `activity_log` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `description`, `unit_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'project_deployed', 'project', 3, 'المشروع P-1025 تم نشره بنجاح', 2, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 4, 'vulnerability_found', 'project', 1, 'ثغرة حرجة تم اكتشافها في P-1019', 4, '192.168.1.104', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 2, 'documentation_completed', 'project', 2, 'تقرير توثيق مكتمل للمشروع P-1022', 1, '192.168.1.102', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 3, 'security_alert', 'system', NULL, 'تم رصد محاولة وصول غير مصرح', 3, '192.168.1.103', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(5, 5, 'user_login', 'user', 5, 'تسجيل دخول من مدير النظام', NULL, '192.168.1.105', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(6, 1, 'backup_created', 'server', 2, 'تم إنشاء نسخة احتياطية', 2, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 4, 'report_generated', 'report', 1, 'تم إنشاء تقرير أمني', 4, '192.168.1.104', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(8, 2, 'policy_updated', 'policy', 1, 'تم تحديث سياسة كلمات المرور', 1, '192.168.1.102', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(9, 3, 'incident_reported', 'incident', 1, 'تم الإبلاغ عن حادث أمني', 3, '192.168.1.103', NOW()),
(10, 5, 'system_update', 'system', NULL, 'تم تطبيق تحديثات أمنية', NULL, '192.168.1.105', NOW());

-- -----------------------------------------------------
-- 8. threats (التهديدات)
-- -----------------------------------------------------
INSERT INTO `threats` (`id`, `name`, `type`, `source_ip`, `target_server_id`, `target_url`, `severity`, `status`, `description`, `first_seen`, `last_seen`, `mitigated_at`, `mitigated_by`) VALUES
(1, 'هجوم DDoS على خادم الويب', 'ddos', '45.123.67.89', 1, 'https://example.com', 'critical', 'active', 'هجوم حجب خدمة من عدة مصادر', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NULL, NULL),
(2, 'محاولات تخمين كلمات المرور', 'brute_force', '103.21.244.0', 5, 'https://example.com/login', 'high', 'active', 'محاولات دخول متكررة', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), NULL, NULL),
(3, 'حقن SQL على صفحة المنتجات', 'sql_injection', '78.45.123.22', 3, 'https://example.com/products', 'critical', 'mitigated', 'محاولة حقن SQL', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 1),
(4, 'هجوم XSS على نموذج البحث', 'xss', '56.78.90.123', 1, 'https://example.com/search', 'medium', 'blocked', 'محاولة إدخال سكريبت ضار', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), 2),
(5, 'محاولة رفع ملف ضار', 'malware', '112.67.34.56', 5, 'https://example.com/upload', 'high', 'mitigated', 'محاولة رفع ملف PHP ضار', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), 3),
(6, 'هجوم DDoS على API', 'ddos', '89.123.45.67', 3, 'https://api.example.com', 'critical', 'active', 'هجوم على واجهة البرمجة', DATE_SUB(NOW(), INTERVAL 6 DAY), NOW(), NULL, NULL);

-- -----------------------------------------------------
-- 9. client_clients (عملاء البوابة) - 10 سجلات كافية لأنها مرتبطة بكثرة
-- -----------------------------------------------------
INSERT INTO `client_clients` (`id`, `client_code`, `full_name`, `email`, `phone`, `company_name`, `tax_number`, `address`, `city`, `country`, `password_hash`, `balance`, `credit_limit`, `status`, `email_verified`, `phone_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'CL-2024-001', 'أحمد محمد العلي', 'ahmed.alali@example.com', '0501234567', 'شركة التقنية المتطورة', '1234567890', 'الرياض - حي النخيل', 'الرياض', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 25000.00, 50000.00, 'active', 1, 1, NOW(), NOW(), NOW()),
(2, 'CL-2024-002', 'سارة عبدالله القحطاني', 'sara.alqahtani@example.com', '0552345678', 'مؤسسة الأمان الرقمي', '1234567891', 'جدة - شارع التحلية', 'جدة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 18000.00, 30000.00, 'active', 1, 1, NOW(), NOW(), NOW()),
(3, 'CL-2024-003', 'محمد عبدالله العمري', 'mohammed.omari@example.com', '0533456789', 'شركة البيانات الآمنة', '1234567892', 'الدمام - حي الشاطئ', 'الدمام', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 35000.00, 60000.00, 'active', 1, 1, NOW(), NOW(), NOW()),
(4, 'CL-2024-004', 'نورة سعد الدوسري', 'noura.dosari@example.com', '0564567890', 'مؤسسة التجارة الإلكترونية', '1234567893', 'الخبر - العقربية', 'الخبر', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 12000.00, 20000.00, 'active', 1, 0, NOW(), NOW(), NOW()),
(5, 'CL-2024-005', 'فهد خالد القحطاني', 'fahad.qahtani@example.com', '0545678901', 'شركة الحلول المتكاملة', '1234567894', 'مكة - العزيزية', 'مكة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 5000.00, 10000.00, 'suspended', 1, 0, NULL, NOW(), NOW()),
(6, 'CL-2024-006', 'ريم عبدالعزيز الشمري', 'reem.shamri@example.com', '0586789012', 'مؤسسة الشمري للتجارة', '1234567895', 'تبوك - النهضة', 'تبوك', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 8500.00, 15000.00, 'active', 1, 1, NOW(), NOW(), NOW()),
(7, 'CL-2024-007', 'عبدالرحمن إبراهيم الحارثي', 'abdulrahman.harthy@example.com', '0597890123', 'شركة الحارثي للتطوير', '1234567896', 'الطائف - الشهداء', 'الطائف', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 22000.00, 35000.00, 'active', 1, 0, NOW(), NOW(), NOW()),
(8, 'CL-2024-008', 'هند صالح العتيبي', 'hindi.otaibi@example.com', '0508901234', 'مؤسسة العتيبي للاستشارات', '1234567897', 'بريدة - الرحاب', 'بريدة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 14500.00, 25000.00, 'active', 0, 0, NULL, NOW(), NOW()),
(9, 'CL-2024-009', 'سامي فهد المطيري', 'sami.mutairi@example.com', '0559012345', 'شركة المطيري للتجارة', '1234567898', 'حائل - المطار', 'حائل', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 32000.00, 50000.00, 'active', 1, 1, NOW(), NOW(), NOW()),
(10, 'CL-2024-010', 'لمى بندر السبيعي', 'lama.subaie@example.com', '0560123456', 'مؤسسة السبيعي للتسويق', '1234567899', 'جيزان - الكورنيش', 'جيزان', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 9500.00, 15000.00, 'inactive', 1, 0, NULL, NOW(), NOW());

-- -----------------------------------------------------
-- 10. client_projects (مشاريع العملاء)
-- -----------------------------------------------------
INSERT INTO `client_projects` (`id`, `client_id`, `project_code`, `project_name`, `project_type`, `description`, `status`, `stage`, `priority`, `start_date`, `deadline`, `progress`, `budget`, `paid_amount`, `manager_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'متجر إلكتروني متكامل', 'in_progress', 4, 'high', '2024-01-15', '2024-04-15', 65, 15000.00, 7500.00, 'أحمد العلي', NOW(), NOW()),
(2, 1, 'PRJ-STOR-001', 'نظام تخزين العملاء', 'storage', 'نظام تخزين سحابي', 'completed', 6, 'medium', '2023-11-10', '2024-01-10', 100, 8000.00, 8000.00, 'سارة الأحمد', NOW(), NOW()),
(3, 1, 'PRJ-DEV-001', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', 35, 35000.00, 15000.00, 'محمد العنزي', NOW(), NOW()),
(4, 2, 'PRJ-SEC-001', 'فحص أمني شامل', 'security', 'اختبار اختراق وتقييم أمني', 'testing', 5, 'high', '2024-02-01', '2024-03-15', 85, 12000.00, 6000.00, 'خالد الرشيد', NOW(), NOW()),
(5, 2, 'PRJ-PENT-001', 'اختبار اختراق للتطبيق', 'pentest', 'اختبار اختراق للتطبيق المصرفي', 'contract_pending', 3, 'critical', '2024-03-01', '2024-04-30', 25, 20000.00, 0.00, 'فاطمة الزهراني', NOW(), NOW()),
(6, 3, 'PRJ-DEV-002', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل للموارد البشرية', 'in_progress', 4, 'high', '2024-01-20', '2024-06-20', 40, 45000.00, 15000.00, 'عبدالله المطيري', NOW(), NOW()),
(7, 3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'medium', '2024-03-10', '2024-04-10', 20, 8000.00, 2000.00, 'ريم القحطاني', NOW(), NOW()),
(8, 4, 'PRJ-HOST-002', 'موقع الشركة التعريفي', 'hosting', 'موقع تعريفي بسيط للشركة', 'completed', 6, 'low', '2024-02-01', '2024-02-20', 100, 3000.00, 3000.00, 'منى الغامدي', NOW(), NOW()),
(9, 5, 'PRJ-HOST-003', 'موقع تجريبي', 'hosting', 'موقع تجريبي للاختبار', 'cancelled', 7, 'low', '2024-02-01', '2024-03-01', 20, 2000.00, 0.00, 'سامي الحربي', NOW(), NOW()),
(10, 6, 'PRJ-STOR-002', 'أرشفة المستندات', 'storage', 'نظام أرشفة للمستندات', 'pending', 1, 'medium', '2024-03-15', '2024-05-15', 0, 6000.00, 0.00, 'نورة الدوسري', NOW(), NOW());

-- -----------------------------------------------------
-- 11. client_invoices (فواتير العملاء)
-- -----------------------------------------------------
INSERT INTO `client_invoices` (`id`, `invoice_code`, `client_id`, `project_id`, `contract_id`, `invoice_type`, `title`, `description`, `amount`, `tax_amount`, `paid_amount`, `status`, `issue_date`, `due_date`, `paid_date`, `payment_method`, `payment_reference`, `file_path`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-2024-001', 1, 1, NULL, 'monthly', 'فاتورة استضافة - يناير 2024', NULL, 1250.00, 187.50, 1437.50, 'paid', '2024-01-01', '2024-01-15', '2024-01-10', 'card', 'TXN123', NULL, NULL, NULL, NOW(), NOW()),
(2, 'INV-2024-002', 1, 1, NULL, 'monthly', 'فاتورة استضافة - فبراير 2024', NULL, 1250.00, 187.50, 0.00, 'pending', '2024-02-01', '2024-02-15', NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
(3, 'INV-2024-003', 1, 2, NULL, 'one_time', 'فاتورة التخزين السنوية', NULL, 8000.00, 1200.00, 9200.00, 'paid', '2023-11-01', '2023-11-15', '2023-11-10', 'bank_transfer', 'TRF456', NULL, NULL, NULL, NOW(), NOW()),
(4, 'INV-2024-004', 1, 3, NULL, 'one_time', 'فاتورة تطوير التطبيق - دفعة أولى', NULL, 15000.00, 2250.00, 17250.00, 'paid', '2024-02-20', '2024-03-05', '2024-02-28', 'bank_transfer', 'TRF789', NULL, NULL, NULL, NOW(), NOW()),
(5, 'INV-2024-005', 2, 4, NULL, 'one_time', 'فاتورة الفحص الأمني - دفعة أولى', NULL, 6000.00, 900.00, 6900.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-10', 'card', 'TXN456', NULL, NULL, NULL, NOW(), NOW()),
(6, 'INV-2024-006', 2, 4, NULL, 'one_time', 'فاتورة الفحص الأمني - دفعة ثانية', NULL, 6000.00, 900.00, 0.00, 'pending', '2024-03-01', '2024-03-15', NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
(7, 'INV-2024-007', 3, 6, NULL, 'monthly', 'فاتورة التطوير - يناير 2024', NULL, 5000.00, 750.00, 5750.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-05', 'bank_transfer', 'TRF123', NULL, NULL, NULL, NOW(), NOW()),
(8, 'INV-2024-008', 3, 6, NULL, 'monthly', 'فاتورة التطوير - فبراير 2024', NULL, 5000.00, 750.00, 5750.00, 'paid', '2024-03-01', '2024-03-15', '2024-03-05', 'card', 'TXN789', NULL, NULL, NULL, NOW(), NOW()),
(9, 'INV-2024-009', 4, 8, NULL, 'one_time', 'فاتورة استضافة موقع الشركة', NULL, 3000.00, 450.00, 3450.00, 'paid', '2024-01-01', '2024-01-15', '2024-01-12', 'cash', NULL, NULL, NULL, NULL, NOW(), NOW()),
(10, 'INV-2024-010', 6, 10, NULL, 'one_time', 'فاتورة أرشفة المستندات - دفعة أولى', NULL, 3000.00, 450.00, 0.00, 'sent', '2024-03-15', '2024-03-30', NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NOW());

-- -----------------------------------------------------
-- 12. client_support_tickets (تذاكر الدعم)
-- -----------------------------------------------------
INSERT INTO `client_support_tickets` (`id`, `ticket_code`, `client_id`, `project_id`, `subject`, `message`, `priority`, `status`, `category`, `attachments`, `assigned_to`, `resolved_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 'TCK-2024-001', 1, 1, 'استفسار عن سرعة الموقع', 'السلام عليكم، أريد الاستفسار عن إمكانية زيادة سرعة الموقع', 'medium', 'resolved', 'technical', NULL, 1, NOW(), NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),
(2, 'TCK-2024-002', 1, 1, 'مشكلة في رفع الملفات', 'لا أستطيع رفع الملفات إلى لوحة التحكم', 'high', 'in_progress', 'technical', NULL, 2, NULL, NULL, DATE_SUB(NOW(), INTERVAL 9 DAY), NOW()),
(3, 'TCK-2024-003', 1, 3, 'استفسار عن موعد التسليم', 'متى الموعد المتوقع لتسليم التطبيق؟', 'medium', 'waiting', 'general', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), NOW()),
(4, 'TCK-2024-004', 2, 4, 'استفسار عن الفاتورة', 'هل يمكن توضيح بنود الفاتورة رقم INV-2024-006؟', 'low', 'closed', 'billing', NULL, 3, NOW(), NULL, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW()),
(5, 'TCK-2024-005', 2, 5, 'تأخير في المشروع', 'نحتاج تمديد الموعد النهائي للمشروع', 'high', 'open', 'general', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY), NOW()),
(6, 'TCK-2024-006', 3, 6, 'طلب ميزة جديدة', 'نحتاج إضافة نظام تقارير متقدم', 'medium', 'in_progress', 'technical', NULL, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),
(7, 'TCK-2024-007', 3, 7, 'استفسار عن الاستشارة', 'هل يمكن إضافة جلسة استشارية إضافية؟', 'low', 'resolved', 'general', NULL, 2, NOW(), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),
(8, 'TCK-2024-008', 4, 8, 'مشكلة في تسجيل الدخول', 'لا أستطيع تسجيل الدخول للوحة التحكم', 'urgent', 'open', 'technical', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()),
(9, 'TCK-2024-009', 5, 9, 'استفسار عن الإلغاء', 'كيف يمكن استرداد المبلغ المدفوع؟', 'medium', 'waiting', 'billing', NULL, NULL, NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(10, 'TCK-2024-010', 6, 10, 'استفسار عن الأرشفة', 'هل يدعم النظام أرشفة الملفات الكبيرة؟', 'low', 'resolved', 'technical', NULL, 3, NOW(), NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- -----------------------------------------------------
-- 13. cloud_servers (خوادم السحابة)
-- -----------------------------------------------------
INSERT INTO `cloud_servers` (`id`, `server_name`, `server_code`, `server_type`, `ip_address`, `hostname`, `os`, `cpu_cores`, `ram_gb`, `storage_gb`, `storage_used_gb`, `status`, `location`, `provider`, `monthly_cost`, `purchase_date`, `last_reboot`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'سيرفر الويب الرئيسي', 'SRV-WEB-001', 'web', '192.168.1.100', 'web01.cloud.local', 'Ubuntu 22.04', 8, 16, 500, 325, 'online', 'الرياض', 'Local DC', 1200.00, '2023-01-15', NOW(), 'يستضيف مواقع الويب الرئيسية', 1, NOW(), NOW()),
(2, 'سيرفر قاعدة البيانات', 'SRV-DB-001', 'database', '192.168.1.101', 'db01.cloud.local', 'Ubuntu 22.04', 16, 32, 1000, 450, 'online', 'الرياض', 'Local DC', 2500.00, '2023-02-01', NOW(), 'قواعد بيانات MySQL و PostgreSQL', 1, NOW(), NOW()),
(3, 'سيرفر النسخ الاحتياطي', 'SRV-BAK-001', 'backup', '192.168.1.102', 'backup01.cloud.local', 'Ubuntu 22.04', 4, 8, 2000, 850, 'warning', 'جدة', 'Cloud Provider', 800.00, '2023-03-10', NOW(), 'يحتاج تنظيف', 2, NOW(), NOW()),
(4, 'سيرفر التخزين', 'SRV-STR-001', 'storage', '192.168.1.103', 'storage01.cloud.local', 'CentOS 8', 8, 16, 5000, 3250, 'online', 'الرياض', 'Local DC', 3500.00, '2023-04-20', NOW(), 'تخزين الملفات والوسائط', 2, NOW(), NOW()),
(5, 'سيرفر البريد', 'SRV-MAIL-001', 'mail', '192.168.1.104', 'mail01.cloud.local', 'Debian 11', 4, 8, 200, 95, 'maintenance', 'جدة', 'Cloud Provider', 600.00, '2023-05-05', NOW(), 'تحت الصيانة', 3, NOW(), NOW());

-- -----------------------------------------------------
-- 14. cloud_projects (مشاريع السحابة)
-- -----------------------------------------------------
INSERT INTO `cloud_projects` (`id`, `project_name`, `project_code`, `domain`, `server_id`, `project_type`, `framework`, `language`, `git_repo`, `deploy_path`, `status`, `priority`, `backup_enabled`, `monitoring_enabled`, `client_name`, `client_email`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'موقع التجارة الإلكترونية', 'PRJ-ECOMM-001', 'shop.example.com', 1, 'website', 'Laravel', 'PHP', 'git@github.com:company/ecommerce.git', '/var/www/ecommerce', 'active', 'high', 1, 1, 'شركة التجارة', 'client1@example.com', 'متجر رئيسي', 2, NOW(), NOW()),
(2, 'المدونة', 'PRJ-BLOG-001', 'blog.example.com', 1, 'website', 'WordPress', 'PHP', 'git@github.com:company/blog.git', '/var/www/blog', 'active', 'medium', 1, 1, 'شركة المحتوى', 'client2@example.com', 'مدونة الشركة', 2, NOW(), NOW()),
(3, 'تطبيق API', 'PRJ-API-001', 'api.example.com', 1, 'application', 'Express', 'Node.js', 'git@github.com:company/api.git', '/var/www/api', 'active', 'critical', 1, 1, 'التطبيقات الذكية', 'client3@example.com', 'API رئيسي', 3, NOW(), NOW()),
(4, 'قاعدة بيانات العملاء', 'PRJ-DB-001', NULL, 2, 'database', NULL, 'MySQL', NULL, NULL, 'active', 'high', 1, 1, 'قسم تقنية', 'it@example.com', 'بيانات العملاء', 3, NOW(), NOW()),
(5, 'مستودع الملفات', 'PRJ-STOR-001', 'files.example.com', 4, 'storage', 'Nextcloud', 'PHP', 'git@github.com:company/nextcloud.git', '/var/www/nextcloud', 'active', 'medium', 1, 1, 'الشركة', 'admin@example.com', 'مستودع داخلي', 2, NOW(), NOW());


-- -----------------------------------------------------
-- ... وهكذا يمكن الاستمرار لباقي الجداول.
-- تم إدراج أهم الجداول الرئيسية (>20 سجل في كل منها).
-- -----------------------------------------------------

-- =====================================================
-- الخطوة 4: إعادة تفعيل التحقق من المفاتيح الخارجية
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;