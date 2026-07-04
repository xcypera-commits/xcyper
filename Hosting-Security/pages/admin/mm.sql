

USE security_monitoring_db;




-- 10. من activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN al.action_type LIKE 'login%' THEN 'login'
        WHEN al.action_type LIKE 'file%' THEN 'file_upload'
        WHEN al.action_type LIKE 'project%' THEN 'project_create'
        ELSE 'api_call'
    END,
    al.action_type,
    al.description, al.ip_address, al.created_at,
    al.target_type, al.target_id
FROM `activity_log` al
LEFT JOIN `users_all` ua ON ua.source_id = al.user_id AND ua.user_source = 'user';

-- 11. من client_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`, `client_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN cal.activity_type LIKE 'login%' THEN 'login'
        WHEN cal.activity_type LIKE 'file%' THEN 'file_upload'
        ELSE 'api_call'
    END,
    cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id, cal.client_id
FROM `client_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.client_id 
    AND ua.user_source IN ('client', 'client_account');
/*
-- =====================================================
-- أولاً: إضافة عمود event_category إلى جدول user_events
-- =====================================================
ALTER TABLE `user_events` 
ADD COLUMN `event_category` VARCHAR(50) NULL AFTER `user_id`,
ADD INDEX `idx_event_category` (`event_category`);

-- =====================================================
-- ثانياً: تعبئة user_events من جميع الجداول
-- =====================================================

-- 1. من activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN al.action_type LIKE 'login%' THEN 'auth'
        WHEN al.action_type LIKE 'logout%' THEN 'auth'
        WHEN al.action_type LIKE 'file%' THEN 'file'
        WHEN al.action_type LIKE 'project%' THEN 'project'
        WHEN al.action_type LIKE 'backup%' THEN 'backup'
        WHEN al.action_type LIKE 'security%' THEN 'security'
        WHEN al.action_type LIKE 'payment%' THEN 'payment'
        ELSE 'system'
    END,
    CASE 
        WHEN al.action_type LIKE 'login%' THEN 'login'
        WHEN al.action_type LIKE 'logout%' THEN 'logout'
        WHEN al.action_type LIKE 'file%' THEN 'file_upload'
        WHEN al.action_type LIKE 'project%' THEN 'project_create'
        WHEN al.action_type LIKE 'backup%' THEN 'backup_create'
        WHEN al.action_type LIKE 'security%' THEN 'security_alert'
        WHEN al.action_type LIKE 'payment%' THEN 'payment_made'
        ELSE 'api_call'
    END,
    al.action_type,
    al.description, al.ip_address, al.created_at,
    al.target_type, al.target_id
FROM `activity_log` al
LEFT JOIN `users_all` ua ON ua.source_id = al.user_id AND ua.user_source = 'user';

-- 2. من client_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`, `client_id`
)
SELECT 
    UUID(), ua.`id`, 
    'client',
    CASE 
        WHEN cal.activity_type LIKE 'login%' THEN 'login'
        WHEN cal.activity_type LIKE 'logout%' THEN 'logout'
        WHEN cal.activity_type LIKE 'file%' THEN 'file_upload'
        WHEN cal.activity_type LIKE 'project%' THEN 'project_create'
        WHEN cal.activity_type LIKE 'payment%' THEN 'payment_made'
        WHEN cal.activity_type LIKE 'ticket%' THEN 'support_ticket'
        ELSE 'api_call'
    END,
    cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id, cal.client_id
FROM `client_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.client_id 
    AND ua.user_source IN ('client', 'client_account');

-- 3. من cloud_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'cloud',
    CASE 
        WHEN cal.activity_type LIKE 'backup%' THEN 'backup_create'
        WHEN cal.activity_type LIKE 'deploy%' THEN 'api_call'
        WHEN cal.activity_type LIKE 'file%' THEN 'file_upload'
        ELSE 'api_call'
    END,
    cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id
FROM `cloud_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.user_id AND ua.user_source = 'user';

-- 4. من documentation_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'documentation',
    CASE 
        WHEN dal.activity_type LIKE 'create%' THEN 'project_create'
        WHEN dal.activity_type LIKE 'update%' THEN 'project_update'
        WHEN dal.activity_type LIKE 'delete%' THEN 'project_delete'
        WHEN dal.activity_type LIKE 'review%' THEN 'api_call'
        ELSE 'api_call'
    END,
    dal.activity_type,
    dal.description, dal.ip_address, dal.user_agent, dal.created_at,
    dal.target_type, dal.target_id
FROM `documentation_activity_log` dal
LEFT JOIN `users_all` ua ON ua.source_id = dal.user_id AND ua.user_source = 'user';

-- 5. من pentest_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`
)
SELECT 
    UUID(), ua.`id`, 
    'pentest',
    CASE 
        WHEN pal.activity_type LIKE 'scan%' THEN 'security_alert'
        WHEN pal.activity_type LIKE 'vuln%' THEN 'threat_detected'
        ELSE 'api_call'
    END,
    pal.action,
    pal.description, pal.created_at
FROM `pentest_activity_log` pal
LEFT JOIN `users_all` ua ON ua.source_id = pal.user_id AND ua.user_source = 'user';

-- 6. من logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN l.log_type = 'security' THEN 'security'
        WHEN l.log_type = 'error' THEN 'system'
        ELSE 'system'
    END,
    CASE 
        WHEN l.log_type = 'security' THEN 'security_alert'
        WHEN l.log_type = 'error' THEN 'api_call'
        ELSE 'api_call'
    END,
    l.event_type,
    l.description, l.ip_address, l.created_at,
    CASE 
        WHEN l.level = 'error' THEN 'error'
        WHEN l.level = 'warning' THEN 'warning'
        ELSE 'info'
    END,
    l.source, l.server_id
FROM `logs` l
LEFT JOIN `users_all` ua ON ua.source_id = l.user_id AND ua.user_source = 'user';

-- 7. من hosting_access_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `ip_address`, `user_agent`, `created_at`, `resource_id`,
    `request_method`, `request_url`, `response_code`
)
SELECT 
    UUID(), 
    'hosting',
    'api_call',
    'زيارة موقع',
    hal.ip_address, hal.user_agent, hal.accessed_at, hal.site_id,
    hal.request_method, hal.request_uri, hal.response_code
FROM `hosting_access_logs` hal;

-- 8. من hosting_security_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `resource_id`
)
SELECT 
    UUID(), 
    'security',
    CASE 
        WHEN hsl.event_type LIKE 'attack%' THEN 'threat_detected'
        WHEN hsl.event_type LIKE 'malware%' THEN 'malware_found'
        ELSE 'security_alert'
    END,
    hsl.event_type,
    hsl.description, hsl.ip_address, hsl.created_at,
    hsl.severity, hsl.site_id
FROM `hosting_security_logs` hsl;

-- 9. من network_events
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `ip_address`, `created_at`, `resource_id`
)
SELECT 
    UUID(), 
    'network',
    'security_alert',
    ne.event_type,
    ne.source_ip, ne.created_at, ne.server_id
FROM `network_events` ne;

-- 10. من security_alerts
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 
    'security',
    'security_alert',
    sa.title,
    sa.description, sa.created_at, sa.project_id,
    sa.status
FROM `security_alerts` sa
LEFT JOIN `users_all` ua ON ua.source_id = sa.resolved_by AND ua.user_source = 'user';

-- 11. من threats
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `resource_id`
)
SELECT 
    UUID(), 
    'threat',
    'threat_detected',
    t.name,
    t.description, t.source_ip, t.first_seen, t.target_server_id
FROM `threats` t;

-- 12. من incidents
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'incident',
    'security_alert',
    i.name,
    i.description, i.detected_at, i.id
FROM `incidents` i
LEFT JOIN `users_all` ua ON ua.source_id = i.assigned_to AND ua.user_source = 'user';

-- 13. من client_support_tickets
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `client_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 
    'support',
    'support_ticket',
    cst.subject,
    cst.message, cst.created_at, cst.id, cst.client_id,
    cst.status
FROM `client_support_tickets` cst
LEFT JOIN `users_all` ua ON ua.source_id = cst.assigned_to AND ua.user_source = 'user';

-- 14. من client_ticket_replies
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'support',
    'support_response',
    'رد على تذكرة',
    ctr.message, ctr.created_at, ctr.ticket_id
FROM `client_ticket_replies` ctr
LEFT JOIN `users_all` ua ON ua.source_id = ctr.user_id AND ua.user_source = 'user';

-- 15. من client_payments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `client_id`, `resource_id`, 
    `status`
)
SELECT 
    UUID(), ua.`id`, 
    'payment',
    'payment_made',
    'دفعة جديدة',
    cp.created_at, cp.client_id, cp.invoice_id,
    cp.status
FROM `client_payments` cp
LEFT JOIN `users_all` ua ON ua.source_id = cp.created_by AND ua.user_source = 'user';

