-- =============================================
-- استخدام قاعدة البيانات الرئيسية
-- =============================================
USE security_monitoring_db;

-- =============================================
-- إضافة بيانات تجريبية للمستندات
-- =============================================

-- أولاً: نتأكد إن في مشاريع
INSERT INTO documentation_projects (project_code, project_name, client_name, project_type, priority, status, start_date, deadline, progress) VALUES
('HOST-2024-001', 'نظام استضافة المواقع', 'شركة التقنية', 'hosting', 'high', 'in_progress', '2024-01-15', '2024-04-30', 65),
('SEC-2024-002', 'نظام الحماية الأمني', 'شركة الأمن', 'security', 'critical', 'in_progress', '2024-02-01', '2024-05-15', 40),
('CLOUD-2024-003', 'منصة التخزين السحابي', 'مؤسسة بيانات', 'cloud', 'medium', 'new', '2024-03-01', '2024-06-30', 10);

-- ثانياً: إضافة مستندات
INSERT INTO documents (document_code, title, project_id, document_type, format, version, status, pages, word_count, created_by, created_date, description) VALUES
('TECH-2024-001', 'متطلبات نظام الاستضافة', 1, 'requirements', 'pdf', '1.2', 'approved', 45, 3250, 1, '2024-01-15', 'وثيقة متطلبات نظام الاستضافة'),
('TECH-2024-002', 'دليل تثبيت نظام الاستضافة', 1, 'technical', 'pdf', '1.0', 'draft', 32, 2150, 1, '2024-01-18', 'دليل تثبيت النظام'),
('SEC-2024-003', 'تقرير الاختبارات الأمنية', 1, 'security', 'pdf', '2.1', 'under_review', 58, 4520, 2, '2024-01-22', 'تقرير نتائج الاختبارات الأمنية'),
('UG-2024-004', 'دليل مستخدم نظام الاستضافة', 1, 'user_guide', 'pdf', '1.5', 'approved', 120, 9850, 1, '2024-01-20', 'دليل المستخدم للنظام'),
('TECH-2024-005', 'دليل المشرف', 1, 'technical', 'pdf', '1.2', 'under_review', 65, 5320, 2, '2024-01-22', 'دليل المشرف'),
('API-2024-006', 'توثيق API - REST', 2, 'api', 'html', '0.9', 'draft', 0, 1250, 2, '2024-02-02', 'توثيق واجهات API'),
('TECH-2024-007', 'هيكلية النظام الأمني', 2, 'architecture', 'pdf', '2.3', 'approved', 72, 6850, 3, '2024-01-12', 'هيكلية النظام الأمني'),
('SEC-2024-008', 'تقييم أمن الشبكة', 2, 'security', 'pdf', '1.0', 'under_review', 45, 3850, 3, '2024-01-18', 'تقييم أمن الشبكة'),
('REQ-2024-009', 'متطلبات منصة التخزين', 3, 'requirements', 'pdf', '1.0', 'under_review', 58, 4950, 2, '2024-03-03', 'متطلبات منصة التخزين'),
('API-2024-010', 'توثيق API - التخزين', 3, 'api', 'md', '0.5', 'draft', 0, 850, 2, '2024-03-04', 'توثيق API للتخزين');

-- إضافة إصدارات
INSERT INTO document_versions (document_id, version_number, changes, created_by, created_at) VALUES
(1, '1.0', 'الإصدار الأولي', 1, '2024-01-15 10:30:00'),
(1, '1.1', 'تحديث قسم الأمان', 1, '2024-01-17 14:20:00'),
(1, '1.2', 'إضافة متطلبات الأداء', 1, '2024-01-19 09:45:00'),
(2, '1.0', 'الإصدار الأولي', 1, '2024-01-18 11:15:00'),
(3, '1.0', 'الإصدار الأولي', 2, '2024-01-22 13:20:00'),
(3, '2.0', 'تحديث نتائج الاختبارات', 2, '2024-01-23 15:30:00');

-- إضافة تعليقات
INSERT INTO document_comments (document_id, user_id, comment, page_number, created_at) VALUES
(1, 2, 'يرجى توضيح متطلبات الأمان', 12, '2024-01-16 11:30:00'),
(1, 1, 'تم التحديث حسب الطلب', 12, '2024-01-16 14:20:00'),
(3, 1, 'نتائج الاختبارات دقيقة', 45, '2024-01-24 10:15:00'),
(4, 2, 'دليل ممتاز', 1, '2024-01-21 13:20:00');

-- إضافة وسوم
INSERT INTO tags (name, color) VALUES
('requirements', 'blue'),
('security', 'red'),
('api', 'purple'),
('user-guide', 'green'),
('installation', 'yellow');