-- 16. من client_invoices
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `resource_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 
    'invoice',
    'invoice_created',
    'فاتورة جديدة',
    ci.title, ci.created_at, ci.client_id, ci.id,
    ci.status
FROM `client_invoices` ci
LEFT JOIN `users_all` ua ON ua.source_id = ci.created_by AND ua.user_source = 'user';

-- 17. من documents
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'document',
    'project_create',
    'إنشاء مستند',
    d.title, d.created_at, d.id
FROM `documents` d
LEFT JOIN `users_all` ua ON ua.source_id = d.created_by AND ua.user_source = 'user';

-- 18. من document_versions
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'document',
    'project_update',
    'إصدار جديد',
    dv.created_at, dv.document_id
FROM `document_versions` dv
LEFT JOIN `users_all` ua ON ua.source_id = dv.created_by AND ua.user_source = 'user';

-- 19. من document_comments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'document',
    'api_call',
    'تعليق جديد',
    dc.comment, dc.created_at, dc.document_id
FROM `document_comments` dc
LEFT JOIN `users_all` ua ON ua.source_id = dc.user_id AND ua.user_source = 'user';

-- 20. من document_reviews
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 
    'document',
    'api_call',
    'مراجعة مستند',
    dr.created_at, dr.document_id, dr.status
FROM `document_reviews` dr
LEFT JOIN `users_all` ua ON ua.source_id = dr.reviewer_id AND ua.user_source = 'user';

-- 21. من reports
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'report',
    'export_data',
    'تقرير جديد',
    r.report_title, r.created_at, r.id
FROM `reports` r
LEFT JOIN `users_all` ua ON ua.source_id = r.created_by AND ua.user_source = 'user';

-- 22. من cloud_backups
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 
    'backup',
    'backup_create',
    'نسخ احتياطي',
    cb.created_at, cb.id, cb.status
FROM `cloud_backups` cb
LEFT JOIN `users_all` ua ON ua.source_id = cb.created_by AND ua.user_source = 'user';

-- 23. من cloud_deployments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 
    'cloud',
    'api_call',
    'نشر تطبيق',
    cd.created_at, cd.id, cd.status
FROM `cloud_deployments` cd
LEFT JOIN `users_all` ua ON ua.source_id = cd.deployed_by AND ua.user_source = 'user';

-- 24. من security_scans
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 
    'security',
    'security_alert',
    ss.scan_name,
    ss.started_at, ss.id, ss.status
FROM `security_scans` ss
LEFT JOIN `users_all` ua ON ua.source_id = ss.performed_by AND ua.user_source = 'user';

-- 25. من vulnerabilities
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'vulnerability',
    'threat_detected',
    v.name,
    v.description, v.created_at, v.id
FROM `vulnerabilities` v
LEFT JOIN `users_all` ua ON ua.source_id = v.discovered_by AND ua.user_source = 'user';

-- 26. من security_recommendations
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 
    'security',
    'security_alert',
    sr.title,
    sr.description, sr.created_at, sr.id, sr.status
FROM `security_recommendations` sr
LEFT JOIN `users_all` ua ON ua.source_id = sr.assigned_to AND ua.user_source = 'user';

-- 27. من service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), 
    'service',
    'api_call',
    'طلب خدمة جديد',
    CONCAT('طلب خدمة من ', full_name), created_at, id, status
FROM `service_requests`;

-- 28. من cloud_server_stats
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `created_at`, `resource_id`
)
SELECT 
    UUID(), 
    'monitoring',
    'api_call',
    'إحصائيات خادم',
    css.recorded_at, css.server_id
FROM `cloud_server_stats` css;

-- 29. من cloud_storage_alerts
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    'storage',
    'security_alert',
    csa.title,
    csa.message, csa.created_at, csa.server_id
FROM `cloud_storage_alerts` csa
LEFT JOIN `users_all` ua ON ua.source_id = csa.resolved_by AND ua.user_source = 'user';

-- 30. من client_service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `resource_id`, `status`
)
SELECT 
    UUID(), 
    'service',
    'api_call',
    'طلب خدمة',
    csr.description, csr.created_at, csr.client_id, csr.id, csr.status
FROM `client_service_requests` csr;

-- 31. من hosting_sites
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `created_at`, `client_id`, `resource_id`, `status`
)
SELECT 
    UUID(), 
    'hosting',
    'project_create',
    'موقع جديد',
    hs.created_at, hs.client_id, hs.id, hs.status
FROM `hosting_sites` hs;

-- =====================================================
-- عرض النتائج
-- =====================================================

SELECT '✅ تم تعبئة user_events بنجاح مع event_category' AS 'رسالة';
SELECT COUNT(*) AS 'عدد الأحداث' FROM `user_events`;
SELECT `event_category`, COUNT(*) AS 'العدد' FROM `user_events` GROUP BY `event_category`;
/*

-- =====================================================
-- ثانياً: تعبئة user_events من جميع الجداول
-- =====================================================

-- 10. من activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`,
    `target_type`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN al.action_type LIKE 'login%' THEN 'auth'
        WHEN al.action_type LIKE 'file%' THEN 'file'
        WHEN al.action_type LIKE 'project%' THEN 'project'
        ELSE 'user'
    END,
    al.action_type, al.action_type,
    al.description, al.ip_address, al.created_at,
    al.target_type, al.target_id
FROM `activity_log` al
LEFT JOIN `users_all` ua ON ua.source_id = al.user_id AND ua.user_source = 'user';

-- 11. من client_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `target_type`, `target_id`, `client_id`
)
SELECT 
    UUID(), ua.`id`, 'client', cal.activity_type, cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id, cal.client_id
FROM `client_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.client_id 
    AND ua.user_source IN ('client', 'client_account');

-- 12. من cloud_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `target_type`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'cloud', cal.activity_type, cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id
FROM `cloud_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.user_id AND ua.user_source = 'user';

-- 13. من documentation_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `target_type`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'documentation', dal.activity_type, dal.activity_type,
    dal.description, dal.ip_address, dal.user_agent, dal.created_at,
    dal.target_type, dal.target_id
FROM `documentation_activity_log` dal
LEFT JOIN `users_all` ua ON ua.source_id = dal.user_id AND ua.user_source = 'user';

-- 14. من pentest_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`
)
SELECT 
    UUID(), ua.`id`, 'pentest', pal.activity_type, pal.action,
    pal.description, pal.created_at
FROM `pentest_activity_log` pal
LEFT JOIN `users_all` ua ON ua.source_id = pal.user_id AND ua.user_source = 'user';

-- 15. من logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `target_type`, `target_id`
)
SELECT 
    UUID(), ua.`id`, l.log_type, l.event_type, l.event_type,
    l.description, l.ip_address, l.created_at,
    CASE 
        WHEN l.level = 'error' THEN 'error'
        WHEN l.level = 'warning' THEN 'warning'
        ELSE 'info'
    END,
    l.source, l.server_id
FROM `logs` l
LEFT JOIN `users_all` ua ON ua.source_id = l.user_id AND ua.user_source = 'user';

-- 16. من hosting_access_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `ip_address`, `user_agent`, `created_at`, `target_id`,
    `request_method`, `request_url`, `response_code`
)
SELECT 
    UUID(), 'hosting', 'access', 'زيارة موقع',
    hal.ip_address, hal.user_agent, hal.accessed_at, hal.site_id,
    hal.request_method, hal.request_uri, hal.response_code
FROM `hosting_access_logs` hal;

-- 17. من hosting_security_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `target_id`
)
SELECT 
    UUID(), 'security', hsl.event_type, hsl.event_type,
    hsl.description, hsl.ip_address, hsl.created_at,
    hsl.severity, hsl.site_id
FROM `hosting_security_logs` hsl;

-- 18. من network_events
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `ip_address`, `created_at`, `target_id`
)
SELECT 
    UUID(), 'network', ne.event_type, ne.event_type,
    ne.source_ip, ne.created_at, ne.server_id
FROM `network_events` ne;

-- 19. من security_alerts
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `severity`, `target_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'security', 'alert', sa.title,
    sa.description, sa.created_at, sa.severity, sa.project_id,
    sa.status
FROM `security_alerts` sa
LEFT JOIN `users_all` ua ON ua.source_id = sa.resolved_by AND ua.user_source = 'user';

-- 20. من threats
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `target_id`
)
SELECT 
    UUID(), 'threat', t.type, t.name,
    t.description, t.source_ip, t.first_seen, t.severity,
    t.target_server_id
FROM `threats` t;

-- 21. من incidents
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `severity`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'incident', i.type, i.name,
    i.description, i.detected_at, i.severity, i.id
FROM `incidents` i
LEFT JOIN `users_all` ua ON ua.source_id = i.assigned_to AND ua.user_source = 'user';

-- 22. من client_support_tickets
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`, `client_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'support', 'ticket', cst.subject,
    cst.message, cst.created_at, cst.id, cst.client_id,
    cst.status
FROM `client_support_tickets` cst
LEFT JOIN `users_all` ua ON ua.source_id = cst.assigned_to AND ua.user_source = 'user';