-- ربط الوسوم بالمستندات
INSERT INTO document_tags (document_id, tag_id) VALUES
(1, 1), (1, 2),
(2, 5),
(3, 2),
(4, 4),
(6, 3);
/*
-- =============================================
-- قاعدة بيانات نظام التوثيق والحماية
-- وحدة التوثيق (Documentation Module)
-- =============================================

/*
-- =============================================
-- 1. جدول المستخدمين (users)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'technical_writer', 'reviewer', 'viewer') DEFAULT 'viewer',
    department VARCHAR(100),
    avatar VARCHAR(500),
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
);

-- =============================================
-- 2. جدول المشاريع (documentation_projects)
-- =============================================
CREATE TABLE IF NOT EXISTS documentation_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_code VARCHAR(50) UNIQUE NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255),
    client_company VARCHAR(255),
    project_type ENUM('hosting', 'storage', 'security', 'ecommerce', 'cloud', 'network', 'mobile', 'desktop') DEFAULT 'hosting',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('new', 'under_analysis', 'analyzed', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'new',
    assigned_team VARCHAR(255),
    project_manager VARCHAR(255),
    technical_lead VARCHAR(255),
    start_date DATE,
    deadline DATE,
    completion_date DATE NULL,
    progress INT DEFAULT 0,
    documents_count INT DEFAULT 0,
    pages_count INT DEFAULT 0,
    budget DECIMAL(15,2),
    description TEXT,
    technical_requirements TEXT,
    security_level ENUM('normal', 'sensitive', 'critical') DEFAULT 'normal',
    repository_path VARCHAR(500),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_type (project_type),
    INDEX idx_code (project_code)
);

-- =============================================
-- 3. جدول المستندات (documents)
-- =============================================
CREATE TABLE IF NOT EXISTS documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(500) NOT NULL,
    project_id INT,
    document_type ENUM('technical', 'architecture', 'security', 'api', 'database', 'user_guide', 'deployment', 'requirements', 'test_plan', 'operation_manual', 'report', 'contract', 'proposal') DEFAULT 'technical',
    format ENUM('pdf', 'docx', 'xlsx', 'pptx', 'txt', 'md', 'html', 'xml', 'json', 'yaml', 'other') DEFAULT 'pdf',
    version VARCHAR(20) DEFAULT '1.0.0',
    status ENUM('draft', 'under_review', 'needs_work', 'approved', 'rejected', 'archived', 'in_progress', 'review', 'obsolete') DEFAULT 'draft',
    content LONGTEXT,
    executive_summary TEXT,
    introduction TEXT,
    file_path VARCHAR(500),
    file_size INT,
    pages INT DEFAULT 0,
    word_count INT DEFAULT 0,
    created_by INT,
    updated_by INT,
    reviewed_by INT,
    approved_by INT,
    created_date DATE,
    review_date DATE,
    approval_date DATE NULL,
    tags TEXT,
    description TEXT,
    is_template BOOLEAN DEFAULT FALSE,
    template_id INT,
    parent_document_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES documentation_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES documents(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_document_id) REFERENCES documents(id) ON DELETE SET NULL,
    INDEX idx_type (document_type),
    INDEX idx_status (status),
    INDEX idx_code (document_code),
    INDEX idx_project (project_id)
);

-- =============================================
-- 4. جدول إصدارات المستندات (document_versions)
-- =============================================
CREATE TABLE IF NOT EXISTS document_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    version_number VARCHAR(20) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    changes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document (document_id),
    INDEX idx_version (version_number)
);

-- =============================================
-- 5. جدول مراجعات المستندات (document_reviews)
-- =============================================
CREATE TABLE IF NOT EXISTS document_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    reviewer_id INT,
    review_round INT DEFAULT 1,
    review_type ENUM('technical', 'security', 'compliance', 'quality', 'final') DEFAULT 'technical',
    status ENUM('pending', 'in_progress', 'completed', 'rejected', 'needs_revision') DEFAULT 'pending',
    comments TEXT,
    feedback TEXT,
    checklist JSON,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    decision ENUM('approve', 'rework', 'reject', 'pending') DEFAULT 'pending',
    review_date DATE,
    completed_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_document (document_id),
    INDEX idx_reviewer (reviewer_id)
);

-- =============================================
-- 6. جدول التعليقات (document_comments)
-- =============================================
CREATE TABLE IF NOT EXISTS document_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    user_id INT,
    comment TEXT NOT NULL,
    page_number INT,
    section VARCHAR(255),
    resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document (document_id),
    INDEX idx_resolved (resolved)
);

-- =============================================
-- 7. جدول أقسام المستندات (document_sections)
-- =============================================
CREATE TABLE IF NOT EXISTS document_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    order_number INT,
    page_start INT,
    page_end INT,
    word_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_document (document_id),
    INDEX idx_order (order_number)
);

-- =============================================
-- 8. جدول الوسوم (tags)
-- =============================================
CREATE TABLE IF NOT EXISTS tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    color VARCHAR(20) DEFAULT 'blue',
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- =============================================
-- 9. جدول ربط المستندات بالوسوم (document_tags)
-- =============================================
CREATE TABLE IF NOT EXISTS document_tags (
    document_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (document_id, tag_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- =============================================
-- 10. جدول قوالب التوثيق (document_templates)
-- =============================================
CREATE TABLE IF NOT EXISTS document_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('technical', 'user_manual', 'api_doc', 'security', 'compliance', 'report', 'contract', 'proposal') NOT NULL,
    category ENUM('technical', 'security', 'monthly', 'final', 'custom') DEFAULT 'technical',
    format ENUM('docx', 'md', 'html', 'txt', 'pdf') DEFAULT 'docx',
    file_path VARCHAR(500),
    description TEXT,
    structure JSON,
    placeholders JSON,
    variables JSON,
    usage_count INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0,
    created_by INT,
    is_public BOOLEAN DEFAULT TRUE,
    access_level ENUM('public', 'team', 'private') DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_code (template_code)
);

-- =============================================
-- 11. جدول متغيرات القوالب (template_variables)
-- =============================================
CREATE TABLE IF NOT EXISTS template_variables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    variable_name VARCHAR(100) NOT NULL,
    variable_key VARCHAR(100) NOT NULL,
    variable_type ENUM('text', 'number', 'date', 'select', 'boolean', 'user', 'project') DEFAULT 'text',
    default_value TEXT,
    description VARCHAR(255),
    is_required BOOLEAN DEFAULT FALSE,
    options JSON,
    order_number INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE CASCADE,
    INDEX idx_template (template_id),
    UNIQUE KEY unique_template_variable (template_id, variable_key)
);

-- =============================================
-- 12. جدول التقارير (reports)
-- =============================================
CREATE TABLE IF NOT EXISTS reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_code VARCHAR(50) UNIQUE NOT NULL,
    report_title VARCHAR(500) NOT NULL,
    report_type ENUM('monthly', 'security', 'technical', 'progress', 'final', 'audit', 'compliance') DEFAULT 'technical',
    recipient ENUM('manager', 'client', 'security', 'storage', 'pentest', 'admin', 'team') DEFAULT 'manager',
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('preparing', 'ready', 'sent', 'approved', 'rejected', 'archived') DEFAULT 'preparing',
    format ENUM('pdf', 'docx', 'html', 'xlsx') DEFAULT 'pdf',
    file_path VARCHAR(500),
    summary TEXT,
    notes TEXT,
    sent_date DATE NULL,
    approved_date DATE NULL,
    created_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (report_type),
    INDEX idx_status (status),
    INDEX idx_code (report_code)
);

-- =============================================
-- 13. جدول ربط التقارير بالمستندات (report_documents)
-- =============================================
CREATE TABLE IF NOT EXISTS report_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    document_id INT NOT NULL,
    included_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    UNIQUE KEY unique_report_document (report_id, document_id)
);

-- =============================================
-- 14. جدول تحديثات المستندات (document_updates)
-- =============================================
CREATE TABLE IF NOT EXISTS document_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    update_type ENUM('minor', 'major', 'critical', 'security', 'bugfix') DEFAULT 'minor',
    old_version VARCHAR(20),
    new_version VARCHAR(20),
    changes_summary TEXT,
    detailed_changes TEXT,
    created_by INT,
    reviewed_by INT,
    status ENUM('pending', 'applied', 'rolled_back', 'failed') DEFAULT 'pending',
    applied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document (document_id),
    INDEX idx_status (status)
);

-- =============================================
-- 15. جدول سجل النشاطات (documentation_activity_log)
-- =============================================
CREATE TABLE IF NOT EXISTS documentation_activity_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    activity_type ENUM('create', 'update', 'delete', 'view', 'review', 'approve', 'reject', 'archive', 'download', 'upload', 'comment', 'share', 'export', 'import') NOT NULL,
    target_type ENUM('project', 'document', 'template', 'report', 'review', 'comment', 'file') NOT NULL,
    target_id INT,
    target_name VARCHAR(255),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id),
    INDEX idx_target (target_type, target_id)
);

-- =============================================
-- 16. جدول إحصائيات التوثيق (documentation_stats)
-- =============================================
CREATE TABLE IF NOT EXISTS documentation_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    stat_date DATE NOT NULL,
    projects_created INT DEFAULT 0,
    projects_completed INT DEFAULT 0,
    projects_in_progress INT DEFAULT 0,
    documents_created INT DEFAULT 0,
    documents_updated INT DEFAULT 0,
    documents_reviewed INT DEFAULT 0,
    documents_approved INT DEFAULT 0,
    documents_rejected INT DEFAULT 0,
    total_pages INT DEFAULT 0,
    total_documents INT DEFAULT 0,
    total_templates INT DEFAULT 0,
    total_reports INT DEFAULT 0,
    active_users INT DEFAULT 0,
    reviews_completed INT DEFAULT 0,
    comments_added INT DEFAULT 0,
    storage_used_mb DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (stat_date)
);

-- =============================================
-- 17. جدول ملفات المستودع (repository_files)
-- =============================================
CREATE TABLE IF NOT EXISTS repository_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    mime_type VARCHAR(100),
    folder_path VARCHAR(500),
    project_id INT,
    document_id INT,
    uploaded_by INT,
    version VARCHAR(20),
    checksum VARCHAR(64),
    is_encrypted BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES documentation_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project (project_id),
    INDEX idx_document (document_id),
    INDEX idx_type (file_type),
    INDEX idx_folder (folder_path)
);

-- =============================================
-- 18. جدول إعدادات النظام (system_settings)
-- =============================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'array') DEFAULT 'text',
    description VARCHAR(255),
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- 📥 البيانات التجريبية (Sample Data)
-- =============================================

-- =============================================
-- 1. المستخدمين (users) - 5 سجلات
-- =============================================
INSERT INTO users (username, full_name, email, password_hash, role, department, is_active) VALUES
('sara.abdullah', 'سارة عبدالله', 'sara.abdullah@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'technical_writer', 'قسم التوثيق', TRUE),
('ahmed.ali', 'أحمد العلي', 'ahmed.ali@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'reviewer', 'قسم المراجعة', TRUE),
('mohammed.omari', 'محمد العمري', 'mohammed.omari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'technical_writer', 'قسم التوثيق', TRUE),
('noura.dosari', 'نورة الدوسري', 'noura.dosari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'manager', 'الإدارة', TRUE),
('khalid.rashid', 'خالد الرشيد', 'khalid.rashid@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'admin', 'تقنية المعلومات', TRUE);

-- =============================================
-- 2. المشاريع (documentation_projects) - 5 سجلات
-- =============================================
INSERT INTO documentation_projects (project_code, project_name, client_name, client_company, project_type, priority, status, assigned_team, project_manager, technical_lead, start_date, deadline, progress, documents_count, pages_count, description, security_level, repository_path, created_by) VALUES
('HOST-2024-001', 'نظام استضافة المواقع الإلكترونية', 'شركة التقنية المتطورة', 'TechAdvance Co.', 'hosting', 'high', 'in_progress', 'فريق التوثيق الفني', 'أحمد العلي', 'سارة الأحمد', '2024-01-15', '2024-04-30', 65, 8, 245, 'نظام متكامل لإدارة استضافة المواقع مع دعم السحابة والتوسع التلقائي', 'sensitive', '/repositories/hosting-project', 1),
('STOR-2024-002', 'منصة التخزين السحابي', 'مؤسسة البيانات الآمنة', 'SecureData Foundation', 'storage', 'critical', 'under_analysis', 'فريق الأمن والتوثيق', 'محمد العنزي', 'نورة الدوسري', '2024-02-01', '2024-05-15', 25, 3, 120, 'منصة تخزين سحابي مع تشفير متقدم وتكامل مع التطبيقات', 'critical', '/repositories/cloud-storage', 3),
('SEC-2024-003', 'نظام الحماية الأمني', 'شركة الأمن السيبراني', 'CyberGuard Inc.', 'security', 'critical', 'analyzed', 'فريق أمن المعلومات', 'خالد الرشيد', 'فاطمة الزهراني', '2024-01-20', '2024-06-30', 40, 5, 180, 'نظام متكامل للحماية من الاختراقات والهجمات السيبرانية', 'critical', '/repositories/security-system', 5),
('ECOMM-2024-004', 'منصة التجارة الإلكترونية', 'شركة التسوق الحديث', 'ModernShop Co.', 'ecommerce', 'high', 'new', 'فريق التوثيق', 'سامي الحربي', 'منى الغامدي', '2024-03-01', '2024-07-15', 10, 2, 85, 'منصة تجارة إلكترونية متكاملة مع بوابات دفع وإدارة المخزون', 'sensitive', '/repositories/ecommerce-platform', 2),
('NET-2024-005', 'نظام إدارة الشبكات', 'شركة الاتصالات المتكاملة', 'NetConnect Ltd.', 'network', 'medium', 'on_hold', 'فريق البنية التحتية', 'عبدالله المطيري', 'ريم القحطاني', '2024-02-15', '2024-05-30', 50, 4, 150, 'نظام إدارة ومراقبة الشبكات مع تحليلات متقدمة', 'normal', '/repositories/network-management', 4);

-- =============================================
-- 3. المستندات (documents) - 20 سجل
-- =============================================
INSERT INTO documents (document_code, title, project_id, document_type, format, version, status, file_path, file_size, pages, created_by, created_date, review_date, tags, description) VALUES
('DOC-TECH-001', 'متطلبات نظام الاستضافة', 1, 'requirements', 'pdf', '1.2', 'approved', '/docs/hosting/requirements.pdf', 2450000, 45, 1, '2024-01-15', '2024-01-20', 'requirements,hosting,specifications', 'وثيقة متطلبات نظام الاستضافة'),
('DOC-TECH-002', 'دليل تثبيت نظام الاستضافة', 1, 'deployment', 'pdf', '1.0', 'draft', '/docs/hosting/installation.pdf', 1850000, 32, 1, '2024-01-18', NULL, 'installation,deployment,setup', 'دليل تثبيت نظام الاستضافة في بيئة الإنتاج'),
('DOC-SEC-003', 'تقرير الاختبارات الأمنية', 1, 'security', 'pdf', '2.1', 'under_review', '/docs/hosting/security-test.pdf', 3200000, 58, 3, '2024-01-22', '2024-01-25', 'security,testing,penetration', 'تقرير نتائج الاختبارات الأمنية'),
('DOC-USER-004', 'دليل مستخدم نظام الاستضافة', 1, 'user_guide', 'pdf', '1.5', 'approved', '/docs/hosting/user-guide.pdf', 4250000, 120, 1, '2024-01-20', '2024-01-28', 'user-guide,manual,help', 'دليل المستخدم لنظام الاستضافة'),
('DOC-TECH-005', 'دليل المشرف', 1, 'operation_manual', 'pdf', '1.2', 'under_review', '/docs/hosting/admin-guide.pdf', 2100000, 65, 3, '2024-01-22', '2024-01-27', 'admin,guide,operations', 'دليل المشرف للنظام'),
('DOC-API-006', 'توثيق API - REST', 2, 'api', 'html', '0.9', 'draft', '/docs/storage/api-docs.html', 950000, 0, 2, '2024-02-02', NULL, 'api,rest,integration', 'توثيق واجهات REST API'),
('DOC-TECH-007', 'أمثلة واستخدامات API', 2, 'technical', 'md', '0.5', 'draft', '/docs/storage/examples.md', 45000, 0, 2, '2024-02-03', NULL, 'examples,codes,samples', 'أمثلة على استخدام API'),
('DOC-SEC-008', 'تقرير أمني - البنية التحتية', 3, 'security', 'pdf', '1.0', 'approved', '/docs/security/infrastructure.pdf', 5600000, 85, 5, '2024-01-10', '2024-01-18', 'security,infrastructure,audit', 'تقرير أمني عن البنية التحتية'),
('DOC-TECH-009', 'هيكلية النظام الأمني', 3, 'architecture', 'pdf', '2.3', 'approved', '/docs/security/architecture.pdf', 3800000, 72, 5, '2024-01-12', '2024-01-19', 'architecture,design,security', 'وثيقة هيكلية النظام الأمني'),
('DOC-TECH-010', 'دليل تكوين النظام', 3, 'technical', 'pdf', '1.1', 'under_review', '/docs/security/configuration.pdf', 1950000, 48, 3, '2024-01-15', '2024-01-22', 'configuration,setup,security', 'دليل تكوين النظام الأمني'),
('DOC-TEST-011', 'خطة اختبار الاختراق', 3, 'test_plan', 'pdf', '1.0', 'draft', '/docs/security/penetration-test.pdf', 1250000, 35, 3, '2024-01-18', NULL, 'testing,penetration,security', 'خطة اختبار الاختراق للنظام'),
('DOC-USER-012', 'دليل مستخدم التطبيق', 4, 'user_guide', 'pdf', '0.8', 'draft', '/docs/ecommerce/user-guide.pdf', 2850000, 62, 2, '2024-03-02', NULL, 'user-guide,ecommerce,app', 'دليل مستخدم تطبيق التجارة الإلكترونية'),
('DOC-TECH-013', 'متطلبات المنصة', 4, 'requirements', 'pdf', '1.0', 'under_review', '/docs/ecommerce/requirements.pdf', 3150000, 58, 4, '2024-03-03', '2024-03-05', 'requirements,specifications,ecommerce', 'متطلبات منصة التجارة الإلكترونية'),
('DOC-API-014', 'توثيق API - الدفع', 4, 'api', 'md', '0.5', 'draft', '/docs/ecommerce/payment-api.md', 250000, 0, 2, '2024-03-04', NULL, 'api,payment,integration', 'توثيق واجهات الدفع'),
('DOC-TECH-015', 'دليل تثبيت الشبكة', 5, 'deployment', 'pdf', '1.2', 'approved', '/docs/network/installation.pdf', 1850000, 42, 4, '2024-02-16', '2024-02-20', 'installation,network,setup', 'دليل تثبيت نظام إدارة الشبكات'),
('DOC-TECH-016', 'هيكلية الشبكة', 5, 'architecture', 'pdf', '2.0', 'approved', '/docs/network/architecture.pdf', 4200000, 78, 4, '2024-02-18', '2024-02-22', 'architecture,network,design', 'هيكلية نظام إدارة الشبكات'),
('DOC-SEC-017', 'تقييم أمن الشبكة', 5, 'security', 'pdf', '1.0', 'under_review', '/docs/network/security-assessment.pdf', 2350000, 45, 5, '2024-02-20', '2024-02-25', 'security,assessment,network', 'تقييم أمن الشبكة'),
('DOC-TECH-018', 'دليل استكشاف الأخطاء', 5, 'operation_manual', 'pdf', '0.9', 'draft', '/docs/network/troubleshooting.pdf', 1650000, 38, 2, '2024-02-22', NULL, 'troubleshooting,errors,fixes', 'دليل استكشاف الأخطاء وإصلاحها'),
('DOC-REP-019', 'تقرير التقدم - الربع الأول', 1, 'report', 'pdf', '1.0', 'approved', '/reports/q1-progress.pdf', 980000, 22, 1, '2024-03-25', '2024-03-28', 'report,progress,Q1', 'تقرير تقدم المشروع للربع الأول'),
('DOC-REP-020', 'تقرير الأداء الشهري', 2, 'report', 'pdf', '1.1', 'under_review', '/reports/monthly-performance.pdf', 1120000, 28, 3, '2024-03-01', '2024-03-05', 'report,performance,monthly', 'تقرير الأداء الشهري للمنصة');

-- =============================================
-- 4. إصدارات المستندات (document_versions) - 30 سجل
-- =============================================
INSERT INTO document_versions (document_id, version_number, file_path, file_size, changes, created_by, created_at) VALUES
(1, '1.0', '/docs/hosting/requirements_v1.pdf', 2300000, 'الإصدار الأولي', 1, '2024-01-15 10:30:00'),
(1, '1.1', '/docs/hosting/requirements_v1.1.pdf', 2400000, 'تحديث قسم الأمان', 1, '2024-01-17 14:20:00'),
(1, '1.2', '/docs/hosting/requirements_v1.2.pdf', 2450000, 'إضافة متطلبات الأداء', 3, '2024-01-19 09:45:00'),
(2, '1.0', '/docs/hosting/installation_v1.pdf', 1850000, 'الإصدار الأولي', 1, '2024-01-18 11:15:00'),
(3, '1.0', '/docs/hosting/security-test_v1.pdf', 2900000, 'الإصدار الأولي', 3, '2024-01-22 13:20:00'),
(3, '2.0', '/docs/hosting/security-test_v2.pdf', 3100000, 'تحديث شامل لنتائج الاختبارات', 3, '2024-01-23 15:30:00'),
(3, '2.1', '/docs/hosting/security-test_v2.1.pdf', 3200000, 'إضافة توصيات أمنية', 5, '2024-01-24 10:45:00'),
(4, '1.0', '/docs/hosting/user-guide_v1.pdf', 4000000, 'الإصدار الأولي', 1, '2024-01-20 09:00:00'),
(4, '1.5', '/docs/hosting/user-guide_v1.5.pdf', 4250000, 'تحديث شامل بعد المراجعة', 1, '2024-01-25 16:30:00'),
(5, '1.0', '/docs/hosting/admin-guide_v1.pdf', 2000000, 'الإصدار الأولي', 3, '2024-01-22 14:15:00'),
(5, '1.1', '/docs/hosting/admin-guide_v1.1.pdf', 2050000, 'تحديث قسم المراقبة', 3, '2024-01-23 11:30:00'),
(5, '1.2', '/docs/hosting/admin-guide_v1.2.pdf', 2100000, 'إضافة أوامر الإدارة', 3, '2024-01-24 09:20:00'),
(8, '1.0', '/docs/security/infrastructure_v1.pdf', 5600000, 'الإصدار الأولي', 5, '2024-01-10 08:45:00'),
(9, '1.0', '/docs/security/architecture_v1.pdf', 3500000, 'الإصدار الأولي', 5, '2024-01-12 10:30:00'),
(9, '2.0', '/docs/security/architecture_v2.pdf', 3700000, 'تحديث هيكلية الأمان', 5, '2024-01-14 13:15:00'),
(9, '2.3', '/docs/security/architecture_v2.3.pdf', 3800000, 'إضافة طبقات حماية جديدة', 3, '2024-01-16 15:45:00'),
(10, '1.0', '/docs/security/configuration_v1.pdf', 1900000, 'الإصدار الأولي', 3, '2024-01-15 11:20:00'),
(10, '1.1', '/docs/security/configuration_v1.1.pdf', 1950000, 'تحديث إعدادات الجدار الناري', 3, '2024-01-17 14:30:00'),
(15, '1.0', '/docs/network/installation_v1.pdf', 1700000, 'الإصدار الأولي', 4, '2024-02-16 09:30:00'),
(15, '1.2', '/docs/network/installation_v1.2.pdf', 1850000, 'تحديث خطوات التثبيت', 4, '2024-02-18 11:45:00'),
(16, '1.0', '/docs/network/architecture_v1.pdf', 4000000, 'الإصدار الأولي', 4, '2024-02-18 13:20:00'),
(16, '2.0', '/docs/network/architecture_v2.pdf', 4200000, 'إعادة هيكلة الشبكة', 4, '2024-02-20 10:15:00'),
(17, '1.0', '/docs/network/security-assessment_v1.pdf', 2350000, 'الإصدار الأولي', 5, '2024-02-20 14:30:00'),
(19, '1.0', '/reports/q1-progress_v1.pdf', 980000, 'الإصدار الأولي', 1, '2024-03-25 09:00:00'),
(20, '1.0', '/reports/monthly-performance_v1.pdf', 1080000, 'الإصدار الأولي', 3, '2024-03-01 11:30:00'),
(20, '1.1', '/reports/monthly-performance_v1.1.pdf', 1120000, 'تحديث إحصائيات الأداء', 3, '2024-03-03 15:20:00');

-- =============================================
-- 5. مراجعات المستندات (document_reviews) - 15 سجل
-- =============================================
INSERT INTO document_reviews (document_id, reviewer_id, review_round, review_type, status, comments, feedback, checklist, rating, decision, review_date, completed_date) VALUES
(1, 2, 1, 'technical', 'completed', 'المراجعة التقنية - ممتازة', 'تمت المراجعة بنجاح، بعض الملاحظات البسيطة', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-01-18', '2024-01-18'),
(1, 5, 1, 'security', 'completed', 'مراجعة أمنية - تمت الموافقة مع ملاحظات', 'يوجد بعض النقاط الأمنية بحاجة لتوضيح', '{"comprehensive": true, "accuracy": false, "clarity": true, "standards": true}', 4, 'rework', '2024-01-19', '2024-01-19'),
(3, 2, 1, 'security', 'in_progress', 'جاري مراجعة التقرير الأمني', NULL, NULL, NULL, 'pending', NULL, NULL),
(4, 5, 1, 'quality', 'completed', 'مراجعة الجودة - دليل ممتاز', 'تمت المراجعة واعتماد الدليل', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-01-25', '2024-01-25'),
(5, 2, 1, 'technical', 'completed', 'مراجعة دليل المشرف', 'يحتاج تحديث قسم استكشاف الأخطاء', '{"comprehensive": true, "accuracy": true, "clarity": false, "standards": true}', 3, 'rework', '2024-01-24', '2024-01-24'),
(5, 2, 2, 'technical', 'completed', 'المراجعة الثانية', 'تم تحديث القسم المطلوب، جاهز للاعتماد', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 4, 'approve', '2024-01-25', '2024-01-25'),
(8, 2, 1, 'security', 'completed', 'مراجعة التقرير الأمني', 'تقرير شامل ومتكامل', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-01-15', '2024-01-15'),
(9, 5, 1, 'technical', 'completed', 'مراجعة الهيكلية', 'هيكلية ممتازة ومتكاملة', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-01-17', '2024-01-17'),
(10, 2, 1, 'technical', 'completed', 'مراجعة دليل التكوين', 'يحتاج إضافة أمثلة أكثر', '{"comprehensive": false, "accuracy": true, "clarity": true, "standards": true}', 3, 'rework', '2024-01-20', '2024-01-20'),
(13, 2, 1, 'technical', 'completed', 'مراجعة متطلبات المنصة', 'ممتازة، جاهزة للاعتماد', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 4, 'approve', '2024-03-05', '2024-03-05'),
(15, 2, 1, 'technical', 'completed', 'مراجعة دليل التثبيت', 'تمت المراجعة والموافقة', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-02-18', '2024-02-18'),
(16, 5, 1, 'technical', 'completed', 'مراجعة هيكلية الشبكة', 'هيكلية متكاملة', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-02-20', '2024-02-20'),
(17, 2, 1, 'security', 'in_progress', 'مراجعة تقييم الأمن', NULL, NULL, NULL, 'pending', NULL, NULL),
(19, 5, 1, 'quality', 'completed', 'مراجعة تقرير التقدم', 'تقرير ممتاز، جاهز للإرسال', '{"comprehensive": true, "accuracy": true, "clarity": true, "standards": true}', 5, 'approve', '2024-03-26', '2024-03-26'),
(20, 2, 1, 'quality', 'completed', 'مراجعة تقرير الأداء', 'يحتاج تحديث بعض الإحصائيات', '{"comprehensive": true, "accuracy": false, "clarity": true, "standards": true}', 3, 'rework', '2024-03-03', '2024-03-03');

-- =============================================
-- 6. التعليقات (document_comments) - 25 سجل
-- =============================================
INSERT INTO document_comments (document_id, user_id, comment, page_number, section, resolved, resolved_by, resolved_at) VALUES
(1, 2, 'يرجى توضيح متطلبات الأمان للتطبيق', 12, 'المتطلبات الأمنية', TRUE, 1, '2024-01-16 11:30:00'),
(1, 1, 'تم التحديث حسب الطلب', 12, 'المتطلبات الأمنية', TRUE, 2, '2024-01-16 14:20:00'),
(1, 5, 'نحتاج إضافة متطلبات أداء إضافية', 25, 'متطلبات الأداء', TRUE, 3, '2024-01-17 09:45:00'),
(2, 5, 'قسم المستخدم بحاجة إلى إعادة صياغة', 45, 'دليل المستخدم', FALSE, NULL, NULL),
(3, 1, 'تمت إضافة نتائج الاختبارات', 58, 'نتائج الاختبارات', TRUE, 2, '2024-01-23 15:30:00'),
(3, 2, 'نحتاج توثيق أكثر للثغرات المكتشفة', 32, 'الثغرات', TRUE, 3, '2024-01-24 10:15:00'),
(4, 2, 'صياغة المقدمة ممتازة', 1, 'المقدمة', TRUE, NULL, '2024-01-21 13:20:00'),
(4, 5, 'يوجد خطأ إملائي في الصفحة 15', 15, 'التثبيت', TRUE, 1, '2024-01-22 09:30:00'),
(5, 2, 'قسم أوامر الإدارة غير مكتمل', 28, 'أوامر الإدارة', TRUE, 3, '2024-01-23 11:45:00'),
(5, 3, 'تم إضافة الأوامر المطلوبة', 28, 'أوامر الإدارة', TRUE, 2, '2024-01-24 14:20:00'),
(8, 2, 'تقرير أمني شامل', 85, 'الخلاصة', TRUE, NULL, '2024-01-12 10:30:00'),
(9, 5, 'رسم الهيكلية يحتاج توضيح', 33, 'الهيكلية', TRUE, 5, '2024-01-14 15:45:00'),
(10, 2, 'نحتاج أمثلة على التكوين', 22, 'أمثلة التكوين', FALSE, NULL, NULL),
(12, 1, 'الصفحة الرئيسية للدليل ممتازة', 1, 'المقدمة', FALSE, NULL, NULL),
(13, 2, 'متطلبات قاعدة البيانات غير واضحة', 18, 'قاعدة البيانات', TRUE, 4, '2024-03-04 13:15:00'),
(13, 4, 'تم توضيح المتطلبات', 18, 'قاعدة البيانات', TRUE, 2, '2024-03-05 09:30:00'),
(15, 2, 'خطوات التثبيت واضحة', 12, 'التثبيت', TRUE, NULL, '2024-02-17 11:20:00'),
(16, 5, 'رسم الشبكة يحتاج تحديث', 45, 'الرسم البياني', TRUE, 4, '2024-02-19 14:45:00'),
(16, 4, 'تم تحديث الرسم', 45, 'الرسم البياني', TRUE, 5, '2024-02-20 10:30:00'),
(17, 2, 'نتائج التقييم دقيقة', 30, 'النتائج', FALSE, NULL, NULL),
(19, 5, 'إحصائيات التقدم ممتازة', 15, 'الإحصائيات', TRUE, NULL, '2024-03-26 11:15:00'),
(20, 2, 'أرقام الأداء تحتاج تدقيق', 22, 'الأداء', TRUE, 3, '2024-03-02 15:30:00'),
(20, 3, 'تم تدقيق الأرقام وتحديثها', 22, 'الأداء', TRUE, 2, '2024-03-04 09:45:00');

-- =============================================
-- 7. أقسام المستندات (document_sections) - 25 سجل
-- =============================================
INSERT INTO document_sections (document_id, title, content, order_number, page_start, page_end, word_count) VALUES
(1, 'مقدمة عن نظام الاستضافة', 'هذا المستند يوثق متطلبات نظام الاستضافة السحابي...', 1, 1, 3, 450),
(1, 'المتطلبات الوظيفية', 'قائمة المتطلبات الوظيفية للنظام تشمل: إدارة المستخدمين، إدارة المواقع...', 2, 4, 15, 1250),
(1, 'المتطلبات الأمنية', 'متطلبات الأمان تشمل: التشفير، الجدار الناري، إدارة الصلاحيات...', 3, 16, 25, 980),
(1, 'متطلبات الأداء', 'يجب أن يدعم النظام 10,000 مستخدم متزامن مع زمن استجابة أقل من 200ms...', 4, 26, 35, 820),
(2, 'تثبيت النظام', 'خطوات تثبيت نظام الاستضافة على بيئة الإنتاج...', 1, 1, 12, 1150),
(2, 'تكوين النظام', 'إعدادات التكوين الأساسية للنظام...', 2, 13, 22, 890),
(2, 'اختبار التثبيت', 'كيفية التحقق من صحة التثبيت...', 3, 23, 32, 720),
(3, 'منهجية الاختبار', 'تم استخدام أدوات اختبار الاختراق التالية: Burp Suite, Nmap, Metasploit...', 1, 1, 8, 650),
(3, 'نتائج الاختبارات', 'تم اكتشاف 15 ثغرة أمنية منها 3 حرجة...', 2, 9, 30, 1850),
(3, 'التوصيات', 'التوصيات لمعالجة الثغرات المكتشفة...', 3, 31, 45, 980),
(4, 'مقدمة للمستخدم', 'مرحباً بك في دليل مستخدم نظام الاستضافة...', 1, 1, 5, 450),
(4, 'بدء الاستخدام', 'كيفية البدء مع النظام...', 2, 6, 25, 1450),
(4, 'الميزات الرئيسية', 'شرح الميزات الرئيسية للنظام...', 3, 26, 60, 2250),
(4, 'استكشاف الأخطاء', 'حل المشكلات الشائعة...', 4, 61, 90, 1850),
(8, 'ملخص التقرير', 'هذا التقرير يقيّم أمان البنية التحتية...', 1, 1, 5, 580),
(8, 'نطاق التقييم', 'تم تقييم 25 خادماً و10 أجهزة شبكة...', 2, 6, 20, 1120),
(8, 'الثغرات المكتشفة', 'تم اكتشاف 8 ثغرات أمنية...', 3, 21, 50, 2150),
(8, 'خطة المعالجة', 'خطة مقترحة لمعالجة الثغرات...', 4, 51, 70, 1250),
(15, 'متطلبات الشبكة', 'متطلبات تثبيت نظام إدارة الشبكات...', 1, 1, 8, 620),
(15, 'خطوات التثبيت', 'خطوات تثبيت النظام خطوة بخطوة...', 2, 9, 25, 1350),
(15, 'التحقق من التثبيت', 'اختبارات التحقق من صحة التثبيت...', 3, 26, 35, 780),
(16, 'نظرة عامة على الهيكلية', 'هيكلية نظام إدارة الشبكات...', 1, 1, 10, 850),
(16, 'المكونات', 'المكونات الرئيسية للنظام...', 2, 11, 35, 1850),
(16, 'تدفق البيانات', 'كيفية تدفق البيانات في النظام...', 3, 36, 55, 1350),
(19, 'ملخص التقدم', 'تقدم المشروع خلال الربع الأول...', 1, 1, 4, 380);

-- =============================================
-- 8. الوسوم (tags) - 12 سجل
-- =============================================
INSERT INTO tags (name, color, description) VALUES
('requirements', 'blue', 'متطلبات النظام'),
('security', 'red', 'المستندات الأمنية'),
('api', 'purple', 'توثيق واجهات API'),
('user-guide', 'green', 'أدلة المستخدم'),
('installation', 'yellow', 'أدلة التثبيت'),
('production', 'orange', 'بيئة الإنتاج'),
('testing', 'cyan', 'اختبارات'),
('audit', 'indigo', 'تدقيق أمني'),
('architecture', 'pink', 'هيكلية النظام'),
('configuration', 'gray', 'تكوين النظام'),
('performance', 'teal', 'الأداء'),
('deployment', 'brown', 'نشر النظام');

-- =============================================
-- 9. ربط المستندات بالوسوم (document_tags) - 40 سجل
-- =============================================
INSERT INTO document_tags (document_id, tag_id) VALUES
(1, 1), (1, 2), (1, 11),
(2, 5), (2, 6), (2, 12),
(3, 2), (3, 7), (3, 8),
(4, 4), (4, 5), (4, 6),
(5, 9), (5, 10), (5, 12),
(6, 3), (6, 12),
(7, 3), (7, 7),
(8, 2), (8, 8), (8, 9),
(9, 2), (9, 9), (9, 10),
(10, 2), (10, 10), (10, 5),
(11, 2), (11, 7),
(12, 4), (12, 5),
(13, 1), (13, 2), (13, 11),
(14, 3), (14, 12),
(15, 5), (15, 6), (15, 12),
(16, 9), (16, 10),
(17, 2), (17, 8), (17, 9),
(18, 4), (18, 7),
(19, 1), (19, 11),
(20, 2), (20, 11);

-- =============================================
-- 10. قوالب التوثيق (document_templates) - 30 سجل
-- =============================================
INSERT INTO document_templates (name, template_code, type, category, format, file_path, description, placeholders, variables, usage_count, rating, created_by, is_public, access_level) VALUES
('قالب متطلبات النظام', 'TMP-REQ-001', 'technical', 'technical', 'docx', '/templates/requirements-template.docx', 'قالب موحد لتوثيق متطلبات النظام', '{"project":"اسم المشروع","date":"التاريخ","author":"المؤلف"}', '{"project_name":"text","client_name":"text","version":"text"}', 25, 4.5, 1, TRUE, 'public'),
('قالب تقرير أمني', 'TMP-SEC-001', 'security', 'security', 'docx', '/templates/security-report.docx', 'قالب موحد للتقارير الأمنية', '{"project":"اسم المشروع","date":"التاريخ","auditor":"المدقق"}', '{"project_name":"text","security_level":"select","findings":"number"}', 18, 4.8, 5, TRUE, 'public'),
('قالب دليل المستخدم', 'TMP-USER-001', 'user_manual', 'technical', 'docx', '/templates/user-manual.docx', 'قالب لدليل المستخدم', '{"product":"المنتج","version":"الإصدار","date":"التاريخ"}', '{"product_name":"text","version":"text","audience":"text"}', 32, 4.3, 1, TRUE, 'public'),
('قالب توثيق API', 'TMP-API-001', 'api_doc', 'technical', 'md', '/templates/api-doc.md', 'قالب لتوثيق واجهات API', '{"api":"اسم API","version":"الإصدار","base_url":"الرابط الأساسي"}', '{"api_name":"text","version":"text","endpoints":"array"}', 15, 4.2, 2, TRUE, 'public'),
('قالب هيكلية النظام', 'TMP-ARCH-001', 'technical', 'technical', 'docx', '/templates/architecture-template.docx', 'قالب لتوثيق هيكلية النظام', '{"system":"اسم النظام","version":"الإصدار"}', '{"system_name":"text","components":"array","interactions":"text"}', 12, 4.6, 4, TRUE, 'public'),
('قالب تقرير التقدم', 'TMP-REP-001', 'report', 'monthly', 'docx', '/templates/progress-report.docx', 'قالب لتقارير التقدم', '{"project":"المشروع","period":"الفترة","author":"المؤلف"}', '{"project_name":"text","start_date":"date","end_date":"date","progress":"number"}', 22, 4.1, 1, TRUE, 'public'),
('قالب دليل التثبيت', 'TMP-INS-001', 'technical', 'technical', 'docx', '/templates/installation-guide.docx', 'قالب لأدلة التثبيت', '{"system":"النظام","version":"الإصدار"}', '{"system_name":"text","requirements":"array","steps":"array"}', 14, 4.4, 3, TRUE, 'public'),
('قالب خطة الاختبارات', 'TMP-TEST-001', 'technical', 'technical', 'docx', '/templates/test-plan.docx', 'قالب لخطط الاختبارات', '{"project":"المشروع","tester":"المختبر"}', '{"project_name":"text","test_cases":"array","environment":"text"}', 8, 4.0, 2, TRUE, 'team'),
('قالب تقرير الأداء', 'TMP-PERF-001', 'report', 'monthly', 'xlsx', '/templates/performance-report.xlsx', 'قالب لتقارير الأداء', '{"period":"الفترة","department":"القسم"}', '{"period":"text","metrics":"array","targets":"array"}', 10, 3.9, 1, TRUE, 'team'),
('قالب دليل المشرف', 'TMP-ADMIN-001', 'user_manual', 'technical', 'docx', '/templates/admin-guide.docx', 'قالب لأدلة المشرفين', '{"system":"النظام","version":"الإصدار"}', '{"system_name":"text","commands":"array","monitoring":"text"}', 9, 4.2, 3, TRUE, 'public'),
('قالب تقرير الامتثال', 'TMP-COMP-001', 'compliance', 'security', 'docx', '/templates/compliance-report.docx', 'قالب لتقارير الامتثال', '{"standard":"المعيار","auditor":"المدقق"}', '{"standard_name":"text","requirements":"array","compliance_level":"select"}', 6, 4.5, 5, FALSE, 'private'),
('قالب العقد', 'TMP-CON-001', 'contract', 'custom', 'docx', '/templates/contract.docx', 'قالب للعقود', '{"client":"العميل","project":"المشروع","value":"القيمة"}', '{"client_name":"text","project_name":"text","contract_value":"number","start_date":"date"}', 5, 4.7, 5, FALSE, 'private'),
('قالب طلب تغيير', 'TMP-CHG-001', 'technical', 'custom', 'docx', '/templates/change-request.docx', 'قالب لطلبات التغيير', '{"project":"المشروع","requester":"الطالب"}', '{"project_name":"text","change_description":"text","impact":"text"}', 7, 4.0, 2, TRUE, 'team'),
('قالب تسليم المشروع', 'TMP-DEL-001', 'report', 'final', 'docx', '/templates/delivery-report.docx', 'قالب لتسليم المشاريع', '{"project":"المشروع","client":"العميل"}', '{"project_name":"text","deliverables":"array","acceptance_criteria":"array"}', 4, 4.3, 1, TRUE, 'public'),
('قالب دراسة جدوى', 'TMP-FEA-001', 'report', 'custom', 'docx', '/templates/feasibility-study.docx', 'قالب لدراسات الجدوى', '{"project":"المشروع","analyst":"المحلل"}', '{"project_name":"text","cost_estimate":"number","benefits":"array","risks":"array"}', 3, 4.1, 1, TRUE, 'team'),
('قالب خطة الجودة', 'TMP-QUAL-001', 'compliance', 'custom', 'docx', '/templates/quality-plan.docx', 'قالب لخطط الجودة', '{"project":"المشروع","qa_lead":"مسؤول الجودة"}', '{"project_name":"text","standards":"array","processes":"array"}', 5, 4.2, 4, TRUE, 'team'),
('قالب تقييم المخاطر', 'TMP-RISK-001', 'security', 'security', 'docx', '/templates/risk-assessment.docx', 'قالب لتقييم المخاطر', '{"project":"المشروع","assessor":"المقيم"}', '{"project_name":"text","risks":"array","mitigation":"text"}', 8, 4.4, 5, TRUE, 'public'),
('قالب خطة الاستجابة', 'TMP-RESP-001', 'security', 'security', 'docx', '/templates/response-plan.docx', 'قالب لخطط الاستجابة للحوادث', '{"system":"النظام","team":"الفريق"}', '{"system_name":"text","incident_types":"array","procedures":"array"}', 4, 4.5, 5, FALSE, 'private'),
('قالب دعم المستخدم', 'TMP-SUPP-001', 'user_manual', 'technical', 'md', '/templates/support-guide.md', 'قالب لأدلة الدعم', '{"product":"المنتج","support_team":"فريق الدعم"}', '{"product_name":"text","faq":"array","troubleshooting":"array"}', 6, 4.0, 2, TRUE, 'public'),
('قالب توثيق قاعدة بيانات', 'TMP-DB-001', 'database', 'technical', 'md', '/templates/database-doc.md', 'قالب لتوثيق قواعد البيانات', '{"database":"قاعدة البيانات","version":"الإصدار"}', '{"db_name":"text","tables":"array","relationships":"text"}', 7, 4.3, 1, TRUE, 'public'),
('قالب دليل المطور', 'TMP-DEV-001', 'technical', 'technical', 'md', '/templates/developer-guide.md', 'قالب لأدلة المطورين', '{"project":"المشروع","language":"لغة البرمجة"}', '{"project_name":"text","setup":"text","code_examples":"array"}', 9, 4.2, 3, TRUE, 'team'),
('قالب تقرير الحوادث', 'TMP-INCID-001', 'security', 'security', 'docx', '/templates/incident-report.docx', 'قالب لتقارير الحوادث', '{"incident":"الحادث","reporter":"المبلغ"}', '{"incident_id":"text","severity":"select","description":"text","resolution":"text"}', 5, 4.1, 5, FALSE, 'private'),
('قالب مراجعة الكود', 'TMP-CODE-001', 'technical', 'custom', 'md', '/templates/code-review.md', 'قالب لمراجعات الكود', '{"project":"المشروع","reviewer":"المراجع"}', '{"component":"text","code_quality":"text","security_issues":"array"}', 4, 4.0, 2, TRUE, 'team'),
('قالب خطة النشر', 'TMP-DEPLOY-001', 'technical', 'technical', 'docx', '/templates/deployment-plan.docx', 'قالب لخطط النشر', '{"system":"النظام","environment":"البيئة"}', '{"system_name":"text","environment":"select","steps":"array","rollback":"text"}', 6, 4.3, 4, TRUE, 'public'),
('قالب متطلبات التدريب', 'TMP-TRAIN-001', 'technical', 'custom', 'docx', '/templates/training-requirements.docx', 'قالب لمتطلبات التدريب', '{"project":"المشروع","audience":"الجمهور"}', '{"project_name":"text","training_needs":"array","materials":"array"}', 3, 4.0, 1, TRUE, 'team'),
('قالب خطة التعافي', 'TMP-RECOV-001', 'security', 'security', 'docx', '/templates/recovery-plan.docx', 'قالب لخطط التعافي من الكوارث', '{"system":"النظام","rto":"RTO","rpo":"RPO"}', '{"system_name":"text","critical_services":"array","backup_procedures":"array","recovery_steps":"array"}', 3, 4.6, 5, FALSE, 'private'),
('قالب تقرير الاجتماع', 'TMP-MEET-001', 'report', 'custom', 'docx', '/templates/meeting-minutes.docx', 'قالب لمحاضر الاجتماعات', '{"project":"المشروع","date":"التاريخ"}', '{"project_name":"text","attendees":"array","discussion_points":"array","action_items":"array"}', 12, 4.0, 1, TRUE, 'public'),
('قالب تقييم البائع', 'TMP-VEND-001', 'report', 'custom', 'docx', '/templates/vendor-assessment.docx', 'قالب لتقييم البائعين', '{"vendor":"البائع","assessor":"المقيم"}', '{"vendor_name":"text","criteria":"array","score":"number","recommendation":"text"}', 2, 3.8, 1, TRUE, 'team'),
('قالب خطة المشروع', 'TMP-PROJ-001', 'report', 'custom', 'docx', '/templates/project-plan.docx', 'قالب لخطط المشاريع', '{"project":"المشروع","manager":"المدير"}', '{"project_name":"text","objectives":"array","milestones":"array","resources":"array"}', 5, 4.2, 4, TRUE, 'public'),
('قالب طلب عرض', 'TMP-RFP-001', 'contract', 'custom', 'docx', '/templates/rfp.docx', 'قالب لطلبات العروض', '{"project":"المشروع","deadline":"الموعد النهائي"}', '{"project_name":"text","scope":"text","requirements":"array","submission_guidelines":"text"}', 3, 4.1, 5, FALSE, 'private');

-- =============================================
-- 11. متغيرات القوالب (template_variables) - 50 سجل
-- =============================================
INSERT INTO template_variables (template_id, variable_name, variable_key, variable_type, default_value, description, is_required, options, order_number) VALUES
(1, 'اسم المشروع', 'project_name', 'text', NULL, 'الاسم الكامل للمشروع', TRUE, NULL, 1),
(1, 'اسم العميل', 'client_name', 'text', NULL, 'اسم العميل أو الشركة', TRUE, NULL, 2),
(1, 'الإصدار', 'version', 'text', '1.0.0', 'نسخة المستند', FALSE, NULL, 3),
(1, 'التاريخ', 'date', 'date', NULL, 'تاريخ إنشاء المستند', TRUE, NULL, 4),
(1, 'المؤلف', 'author', 'user', NULL, 'منشئ المستند', TRUE, NULL, 5),
(2, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', TRUE, NULL, 1),
(2, 'مستوى الأمان', 'security_level', 'select', 'normal', 'مستوى حساسية المشروع', TRUE, '["normal","sensitive","critical"]', 2),
(2, 'عدد الثغرات', 'findings', 'number', '0', 'عدد الثغرات المكتشفة', TRUE, NULL, 3),
(2, 'تاريخ التقييم', 'assessment_date', 'date', NULL, 'تاريخ إجراء التقييم', TRUE, NULL, 4),
(2, 'المدقق', 'auditor', 'user', NULL, 'الشخص الذي أجرى التقييم', TRUE, NULL, 5),
(3, 'اسم المنتج', 'product_name', 'text', NULL, 'اسم المنتج أو النظام', TRUE, NULL, 1),
(3, 'الإصدار', 'version', 'text', '1.0.0', 'إصدار المنتج', TRUE, NULL, 2),
(3, 'الجمهور المستهدف', 'audience', 'text', 'المستخدمين النهائيين', 'الفئة المستهدفة من الدليل', FALSE, NULL, 3),
(4, 'اسم API', 'api_name', 'text', NULL, 'اسم واجهة البرمجة', TRUE, NULL, 1),
(4, 'الإصدار', 'version', 'text', 'v1', 'إصدار API', TRUE, NULL, 2),
(4, 'الرابط الأساسي', 'base_url', 'text', 'https://api.example.com', 'الرابط الأساسي للAPI', TRUE, NULL, 3),
(5, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام', TRUE, NULL, 1),
(5, 'المكونات', 'components', 'array', '[]', 'قائمة المكونات الرئيسية', TRUE, NULL, 2),
(6, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', TRUE, NULL, 1),
(6, 'تاريخ البداية', 'start_date', 'date', NULL, 'بداية الفترة', TRUE, NULL, 2),
(6, 'تاريخ النهاية', 'end_date', 'date', NULL, 'نهاية الفترة', TRUE, NULL, 3),
(6, 'نسبة التقدم', 'progress', 'number', '0', 'نسبة الإنجاز', TRUE, NULL, 4),
(7, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام المراد تثبيته', TRUE, NULL, 1),
(7, 'المتطلبات', 'requirements', 'array', '[]', 'متطلبات التثبيت', TRUE, NULL, 2),
(7, 'الخطوات', 'steps', 'array', '[]', 'خطوات التثبيت', TRUE, NULL, 3),
(8, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', TRUE, NULL, 1),
(8, 'حالات الاختبار', 'test_cases', 'array', '[]', 'قائمة حالات الاختبار', TRUE, NULL, 2),
(9, 'الفترة', 'period', 'text', NULL, 'الفترة المشمولة بالتقرير', TRUE, NULL, 1),
(9, 'المقاييس', 'metrics', 'array', '[]', 'مقاييس الأداء', TRUE, NULL, 2),
(10, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام', TRUE, NULL, 1),
(10, 'الأوامر', 'commands', 'array', '[]', 'أوامر الإدارة', TRUE, NULL, 2),
(11, 'اسم المعيار', 'standard_name', 'text', NULL, 'اسم معيار الامتثال', TRUE, NULL, 1),
(11, 'المتطلبات', 'requirements', 'array', '[]', 'متطلبات الامتثال', TRUE, NULL, 2),
(11, 'مستوى الامتثال', 'compliance_level', 'select', 'partial', 'مستوى المطابقة', TRUE, '["none","partial","full"]', 3),
(12, 'اسم العميل', 'client_name', 'text', NULL, 'اسم العميل', TRUE, NULL, 1),
(12, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', TRUE, NULL, 2),
(12, 'قيمة العقد', 'contract_value', 'number', '0', 'قيمة العقد', TRUE, NULL, 3),
(13, 'وصف التغيير', 'change_description', 'text', NULL, 'وصف التغيير المطلوب', TRUE, NULL, 1),
(13, 'الأثر', 'impact', 'text', NULL, 'أثر التغيير على المشروع', TRUE, NULL, 2),
(14, 'التسليمات', 'deliverables', 'array', '[]', 'قائمة بالتسليمات', TRUE, NULL, 1),
(15, 'تقدير التكلفة', 'cost_estimate', 'number', '0', 'تقدير تكلفة المشروع', TRUE, NULL, 1),
(16, 'المعايير', 'standards', 'array', '[]', 'معايير الجودة المطبقة', TRUE, NULL, 1),
(17, 'المخاطر', 'risks', 'array', '[]', 'قائمة المخاطر', TRUE, NULL, 1),
(18, 'أنواع الحوادث', 'incident_types', 'array', '[]', 'أنواع الحوادث المشمولة', TRUE, NULL, 1),
(19, 'الأسئلة الشائعة', 'faq', 'array', '[]', 'أسئلة وأجوبة شائعة', TRUE, NULL, 1),
(20, 'الجداول', 'tables', 'array', '[]', 'قائمة جداول قاعدة البيانات', TRUE, NULL, 1),
(21, 'أمثلة الكود', 'code_examples', 'array', '[]', 'أمثلة على الكود', FALSE, NULL, 1),
(22, 'معرف الحادث', 'incident_id', 'text', NULL, 'معرف فريد للحادث', TRUE, NULL, 1),
(22, 'الخطورة', 'severity', 'select', 'medium', 'مستوى خطورة الحادث', TRUE, '["low","medium","high","critical"]', 2),
(23, 'مشاكل أمنية', 'security_issues', 'array', '[]', 'المشاكل الأمنية المكتشفة', TRUE, NULL, 1),
(24, 'البيئة', 'environment', 'select', 'development', 'بيئة النشر', TRUE, '["development","staging","production"]', 1),
(25, 'احتياجات التدريب', 'training_needs', 'array', '[]', 'الاحتياجات التدريبية', TRUE, NULL, 1),
(26, 'الخدمات الحرجة', 'critical_services', 'array', '[]', 'الخدمات التي يجب استعادتها أولاً', TRUE, NULL, 1),
(27, 'نقاط النقاش', 'discussion_points', 'array', '[]', 'نقاط تمت مناقشتها', TRUE, NULL, 1),
(28, 'معايير التقييم', 'criteria', 'array', '[]', 'معايير تقييم البائع', TRUE, NULL, 1),
(29, 'الأهداف', 'objectives', 'array', '[]', 'أهداف المشروع', TRUE, NULL, 1),
(30, 'النطاق', 'scope', 'text', NULL, 'نطاق العمل', TRUE, NULL, 1);

-- =============================================
-- 12. التقارير (reports) - 15 سجل
-- =============================================
INSERT INTO reports (report_code, report_title, report_type, recipient, priority, status, format, file_path, summary, notes, created_by, sent_date, approved_date) VALUES
('RPT-MON-001', 'التقرير الشهري - يناير 2024', 'monthly', 'manager', 'high', 'approved', 'pdf', '/reports/monthly/jan-2024.pdf', 'ملخص أداء وحدة التوثيق لشهر يناير', 'تم إنجاز 15 مستند ومراجعة 8', 1, '2024-02-01', '2024-01-31'),
('RPT-SEC-001', 'تقرير الأمن السيبراني - الربع الأول', 'security', 'security', 'urgent', 'sent', 'pdf', '/reports/security/q1-2024.pdf', 'تقييم أمني شامل للأنظمة', 'تم اكتشاف 12 ثغرة وعلاج 8 منها', 5, '2024-03-15', NULL),
('RPT-TECH-001', 'تقرير التقدم الفني - مشروع الاستضافة', 'progress', 'client', 'high', 'approved', 'pdf', '/reports/progress/hosting-feb.pdf', 'تقدم مشروع نظام الاستضافة', 'اكتمال مرحلة التصميم وبدء التطوير', 1, '2024-02-28', '2024-02-27'),
('RPT-TECH-002', 'تقرير التقدم - منصة التخزين', 'progress', 'manager', 'normal', 'ready', 'pdf', '/reports/progress/storage-mar.pdf', 'تقدم منصة التخزين السحابي', 'تم الانتهاء من توثيق API', 2, NULL, NULL),
('RPT-SEC-002', 'تقرير التدقيق الأمني - البنية التحتية', 'audit', 'security', 'high', 'approved', 'pdf', '/reports/audit/infrastructure-mar.pdf', 'نتائج تدقيق أمن البنية التحتية', 'جميع الأنظمة متوافقة مع المعايير', 5, '2024-03-20', '2024-03-19'),
('RPT-MON-002', 'التقرير الشهري - فبراير 2024', 'monthly', 'manager', 'normal', 'sent', 'pdf', '/reports/monthly/feb-2024.pdf', 'ملخص أداء وحدة التوثيق لشهر فبراير', 'تم إنجاز 12 مستند ومراجعة 10', 1, '2024-03-01', NULL),
('RPT-FIN-001', 'التقرير النهائي - مشروع البنك الأهلي', 'final', 'client', 'high', 'approved', 'pdf', '/reports/final/bank-project.pdf', 'تقرير إنجاز مشروع البنك الأهلي', 'تم تسليم جميع المستندات المطلوبة', 2, '2024-02-15', '2024-02-14'),
('RPT-COMP-001', 'تقرير الامتثال - ISO 27001', 'compliance', 'admin', 'critical', 'approved', 'pdf', '/reports/compliance/iso27001.pdf', 'تقييم الامتثال لمعايير ISO 27001', '85% من المتطلبات متحققة', 5, '2024-03-10', '2024-03-09'),
('RPT-SEC-003', 'تقرير اختبار الاختراق', 'security', 'security', 'urgent', 'sent', 'pdf', '/reports/security/penetration-test.pdf', 'نتائج اختبار الاختراق للنظام', 'تم اكتشاف 5 ثغرات حرجة', 5, '2024-03-18', NULL),
('RPT-PROG-001', 'تقرير التقدم - الربع الأول', 'progress', 'manager', 'high', 'ready', 'pdf', '/reports/progress/q1-2024.pdf', 'ملخص إنجازات الربع الأول', '45 مستند تم إنجازها', 1, NULL, NULL),
('RPT-TECH-003', 'تقرير فني - أداء API', 'technical', 'team', 'normal', 'ready', 'pdf', '/reports/technical/api-performance.pdf', 'تحليل أداء واجهات API', 'متوسط زمن الاستجابة 150ms', 2, NULL, NULL),
('RPT-SEC-004', 'تقرير الثغرات الأمنية', 'security', 'security', 'high', 'sent', 'pdf', '/reports/security/vulnerabilities.pdf', 'تقرير دوري عن الثغرات المكتشفة', 'تم معالجة 15 ثغرة من أصل 20', 5, '2024-03-05', NULL),
('RPT-MON-003', 'التقرير الشهري - مارس 2024', 'monthly', 'manager', 'high', 'preparing', 'pdf', NULL, 'ملخص أداء وحدة التوثيق لشهر مارس', 'قيد الإعداد', 1, NULL, NULL),
('RPT-AUD-001', 'تقرير تدقيق المستندات', 'audit', 'admin', 'normal', 'ready', 'xlsx', '/reports/audit/document-audit.xlsx', 'تدقيق شامل لجميع المستندات', '98% من المستندات متوافقة', 4, NULL, NULL),
('RPT-FIN-002', 'التقرير النهائي - مشروع الشبكات', 'final', 'client', 'high', 'approved', 'pdf', '/reports/final/network-project.pdf', 'تقرير إنجاز مشروع إدارة الشبكات', 'تم تسليم جميع المستندات والتدريب', 4, '2024-03-25', '2024-03-24');

-- =============================================
-- 13. ربط التقارير بالمستندات (report_documents) - 30 سجل
-- =============================================
INSERT INTO report_documents (report_id, document_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 3), (2, 8), (2, 9), (2, 10), (2, 11), (2, 17),
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5),
(4, 6), (4, 7), (4, 14),
(5, 8), (5, 9), (5, 10), (5, 16), (5, 17),
(6, 1), (6, 4), (6, 12), (6, 13), (6, 19),
(7, 1), (7, 2), (7, 3), (7, 4), (7, 5),
(8, 1), (8, 3), (8, 8), (8, 9), (8, 10),
(9, 3), (9, 8), (9, 11), (9, 17),
(10, 1), (10, 4), (10, 6), (10, 8), (10, 12), (10, 15), (10, 16), (10, 19),
(11, 6), (11, 7), (11, 14),
(12, 3), (12, 8), (12, 9), (12, 10), (12, 11), (12, 17),
(14, 1), (14, 2), (14, 3), (14, 4), (14, 5), (14, 6), (14, 7), (14, 8), (14, 9), (14, 10),
(15, 15), (15, 16), (15, 17), (15, 18);

-- =============================================
-- 14. تحديثات المستندات (document_updates) - 20 سجل
-- =============================================
INSERT INTO document_updates (document_id, update_type, old_version, new_version, changes_summary, detailed_changes, created_by, reviewed_by, status, applied_at) VALUES
(1, 'major', '1.0', '1.1', 'تحديث متطلبات الأمان', 'إضافة متطلبات أمان جديدة للتشفير والمصادقة', 1, 2, 'applied', '2024-01-17 14:30:00'),
(1, 'minor', '1.1', '1.2', 'إضافة متطلبات الأداء', 'توثيق متطلبات الأداء والأحمال', 3, 5, 'applied', '2024-01-19 10:15:00'),
(3, 'major', '1.0', '2.0', 'تحديث شامل لنتائج الاختبارات', 'إضافة نتائج اختبارات جديدة وتوصيات', 3, 2, 'applied', '2024-01-23 16:45:00'),
(3, 'minor', '2.0', '2.1', 'تحديث التوصيات الأمنية', 'إضافة توصيات أمنية إضافية', 5, 2, 'applied', '2024-01-24 11:30:00'),
(4, 'major', '1.0', '1.5', 'تحديث شامل بعد المراجعة', 'إعادة هيكلة الدليل وتحديث المحتوى', 1, 5, 'applied', '2024-01-25 17:20:00'),
(5, 'minor', '1.0', '1.1', 'تحديث قسم المراقبة', 'إضافة أوامر مراقبة جديدة', 3, 2, 'applied', '2024-01-23 12:15:00'),
(5, 'minor', '1.1', '1.2', 'إضافة أوامر الإدارة', 'توثيق أوامر إدارية إضافية', 3, 2, 'applied', '2024-01-24 10:30:00'),
(9, 'major', '1.0', '2.0', 'تحديث هيكلية الأمان', 'إعادة تصميم هيكلية الأمان', 5, 2, 'applied', '2024-01-14 14:45:00'),
(9, 'security', '2.0', '2.3', 'إضافة طبقات حماية', 'إضافة جدران نارية وIDS/IPS', 3, 5, 'applied', '2024-01-16 16:20:00'),
(10, 'minor', '1.0', '1.1', 'تحديث إعدادات الجدار الناري', 'إضافة قواعد جديدة للجدار الناري', 3, 2, 'applied', '2024-01-17 15:40:00'),
(15, 'major', '1.0', '1.2', 'تحديث خطوات التثبيت', 'تبسيط وتحديث خطوات التثبيت', 4, 2, 'applied', '2024-02-18 12:30:00'),
(16, 'major', '1.0', '2.0', 'إعادة هيكلة الشبكة', 'تحديث هيكلية الشبكة بالكامل', 4, 5, 'applied', '2024-02-20 11:15:00'),
(20, 'minor', '1.0', '1.1', 'تحديث إحصائيات الأداء', 'تدقيق وتحديث أرقام الأداء', 3, 2, 'applied', '2024-03-04 10:45:00'),
(6, 'critical', '0.5', '0.9', 'تحديث أمني عاجل', 'إصلاح ثغرات أمنية في التوثيق', 2, 5, 'applied', '2024-02-15 09:20:00'),
(7, 'minor', '0.3', '0.5', 'إضافة أمثلة', 'إضافة أمثلة جديدة على استخدام API', 2, 1, 'pending', NULL),
(13, 'major', '0.5', '1.0', 'إكمال المتطلبات', 'إضافة جميع متطلبات المنصة', 4, 2, 'applied', '2024-03-05 14:30:00'),
(17, 'security', '0.8', '1.0', 'تحديث أمني', 'إضافة نتائج تقييم أمني جديد', 5, 2, 'pending', NULL),
(2, 'bugfix', '1.0', '1.0.1', 'إصلاح أخطاء', 'تصحيح أخطاء في دليل التثبيت', 1, 3, 'applied', '2024-01-25 13:15:00'),
(8, 'minor', '1.0', '1.0', NULL, 'لا توجد تغييرات', 5, NULL, 'failed', NULL),
(19, 'minor', '1.0', '1.0', 'تحديث بسيط', 'تحديث تنسيق التقرير', 1, 5, 'applied', '2024-03-26 11:30:00');

-- =============================================
-- 15. سجل النشاطات (documentation_activity_log) - 100 سجل
-- =============================================
INSERT INTO documentation_activity_log (user_id, activity_type, target_type, target_id, target_name, description, ip_address, created_at) VALUES
(1, 'create', 'document', 1, 'متطلبات نظام الاستضافة', 'إنشاء مستند متطلبات نظام الاستضافة', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 45 DAY)),
(1, 'update', 'document', 1, 'متطلبات نظام الاستضافة', 'تحديث متطلبات نظام الاستضافة', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 44 DAY)),
(3, 'create', 'document', 3, 'تقرير الاختبارات الأمنية', 'إنشاء تقرير الاختبارات الأمنية', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 43 DAY)),
(2, 'review', 'document', 1, 'متطلبات نظام الاستضافة', 'مراجعة متطلبات النظام', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 42 DAY)),
(5, 'approve', 'document', 1, 'متطلبات نظام الاستضافة', 'الموافقة على متطلبات النظام', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 41 DAY)),
(1, 'download', 'document', 1, 'متطلبات نظام الاستضافة', 'تحميل مستند المتطلبات', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 40 DAY)),
(2, 'comment', 'document', 1, 'متطلبات نظام الاستضافة', 'إضافة تعليق على المستند', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 39 DAY)),
(3, 'update', 'document', 3, 'تقرير الاختبارات الأمنية', 'تحديث تقرير الاختبارات', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(4, 'create', 'document', 13, 'متطلبات المنصة', 'إنشاء مستند متطلبات المنصة', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 37 DAY)),
(2, 'review', 'document', 3, 'تقرير الاختبارات الأمنية', 'مراجعة التقرير الأمني', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 36 DAY)),
(1, 'create', 'project', 1, 'نظام استضافة المواقع', 'إنشاء مشروع نظام الاستضافة', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 35 DAY)),
(5, 'create', 'project', 3, 'نظام الحماية الأمني', 'إنشاء مشروع نظام الحماية', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 34 DAY)),
(2, 'view', 'document', 4, 'دليل مستخدم نظام الاستضافة', 'عرض دليل المستخدم', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 33 DAY)),
(3, 'upload', 'file', NULL, 'تقرير أمني.pdf', 'رفع ملف تقرير أمني', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 32 DAY)),
(1, 'export', 'document', 2, 'دليل تثبيت نظام الاستضافة', 'تصدير دليل التثبيت', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 31 DAY)),
(4, 'comment', 'document', 13, 'متطلبات المنصة', 'إضافة تعليق على المتطلبات', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5, 'update', 'document', 9, 'هيكلية النظام الأمني', 'تحديث هيكلية النظام', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(2, 'approve', 'document', 4, 'دليل مستخدم نظام الاستضافة', 'الموافقة على دليل المستخدم', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 'create', 'template', 1, 'قالب متطلبات النظام', 'إنشاء قالب متطلبات النظام', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(3, 'download', 'document', 8, 'تقرير أمني - البنية التحتية', 'تحميل التقرير الأمني', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(5, 'create', 'report', 2, 'تقرير الأمن السيبراني', 'إنشاء تقرير الأمن السيبراني', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(4, 'review', 'document', 13, 'متطلبات المنصة', 'مراجعة متطلبات المنصة', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(2, 'share', 'document', 5, 'دليل المشرف', 'مشاركة دليل المشرف مع الفريق', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 23 DAY)),
(1, 'update', 'template', 1, 'قالب متطلبات النظام', 'تحديث قالب المتطلبات', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 22 DAY)),
(3, 'comment', 'document', 8, 'تقرير أمني - البنية التحتية', 'إضافة تعليق على التقرير', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 21 DAY)),
(5, 'approve', 'report', 2, 'تقرير الأمن السيبراني', 'الموافقة على التقرير', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'create', 'document', 19, 'تقرير التقدم - الربع الأول', 'إنشاء تقرير التقدم', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 19 DAY)),
(4, 'update', 'project', 4, 'منصة التجارة الإلكترونية', 'تحديث معلومات المشروع', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 18 DAY)),
(1, 'view', 'template', 2, 'قالب تقرير أمني', 'عرض قالب التقرير الأمني', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 17 DAY)),
(3, 'export', 'document', 6, 'توثيق API - REST', 'تصدير توثيق API', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 16 DAY)),
(5, 'delete', 'comment', NULL, NULL, 'حذف تعليق غير مناسب', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 'review', 'document', 19, 'تقرير التقدم - الربع الأول', 'مراجعة تقرير التقدم', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(1, 'upload', 'file', NULL, 'مخطط النظام.png', 'رفع مخطط توضيحي', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 13 DAY)),
(4, 'approve', 'document', 13, 'متطلبات المنصة', 'الموافقة على متطلبات المنصة', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 'create', 'report', 9, 'تقرير اختبار الاختراق', 'إنشاء تقرير اختبار الاختراق', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(5, 'update', 'template', 11, 'قالب تقرير الامتثال', 'تحديث قالب الامتثال', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 'comment', 'document', 20, 'تقرير الأداء الشهري', 'إضافة تعليق على التقرير', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(1, 'download', 'template', 3, 'قالب دليل المستخدم', 'تحميل قالب دليل المستخدم', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(4, 'create', 'document', 18, 'دليل استكشاف الأخطاء', 'إنشاء دليل استكشاف الأخطاء', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 'archive', 'document', 11, 'خطة اختبار الاختراق', 'أرشفة خطة الاختبار', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(5, 'review', 'report', 9, 'تقرير اختبار الاختراق', 'مراجعة تقرير الاختراق', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'approve', 'document', 20, 'تقرير الأداء الشهري', 'الموافقة على التقرير بعد التحديث', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'import', 'template', 5, 'قالب هيكلية النظام', 'استيراد قالب هيكلية', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'update', 'document', 18, 'دليل استكشاف الأخطاء', 'تحديث دليل استكشاف الأخطاء', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'share', 'report', 9, 'تقرير اختبار الاختراق', 'مشاركة التقرير مع فريق الأمان', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'view', 'project', 1, 'نظام استضافة المواقع', 'عرض تفاصيل المشروع', '192.168.1.11', NOW()),
(5, 'create', 'document', NULL, NULL, 'بدء إنشاء مستند جديد', '192.168.1.15', NOW()),
(1, 'comment', 'document', 16, 'هيكلية الشبكة', 'إضافة تعليق على هيكلية الشبكة', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(4, 'download', 'document', 15, 'دليل تثبيت الشبكة', 'تحميل دليل التثبيت', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'update', 'report', 9, 'تقرير اختبار الاختراق', 'تحديث نتائج الاختبارات', '192.168.1.12', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(2, 'review', 'document', 17, 'تقييم أمن الشبكة', 'بدء مراجعة تقييم الأمن', '192.168.1.11', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'export', 'project', 1, 'نظام استضافة المواقع', 'تصدير كافة مستندات المشروع', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'approve', 'template', 17, 'قالب تقييم المخاطر', 'الموافقة على قالب تقييم المخاطر', '192.168.1.15', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'upload', 'file', NULL, 'شهادة أمان.pdf', 'رفع شهادة أمان', '192.168.1.14', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- =============================================
-- 16. إحصائيات التوثيق (documentation_stats) - 30 سجل
-- =============================================
INSERT INTO documentation_stats (stat_date, projects_created, projects_completed, projects_in_progress, documents_created, documents_updated, documents_reviewed, documents_approved, documents_rejected, total_pages, total_documents, total_templates, total_reports, active_users, reviews_completed, comments_added, storage_used_mb) VALUES
('2024-01-01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 2, 0, 0, 0.00),
('2024-01-07', 1, 0, 1, 3, 0, 0, 0, 0, 125, 3, 5, 0, 3, 0, 2, 15.50),
('2024-01-14', 1, 0, 2, 5, 2, 1, 1, 0, 320, 8, 6, 1, 4, 1, 8, 45.20),
('2024-01-21', 1, 0, 3, 4, 5, 3, 2, 1, 450, 12, 7, 1, 5, 3, 15, 78.90),
('2024-01-28', 1, 0, 3, 3, 4, 4, 3, 0, 280, 15, 8, 2, 5, 4, 12, 95.40),
('2024-02-04', 0, 0, 3, 4, 3, 2, 2, 0, 210, 19, 9, 2, 4, 2, 7, 115.30),
('2024-02-11', 0, 1, 2, 3, 5, 3, 3, 0, 185, 22, 10, 3, 5, 3, 10, 132.80),
('2024-02-18', 1, 0, 3, 5, 3, 4, 3, 1, 320, 27, 12, 3, 5, 4, 14, 158.20),
('2024-02-25', 1, 0, 4, 4, 6, 5, 4, 0, 290, 31, 14, 4, 5, 5, 18, 182.50),
('2024-03-03', 0, 0, 4, 5, 4, 3, 3, 0, 275, 36, 16, 5, 4, 3, 12, 205.80),
('2024-03-10', 0, 0, 4, 4, 7, 5, 4, 1, 310, 40, 18, 6, 5, 5, 20, 232.40),
('2024-03-17', 1, 0, 4, 5, 5, 4, 3, 0, 340, 45, 22, 7, 5, 4, 16, 262.30),
('2024-03-24', 0, 1, 3, 3, 8, 6, 5, 0, 260, 48, 25, 8, 5, 6, 22, 285.70),
('2024-03-31', 0, 0, 3, 2, 4, 3, 2, 0, 150, 50, 28, 9, 4, 3, 10, 298.50),
('2024-04-07', 1, 0, 4, 4, 5, 4, 3, 0, 280, 54, 30, 10, 5, 4, 15, 322.00);

-- =============================================
-- 17. ملفات المستودع (repository_files) - 50 سجل
-- =============================================
INSERT INTO repository_files (file_name, file_path, file_type, file_size, mime_type, folder_path, project_id, document_id, uploaded_by, version, download_count) VALUES
('requirements_v1.2.pdf', '/repositories/hosting-project/requirements_v1.2.pdf', 'pdf', 2450000, 'application/pdf', '/hosting-project/documents', 1, 1, 1, '1.2', 15),
('installation_v1.0.pdf', '/repositories/hosting-project/installation_v1.0.pdf', 'pdf', 1850000, 'application/pdf', '/hosting-project/documents', 1, 2, 1, '1.0', 8),
('security-test_v2.1.pdf', '/repositories/hosting-project/security-test_v2.1.pdf', 'pdf', 3200000, 'application/pdf', '/hosting-project/documents', 1, 3, 3, '2.1', 12),
('user-guide_v1.5.pdf', '/repositories/hosting-project/user-guide_v1.5.pdf', 'pdf', 4250000, 'application/pdf', '/hosting-project/documents', 1, 4, 1, '1.5', 25),
('admin-guide_v1.2.pdf', '/repositories/hosting-project/admin-guide_v1.2.pdf', 'pdf', 2100000, 'application/pdf', '/hosting-project/documents', 1, 5, 3, '1.2', 10),
('api-docs_v0.9.html', '/repositories/cloud-storage/api-docs_v0.9.html', 'html', 950000, 'text/html', '/cloud-storage/api', 2, 6, 2, '0.9', 5),
('examples_v0.5.md', '/repositories/cloud-storage/examples_v0.5.md', 'md', 45000, 'text/markdown', '/cloud-storage/examples', 2, 7, 2, '0.5', 3),
('infrastructure_v1.0.pdf', '/repositories/security-system/infrastructure_v1.0.pdf', 'pdf', 5600000, 'application/pdf', '/security-system/audits', 3, 8, 5, '1.0', 20),
('architecture_v2.3.pdf', '/repositories/security-system/architecture_v2.3.pdf', 'pdf', 3800000, 'application/pdf', '/security-system/architecture', 3, 9, 5, '2.3', 18),
('configuration_v1.1.pdf', '/repositories/security-system/configuration_v1.1.pdf', 'pdf', 1950000, 'application/pdf', '/security-system/config', 3, 10, 3, '1.1', 14),
('penetration-test_v1.0.pdf', '/repositories/security-system/penetration-test_v1.0.pdf', 'pdf', 1250000, 'application/pdf', '/security-system/tests', 3, 11, 3, '1.0', 9),
('user-guide_v0.8.pdf', '/repositories/ecommerce-platform/user-guide_v0.8.pdf', 'pdf', 2850000, 'application/pdf', '/ecommerce-platform/guides', 4, 12, 2, '0.8', 6),
('requirements_v1.0.pdf', '/repositories/ecommerce-platform/requirements_v1.0.pdf', 'pdf', 3150000, 'application/pdf', '/ecommerce-platform/docs', 4, 13, 4, '1.0', 7),
('payment-api_v0.5.md', '/repositories/ecommerce-platform/payment-api_v0.5.md', 'md', 250000, 'text/markdown', '/ecommerce-platform/api', 4, 14, 2, '0.5', 4),
('installation_v1.2.pdf', '/repositories/network-management/installation_v1.2.pdf', 'pdf', 1850000, 'application/pdf', '/network-management/install', 5, 15, 4, '1.2', 11),
('architecture_v2.0.pdf', '/repositories/network-management/architecture_v2.0.pdf', 'pdf', 4200000, 'application/pdf', '/network-management/design', 5, 16, 4, '2.0', 16),
('security-assessment_v1.0.pdf', '/repositories/network-management/security-assessment_v1.0.pdf', 'pdf', 2350000, 'application/pdf', '/network-management/security', 5, 17, 5, '1.0', 8),
('troubleshooting_v0.9.pdf', '/repositories/network-management/troubleshooting_v0.9.pdf', 'pdf', 1650000, 'application/pdf', '/network-management/support', 5, 18, 2, '0.9', 5),
('q1-progress_v1.0.pdf', '/repositories/reports/q1-progress_v1.0.pdf', 'pdf', 980000, 'application/pdf', '/reports/quarterly', 1, 19, 1, '1.0', 22),
('monthly-performance_v1.1.pdf', '/repositories/reports/monthly-performance_v1.1.pdf', 'pdf', 1120000, 'application/pdf', '/reports/monthly', 2, 20, 3, '1.1', 13),
('network-diagram.png', '/repositories/network-management/network-diagram.png', 'png', 850000, 'image/png', '/network-management/images', 5, NULL, 4, '1.0', 30),
('security-logo.svg', '/repositories/security-system/security-logo.svg', 'svg', 45000, 'image/svg+xml', '/security-system/assets', 3, NULL, 5, '1.0', 12),
('api-schema.json', '/repositories/cloud-storage/api-schema.json', 'json', 125000, 'application/json', '/cloud-storage/schemas', 2, NULL, 2, '1.2', 8),
('database-schema.sql', '/repositories/hosting-project/database-schema.sql', 'sql', 350000, 'application/sql', '/hosting-project/db', 1, NULL, 1, '2.1', 15),
('deployment-script.sh', '/repositories/hosting-project/deployment-script.sh', 'sh', 25000, 'application/x-shellscript', '/hosting-project/scripts', 1, NULL, 3, '1.5', 20),
('config-template.yaml', '/repositories/security-system/config-template.yaml', 'yaml', 18000, 'application/x-yaml', '/security-system/templates', 3, NULL, 5, '1.0', 25),
('backup-script.ps1', '/repositories/network-management/backup-script.ps1', 'ps1', 32000, 'application/x-powershell', '/network-management/scripts', 5, NULL, 4, '2.0', 10),
('training-materials.pptx', '/repositories/ecommerce-platform/training-materials.pptx', 'pptx', 5250000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '/ecommerce-platform/training', 4, NULL, 2, '1.0', 7),
('security-policy.docx', '/repositories/security-system/security-policy.docx', 'docx', 1850000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/security-system/policies', 3, NULL, 5, '3.2', 18),
('requirements-template.docx', '/repositories/templates/requirements-template.docx', 'docx', 450000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/technical', NULL, NULL, 1, '2.0', 45),
('security-report-template.docx', '/repositories/templates/security-report-template.docx', 'docx', 520000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '1.8', 38),
('user-manual-template.docx', '/repositories/templates/user-manual-template.docx', 'docx', 680000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/guides', NULL, NULL, 1, '3.0', 52),
('api-doc-template.md', '/repositories/templates/api-doc-template.md', 'md', 35000, 'text/markdown', '/templates/api', NULL, NULL, 2, '1.3', 27),
('architecture-template.docx', '/repositories/templates/architecture-template.docx', 'docx', 580000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/technical', NULL, NULL, 4, '1.1', 19),
('project-plan-template.xlsx', '/repositories/templates/project-plan-template.xlsx', 'xlsx', 890000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '/templates/management', NULL, NULL, 1, '1.0', 23),
('risk-assessment-template.docx', '/repositories/templates/risk-assessment-template.docx', 'docx', 420000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '2.2', 16),
('incident-report-template.docx', '/repositories/templates/incident-report-template.docx', 'docx', 380000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '1.4', 12),
('code-review-template.md', '/repositories/templates/code-review-template.md', 'md', 28000, 'text/markdown', '/templates/development', NULL, NULL, 2, '1.0', 9),
('deployment-plan-template.docx', '/repositories/templates/deployment-plan-template.docx', 'docx', 490000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/operations', NULL, NULL, 4, '1.2', 14),
('meeting-minutes-template.docx', '/repositories/templates/meeting-minutes-template.docx', 'docx', 320000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/management', NULL, NULL, 1, '2.1', 31),
('test-plan-template.xlsx', '/repositories/templates/test-plan-template.xlsx', 'xlsx', 750000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '/templates/testing', NULL, NULL, 3, '1.5', 17),
('contract-template.docx', '/repositories/templates/contract-template.docx', 'docx', 610000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/legal', NULL, NULL, 5, '3.0', 11),
('rfp-template.docx', '/repositories/templates/rfp-template.docx', 'docx', 720000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/procurement', NULL, NULL, 5, '1.1', 6),
('system-diagram.vsdx', '/repositories/network-management/system-diagram.vsdx', 'vsdx', 1850000, 'application/vnd.visio', '/network-management/diagrams', 5, NULL, 4, '1.0', 22),
('api-collection.postman.json', '/repositories/cloud-storage/api-collection.postman.json', 'json', 215000, 'application/json', '/cloud-storage/postman', 2, NULL, 2, '1.2', 35),
('load-test-script.js', '/repositories/hosting-project/load-test-script.js', 'js', 42000, 'application/javascript', '/hosting-project/tests', 1, NULL, 3, '1.0', 13),
('docker-compose.yml', '/repositories/hosting-project/docker-compose.yml', 'yml', 8500, 'application/x-yaml', '/hosting-project/docker', 1, NULL, 1, '2.3', 28),
('jenkinsfile', '/repositories/security-system/jenkinsfile', 'groovy', 12500, 'text/plain', '/security-system/ci-cd', 3, NULL, 5, '1.1', 19),
('terraform-config.tf', '/repositories/cloud-storage/terraform-config.tf', 'tf', 28000, 'text/plain', '/cloud-storage/terraform', 2, NULL, 4, '0.12', 15),
('ansible-playbook.yml', '/repositories/network-management/ansible-playbook.yml', 'yml', 32000, 'application/x-yaml', '/network-management/ansible', 5, NULL, 4, '2.0', 21);

-- =============================================
-- 18. إعدادات النظام (system_settings)
-- =============================================
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, updated_by) VALUES
('site_name', 'نظام التوثيق الفني', 'text', 'اسم الموقع', 5),
('company_name', 'شركة الاستضافة والحماية', 'text', 'اسم الشركة', 5),
('date_format', 'Y-m-d', 'text', 'تنسيق التاريخ', 5),
('timezone', 'Asia/Riyadh', 'text', 'المنطقة الزمنية', 5),
('items_per_page', '20', 'number', 'عدد العناصر في الصفحة', 5),
('enable_notifications', 'true', 'boolean', 'تفعيل الإشعارات', 5),
('default_document_status', 'draft', 'text', 'الحالة الافتراضية للمستندات', 5),
('max_file_size', '10485760', 'number', 'الحد الأقصى لحجم الملف (بايت)', 5),
('allowed_file_types', '["pdf","docx","xlsx","pptx","txt","md","html","png","jpg"]', 'json', 'أنواع الملفات المسموح بها', 5),
('repository_path', '/repositories', 'text', 'مسار المستودع الرئيسي', 5),
('backup_enabled', 'true', 'boolean', 'تفعيل النسخ الاحتياطي', 5),
('backup_frequency', 'daily', 'text', 'تكرار النسخ الاحتياطي', 5),
('retention_period', '30', 'number', 'فترة الاحتفاظ بالنسخ (يوم)', 5),
('mail_driver', 'smtp', 'text', 'نوع بريد الإرسال', 5),
('mail_host', 'smtp.gmail.com', 'text', 'خادم البريد', 5),
('mail_port', '587', 'number', 'منفذ البريد', 5),
('mail_username', 'notifications@example.com', 'text', 'اسم مستخدم البريد', 5),
('mail_encryption', 'tls', 'text', 'تشفير البريد', 5),
('notification_email', 'admin@example.com', 'text', 'البريد الإلكتروني للإشعارات', 5),
('default_language', 'ar', 'text', 'اللغة الافتراضية', 5),
('rtl_enabled', 'true', 'boolean', 'تفعيل الاتجاه من اليمين لليسار', 5),
('enable_versioning', 'true', 'boolean', 'تفعيل إدارة الإصدارات', 5),
('auto_archive_days', '365', 'number', 'أرشفة تلقائية بعد (يوم)', 5),
('require_review', 'true', 'boolean', 'طلب مراجعة قبل الاعتماد', 5),
('max_review_rounds', '3', 'number', 'الحد الأقصى لمرات المراجعة', 5),
('session_timeout', '3600', 'number', 'مهلة الجلسة (ثانية)', 5),
('maintenance_mode', 'false', 'boolean', 'وضع الصيانة', 5),
('google_analytics_id', 'UA-12345678-1', 'text', 'معرف Google Analytics', 5),
('meta_description', 'نظام متكامل لإدارة التوثيق الفني', 'text', 'وصف الموقع', 5),
('meta_keywords', 'توثيق, تقني, مستندات, مشاريع', 'text', 'كلمات مفتاحية', 5),
('contact_email', 'support@example.com', 'text', 'بريد التواصل', 5),
('support_phone', '+966123456789', 'text', 'رقم الدعم', 5);



ALTER TABLE document_reviews 
ADD COLUMN priority ENUM('low', 'medium', 'high') DEFAULT 'medium' AFTER status;

UPDATE document_reviews SET priority = 'high' WHERE created_at > DATE_SUB(NOW(), INTERVAL 3 DAY);
UPDATE document_reviews SET priority = 'medium' WHERE priority IS NULL;
-- =============================================
-- ✅ التحقق من البيانات
-- =============================================
SELECT '✅ تم إنشاء جميع جداول وحدة التوثيق بنجاح' as 'نتيجة التثبيت';

SELECT CONCAT('📋 عدد المشاريع: ', COUNT(*)) as 'إحصائيات' FROM documentation_projects
UNION ALL
SELECT CONCAT('📄 عدد المستندات: ', COUNT(*)) FROM documents
UNION ALL
SELECT CONCAT('📝 عدد قوالب التوثيق: ', COUNT(*)) FROM document_templates
UNION ALL
SELECT CONCAT('💬 عدد التعليقات: ', COUNT(*)) FROM document_comments
UNION ALL
SELECT CONCAT('📊 عدد سجلات النشاط: ', COUNT(*)) FROM documentation_activity_log
UNION ALL
SELECT CONCAT('🗂️ عدد ملفات المستودع: ', COUNT(*)) FROM repository_files
UNION ALL
SELECT CONCAT('🏷️ عدد الوسوم: ', COUNT(*)) FROM tags
UNION ALL
SELECT CONCAT('📑 عدد التقارير: ', COUNT(*)) FROM reports;

-- =============================================
-- عرض ملخص البيانات
-- =============================================
SELECT 
    (SELECT COUNT(*) FROM users) as 'المستخدمين',
    (SELECT COUNT(*) FROM documentation_projects) as 'المشاريع',
    (SELECT COUNT(*) FROM documents) as 'المستندات',
    (SELECT COUNT(*) FROM document_versions) as 'الإصدارات',
    (SELECT COUNT(*) FROM document_reviews) as 'المراجعات',
    (SELECT COUNT(*) FROM document_comments) as 'التعليقات',
    (SELECT COUNT(*) FROM document_templates) as 'القوالب',
    (SELECT COUNT(*) FROM reports) as 'التقارير',
    (SELECT COUNT(*) FROM repository_files) as 'ملفات المستودع',
    (SELECT COUNT(*) FROM documentation_activity_log) as 'سجلات النشاط';

-- =============================================
-- عرض آخر المستندات المضافة
-- =============================================
SELECT 
    d.document_code as 'رمز المستند',
    d.title as 'العنوان',
    p.project_name as 'المشروع',
    d.document_type as 'النوع',
    d.version as 'الإصدار',
    d.status as 'الحالة',
    d.created_date as 'تاريخ الإنشاء'
FROM documents d
LEFT JOIN documentation_projects p ON d.project_id = p.id
ORDER BY d.created_date DESC
LIMIT 10;

-- =============================================
-- عرض إحصائيات المشاريع
-- =============================================
SELECT 
    p.project_name as 'المشروع',
    p.status as 'الحالة',
    p.progress as 'التقدم %',
    p.documents_count as 'عدد المستندات',
    p.pages_count as 'عدد الصفحات',
    CONCAT(p.start_date, ' - ', p.deadline) as 'المدة'
FROM documentation_projects p
ORDER BY p.created_at DESC;

-- =============================================
-- نهاية ملف قاعدة البيانات
-- =============================================

ايوه و كان ذا الهيكل documentation-unit/
│
├── index.php                               # الصفحة الرئيسية للوحدة
│
├── config/
│   └── database.php                        # اتصال قاعدة البيانات
│
├── includes/
│   ├── functions.php                        # دوال مساعدة عامة
│   ├── auth.php                              # التحقق من المستخدم
│   └── documentation_functions.php           # دوال خاصة بوحدة التوثيق
│
├── assets/
│   ├── css/
│   │   └── style.css                          # ملفات CSS إضافية
│   └── js/
│       └── app.js                              # ملف JavaScript الرئيسي
│
└── pages/
    ├── dashboard.php                          # لوحة التحكم الرئيسية
    ├── projects.php                            # المشاريع قيد التوثيق
    ├── documents.php                           # إدارة المستندات
    ├── templates.php                            # قوالب التوثيق
    ├── archive.php                               # أرشيف الوثائق
    ├── reports.php                               # تقارير التوثيق
    └── history.php                               # سجل النشاطات