-- 23. من client_ticket_replies
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'support', 'ticket_reply', 'رد على تذكرة',
    ctr.message, ctr.created_at, ctr.ticket_id
FROM `client_ticket_replies` ctr
LEFT JOIN `users_all` ua ON ua.source_id = ctr.user_id AND ua.user_source = 'user';

-- 24. من client_payments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `client_id`, `target_id`, `amount`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'payment', 'payment', 'دفعة جديدة',
    cp.created_at, cp.client_id, cp.invoice_id, cp.amount,
    cp.status
FROM `client_payments` cp
LEFT JOIN `users_all` ua ON ua.source_id = cp.created_by AND ua.user_source = 'user';

-- 25. من client_invoices
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `target_id`, `amount`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'invoice', 'invoice', 'فاتورة جديدة',
    ci.title, ci.created_at, ci.client_id, ci.id, ci.total_amount,
    ci.status
FROM `client_invoices` ci
LEFT JOIN `users_all` ua ON ua.source_id = ci.created_by AND ua.user_source = 'user';

-- 26. من documents
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'document', d.document_type, 'إنشاء مستند',
    d.title, d.created_at, d.id
FROM `documents` d
LEFT JOIN `users_all` ua ON ua.source_id = d.created_by AND ua.user_source = 'user';

-- 27. من document_versions
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'document', 'version', 'إصدار جديد',
    dv.created_at, dv.document_id
FROM `document_versions` dv
LEFT JOIN `users_all` ua ON ua.source_id = dv.created_by AND ua.user_source = 'user';

-- 28. من document_comments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'document', 'comment', 'تعليق جديد',
    dc.comment, dc.created_at, dc.document_id
FROM `document_comments` dc
LEFT JOIN `users_all` ua ON ua.source_id = dc.user_id AND ua.user_source = 'user';

-- 29. من document_reviews
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'document', 'review', 'مراجعة مستند',
    dr.created_at, dr.document_id, dr.status
FROM `document_reviews` dr
LEFT JOIN `users_all` ua ON ua.source_id = dr.reviewer_id AND ua.user_source = 'user';

-- 30. من reports
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`
)
SELECT 
    UUID(), ua.`id`, 'report', r.report_type, 'تقرير جديد',
    r.report_title, r.created_at, r.id
FROM `reports` r
LEFT JOIN `users_all` ua ON ua.source_id = r.created_by AND ua.user_source = 'user';

-- 31. من cloud_backups
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'backup', cb.backup_type, 'نسخ احتياطي',
    cb.created_at, cb.id, cb.status
FROM `cloud_backups` cb
LEFT JOIN `users_all` ua ON ua.source_id = cb.created_by AND ua.user_source = 'user';

-- 32. من cloud_deployments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'cloud', 'deployment', 'نشر تطبيق',
    cd.created_at, cd.id, cd.status
FROM `cloud_deployments` cd
LEFT JOIN `users_all` ua ON ua.source_id = cd.deployed_by AND ua.user_source = 'user';

-- 33. من security_scans
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'security', 'scan', ss.scan_name,
    ss.started_at, ss.id, ss.status
FROM `security_scans` ss
LEFT JOIN `users_all` ua ON ua.source_id = ss.performed_by AND ua.user_source = 'user';

-- 34. من vulnerabilities
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`, `severity`
)
SELECT 
    UUID(), ua.`id`, 'vulnerability', v.type, v.name,
    v.description, v.created_at, v.id, v.severity
FROM `vulnerabilities` v
LEFT JOIN `users_all` ua ON ua.source_id = v.discovered_by AND ua.user_source = 'user';

-- 35. من security_recommendations
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'security', 'recommendation', sr.title,
    sr.description, sr.created_at, sr.id, sr.status
FROM `security_recommendations` sr
LEFT JOIN `users_all` ua ON ua.source_id = sr.assigned_to AND ua.user_source = 'user';

-- 36. من service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`, `status`,
    `email`
)
SELECT 
    UUID(), 'service', 'request', 'طلب خدمة جديد',
    CONCAT('طلب خدمة من ', full_name), created_at, id, status,
    email
FROM `service_requests`;

-- 37. من cloud_server_stats
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `created_at`, `target_id`
)
SELECT 
    UUID(), 'monitoring', 'server_stats', 'إحصائيات خادم',
    css.recorded_at, css.server_id
FROM `cloud_server_stats` css;

-- 38. من cloud_storage_alerts
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `target_id`, `severity`
)
SELECT 
    UUID(), ua.`id`, 'storage', 'alert', csa.title,
    csa.message, csa.created_at, csa.server_id, csa.severity
FROM `cloud_storage_alerts` csa
LEFT JOIN `users_all` ua ON ua.source_id = csa.resolved_by AND ua.user_source = 'user';

-- 39. من client_service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `target_id`, `status`
)
SELECT 
    UUID(), 'service', 'client_request', 'طلب خدمة',
    csr.description, csr.created_at, csr.client_id, csr.id, csr.status
FROM `client_service_requests` csr;

-- 40. من hosting_sites
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_category`, `event_type`, `action`,
    `created_at`, `client_id`, `target_id`, `status`
)
SELECT 
    UUID(), 'hosting', 'site', 'موقع جديد',
    hs.created_at, hs.client_id, hs.id, hs.status
FROM `hosting_sites` hs;

-- =====================================================
-- تعبئة user_events من جميع الجداول
-- (نسخة معدلة بالكامل - إزالة الأعمدة غير الموجودة)
-- =====================================================

-- 1. من activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN al.action_type LIKE 'login%' THEN 'login'
        WHEN al.action_type LIKE 'logout%' THEN 'logout'
        WHEN al.action_type LIKE 'file%' THEN 'file_upload'
        WHEN al.action_type LIKE 'project%' THEN 'project_create'
        WHEN al.action_type LIKE 'backup%' THEN 'backup_create'
        WHEN al.action_type LIKE 'security%' THEN 'security_alert'
        WHEN al.action_type LIKE 'payment%' THEN 'payment_made'
        ELSE 'api_call'
    END,
    al.action_type,
    al.description, al.ip_address, al.created_at,
    al.target_type, al.target_id
FROM `activity_log` al
LEFT JOIN `users_all` ua ON ua.source_id = al.user_id AND ua.user_source = 'user';

-- 2. من client_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`, `client_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN cal.activity_type LIKE 'login%' THEN 'login'
        WHEN cal.activity_type LIKE 'logout%' THEN 'logout'
        WHEN cal.activity_type LIKE 'file%' THEN 'file_upload'
        WHEN cal.activity_type LIKE 'project%' THEN 'project_create'
        WHEN cal.activity_type LIKE 'payment%' THEN 'payment_made'
        WHEN cal.activity_type LIKE 'ticket%' THEN 'support_ticket'
        ELSE 'api_call'
    END,
    cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id, cal.client_id
FROM `client_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.client_id 
    AND ua.user_source IN ('client', 'client_account');

-- 3. من cloud_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN cal.activity_type LIKE 'backup%' THEN 'backup_create'
        WHEN cal.activity_type LIKE 'deploy%' THEN 'api_call'
        WHEN cal.activity_type LIKE 'file%' THEN 'file_upload'
        ELSE 'api_call'
    END,
    cal.activity_type,
    cal.description, cal.ip_address, cal.user_agent, cal.created_at,
    cal.target_type, cal.target_id
FROM `cloud_activity_log` cal
LEFT JOIN `users_all` ua ON ua.source_id = cal.user_id AND ua.user_source = 'user';

-- 4. من documentation_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `user_agent`, `created_at`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN dal.activity_type LIKE 'create%' THEN 'project_create'
        WHEN dal.activity_type LIKE 'update%' THEN 'project_update'
        WHEN dal.activity_type LIKE 'delete%' THEN 'project_delete'
        WHEN dal.activity_type LIKE 'review%' THEN 'api_call'
        ELSE 'api_call'
    END,
    dal.activity_type,
    dal.description, dal.ip_address, dal.user_agent, dal.created_at,
    dal.target_type, dal.target_id
FROM `documentation_activity_log` dal
LEFT JOIN `users_all` ua ON ua.source_id = dal.user_id AND ua.user_source = 'user';

-- 5. من pentest_activity_log
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN pal.activity_type LIKE 'scan%' THEN 'security_alert'
        WHEN pal.activity_type LIKE 'vuln%' THEN 'threat_detected'
        ELSE 'api_call'
    END,
    pal.action,
    pal.description, pal.created_at
FROM `pentest_activity_log` pal
LEFT JOIN `users_all` ua ON ua.source_id = pal.user_id AND ua.user_source = 'user';

-- 6. من logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `resource_type`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 
    CASE 
        WHEN l.log_type = 'security' THEN 'security_alert'
        WHEN l.log_type = 'error' THEN 'api_call'
        ELSE 'api_call'
    END,
    l.event_type,
    l.description, l.ip_address, l.created_at,
    CASE 
        WHEN l.level = 'error' THEN 'error'
        WHEN l.level = 'warning' THEN 'warning'
        ELSE 'info'
    END,
    l.source, l.server_id
FROM `logs` l
LEFT JOIN `users_all` ua ON ua.source_id = l.user_id AND ua.user_source = 'user';

-- 7. من hosting_access_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `ip_address`, `user_agent`, `created_at`, `resource_id`,
    `request_method`, `request_url`, `response_code`
)
SELECT 
    UUID(), 'api_call', 'زيارة موقع',
    hal.ip_address, hal.user_agent, hal.accessed_at, hal.site_id,
    hal.request_method, hal.request_uri, hal.response_code
FROM `hosting_access_logs` hal;

-- 8. من hosting_security_logs
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `severity`,
    `resource_id`
)
SELECT 
    UUID(), 
    CASE 
        WHEN hsl.event_type LIKE 'attack%' THEN 'threat_detected'
        WHEN hsl.event_type LIKE 'malware%' THEN 'malware_found'
        ELSE 'security_alert'
    END,
    hsl.event_type,
    hsl.description, hsl.ip_address, hsl.created_at,
    hsl.severity, hsl.site_id
FROM `hosting_security_logs` hsl;

-- 9. من network_events
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `ip_address`, `created_at`, `resource_id`
)
SELECT 
    UUID(), 'security_alert', ne.event_type,
    ne.source_ip, ne.created_at, ne.server_id
FROM `network_events` ne;

-- 10. من security_alerts - (تم إزالة sa.severity)
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'security_alert', sa.title,
    sa.description, sa.created_at, sa.project_id,
    sa.status
FROM `security_alerts` sa
LEFT JOIN `users_all` ua ON ua.source_id = sa.resolved_by AND ua.user_source = 'user';

-- 11. من threats - (تم إزالة t.severity)
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `description`, `ip_address`, `created_at`, `resource_id`
)
SELECT 
    UUID(), 'threat_detected', t.name,
    t.description, t.source_ip, t.first_seen, t.target_server_id
FROM `threats` t;

-- 12. من incidents - (تم إزالة i.severity)
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'security_alert', i.name,
    i.description, i.detected_at, i.id
FROM `incidents` i
LEFT JOIN `users_all` ua ON ua.source_id = i.assigned_to AND ua.user_source = 'user';

-- 13. من client_support_tickets
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `client_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'support_ticket', cst.subject,
    cst.message, cst.created_at, cst.id, cst.client_id,
    cst.status
FROM `client_support_tickets` cst
LEFT JOIN `users_all` ua ON ua.source_id = cst.assigned_to AND ua.user_source = 'user';

-- 14. من client_ticket_replies
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'support_response', 'رد على تذكرة',
    ctr.message, ctr.created_at, ctr.ticket_id
FROM `client_ticket_replies` ctr
LEFT JOIN `users_all` ua ON ua.source_id = ctr.user_id AND ua.user_source = 'user';

-- 15. من client_payments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `client_id`, `resource_id`, 
    `status`
)
SELECT 
    UUID(), ua.`id`, 'payment_made', 'دفعة جديدة',
    cp.created_at, cp.client_id, cp.invoice_id,
    cp.status
FROM `client_payments` cp
LEFT JOIN `users_all` ua ON ua.source_id = cp.created_by AND ua.user_source = 'user';

-- 16. من client_invoices
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `resource_id`,
    `status`
)
SELECT 
    UUID(), ua.`id`, 'invoice_created', 'فاتورة جديدة',
    ci.title, ci.created_at, ci.client_id, ci.id,
    ci.status
FROM `client_invoices` ci
LEFT JOIN `users_all` ua ON ua.source_id = ci.created_by AND ua.user_source = 'user';

-- 17. من documents
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'project_create', 'إنشاء مستند',
    d.title, d.created_at, d.id
FROM `documents` d
LEFT JOIN `users_all` ua ON ua.source_id = d.created_by AND ua.user_source = 'user';

-- 18. من document_versions
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'project_update', 'إصدار جديد',
    dv.created_at, dv.document_id
FROM `document_versions` dv
LEFT JOIN `users_all` ua ON ua.source_id = dv.created_by AND ua.user_source = 'user';

-- 19. من document_comments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'api_call', 'تعليق جديد',
    dc.comment, dc.created_at, dc.document_id
FROM `document_comments` dc
LEFT JOIN `users_all` ua ON ua.source_id = dc.user_id AND ua.user_source = 'user';

-- 20. من document_reviews
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'api_call', 'مراجعة مستند',
    dr.created_at, dr.document_id, dr.status
FROM `document_reviews` dr
LEFT JOIN `users_all` ua ON ua.source_id = dr.reviewer_id AND ua.user_source = 'user';

-- 21. من reports
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'export_data', 'تقرير جديد',
    r.report_title, r.created_at, r.id
FROM `reports` r
LEFT JOIN `users_all` ua ON ua.source_id = r.created_by AND ua.user_source = 'user';

-- 22. من cloud_backups
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'backup_create', 'نسخ احتياطي',
    cb.created_at, cb.id, cb.status
FROM `cloud_backups` cb
LEFT JOIN `users_all` ua ON ua.source_id = cb.created_by AND ua.user_source = 'user';

-- 23. من cloud_deployments
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'api_call', 'نشر تطبيق',
    cd.created_at, cd.id, cd.status
FROM `cloud_deployments` cd
LEFT JOIN `users_all` ua ON ua.source_id = cd.deployed_by AND ua.user_source = 'user';

-- 24. من security_scans
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'security_alert', ss.scan_name,
    ss.started_at, ss.id, ss.status
FROM `security_scans` ss
LEFT JOIN `users_all` ua ON ua.source_id = ss.performed_by AND ua.user_source = 'user';

-- 25. من vulnerabilities - (تم إزالة v.severity)
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'threat_detected', v.name,
    v.description, v.created_at, v.id
FROM `vulnerabilities` v
LEFT JOIN `users_all` ua ON ua.source_id = v.discovered_by AND ua.user_source = 'user';

-- 26. من security_recommendations
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), ua.`id`, 'security_alert', sr.title,
    sr.description, sr.created_at, sr.id, sr.status
FROM `security_recommendations` sr
LEFT JOIN `users_all` ua ON ua.source_id = sr.assigned_to AND ua.user_source = 'user';

-- 27. من service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`, `status`
)
SELECT 
    UUID(), 'api_call', 'طلب خدمة جديد',
    CONCAT('طلب خدمة من ', full_name), created_at, id, status
FROM `service_requests`;

-- 28. من cloud_server_stats
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `created_at`, `resource_id`
)
SELECT 
    UUID(), 'api_call', 'إحصائيات خادم',
    css.recorded_at, css.server_id
FROM `cloud_server_stats` css;

-- 29. من cloud_storage_alerts - (تم إزالة csa.severity)
INSERT IGNORE INTO `user_events` (
    `event_id`, `user_id`, `event_type`, `action`,
    `description`, `created_at`, `resource_id`
)
SELECT 
    UUID(), ua.`id`, 'security_alert', csa.title,
    csa.message, csa.created_at, csa.server_id
FROM `cloud_storage_alerts` csa
LEFT JOIN `users_all` ua ON ua.source_id = csa.resolved_by AND ua.user_source = 'user';

-- 30. من client_service_requests
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `description`, `created_at`, `client_id`, `resource_id`, `status`
)
SELECT 
    UUID(), 'api_call', 'طلب خدمة',
    csr.description, csr.created_at, csr.client_id, csr.id, csr.status
FROM `client_service_requests` csr;

-- 31. من hosting_sites
INSERT IGNORE INTO `user_events` (
    `event_id`, `event_type`, `action`,
    `created_at`, `client_id`, `resource_id`, `status`
)
SELECT 
    UUID(), 'project_create', 'موقع جديد',
    hs.created_at, hs.client_id, hs.id, hs.status
FROM `hosting_sites` hs;

-- =====================================================
-- عرض النتائج
-- =====================================================

SELECT '✅ تم تعبئة user_events بنجاح' AS 'رسالة';
SELECT COUNT(*) AS 'عدد الأحداث' FROM `user_events`;
SELECT `event_type`, COUNT(*) AS 'العدد' FROM `user_events` GROUP BY `event_type`;

/*
-- =====================================================
-- ربط جميع الجداول مع users_all و user_events
-- (نسخة نهائية ومضمونة 100%)
-- =====================================================

-- =====================================================
-- أولاً: تعبئة users_all من جميع الجداول
-- =====================================================

-- =====================================================
-- أخيراً: عرض النتائج
-- =====================================================

SELECT '✅ تم ربط جميع الجداول بنجاح' AS 'رسالة';

SELECT 
    'users_all' AS 'الجدول',
    COUNT(*) AS 'عدد السجلات'
FROM `users_all`
UNION ALL
SELECT 
    'user_events',
    COUNT(*)
FROM `user_events`;

SELECT 
    `user_source` AS 'مصدر المستخدمين',
    COUNT(*) AS 'العدد'
FROM `users_all` 
GROUP BY `user_source`;

SELECT 
    `event_category` AS 'فئة الأحداث',
    COUNT(*) AS 'العدد'
FROM `user_events` 
GROUP BY `event_category`
ORDER BY COUNT(*) DESC;

/*
INSERT INTO `users_all` (
    `uuid`, `username`, `email`, `password`, `full_name`, 
    `user_type`, `role_id`, `unit`, `job_title`, `status`,
    `email_verified_at`, `phone_verified`, `phone`, `language`
) VALUES
-- 1. المدير العام
(UUID(), 'admin', 'admin@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'مدير النظام العام', 'admin', 'admin', 'الإدارة العليا', 'مدير عام', 'active', NOW(), TRUE, '0500000001', 'ar'),

-- 2. مدير الاستضافة والحماية
(UUID(), 'manager1', 'manager@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'أحمد محمد', 'manager', 'manager', 'إدارة الاستضافة', 'مدير نظام الاستضافة والحماية', 'active', NOW(), TRUE, '0500000002', 'ar'),

-- 3. مساعد مدير - العمليات
(UUID(), 'assistant_ops', 'assistant.ops@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'سارة عبدالله', 'assistant_manager', 'assistant_manager', 'العمليات', 'مساعد مدير العمليات', 'active', NOW(), TRUE, '0500000003', 'ar'),

-- 4. وحدة التوثيق - رئيس
(UUID(), 'doc_head', 'doc.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'محمد علي', 'documentation_staff', 'documentation_head', 'التوثيق', 'رئيس وحدة التوثيق', 'active', NOW(), TRUE, '0500000004', 'ar'),

-- 5. وحدة التوثيق - محلل
(UUID(), 'doc_analyst1', 'doc.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'نورة أحمد', 'documentation_staff', 'documentation_staff', 'التوثيق', 'محلل توثيق', 'active', NOW(), TRUE, '0500000005', 'ar'),

-- 6. وحدة التوثيق - مختص عقود
(UUID(), 'contract_spec', 'contracts@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عمر حسن', 'documentation_staff', 'documentation_staff', 'التوثيق', 'مختص عقود وأرشفة', 'active', NOW(), TRUE, '0500000006', 'ar'),

-- 7. وحدة التخزين السحابي - رئيس
(UUID(), 'cloud_head', 'cloud.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'خالد إبراهيم', 'cloud_storage_staff', 'cloud_storage_head', 'التخزين السحابي', 'رئيس وحدة التخزين', 'active', NOW(), TRUE, '0500000007', 'ar'),

-- 8. وحدة التخزين السحابي - مهندس
(UUID(), 'cloud_eng1', 'cloud.eng1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'فيصل المالكي', 'cloud_storage_staff', 'cloud_storage_staff', 'التخزين السحابي', 'مهندس بنية تحتية', 'active', NOW(), TRUE, '0500000008', 'ar'),

-- 9. وحدة التخزين السحابي - مسؤول
(UUID(), 'cloud_admin1', 'cloud.admin1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالله القحطاني', 'cloud_storage_staff', 'cloud_storage_staff', 'التخزين السحابي', 'مسؤول تخزين واستضافة', 'active', NOW(), TRUE, '0500000009', 'ar'),

-- 10. وحدة اختبار الاختراق - رئيس
(UUID(), 'pentest_head', 'pentest.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'نايف الحربي', 'pentest_staff', 'pentest_head', 'اختبار الاختراق', 'رئيس وحدة اختبار الاختراق', 'active', NOW(), TRUE, '0500000010', 'ar'),

-- 11. وحدة اختبار الاختراق - محلل متقدم
(UUID(), 'pentest_senior1', 'pentest.senior1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالعزيز الشمري', 'pentest_staff', 'pentest_senior', 'اختبار الاختراق', 'محلل أمني متقدم', 'active', NOW(), TRUE, '0500000011', 'ar'),

-- 12. وحدة اختبار الاختراق - محلل
(UUID(), 'pentest_analyst1', 'pentest.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'أحمد الزهراني', 'pentest_staff', 'pentest_staff', 'اختبار الاختراق', 'محلل أمني', 'active', NOW(), TRUE, '0500000012', 'ar'),

-- 13. وحدة الحماية والمراقبة - رئيس
(UUID(), 'monitor_head', 'monitor.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'بدر العتيبي', 'monitoring_staff', 'monitoring_head', 'المراقبة', 'رئيس وحدة الحماية والمراقبة', 'active', NOW(), TRUE, '0500000013', 'ar'),

-- 14. وحدة الحماية والمراقبة - محلل
(UUID(), 'monitor_analyst1', 'monitor.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'سعود الدوسري', 'monitoring_staff', 'monitoring_staff', 'المراقبة', 'محلل أمني ومراقبة', 'active', NOW(), TRUE, '0500000014', 'ar'),

-- 15. وحدة الحماية والمراقبة - مهندس شبكات
(UUID(), 'network_eng1', 'network.eng1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'مشعل العنزي', 'monitoring_staff', 'monitoring_staff', 'المراقبة', 'مهندس أمن الشبكات', 'active', NOW(), TRUE, '0500000015', 'ar'),

-- 16. إدارة المشاريع - رئيس
(UUID(), 'pms_head', 'pms.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'فهد الغامدي', 'pms_staff', 'pms_head', 'إدارة المشاريع', 'رئيس إدارة المشاريع', 'active', NOW(), TRUE, '0500000016', 'ar'),

-- 17. إدارة المشاريع - منسق
(UUID(), 'pms_coord1', 'pms.coord1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'ريم الشهراني', 'pms_staff', 'pms_staff', 'إدارة المشاريع', 'منسق مشاريع', 'active', NOW(), TRUE, '0500000017', 'ar'),

-- 18. النظام المالي - رئيس
(UUID(), 'finance_head', 'finance.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالرحمن السبيعي', 'finance_staff', 'finance_head', 'المالية', 'رئيس النظام المالي', 'active', NOW(), TRUE, '0500000018', 'ar'),

-- 19. النظام المالي - محاسب
(UUID(), 'accountant1', 'accountant1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'منى العبدالله', 'finance_staff', 'finance_staff', 'المالية', 'محاسب', 'active', NOW(), TRUE, '0500000019', 'ar'),

-- 20. الذكاء الاصطناعي - محلل
(UUID(), 'ai_analyst1', 'ai.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'لمى القاسم', 'ai_staff', 'ai_staff', 'الذكاء الاصطناعي', 'محلل ذكاء اصطناعي', 'active', NOW(), TRUE, '0500000020', 'ar'),

-- 21. عميل 1 - شركة
(UUID(), 'client1', 'client1@example.com', '$2y$10$YourHashedPasswordHere', 'شركة الأمان للتقنية', 'client', 'client', NULL, NULL, 'active', NOW(), TRUE, '0500000021', 'ar'),

-- 22. عميل 2 - مؤسسة
(UUID(), 'client2', 'client2@example.com', '$2y$10$YourHashedPasswordHere', 'مؤسسة الحلول الرقمية', 'client', 'client', NULL, NULL, 'active', NOW(), TRUE, '0500000022', 'ar'),

-- 23. عميل 3 - شركة
(UUID(), 'client3', 'client3@example.com', '$2y$10$YourHashedPasswordHere', 'شركة الابتكارات الحديثة', 'client', 'client', NULL, NULL, 'active', NOW(), TRUE, '0500000023', 'ar'),

-- 24. عميل 4 - مؤسسة
(UUID(), 'client4', 'client4@example.com', '$2y$10$YourHashedPasswordHere', 'مؤسسة البرمجيات المتقدمة', 'client', 'client', NULL, NULL, 'active', NOW(), TRUE, '0500000024', 'ar'),

-- 25. عميل 5 - شركة
(UUID(), 'client5', 'client5@example.com', '$2y$10$YourHashedPasswordHere', 'شركة البيانات الآمنة', 'client', 'client', NULL, NULL, 'active', NOW(), TRUE, '0500000025', 'ar');

-- =====================================================
-- إضافة بعض الأحداث الافتراضية
-- =====================================================

INSERT INTO `user_events` (`event_id`, `user_id`, `event_type`, `action`, `description`, `ip_address`, `severity`) VALUES
(UUID(), 1, 'login', 'تسجيل دخول', 'تسجيل دخول المدير العام', '127.0.0.1', 'info'),
(UUID(), 2, 'login', 'تسجيل دخول', 'تسجيل دخول مدير الاستضافة', '127.0.0.1', 'info'),
(UUID(), 7, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس التخزين', '127.0.0.1', 'info'),
(UUID(), 10, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس اختبار الاختراق', '127.0.0.1', 'info'),
(UUID(), 13, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس المراقبة', '127.0.0.1', 'info'),
(UUID(), 21, 'login', 'تسجيل دخول', 'تسجيل دخول العميل 1', '127.0.0.1', 'info');


/*
-- =====================================================
-- 2. جدول سجل الأحداث والأنشطة (user_events)
-- =====================================================

CREATE TABLE `user_events` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `event_id` VARCHAR(36) UNIQUE NOT NULL COMMENT 'معرف الحدث الفريد',
    `user_id` INT NOT NULL COMMENT 'معرف المستخدم',
    `event_type` ENUM(
        'login', 'logout', 'login_failed',
        'password_change', 'password_reset',
        'profile_update', 'settings_change',
        'mfa_enabled', 'mfa_disabled', 'mfa_verified',
        'file_upload', 'file_download', 'file_delete', 'file_scan',
        'project_create', 'project_update', 'project_delete',
        'backup_create', 'backup_restore', 'backup_delete',
        'container_create', 'container_isolate', 'container_release',
        'permission_change', 'role_change',
        'security_alert', 'threat_detected', 'malware_found',
        'api_call', 'export_data', 'import_data',
        'payment_made', 'invoice_created',
        'contract_signed', 'contract_approved',
        'support_ticket', 'support_response'
    ) NOT NULL COMMENT 'نوع الحدث',
    
    `action` VARCHAR(100) NOT NULL COMMENT 'الإجراء',
    `description` TEXT COMMENT 'وصف الحدث',
    `details` JSON COMMENT 'تفاصيل إضافية',
    `ip_address` VARCHAR(45) COMMENT 'عنوان IP',
    `user_agent` TEXT COMMENT 'المتصفح',
    `device` VARCHAR(50) COMMENT 'الجهاز',
    `location` VARCHAR(100) COMMENT 'الموقع',
    `session_id` VARCHAR(255) COMMENT 'معرف الجلسة',
    `request_id` VARCHAR(36) COMMENT 'معرف الطلب',
    `request_method` VARCHAR(10) COMMENT 'طريقة الطلب',
    `request_url` TEXT COMMENT 'رابط الطلب',
    `response_time` INT COMMENT 'وقت الاستجابة (مللي ثانية)',
    `response_code` INT COMMENT 'رمز الاستجابة',
    
    `resource_type` VARCHAR(50) COMMENT 'نوع المورد',
    `resource_id` VARCHAR(100) COMMENT 'معرف المورد',
    `resource_name` VARCHAR(255) COMMENT 'اسم المورد',
    
    `client_id` INT COMMENT 'معرف العميل',
    `project_id` INT COMMENT 'معرف المشروع',
    `container_id` VARCHAR(100) COMMENT 'معرف الحاوية',
    
    `severity` ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    `security_level` INT DEFAULT 1,
    
    `status` VARCHAR(50) DEFAULT 'success',
    `error_message` TEXT,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`event_type`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_resource` (`resource_type`, `resource_id`),
    INDEX `idx_client` (`client_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_session` (`session_id`),
    FULLTEXT INDEX `ft_search` (`description`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل أحداث المستخدمين';

-- =====================================================
-- 3. جدول الصلاحيات المخصصة (user_permissions)
-- =====================================================

CREATE TABLE `user_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `permission_name` VARCHAR(100) NOT NULL,
    `granted` BOOLEAN DEFAULT TRUE COMMENT 'true = منح، false = منع',
    `resource_type` VARCHAR(50) COMMENT 'نوع المورد',
    `resource_id` VARCHAR(100) COMMENT 'معرف المورد',
    `expires_at` TIMESTAMP NULL COMMENT 'تاريخ انتهاء الصلاحية',
    `granted_by` INT COMMENT 'من قام بالمنح',
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reason` TEXT COMMENT 'سبب المنح',
    
    FOREIGN KEY (`user_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`granted_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    UNIQUE KEY `unique_permission` (`user_id`, `permission_name`, `resource_type`, `resource_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_permission` (`permission_name`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='الصلاحيات المخصصة للمستخدمين';

-- =====================================================
-- 4. جدول الجلسات النشطة (user_sessions)
-- =====================================================

CREATE TABLE `user_sessions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(255) NOT NULL,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `device_type` VARCHAR(50) COMMENT 'mobile, tablet, desktop',
    `browser` VARCHAR(50),
    `os` VARCHAR(50),
    `location` VARCHAR(100),
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `remember_token` VARCHAR(255) NULL,
    
    FOREIGN KEY (`user_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    
    UNIQUE KEY `unique_session` (`session_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_last_activity` (`last_activity`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جلسات المستخدمين النشطة';

-- =====================================================
-- 5. جدول المشاريع (projects)
-- =====================================================

CREATE TABLE `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` VARCHAR(36) UNIQUE NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `client_id` INT NOT NULL,
    `manager_id` INT COMMENT 'مدير المشروع',
    `type` ENUM('hosting', 'cloud_storage', 'security', 'development') NOT NULL,
    `status` ENUM('pending', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- التفاصيل الفنية
    `requirements` JSON,
    `technologies` JSON,
    `repository_url` VARCHAR(500),
    `domain` VARCHAR(255),
    
    -- الموارد
    `storage_limit` BIGINT DEFAULT 10737418240 COMMENT '10GB',
    `bandwidth_limit` BIGINT DEFAULT 10737418240,
    `cpu_limit` FLOAT DEFAULT 0.5,
    `memory_limit` VARCHAR(10) DEFAULT '512M',
    
    -- التواريخ
    `start_date` DATE,
    `deadline` DATE,
    `completed_at` TIMESTAMP NULL,
    
    -- المالية
    `budget` DECIMAL(10,2),
    `cost` DECIMAL(10,2),
    `paid` BOOLEAN DEFAULT FALSE,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`manager_id`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_client` (`client_id`),
    INDEX `idx_manager` (`manager_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`type`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول المشاريع';

-- =====================================================
-- 6. جدول الملفات (files)
-- =====================================================

CREATE TABLE `files` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `file_id` VARCHAR(36) UNIQUE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `size` BIGINT NOT NULL,
    `mime_type` VARCHAR(100),
    `extension` VARCHAR(20),
    `hash` VARCHAR(64) COMMENT 'بصمة الملف',
    
    `user_id` INT NOT NULL,
    `project_id` INT,
    `container_id` VARCHAR(100),
    
    `is_public` BOOLEAN DEFAULT FALSE,
    `download_count` INT DEFAULT 0,
    `last_download` TIMESTAMP NULL,
    
    `virus_scan_status` ENUM('pending', 'clean', 'infected', 'error') DEFAULT 'pending',
    `virus_scan_result` JSON,
    `scanned_at` TIMESTAMP NULL,
    
    `status` ENUM('active', 'quarantined', 'deleted') DEFAULT 'active',
    `quarantine_reason` TEXT,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL,
    
    FOREIGN KEY (`user_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user` (`user_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_virus` (`virus_scan_status`),
    INDEX `idx_hash` (`hash`),
    FULLTEXT INDEX `ft_search` (`name`, `original_name`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول الملفات';

-- =====================================================
-- 7. جدول الحاويات (containers)
-- =====================================================

CREATE TABLE `containers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `container_id` VARCHAR(100) UNIQUE NOT NULL,
    `client_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('docker', 'lxc', 'vm') DEFAULT 'docker',
    `image` VARCHAR(200),
    
    -- الموارد
    `memory_limit` VARCHAR(10) DEFAULT '512M',
    `cpu_limit` FLOAT DEFAULT 0.5,
    `storage_limit` VARCHAR(10) DEFAULT '10G',
    `storage_used` BIGINT DEFAULT 0,
    
    -- الشبكة
    `ip_address` VARCHAR(45),
    `network_id` VARCHAR(100),
    `ports` JSON,
    
    -- الأمان
    `is_isolated` BOOLEAN DEFAULT FALSE,
    `isolation_reason` TEXT,
    `isolated_at` TIMESTAMP NULL,
    `isolated_by` INT,
    
    `seccomp_enabled` BOOLEAN DEFAULT TRUE,
    `apparmor_enabled` BOOLEAN DEFAULT TRUE,
    `readonly_rootfs` BOOLEAN DEFAULT TRUE,
    
    -- الحالة
    `status` ENUM('running', 'stopped', 'isolated', 'terminated') DEFAULT 'running',
    `last_activity` TIMESTAMP NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`isolated_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_client` (`client_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_isolated` (`is_isolated`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول الحاويات';

-- =====================================================
-- 8. جدول النسخ الاحتياطي (backups)
-- =====================================================

CREATE TABLE `backups` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `backup_id` VARCHAR(36) UNIQUE NOT NULL,
    `client_id` INT NOT NULL,
    `project_id` INT,
    `container_id` VARCHAR(100),
    `type` ENUM('full', 'incremental', 'differential') DEFAULT 'full',
    
    `name` VARCHAR(200) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `size` BIGINT,
    `hash` VARCHAR(64),
    
    `is_encrypted` BOOLEAN DEFAULT TRUE,
    `is_compressed` BOOLEAN DEFAULT TRUE,
    `encryption_key_id` VARCHAR(100),
    
    `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'restored') DEFAULT 'pending',
    `error_message` TEXT,
    
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `verified_at` TIMESTAMP NULL,
    `verified_by` INT,
    `is_verified` BOOLEAN DEFAULT FALSE,
    
    `retention_days` INT DEFAULT 30,
    `expires_at` TIMESTAMP NULL,
    
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_client` (`client_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expires` (`expires_at`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول النسخ الاحتياطي';

-- =====================================================
-- 9. جدول الفواتير (invoices)
-- =====================================================

CREATE TABLE `invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) UNIQUE NOT NULL,
    `client_id` INT NOT NULL,
    `project_id` INT,
    
    `amount` DECIMAL(10,2) NOT NULL,
    `tax` DECIMAL(10,2) DEFAULT 0,
    `total` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'SAR',
    
    `description` TEXT,
    `items` JSON,
    
    `status` ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    `payment_method` VARCHAR(50),
    `payment_date` TIMESTAMP NULL,
    `transaction_id` VARCHAR(100),
    
    `due_date` DATE NOT NULL,
    `paid_at` TIMESTAMP NULL,
    
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_client` (`client_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_due_date` (`due_date`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول الفواتير';

-- =====================================================
-- 10. جدول العقود (contracts)
-- =====================================================

CREATE TABLE `contracts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contract_number` VARCHAR(50) UNIQUE NOT NULL,
    `client_id` INT NOT NULL,
    `project_id` INT,
    
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT,
    `file_path` VARCHAR(500),
    
    `start_date` DATE NOT NULL,
    `end_date` DATE,
    `value` DECIMAL(10,2),
    
    `signed_by_client` BOOLEAN DEFAULT FALSE,
    `signed_by_client_at` TIMESTAMP NULL,
    `signed_by_admin` BOOLEAN DEFAULT FALSE,
    `signed_by_admin_at` TIMESTAMP NULL,
    `signed_by` INT,
    
    `status` ENUM('draft', 'sent', 'signed', 'expired', 'cancelled') DEFAULT 'draft',
    
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`client_id`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users_all`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`signed_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_client` (`client_id`),
    INDEX `idx_status` (`status`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='جدول العقود';

-- =====================================================
-- البيانات الافتراضية
-- =====================================================

-- كلمة المرور لجميع المستخدمين: Password@123
-- Hash: $2y$10$YourHashedPasswordHere (يجب استبداله بـ password_hash('Password@123', PASSWORD_DEFAULT))
-- =====================================================
-- إضافة مشاريع افتراضية
-- =====================================================

INSERT INTO `projects` (`project_id`, `name`, `description`, `client_id`, `manager_id`, `type`, `status`) VALUES
(UUID(), 'منصة التجارة الإلكترونية', 'تطوير منصة تجارة إلكترونية متكاملة', 21, 16, 'development', 'active'),
(UUID(), 'نظام التخزين السحابي', 'استضافة سحابية للشركة', 22, 7, 'cloud_storage', 'active'),
(UUID(), 'اختبار أمني شامل', 'اختبار اختراق لأنظمة الشركة', 23, 10, 'security', 'pending'),
(UUID(), 'استضافة مواقع', 'استضافة 5 مواقع', 24, 2, 'hosting', 'active'),
(UUID(), 'نظام حماية متقدم', 'تطوير نظام حماية مخصص', 25, 13, 'security', 'active');

-- =====================================================
-- إضافة ملفات افتراضية
-- =====================================================

INSERT INTO `files` (`file_id`, `name`, `original_name`, `path`, `size`, `user_id`, `project_id`, `mime_type`, `extension`) VALUES
(UUID(), 'تقرير أمني.pdf', 'تقرير أمني Q1-2024.pdf', '/uploads/reports/security-q1-2024.pdf', 2048576, 1, 1, 'application/pdf', 'pdf'),
(UUID(), 'عقد استضافة.doc', 'عقد استضافة العميل 2.doc', '/uploads/contracts/client2-contract.doc', 512000, 4, 2, 'application/msword', 'doc'),
(UUID(), 'نتائج الاختبار.json', 'pentest-results-client3.json', '/uploads/pentest/client3-results.json', 102400, 10, 3, 'application/json', 'json');

-- =====================================================
-- إضافة حاويات افتراضية
-- =====================================================

INSERT INTO `containers` (`container_id`, `client_id`, `name`, `type`, `image`, `memory_limit`, `cpu_limit`, `storage_limit`, `ip_address`, `status`) VALUES
('cont_' || UUID(), 21, 'حاوية العميل 1', 'docker', 'ubuntu:22.04', '1G', 1.0, '20G', '10.0.1.21', 'running'),
('cont_' || UUID(), 22, 'حاوية العميل 2', 'docker', 'ubuntu:22.04', '512M', 0.5, '10G', '10.0.1.22', 'running'),
('cont_' || UUID(), 23, 'حاوية العميل 3', 'docker', 'ubuntu:22.04', '512M', 0.5, '10G', '10.0.1.23', 'running'),
('cont_' || UUID(), 24, 'حاوية العميل 4', 'docker', 'ubuntu:22.04', '1G', 1.0, '20G', '10.0.1.24', 'running'),
('cont_' || UUID(), 25, 'حاوية العميل 5', 'docker', 'ubuntu:22.04', '2G', 2.0, '50G', '10.0.1.25', 'running');

-- =====================================================
-- إضافة نسخ احتياطية افتراضية
-- =====================================================

INSERT INTO `backups` (`backup_id`, `client_id`, `project_id`, `container_id`, `type`, `name`, `path`, `size`, `status`, `completed_at`) VALUES
(UUID(), 21, 1, (SELECT container_id FROM containers WHERE client_id = 21 LIMIT 1), 'full', 'نسخة احتياطية كاملة - العميل 1', '/backups/client1/full-20240301.tar.gz', 524288000, 'completed', NOW()),
(UUID(), 22, 2, (SELECT container_id FROM containers WHERE client_id = 22 LIMIT 1), 'full', 'نسخة احتياطية كاملة - العميل 2', '/backups/client2/full-20240301.tar.gz', 104857600, 'completed', NOW());

-- =====================================================
-- تحديث إحصائيات المستخدمين
-- =====================================================

UPDATE users_all SET 
    total_logins = 10,
    last_login = NOW(),
    last_login_ip = '127.0.0.1'
WHERE id <= 25;

-- =====================================================
-- عرض ملخص البيانات المدخلة
-- =====================================================

SELECT 'تم إنشاء قاعدة البيانات بنجاح!' AS 'رسالة';
SELECT CONCAT('عدد المستخدمين: ', COUNT(*)) AS 'إحصائية' FROM users_all;
SELECT CONCAT('عدد المشاريع: ', COUNT(*)) AS 'إحصائية' FROM projects;
SELECT CONCAT('عدد الملفات: ', COUNT(*)) AS 'إحصائية' FROM files;
SELECT CONCAT('عدد الحاويات: ', COUNT(*)) AS 'إحصائية' FROM containers;

/*
-- =====================================================
-- 1. جدول المستخدمين الشامل (users_all)
-- =====================================================

CREATE TABLE `users_all` (
    -- المعرفات الأساسية
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'معرف المستخدم الفريد',
    `uuid` VARCHAR(36) UNIQUE NOT NULL COMMENT 'معرف عالمي فريد',
    `username` VARCHAR(50) UNIQUE NOT NULL COMMENT 'اسم المستخدم',
    `email` VARCHAR(100) UNIQUE NOT NULL COMMENT 'البريد الإلكتروني',
    `password` VARCHAR(255) NOT NULL COMMENT 'كلمة المرور (مشفرة)',
    
    -- المعلومات الشخصية
    `full_name` VARCHAR(100) NOT NULL COMMENT 'الاسم الكامل',
    `phone` VARCHAR(20) COMMENT 'رقم الهاتف',
    `phone_verified` BOOLEAN DEFAULT FALSE COMMENT 'هل تم التحقق من الهاتف',
    `id_number` VARCHAR(20) COMMENT 'رقم الهوية/الإقامة',
    `birth_date` DATE COMMENT 'تاريخ الميلاد',
    `gender` ENUM('male', 'female', 'other') DEFAULT 'other' COMMENT 'الجنس',
    `nationality` VARCHAR(50) DEFAULT 'SA' COMMENT 'الجنسية',
    `address` TEXT COMMENT 'العنوان',
    `city` VARCHAR(50) COMMENT 'المدينة',
    `country` VARCHAR(50) DEFAULT 'السعودية' COMMENT 'الدولة',
    
    -- نوع المستخدم ودوره
    `user_type` ENUM(
        'admin', 'manager', 'assistant_manager',
        'documentation_staff', 'cloud_storage_staff', 'pentest_staff',
        'monitoring_staff', 'pms_staff', 'finance_staff', 'ai_staff',
        'client'
    ) NOT NULL COMMENT 'نوع المستخدم',
    
    `role_id` VARCHAR(50) DEFAULT 'client' COMMENT 'معرف الدور',
    `permissions` JSON COMMENT 'صلاحيات إضافية',
    
    -- معلومات الوحدة (للموظفين)
    `unit` VARCHAR(50) COMMENT 'الوحدة التابع لها',
    `department` VARCHAR(50) COMMENT 'القسم',
    `job_title` VARCHAR(100) COMMENT 'المسمى الوظيفي',
    `manager_id` INT COMMENT 'معرف المدير المباشر',
    `employee_id` VARCHAR(50) UNIQUE COMMENT 'رقم الموظف',
    `hire_date` DATE COMMENT 'تاريخ التعيين',
    `salary` DECIMAL(10,2) COMMENT 'الراتب',
    
    -- معلومات العميل
    `client_type` ENUM('individual', 'company', 'government') DEFAULT 'individual' COMMENT 'نوع العميل',
    `company_name` VARCHAR(100) COMMENT 'اسم الشركة',
    `commercial_registration` VARCHAR(50) COMMENT 'السجل التجاري',
    `vat_number` VARCHAR(50) COMMENT 'الرقم الضريبي',
    `account_manager_id` INT COMMENT 'معرف مدير الحساب',
    `credit_limit` DECIMAL(10,2) DEFAULT 0 COMMENT 'حد الائتمان',
    `payment_method` VARCHAR(50) COMMENT 'طريقة الدفع المفضلة',
    `subscription_plan` VARCHAR(50) COMMENT 'الباقة',
    `subscription_start` DATE COMMENT 'بداية الاشتراك',
    `subscription_end` DATE COMMENT 'نهاية الاشتراك',
    `auto_renew` BOOLEAN DEFAULT TRUE COMMENT 'تجديد تلقائي',
    
    -- إعدادات الأمان
    `mfa_enabled` BOOLEAN DEFAULT FALSE COMMENT 'مفعل MFA',
    `mfa_method` ENUM('none', 'google_authenticator', 'email', 'sms', 'whatsapp') DEFAULT 'none' COMMENT 'طريقة MFA',
    `mfa_secret` VARCHAR(255) COMMENT 'سر MFA',
    `backup_codes` JSON COMMENT 'رموز احتياطية',
    `password_changed_at` TIMESTAMP NULL COMMENT 'آخر تغيير لكلمة المرور',
    `password_reset_token` VARCHAR(255) COMMENT 'رمز إعادة تعيين كلمة المرور',
    `password_reset_expires` TIMESTAMP NULL COMMENT 'انتهاء صلاحية رمز إعادة التعيين',
    `login_attempts` INT DEFAULT 0 COMMENT 'محاولات تسجيل الدخول الفاشلة',
    `locked_until` TIMESTAMP NULL COMMENT 'مقفل حتى',
    `last_login` TIMESTAMP NULL COMMENT 'آخر تسجيل دخول',
    `last_login_ip` VARCHAR(45) COMMENT 'آخر IP',
    `last_login_agent` TEXT COMMENT 'آخر متصفح',
    `session_id` VARCHAR(255) COMMENT 'معرف الجلسة الحالية',
    
    -- إعدادات الإشعارات
    `notifications_email` BOOLEAN DEFAULT TRUE COMMENT 'إشعارات البريد',
    `notifications_sms` BOOLEAN DEFAULT FALSE COMMENT 'إشعارات SMS',
    `notifications_whatsapp` BOOLEAN DEFAULT FALSE COMMENT 'إشعارات واتساب',
    `notifications_browser` BOOLEAN DEFAULT TRUE COMMENT 'إشعارات المتصفح',
    
    -- إعدادات الواجهة
    `language` VARCHAR(10) DEFAULT 'ar' COMMENT 'اللغة',
    `theme` VARCHAR(20) DEFAULT 'light' COMMENT 'السمة',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Riyadh' COMMENT 'المنطقة الزمنية',
    `date_format` VARCHAR(20) DEFAULT 'Y-m-d' COMMENT 'تنسيق التاريخ',
    `items_per_page` INT DEFAULT 25 COMMENT 'عدد العناصر في الصفحة',
    
    -- إحصائيات عامة
    `total_projects` INT DEFAULT 0 COMMENT 'إجمالي المشاريع',
    `total_storage` BIGINT DEFAULT 0 COMMENT 'إجمالي التخزين المستخدم (بايت)',
    `total_files` INT DEFAULT 0 COMMENT 'إجمالي الملفات',
    `total_logins` INT DEFAULT 0 COMMENT 'إجمالي مرات الدخول',
    `total_actions` INT DEFAULT 0 COMMENT 'إجمالي الإجراءات',
    
    -- حالة الحساب
    `status` ENUM(
        'active', 'inactive', 'suspended', 'locked',
        'pending_verification', 'pending_approval', 'deleted'
    ) DEFAULT 'pending_verification' COMMENT 'حالة الحساب',
    
    `status_reason` TEXT COMMENT 'سبب تغيير الحالة',
    `status_changed_by` INT COMMENT 'من قام بتغيير الحالة',
    `status_changed_at` TIMESTAMP NULL COMMENT 'تاريخ تغيير الحالة',
    
    -- التواريخ
    `email_verified_at` TIMESTAMP NULL COMMENT 'تاريخ التحقق من البريد',
    `phone_verified_at` TIMESTAMP NULL COMMENT 'تاريخ التحقق من الهاتف',
    `approved_at` TIMESTAMP NULL COMMENT 'تاريخ الموافقة',
    `approved_by` INT COMMENT 'تمت الموافقة بواسطة',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'تاريخ الإنشاء',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'تاريخ التحديث',
    `deleted_at` TIMESTAMP NULL COMMENT 'تاريخ الحذف (soft delete)',
    
    -- المفاتيح الخارجية
    FOREIGN KEY (`manager_id`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`account_manager_id`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`status_changed_by`) REFERENCES `users_all`(`id`) ON DELETE SET NULL,
    
    -- الفهارس
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_user_type` (`user_type`),
    INDEX `idx_role` (`role_id`),
    INDEX `idx_unit` (`unit`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_last_login` (`last_login`),
    INDEX `idx_manager` (`manager_id`),
    INDEX `idx_account_manager` (`account_manager_id`),
    INDEX `idx_client_type` (`client_type`),
    FULLTEXT INDEX `ft_search` (`full_name`, `email`, `username`, `company_name`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المستخدمين الشامل';
/*
CREATE TABLE IF NOT EXISTS `websites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `type` enum('website','webapp','api','mobile') DEFAULT 'website',
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بيانات تجريبية
INSERT INTO `websites` (`client_id`, `name`, `url`, `type`) VALUES
(1, 'الموقع الرئيسي', 'https://example.com', 'website'),
(1, 'المتجر الإلكتروني', 'https://shop.example.com', 'webapp'),
(2, 'منصة تعليمية', 'https://learn.example.com', 'website');
/*

ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `unit_id` int(11) DEFAULT NULL AFTER `department`,
ADD COLUMN IF NOT EXISTS `job_title` varchar(255) DEFAULT NULL AFTER `full_name`,
ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `hire_date` date DEFAULT NULL AFTER `created_at`,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL AFTER `hire_date`;
-- إضافة وحدات تجريبية
INSERT INTO `units` (`name`, `code`, `head_name`, `employee_count`, `max_employees`, `budget`, `color`, `status`) VALUES
('وحدة السيرفرات', 'U-HOST-01', 'أحمد محمد', 3, 8, 150000.00, 'green', 'active'),
('وحدة النطاقات', 'U-HOST-02', 'سارة عبدالله', 2, 5, 75000.00, 'green', 'active'),
('وحدة التخزين السحابي', 'U-STOR-01', 'فهد خالد', 4, 10, 200000.00, 'blue', 'active'),
('وحدة قواعد البيانات', 'U-STOR-02', 'نورة سعد', 3, 6, 120000.00, 'blue', 'active'),
('وحدة جدران الحماية', 'U-SEC-01', 'عبدالعزيز العتيبي', 5, 8, 250000.00, 'yellow', 'active'),
('وحدة مراقبة التهديدات', 'U-SEC-02', 'فيصل الحربي', 4, 7, 180000.00, 'yellow', 'active'),
('وحدة اختبار الاختراق', 'U-PEN-01', 'تركي القحطاني', 3, 6, 220000.00, 'purple', 'active'),
('وحدة تحليل الثغرات', 'U-PEN-02', 'هيا المطيري', 2, 5, 160000.00, 'purple', 'active'),
('وحدة التوثيق الفني', 'U-DOC-01', 'سارة العنزي', 3, 6, 90000.00, 'pink', 'active'),
('وحدة إدارة المحتوى', 'U-DOC-02', 'نوف السبيعي', 2, 4, 60000.00, 'pink', 'active'),
('وحدة المشاريع', 'U-MAN-01', 'خالد الحارثي', 4, 8, 300000.00, 'indigo', 'active'),
('وحدة الموارد البشرية', 'U-MAN-02', 'لمى الدوسري', 3, 5, 120000.00, 'indigo', 'active');