-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 28 فبراير 2026 الساعة 07:26
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

USE security_monitoring_db;
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--

--

-- --------------------------------------------------------

--
-- بنية الجدول `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action_type`, `target_type`, `target_id`, `description`, `unit_id`, `ip_address`, `created_at`) VALUES
(1, 1, 'project_deployed', 'project', 3, 'المشروع P-1025 تم نشره بنجاح', 2, '192.168.1.100', '2026-02-16 23:30:45'),
(2, 4, 'vulnerability_found', 'project', 1, 'ثغرة حرجة تم اكتشافها في P-1019', 4, '192.168.1.104', '2026-02-16 23:30:45'),
(3, 2, 'documentation_completed', 'project', 2, 'تقرير توثيق مكتمل للمشروع P-1022', 1, '192.168.1.102', '2026-02-16 23:30:45'),
(4, 3, 'security_alert', 'system', NULL, 'تم رصد محاولة وصول غير مصرح', 3, '192.168.1.103', '2026-02-16 23:30:45'),
(5, 1, 'project_deployed', 'project', 3, 'المشروع P-1025 تم نشره بنجاح', 2, '192.168.1.100', '2026-02-17 00:43:53'),
(6, 4, 'vulnerability_found', 'project', 1, 'ثغرة حرجة تم اكتشافها في P-1019', 4, '192.168.1.104', '2026-02-17 00:43:53'),
(7, 2, 'documentation_completed', 'project', 2, 'تقرير توثيق مكتمل للمشروع P-1022', 1, '192.168.1.102', '2026-02-17 00:43:53'),
(8, 3, 'security_alert', 'system', NULL, 'تم رصد محاولة وصول غير مصرح', 3, '192.168.1.103', '2026-02-17 00:43:53');

-- --------------------------------------------------------

--
-- بنية الجدول `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('critical','warning','info') NOT NULL,
  `severity` enum('high','medium','low') NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `status` enum('new','acknowledged','in-progress','resolved') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `alerts`
--

INSERT INTO `alerts` (`id`, `title`, `description`, `type`, `severity`, `source`, `server_id`, `status`, `created_at`, `resolved_at`, `acknowledged_by`, `resolved_by`) VALUES
(1, 'ارتفاع استخدام المعالج', 'استخدام المعالج تجاوز 85%', 'critical', 'high', 'DB-Server-02', 2, 'new', '2026-02-16 03:52:22', NULL, NULL, NULL),
(2, 'محاولة وصول غير مصرح', '5 محاولات فاشلة من IP 45.76.89.123', 'warning', 'medium', 'Web-Server-01', 1, 'acknowledged', '2026-02-16 03:37:22', NULL, NULL, NULL),
(3, 'مساحة تخزين منخفضة', 'المساحة أقل من 10%', 'critical', 'high', 'DB-Server-02', 2, 'in-progress', '2026-02-16 03:07:22', NULL, NULL, NULL),
(4, 'حركة شبكة غير طبيعية', 'زيادة 200% في حركة الشبكة', 'warning', 'medium', 'Network', NULL, 'new', '2026-02-16 03:47:22', NULL, NULL, NULL),
(5, 'انقطاع خدمة', 'خادم التطبيقات 02 غير مستجيب', 'critical', 'high', 'App-Server-02', 8, 'new', '2026-02-16 03:12:22', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `alert_rules`
--

CREATE TABLE `alert_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `condition_type` enum('cpu','memory','storage','login_attempts','network') NOT NULL,
  `threshold_value` decimal(10,2) NOT NULL,
  `comparison_operator` enum('>','<','>=','<=','=') NOT NULL,
  `severity` enum('critical','warning','info') NOT NULL,
  `target_server_type` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `alert_rules`
--

INSERT INTO `alert_rules` (`id`, `name`, `description`, `condition_type`, `threshold_value`, `comparison_operator`, `severity`, `target_server_type`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ارتفاع CPU - خادم قاعدة بيانات', 'تنبيه عند ارتفاع استخدام المعالج فوق 85%', 'cpu', 85.00, '>', 'critical', 'database', 1, 1, '2026-02-16 04:07:22', '2026-02-16 04:07:22'),
(2, 'استخدام ذاكرة عالي', 'تنبيه عند استخدام الذاكرة فوق 90%', 'memory', 90.00, '>', 'critical', NULL, 1, 1, '2026-02-16 04:07:22', '2026-02-16 04:07:22'),
(3, 'مساحة تخزين منخفضة', 'تنبيه عند انخفاض المساحة تحت 15%', 'storage', 15.00, '<', 'warning', NULL, 1, 2, '2026-02-16 04:07:22', '2026-02-16 04:07:22'),
(4, 'محاولات دخول فاشلة', 'تنبيه عند 5 محاولات دخول فاشلة', 'login_attempts', 5.00, '>=', 'warning', NULL, 1, 2, '2026-02-16 04:07:22', '2026-02-16 04:07:22');

-- --------------------------------------------------------

--
-- بنية الجدول `archived_reports`
--

CREATE TABLE `archived_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('security','performance','financial','audit','compliance','operational') NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_format` enum('PDF','Excel','Word','CSV') DEFAULT 'PDF',
  `unit_id` int(11) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `archive_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `retention_years` int(11) DEFAULT 7,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `archived_reports`
--

INSERT INTO `archived_reports` (`id`, `report_name`, `report_type`, `file_path`, `file_size`, `file_format`, `unit_id`, `generated_by`, `report_date`, `archive_date`, `retention_years`, `tags`, `description`) VALUES
(1, 'تقرير الأمان الشهري - يناير 2024', 'security', '/reports/2024/01/security_jan.pdf', 24500000, 'PDF', 3, 1, '2024-01-28', '2026-02-16 23:30:45', 7, NULL, NULL),
(2, 'تقرير أداء الخوادم - الأسبوع الثالث', 'performance', '/reports/2024/01/performance_w3.xlsx', 15200000, 'Excel', 2, 3, '2024-01-25', '2026-02-16 23:30:45', 7, NULL, NULL),
(3, 'تقرير تدقيق التوثيق الربعي', 'audit', '/reports/2024/01/audit_q1.docx', 8700000, 'Word', 1, 2, '2024-01-20', '2026-02-16 23:30:45', 7, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `audit_findings`
--

CREATE TABLE `audit_findings` (
  `id` int(11) NOT NULL,
  `audit_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('security','performance','compliance','documentation','access','configuration','other') NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL,
  `status` enum('open','in-progress','resolved') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `detected_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `audit_findings`
--

INSERT INTO `audit_findings` (`id`, `audit_id`, `title`, `description`, `category`, `severity`, `status`, `assigned_to`, `created_by`, `detected_date`, `resolved_date`, `resolution_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'كلمات مرور ضعيفة', 'تم اكتشاف 12 حساب بكلمات مرور ضعيفة', 'security', 'high', 'resolved', 3, 1, '2024-01-16', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(2, 1, 'نقص في التوثيق', 'إجراءات الأمان غير موثقة بشكل كامل', 'documentation', 'medium', 'resolved', 2, 1, '2024-01-16', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(3, 2, 'ارتفاع استخدام المعالج', 'خادم قاعدة البيانات يعمل باستمرار عند 85%', 'performance', 'high', 'in-progress', 3, 1, '2024-02-02', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(4, 2, 'مساحة تخزين منخفضة', 'مساحة التخزين أقل من 15%', 'performance', 'medium', 'open', 3, 1, '2024-02-02', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(5, 3, 'عدم تحديث السياسات', 'سياسات الأمن لم تراجع منذ 6 أشهر', 'compliance', 'medium', 'open', 2, 1, '2024-02-15', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(6, 5, 'توثيق غير مكتمل', 'بعض إجراءات التشغيل غير موثقة', 'documentation', 'low', 'open', 4, 1, '2024-02-21', NULL, NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12');

-- --------------------------------------------------------

--
-- بنية الجدول `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_key` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT 'blue',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `categories`
--

INSERT INTO `categories` (`id`, `name`, `category_key`, `color`, `created_at`) VALUES
(1, 'خدمات الاستضافة', 'hosting', 'blue', '2026-02-22 22:44:57'),
(2, 'خدمات الحماية', 'security', 'red', '2026-02-22 22:44:57'),
(3, 'التخزين السحابي', 'storage', 'green', '2026-02-22 22:44:57'),
(4, 'خدمات إضافية', 'additional', 'purple', '2026-02-22 22:44:57');

-- --------------------------------------------------------

--
-- بنية الجدول `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `package_type` enum('basic','professional','enterprise') NOT NULL,
  `status` enum('active','suspended','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `clients`
--

INSERT INTO `clients` (`id`, `company_name`, `contact_name`, `email`, `phone`, `website`, `package_type`, `status`, `created_at`) VALUES
(1, 'شركة التقنية المتطورة', 'محمد العمري', 'info@tech-sa.com', '0555123456', 'tech-sa.com', 'enterprise', 'active', '2026-02-16 04:07:22'),
(2, 'مؤسسة الأمان الرقمي', 'أحمد الجهني', 'contact@digital-security.com', '0555234567', 'digital-security.com', 'professional', 'active', '2026-02-16 04:07:22'),
(3, 'متجر الإلكترونيات', 'سارة القحطاني', 'support@electronics-store.com', '0555345678', 'electronics-store.com', 'basic', 'active', '2026-02-16 04:07:22');

-- --------------------------------------------------------

--
-- بنية الجدول `client_activity_log`
--

CREATE TABLE `client_activity_log` (
  `id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','view','download','upload','payment','ticket','contract','report') NOT NULL,
  `target_type` enum('project','file','contract','invoice','ticket','report') DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_activity_log`
--

INSERT INTO `client_activity_log` (`id`, `client_id`, `activity_type`, `target_type`, `target_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.100', NULL, '2026-02-20 23:05:59'),
(2, 1, 'view', 'project', 1, 'عرض تفاصيل المشروع', '192.168.1.100', NULL, '2026-02-21 00:05:59'),
(3, 1, 'download', 'report', 1, 'تحميل تقرير', '192.168.1.100', NULL, '2026-02-21 00:35:59'),
(4, 2, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.101', NULL, '2026-02-20 20:05:59'),
(5, 2, 'view', 'invoice', 6, 'عرض الفاتورة', '192.168.1.101', NULL, '2026-02-20 21:05:59'),
(6, 3, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.102', NULL, '2026-02-20 01:05:59'),
(7, 3, 'upload', 'file', 6, 'رفع ملف', '192.168.1.102', NULL, '2026-02-20 02:05:59'),
(8, 4, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.103', NULL, '2026-02-19 01:05:59'),
(9, 4, 'view', '', 8, 'عرض معلومات الاستضافة', '192.168.1.103', NULL, '2026-02-19 01:05:59'),
(10, 5, 'login', NULL, NULL, 'تسجيل دخول', '192.168.1.104', NULL, '2026-02-18 01:05:59'),
(11, 1, 'upload', 'file', 11, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', NULL, NULL, '2026-02-21 01:09:08'),
(12, 1, 'upload', 'file', 12, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', NULL, NULL, '2026-02-21 01:09:08'),
(13, 1, 'upload', 'file', 13, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, '2026-02-21 01:09:08'),
(14, 1, 'upload', 'file', 14, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, '2026-02-21 01:09:08'),
(15, 1, 'upload', 'file', 15, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, '2026-02-21 03:41:16'),
(16, 1, 'upload', 'file', 16, 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, '2026-02-21 03:41:16'),
(17, 1, '', '', 1, 'إنشاء مجلد: محمد', NULL, NULL, '2026-02-21 04:01:15'),
(18, 1, 'upload', 'file', 17, 'رفع ملف: WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', NULL, NULL, '2026-02-21 04:01:43'),
(19, 1, '', 'file', 13, 'نقل ملف إلى /محمد', NULL, NULL, '2026-02-21 04:02:11'),
(20, 1, 'upload', 'file', 18, 'رفع ملف: applications.html', NULL, NULL, '2026-02-23 10:40:56'),
(21, 1, 'upload', 'file', 19, 'رفع ملف: deepseek_mermaid_20260105_6dacad.png', NULL, NULL, '2026-02-27 01:03:32'),
(22, 1, 'upload', 'file', 20, 'رفع ملف: تنبيه_قانوني.docx', NULL, NULL, '2026-02-27 01:04:58'),
(23, 1, '', 'file', 20, 'حذف ملف', NULL, NULL, '2026-02-27 01:05:19'),
(24, 1, '', '', 2, 'إنشاء مجلد: قاسم', NULL, NULL, '2026-02-27 01:05:30'),
(25, 1, 'upload', 'file', 21, 'رفع ملف: الثغرات.docx', NULL, NULL, '2026-02-27 01:05:59'),
(26, 1, 'upload', 'file', 22, 'رفع ملف: ثغرات_الويب.docx', NULL, NULL, '2026-02-27 01:05:59'),
(27, 1, 'upload', 'file', 23, 'رفع ملف: ملخص_جدار_الحماية.docx', NULL, NULL, '2026-02-27 01:05:59'),
(28, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:06:28'),
(29, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:29:54'),
(30, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:31:20'),
(31, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:31:25'),
(32, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:32:40'),
(33, 1, '', 'file', 23, 'نقل ملف إلى /', NULL, NULL, '2026-02-27 01:34:47');

-- --------------------------------------------------------

--
-- بنية الجدول `client_attachments`
--

CREATE TABLE `client_attachments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `target_type` enum('project','contract','invoice','ticket','report') NOT NULL,
  `target_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_attachments`
--

INSERT INTO `client_attachments` (`id`, `client_id`, `target_type`, `target_id`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'ticket', 2, 'خطأ_الرفع.png', '/attachments/ticket2/error.png', 245000, 'png', NULL, '2026-02-21 01:05:59'),
(2, 1, 'project', 1, 'ملاحظات_إضافية.pdf', '/attachments/project1/notes.pdf', 450000, 'pdf', NULL, '2026-02-21 01:05:59'),
(3, 2, 'contract', 4, 'تعديلات_العقد.docx', '/attachments/contract4/amendments.docx', 125000, 'docx', NULL, '2026-02-21 01:05:59'),
(4, 2, 'ticket', 4, 'صورة_الفاتورة.jpg', '/attachments/ticket4/invoice.jpg', 780000, 'jpg', NULL, '2026-02-21 01:05:59'),
(5, 3, 'report', 7, 'ملف_التدقيق.xlsx', '/attachments/report7/audit.xlsx', 560000, 'xlsx', NULL, '2026-02-21 01:05:59'),
(6, 3, 'project', 6, 'مخطط_قاعدة_البيانات.png', '/attachments/project6/db-diagram.png', 890000, 'png', NULL, '2026-02-21 01:05:59'),
(7, 4, 'ticket', 8, 'شرح_المشكلة.txt', '/attachments/ticket8/description.txt', 12000, 'txt', NULL, '2026-02-21 01:05:59'),
(8, 5, 'contract', 9, 'طلب_الإلغاء.pdf', '/attachments/contract9/cancellation.pdf', 340000, 'pdf', NULL, '2026-02-21 01:05:59'),
(9, 6, 'project', 10, 'متطلبات_الأرشفة.docx', '/attachments/project10/requirements.docx', 230000, 'docx', NULL, '2026-02-21 01:05:59'),
(10, 7, '', 1, 'وصف_المشروع.pdf', '/attachments/request1/project-desc.pdf', 180000, 'pdf', NULL, '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_clients`
--

CREATE TABLE `client_clients` (
  `id` int(11) NOT NULL,
  `client_code` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'السعودية',
  `password_hash` varchar(255) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `credit_limit` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_clients`
--

INSERT INTO `client_clients` (`id`, `client_code`, `full_name`, `email`, `phone`, `company_name`, `tax_number`, `address`, `city`, `country`, `password_hash`, `balance`, `credit_limit`, `status`, `email_verified`, `phone_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'CL-2024-001', 'أحمد محمد العلي', 'ahmed.alali@example.com', '0501234567', 'شركة التقنية المتطورة', '1234567890', 'الرياض - حي النخيل', 'الرياض', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 25000.00, 50000.00, 'active', 1, 1, '2024-03-15 10:30:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(2, 'CL-2024-002', 'سارة عبدالله القحطاني', 'sara.alqahtani@example.com', '0552345678', 'مؤسسة الأمان الرقمي', '1234567891', 'جدة - شارع التحلية', 'جدة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 18000.00, 30000.00, 'active', 1, 1, '2024-03-14 09:15:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(3, 'CL-2024-003', 'محمد عبدالله العمري', 'mohammed.omari@example.com', '0533456789', 'شركة البيانات الآمنة', '1234567892', 'الدمام - حي الشاطئ', 'الدمام', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 35000.00, 60000.00, 'active', 1, 1, '2024-03-15 11:45:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(4, 'CL-2024-004', 'نورة سعد الدوسري', 'noura.dosari@example.com', '0564567890', 'مؤسسة التجارة الإلكترونية', '1234567893', 'الخبر - العقربية', 'الخبر', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 12000.00, 20000.00, 'active', 1, 0, '2024-03-13 14:20:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(5, 'CL-2024-005', 'فهد خالد القحطاني', 'fahad.qahtani@example.com', '0545678901', 'شركة الحلول المتكاملة', '1234567894', 'مكة - العزيزية', 'مكة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 5000.00, 10000.00, 'suspended', 1, 0, '2024-03-10 08:30:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(6, 'CL-2024-006', 'ريم عبدالعزيز الشمري', 'reem.shamri@example.com', '0586789012', 'مؤسسة الشمري للتجارة', '1234567895', 'تبوك - النهضة', 'تبوك', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 8500.00, 15000.00, 'active', 1, 1, '2024-03-14 16:10:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(7, 'CL-2024-007', 'عبدالرحمن إبراهيم الحارثي', 'abdulrahman.harthy@example.com', '0597890123', 'شركة الحارثي للتطوير', '1234567896', 'الطائف - الشهداء', 'الطائف', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 22000.00, 35000.00, 'active', 1, 0, '2024-03-12 13:40:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(8, 'CL-2024-008', 'هند صالح العتيبي', 'hindi.otaibi@example.com', '0508901234', 'مؤسسة العتيبي للاستشارات', '1234567897', 'بريدة - الرحاب', 'بريدة', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 14500.00, 25000.00, 'active', 0, 0, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(9, 'CL-2024-009', 'سامي فهد المطيري', 'sami.mutairi@example.com', '0559012345', 'شركة المطيري للتجارة', '1234567898', 'حائل - المطار', 'حائل', 'السعودية', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 32000.00, 50000.00, 'active', 1, 1, '2024-03-11 10:15:00', '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(10, 'CL-2024-010', 'لمى بندر السبيعي', 'lama.subaie@example.com', '0560123456', 'مؤسسة السبيعي للتسويق', '1234567899', 'جيزان - الكورنيش', 'جيزان', 'السعودية', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 9500.00, 15000.00, 'inactive', 1, 0, '2024-03-05 09:30:00', '2026-02-21 01:05:58', '2026-02-27 00:53:48'),
(11, 'CL-MOH-001', 'محمد العلي', 'mohammed.alali@example.com', '0509876543', 'شركة العلي للتجارة', '987654321', 'الرياض - حي الملقا', 'الرياض', 'السعودية', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 25000.00, 50000.00, 'active', 1, 1, '2026-02-27 03:52:36', '2026-02-22 23:03:38', '2026-02-27 00:52:36');

-- --------------------------------------------------------

--
-- بنية الجدول `client_contracts`
--

CREATE TABLE `client_contracts` (
  `id` int(11) NOT NULL,
  `contract_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `contract_type` enum('hosting','storage','security','service') DEFAULT 'service',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `status` enum('draft','sent','under_review','signed','active','expired','cancelled') DEFAULT 'draft',
  `signed_by_client` tinyint(1) DEFAULT 0,
  `signed_by_company` tinyint(1) DEFAULT 0,
  `signed_at` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `value` decimal(15,2) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `special_terms` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_contracts`
--

INSERT INTO `client_contracts` (`id`, `contract_code`, `client_id`, `project_id`, `contract_type`, `title`, `description`, `file_path`, `file_size`, `status`, `signed_by_client`, `signed_by_company`, `signed_at`, `start_date`, `end_date`, `value`, `payment_terms`, `special_terms`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CON-HOST-001', 1, 1, 'hosting', 'عقد استضافة موقع التجارة الإلكترونية', NULL, '/contracts/contract-001.pdf', NULL, 'active', 1, 1, '2024-01-20 10:30:00', '2024-01-20', '2025-01-20', 15000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(2, 'CON-STOR-001', 1, 2, 'storage', 'عقد تخزين البيانات السحابي', NULL, '/contracts/contract-002.pdf', NULL, 'expired', 1, 1, '2023-11-15 14:20:00', '2023-11-15', '2024-01-15', 8000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(3, 'CON-DEV-001', 1, 3, 'service', 'عقد تطوير تطبيق الجوال', NULL, '/contracts/contract-003.pdf', NULL, 'active', 1, 1, '2024-02-20 11:30:00', '2024-02-20', '2024-05-30', 35000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(4, 'CON-SEC-001', 2, 4, 'security', 'عقد الفحص الأمني', NULL, '/contracts/contract-004.pdf', NULL, 'signed', 1, 0, '2024-02-05 09:15:00', '2024-02-05', '2024-03-15', 12000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(5, 'CON-PENT-001', 2, 5, 'service', 'عقد اختبار الاختراق', NULL, NULL, NULL, 'under_review', 0, 0, NULL, NULL, NULL, 20000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(6, 'CON-DEV-002', 3, 6, 'service', 'عقد تطوير نظام الموارد البشرية', NULL, '/contracts/contract-005.pdf', NULL, 'active', 1, 1, '2024-01-25 11:00:00', '2024-01-25', '2024-06-20', 45000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(7, 'CON-CONS-001', 3, 7, 'service', 'عقد الاستشارات التقنية', NULL, '/contracts/contract-006.pdf', NULL, 'signed', 1, 1, '2024-03-12 10:00:00', '2024-03-12', '2024-04-10', 8000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(8, 'CON-HOST-002', 4, 8, 'hosting', 'عقد استضافة موقع الشركة', NULL, '/contracts/contract-007.pdf', NULL, 'expired', 1, 1, '2024-01-10 13:45:00', '2024-01-10', '2024-02-28', 3000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(9, 'CON-HOST-003', 5, 9, 'hosting', 'عقد استضافة موقع تجريبي', NULL, NULL, NULL, 'cancelled', 0, 0, NULL, NULL, NULL, 2000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(10, 'CON-STOR-002', 6, 10, 'storage', 'عقد أرشفة المستندات', NULL, NULL, NULL, 'draft', 0, 0, NULL, NULL, NULL, 6000.00, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_domains`
--

CREATE TABLE `client_domains` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `domain_name` varchar(255) NOT NULL,
  `domain_type` enum('primary','secondary','parked','subdomain') DEFAULT 'primary',
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `registrar` varchar(100) DEFAULT NULL,
  `dns_provider` varchar(100) DEFAULT NULL,
  `nameserver1` varchar(255) DEFAULT NULL,
  `nameserver2` varchar(255) DEFAULT NULL,
  `nameserver3` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('active','pending','expired','cancelled') DEFAULT 'active',
  `ssl_status` enum('none','pending','active','expired') DEFAULT 'none',
  `ssl_issuer` varchar(100) DEFAULT NULL,
  `ssl_expiry` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_domains`
--

INSERT INTO `client_domains` (`id`, `client_id`, `project_id`, `domain_name`, `domain_type`, `registration_date`, `expiry_date`, `auto_renew`, `registrar`, `dns_provider`, `nameserver1`, `nameserver2`, `nameserver3`, `ip_address`, `status`, `ssl_status`, `ssl_issuer`, `ssl_expiry`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'ecommerce-store.com', 'primary', '2024-01-15', '2025-01-15', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'active', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(2, 1, 3, 'mobileapp.com', 'primary', '2024-02-20', '2025-02-20', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(3, 2, 4, 'security-scan.net', 'primary', '2024-02-01', '2025-02-01', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'active', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(4, 2, 5, 'pentest-lab.com', 'secondary', '2024-03-01', '2025-03-01', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 'none', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(5, 3, 6, 'hr-system.org', 'primary', '2024-01-20', '2025-01-20', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'active', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(6, 4, 8, 'corporate-site.com', 'primary', '2024-01-05', '2025-01-05', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'active', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(7, 5, 9, 'test-project.com', 'primary', '2024-02-01', '2024-03-01', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'expired', 'expired', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(8, 6, 10, 'archive-system.com', 'parked', '2024-03-15', '2025-03-15', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', 'none', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(9, 7, NULL, 'new-company.com', 'primary', '2024-03-10', '2025-03-10', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(10, 8, NULL, 'consulting.sa', 'primary', '2024-03-01', '2025-03-01', 1, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 'active', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_files`
--

CREATE TABLE `client_files` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT '/',
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_files`
--

INSERT INTO `client_files` (`id`, `client_id`, `project_id`, `file_name`, `file_path`, `file_type`, `file_size`, `mime_type`, `folder_path`, `description`, `uploaded_by`, `version`, `download_count`, `created_at`) VALUES
(1, 1, 1, 'المتطلبات_الفنية.pdf', '/uploads/client/1/project1/requirements.pdf', 'pdf', 2450000, NULL, '/project1', 'وثيقة متطلبات المشروع', NULL, '1.0', 15, '2026-02-21 01:05:58'),
(2, 1, 1, 'شعار_الشركة.png', '/uploads/client/1/project1/logo.png', 'png', 450000, NULL, '/project1/images', 'شعار الشركة', NULL, '1.0', 23, '2026-02-21 01:05:58'),
(3, 1, 3, 'تصميم_التطبيق.fig', '/uploads/client/1/project3/design.fig', 'fig', 8900000, NULL, '/project3/design', 'ملف تصميم التطبيق', NULL, '1.0', 8, '2026-02-21 01:05:58'),
(4, 2, 4, 'تقرير_الفحص_الأولي.pdf', '/uploads/client/2/project4/initial-report.pdf', 'pdf', 1850000, NULL, '/project4', 'تقرير الفحص الأمني الأولي', NULL, '1.0', 12, '2026-02-21 01:05:58'),
(5, 2, 5, 'نطاق_الاختبار.docx', '/uploads/client/2/project5/scope.docx', 'docx', 560000, NULL, '/project5', 'نطاق اختبار الاختراق', NULL, '1.0', 5, '2026-02-21 01:05:58'),
(6, 3, 6, 'هيكل_قاعدة_البيانات.sql', '/uploads/client/3/project6/db-schema.sql', 'sql', 125000, NULL, '/project6', 'هيكل قاعدة البيانات', NULL, '1.0', 7, '2026-02-21 01:05:58'),
(7, 3, 7, 'أسئلة_الاستشارة.pdf', '/uploads/client/3/project7/questions.pdf', 'pdf', 450000, NULL, '/project7', 'أسئلة استشارية', NULL, '1.0', 4, '2026-02-21 01:05:58'),
(8, 4, 8, 'صور_الشركة.zip', '/uploads/client/4/project8/images.zip', 'zip', 12500000, NULL, '/project8', 'صور الشركة', NULL, '1.0', 18, '2026-02-21 01:05:58'),
(9, 5, 9, 'عقد_الإلغاء.pdf', '/uploads/client/5/project9/cancellation.pdf', 'pdf', 120000, NULL, '/project9', 'عقد إلغاء المشروع', NULL, '1.0', 3, '2026-02-21 01:05:58'),
(10, 6, 10, 'مسودة_الأرشفة.docx', '/uploads/client/6/project10/draft.docx', 'docx', 340000, NULL, '/project10', 'مسودة نظام الأرشفة', NULL, '1.0', 2, '2026-02-21 01:05:58'),
(11, 1, NULL, 'WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', '/uploads/client/1/2026/02/21/699905b4d7fef_20260221_020908.jpg', 'jpg', 400559, NULL, '/', '', NULL, '1.0', 0, '2026-02-21 01:09:08'),
(12, 1, NULL, 'WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', '/uploads/client/1/2026/02/21/699905b4d9795_20260221_020908.jpg', 'jpg', 48691, NULL, '/', '', NULL, '1.0', 0, '2026-02-21 01:09:08'),
(13, 1, NULL, 'WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', '/uploads/client/1/محمد/WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', 'jpg', 63354, NULL, '/محمد', '', NULL, '1.0', 0, '2026-02-21 01:09:08'),
(14, 1, NULL, 'WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', '/uploads/client/1/2026/02/21/699905b4dc132_20260221_020908.jpg', 'jpg', 91277, NULL, '/', '', NULL, '1.0', 0, '2026-02-21 01:09:08'),
(15, 1, 1, 'WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', '/uploads/client/1/2026/02/21/6999295cd81bf_20260221_044116.jpg', 'jpg', 63354, NULL, '/', '', NULL, '1.0', 0, '2026-02-21 03:41:16'),
(16, 1, 1, 'WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', '/uploads/client/1/2026/02/21/6999295cd9ee5_20260221_044116.jpg', 'jpg', 91277, NULL, '/', '', NULL, '1.0', 0, '2026-02-21 03:41:16'),
(17, 1, 3, 'WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', '/uploads/client/1/محمد/WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', 'jpg', 91277, NULL, '/محمد/', '', NULL, '1.0', 0, '2026-02-21 04:01:43'),
(18, 1, 3, 'applications.html', '/uploads/client/1//applications.html', 'html', 3607, NULL, '/', 'توثيق النظام', NULL, '1.0', 0, '2026-02-23 10:40:56'),
(19, 1, NULL, 'deepseek_mermaid_20260105_6dacad.png', '/uploads/client/1//deepseek_mermaid_20260105_6dacad.png', 'png', 1168608, NULL, '/', '', NULL, '1.0', 0, '2026-02-27 01:03:32'),
(21, 1, 3, 'الثغرات.docx', '/uploads/client/1/محمد/قاسم/الثغرات.docx', 'docx', 42709, NULL, '/محمد/قاسم/', 'ملفات قاسم ', NULL, '1.0', 0, '2026-02-27 01:05:59'),
(22, 1, 3, 'ثغرات_الويب.docx', '/uploads/client/1/محمد/قاسم/ثغرات_الويب.docx', 'docx', 48548, NULL, '/محمد/قاسم/', 'ملفات قاسم ', NULL, '1.0', 0, '2026-02-27 01:05:59'),
(23, 1, 3, 'ملخص_جدار_الحماية.docx', '/uploads/client/1//ملخص_جدار_الحماية.docx', 'docx', 26702, NULL, '/', 'ملفات قاسم ', NULL, '1.0', 0, '2026-02-27 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_folders`
--

CREATE TABLE `client_folders` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `parent_path` varchar(500) DEFAULT '/',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_folders`
--

INSERT INTO `client_folders` (`id`, `client_id`, `folder_name`, `parent_path`, `created_at`) VALUES
(1, 1, 'محمد', '/', '2026-02-21 04:01:15'),
(2, 1, 'قاسم', '/محمد/', '2026-02-27 01:05:30');

-- --------------------------------------------------------

--
-- بنية الجدول `client_invoices`
--

CREATE TABLE `client_invoices` (
  `id` int(11) NOT NULL,
  `invoice_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `invoice_type` enum('monthly','quarterly','yearly','one_time','penalty') DEFAULT 'monthly',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`amount` + `tax_amount`) STORED,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','sent','pending','paid','overdue','cancelled') DEFAULT 'draft',
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_method` enum('cash','card','bank_transfer','cheque') DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_invoices`
--

INSERT INTO `client_invoices` (`id`, `invoice_code`, `client_id`, `project_id`, `contract_id`, `invoice_type`, `title`, `description`, `amount`, `tax_amount`, `paid_amount`, `status`, `issue_date`, `due_date`, `paid_date`, `payment_method`, `payment_reference`, `file_path`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-2024-001', 1, 1, 1, 'monthly', 'فاتورة استضافة - يناير 2024', NULL, 1250.00, 187.50, 1437.50, 'paid', '2024-01-01', '2024-01-15', '2024-01-10', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(2, 'INV-2024-002', 1, 1, 1, 'monthly', 'فاتورة استضافة - فبراير 2024', NULL, 1250.00, 187.50, 0.00, 'paid', '2024-02-01', '2024-02-15', '2026-02-24', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-24 20:51:25'),
(3, 'INV-2024-003', 1, 2, 2, 'one_time', 'فاتورة التخزين السنوية', NULL, 8000.00, 1200.00, 9200.00, 'paid', '2023-11-01', '2023-11-15', '2023-11-10', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(4, 'INV-2024-004', 1, 3, 3, 'one_time', 'فاتورة تطوير التطبيق - دفعة أولى', NULL, 15000.00, 2250.00, 17250.00, 'paid', '2024-02-20', '2024-03-05', '2024-02-28', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(5, 'INV-2024-005', 2, 4, 4, 'one_time', 'فاتورة الفحص الأمني - دفعة أولى', NULL, 6000.00, 900.00, 6900.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-10', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(6, 'INV-2024-006', 2, 4, 4, 'one_time', 'فاتورة الفحص الأمني - دفعة ثانية', NULL, 6000.00, 900.00, 0.00, 'pending', '2024-03-01', '2024-03-15', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(7, 'INV-2024-007', 3, 6, 6, 'monthly', 'فاتورة التطوير - يناير 2024', NULL, 5000.00, 750.00, 5750.00, 'paid', '2024-02-01', '2024-02-15', '2024-02-05', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(8, 'INV-2024-008', 3, 6, 6, 'monthly', 'فاتورة التطوير - فبراير 2024', NULL, 5000.00, 750.00, 5750.00, 'paid', '2024-03-01', '2024-03-15', '2024-03-05', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(9, 'INV-2024-009', 4, 8, 8, 'one_time', 'فاتورة استضافة موقع الشركة', NULL, 3000.00, 450.00, 3450.00, 'paid', '2024-01-01', '2024-01-15', '2024-01-12', NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(10, 'INV-2024-010', 6, 10, 10, 'one_time', 'فاتورة أرشفة المستندات - دفعة أولى', NULL, 3000.00, 450.00, 0.00, 'sent', '2024-03-15', '2024-03-30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_notifications`
--

CREATE TABLE `client_notifications` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_notifications`
--

INSERT INTO `client_notifications` (`id`, `client_id`, `type`, `title`, `message`, `link`, `is_read`, `read_at`, `created_at`) VALUES
(1, 1, 'success', 'تم دفع الفاتورة', 'تم استلام دفعتك بنجاح بقيمة 1437.50 ر.س', '/billing', 1, NULL, '2026-02-21 01:05:58'),
(2, 1, 'info', 'تقرير جديد', 'تم إنشاء تقرير تقدم المشروع لشهر فبراير', '/reports', 0, NULL, '2026-02-21 01:05:58'),
(3, 1, 'warning', 'فاتورة مستحقة', 'لديك فاتورة مستحقة الدفع بقيمة 1437.50 ر.س', '/billing', 0, NULL, '2026-02-21 01:05:58'),
(4, 2, 'info', 'رد على التذكرة', 'تم الرد على تذكرتك رقم TCK-2024-004', '/support', 1, NULL, '2026-02-21 01:05:58'),
(5, 2, 'success', 'اكتمال الفحص', 'اكتمل الفحص الأمني للمشروع', '/projects', 1, NULL, '2026-02-21 01:05:58'),
(6, 3, 'info', 'تحديث المشروع', 'تم تحديث حالة مشروع نظام الموارد البشرية', '/projects', 0, NULL, '2026-02-21 01:05:58'),
(7, 4, 'warning', 'تنبيه أمني', 'تم اكتشاف محاولة دخول غير مصرح بها', '/security', 0, NULL, '2026-02-21 01:05:58'),
(8, 4, 'success', 'تم التفعيل', 'تم تفعيل موقعك بنجاح', '/hosting', 1, NULL, '2026-02-21 01:05:58'),
(9, 5, 'info', 'تأكيد الإلغاء', 'تم تأكيد إلغاء المشروع', '/projects', 1, NULL, '2026-02-21 01:05:58'),
(10, 6, 'info', 'مشروع جديد', 'تم إنشاء مشروع أرشفة المستندات', '/projects', 0, NULL, '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_payments`
--

CREATE TABLE `client_payments` (
  `id` int(11) NOT NULL,
  `payment_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer','cheque') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_payments`
--

INSERT INTO `client_payments` (`id`, `payment_code`, `client_id`, `invoice_id`, `amount`, `payment_method`, `status`, `transaction_id`, `payment_date`, `reference_number`, `notes`, `receipt_path`, `created_by`, `created_at`) VALUES
(1, 'PAY-2024-001', 1, 1, 1437.50, 'card', 'completed', 'TXN123456789', '2024-01-10 14:30:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(2, 'PAY-2024-002', 1, 3, 9200.00, 'bank_transfer', 'completed', 'TRF987654321', '2023-11-10 09:15:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(3, 'PAY-2024-003', 1, 4, 17250.00, 'bank_transfer', 'completed', 'TRF456789123', '2024-02-28 11:20:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(4, 'PAY-2024-004', 2, 5, 6900.00, 'card', 'completed', 'TXN789123456', '2024-02-10 11:20:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(5, 'PAY-2024-005', 3, 7, 5750.00, 'bank_transfer', 'completed', 'TRF321654987', '2024-02-05 13:45:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(6, 'PAY-2024-006', 3, 8, 5750.00, 'card', 'completed', 'TXN654987321', '2024-03-05 10:00:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(7, 'PAY-2024-007', 4, 9, 3450.00, 'cash', 'completed', NULL, '2024-01-12 12:30:00', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(8, 'PAY-2024-008', 1, 2, 1437.50, 'bank_transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(9, 'PAY-2024-009', 2, 6, 6900.00, 'card', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(10, 'PAY-2024-010', 6, 10, 3450.00, 'bank_transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_projects`
--

CREATE TABLE `client_projects` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` enum('hosting','storage','security','pentest','consultation','development') DEFAULT 'hosting',
  `description` text DEFAULT NULL,
  `status` enum('pending','under_study','contract_pending','in_progress','testing','completed','cancelled') DEFAULT 'pending',
  `stage` int(11) DEFAULT 1 COMMENT '1:الطلب, 2:الدراسة, 3:العقد, 4:التنفيذ, 5:الفحص, 6:التسليم, 7:الدعم',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `budget` decimal(15,2) DEFAULT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `manager_name` varchar(255) DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL,
  `technical_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_projects`
--

INSERT INTO `client_projects` (`id`, `client_id`, `project_code`, `project_name`, `project_type`, `description`, `status`, `stage`, `priority`, `start_date`, `deadline`, `completion_date`, `progress`, `budget`, `paid_amount`, `manager_name`, `manager_phone`, `technical_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'PRJ-HOST-001', 'موقع التجارة الإلكترونية', 'hosting', 'متجر إلكتروني متكامل مع نظام إدارة محتوى', 'in_progress', 4, 'high', '2024-01-15', '2024-04-15', NULL, 65, 15000.00, 7500.00, 'أحمد العلي', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(2, 1, 'PRJ-STOR-001', 'نظام تخزين العملاء', 'storage', 'نظام تخزين سحابي لبيانات العملاء', 'completed', 6, 'medium', '2023-11-10', '2024-01-10', NULL, 100, 8000.00, 8000.00, 'سارة الأحمد', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(3, 1, 'PRJ-DEV-001', 'تطبيق الجوال للمتجر', 'development', 'تطوير تطبيق جوال لأنظمة iOS و Android', 'in_progress', 4, 'high', '2024-02-15', '2024-05-30', NULL, 35, 35000.00, 15000.00, 'محمد العنزي', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(4, 2, 'PRJ-SEC-001', 'فحص أمني شامل', 'security', 'اختبار اختراق وتقييم أمني', 'testing', 5, 'high', '2024-02-01', '2024-03-15', NULL, 85, 12000.00, 6000.00, 'خالد الرشيد', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(5, 2, 'PRJ-PENT-001', 'اختبار اختراق للتطبيق', 'pentest', 'اختبار اختراق للتطبيق المصرفي', 'contract_pending', 3, 'critical', '2024-03-01', '2024-04-30', NULL, 25, 20000.00, 0.00, 'فاطمة الزهراني', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(6, 3, 'PRJ-DEV-002', 'نظام إدارة الموارد البشرية', 'development', 'تطوير نظام متكامل للموارد البشرية', 'in_progress', 4, 'high', '2024-01-20', '2024-06-20', NULL, 40, 45000.00, 15000.00, 'عبدالله المطيري', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(7, 3, 'PRJ-CONS-001', 'استشارات تطوير البنية التحتية', 'consultation', 'استشارات لتطوير البنية التحتية التقنية', 'under_study', 2, 'medium', '2024-03-10', '2024-04-10', NULL, 20, 8000.00, 2000.00, 'ريم القحطاني', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(8, 4, 'PRJ-HOST-002', 'موقع الشركة التعريفي', 'hosting', 'موقع تعريفي بسيط للشركة', 'completed', 6, 'low', '2024-02-01', '2024-02-20', NULL, 100, 3000.00, 3000.00, 'منى الغامدي', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(9, 5, 'PRJ-HOST-003', 'موقع تجريبي', 'hosting', 'موقع تجريبي للاختبار', 'cancelled', 7, 'low', '2024-02-01', '2024-03-01', NULL, 20, 2000.00, 0.00, 'سامي الحربي', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(10, 6, 'PRJ-STOR-002', 'أرشفة المستندات', 'storage', 'نظام أرشفة للمستندات', 'pending', 1, 'medium', '2024-03-15', '2024-05-15', NULL, 0, 6000.00, 0.00, 'نورة الدوسري', NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_reports`
--

CREATE TABLE `client_reports` (
  `id` int(11) NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `report_type` enum('progress','security','performance','backup','audit','summary') DEFAULT 'progress',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `format` enum('pdf','excel','html','docx') DEFAULT 'pdf',
  `status` enum('generating','ready','sent','archived') DEFAULT 'generating',
  `generated_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_reports`
--

INSERT INTO `client_reports` (`id`, `report_code`, `client_id`, `project_id`, `report_type`, `title`, `description`, `file_path`, `file_size`, `format`, `status`, `generated_at`, `sent_at`, `viewed_at`, `created_by`, `created_at`) VALUES
(1, 'RPT-PROG-001', 1, 1, 'progress', 'تقرير تقدم المشروع - يناير 2024', NULL, '/reports/progress-jan-2024.pdf', NULL, 'pdf', 'ready', '2024-02-01 09:00:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(2, 'RPT-PROG-002', 1, 1, 'progress', 'تقرير تقدم المشروع - فبراير 2024', NULL, '/reports/progress-feb-2024.pdf', NULL, 'pdf', 'ready', '2024-03-01 10:30:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(3, 'RPT-DEV-001', 1, 3, 'progress', 'تقرير تطور تطبيق الجوال - الأسبوع 4', NULL, '/reports/app-progress-week4.pdf', NULL, 'pdf', 'ready', '2024-03-15 14:00:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(4, 'RPT-SEC-001', 2, 4, 'security', 'تقرير الثغرات الأمنية المكتشفة', NULL, '/reports/security-vulnerabilities.pdf', NULL, 'pdf', 'ready', '2024-02-20 11:15:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(5, 'RPT-SEC-002', 2, 4, 'security', 'تقرير الفحص الأمني - المرحلة الأولى', NULL, '/reports/security-phase1.pdf', NULL, 'pdf', 'ready', '2024-03-01 13:30:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(6, 'RPT-PERF-001', 3, 6, 'performance', 'تقرير أداء النظام', NULL, '/reports/performance-report.pdf', NULL, 'pdf', 'ready', '2024-02-28 09:45:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(7, 'RPT-AUDIT-001', 3, 6, 'audit', 'تقرير تدقيق المتطلبات', NULL, '/reports/audit-requirements.pdf', NULL, 'pdf', 'ready', '2024-03-10 15:20:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(8, 'RPT-HOST-001', 4, 8, 'summary', 'تقرير إحصائيات الموقع', NULL, '/reports/site-stats.pdf', NULL, 'pdf', 'ready', '2024-02-15 08:30:00', NULL, NULL, NULL, '2026-02-21 01:05:58'),
(9, 'RPT-STOR-001', 6, 10, 'backup', 'تقرير حالة النسخ الاحتياطي', NULL, '/reports/backup-status.pdf', NULL, 'pdf', 'generating', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58'),
(10, 'RPT-SUM-001', 5, 9, 'summary', 'ملخص إلغاء المشروع', NULL, '/reports/cancellation-summary.pdf', NULL, 'pdf', 'sent', '2024-03-05 12:00:00', NULL, NULL, NULL, '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_requests`
--

CREATE TABLE `client_requests` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `company` varchar(200) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_type` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `status` enum('new','reviewing','accepted','rejected','completed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_requests`
--

INSERT INTO `client_requests` (`id`, `full_name`, `email`, `phone`, `company`, `service_id`, `service_type`, `details`, `status`, `created_at`) VALUES
(1, 'أحمد محمد', 'ahmed@example.com', '0555555555', 'شركة التقنية', 2, 'استضافة VPS مخصصة', 'أحتاج إلى استضافة لتطبيق ويب مع قاعدة بيانات. نتوقع حوالي 10000 زائر يومياً.', 'reviewing', '2024-01-15 07:30:00'),
(2, 'سارة علي', 'sara@example.com', '0566666666', 'متجر الإلكتروني', 4, 'استضافة متاجر إلكترونية', 'أريد استضافة لمتجر WooCommerce مع 500 منتج.', 'accepted', '2024-01-14 11:20:00'),
(3, 'محمد عبدالله', 'mohamed@example.com', '0577777777', NULL, 13, 'جدار حماية متقدم (WAF)', 'نريد حماية موقع الشركة من الهجمات الإلكترونية.', 'new', '2024-01-16 06:15:00'),
(4, 'نورة سعد', 'noura@example.com', '0588888888', 'شركة المحاماة', 23, 'تخزين سحابي مشفر', 'نحتاج إلى تخزين آمن للمستندات القانونية.', 'completed', '2024-01-13 08:45:00'),
(5, 'خالد ابراهيم', 'khalid@example.com', '0599999999', 'مؤسسة البرمجيات', 5, 'استضافة تطبيقات Node.js', 'نحن نطور تطبيق دردشة ونحتاج استضافة تدعم WebSocket.', 'reviewing', '2024-01-16 10:30:00'),
(6, 'Fatom Alariqi', 'fatomalariqi@gmail.com', '771241661', 'mihammed', NULL, 'استضافة تطبيقات Python', 'MOHAMMED', 'new', '2026-02-24 20:46:26'),
(7, 'Fatom Alariqi', 'fatomalariqi@gmail.com', '771241661', 'mihammed', NULL, 'حماية من هجمات DDoS', 'MOHAMMED', 'new', '2026-02-24 20:48:15'),
(8, 'Fatom Alariqi', 'fatomalariqi@gmail.com', '771241661', 'mihammed', NULL, 'حماية من هجمات DDoS', 'MOHAMMED', 'new', '2026-02-24 22:19:39');

-- --------------------------------------------------------

--
-- بنية الجدول `client_service_requests`
--

CREATE TABLE `client_service_requests` (
  `id` int(11) NOT NULL,
  `request_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `service_type` enum('hosting','storage','security','pentest','consultation','development') NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected','converted') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `converted_to_project` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_service_requests`
--

INSERT INTO `client_service_requests` (`id`, `request_code`, `client_id`, `service_type`, `project_name`, `description`, `budget`, `deadline`, `status`, `admin_notes`, `converted_to_project`, `created_at`, `updated_at`) VALUES
(1, 'REQ-2024-001', 7, 'hosting', 'موقع شركة جديد', 'نحتاج موقع تعريفي للشركة', 5000.00, '2024-05-01', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(2, 'REQ-2024-002', 8, 'development', 'تطبيق جوال', 'تطوير تطبيق جوال للمبيعات', 25000.00, '2024-07-01', 'under_review', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(3, 'REQ-2024-003', 9, 'security', 'فحص أمني', 'فحص أمني شامل للأنظمة', 15000.00, '2024-06-01', 'approved', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(4, 'REQ-2024-004', 10, 'storage', 'توسعة التخزين', 'نحتاج زيادة مساحة التخزين', 3000.00, '2024-04-15', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(5, 'REQ-2024-005', 1, 'pentest', 'اختبار اختراق', 'اختبار اختراق للموقع', 10000.00, '2024-05-15', 'under_review', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(6, 'REQ-2024-006', 2, 'consultation', 'استشارة تقنية', 'استشارة حول تحسين الأداء', 4000.00, '2024-04-30', 'approved', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(7, 'REQ-2024-007', 3, 'development', 'تطوير نظام محاسبة', 'نظام محاسبة متكامل', 35000.00, '2024-08-01', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(8, 'REQ-2024-008', 4, 'hosting', 'ترقية استضافة', 'ترقية خطة الاستضافة', 2000.00, '2024-04-20', 'approved', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(9, 'REQ-2024-009', 5, 'security', 'تدقيق أمني', 'تدقيق أمني للموقع', 8000.00, '2024-05-30', 'rejected', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(10, 'REQ-2024-010', 6, 'storage', 'أرشفة إضافية', 'إضافة مساحة أرشفة', 1500.00, '2024-04-25', 'pending', NULL, NULL, '2026-02-21 01:05:59', '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_settings`
--

CREATE TABLE `client_settings` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `language` varchar(10) DEFAULT 'ar',
  `notifications_email` tinyint(1) DEFAULT 1,
  `notifications_sms` tinyint(1) DEFAULT 0,
  `notifications_browser` tinyint(1) DEFAULT 1,
  `theme` varchar(20) DEFAULT 'dark',
  `timezone` varchar(50) DEFAULT 'Asia/Riyadh',
  `date_format` varchar(20) DEFAULT 'Y-m-d',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_settings`
--

INSERT INTO `client_settings` (`id`, `client_id`, `language`, `notifications_email`, `notifications_sms`, `notifications_browser`, `theme`, `timezone`, `date_format`, `updated_at`) VALUES
(1, 1, 'ar', 1, 1, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(2, 2, 'ar', 1, 0, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(3, 3, 'ar', 1, 1, 0, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(4, 4, 'ar', 1, 0, 1, 'light', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(5, 5, 'ar', 0, 0, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(6, 6, 'ar', 1, 1, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(7, 7, 'ar', 1, 0, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(8, 8, 'ar', 1, 0, 0, 'light', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(9, 9, 'ar', 1, 1, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59'),
(10, 10, 'ar', 1, 0, 1, 'dark', 'Asia/Riyadh', 'Y-m-d', '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_stats`
--

CREATE TABLE `client_stats` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `projects_count` int(11) DEFAULT 0,
  `active_projects` int(11) DEFAULT 0,
  `completed_projects` int(11) DEFAULT 0,
  `files_count` int(11) DEFAULT 0,
  `files_size` bigint(20) DEFAULT 0,
  `invoices_total` decimal(15,2) DEFAULT 0.00,
  `invoices_paid` decimal(15,2) DEFAULT 0.00,
  `tickets_count` int(11) DEFAULT 0,
  `open_tickets` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_stats`
--

INSERT INTO `client_stats` (`id`, `client_id`, `stat_date`, `projects_count`, `active_projects`, `completed_projects`, `files_count`, `files_size`, `invoices_total`, `invoices_paid`, `tickets_count`, `open_tickets`, `created_at`) VALUES
(1, 1, '2024-01-31', 3, 2, 1, 8, 11800000, 23000.00, 26250.00, 2, 0, '2026-02-21 01:05:59'),
(2, 1, '2024-02-29', 3, 2, 1, 12, 16300000, 24250.00, 26250.00, 3, 1, '2026-02-21 01:05:59'),
(3, 1, '2024-03-20', 3, 2, 1, 15, 19800000, 25500.00, 26250.00, 4, 1, '2026-02-21 01:05:59'),
(4, 2, '2024-02-29', 2, 2, 0, 5, 2410000, 12000.00, 6900.00, 2, 1, '2026-02-21 01:05:59'),
(5, 2, '2024-03-20', 2, 2, 0, 7, 3260000, 18000.00, 6900.00, 3, 2, '2026-02-21 01:05:59'),
(6, 3, '2024-02-29', 2, 2, 0, 4, 1850000, 5000.00, 5750.00, 2, 1, '2026-02-21 01:05:59'),
(7, 3, '2024-03-20', 2, 2, 0, 6, 2700000, 10000.00, 11500.00, 3, 1, '2026-02-21 01:05:59'),
(8, 4, '2024-02-29', 1, 0, 1, 2, 12500000, 3000.00, 3450.00, 1, 0, '2026-02-21 01:05:59'),
(9, 4, '2024-03-20', 1, 0, 1, 3, 12800000, 3000.00, 3450.00, 2, 1, '2026-02-21 01:05:59'),
(10, 5, '2024-03-20', 1, 0, 0, 1, 120000, 2000.00, 0.00, 1, 0, '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `client_support_tickets`
--

CREATE TABLE `client_support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_code` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
  `category` enum('technical','billing','sales','general') DEFAULT 'general',
  `attachments` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_support_tickets`
--

INSERT INTO `client_support_tickets` (`id`, `ticket_code`, `client_id`, `project_id`, `subject`, `message`, `priority`, `status`, `category`, `attachments`, `assigned_to`, `resolved_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 'TCK-2024-001', 1, 1, 'استفسار عن سرعة الموقع', 'السلام عليكم، أريد الاستفسار عن إمكانية زيادة سرعة الموقع', 'medium', 'resolved', 'technical', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(2, 'TCK-2024-002', 1, 1, 'مشكلة في رفع الملفات', 'لا أستطيع رفع الملفات إلى لوحة التحكم', 'high', 'in_progress', 'technical', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(3, 'TCK-2024-003', 1, 3, 'استفسار عن موعد التسليم', 'متى الموعد المتوقع لتسليم التطبيق؟', 'medium', 'waiting', 'general', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(4, 'TCK-2024-004', 2, 4, 'استفسار عن الفاتورة', 'هل يمكن توضيح بنود الفاتورة رقم INV-2024-006؟', 'low', 'closed', 'billing', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(5, 'TCK-2024-005', 2, 5, 'تأخير في المشروع', 'نحتاج تمديد الموعد النهائي للمشروع', 'high', 'open', 'general', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(6, 'TCK-2024-006', 3, 6, 'طلب ميزة جديدة', 'نحتاج إضافة نظام تقارير متقدم', 'medium', 'in_progress', 'technical', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(7, 'TCK-2024-007', 3, 7, 'استفسار عن الاستشارة', 'هل يمكن إضافة جلسة استشارية إضافية؟', 'low', 'resolved', 'general', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(8, 'TCK-2024-008', 4, 8, 'مشكلة في تسجيل الدخول', 'لا أستطيع تسجيل الدخول للوحة التحكم', 'urgent', 'open', 'technical', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(9, 'TCK-2024-009', 5, 9, 'استفسار عن الإلغاء', 'كيف يمكن استرداد المبلغ المدفوع؟', 'medium', 'waiting', 'billing', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58'),
(10, 'TCK-2024-010', 6, 10, 'استفسار عن الأرشفة', 'هل يدعم النظام أرشفة الملفات الكبيرة؟', 'low', 'resolved', 'technical', NULL, NULL, NULL, NULL, '2026-02-21 01:05:58', '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_ticket_replies`
--

CREATE TABLE `client_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_staff` tinyint(1) DEFAULT 0,
  `message` text NOT NULL,
  `attachments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_ticket_replies`
--

INSERT INTO `client_ticket_replies` (`id`, `ticket_id`, `user_id`, `is_staff`, `message`, `attachments`, `created_at`) VALUES
(1, 1, 1, 0, 'وعليكم السلام، نعم يمكن زيادة السرعة. الرجاء تحديد الخطة المناسبة', NULL, '2026-02-21 01:05:58'),
(2, 1, 2, 1, 'تمت زيادة السرعة إلى 100 Mbps، يرجى التأكد من الأداء', NULL, '2026-02-21 01:05:58'),
(3, 2, 1, 0, 'تظهر رسالة خطأ عند رفع الملفات', NULL, '2026-02-21 01:05:58'),
(4, 2, 2, 1, 'تم حل المشكلة، كان هناك خطأ في الصلاحيات', NULL, '2026-02-21 01:05:58'),
(5, 3, 2, 1, 'الموعد المتوقع للتسليم هو 30 مايو 2024', NULL, '2026-02-21 01:05:58'),
(6, 4, 2, 1, 'الفاتورة تشمل رسوم التطوير الشهرية وخدمات إضافية', NULL, '2026-02-21 01:05:58'),
(7, 5, 2, 1, 'تم تحويل طلبكم للإدارة للنظر في التمديد', NULL, '2026-02-21 01:05:58'),
(8, 6, 2, 1, 'سنقوم بإضافة نظام التقارير في الإصدار القادم', NULL, '2026-02-21 01:05:58'),
(9, 7, 1, 0, 'نعم، نرغب في إضافة جلسة استشارية إضافية', NULL, '2026-02-21 01:05:58'),
(10, 8, 2, 1, 'تم إعادة تعيين كلمة المرور، يرجى التحقق من بريدك الإلكتروني', NULL, '2026-02-21 01:05:58');

-- --------------------------------------------------------

--
-- بنية الجدول `client_websites`
--

CREATE TABLE `client_websites` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `server_id` int(11) DEFAULT NULL,
  `status` enum('active','suspended','maintenance') DEFAULT 'active',
  `disk_usage` bigint(20) DEFAULT 0,
  `bandwidth_usage` bigint(20) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `client_websites`
--

INSERT INTO `client_websites` (`id`, `client_id`, `domain`, `server_id`, `status`, `disk_usage`, `bandwidth_usage`, `created_at`) VALUES
(1, 1, 'tech-sa.com', 1, 'active', 1500000000, 5000000000, '2026-02-16 04:07:22'),
(2, 1, 'api.tech-sa.com', 3, 'active', 500000000, 2000000000, '2026-02-16 04:07:22'),
(3, 2, 'digital-security.com', 1, 'active', 800000000, 3000000000, '2026-02-16 04:07:22'),
(4, 3, 'electronics-store.com', 5, 'active', 2000000000, 8000000000, '2026-02-16 04:07:22');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_activity_log`
--

CREATE TABLE `cloud_activity_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('create','update','delete','upload','download','deploy','backup','restore','security','reboot','start','stop') NOT NULL,
  `target_type` enum('server','project','file','backup','deployment','update','service','report') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_activity_log`
--

INSERT INTO `cloud_activity_log` (`id`, `user_id`, `activity_type`, `target_type`, `target_id`, `target_name`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'create', 'deployment', 6, NULL, 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, '2026-02-20 00:21:43'),
(2, 1, '', 'server', 4, NULL, 'إعادة تشغيل الخادم', NULL, NULL, NULL, '2026-02-20 00:46:07'),
(3, 1, '', '', 2, NULL, 'تطبيق تحديث أمني', NULL, NULL, NULL, '2026-02-20 01:18:48'),
(4, 12, 'create', 'deployment', 7, NULL, 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, '2026-02-27 00:43:19'),
(5, 12, 'restore', 'backup', 1, NULL, 'استعادة نسخة احتياطية', NULL, NULL, NULL, '2026-02-27 00:44:20');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_backups`
--

CREATE TABLE `cloud_backups` (
  `id` int(11) NOT NULL,
  `backup_code` varchar(50) NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `backup_type` enum('full','incremental','differential','mirror','snapshot') DEFAULT 'full',
  `size_mb` decimal(10,2) DEFAULT 0.00,
  `files_count` int(11) DEFAULT 0,
  `destination` enum('local','remote','both','cloud') DEFAULT 'local',
  `storage_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed','restoring') DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `retention_days` int(11) DEFAULT 30,
  `is_automated` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `restored_at` datetime DEFAULT NULL,
  `restored_by` int(11) DEFAULT NULL,
  `restored_to` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_backups`
--

INSERT INTO `cloud_backups` (`id`, `backup_code`, `backup_name`, `project_id`, `server_id`, `backup_type`, `size_mb`, `files_count`, `destination`, `storage_path`, `status`, `started_at`, `completed_at`, `retention_days`, `is_automated`, `created_by`, `restored_at`, `restored_by`, `restored_to`, `notes`, `created_at`) VALUES
(1, 'BAK-2024-001', 'نسخة احتياطية يومية 2024-01-15', 1, 1, 'incremental', 156.00, 1240, 'local', '/backups/ecommerce/2024-01-15', 'completed', '2024-01-15 02:00:00', '2024-01-15 02:15:30', 30, 1, 2, '2026-02-27 03:44:20', 12, NULL, NULL, '2026-02-19 22:11:06'),
(2, 'BAK-2024-002', 'نسخة احتياطية أسبوعية', 2, 1, 'full', 850.00, 5120, 'both', '/backups/blog/weekly-2024-02', 'completed', '2024-01-14 02:00:00', '2024-01-14 02:45:20', 90, 1, 2, NULL, NULL, NULL, NULL, '2026-02-19 22:11:06'),
(3, 'BAK-2024-003', 'نسخة احتياطية قاعدة البيانات', NULL, 2, 'full', 2450.00, 1, 'remote', 's3://backups/db-2024-01-13', 'completed', '2024-01-13 03:00:00', '2024-01-13 03:10:45', 30, 1, 3, NULL, NULL, NULL, NULL, '2026-02-19 22:11:06'),
(4, 'BAK-2024-004', 'نسخة شهرية', 1, 1, 'full', 1250.00, 8560, 'both', '/backups/ecommerce/monthly-jan', 'completed', '2024-01-01 02:00:00', '2024-01-01 03:20:00', 365, 1, 2, NULL, NULL, NULL, NULL, '2026-02-19 22:11:06'),
(5, 'BAK-2024-005', 'نسخة اختبارية', 3, 1, 'full', 45.00, 320, 'local', '/backups/api/test', 'failed', '2024-01-12 04:00:00', NULL, 7, 0, 3, NULL, NULL, NULL, NULL, '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_backup_schedules`
--

CREATE TABLE `cloud_backup_schedules` (
  `id` int(11) NOT NULL,
  `schedule_name` varchar(255) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `backup_type` enum('full','incremental','differential') DEFAULT 'full',
  `frequency` enum('hourly','daily','weekly','monthly','yearly') DEFAULT 'daily',
  `scheduled_time` time DEFAULT NULL,
  `scheduled_day` varchar(20) DEFAULT NULL,
  `scheduled_date` int(11) DEFAULT NULL,
  `destination` enum('local','remote','both','cloud') DEFAULT 'local',
  `retention_days` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_backup_schedules`
--

INSERT INTO `cloud_backup_schedules` (`id`, `schedule_name`, `project_id`, `server_id`, `backup_type`, `frequency`, `scheduled_time`, `scheduled_day`, `scheduled_date`, `destination`, `retention_days`, `is_active`, `last_run`, `next_run`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'نسخ يومي - موقع التجارة', 1, 1, 'incremental', 'daily', '02:00:00', NULL, NULL, 'local', 30, 1, '2024-01-15 02:00:00', '2024-01-16 02:00:00', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(2, 'نسخ أسبوعي - المدونة', 2, 1, 'full', 'weekly', '02:00:00', 'Sunday', NULL, 'both', 90, 1, '2024-01-14 02:00:00', '2024-01-21 02:00:00', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(3, 'نسخ قاعدة البيانات', NULL, 2, 'full', 'daily', '03:00:00', NULL, NULL, 'remote', 30, 1, '2024-01-15 03:00:00', '2024-01-16 03:00:00', 3, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(4, 'نسخ شهري - شامل', 1, 1, 'full', 'monthly', '02:00:00', '1', NULL, 'both', 365, 1, '2024-01-01 02:00:00', '2024-02-01 02:00:00', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(5, 'نسخ اختباري', 3, 1, 'full', 'weekly', '04:00:00', 'Friday', NULL, 'local', 7, 0, NULL, '2024-01-19 04:00:00', 3, '2026-02-19 22:11:06', '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_deployments`
--

CREATE TABLE `cloud_deployments` (
  `id` int(11) NOT NULL,
  `deployment_code` varchar(50) NOT NULL,
  `project_id` int(11) NOT NULL,
  `deployment_type` enum('full','incremental','quick','rollback') DEFAULT 'full',
  `environment` enum('development','staging','production','testing') DEFAULT 'development',
  `status` enum('pending','in_progress','success','failed','rolled_back','cancelled') DEFAULT 'pending',
  `version` varchar(50) DEFAULT NULL,
  `commit_hash` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `files_count` int(11) DEFAULT 0,
  `size_mb` decimal(10,2) DEFAULT 0.00,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `deployed_by` int(11) DEFAULT NULL,
  `logs` text DEFAULT NULL,
  `error_log` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_deployments`
--

INSERT INTO `cloud_deployments` (`id`, `deployment_code`, `project_id`, `deployment_type`, `environment`, `status`, `version`, `commit_hash`, `branch`, `files_count`, `size_mb`, `started_at`, `completed_at`, `deployed_by`, `logs`, `error_log`, `created_at`) VALUES
(1, 'DEP-2024-001', 1, 'full', 'production', 'success', '2.1.0', 'abc123def456', 'main', 124, 45.60, '2024-01-15 10:30:00', '2024-01-15 10:32:15', 2, 'نشر ناجح', NULL, '2026-02-19 22:11:06'),
(2, 'DEP-2024-002', 1, 'incremental', 'staging', 'success', '2.1.1', 'def789ghi012', 'develop', 15, 2.30, '2024-01-14 14:20:00', '2024-01-14 14:21:30', 2, 'تحديثات طفيفة', NULL, '2026-02-19 22:11:06'),
(3, 'DEP-2024-003', 2, 'full', 'production', 'success', '1.3.2', 'jkl345mno678', 'main', 85, 28.40, '2024-01-13 11:00:00', '2024-01-13 11:02:45', 3, 'نشر المدونة', NULL, '2026-02-19 22:11:06'),
(4, 'DEP-2024-004', 3, 'quick', 'development', 'failed', '3.0.1', 'pqr901stu234', 'feature/api', 8, 1.20, '2024-01-12 16:45:00', '2024-01-12 16:45:30', 3, 'فشل في الاتصال', NULL, '2026-02-19 22:11:06'),
(5, 'DEP-2024-005', 1, 'full', 'production', 'rolled_back', '2.0.9', 'vwx567yza890', 'main', 124, 45.60, '2024-01-10 09:15:00', '2024-01-10 09:17:00', 2, 'تم التراجع عن النشر', NULL, '2026-02-19 22:11:06'),
(6, 'DEP-2026-001', 2, 'full', 'production', 'pending', '1.0.0', NULL, 'main', 0, 0.00, '2026-02-20 03:21:43', NULL, 1, NULL, NULL, '2026-02-20 00:21:43'),
(7, 'DEP-2026-002', 2, 'incremental', 'staging', 'pending', '1.0.0', NULL, 'main', 0, 0.00, '2026-02-27 03:43:19', NULL, 12, NULL, NULL, '2026-02-27 00:43:19');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_files`
--

CREATE TABLE `cloud_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT '/',
  `project_id` int(11) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `is_folder` tinyint(1) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0',
  `permissions` varchar(9) DEFAULT '644',
  `owner` varchar(100) DEFAULT NULL,
  `group_owner` varchar(100) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `last_accessed` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_files`
--

INSERT INTO `cloud_files` (`id`, `file_name`, `file_path`, `file_type`, `file_size`, `mime_type`, `folder_path`, `project_id`, `server_id`, `is_folder`, `is_public`, `download_count`, `uploaded_by`, `version`, `permissions`, `owner`, `group_owner`, `checksum`, `last_accessed`, `created_at`, `updated_at`) VALUES
(1, 'index.html', '/var/www/ecommerce/public/index.html', 'html', 45, NULL, '/ecommerce/public', 1, 1, 0, 0, 1245, NULL, '1.0', '644', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(2, 'app.js', '/var/www/ecommerce/resources/js/app.js', 'js', 120, NULL, '/ecommerce/resources/js', 1, 1, 0, 0, 3560, NULL, '2.1', '644', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(3, 'banner.jpg', '/var/www/ecommerce/public/images/banner.jpg', 'jpg', 2450, NULL, '/ecommerce/public/images', 1, 1, 0, 0, 890, NULL, '1.0', '644', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(4, 'style.css', '/var/www/ecommerce/public/css/style.css', 'css', 78, NULL, '/ecommerce/public/css', 1, 1, 0, 0, 2340, NULL, '1.5', '644', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(5, 'wp-config.php', '/var/www/blog/wp-config.php', 'php', 12, NULL, '/blog', 2, 1, 0, 0, 560, NULL, '1.0', '640', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(6, 'database.sql', '/backups/database.sql', 'sql', 15420, NULL, '/backups', NULL, 3, 0, 0, 45, NULL, '2024-01-15', '600', 'root', 'root', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(7, 'المجلد الرئيسي', '/sites', NULL, 0, NULL, '/', NULL, 1, 1, 0, 0, NULL, NULL, '755', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(8, 'صور', '/images', NULL, 0, NULL, '/', NULL, 1, 1, 0, 0, NULL, NULL, '755', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(9, 'سكربتات', '/scripts', NULL, 0, NULL, '/', NULL, 1, 1, 0, 0, NULL, NULL, '755', 'www-data', 'www-data', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(10, 'نسخ احتياطية', '/backups', NULL, 0, NULL, '/', NULL, 3, 1, 0, 0, NULL, NULL, '700', 'root', 'root', NULL, NULL, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(11, 'المستندات', '/documents', NULL, 0, NULL, '/', NULL, NULL, 1, 0, 0, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(12, 'الصور', '/images', NULL, 0, NULL, '/', NULL, NULL, 1, 0, 0, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(13, 'الفيديوهات', '/videos', NULL, 0, NULL, '/', NULL, NULL, 1, 0, 0, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(14, 'النسخ الاحتياطية', '/backups', NULL, 0, NULL, '/', NULL, NULL, 1, 0, 0, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(15, 'تقرير_المبيعات.pdf', '/report.pdf', 'pdf', 2450000, NULL, '/', NULL, NULL, 0, 0, 125, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(16, 'صورة_المنتج.jpg', '/product.jpg', 'jpg', 890000, NULL, '/', NULL, NULL, 0, 0, 67, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(17, 'ملف_تكوين.json', '/config.json', 'json', 4500, NULL, '/', NULL, NULL, 0, 0, 23, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(18, 'دليل_الاستخدام.pdf', '/manual.pdf', 'pdf', 1850000, NULL, '/', NULL, NULL, 0, 0, 89, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(19, 'عقد_تطوير.docx', '/documents/contract.docx', 'docx', 560000, NULL, '/documents', NULL, NULL, 0, 0, 34, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(20, 'مواصفات_المشروع.pdf', '/documents/specs.pdf', 'pdf', 3200000, NULL, '/documents', NULL, NULL, 0, 0, 56, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(21, 'ملاحظات_الاجتماع.txt', '/documents/notes.txt', 'txt', 12000, NULL, '/documents', NULL, NULL, 0, 0, 12, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(22, 'شعار_الشركة.png', '/images/logo.png', 'png', 245000, NULL, '/images', NULL, NULL, 0, 0, 234, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(23, 'خلفية_الموقع.jpg', '/images/background.jpg', 'jpg', 1850000, NULL, '/images', NULL, NULL, 0, 0, 167, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(24, 'أيقونة.png', '/images/icon.png', 'png', 45000, NULL, '/images', NULL, NULL, 0, 0, 89, NULL, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:13:37', '2026-02-19 23:13:37'),
(25, 'محمد ', '/sites/محمد ', 'folder', 0, 'folder', '/sites', NULL, NULL, 1, 0, 0, 1, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:30:36', '2026-02-20 00:10:13'),
(26, 'محمد ', '/محمد /محمد ', 'folder', 0, 'folder', '/محمد ', NULL, NULL, 1, 0, 0, 1, '1.0', '644', NULL, NULL, NULL, NULL, '2026-02-19 23:38:01', '2026-02-19 23:38:01');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_file_types_stats`
--

CREATE TABLE `cloud_file_types_stats` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `file_extension` varchar(20) DEFAULT NULL,
  `files_count` int(11) DEFAULT NULL,
  `total_size_mb` decimal(10,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `recorded_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_file_types_stats`
--

INSERT INTO `cloud_file_types_stats` (`id`, `server_id`, `file_extension`, `files_count`, `total_size_mb`, `percentage`, `recorded_at`, `created_at`) VALUES
(1, 1, 'pdf', 1250, 2560.00, 15.50, '2026-02-20', '2026-02-20 01:22:55'),
(2, 1, 'jpg', 3450, 5120.00, 31.00, '2026-02-20', '2026-02-20 01:22:55'),
(3, 1, 'png', 2340, 3840.00, 23.30, '2026-02-20', '2026-02-20 01:22:55'),
(4, 1, 'mp4', 120, 10240.00, 62.00, '2026-02-20', '2026-02-20 01:22:55'),
(5, 1, 'docx', 890, 1280.00, 7.80, '2026-02-20', '2026-02-20 01:22:55'),
(6, 1, 'zip', 450, 5120.00, 31.00, '2026-02-20', '2026-02-20 01:22:55');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_projects`
--

CREATE TABLE `cloud_projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `project_type` enum('website','application','database','storage','email') DEFAULT 'website',
  `framework` varchar(100) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `git_repo` varchar(500) DEFAULT NULL,
  `deploy_path` varchar(500) DEFAULT NULL,
  `env_file` text DEFAULT NULL,
  `status` enum('active','inactive','suspended','maintenance','deploying') DEFAULT 'active',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `backup_enabled` tinyint(1) DEFAULT 1,
  `monitoring_enabled` tinyint(1) DEFAULT 1,
  `client_name` varchar(255) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_projects`
--

INSERT INTO `cloud_projects` (`id`, `project_name`, `project_code`, `domain`, `server_id`, `project_type`, `framework`, `language`, `git_repo`, `deploy_path`, `env_file`, `status`, `priority`, `backup_enabled`, `monitoring_enabled`, `client_name`, `client_email`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'موقع التجارة الإلكترونية', 'PRJ-ECOMM-001', 'shop.example.com', 1, 'website', 'Laravel', 'PHP', 'git@github.com:company/ecommerce.git', '/var/www/ecommerce', NULL, 'active', 'high', 1, 1, 'شركة التجارة', 'client1@example.com', 'متجر رئيسي', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(2, 'المدونة', 'PRJ-BLOG-001', 'blog.example.com', 1, 'website', 'WordPress', 'PHP', 'git@github.com:company/blog.git', '/var/www/blog', NULL, 'active', 'medium', 1, 1, 'شركة المحتوى', 'client2@example.com', 'مدونة الشركة', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(3, 'تطبيق API', 'PRJ-API-001', 'api.example.com', 1, 'application', 'Express', 'Node.js', 'git@github.com:company/api.git', '/var/www/api', NULL, 'active', 'critical', 1, 1, 'التطبيقات الذكية', 'client3@example.com', 'API رئيسي', 3, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(4, 'قاعدة بيانات العملاء', 'PRJ-DB-001', NULL, 2, 'database', NULL, 'MySQL', NULL, NULL, NULL, 'active', 'high', 1, 1, 'قسم تقنية', 'it@example.com', 'بيانات العملاء', 3, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(5, 'مستودع الملفات', 'PRJ-STOR-001', 'files.example.com', 4, 'storage', 'Nextcloud', 'PHP', 'git@github.com:company/nextcloud.git', '/var/www/nextcloud', NULL, 'active', 'medium', 1, 1, 'الشركة', 'admin@example.com', 'مستودع داخلي', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_reports`
--

CREATE TABLE `cloud_reports` (
  `id` int(11) NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('usage','performance','security','backup','cost','audit','custom') DEFAULT 'usage',
  `period` enum('daily','weekly','monthly','quarterly','yearly','custom') DEFAULT 'monthly',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `format` enum('pdf','excel','html','csv','json') DEFAULT 'pdf',
  `summary` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_reports`
--

INSERT INTO `cloud_reports` (`id`, `report_code`, `report_name`, `report_type`, `period`, `date_from`, `date_to`, `file_path`, `file_size`, `format`, `summary`, `created_by`, `created_at`) VALUES
(2, 'RPT-2024-001', 'تقرير استخدام التخزين - يناير 2024', 'usage', 'monthly', '2024-01-01', '2024-01-31', NULL, NULL, 'pdf', 'تقرير شامل عن استخدام التخزين لشهر يناير، يشمل إحصائيات الملفات والمجلدات وحجم التخزين المستخدم', 1, '2024-02-01 07:30:00'),
(3, 'RPT-2024-002', 'تقرير أداء الخوادم - يناير 2024', 'performance', 'monthly', '2024-01-01', '2024-01-31', NULL, NULL, 'pdf', 'تحليل أداء الخوادم الرئيسية واستخدام الموارد خلال شهر يناير', 2, '2024-02-02 11:15:00'),
(4, 'RPT-2024-003', 'تقرير أمني - يناير 2024', 'security', 'monthly', '2024-01-01', '2024-01-31', NULL, NULL, 'pdf', 'تقرير عن التحديثات الأمنية والثغرات المكتشفة خلال شهر يناير', 1, '2024-02-03 06:45:00'),
(5, 'RPT-2024-004', 'تقرير استخدام التخزين - فبراير 2024', 'usage', 'monthly', '2024-02-01', '2024-02-29', NULL, NULL, 'pdf', 'تقرير استخدام التخزين لشهر فبراير مع تحليل النمو', 3, '2024-03-01 08:20:00'),
(6, 'RPT-2024-005', 'تقرير أداء الخوادم - فبراير 2024', 'performance', 'monthly', '2024-02-01', '2024-02-29', NULL, NULL, 'pdf', 'أداء الخوادم واستجابة الخدمات خلال شهر فبراير', 2, '2024-03-02 13:30:00'),
(7, 'RPT-2024-006', 'تقرير أسبوعي - الأسبوع الأول مارس 2024', 'usage', 'weekly', '2024-03-01', '2024-03-07', NULL, NULL, 'pdf', 'تقرير أسبوعي عن استخدام التخزين للأسبوع الأول من مارس', 1, '2024-03-08 05:00:00'),
(8, 'RPT-2024-007', 'تقرير أسبوعي - الأسبوع الثاني مارس 2024', 'performance', 'weekly', '2024-03-08', '2024-03-14', NULL, NULL, 'excel', 'تقرير أداء الخوادم للأسبوع الثاني', 2, '2024-03-15 10:45:00'),
(9, 'RPT-2024-008', 'تقرير أسبوعي - الأسبوع الثالث مارس 2024', 'security', 'weekly', '2024-03-15', '2024-03-21', NULL, NULL, 'pdf', 'تقرير أمني أسبوعي عن التحديثات والثغرات', 3, '2024-03-22 07:15:00'),
(10, 'RPT-2024-009', 'تقرير أسبوعي - الأسبوع الرابع مارس 2024', 'backup', 'weekly', '2024-03-22', '2024-03-28', NULL, NULL, 'pdf', 'تقرير عن حالة النسخ الاحتياطية الأسبوعية', 1, '2024-03-29 09:30:00'),
(11, 'RPT-2024-010', 'تقرير يومي - 2024-03-15', 'usage', 'daily', '2024-03-15', '2024-03-15', NULL, NULL, 'pdf', 'تقرير يومي مفصل عن استخدام التخزين', 2, '2024-03-15 20:59:00'),
(12, 'RPT-2024-011', 'تقرير يومي - 2024-03-16', 'performance', 'daily', '2024-03-16', '2024-03-16', NULL, NULL, 'pdf', 'تقرير أداء الخوادم ليوم 16 مارس', 1, '2024-03-16 20:59:00'),
(13, 'RPT-2024-012', 'تقرير يومي - 2024-03-17', 'security', 'daily', '2024-03-17', '2024-03-17', NULL, NULL, 'pdf', 'تقرير أمني يومي', 3, '2024-03-17 20:59:00'),
(14, 'RPT-2024-013', 'تقرير الربع الأول 2024', 'usage', 'quarterly', '2024-01-01', '2024-03-31', NULL, NULL, 'pdf', 'تقرير شامل للربع الأول من العام', 1, '2024-04-01 06:00:00'),
(15, 'RPT-2024-014', 'تقرير أداء الربع الأول', 'performance', 'quarterly', '2024-01-01', '2024-03-31', NULL, NULL, 'excel', 'تحليل أداء الخوادم خلال الربع الأول', 2, '2024-04-02 11:30:00'),
(16, 'RPT-2024-015', 'تقرير أمني - الربع الأول', 'security', 'quarterly', '2024-01-01', '2024-03-31', NULL, NULL, 'pdf', 'تقرير أمني ربع سنوي', 3, '2024-04-03 08:15:00'),
(17, 'RPT-2024-016', 'تقرير سنوي 2023', 'audit', 'yearly', '2023-01-01', '2023-12-31', NULL, NULL, 'pdf', 'تقرير تدقيق سنوي شامل', 1, '2024-01-15 07:00:00'),
(18, 'RPT-2024-017', 'تقرير استخدام 2023', 'usage', 'yearly', '2023-01-01', '2023-12-31', NULL, NULL, 'pdf', 'تحليل استخدام التخزين لعام 2023', 2, '2024-01-16 10:20:00'),
(19, 'RPT-2024-018', 'تقرير أمني 2023', 'security', 'yearly', '2023-01-01', '2023-12-31', NULL, NULL, 'pdf', 'تقرير أمني سنوي', 3, '2024-01-17 12:45:00'),
(20, 'RPT-2024-019', 'تقرير خاص - مشروع التجارة الإلكترونية', 'custom', 'custom', '2024-02-01', '2024-02-15', NULL, NULL, 'pdf', 'تقرير مخصص عن مشروع التجارة الإلكترونية', 2, '2024-02-16 06:30:00'),
(21, 'RPT-2024-020', 'تحليل قاعدة البيانات', 'audit', 'custom', '2024-03-01', '2024-03-10', NULL, NULL, 'excel', 'تحليل أداء قاعدة البيانات', 1, '2024-03-11 13:00:00'),
(22, 'RPT-2026-021', 'تقرير استخدام 2026-02-20', 'usage', 'daily', '2026-02-20', '2026-02-20', NULL, NULL, 'pdf', 'إجمالي الملفات: 26 | حجم التخزين: 0.01 GB', 1, '2026-02-20 01:49:29'),
(23, 'RPT-2026-022', 'تقرير استخدام 2026-02-21', 'usage', 'daily', '2026-02-21', '2026-02-21', NULL, NULL, 'pdf', 'إجمالي الملفات: 26 | حجم التخزين: 0.01 GB', 1, '2026-02-20 22:37:24');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_security_updates`
--

CREATE TABLE `cloud_security_updates` (
  `id` int(11) NOT NULL,
  `update_code` varchar(50) NOT NULL,
  `update_name` varchar(255) NOT NULL,
  `package_name` varchar(255) DEFAULT NULL,
  `current_version` varchar(50) DEFAULT NULL,
  `available_version` varchar(50) DEFAULT NULL,
  `severity` enum('critical','high','medium','low') DEFAULT 'medium',
  `description` text DEFAULT NULL,
  `cve_id` varchar(50) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `status` enum('pending','applied','scheduled','failed','skipped') DEFAULT 'pending',
  `applied_at` datetime DEFAULT NULL,
  `scheduled_for` datetime DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_security_updates`
--

INSERT INTO `cloud_security_updates` (`id`, `update_code`, `update_name`, `package_name`, `current_version`, `available_version`, `severity`, `description`, `cve_id`, `server_id`, `project_id`, `status`, `applied_at`, `scheduled_for`, `applied_by`, `created_at`) VALUES
(1, 'SEC-2024-001', 'تحديث OpenSSL', 'openssl', '1.1.1t', '3.0.8', 'critical', 'ثغرة أمنية حرجة في OpenSSL تسمح بتنفيذ تعليمات برمجية عن بعد', 'CVE-2024-1234', 1, NULL, 'skipped', NULL, NULL, NULL, '2026-02-20 01:18:07'),
(2, 'SEC-2024-002', 'تحديث Apache', 'apache2', '2.4.52', '2.4.58', 'high', 'ثغرة في Apache تسمح بتجاوز المصادقة', 'CVE-2024-5678', 1, NULL, 'applied', '2026-02-20 04:18:48', NULL, 1, '2026-02-20 01:18:07'),
(3, 'SEC-2024-003', 'تحديث MySQL', 'mysql-server', '8.0.32', '8.0.36', 'medium', 'تحسينات أمنية وأداء لقاعدة البيانات', NULL, 2, NULL, 'applied', NULL, NULL, NULL, '2026-02-15 01:18:07'),
(4, 'SEC-2024-004', 'تحديث PHP', 'php8.1', '8.1.20', '8.1.27', 'high', 'إصلاح ثغرات أمنية متعددة في PHP', 'CVE-2024-9012', 1, NULL, 'scheduled', NULL, NULL, NULL, '2026-02-18 01:18:07');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_servers`
--

CREATE TABLE `cloud_servers` (
  `id` int(11) NOT NULL,
  `server_name` varchar(255) NOT NULL,
  `server_code` varchar(50) NOT NULL,
  `server_type` enum('web','database','backup','storage','mail','dns') DEFAULT 'web',
  `ip_address` varchar(45) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT 1,
  `ram_gb` int(11) DEFAULT 4,
  `storage_gb` int(11) DEFAULT 100,
  `storage_used_gb` int(11) DEFAULT 0,
  `status` enum('online','offline','maintenance','warning','provisioning') DEFAULT 'online',
  `location` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `monthly_cost` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `last_reboot` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_servers`
--

INSERT INTO `cloud_servers` (`id`, `server_name`, `server_code`, `server_type`, `ip_address`, `hostname`, `os`, `cpu_cores`, `ram_gb`, `storage_gb`, `storage_used_gb`, `status`, `location`, `provider`, `monthly_cost`, `purchase_date`, `last_reboot`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'سيرفر الويب الرئيسي', 'SRV-WEB-001', 'web', '192.168.1.100', 'web01.cloud.local', 'Ubuntu 22.04', 8, 16, 500, 325, 'online', 'الرياض', 'Local DC', 1200.00, '2023-01-15', '2024-01-15 03:00:00', 'يستضيف مواقع الويب الرئيسية', 1, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(2, 'سيرفر قاعدة البيانات', 'SRV-DB-001', 'database', '192.168.1.101', 'db01.cloud.local', 'Ubuntu 22.04', 16, 32, 1000, 450, 'online', 'الرياض', 'Local DC', 2500.00, '2023-02-01', '2024-01-14 02:30:00', 'قواعد بيانات MySQL و PostgreSQL', 1, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(3, 'سيرفر النسخ الاحتياطي', 'SRV-BAK-001', 'backup', '192.168.1.102', 'backup01.cloud.local', 'Ubuntu 22.04', 4, 8, 2000, 850, 'warning', 'جدة', 'Cloud Provider', 800.00, '2023-03-10', '2024-01-10 04:00:00', 'يحتاج تنظيف', 2, '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(4, 'سيرفر التخزين', 'SRV-STR-001', 'storage', '192.168.1.103', 'storage01.cloud.local', 'CentOS 8', 8, 16, 5000, 3250, 'online', 'الرياض', 'Local DC', 3500.00, '2023-04-20', '2026-02-20 03:46:07', 'تخزين الملفات والوسائط', 2, '2026-02-19 22:11:06', '2026-02-20 00:46:07'),
(5, 'سيرفر البريد', 'SRV-MAIL-001', 'mail', '192.168.1.104', 'mail01.cloud.local', 'Debian 11', 4, 8, 200, 95, 'maintenance', 'جدة', 'Cloud Provider', 600.00, '2023-05-05', '2024-01-12 05:00:00', 'تحت الصيانة', 3, '2026-02-19 22:11:06', '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_server_services`
--

CREATE TABLE `cloud_server_services` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `status` enum('running','stopped','failed','starting','stopping','restarting') DEFAULT 'running',
  `port` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `cpu_usage` decimal(5,2) DEFAULT NULL,
  `memory_usage_mb` int(11) DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `config_file` varchar(500) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_server_services`
--

INSERT INTO `cloud_server_services` (`id`, `server_id`, `service_name`, `display_name`, `status`, `port`, `pid`, `cpu_usage`, `memory_usage_mb`, `started_at`, `config_file`, `version`, `created_at`, `updated_at`) VALUES
(1, 1, 'nginx', 'Nginx Web Server', 'running', 80, 1234, 2.50, 128, '2024-01-15 03:00:00', NULL, '1.24.0', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(2, 1, 'php8.1-fpm', 'PHP-FPM', 'running', 9000, 5678, 5.20, 256, '2024-01-15 03:00:00', NULL, '8.1.27', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(3, 2, 'mysql', 'MySQL Database', 'running', 3306, 9012, 15.80, 1024, '2024-01-15 03:00:00', NULL, '8.0.35', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(4, 2, 'redis', 'Redis Cache', 'running', 6379, 3456, 1.20, 64, '2024-01-15 03:00:00', NULL, '7.2.4', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(5, 3, 'rsync', 'Rsync Backup', 'running', 873, 7890, 0.50, 32, '2024-01-15 03:00:00', NULL, '3.2.7', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(6, 4, 'nfs', 'NFS Server', 'running', 2049, 2345, 1.80, 96, '2024-01-15 03:00:00', NULL, '2.6.2', '2026-02-19 22:11:06', '2026-02-19 22:11:06'),
(7, 5, 'postfix', 'Postfix Mail', 'stopped', 25, NULL, 0.00, 0, '2024-01-12 05:00:00', NULL, '3.7.6', '2026-02-19 22:11:06', '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_server_stats`
--

CREATE TABLE `cloud_server_stats` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `cpu_usage_percent` decimal(5,2) DEFAULT NULL,
  `ram_usage_percent` decimal(5,2) DEFAULT NULL,
  `ram_used_mb` int(11) DEFAULT NULL,
  `ram_total_mb` int(11) DEFAULT NULL,
  `disk_io_read_mb` decimal(10,2) DEFAULT NULL,
  `disk_io_write_mb` decimal(10,2) DEFAULT NULL,
  `network_in_mb` decimal(10,2) DEFAULT NULL,
  `network_out_mb` decimal(10,2) DEFAULT NULL,
  `uptime_seconds` int(11) DEFAULT NULL,
  `load_average_1min` decimal(5,2) DEFAULT NULL,
  `load_average_5min` decimal(5,2) DEFAULT NULL,
  `load_average_15min` decimal(5,2) DEFAULT NULL,
  `process_count` int(11) DEFAULT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_settings`
--

CREATE TABLE `cloud_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json','array') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_settings`
--

INSERT INTO `cloud_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'نظام الاستضافة السحابي', 'text', 'اسم الموقع', 1, '2026-02-19 22:11:06'),
(2, 'default_backup_retention', '30', 'number', 'فترة الاحتفاظ الافتراضية للنسخ الاحتياطي (يوم)', 1, '2026-02-19 22:11:06'),
(3, 'backup_enabled', 'true', 'boolean', 'تفعيل النسخ الاحتياطي التلقائي', 1, '2026-02-19 22:11:06'),
(4, 'monitoring_enabled', 'true', 'boolean', 'تفعيل المراقبة', 1, '2026-02-19 22:11:06'),
(5, 'alert_email', 'alerts@example.com', 'text', 'البريد الإلكتروني للتنبيهات', 1, '2026-02-19 22:11:06'),
(6, 'storage_threshold_warning', '80', 'number', 'حد التحذير للتخزين (%)', 1, '2026-02-19 22:11:06'),
(7, 'storage_threshold_critical', '90', 'number', 'حد الخطر للتخزين (%)', 1, '2026-02-19 22:11:06'),
(8, 'auto_cleanup_enabled', 'true', 'boolean', 'تفعيل التنظيف التلقائي', 1, '2026-02-19 22:11:06'),
(9, 'max_upload_size', '1024', 'number', 'الحد الأقصى لحجم الرفع (MB)', 1, '2026-02-19 22:11:06'),
(10, 'allowed_file_types', '[\"jpg\",\"png\",\"pdf\",\"docx\",\"txt\",\"php\",\"html\",\"css\",\"js\"]', 'json', 'أنواع الملفات المسموح بها', 1, '2026-02-19 22:11:06');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_storage_alerts`
--

CREATE TABLE `cloud_storage_alerts` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `alert_type` enum('critical','warning','info','success') DEFAULT 'info',
  `severity` enum('high','medium','low') DEFAULT 'medium',
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `threshold` int(11) DEFAULT NULL,
  `current_value` int(11) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_storage_alerts`
--

INSERT INTO `cloud_storage_alerts` (`id`, `server_id`, `alert_type`, `severity`, `title`, `message`, `threshold`, `current_value`, `is_resolved`, `resolved_at`, `resolved_by`, `created_at`) VALUES
(1, 3, 'warning', 'medium', 'مساحة تخزين منخفضة', 'سيرفر النسخ الاحتياطي وصل لـ 85%', 80, 85, 1, '2026-02-20 03:55:59', NULL, '2024-01-15 05:30:00'),
(2, 2, 'info', 'low', 'نمو سريع في قاعدة البيانات', 'زيادة 10% في حجم قاعدة البيانات هذا الأسبوع', NULL, NULL, 1, '2026-02-20 03:55:57', NULL, '2024-01-14 11:20:00'),
(3, 1, 'success', 'low', 'تم تنظيف السيرفر', 'تم تحرير 25 جيجابايت', NULL, NULL, 1, NULL, NULL, '2024-01-13 08:00:00'),
(4, 4, 'critical', 'high', 'مساحة حرجة', 'السيرفر الرئيسي وصل لـ 90%', 90, 92, 1, NULL, NULL, '2024-01-12 06:15:00'),
(5, 5, 'warning', 'medium', 'خدمة البريد متوقفة', 'سيرفر البريد يحتاج إعادة تشغيل', NULL, NULL, 1, NULL, NULL, '2024-01-11 13:45:00'),
(6, 1, 'warning', 'medium', 'مساحة تخزين منخفضة', 'سيرفر الويب الرئيسي وصل لـ 85% من سعة التخزين', 80, 85, 0, NULL, NULL, '2026-02-20 01:22:55'),
(7, 2, 'critical', 'high', 'مساحة حرجة', 'سيرفر قاعدة البيانات وصل لـ 95% من سعة التخزين', 90, 95, 0, NULL, NULL, '2026-02-20 01:22:55'),
(8, 3, 'info', 'low', 'نمو سريع', 'زيادة كبيرة في حجم الملفات على سيرفر النسخ الاحتياطي', NULL, NULL, 0, NULL, NULL, '2026-02-19 01:22:55');

-- --------------------------------------------------------

--
-- بنية الجدول `cloud_storage_monitoring`
--

CREATE TABLE `cloud_storage_monitoring` (
  `id` int(11) NOT NULL,
  `server_id` int(11) NOT NULL,
  `total_space_gb` decimal(10,2) DEFAULT NULL,
  `used_space_gb` decimal(10,2) DEFAULT NULL,
  `free_space_gb` decimal(10,2) DEFAULT NULL,
  `used_percent` decimal(5,2) DEFAULT NULL,
  `files_count` int(11) DEFAULT NULL,
  `folders_count` int(11) DEFAULT NULL,
  `inodes_used` int(11) DEFAULT NULL,
  `inodes_total` int(11) DEFAULT NULL,
  `daily_growth_mb` decimal(10,2) DEFAULT NULL,
  `weekly_growth_mb` decimal(10,2) DEFAULT NULL,
  `monthly_growth_mb` decimal(10,2) DEFAULT NULL,
  `check_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `cloud_storage_monitoring`
--

INSERT INTO `cloud_storage_monitoring` (`id`, `server_id`, `total_space_gb`, `used_space_gb`, `free_space_gb`, `used_percent`, `files_count`, `folders_count`, `inodes_used`, `inodes_total`, `daily_growth_mb`, `weekly_growth_mb`, `monthly_growth_mb`, `check_time`, `created_at`) VALUES
(1, 1, 500.00, 325.00, 175.00, 65.00, 12450, 345, NULL, NULL, 256.00, NULL, NULL, '2026-02-20 01:11:06', '2026-02-19 22:11:06'),
(2, 2, 1000.00, 450.00, 550.00, 45.00, 230, 45, NULL, NULL, 124.00, NULL, NULL, '2026-02-20 01:11:06', '2026-02-19 22:11:06'),
(3, 3, 2000.00, 850.00, 1150.00, 42.50, 15670, 890, NULL, NULL, 512.00, NULL, NULL, '2026-02-20 01:11:06', '2026-02-19 22:11:06'),
(4, 4, 5000.00, 3250.00, 1750.00, 65.00, 45670, 2340, NULL, NULL, 1024.00, NULL, NULL, '2026-02-20 01:11:06', '2026-02-19 22:11:06'),
(5, 5, 200.00, 95.00, 105.00, 47.50, 2340, 156, NULL, NULL, 45.00, NULL, NULL, '2026-02-20 01:11:06', '2026-02-19 22:11:06'),
(6, 1, 500.00, 325.00, NULL, 65.00, 12450, 345, NULL, NULL, 256.00, NULL, NULL, '2026-02-14 04:22:55', '2026-02-20 01:22:55'),
(7, 1, 500.00, 328.00, NULL, 65.60, 12500, 346, NULL, NULL, 245.00, NULL, NULL, '2026-02-15 04:22:55', '2026-02-20 01:22:55'),
(8, 1, 500.00, 332.00, NULL, 66.40, 12580, 347, NULL, NULL, 278.00, NULL, NULL, '2026-02-16 04:22:55', '2026-02-20 01:22:55'),
(9, 1, 500.00, 335.00, NULL, 67.00, 12650, 348, NULL, NULL, 290.00, NULL, NULL, '2026-02-17 04:22:55', '2026-02-20 01:22:55'),
(10, 1, 500.00, 338.00, NULL, 67.60, 12720, 349, NULL, NULL, 265.00, NULL, NULL, '2026-02-18 04:22:55', '2026-02-20 01:22:55'),
(11, 1, 500.00, 342.00, NULL, 68.40, 12800, 350, NULL, NULL, 310.00, NULL, NULL, '2026-02-19 04:22:55', '2026-02-20 01:22:55'),
(12, 1, 500.00, 345.00, NULL, 69.00, 12850, 351, NULL, NULL, 280.00, NULL, NULL, '2026-02-20 04:22:55', '2026-02-20 01:22:55');

-- --------------------------------------------------------

--
-- بنية الجدول `compliance_standards`
--

CREATE TABLE `compliance_standards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `compliance_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('compliant','in-progress','non-compliant') DEFAULT 'in-progress',
  `last_audit` date DEFAULT NULL,
  `next_audit` date DEFAULT NULL,
  `responsible_unit` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `compliance_standards`
--

INSERT INTO `compliance_standards` (`id`, `name`, `code`, `description`, `compliance_rate`, `status`, `last_audit`, `next_audit`, `responsible_unit`, `created_at`, `updated_at`) VALUES
(1, 'ISO 27001 - أمن المعلومات', 'ISO27001', 'معيار أمن المعلومات الدولي', 98.00, 'compliant', '2024-01-15', '2024-04-15', 3, '2026-02-16 23:30:45', '2026-02-16 23:30:45'),
(2, 'PCI DSS - معاملات الدفع', 'PCI-DSS', 'معيار أمن بطاقات الدفع', 85.00, 'in-progress', '2024-01-20', '2024-04-20', 3, '2026-02-16 23:30:45', '2026-02-16 23:30:45'),
(3, 'GDPR - حماية البيانات', 'GDPR', 'اللائحة العامة لحماية البيانات', 96.00, 'compliant', '2024-01-10', '2024-04-10', 1, '2026-02-16 23:30:45', '2026-02-16 23:30:45'),
(4, 'SOX - الرقابة المالية', 'SOX', 'قانون ساربينز أوكسلي', 82.00, 'in-progress', '2024-01-05', '2024-04-05', 2, '2026-02-16 23:30:45', '2026-02-16 23:30:45');

-- --------------------------------------------------------

--
-- بنية الجدول `custom_scripts`
--

CREATE TABLE `custom_scripts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `script_type` enum('bash','python','powershell','other') NOT NULL,
  `script_content` text DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `author_id` int(11) DEFAULT NULL,
  `last_run` date DEFAULT NULL,
  `run_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','deprecated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `custom_scripts`
--

INSERT INTO `custom_scripts` (`id`, `name`, `description`, `script_type`, `script_content`, `parameters`, `author_id`, `last_run`, `run_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 'scan_websites.sh', 'سكربت لفحص مجموعة من المواقع', 'bash', NULL, NULL, 1, '2024-01-27', 15, 'active', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(2, 'report_generator.py', 'إنشاء تقارير تلقائية من نتائج الفحص', 'python', NULL, NULL, 1, '2024-01-28', 8, 'active', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(3, 'vuln_parser.py', 'تحليل نتائج الفحص وتصنيف الثغرات', 'python', NULL, NULL, 2, '2024-01-26', 5, 'active', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(4, 'network_scan.ps1', 'سكربت لفحص الشبكة الداخلية', 'powershell', NULL, NULL, 3, '2024-01-25', 3, 'active', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(5, 'auto_exploit.rb', 'سكربت استغلال تلقائي', 'other', NULL, NULL, 1, '2024-01-24', 2, 'deprecated', '2026-02-18 00:55:52', '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `daily_reports`
--

CREATE TABLE `daily_reports` (
  `id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `report_type` enum('security','performance','network','incident','compliance') NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `statistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statistics`)),
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `daily_reports`
--

INSERT INTO `daily_reports` (`id`, `report_date`, `report_type`, `title`, `summary`, `statistics`, `file_path`, `file_size`, `generated_by`, `status`, `created_at`) VALUES
(1, '2026-02-16', 'security', 'تقرير الأمان اليومي', 'تقرير شامل لأحداث الأمان لليوم', '{\"total_alerts\":24,\"critical\":5,\"warning\":12,\"info\":8,\"threats\":8}', NULL, NULL, 1, 'published', '2026-02-16 04:07:22'),
(2, '2026-02-15', 'security', 'تقرير الأمان ليوم أمس', 'تقرير أحداث الأمان ليوم أمس', '{\"total_alerts\":18,\"critical\":3,\"warning\":9,\"info\":6,\"threats\":5}', NULL, NULL, 1, 'published', '2026-02-16 04:07:22'),
(3, '2026-02-14', 'performance', 'تقرير أداء الخوادم', 'تحليل أداء جميع الخوادم', '{\"avg_cpu\":48.5,\"avg_memory\":52.3,\"avg_storage\":46.2,\"uptime\":99.98}', NULL, NULL, 2, 'published', '2026-02-16 04:07:22');

-- --------------------------------------------------------

--
-- بنية الجدول `documentation_activity_log`
--

CREATE TABLE `documentation_activity_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('create','update','delete','view','review','approve','reject','archive','download','upload','comment','share','export','import') NOT NULL,
  `target_type` enum('project','document','template','report','review','comment','file') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `documentation_activity_log`
--

INSERT INTO `documentation_activity_log` (`id`, `user_id`, `activity_type`, `target_type`, `target_id`, `target_name`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'create', 'document', 1, 'متطلبات نظام الاستضافة', 'إنشاء مستند متطلبات نظام الاستضافة', '192.168.1.10', NULL, NULL, '2026-01-04 22:19:47'),
(2, 1, 'update', 'document', 1, 'متطلبات نظام الاستضافة', 'تحديث متطلبات نظام الاستضافة', '192.168.1.10', NULL, NULL, '2026-01-05 22:19:47'),
(3, 3, 'create', 'document', 3, 'تقرير الاختبارات الأمنية', 'إنشاء تقرير الاختبارات الأمنية', '192.168.1.12', NULL, NULL, '2026-01-06 22:19:47'),
(4, 2, 'review', 'document', 1, 'متطلبات نظام الاستضافة', 'مراجعة متطلبات النظام', '192.168.1.11', NULL, NULL, '2026-01-07 22:19:47'),
(5, 5, 'approve', 'document', 1, 'متطلبات نظام الاستضافة', 'الموافقة على متطلبات النظام', '192.168.1.15', NULL, NULL, '2026-01-08 22:19:47'),
(6, 1, 'download', 'document', 1, 'متطلبات نظام الاستضافة', 'تحميل مستند المتطلبات', '192.168.1.10', NULL, NULL, '2026-01-09 22:19:47'),
(7, 2, 'comment', 'document', 1, 'متطلبات نظام الاستضافة', 'إضافة تعليق على المستند', '192.168.1.11', NULL, NULL, '2026-01-10 22:19:47'),
(8, 3, 'update', 'document', 3, 'تقرير الاختبارات الأمنية', 'تحديث تقرير الاختبارات', '192.168.1.12', NULL, NULL, '2026-01-11 22:19:47'),
(9, 4, 'create', 'document', 13, 'متطلبات المنصة', 'إنشاء مستند متطلبات المنصة', '192.168.1.14', NULL, NULL, '2026-01-12 22:19:47'),
(10, 2, 'review', 'document', 3, 'تقرير الاختبارات الأمنية', 'مراجعة التقرير الأمني', '192.168.1.11', NULL, NULL, '2026-01-13 22:19:47'),
(11, 1, 'create', 'project', 1, 'نظام استضافة المواقع', 'إنشاء مشروع نظام الاستضافة', '192.168.1.10', NULL, NULL, '2026-01-14 22:19:47'),
(12, 5, 'create', 'project', 3, 'نظام الحماية الأمني', 'إنشاء مشروع نظام الحماية', '192.168.1.15', NULL, NULL, '2026-01-15 22:19:47'),
(13, 2, 'view', 'document', 4, 'دليل مستخدم نظام الاستضافة', 'عرض دليل المستخدم', '192.168.1.11', NULL, NULL, '2026-01-16 22:19:47'),
(14, 3, 'upload', 'file', NULL, 'تقرير أمني.pdf', 'رفع ملف تقرير أمني', '192.168.1.12', NULL, NULL, '2026-01-17 22:19:47'),
(15, 1, 'export', 'document', 2, 'دليل تثبيت نظام الاستضافة', 'تصدير دليل التثبيت', '192.168.1.10', NULL, NULL, '2026-01-18 22:19:47'),
(16, 4, 'comment', 'document', 13, 'متطلبات المنصة', 'إضافة تعليق على المتطلبات', '192.168.1.14', NULL, NULL, '2026-01-19 22:19:47'),
(17, 5, 'update', 'document', 9, 'هيكلية النظام الأمني', 'تحديث هيكلية النظام', '192.168.1.15', NULL, NULL, '2026-01-20 22:19:47'),
(18, 2, 'approve', 'document', 4, 'دليل مستخدم نظام الاستضافة', 'الموافقة على دليل المستخدم', '192.168.1.11', NULL, NULL, '2026-01-21 22:19:47'),
(19, 1, 'create', 'template', 1, 'قالب متطلبات النظام', 'إنشاء قالب متطلبات النظام', '192.168.1.10', NULL, NULL, '2026-01-22 22:19:47'),
(20, 3, 'download', 'document', 8, 'تقرير أمني - البنية التحتية', 'تحميل التقرير الأمني', '192.168.1.12', NULL, NULL, '2026-01-23 22:19:47'),
(21, 5, 'create', 'report', 2, 'تقرير الأمن السيبراني', 'إنشاء تقرير الأمن السيبراني', '192.168.1.15', NULL, NULL, '2026-01-24 22:19:47'),
(22, 4, 'review', 'document', 13, 'متطلبات المنصة', 'مراجعة متطلبات المنصة', '192.168.1.14', NULL, NULL, '2026-01-25 22:19:47'),
(23, 2, 'share', 'document', 5, 'دليل المشرف', 'مشاركة دليل المشرف مع الفريق', '192.168.1.11', NULL, NULL, '2026-01-26 22:19:47'),
(24, 1, 'update', 'template', 1, 'قالب متطلبات النظام', 'تحديث قالب المتطلبات', '192.168.1.10', NULL, NULL, '2026-01-27 22:19:47'),
(25, 3, 'comment', 'document', 8, 'تقرير أمني - البنية التحتية', 'إضافة تعليق على التقرير', '192.168.1.12', NULL, NULL, '2026-01-28 22:19:47'),
(26, 5, 'approve', 'report', 2, 'تقرير الأمن السيبراني', 'الموافقة على التقرير', '192.168.1.15', NULL, NULL, '2026-01-29 22:19:47'),
(27, 2, 'create', 'document', 19, 'تقرير التقدم - الربع الأول', 'إنشاء تقرير التقدم', '192.168.1.11', NULL, NULL, '2026-01-30 22:19:47'),
(28, 4, 'update', 'project', 4, 'منصة التجارة الإلكترونية', 'تحديث معلومات المشروع', '192.168.1.14', NULL, NULL, '2026-01-31 22:19:47'),
(29, 1, 'view', 'template', 2, 'قالب تقرير أمني', 'عرض قالب التقرير الأمني', '192.168.1.10', NULL, NULL, '2026-02-01 22:19:47'),
(30, 3, 'export', 'document', 6, 'توثيق API - REST', 'تصدير توثيق API', '192.168.1.12', NULL, NULL, '2026-02-02 22:19:47'),
(31, 5, 'delete', 'comment', NULL, NULL, 'حذف تعليق غير مناسب', '192.168.1.15', NULL, NULL, '2026-02-03 22:19:47'),
(32, 2, 'review', 'document', 19, 'تقرير التقدم - الربع الأول', 'مراجعة تقرير التقدم', '192.168.1.11', NULL, NULL, '2026-02-04 22:19:47'),
(33, 1, 'upload', 'file', NULL, 'مخطط النظام.png', 'رفع مخطط توضيحي', '192.168.1.10', NULL, NULL, '2026-02-05 22:19:47'),
(34, 4, 'approve', 'document', 13, 'متطلبات المنصة', 'الموافقة على متطلبات المنصة', '192.168.1.14', NULL, NULL, '2026-02-06 22:19:47'),
(35, 3, 'create', 'report', 9, 'تقرير اختبار الاختراق', 'إنشاء تقرير اختبار الاختراق', '192.168.1.12', NULL, NULL, '2026-02-07 22:19:47'),
(36, 5, 'update', 'template', 11, 'قالب تقرير الامتثال', 'تحديث قالب الامتثال', '192.168.1.15', NULL, NULL, '2026-02-08 22:19:47'),
(37, 2, 'comment', 'document', 20, 'تقرير الأداء الشهري', 'إضافة تعليق على التقرير', '192.168.1.11', NULL, NULL, '2026-02-09 22:19:47'),
(38, 1, 'download', 'template', 3, 'قالب دليل المستخدم', 'تحميل قالب دليل المستخدم', '192.168.1.10', NULL, NULL, '2026-02-10 22:19:47'),
(39, 4, 'create', 'document', 18, 'دليل استكشاف الأخطاء', 'إنشاء دليل استكشاف الأخطاء', '192.168.1.14', NULL, NULL, '2026-02-11 22:19:47'),
(40, 3, 'archive', 'document', 11, 'خطة اختبار الاختراق', 'أرشفة خطة الاختبار', '192.168.1.12', NULL, NULL, '2026-02-12 22:19:47'),
(41, 5, 'review', 'report', 9, 'تقرير اختبار الاختراق', 'مراجعة تقرير الاختراق', '192.168.1.15', NULL, NULL, '2026-02-13 22:19:47'),
(42, 2, 'approve', 'document', 20, 'تقرير الأداء الشهري', 'الموافقة على التقرير بعد التحديث', '192.168.1.11', NULL, NULL, '2026-02-14 22:19:47'),
(43, 1, 'import', 'template', 5, 'قالب هيكلية النظام', 'استيراد قالب هيكلية', '192.168.1.10', NULL, NULL, '2026-02-15 22:19:47'),
(44, 4, 'update', 'document', 18, 'دليل استكشاف الأخطاء', 'تحديث دليل استكشاف الأخطاء', '192.168.1.14', NULL, NULL, '2026-02-16 22:19:47'),
(45, 3, 'share', 'report', 9, 'تقرير اختبار الاختراق', 'مشاركة التقرير مع فريق الأمان', '192.168.1.12', NULL, NULL, '2026-02-17 22:19:47'),
(46, 2, 'view', 'project', 1, 'نظام استضافة المواقع', 'عرض تفاصيل المشروع', '192.168.1.11', NULL, NULL, '2026-02-18 22:19:47'),
(47, 5, 'create', 'document', NULL, NULL, 'بدء إنشاء مستند جديد', '192.168.1.15', NULL, NULL, '2026-02-18 22:19:47'),
(48, 1, 'comment', 'document', 16, 'هيكلية الشبكة', 'إضافة تعليق على هيكلية الشبكة', '192.168.1.10', NULL, NULL, '2026-02-18 21:49:47'),
(49, 4, 'download', 'document', 15, 'دليل تثبيت الشبكة', 'تحميل دليل التثبيت', '192.168.1.14', NULL, NULL, '2026-02-18 20:19:47'),
(50, 3, 'update', 'report', 9, 'تقرير اختبار الاختراق', 'تحديث نتائج الاختبارات', '192.168.1.12', NULL, NULL, '2026-02-18 17:19:47'),
(51, 2, 'review', 'document', 17, 'تقييم أمن الشبكة', 'بدء مراجعة تقييم الأمن', '192.168.1.11', NULL, NULL, '2026-02-17 22:19:47'),
(52, 1, 'export', 'project', 1, 'نظام استضافة المواقع', 'تصدير كافة مستندات المشروع', '192.168.1.10', NULL, NULL, '2026-02-15 22:19:47'),
(53, 5, 'approve', 'template', 17, 'قالب تقييم المخاطر', 'الموافقة على قالب تقييم المخاطر', '192.168.1.15', NULL, NULL, '2026-02-14 22:19:47'),
(54, 4, 'upload', 'file', NULL, 'شهادة أمان.pdf', 'رفع شهادة أمان', '192.168.1.14', NULL, NULL, '2026-02-13 22:19:47'),
(55, 1, 'create', 'document', 21, NULL, 'إضافة مستند جديد: ؤ', NULL, NULL, NULL, '2026-02-19 00:22:59'),
(56, 1, 'update', 'document', 21, NULL, 'تحديث حالة المستند إلى: needs_work', NULL, NULL, NULL, '2026-02-19 00:30:37'),
(57, 1, 'update', 'document', 21, NULL, 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, '2026-02-19 00:31:39'),
(58, 1, 'update', 'document', 21, NULL, 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, '2026-02-19 00:31:41'),
(59, 1, 'delete', 'document', 21, NULL, 'حذف مستند: ؤ', NULL, NULL, NULL, '2026-02-19 00:32:06');

-- --------------------------------------------------------

--
-- بنية الجدول `documentation_projects`
--

CREATE TABLE `documentation_projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_company` varchar(255) DEFAULT NULL,
  `project_type` enum('hosting','storage','security','ecommerce','cloud','network','mobile','desktop') DEFAULT 'hosting',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('new','under_analysis','analyzed','in_progress','completed','on_hold','cancelled') DEFAULT 'new',
  `assigned_team` varchar(255) DEFAULT NULL,
  `project_manager` varchar(255) DEFAULT NULL,
  `technical_lead` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `documents_count` int(11) DEFAULT 0,
  `pages_count` int(11) DEFAULT 0,
  `budget` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `technical_requirements` text DEFAULT NULL,
  `security_level` enum('normal','sensitive','critical') DEFAULT 'normal',
  `repository_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `documentation_projects`
--

INSERT INTO `documentation_projects` (`id`, `project_code`, `project_name`, `client_name`, `client_company`, `project_type`, `priority`, `status`, `assigned_team`, `project_manager`, `technical_lead`, `start_date`, `deadline`, `completion_date`, `progress`, `documents_count`, `pages_count`, `budget`, `description`, `technical_requirements`, `security_level`, `repository_path`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'HOST-2024-001', 'نظام استضافة المواقع الإلكترونية', 'شركة التقنية المتطورة', 'TechAdvance Co.', 'hosting', 'high', 'in_progress', 'فريق التوثيق الفني', 'أحمد العلي', 'سارة الأحمد', '2024-01-15', '2024-04-30', NULL, 65, 8, 245, NULL, 'نظام متكامل لإدارة استضافة المواقع مع دعم السحابة والتوسع التلقائي', NULL, 'sensitive', '/repositories/hosting-project', 1, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(2, 'STOR-2024-002', 'منصة التخزين السحابي', 'مؤسسة البيانات الآمنة', 'SecureData Foundation', 'storage', 'critical', 'under_analysis', 'فريق الأمن والتوثيق', 'محمد العنزي', 'نورة الدوسري', '2024-02-01', '2024-05-15', NULL, 25, 3, 120, NULL, 'منصة تخزين سحابي مع تشفير متقدم وتكامل مع التطبيقات', NULL, 'critical', '/repositories/cloud-storage', 3, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(3, 'SEC-2024-003', 'نظام الحماية الأمني', 'شركة الأمن السيبراني', 'CyberGuard Inc.', 'security', 'critical', 'analyzed', 'فريق أمن المعلومات', 'خالد الرشيد', 'فاطمة الزهراني', '2024-01-20', '2024-06-30', NULL, 40, 5, 180, NULL, 'نظام متكامل للحماية من الاختراقات والهجمات السيبرانية', NULL, 'critical', '/repositories/security-system', 5, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(4, 'ECOMM-2024-004', 'منصة التجارة الإلكترونية', 'شركة التسوق الحديث', 'ModernShop Co.', 'ecommerce', 'high', 'new', 'فريق التوثيق', 'سامي الحربي', 'منى الغامدي', '2024-03-01', '2024-07-15', NULL, 10, 2, 85, NULL, 'منصة تجارة إلكترونية متكاملة مع بوابات دفع وإدارة المخزون', NULL, 'sensitive', '/repositories/ecommerce-platform', 2, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(5, 'NET-2024-005', 'نظام إدارة الشبكات', 'شركة الاتصالات المتكاملة', 'NetConnect Ltd.', 'network', 'medium', 'on_hold', 'فريق البنية التحتية', 'عبدالله المطيري', 'ريم القحطاني', '2024-02-15', '2024-05-30', NULL, 50, 4, 150, NULL, 'نظام إدارة ومراقبة الشبكات مع تحليلات متقدمة', NULL, 'normal', '/repositories/network-management', 4, '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `documentation_stats`
--

CREATE TABLE `documentation_stats` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `projects_created` int(11) DEFAULT 0,
  `projects_completed` int(11) DEFAULT 0,
  `projects_in_progress` int(11) DEFAULT 0,
  `documents_created` int(11) DEFAULT 0,
  `documents_updated` int(11) DEFAULT 0,
  `documents_reviewed` int(11) DEFAULT 0,
  `documents_approved` int(11) DEFAULT 0,
  `documents_rejected` int(11) DEFAULT 0,
  `total_pages` int(11) DEFAULT 0,
  `total_documents` int(11) DEFAULT 0,
  `total_templates` int(11) DEFAULT 0,
  `total_reports` int(11) DEFAULT 0,
  `active_users` int(11) DEFAULT 0,
  `reviews_completed` int(11) DEFAULT 0,
  `comments_added` int(11) DEFAULT 0,
  `storage_used_mb` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `documentation_stats`
--

INSERT INTO `documentation_stats` (`id`, `stat_date`, `projects_created`, `projects_completed`, `projects_in_progress`, `documents_created`, `documents_updated`, `documents_reviewed`, `documents_approved`, `documents_rejected`, `total_pages`, `total_documents`, `total_templates`, `total_reports`, `active_users`, `reviews_completed`, `comments_added`, `storage_used_mb`, `created_at`) VALUES
(1, '2024-01-01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 2, 0, 0, 0.00, '2026-02-18 22:19:47'),
(2, '2024-01-07', 1, 0, 1, 3, 0, 0, 0, 0, 125, 3, 5, 0, 3, 0, 2, 15.50, '2026-02-18 22:19:47'),
(3, '2024-01-14', 1, 0, 2, 5, 2, 1, 1, 0, 320, 8, 6, 1, 4, 1, 8, 45.20, '2026-02-18 22:19:47'),
(4, '2024-01-21', 1, 0, 3, 4, 5, 3, 2, 1, 450, 12, 7, 1, 5, 3, 15, 78.90, '2026-02-18 22:19:47'),
(5, '2024-01-28', 1, 0, 3, 3, 4, 4, 3, 0, 280, 15, 8, 2, 5, 4, 12, 95.40, '2026-02-18 22:19:47'),
(6, '2024-02-04', 0, 0, 3, 4, 3, 2, 2, 0, 210, 19, 9, 2, 4, 2, 7, 115.30, '2026-02-18 22:19:47'),
(7, '2024-02-11', 0, 1, 2, 3, 5, 3, 3, 0, 185, 22, 10, 3, 5, 3, 10, 132.80, '2026-02-18 22:19:47'),
(8, '2024-02-18', 1, 0, 3, 5, 3, 4, 3, 1, 320, 27, 12, 3, 5, 4, 14, 158.20, '2026-02-18 22:19:47'),
(9, '2024-02-25', 1, 0, 4, 4, 6, 5, 4, 0, 290, 31, 14, 4, 5, 5, 18, 182.50, '2026-02-18 22:19:47'),
(10, '2024-03-03', 0, 0, 4, 5, 4, 3, 3, 0, 275, 36, 16, 5, 4, 3, 12, 205.80, '2026-02-18 22:19:47'),
(11, '2024-03-10', 0, 0, 4, 4, 7, 5, 4, 1, 310, 40, 18, 6, 5, 5, 20, 232.40, '2026-02-18 22:19:47'),
(12, '2024-03-17', 1, 0, 4, 5, 5, 4, 3, 0, 340, 45, 22, 7, 5, 4, 16, 262.30, '2026-02-18 22:19:47'),
(13, '2024-03-24', 0, 1, 3, 3, 8, 6, 5, 0, 260, 48, 25, 8, 5, 6, 22, 285.70, '2026-02-18 22:19:47'),
(14, '2024-03-31', 0, 0, 3, 2, 4, 3, 2, 0, 150, 50, 28, 9, 4, 3, 10, 298.50, '2026-02-18 22:19:47'),
(15, '2024-04-07', 1, 0, 4, 4, 5, 4, 3, 0, 280, 54, 30, 10, 5, 4, 15, 322.00, '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `document_code` varchar(50) NOT NULL,
  `title` varchar(500) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `document_type` enum('technical','architecture','security','api','database','user_guide','deployment','requirements','test_plan','operation_manual','report','contract','proposal') DEFAULT 'technical',
  `format` enum('pdf','docx','xlsx','pptx','txt','md','html','xml','json','yaml','other') DEFAULT 'pdf',
  `version` varchar(20) DEFAULT '1.0.0',
  `status` enum('draft','under_review','needs_work','approved','rejected','archived','in_progress','review','obsolete') DEFAULT 'draft',
  `content` longtext DEFAULT NULL,
  `executive_summary` text DEFAULT NULL,
  `introduction` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `pages` int(11) DEFAULT 0,
  `word_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_template` tinyint(1) DEFAULT 0,
  `template_id` int(11) DEFAULT NULL,
  `parent_document_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `documents`
--

INSERT INTO `documents` (`id`, `document_code`, `title`, `project_id`, `document_type`, `format`, `version`, `status`, `content`, `executive_summary`, `introduction`, `file_path`, `file_size`, `pages`, `word_count`, `created_by`, `updated_by`, `reviewed_by`, `approved_by`, `created_date`, `review_date`, `approval_date`, `tags`, `description`, `is_template`, `template_id`, `parent_document_id`, `created_at`, `updated_at`) VALUES
(2, 'DOC-TECH-002', 'دليل تثبيت نظام الاستضافة', 1, 'deployment', 'pdf', '1.0', 'draft', NULL, NULL, NULL, '/docs/hosting/installation.pdf', 1850000, 32, 0, 1, NULL, NULL, NULL, '2024-01-18', NULL, NULL, 'installation,deployment,setup', 'دليل تثبيت نظام الاستضافة في بيئة الإنتاج', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(3, 'DOC-SEC-003', 'تقرير الاختبارات الأمنية', 1, 'security', 'pdf', '2.1', 'under_review', NULL, NULL, NULL, '/docs/hosting/security-test.pdf', 3200000, 58, 0, 3, NULL, NULL, NULL, '2024-01-22', '2024-01-25', NULL, 'security,testing,penetration', 'تقرير نتائج الاختبارات الأمنية', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(4, 'DOC-USER-004', 'دليل مستخدم نظام الاستضافة', 1, 'user_guide', 'pdf', '1.5', 'approved', NULL, NULL, NULL, '/docs/hosting/user-guide.pdf', 4250000, 120, 0, 1, NULL, NULL, NULL, '2024-01-20', '2024-01-28', NULL, 'user-guide,manual,help', 'دليل المستخدم لنظام الاستضافة', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(5, 'DOC-TECH-005', 'دليل المشرف', 1, 'operation_manual', 'pdf', '1.2', 'under_review', NULL, NULL, NULL, '/docs/hosting/admin-guide.pdf', 2100000, 65, 0, 3, NULL, NULL, NULL, '2024-01-22', '2024-01-27', NULL, 'admin,guide,operations', 'دليل المشرف للنظام', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(6, 'DOC-API-006', 'توثيق API - REST', 2, 'api', 'html', '0.9', 'draft', NULL, NULL, NULL, '/docs/storage/api-docs.html', 950000, 0, 0, 2, NULL, NULL, NULL, '2024-02-02', NULL, NULL, 'api,rest,integration', 'توثيق واجهات REST API', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 'DOC-SEC-008', 'تقرير أمني - البنية التحتية', 3, 'security', 'pdf', '1.0', 'approved', NULL, NULL, NULL, '/docs/security/infrastructure.pdf', 5600000, 85, 0, 5, NULL, NULL, NULL, '2024-01-10', '2024-01-18', NULL, 'security,infrastructure,audit', 'تقرير أمني عن البنية التحتية', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 'DOC-TECH-009', 'هيكلية النظام الأمني', 3, 'architecture', 'pdf', '2.3', 'approved', NULL, NULL, NULL, '/docs/security/architecture.pdf', 3800000, 72, 0, 5, NULL, NULL, NULL, '2024-01-12', '2024-01-19', NULL, 'architecture,design,security', 'وثيقة هيكلية النظام الأمني', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 'DOC-TECH-010', 'دليل تكوين النظام', 3, 'technical', 'pdf', '1.1', 'needs_work', NULL, NULL, NULL, '/docs/security/configuration.pdf', 1950000, 48, 0, 3, NULL, 1, NULL, '2024-01-15', '2026-02-20', NULL, 'configuration,setup,security', 'دليل تكوين النظام الأمني', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-20 20:48:00'),
(11, 'DOC-TEST-011', 'خطة اختبار الاختراق', 3, 'test_plan', 'pdf', '1.0', 'draft', NULL, NULL, NULL, '/docs/security/penetration-test.pdf', 1250000, 35, 0, 3, NULL, NULL, NULL, '2024-01-18', NULL, NULL, 'testing,penetration,security', 'خطة اختبار الاختراق للنظام', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 'DOC-USER-012', 'دليل مستخدم التطبيق', 4, 'user_guide', 'pdf', '0.8', 'draft', NULL, NULL, NULL, '/docs/ecommerce/user-guide.pdf', 2850000, 62, 0, 2, NULL, NULL, NULL, '2024-03-02', NULL, NULL, 'user-guide,ecommerce,app', 'دليل مستخدم تطبيق التجارة الإلكترونية', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 'DOC-TECH-013', 'متطلبات المنصة', 4, 'requirements', 'pdf', '1.0', 'under_review', NULL, NULL, NULL, '/docs/ecommerce/requirements.pdf', 3150000, 58, 0, 4, NULL, NULL, NULL, '2024-03-03', '2024-03-05', NULL, 'requirements,specifications,ecommerce', 'متطلبات منصة التجارة الإلكترونية', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 'DOC-API-014', 'توثيق API - الدفع', 4, 'api', 'md', '0.5', 'draft', NULL, NULL, NULL, '/docs/ecommerce/payment-api.md', 250000, 0, 0, 2, NULL, NULL, NULL, '2024-03-04', NULL, NULL, 'api,payment,integration', 'توثيق واجهات الدفع', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 'DOC-TECH-015', 'دليل تثبيت الشبكة', 5, 'deployment', 'pdf', '1.2', 'approved', NULL, NULL, NULL, '/docs/network/installation.pdf', 1850000, 42, 0, 4, NULL, NULL, NULL, '2024-02-16', '2024-02-20', NULL, 'installation,network,setup', 'دليل تثبيت نظام إدارة الشبكات', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(16, 'DOC-TECH-016', 'هيكلية الشبكة', 5, 'architecture', 'pdf', '2.0', 'approved', NULL, NULL, NULL, '/docs/network/architecture.pdf', 4200000, 78, 0, 4, NULL, NULL, NULL, '2024-02-18', '2024-02-22', NULL, 'architecture,network,design', 'هيكلية نظام إدارة الشبكات', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(17, 'DOC-SEC-017', 'تقييم أمن الشبكة', 5, 'security', 'pdf', '1.0', 'under_review', NULL, NULL, NULL, '/docs/network/security-assessment.pdf', 2350000, 45, 0, 5, NULL, NULL, NULL, '2024-02-20', '2024-02-25', NULL, 'security,assessment,network', 'تقييم أمن الشبكة', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(18, 'DOC-TECH-018', 'دليل استكشاف الأخطاء', 5, 'operation_manual', 'pdf', '0.9', 'draft', NULL, NULL, NULL, '/docs/network/troubleshooting.pdf', 1650000, 38, 0, 2, NULL, NULL, NULL, '2024-02-22', NULL, NULL, 'troubleshooting,errors,fixes', 'دليل استكشاف الأخطاء وإصلاحها', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(19, 'DOC-REP-019', 'تقرير التقدم - الربع الأول', 1, 'report', 'pdf', '1.0', 'approved', NULL, NULL, NULL, '/reports/q1-progress.pdf', 980000, 22, 0, 1, NULL, NULL, NULL, '2024-03-25', '2024-03-28', NULL, 'report,progress,Q1', 'تقرير تقدم المشروع للربع الأول', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(20, 'DOC-REP-020', 'تقرير الأداء الشهري', 2, 'report', 'pdf', '1.1', 'under_review', NULL, NULL, NULL, '/reports/monthly-performance.pdf', 1120000, 28, 0, 3, NULL, NULL, NULL, '2024-03-01', '2024-03-05', NULL, 'report,performance,monthly', 'تقرير الأداء الشهري للمنصة', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_comments`
--

CREATE TABLE `document_comments` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `page_number` int(11) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_comments`
--

INSERT INTO `document_comments` (`id`, `document_id`, `user_id`, `comment`, `page_number`, `section`, `resolved`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(4, 2, 5, 'قسم المستخدم بحاجة إلى إعادة صياغة', 45, 'دليل المستخدم', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(5, 3, 1, 'تمت إضافة نتائج الاختبارات', 58, 'نتائج الاختبارات', 1, 2, '2024-01-23 12:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(6, 3, 2, 'نحتاج توثيق أكثر للثغرات المكتشفة', 32, 'الثغرات', 1, 3, '2024-01-24 07:15:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(7, 4, 2, 'صياغة المقدمة ممتازة', 1, 'المقدمة', 1, NULL, '2024-01-21 10:20:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 4, 5, 'يوجد خطأ إملائي في الصفحة 15', 15, 'التثبيت', 1, 1, '2024-01-22 06:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 5, 2, 'قسم أوامر الإدارة غير مكتمل', 28, 'أوامر الإدارة', 1, 3, '2024-01-23 08:45:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 5, 3, 'تم إضافة الأوامر المطلوبة', 28, 'أوامر الإدارة', 1, 2, '2024-01-24 11:20:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(11, 8, 2, 'تقرير أمني شامل', 85, 'الخلاصة', 1, NULL, '2024-01-12 07:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 9, 5, 'رسم الهيكلية يحتاج توضيح', 33, 'الهيكلية', 1, 5, '2024-01-14 12:45:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 10, 2, 'نحتاج أمثلة على التكوين', 22, 'أمثلة التكوين', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 12, 1, 'الصفحة الرئيسية للدليل ممتازة', 1, 'المقدمة', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 13, 2, 'متطلبات قاعدة البيانات غير واضحة', 18, 'قاعدة البيانات', 1, 4, '2024-03-04 10:15:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(16, 13, 4, 'تم توضيح المتطلبات', 18, 'قاعدة البيانات', 1, 2, '2024-03-05 06:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(17, 15, 2, 'خطوات التثبيت واضحة', 12, 'التثبيت', 1, NULL, '2024-02-17 08:20:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(18, 16, 5, 'رسم الشبكة يحتاج تحديث', 45, 'الرسم البياني', 1, 4, '2024-02-19 11:45:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(19, 16, 4, 'تم تحديث الرسم', 45, 'الرسم البياني', 1, 5, '2024-02-20 07:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(20, 17, 2, 'نتائج التقييم دقيقة', 30, 'النتائج', 0, NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(21, 19, 5, 'إحصائيات التقدم ممتازة', 15, 'الإحصائيات', 1, NULL, '2024-03-26 08:15:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(22, 20, 2, 'أرقام الأداء تحتاج تدقيق', 22, 'الأداء', 1, 3, '2024-03-02 12:30:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(23, 20, 3, 'تم تدقيق الأرقام وتحديثها', 22, 'الأداء', 1, 2, '2024-03-04 06:45:00', '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_reviews`
--

CREATE TABLE `document_reviews` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_round` int(11) DEFAULT 1,
  `review_type` enum('technical','security','compliance','quality','final') DEFAULT 'technical',
  `status` enum('pending','in_progress','completed','rejected','needs_revision') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `comments` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist`)),
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `decision` enum('approve','rework','reject','pending') DEFAULT 'pending',
  `review_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_reviews`
--

INSERT INTO `document_reviews` (`id`, `document_id`, `reviewer_id`, `review_round`, `review_type`, `status`, `priority`, `comments`, `feedback`, `checklist`, `rating`, `decision`, `review_date`, `completed_date`, `created_at`, `updated_at`) VALUES
(3, 3, 2, 1, 'security', 'in_progress', 'high', 'جاري مراجعة التقرير الأمني', NULL, NULL, NULL, 'pending', NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(4, 4, 5, 1, 'quality', 'completed', 'high', 'مراجعة الجودة - دليل ممتاز', 'تمت المراجعة واعتماد الدليل', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-01-25', '2024-01-25', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(5, 5, 2, 1, 'technical', 'completed', 'high', 'مراجعة دليل المشرف', 'يحتاج تحديث قسم استكشاف الأخطاء', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": false, \"standards\": true}', 3, 'rework', '2024-01-24', '2024-01-24', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(6, 5, 2, 2, 'technical', 'completed', 'high', 'المراجعة الثانية', 'تم تحديث القسم المطلوب، جاهز للاعتماد', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 4, 'approve', '2024-01-25', '2024-01-25', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(7, 8, 2, 1, 'security', 'completed', 'high', 'مراجعة التقرير الأمني', 'تقرير شامل ومتكامل', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-01-15', '2024-01-15', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(8, 9, 5, 1, 'technical', 'completed', 'high', 'مراجعة الهيكلية', 'هيكلية ممتازة ومتكاملة', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-01-17', '2024-01-17', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(9, 10, 2, 1, 'technical', 'completed', 'high', 'مراجعة دليل التكوين', 'يحتاج إضافة أمثلة أكثر', '{\"comprehensive\": false, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 3, 'rework', '2024-01-20', '2024-01-20', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(10, 13, 2, 1, 'technical', 'completed', 'high', 'مراجعة متطلبات المنصة', 'ممتازة، جاهزة للاعتماد', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 4, 'approve', '2024-03-05', '2024-03-05', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(11, 15, 2, 1, 'technical', 'completed', 'high', 'مراجعة دليل التثبيت', 'تمت المراجعة والموافقة', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-02-18', '2024-02-18', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(12, 16, 5, 1, 'technical', 'completed', 'high', 'مراجعة هيكلية الشبكة', 'هيكلية متكاملة', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-02-20', '2024-02-20', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(13, 17, 2, 1, 'security', 'in_progress', 'high', 'مراجعة تقييم الأمن', NULL, NULL, NULL, 'pending', NULL, NULL, '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(14, 19, 5, 1, 'quality', 'completed', 'high', 'مراجعة تقرير التقدم', 'تقرير ممتاز، جاهز للإرسال', '{\"comprehensive\": true, \"accuracy\": true, \"clarity\": true, \"standards\": true}', 5, 'approve', '2024-03-26', '2024-03-26', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(15, 20, 2, 1, 'quality', 'completed', 'high', 'مراجعة تقرير الأداء', 'يحتاج تحديث بعض الإحصائيات', '{\"comprehensive\": true, \"accuracy\": false, \"clarity\": true, \"standards\": true}', 3, 'rework', '2024-03-03', '2024-03-03', '2026-02-18 22:19:47', '2026-02-18 23:26:28'),
(16, 10, 1, 1, 'technical', 'completed', 'medium', '', '', '\"[true,true,true,true]\"', 1, 'reject', '2026-02-19', NULL, '2026-02-19 02:10:36', '2026-02-19 02:10:36'),
(17, 10, 1, 1, 'technical', 'completed', 'medium', '', '', '\"[true,true,true,true]\"', 1, 'reject', '2026-02-20', NULL, '2026-02-20 20:48:00', '2026-02-20 20:48:00');

-- --------------------------------------------------------

--
-- بنية الجدول `document_sections`
--

CREATE TABLE `document_sections` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `order_number` int(11) DEFAULT NULL,
  `page_start` int(11) DEFAULT NULL,
  `page_end` int(11) DEFAULT NULL,
  `word_count` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_sections`
--

INSERT INTO `document_sections` (`id`, `document_id`, `title`, `content`, `order_number`, `page_start`, `page_end`, `word_count`, `created_at`, `updated_at`) VALUES
(5, 2, 'تثبيت النظام', 'خطوات تثبيت نظام الاستضافة على بيئة الإنتاج...', 1, 1, 12, 1150, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(6, 2, 'تكوين النظام', 'إعدادات التكوين الأساسية للنظام...', 2, 13, 22, 890, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(7, 2, 'اختبار التثبيت', 'كيفية التحقق من صحة التثبيت...', 3, 23, 32, 720, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 3, 'منهجية الاختبار', 'تم استخدام أدوات اختبار الاختراق التالية: Burp Suite, Nmap, Metasploit...', 1, 1, 8, 650, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 3, 'نتائج الاختبارات', 'تم اكتشاف 15 ثغرة أمنية منها 3 حرجة...', 2, 9, 30, 1850, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 3, 'التوصيات', 'التوصيات لمعالجة الثغرات المكتشفة...', 3, 31, 45, 980, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(11, 4, 'مقدمة للمستخدم', 'مرحباً بك في دليل مستخدم نظام الاستضافة...', 1, 1, 5, 450, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 4, 'بدء الاستخدام', 'كيفية البدء مع النظام...', 2, 6, 25, 1450, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 4, 'الميزات الرئيسية', 'شرح الميزات الرئيسية للنظام...', 3, 26, 60, 2250, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 4, 'استكشاف الأخطاء', 'حل المشكلات الشائعة...', 4, 61, 90, 1850, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 8, 'ملخص التقرير', 'هذا التقرير يقيّم أمان البنية التحتية...', 1, 1, 5, 580, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(16, 8, 'نطاق التقييم', 'تم تقييم 25 خادماً و10 أجهزة شبكة...', 2, 6, 20, 1120, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(17, 8, 'الثغرات المكتشفة', 'تم اكتشاف 8 ثغرات أمنية...', 3, 21, 50, 2150, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(18, 8, 'خطة المعالجة', 'خطة مقترحة لمعالجة الثغرات...', 4, 51, 70, 1250, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(19, 15, 'متطلبات الشبكة', 'متطلبات تثبيت نظام إدارة الشبكات...', 1, 1, 8, 620, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(20, 15, 'خطوات التثبيت', 'خطوات تثبيت النظام خطوة بخطوة...', 2, 9, 25, 1350, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(21, 15, 'التحقق من التثبيت', 'اختبارات التحقق من صحة التثبيت...', 3, 26, 35, 780, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(22, 16, 'نظرة عامة على الهيكلية', 'هيكلية نظام إدارة الشبكات...', 1, 1, 10, 850, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(23, 16, 'المكونات', 'المكونات الرئيسية للنظام...', 2, 11, 35, 1850, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(24, 16, 'تدفق البيانات', 'كيفية تدفق البيانات في النظام...', 3, 36, 55, 1350, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(25, 19, 'ملخص التقدم', 'تقدم المشروع خلال الربع الأول...', 1, 1, 4, 380, '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_tags`
--

CREATE TABLE `document_tags` (
  `document_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_tags`
--

INSERT INTO `document_tags` (`document_id`, `tag_id`, `created_at`) VALUES
(2, 5, '2026-02-18 22:19:47'),
(2, 6, '2026-02-18 22:19:47'),
(2, 12, '2026-02-18 22:19:47'),
(3, 2, '2026-02-18 22:19:47'),
(3, 7, '2026-02-18 22:19:47'),
(3, 8, '2026-02-18 22:19:47'),
(4, 4, '2026-02-18 22:19:47'),
(4, 5, '2026-02-18 22:19:47'),
(4, 6, '2026-02-18 22:19:47'),
(5, 9, '2026-02-18 22:19:47'),
(5, 10, '2026-02-18 22:19:47'),
(5, 12, '2026-02-18 22:19:47'),
(6, 3, '2026-02-18 22:19:47'),
(6, 12, '2026-02-18 22:19:47'),
(8, 2, '2026-02-18 22:19:47'),
(8, 8, '2026-02-18 22:19:47'),
(8, 9, '2026-02-18 22:19:47'),
(9, 2, '2026-02-18 22:19:47'),
(9, 9, '2026-02-18 22:19:47'),
(9, 10, '2026-02-18 22:19:47'),
(10, 2, '2026-02-18 22:19:47'),
(10, 5, '2026-02-18 22:19:47'),
(10, 10, '2026-02-18 22:19:47'),
(11, 2, '2026-02-18 22:19:47'),
(11, 7, '2026-02-18 22:19:47'),
(12, 4, '2026-02-18 22:19:47'),
(12, 5, '2026-02-18 22:19:47'),
(13, 1, '2026-02-18 22:19:47'),
(13, 2, '2026-02-18 22:19:47'),
(13, 11, '2026-02-18 22:19:47'),
(14, 3, '2026-02-18 22:19:47'),
(14, 12, '2026-02-18 22:19:47'),
(15, 5, '2026-02-18 22:19:47'),
(15, 6, '2026-02-18 22:19:47'),
(15, 12, '2026-02-18 22:19:47'),
(16, 9, '2026-02-18 22:19:47'),
(16, 10, '2026-02-18 22:19:47'),
(17, 2, '2026-02-18 22:19:47'),
(17, 8, '2026-02-18 22:19:47'),
(17, 9, '2026-02-18 22:19:47'),
(18, 4, '2026-02-18 22:19:47'),
(18, 7, '2026-02-18 22:19:47'),
(19, 1, '2026-02-18 22:19:47'),
(19, 11, '2026-02-18 22:19:47'),
(20, 2, '2026-02-18 22:19:47'),
(20, 11, '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_templates`
--

CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `template_code` varchar(50) NOT NULL,
  `type` enum('technical','user_manual','api_doc','security','compliance','report','contract','proposal') NOT NULL,
  `category` enum('technical','security','monthly','final','custom') DEFAULT 'technical',
  `format` enum('docx','md','html','txt','pdf') DEFAULT 'docx',
  `file_path` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`structure`)),
  `placeholders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`placeholders`)),
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `usage_count` int(11) DEFAULT 0,
  `rating` decimal(2,1) DEFAULT 0.0,
  `created_by` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `access_level` enum('public','team','private') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_templates`
--

INSERT INTO `document_templates` (`id`, `name`, `template_code`, `type`, `category`, `format`, `file_path`, `description`, `structure`, `placeholders`, `variables`, `usage_count`, `rating`, `created_by`, `is_public`, `access_level`, `created_at`, `updated_at`) VALUES
(1, 'قالب متطلبات النظام', 'TMP-REQ-001', 'technical', 'technical', 'docx', '/templates/requirements-template.docx', 'قالب موحد لتوثيق متطلبات النظام', NULL, '{\"project\":\"اسم المشروع\",\"date\":\"التاريخ\",\"author\":\"المؤلف\"}', '{\"project_name\":\"text\",\"client_name\":\"text\",\"version\":\"text\"}', 26, 4.5, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-20 20:53:10'),
(2, 'قالب تقرير أمني', 'TMP-SEC-001', 'security', 'security', 'docx', '/templates/security-report.docx', 'قالب موحد للتقارير الأمنية', NULL, '{\"project\":\"اسم المشروع\",\"date\":\"التاريخ\",\"auditor\":\"المدقق\"}', '{\"project_name\":\"text\",\"security_level\":\"select\",\"findings\":\"number\"}', 20, 4.8, 5, 1, 'public', '2026-02-18 22:19:47', '2026-02-20 20:52:42'),
(3, 'قالب دليل المستخدم', 'TMP-USER-001', 'user_manual', 'technical', 'docx', '/templates/user-manual.docx', 'قالب لدليل المستخدم', NULL, '{\"product\":\"المنتج\",\"version\":\"الإصدار\",\"date\":\"التاريخ\"}', '{\"product_name\":\"text\",\"version\":\"text\",\"audience\":\"text\"}', 32, 4.3, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(4, 'قالب توثيق API', 'TMP-API-001', 'api_doc', 'technical', 'md', '/templates/api-doc.md', 'قالب لتوثيق واجهات API', NULL, '{\"api\":\"اسم API\",\"version\":\"الإصدار\",\"base_url\":\"الرابط الأساسي\"}', '{\"api_name\":\"text\",\"version\":\"text\",\"endpoints\":\"array\"}', 17, 4.2, 2, 1, 'public', '2026-02-18 22:19:47', '2026-02-20 20:51:35'),
(5, 'قالب هيكلية النظام', 'TMP-ARCH-001', 'technical', 'technical', 'docx', '/templates/architecture-template.docx', 'قالب لتوثيق هيكلية النظام', NULL, '{\"system\":\"اسم النظام\",\"version\":\"الإصدار\"}', '{\"system_name\":\"text\",\"components\":\"array\",\"interactions\":\"text\"}', 14, 4.6, 4, 1, 'public', '2026-02-18 22:19:47', '2026-02-19 02:52:13'),
(6, 'قالب تقرير التقدم', 'TMP-REP-001', 'report', 'monthly', 'docx', '/templates/progress-report.docx', 'قالب لتقارير التقدم', NULL, '{\"project\":\"المشروع\",\"period\":\"الفترة\",\"author\":\"المؤلف\"}', '{\"project_name\":\"text\",\"start_date\":\"date\",\"end_date\":\"date\",\"progress\":\"number\"}', 23, 4.1, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-19 01:57:15'),
(7, 'قالب دليل التثبيت', 'TMP-INS-001', 'technical', 'technical', 'docx', '/templates/installation-guide.docx', 'قالب لأدلة التثبيت', NULL, '{\"system\":\"النظام\",\"version\":\"الإصدار\"}', '{\"system_name\":\"text\",\"requirements\":\"array\",\"steps\":\"array\"}', 14, 4.4, 3, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 'قالب خطة الاختبارات', 'TMP-TEST-001', 'technical', 'technical', 'docx', '/templates/test-plan.docx', 'قالب لخطط الاختبارات', NULL, '{\"project\":\"المشروع\",\"tester\":\"المختبر\"}', '{\"project_name\":\"text\",\"test_cases\":\"array\",\"environment\":\"text\"}', 8, 4.0, 2, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 'قالب تقرير الأداء', 'TMP-PERF-001', 'report', 'monthly', '', '/templates/performance-report.xlsx', 'قالب لتقارير الأداء', NULL, '{\"period\":\"الفترة\",\"department\":\"القسم\"}', '{\"period\":\"text\",\"metrics\":\"array\",\"targets\":\"array\"}', 10, 3.9, 1, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 'قالب دليل المشرف', 'TMP-ADMIN-001', 'user_manual', 'technical', 'docx', '/templates/admin-guide.docx', 'قالب لأدلة المشرفين', NULL, '{\"system\":\"النظام\",\"version\":\"الإصدار\"}', '{\"system_name\":\"text\",\"commands\":\"array\",\"monitoring\":\"text\"}', 9, 4.2, 3, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(11, 'قالب تقرير الامتثال', 'TMP-COMP-001', 'compliance', 'security', 'docx', '/templates/compliance-report.docx', 'قالب لتقارير الامتثال', NULL, '{\"standard\":\"المعيار\",\"auditor\":\"المدقق\"}', '{\"standard_name\":\"text\",\"requirements\":\"array\",\"compliance_level\":\"select\"}', 6, 4.5, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 'قالب العقد', 'TMP-CON-001', 'contract', 'custom', 'docx', '/templates/contract.docx', 'قالب للعقود', NULL, '{\"client\":\"العميل\",\"project\":\"المشروع\",\"value\":\"القيمة\"}', '{\"client_name\":\"text\",\"project_name\":\"text\",\"contract_value\":\"number\",\"start_date\":\"date\"}', 5, 4.7, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 'قالب طلب تغيير', 'TMP-CHG-001', 'technical', 'custom', 'docx', '/templates/change-request.docx', 'قالب لطلبات التغيير', NULL, '{\"project\":\"المشروع\",\"requester\":\"الطالب\"}', '{\"project_name\":\"text\",\"change_description\":\"text\",\"impact\":\"text\"}', 7, 4.0, 2, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 'قالب تسليم المشروع', 'TMP-DEL-001', 'report', 'final', 'docx', '/templates/delivery-report.docx', 'قالب لتسليم المشاريع', NULL, '{\"project\":\"المشروع\",\"client\":\"العميل\"}', '{\"project_name\":\"text\",\"deliverables\":\"array\",\"acceptance_criteria\":\"array\"}', 4, 4.3, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 'قالب دراسة جدوى', 'TMP-FEA-001', 'report', 'custom', 'docx', '/templates/feasibility-study.docx', 'قالب لدراسات الجدوى', NULL, '{\"project\":\"المشروع\",\"analyst\":\"المحلل\"}', '{\"project_name\":\"text\",\"cost_estimate\":\"number\",\"benefits\":\"array\",\"risks\":\"array\"}', 3, 4.1, 1, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(16, 'قالب خطة الجودة', 'TMP-QUAL-001', 'compliance', 'custom', 'docx', '/templates/quality-plan.docx', 'قالب لخطط الجودة', NULL, '{\"project\":\"المشروع\",\"qa_lead\":\"مسؤول الجودة\"}', '{\"project_name\":\"text\",\"standards\":\"array\",\"processes\":\"array\"}', 5, 4.2, 4, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(17, 'قالب تقييم المخاطر', 'TMP-RISK-001', 'security', 'security', 'docx', '/templates/risk-assessment.docx', 'قالب لتقييم المخاطر', NULL, '{\"project\":\"المشروع\",\"assessor\":\"المقيم\"}', '{\"project_name\":\"text\",\"risks\":\"array\",\"mitigation\":\"text\"}', 8, 4.4, 5, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(18, 'قالب خطة الاستجابة', 'TMP-RESP-001', 'security', 'security', 'docx', '/templates/response-plan.docx', 'قالب لخطط الاستجابة للحوادث', NULL, '{\"system\":\"النظام\",\"team\":\"الفريق\"}', '{\"system_name\":\"text\",\"incident_types\":\"array\",\"procedures\":\"array\"}', 4, 4.5, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(19, 'قالب دعم المستخدم', 'TMP-SUPP-001', 'user_manual', 'technical', 'md', '/templates/support-guide.md', 'قالب لأدلة الدعم', NULL, '{\"product\":\"المنتج\",\"support_team\":\"فريق الدعم\"}', '{\"product_name\":\"text\",\"faq\":\"array\",\"troubleshooting\":\"array\"}', 6, 4.0, 2, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(20, 'قالب توثيق قاعدة بيانات', 'TMP-DB-001', '', 'technical', 'md', '/templates/database-doc.md', 'قالب لتوثيق قواعد البيانات', NULL, '{\"database\":\"قاعدة البيانات\",\"version\":\"الإصدار\"}', '{\"db_name\":\"text\",\"tables\":\"array\",\"relationships\":\"text\"}', 7, 4.3, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(21, 'قالب دليل المطور', 'TMP-DEV-001', 'technical', 'technical', 'md', '/templates/developer-guide.md', 'قالب لأدلة المطورين', NULL, '{\"project\":\"المشروع\",\"language\":\"لغة البرمجة\"}', '{\"project_name\":\"text\",\"setup\":\"text\",\"code_examples\":\"array\"}', 9, 4.2, 3, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(22, 'قالب تقرير الحوادث', 'TMP-INCID-001', 'security', 'security', 'docx', '/templates/incident-report.docx', 'قالب لتقارير الحوادث', NULL, '{\"incident\":\"الحادث\",\"reporter\":\"المبلغ\"}', '{\"incident_id\":\"text\",\"severity\":\"select\",\"description\":\"text\",\"resolution\":\"text\"}', 5, 4.1, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(23, 'قالب مراجعة الكود', 'TMP-CODE-001', 'technical', 'custom', 'md', '/templates/code-review.md', 'قالب لمراجعات الكود', NULL, '{\"project\":\"المشروع\",\"reviewer\":\"المراجع\"}', '{\"component\":\"text\",\"code_quality\":\"text\",\"security_issues\":\"array\"}', 4, 4.0, 2, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(24, 'قالب خطة النشر', 'TMP-DEPLOY-001', 'technical', 'technical', 'docx', '/templates/deployment-plan.docx', 'قالب لخطط النشر', NULL, '{\"system\":\"النظام\",\"environment\":\"البيئة\"}', '{\"system_name\":\"text\",\"environment\":\"select\",\"steps\":\"array\",\"rollback\":\"text\"}', 6, 4.3, 4, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(25, 'قالب متطلبات التدريب', 'TMP-TRAIN-001', 'technical', 'custom', 'docx', '/templates/training-requirements.docx', 'قالب لمتطلبات التدريب', NULL, '{\"project\":\"المشروع\",\"audience\":\"الجمهور\"}', '{\"project_name\":\"text\",\"training_needs\":\"array\",\"materials\":\"array\"}', 3, 4.0, 1, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(26, 'قالب خطة التعافي', 'TMP-RECOV-001', 'security', 'security', 'docx', '/templates/recovery-plan.docx', 'قالب لخطط التعافي من الكوارث', NULL, '{\"system\":\"النظام\",\"rto\":\"RTO\",\"rpo\":\"RPO\"}', '{\"system_name\":\"text\",\"critical_services\":\"array\",\"backup_procedures\":\"array\",\"recovery_steps\":\"array\"}', 3, 4.6, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(27, 'قالب تقرير الاجتماع', 'TMP-MEET-001', 'report', 'custom', 'docx', '/templates/meeting-minutes.docx', 'قالب لمحاضر الاجتماعات', NULL, '{\"project\":\"المشروع\",\"date\":\"التاريخ\"}', '{\"project_name\":\"text\",\"attendees\":\"array\",\"discussion_points\":\"array\",\"action_items\":\"array\"}', 12, 4.0, 1, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(28, 'قالب تقييم البائع', 'TMP-VEND-001', 'report', 'custom', 'docx', '/templates/vendor-assessment.docx', 'قالب لتقييم البائعين', NULL, '{\"vendor\":\"البائع\",\"assessor\":\"المقيم\"}', '{\"vendor_name\":\"text\",\"criteria\":\"array\",\"score\":\"number\",\"recommendation\":\"text\"}', 2, 3.8, 1, 1, 'team', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(29, 'قالب خطة المشروع', 'TMP-PROJ-001', 'report', 'custom', 'docx', '/templates/project-plan.docx', 'قالب لخطط المشاريع', NULL, '{\"project\":\"المشروع\",\"manager\":\"المدير\"}', '{\"project_name\":\"text\",\"objectives\":\"array\",\"milestones\":\"array\",\"resources\":\"array\"}', 5, 4.2, 4, 1, 'public', '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(30, 'قالب طلب عرض', 'TMP-RFP-001', 'contract', 'custom', 'docx', '/templates/rfp.docx', 'قالب لطلبات العروض', NULL, '{\"project\":\"المشروع\",\"deadline\":\"الموعد النهائي\"}', '{\"project_name\":\"text\",\"scope\":\"text\",\"requirements\":\"array\",\"submission_guidelines\":\"text\"}', 3, 4.1, 5, 0, 'private', '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_updates`
--

CREATE TABLE `document_updates` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `update_type` enum('minor','major','critical','security','bugfix') DEFAULT 'minor',
  `old_version` varchar(20) DEFAULT NULL,
  `new_version` varchar(20) DEFAULT NULL,
  `changes_summary` text DEFAULT NULL,
  `detailed_changes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `status` enum('pending','applied','rolled_back','failed') DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_updates`
--

INSERT INTO `document_updates` (`id`, `document_id`, `update_type`, `old_version`, `new_version`, `changes_summary`, `detailed_changes`, `created_by`, `reviewed_by`, `status`, `applied_at`, `created_at`) VALUES
(3, 3, 'major', '1.0', '2.0', 'تحديث شامل لنتائج الاختبارات', 'إضافة نتائج اختبارات جديدة وتوصيات', 3, 2, 'rolled_back', '2024-01-23 13:45:00', '2026-02-18 22:19:47'),
(4, 3, 'minor', '2.0', '2.1', 'تحديث التوصيات الأمنية', 'إضافة توصيات أمنية إضافية', 5, 2, 'rolled_back', '2024-01-24 08:30:00', '2026-02-18 22:19:47'),
(5, 4, 'major', '1.0', '1.5', 'تحديث شامل بعد المراجعة', 'إعادة هيكلة الدليل وتحديث المحتوى', 1, 5, 'applied', '2024-01-25 14:20:00', '2026-02-18 22:19:47'),
(6, 5, 'minor', '1.0', '1.1', 'تحديث قسم المراقبة', 'إضافة أوامر مراقبة جديدة', 3, 2, 'applied', '2024-01-23 09:15:00', '2026-02-18 22:19:47'),
(7, 5, 'minor', '1.1', '1.2', 'إضافة أوامر الإدارة', 'توثيق أوامر إدارية إضافية', 3, 2, 'applied', '2024-01-24 07:30:00', '2026-02-18 22:19:47'),
(8, 9, 'major', '1.0', '2.0', 'تحديث هيكلية الأمان', 'إعادة تصميم هيكلية الأمان', 5, 2, 'applied', '2024-01-14 11:45:00', '2026-02-18 22:19:47'),
(9, 9, 'security', '2.0', '2.3', 'إضافة طبقات حماية', 'إضافة جدران نارية وIDS/IPS', 3, 5, 'applied', '2024-01-16 13:20:00', '2026-02-18 22:19:47'),
(10, 10, 'minor', '1.0', '1.1', 'تحديث إعدادات الجدار الناري', 'إضافة قواعد جديدة للجدار الناري', 3, 2, 'applied', '2024-01-17 12:40:00', '2026-02-18 22:19:47'),
(11, 15, 'major', '1.0', '1.2', 'تحديث خطوات التثبيت', 'تبسيط وتحديث خطوات التثبيت', 4, 2, 'applied', '2024-02-18 09:30:00', '2026-02-18 22:19:47'),
(12, 16, 'major', '1.0', '2.0', 'إعادة هيكلة الشبكة', 'تحديث هيكلية الشبكة بالكامل', 4, 5, 'applied', '2024-02-20 08:15:00', '2026-02-18 22:19:47'),
(13, 20, 'minor', '1.0', '1.1', 'تحديث إحصائيات الأداء', 'تدقيق وتحديث أرقام الأداء', 3, 2, 'applied', '2024-03-04 07:45:00', '2026-02-18 22:19:47'),
(14, 6, 'critical', '0.5', '0.9', 'تحديث أمني عاجل', 'إصلاح ثغرات أمنية في التوثيق', 2, 5, 'applied', '2024-02-15 06:20:00', '2026-02-18 22:19:47'),
(16, 13, 'major', '0.5', '1.0', 'إكمال المتطلبات', 'إضافة جميع متطلبات المنصة', 4, 2, 'applied', '2024-03-05 11:30:00', '2026-02-18 22:19:47'),
(17, 17, 'security', '0.8', '1.0', 'تحديث أمني', 'إضافة نتائج تقييم أمني جديد', 5, 2, 'pending', NULL, '2026-02-18 22:19:47'),
(18, 2, 'bugfix', '1.0', '1.0.1', 'إصلاح أخطاء', 'تصحيح أخطاء في دليل التثبيت', 1, 3, 'applied', '2024-01-25 10:15:00', '2026-02-18 22:19:47'),
(20, 19, 'minor', '1.0', '1.0', 'تحديث بسيط', 'تحديث تنسيق التقرير', 1, 5, 'applied', '2024-03-26 08:30:00', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `document_versions`
--

CREATE TABLE `document_versions` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `changes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `document_versions`
--

INSERT INTO `document_versions` (`id`, `document_id`, `version_number`, `file_path`, `file_size`, `changes`, `created_by`, `created_at`) VALUES
(4, 2, '1.0', '/docs/hosting/installation_v1.pdf', 1850000, 'الإصدار الأولي', 1, '2024-01-18 08:15:00'),
(5, 3, '1.0', '/docs/hosting/security-test_v1.pdf', 2900000, 'الإصدار الأولي', 3, '2024-01-22 10:20:00'),
(6, 3, '2.0', '/docs/hosting/security-test_v2.pdf', 3100000, 'تحديث شامل لنتائج الاختبارات', 3, '2024-01-23 12:30:00'),
(7, 3, '2.1', '/docs/hosting/security-test_v2.1.pdf', 3200000, 'إضافة توصيات أمنية', 5, '2024-01-24 07:45:00'),
(8, 4, '1.0', '/docs/hosting/user-guide_v1.pdf', 4000000, 'الإصدار الأولي', 1, '2024-01-20 06:00:00'),
(9, 4, '1.5', '/docs/hosting/user-guide_v1.5.pdf', 4250000, 'تحديث شامل بعد المراجعة', 1, '2024-01-25 13:30:00'),
(10, 5, '1.0', '/docs/hosting/admin-guide_v1.pdf', 2000000, 'الإصدار الأولي', 3, '2024-01-22 11:15:00'),
(11, 5, '1.1', '/docs/hosting/admin-guide_v1.1.pdf', 2050000, 'تحديث قسم المراقبة', 3, '2024-01-23 08:30:00'),
(12, 5, '1.2', '/docs/hosting/admin-guide_v1.2.pdf', 2100000, 'إضافة أوامر الإدارة', 3, '2024-01-24 06:20:00'),
(13, 8, '1.0', '/docs/security/infrastructure_v1.pdf', 5600000, 'الإصدار الأولي', 5, '2024-01-10 05:45:00'),
(14, 9, '1.0', '/docs/security/architecture_v1.pdf', 3500000, 'الإصدار الأولي', 5, '2024-01-12 07:30:00'),
(15, 9, '2.0', '/docs/security/architecture_v2.pdf', 3700000, 'تحديث هيكلية الأمان', 5, '2024-01-14 10:15:00'),
(16, 9, '2.3', '/docs/security/architecture_v2.3.pdf', 3800000, 'إضافة طبقات حماية جديدة', 3, '2024-01-16 12:45:00'),
(17, 10, '1.0', '/docs/security/configuration_v1.pdf', 1900000, 'الإصدار الأولي', 3, '2024-01-15 08:20:00'),
(18, 10, '1.1', '/docs/security/configuration_v1.1.pdf', 1950000, 'تحديث إعدادات الجدار الناري', 3, '2024-01-17 11:30:00'),
(19, 15, '1.0', '/docs/network/installation_v1.pdf', 1700000, 'الإصدار الأولي', 4, '2024-02-16 06:30:00'),
(20, 15, '1.2', '/docs/network/installation_v1.2.pdf', 1850000, 'تحديث خطوات التثبيت', 4, '2024-02-18 08:45:00'),
(21, 16, '1.0', '/docs/network/architecture_v1.pdf', 4000000, 'الإصدار الأولي', 4, '2024-02-18 10:20:00'),
(22, 16, '2.0', '/docs/network/architecture_v2.pdf', 4200000, 'إعادة هيكلة الشبكة', 4, '2024-02-20 07:15:00'),
(23, 17, '1.0', '/docs/network/security-assessment_v1.pdf', 2350000, 'الإصدار الأولي', 5, '2024-02-20 11:30:00'),
(24, 19, '1.0', '/reports/q1-progress_v1.pdf', 980000, 'الإصدار الأولي', 1, '2024-03-25 06:00:00'),
(25, 20, '1.0', '/reports/monthly-performance_v1.pdf', 1080000, 'الإصدار الأولي', 3, '2024-03-01 08:30:00'),
(26, 20, '1.1', '/reports/monthly-performance_v1.1.pdf', 1120000, 'تحديث إحصائيات الأداء', 3, '2024-03-03 12:20:00');

-- --------------------------------------------------------

--
-- بنية الجدول `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'كم تستغرق عملية إعداد الاستضافة؟', 'تستغرق عملية إعداد الاستضافة من 2-24 ساعة حسب نوع الخدمة المطلوبة. الخطط العادية تستغرق حتى 24 ساعة، بينما الخطط المميزة تستغرق 2-4 ساعات فقط.', 'الاستضافة', 1, 1, '2026-02-21 04:24:31', '2026-02-21 04:24:31'),
(2, 'هل توفرون نسخ احتياطي للملفات؟', 'نعم، نوفر نسخ احتياطي تلقائي للملفات وقواعد البيانات بشكل يومي أو أسبوعي حسب الخطة، مع إمكانية استعادة البيانات بكل سهولة.', 'الاستضافة', 2, 1, '2026-02-21 04:24:31', '2026-02-21 04:24:31'),
(3, 'كيف أتأكد من أمان موقعي؟', 'نوفر عدة طبقات أمان منها جدار حماية متقدم، فحص دوري للثغرات، شهادات SSL، ومراقبة 24/7. كما يمكنك طلب فحص أمني متخصص.', 'الأمان', 3, 1, '2026-02-21 04:24:31', '2026-02-21 04:24:31'),
(4, 'ما هو ضمان استعادة الأموال؟', 'نقدم ضمان استعادة الأموال لمدة 30 يوماً من تاريخ الاشتراك، إذا لم تكن راضياً عن الخدمة يمكنك إلغاء الاشتراك واسترداد أموالك كاملة.', 'الفواتير', 4, 1, '2026-02-21 04:24:31', '2026-02-21 04:24:31'),
(5, 'هل يمكنني ترقية خطتي لاحقاً؟', 'نعم، يمكنك ترقية خطتك في أي وقت وسيتم خصم المبلغ المتبقي من خطتك الحالية. كما نوفر ترقيات مرنة تناسب احتياجاتك.', 'الاستضافة', 5, 1, '2026-02-21 04:24:31', '2026-02-21 04:24:31');

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_access_logs`
--

CREATE TABLE `hosting_access_logs` (
  `id` bigint(20) NOT NULL,
  `site_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_uri` varchar(500) DEFAULT NULL,
  `http_referer` varchar(500) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `response_time` int(11) DEFAULT NULL COMMENT 'بالملي ثانية',
  `bytes_sent` int(11) DEFAULT NULL,
  `accessed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_backups`
--

CREATE TABLE `hosting_backups` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `backup_type` enum('full','database','files') DEFAULT 'full',
  `backup_size` int(11) DEFAULT NULL COMMENT 'بالميجابايت',
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_backups`
--

INSERT INTO `hosting_backups` (`id`, `site_id`, `backup_type`, `backup_size`, `file_path`, `status`, `started_at`, `completed_at`, `created_at`) VALUES
(1, 1, 'full', 2048, '/backups/site1/eshop_full_20250120.zip', 'completed', '2025-01-20 02:00:00', '2025-01-20 02:45:30', '2025-01-19 23:45:30'),
(2, 1, 'full', 2050, '/backups/site1/eshop_full_20250119.zip', 'completed', '2025-01-19 02:00:00', '2025-01-19 02:44:15', '2025-01-18 23:44:15'),
(3, 1, 'full', 2035, '/backups/site1/eshop_full_20250118.zip', 'completed', '2025-01-18 02:00:00', '2025-01-18 02:43:45', '2025-01-17 23:43:45'),
(4, 1, 'full', 2028, '/backups/site1/eshop_full_20250117.zip', 'completed', '2025-01-17 02:00:00', '2025-01-17 02:42:20', '2025-01-16 23:42:20'),
(5, 1, 'full', 2020, '/backups/site1/eshop_full_20250116.zip', 'completed', '2025-01-16 02:00:00', '2025-01-16 02:41:10', '2025-01-15 23:41:10'),
(6, 2, 'full', 512, '/backups/site2/company_full_20250120.zip', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:20:45', '2025-01-20 00:20:45'),
(7, 2, 'full', 508, '/backups/site2/company_full_20250119.zip', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:19:30', '2025-01-19 00:19:30'),
(8, 2, 'full', 505, '/backups/site2/company_full_20250118.zip', 'completed', '2025-01-18 03:00:00', '2025-01-18 03:18:15', '2025-01-18 00:18:15'),
(9, 2, 'full', 503, '/backups/site2/company_full_20250117.zip', 'completed', '2025-01-17 03:00:00', '2025-01-17 03:17:40', '2025-01-17 00:17:40'),
(10, 3, 'full', 8192, '/backups/site3/lms_full_20250120.zip', 'completed', '2025-01-20 01:00:00', '2025-01-20 02:30:15', '2025-01-19 23:30:15'),
(11, 3, 'full', 8185, '/backups/site3/lms_full_20250119.zip', 'completed', '2025-01-19 01:00:00', '2025-01-19 02:28:45', '2025-01-18 23:28:45'),
(12, 3, 'full', 8178, '/backups/site3/lms_full_20250118.zip', 'completed', '2025-01-18 01:00:00', '2025-01-18 02:27:30', '2025-01-17 23:27:30'),
(13, 3, 'full', 8170, '/backups/site3/lms_full_20250117.zip', 'completed', '2025-01-17 01:00:00', '2025-01-17 02:26:15', '2025-01-16 23:26:15'),
(14, 3, 'full', 8165, '/backups/site3/lms_full_20250116.zip', 'completed', '2025-01-16 01:00:00', '2025-01-16 02:25:00', '2025-01-15 23:25:00'),
(15, 4, 'full', 3072, '/backups/site4/forum_full_20250120.zip', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:50:30', '2025-01-20 01:50:30'),
(16, 4, 'full', 3065, '/backups/site4/forum_full_20250119.zip', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:48:45', '2025-01-19 01:48:45'),
(17, 4, 'full', 3058, '/backups/site4/forum_full_20250118.zip', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:47:15', '2025-01-18 01:47:15'),
(18, 5, 'full', 256, '/backups/site5/blog_full_20250120.zip', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:12:20', '2025-01-20 02:12:20'),
(19, 5, 'full', 252, '/backups/site5/blog_full_20250119.zip', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:11:45', '2025-01-19 02:11:45'),
(20, 5, 'full', 250, '/backups/site5/blog_full_20250118.zip', 'completed', '2025-01-18 05:00:00', '2025-01-18 05:10:30', '2025-01-18 02:10:30'),
(21, 1, 'database', 512, '/backups/site1/eshop_db_20250120.sql', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:15:30', '2025-01-20 00:15:30'),
(22, 1, 'database', 508, '/backups/site1/eshop_db_20250119.sql', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:14:45', '2025-01-19 00:14:45'),
(23, 1, 'database', 505, '/backups/site1/eshop_db_20250118.sql', 'completed', '2025-01-18 03:00:00', '2025-01-18 03:14:15', '2025-01-18 00:14:15'),
(24, 1, 'database', 503, '/backups/site1/eshop_db_20250117.sql', 'completed', '2025-01-17 03:00:00', '2025-01-17 03:13:40', '2025-01-17 00:13:40'),
(25, 2, 'database', 128, '/backups/site2/company_db_20250120.sql', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:08:20', '2025-01-20 01:08:20'),
(26, 2, 'database', 126, '/backups/site2/company_db_20250119.sql', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:07:45', '2025-01-19 01:07:45'),
(27, 2, 'database', 125, '/backups/site2/company_db_20250118.sql', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:07:15', '2025-01-18 01:07:15'),
(28, 3, 'database', 2048, '/backups/site3/lms_db_20250120.sql', 'completed', '2025-01-20 02:00:00', '2025-01-20 02:45:30', '2025-01-19 23:45:30'),
(29, 3, 'database', 2040, '/backups/site3/lms_db_20250119.sql', 'completed', '2025-01-19 02:00:00', '2025-01-19 02:44:15', '2025-01-18 23:44:15'),
(30, 3, 'database', 2035, '/backups/site3/lms_db_20250118.sql', 'completed', '2025-01-18 02:00:00', '2025-01-18 02:43:45', '2025-01-17 23:43:45'),
(31, 4, 'database', 768, '/backups/site4/forum_db_20250120.sql', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:22:30', '2025-01-20 02:22:30'),
(32, 4, 'database', 765, '/backups/site4/forum_db_20250119.sql', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:21:45', '2025-01-19 02:21:45'),
(33, 4, 'database', 762, '/backups/site4/forum_db_20250118.sql', 'completed', '2025-01-18 05:00:00', '2025-01-18 05:21:15', '2025-01-18 02:21:15'),
(34, 5, 'database', 64, '/backups/site5/blog_db_20250120.sql', 'completed', '2025-01-20 06:00:00', '2025-01-20 06:04:30', '2025-01-20 03:04:30'),
(35, 5, 'database', 63, '/backups/site5/blog_db_20250119.sql', 'completed', '2025-01-19 06:00:00', '2025-01-19 06:04:15', '2025-01-19 03:04:15'),
(36, 1, 'files', 1536, '/backups/site1/eshop_files_20250120.zip', 'completed', '2025-01-20 04:00:00', '2025-01-20 04:30:15', '2025-01-20 01:30:15'),
(37, 1, 'files', 1532, '/backups/site1/eshop_files_20250119.zip', 'completed', '2025-01-19 04:00:00', '2025-01-19 04:29:45', '2025-01-19 01:29:45'),
(38, 1, 'files', 1530, '/backups/site1/eshop_files_20250118.zip', 'completed', '2025-01-18 04:00:00', '2025-01-18 04:29:15', '2025-01-18 01:29:15'),
(39, 2, 'files', 384, '/backups/site2/company_files_20250120.zip', 'completed', '2025-01-20 05:00:00', '2025-01-20 05:12:30', '2025-01-20 02:12:30'),
(40, 2, 'files', 382, '/backups/site2/company_files_20250119.zip', 'completed', '2025-01-19 05:00:00', '2025-01-19 05:12:15', '2025-01-19 02:12:15'),
(41, 3, 'files', 6144, '/backups/site3/lms_files_20250120.zip', 'completed', '2025-01-20 03:00:00', '2025-01-20 03:45:30', '2025-01-20 00:45:30'),
(42, 3, 'files', 6138, '/backups/site3/lms_files_20250119.zip', 'completed', '2025-01-19 03:00:00', '2025-01-19 03:44:45', '2025-01-19 00:44:45'),
(43, 4, 'files', 2304, '/backups/site4/forum_files_20250120.zip', 'completed', '2025-01-20 06:00:00', '2025-01-20 06:28:30', '2025-01-20 03:28:30'),
(44, 4, 'files', 2300, '/backups/site4/forum_files_20250119.zip', 'completed', '2025-01-19 06:00:00', '2025-01-19 06:27:45', '2025-01-19 03:27:45'),
(45, 1, 'full', 1024, '/backups/site1/eshop_full_20250121_in_progress.zip', 'in_progress', '2025-01-21 02:00:00', NULL, '2025-01-20 23:00:00'),
(46, 3, 'database', 1024, '/backups/site3/lms_db_20250121_in_progress.sql', 'in_progress', '2025-01-21 02:00:00', NULL, '2025-01-20 23:00:00'),
(47, 2, 'full', 0, '/backups/site2/company_full_20250115_failed.zip', 'failed', '2025-01-15 03:00:00', '2025-01-15 03:05:30', '2025-01-15 00:05:30'),
(48, 4, 'database', 0, '/backups/site4/forum_db_20250114_failed.sql', 'failed', '2025-01-14 05:00:00', '2025-01-14 05:03:15', '2025-01-14 02:03:15'),
(49, 5, 'full', 0, '/backups/site5/blog_full_20250121_pending.zip', 'pending', NULL, NULL, '2025-01-20 23:00:00'),
(50, 3, 'files', 0, '/backups/site3/lms_files_20250121_pending.zip', 'pending', NULL, NULL, '2025-01-20 23:00:00'),
(51, 1, 'full', 2010, '/backups/site1/eshop_full_20250110.zip', 'completed', '2025-01-10 02:00:00', '2025-01-10 02:40:15', '2025-01-09 23:40:15'),
(52, 1, 'full', 2005, '/backups/site1/eshop_full_20250103.zip', 'completed', '2025-01-03 02:00:00', '2025-01-03 02:39:30', '2025-01-02 23:39:30'),
(53, 2, 'full', 500, '/backups/site2/company_full_20250110.zip', 'completed', '2025-01-10 03:00:00', '2025-01-10 03:16:45', '2025-01-10 00:16:45'),
(54, 2, 'full', 498, '/backups/site2/company_full_20250103.zip', 'completed', '2025-01-03 03:00:00', '2025-01-03 03:16:15', '2025-01-03 00:16:15'),
(55, 3, 'full', 8150, '/backups/site3/lms_full_20250110.zip', 'completed', '2025-01-10 01:00:00', '2025-01-10 02:22:30', '2025-01-09 23:22:30'),
(56, 3, 'full', 8140, '/backups/site3/lms_full_20250103.zip', 'completed', '2025-01-03 01:00:00', '2025-01-03 02:21:15', '2025-01-02 23:21:15');

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_databases`
--

CREATE TABLE `hosting_databases` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `db_name` varchar(100) NOT NULL,
  `db_user` varchar(100) NOT NULL,
  `db_password` varchar(255) NOT NULL,
  `db_host` varchar(100) DEFAULT 'localhost',
  `db_type` enum('mysql','postgresql','mongodb') DEFAULT 'mysql',
  `db_version` varchar(20) DEFAULT NULL,
  `db_size` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_backup` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_databases`
--

INSERT INTO `hosting_databases` (`id`, `site_id`, `db_name`, `db_user`, `db_password`, `db_host`, `db_type`, `db_version`, `db_size`, `created_at`, `last_backup`) VALUES
(1, 1, 'db_ecommerce', 'user_ecommerce', 'pass123456', 'localhost', 'mysql', NULL, 1850, '2026-02-21 01:05:59', NULL),
(2, 1, 'db_ecommerce_logs', 'user_ecommerce_logs', 'log789012', 'localhost', 'mysql', NULL, 420, '2026-02-21 01:05:59', NULL),
(3, 1, 'db_ecommerce_cache', 'user_ecommerce_cache', 'cache345678', 'localhost', 'mysql', NULL, 180, '2026-02-21 01:05:59', NULL),
(4, 2, 'db_mobileapp', 'user_mobile', 'pass789012', 'localhost', 'mysql', NULL, 560, '2026-02-21 01:05:59', NULL),
(5, 3, 'db_security', 'user_security', 'pass345678', 'localhost', 'mysql', NULL, 380, '2026-02-21 01:05:59', NULL),
(6, 5, 'db_hr', 'user_hr', 'pass567890', 'localhost', 'mysql', NULL, 1240, '2026-02-21 01:05:59', NULL),
(7, 5, 'db_hr_reports', 'user_hr_reports', 'report123456', 'localhost', 'mysql', NULL, 320, '2026-02-21 01:05:59', NULL),
(8, 6, 'db_corporate', 'user_corporate', 'pass123789', 'localhost', 'mysql', NULL, 490, '2026-02-21 01:05:59', NULL),
(9, 9, 'db_newcompany', 'user_newcompany', 'newpass123', 'localhost', 'mysql', NULL, 210, '2026-02-21 01:05:59', NULL),
(10, 10, 'db_consulting', 'user_consulting', 'consult456', 'localhost', 'mysql', NULL, 280, '2026-02-21 01:05:59', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_ftp_accounts`
--

CREATE TABLE `hosting_ftp_accounts` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `home_directory` varchar(500) DEFAULT NULL,
  `permissions` enum('read','write','execute','full') DEFAULT 'full',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_ftp_accounts`
--

INSERT INTO `hosting_ftp_accounts` (`id`, `site_id`, `username`, `password_hash`, `home_directory`, `permissions`, `created_at`, `last_login`) VALUES
(1, 1, 'ecommerce_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/ecommerce-store.com', 'full', '2026-02-21 01:05:59', NULL),
(2, 2, 'mobile_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client1/mobileapp.com', 'full', '2026-02-21 01:05:59', NULL),
(3, 3, 'security_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client2/security-scan.net', 'full', '2026-02-21 01:05:59', NULL),
(4, 4, 'pentest_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client2/pentest-lab.com', 'write', '2026-02-21 01:05:59', NULL),
(5, 5, 'hr_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client3/hr-system.org', 'full', '2026-02-21 01:05:59', NULL),
(6, 6, 'corporate_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client4/corporate-site.com', 'full', '2026-02-21 01:05:59', NULL),
(7, 9, 'newcompany_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client7/new-company.com', 'full', '2026-02-21 01:05:59', NULL),
(8, 10, 'consulting_ftp', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', '/home/client8/consulting.sa', 'full', '2026-02-21 01:05:59', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_plans`
--

CREATE TABLE `hosting_plans` (
  `id` int(11) NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_type` enum('basic','advanced','professional','custom') DEFAULT 'basic',
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) NOT NULL,
  `disk_space` int(11) NOT NULL COMMENT 'بالميجابايت',
  `bandwidth` int(11) NOT NULL COMMENT 'بالميجابايت',
  `domains_limit` int(11) DEFAULT 1,
  `databases_limit` int(11) DEFAULT 5,
  `emails_limit` int(11) DEFAULT 10,
  `subdomains_limit` int(11) DEFAULT 5,
  `ftp_accounts` int(11) DEFAULT 1,
  `backup_type` enum('none','weekly','daily','realtime') DEFAULT 'weekly',
  `backup_retention` int(11) DEFAULT 7 COMMENT 'أيام',
  `ssl_certificate` tinyint(1) DEFAULT 1,
  `dedicated_ip` tinyint(1) DEFAULT 0,
  `priority_support` tinyint(1) DEFAULT 0,
  `features` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_popular` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_plans`
--

INSERT INTO `hosting_plans` (`id`, `plan_code`, `plan_name`, `plan_type`, `price_monthly`, `price_yearly`, `disk_space`, `bandwidth`, `domains_limit`, `databases_limit`, `emails_limit`, `subdomains_limit`, `ftp_accounts`, `backup_type`, `backup_retention`, `ssl_certificate`, `dedicated_ip`, `priority_support`, `features`, `description`, `is_popular`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'PLAN-BASIC-001', 'الخطة الأساسية', 'basic', 99.00, 990.00, 10240, 102400, 1, 5, 10, 5, 1, 'weekly', 7, 1, 0, 0, 'شهادة SSL مجانية، لوحة تحكم، نطاق مجاني للسنة الأولى', NULL, 0, 1, 0, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(2, 'PLAN-ADV-001', 'الخطة المتقدمة', 'advanced', 199.00, 1990.00, 51200, 512000, 5, 20, 50, 5, 1, 'daily', 7, 1, 0, 0, 'شهادة SSL مجانية، لوحة تحكم متقدمة، نطاق مجاني، نسخ احتياطي يومي، دعم فوري', NULL, 1, 1, 0, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(3, 'PLAN-PRO-001', 'الخطة الاحترافية', 'professional', 299.00, 2990.00, 102400, 1048576, 0, 0, 0, 5, 1, 'realtime', 7, 1, 0, 0, 'شهادة SSL متقدمة، لوحة تحكم مخصصة، نطاق مجاني، نسخ احتياطي فوري، دعم 24/7، تسريع CDN، IP مخصص', NULL, 0, 1, 0, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(4, 'PLAN-BUS-001', 'خطة الأعمال', 'custom', 499.00, 4990.00, 204800, 2097152, 0, 0, 0, 5, 1, 'realtime', 7, 1, 0, 0, 'جميع مزايا الخطة الاحترافية + خادم مخصص + دعم VIP', NULL, 0, 1, 0, '2026-02-21 01:05:59', '2026-02-21 01:05:59'),
(5, 'PLAN-ECOMM-001', 'خطة المتاجر', 'custom', 399.00, 3990.00, 153600, 1572864, 10, 50, 100, 5, 1, 'daily', 7, 1, 0, 0, 'مخصصة للمتاجر الإلكترونية، شهادة SSL متقدمة، دعم فوري، أدوات تحسين محركات البحث', NULL, 0, 1, 0, '2026-02-21 01:05:59', '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_security_logs`
--

CREATE TABLE `hosting_security_logs` (
  `id` bigint(20) NOT NULL,
  `site_id` int(11) NOT NULL,
  `event_type` enum('login','logout','failed_login','file_change','permission_change','malware_detected','attack_detected') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_security_logs`
--

INSERT INTO `hosting_security_logs` (`id`, `site_id`, `event_type`, `severity`, `ip_address`, `description`, `created_at`) VALUES
(1, 1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح من لوحة التحكم', '2026-02-21 00:34:44'),
(2, 1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح - جلسة جديدة', '2026-02-20 21:34:44'),
(3, 2, 'logout', 'info', '45.67.89.123', 'تسجيل خروج من لوحة التحكم', '2026-02-20 02:34:44'),
(4, 3, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات النظام: wp-config.php', '2026-02-20 02:34:44'),
(5, 1, 'failed_login', 'warning', '45.67.89.123', 'محاولة دخول فاشلة - كلمة مرور خاطئة (3 مرات)', '2026-02-20 02:34:44'),
(6, 2, 'failed_login', 'warning', '89.123.45.67', 'محاولات دخول فاشلة متعددة من نفس IP', '2026-02-20 02:34:44'),
(7, 2, 'failed_login', 'critical', '89.123.45.67', 'هجوم تخمين كلمات مرور - تم حظر IP', '2026-02-20 02:34:44'),
(8, 1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح من جهاز معروف', '2026-02-19 02:34:44'),
(9, 3, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات ملف حساس', '2026-02-19 02:34:44'),
(10, 4, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح - مستخدم جديد', '2026-02-19 02:34:44'),
(11, 5, 'attack_detected', 'critical', '45.67.89.123', 'هجوم SQL Injection تم التصدي له', '2026-02-18 02:34:44'),
(12, 5, 'attack_detected', 'critical', '45.67.89.123', 'محاولة حقن قاعدة بيانات', '2026-02-18 02:34:44'),
(13, 2, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات القالب', '2026-02-18 02:34:44'),
(14, 1, 'login', 'info', '192.168.1.100', 'تسجيل دخول ناجح', '2026-02-18 02:34:44'),
(15, 3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', '2026-02-18 02:34:44'),
(16, 1, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف ملف مشبوه في المجلد العام', '2026-02-14 02:34:44'),
(17, 1, 'malware_detected', 'critical', '45.67.89.123', 'برمجية خبيثة في ملف index.php', '2026-02-14 02:34:44'),
(18, 1, 'file_change', 'warning', '45.67.89.123', 'تغيير في ملفات النظام', '2026-02-14 02:34:44'),
(19, 2, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', '2026-02-14 02:34:44'),
(20, 4, 'failed_login', 'warning', '112.34.56.78', 'محاولة دخول فاشلة', '2026-02-14 02:34:44'),
(21, 2, 'attack_detected', 'critical', '45.67.89.123', 'هجوم XSS على نموذج البحث', '2026-02-11 02:34:44'),
(22, 2, 'attack_detected', 'critical', '45.67.89.123', 'محاولة تنفيذ سكريبت ضار', '2026-02-11 02:34:44'),
(23, 3, 'file_change', 'info', '103.45.67.89', 'تحديث آمن للملفات', '2026-02-11 02:34:44'),
(24, 5, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', '2026-02-11 02:34:44'),
(25, 1, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات مجلد', '2026-02-11 02:34:44'),
(26, 3, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف ثغرة أمنية في الإضافة', '2026-02-07 02:34:44'),
(27, 3, 'file_change', 'warning', '45.67.89.123', 'تغيير في ملفات الإضافات', '2026-02-07 02:34:44'),
(28, 4, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', '2026-02-07 02:34:44'),
(29, 2, 'failed_login', 'info', '89.123.45.67', 'محاولة دخول فاشلة منسية', '2026-02-07 02:34:44'),
(30, 1, 'logout', 'info', '192.168.1.100', 'تسجيل خروج', '2026-02-07 02:34:44'),
(31, 4, 'attack_detected', 'critical', '45.67.89.123', 'هجوم DDoS تم التصدي له', '2026-02-01 02:34:44'),
(32, 4, 'attack_detected', 'critical', '45.67.89.123', 'هجوم تخمين عنيف', '2026-02-01 02:34:44'),
(33, 5, 'file_change', 'warning', '103.45.67.89', 'تغيير في ملفات النظام', '2026-02-01 02:34:44'),
(34, 2, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', '2026-02-01 02:34:44'),
(35, 3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', '2026-02-01 02:34:44'),
(36, 1, 'failed_login', 'warning', '45.67.89.123', 'محاولة دخول فاشلة', '2026-01-27 02:34:44'),
(37, 2, 'permission_change', 'info', '103.45.67.89', 'تحديث صلاحيات المجلدات', '2026-01-27 02:34:44'),
(38, 3, 'login', 'info', '103.45.67.89', 'تسجيل دخول ناجح', '2026-01-27 02:34:44'),
(39, 4, 'logout', 'info', '78.90.12.34', 'تسجيل خروج', '2026-01-27 02:34:44'),
(40, 5, 'login', 'info', '78.90.12.34', 'تسجيل دخول ناجح', '2026-01-27 02:34:44'),
(41, 1, 'login', 'info', '192.168.1.100', 'دخول روتيني للوحة التحكم', '2026-02-20 14:34:44'),
(42, 1, 'login', 'info', '192.168.1.100', 'جلسة عمل جديدة', '2026-02-20 08:34:44'),
(43, 2, 'login', 'info', '78.90.12.34', 'دخول مساء', '2026-02-19 02:34:44'),
(44, 3, 'file_change', 'info', '103.45.67.89', 'نسخ احتياطي تلقائي', '2026-02-17 02:34:44'),
(45, 4, 'login', 'info', '78.90.12.34', 'دخول صباحي', '2026-02-16 02:34:44'),
(46, 5, 'logout', 'info', '78.90.12.34', 'خروج من النظام', '2026-02-15 02:34:44'),
(47, 2, 'failed_login', 'warning', '89.123.45.67', 'محاولة دخول من دولة غير معتادة', '2026-02-19 02:34:44'),
(48, 3, 'permission_change', 'warning', '103.45.67.89', 'تغيير صلاحيات ملف مهم', '2026-02-18 02:34:44'),
(49, 4, 'file_change', 'warning', '103.45.67.89', 'تغيير غير متوقع في الملفات', '2026-02-16 02:34:44'),
(50, 5, 'failed_login', 'warning', '45.67.89.123', 'محاولات دخول متعددة', '2026-02-15 02:34:44'),
(51, 1, 'file_change', 'warning', '45.67.89.123', 'تعديل ملف خارج ساعات العمل', '2026-02-14 02:34:44'),
(52, 1, 'attack_detected', 'critical', '45.67.89.123', 'هجوم من نوع RFI', '2026-02-17 02:34:44'),
(53, 2, 'malware_detected', 'critical', '45.67.89.123', 'اكتشاف backdoor في النظام', '2026-02-13 02:34:44'),
(54, 3, 'attack_detected', 'critical', '45.67.89.123', 'هجوم حجب خدمة (DoS)', '2026-02-09 02:34:44'),
(55, 4, 'malware_detected', 'critical', '45.67.89.123', 'برمجية فدية محتملة', '2026-02-06 02:34:44'),
(56, 5, 'attack_detected', 'critical', '45.67.89.123', 'هجوم على قاعدة البيانات', '2026-02-03 02:34:44');

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_sites`
--

CREATE TABLE `hosting_sites` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_path` varchar(500) DEFAULT NULL,
  `document_root` varchar(500) DEFAULT NULL,
  `php_version` enum('5.6','7.0','7.1','7.2','7.3','7.4','8.0','8.1','8.2') DEFAULT '8.1',
  `database_name` varchar(100) DEFAULT NULL,
  `database_user` varchar(100) DEFAULT NULL,
  `database_password` varchar(255) DEFAULT NULL,
  `ftp_username` varchar(100) DEFAULT NULL,
  `ftp_password` varchar(255) DEFAULT NULL,
  `ftp_home` varchar(500) DEFAULT NULL,
  `status` enum('pending','active','suspended','expired') DEFAULT 'pending',
  `setup_status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activated_at` datetime DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `suspended_at` datetime DEFAULT NULL,
  `last_backup` datetime DEFAULT NULL,
  `last_accessed` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_sites`
--

INSERT INTO `hosting_sites` (`id`, `client_id`, `project_id`, `plan_id`, `domain_id`, `site_name`, `site_path`, `document_root`, `php_version`, `database_name`, `database_user`, `database_password`, `ftp_username`, `ftp_password`, `ftp_home`, `status`, `setup_status`, `created_at`, `activated_at`, `expires_at`, `suspended_at`, `last_backup`, `last_accessed`, `notes`) VALUES
(1, 1, 1, 2, 1, 'موقع التجارة الإلكترونية', NULL, '/home/client1/ecommerce-store.com', '8.1', 'db_ecommerce', NULL, NULL, 'ftp_ecommerce', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-01-20 10:30:00', '2025-01-20', NULL, '2025-01-20 02:45:30', NULL, NULL),
(2, 1, 3, 2, 2, 'تطبيق الجوال', NULL, '/home/client1/mobileapp.com', '8.0', 'db_mobileapp', NULL, NULL, 'ftp_mobile', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-02-25 14:20:00', '2025-02-25', NULL, '2025-01-20 03:20:45', NULL, NULL),
(3, 2, 4, 1, 3, 'موقع الفحص الأمني', NULL, '/home/client2/security-scan.net', '7.4', 'db_security', NULL, NULL, 'ftp_security', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-02-10 09:15:00', '2025-02-10', NULL, '2025-01-20 02:30:15', NULL, NULL),
(4, 2, 5, 2, 4, 'بوابة اختبار الاختراق', NULL, '/home/client2/pentest-lab.com', '8.1', NULL, NULL, NULL, 'ftp_pentest', NULL, NULL, 'pending', 'in_progress', '2026-02-21 01:05:59', NULL, NULL, NULL, '2025-01-20 04:50:30', NULL, NULL),
(5, 3, 6, 3, 5, 'نظام الموارد البشرية', NULL, '/home/client3/hr-system.org', '8.1', 'db_hr', NULL, NULL, 'ftp_hr', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-01-25 11:45:00', '2025-01-25', NULL, '2025-01-20 05:12:20', NULL, NULL),
(6, 4, 8, 1, 6, 'موقع الشركة', NULL, '/home/client4/corporate-site.com', '7.4', 'db_corporate', NULL, NULL, 'ftp_corporate', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-01-10 13:30:00', '2025-01-10', NULL, NULL, NULL, NULL),
(7, 5, 9, 1, 7, 'موقع تجريبي', NULL, '/home/client5/test-project.com', '7.4', NULL, NULL, NULL, 'ftp_test', NULL, NULL, 'suspended', 'failed', '2026-02-21 01:05:59', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 6, 10, 1, 8, 'نظام الأرشفة', NULL, '/home/client6/archive-system.com', '8.1', NULL, NULL, NULL, 'ftp_archive', NULL, NULL, 'pending', 'pending', '2026-02-21 01:05:59', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 7, 0, 2, 9, 'موقع شركة جديد', NULL, '/home/client7/new-company.com', '8.1', 'db_newcompany', NULL, NULL, 'ftp_newcompany', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-03-12 09:00:00', '2025-03-12', NULL, NULL, NULL, NULL),
(10, 8, 0, 1, 10, 'موقع استشارات', NULL, '/home/client8/consulting.sa', '7.4', 'db_consulting', NULL, NULL, 'ftp_consulting', NULL, NULL, 'active', 'completed', '2026-02-21 01:05:59', '2024-03-05 15:30:00', '2025-03-05', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_stats`
--

CREATE TABLE `hosting_stats` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `disk_usage` int(11) DEFAULT 0 COMMENT 'بالميجابايت',
  `bandwidth_usage` int(11) DEFAULT 0 COMMENT 'بالميجابايت',
  `inodes_usage` int(11) DEFAULT 0,
  `databases_count` int(11) DEFAULT 0,
  `emails_count` int(11) DEFAULT 0,
  `subdomains_count` int(11) DEFAULT 0,
  `ftp_accounts_count` int(11) DEFAULT 0,
  `daily_visitors` int(11) DEFAULT 0,
  `monthly_visitors` int(11) DEFAULT 0,
  `cpu_usage` decimal(5,2) DEFAULT 0.00,
  `memory_usage` decimal(5,2) DEFAULT 0.00,
  `stat_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `hosting_stats`
--

INSERT INTO `hosting_stats` (`id`, `site_id`, `disk_usage`, `bandwidth_usage`, `inodes_usage`, `databases_count`, `emails_count`, `subdomains_count`, `ftp_accounts_count`, `daily_visitors`, `monthly_visitors`, `cpu_usage`, `memory_usage`, `stat_date`, `created_at`) VALUES
(1, 1, 2450, 15200, 0, 3, 8, 0, 0, 1250, 37500, 32.50, 41.20, '2026-02-21', '2026-02-21 01:05:59'),
(2, 1, 2500, 15800, 0, 3, 8, 0, 0, 1300, 39000, 33.10, 42.00, '2026-02-20', '2026-02-21 01:05:59'),
(3, 1, 2550, 16300, 0, 3, 8, 0, 0, 1350, 40500, 33.80, 42.50, '2026-02-19', '2026-02-21 01:05:59'),
(4, 2, 890, 5600, 0, 1, 2, 0, 0, 450, 13500, 18.30, 22.70, '2026-02-21', '2026-02-21 01:05:59'),
(5, 3, 560, 3200, 0, 2, 4, 0, 0, 320, 9600, 12.80, 18.50, '2026-02-21', '2026-02-21 01:05:59'),
(6, 5, 1850, 12400, 0, 4, 12, 0, 0, 980, 29400, 28.90, 35.60, '2026-02-21', '2026-02-21 01:05:59'),
(7, 6, 780, 4300, 0, 2, 5, 0, 0, 560, 16800, 15.60, 20.30, '2026-02-21', '2026-02-21 01:05:59'),
(8, 9, 320, 2100, 0, 1, 3, 0, 0, 120, 3600, 8.50, 12.40, '2026-02-21', '2026-02-21 01:05:59'),
(9, 10, 450, 2800, 0, 1, 3, 0, 0, 180, 5400, 10.20, 14.80, '2026-02-21', '2026-02-21 01:05:59');

-- --------------------------------------------------------

--
-- بنية الجدول `hosting_support_requests`
--

CREATE TABLE `hosting_support_requests` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `request_type` enum('technical','billing','upgrade','downgrade','cancellation') DEFAULT 'technical',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('breach','outage','attack','data_loss','compliance') NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL,
  `status` enum('open','in-progress','resolved','closed') DEFAULT 'open',
  `impact` text DEFAULT NULL,
  `affected_servers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`affected_servers`)),
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_time` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `incidents`
--

INSERT INTO `incidents` (`id`, `name`, `description`, `type`, `severity`, `status`, `impact`, `affected_servers`, `detected_at`, `resolved_at`, `resolution_time`, `assigned_to`, `unit_id`, `created_by`) VALUES
(1, 'هجوم DDoS', 'هجوم حجب خدمة على خوادم الويب', 'attack', 'critical', 'in-progress', 'تأثير على سرعة المواقع', NULL, '2026-02-16 02:07:23', NULL, NULL, 1, NULL, 1),
(2, 'انقطاع خادم التطبيقات', 'خادم التطبيقات 02 توقف عن العمل', 'outage', 'high', 'open', 'خدمة التطبيقات غير متاحة', NULL, '2026-02-16 03:07:23', NULL, NULL, 2, NULL, 3),
(3, 'محاولات اختراق متكررة', 'محاولات تخمين كلمات مرور من مصادر متعددة', 'attack', 'high', 'in-progress', 'زيادة محاولات الدخول الفاشلة', NULL, '2026-02-16 01:07:23', NULL, NULL, 3, NULL, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `kpi_metrics`
--

CREATE TABLE `kpi_metrics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `metric_name` varchar(100) DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `kpi_metrics`
--

INSERT INTO `kpi_metrics` (`id`, `metric_date`, `unit_id`, `metric_name`, `metric_value`, `target_value`, `created_at`) VALUES
(1, '2026-02-17', 1, 'productivity', 92.00, 90.00, '2026-02-16 23:30:45'),
(2, '2026-02-17', 2, 'productivity', 85.00, 90.00, '2026-02-16 23:30:45'),
(3, '2026-02-17', 3, 'productivity', 78.00, 90.00, '2026-02-16 23:30:45'),
(4, '2026-02-17', 4, 'productivity', 88.00, 90.00, '2026-02-16 23:30:45'),
(5, '2026-02-17', 1, 'quality', 95.00, 95.00, '2026-02-16 23:30:45'),
(6, '2026-02-17', 2, 'quality', 92.00, 95.00, '2026-02-16 23:30:45'),
(7, '2026-02-17', 3, 'quality', 85.00, 95.00, '2026-02-16 23:30:45'),
(8, '2026-02-17', 4, 'quality', 90.00, 95.00, '2026-02-16 23:30:45');

-- --------------------------------------------------------

--
-- بنية الجدول `live_threats`
--

CREATE TABLE `live_threats` (
  `id` int(11) NOT NULL,
  `threat_type` enum('ddos','brute_force','sql_injection','xss','malware') NOT NULL,
  `count` int(11) DEFAULT 0,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `live_threats`
--

INSERT INTO `live_threats` (`id`, `threat_type`, `count`, `severity`, `last_seen`, `is_active`) VALUES
(1, 'ddos', 3, 'critical', '2026-02-16 23:30:45', 1),
(2, 'brute_force', 5, 'high', '2026-02-16 23:30:45', 1),
(3, 'sql_injection', 4, 'medium', '2026-02-16 23:30:45', 1);

-- --------------------------------------------------------

--
-- بنية الجدول `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL,
  `log_type` enum('security','system','network','application') NOT NULL,
  `level` enum('error','warning','info','debug') NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `logs`
--

INSERT INTO `logs` (`id`, `log_type`, `level`, `source`, `user_id`, `server_id`, `event_type`, `description`, `ip_address`, `created_at`) VALUES
(1, 'security', 'warning', 'Firewall', 2, 1, 'Access Denied', 'محاولة وصول مرفوضة من IP 45.123.67.89', '45.123.67.89', '2026-02-16 03:37:23'),
(2, 'system', 'info', 'Backup', NULL, NULL, 'Backup Completed', 'اكتمال النسخ الاحتياطي اليومي', NULL, '2026-02-16 03:32:23'),
(3, 'security', 'error', 'IDS', NULL, 2, 'SQL Injection', 'محاولة حقن SQL مكتشفة', '78.45.123.22', '2026-02-16 03:27:23');

-- --------------------------------------------------------

--
-- بنية الجدول `network_events`
--

CREATE TABLE `network_events` (
  `id` bigint(20) NOT NULL,
  `event_type` enum('inbound','outbound') NOT NULL,
  `bandwidth_used` decimal(10,2) DEFAULT NULL,
  `protocol` varchar(10) DEFAULT NULL,
  `source_ip` varchar(45) DEFAULT NULL,
  `destination_ip` varchar(45) DEFAULT NULL,
  `source_port` int(11) DEFAULT NULL,
  `destination_port` int(11) DEFAULT NULL,
  `connection_status` enum('established','closed','timeout','blocked') DEFAULT NULL,
  `server_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `network_events`
--

INSERT INTO `network_events` (`id`, `event_type`, `bandwidth_used`, `protocol`, `source_ip`, `destination_ip`, `source_port`, `destination_port`, `connection_status`, `server_id`, `created_at`) VALUES
(1, 'inbound', 2.40, 'HTTPS', '45.123.67.89', '192.168.1.100', NULL, NULL, 'established', 1, '2026-02-16 04:07:23'),
(2, 'outbound', 1.80, 'HTTPS', '192.168.1.100', '8.8.8.8', NULL, NULL, 'established', 1, '2026-02-16 04:07:23'),
(3, 'inbound', 3.20, 'HTTP', '78.45.123.22', '192.168.1.102', NULL, NULL, 'blocked', 3, '2026-02-16 04:02:23');

-- --------------------------------------------------------

--
-- بنية الجدول `pending_approvals`
--

CREATE TABLE `pending_approvals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('budget','hire','policy','project','other') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_date` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `pending_approvals`
--

INSERT INTO `pending_approvals` (`id`, `title`, `description`, `type`, `amount`, `requester_id`, `unit_id`, `status`, `priority`, `request_date`, `review_date`, `reviewed_by`, `notes`) VALUES
(1, 'ترقية خادم', 'طلب ترقية خادم قاعدة البيانات الرئيسي', 'budget', 50000.00, 3, 2, 'pending', 'high', '2026-02-16 23:30:45', NULL, NULL, NULL),
(2, 'تعيين جديد', 'طلب تعيين محلل أمني جديد', 'hire', 120000.00, 1, 3, 'pending', 'high', '2026-02-16 23:30:45', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `pentest_activity_log`
--

CREATE TABLE `pentest_activity_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('scan','report','vulnerability','tool','alert','recommendation') NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `pentest_activity_log`
--

INSERT INTO `pentest_activity_log` (`id`, `user_id`, `activity_type`, `action`, `target_type`, `target_id`, `description`, `duration`, `result`, `metadata`, `created_at`) VALUES
(1, 1, 'scan', 'start', 'scan', 1, 'بدء فحص شامل لنظام البنك الأهلي', NULL, NULL, NULL, '2024-01-28 07:30:00'),
(2, 2, 'scan', 'complete', 'scan', 2, 'اكتمال فحص الثغرات لمنصة الحكومة', 9000, '8 ثغرات مكتشفة', NULL, '2024-01-27 08:30:00'),
(3, 1, 'vulnerability', 'discover', 'vuln', 1, 'اكتشاف ثغرة SQL Injection حرجة', NULL, 'CVSS 9.8', NULL, '2024-01-28 07:45:00'),
(4, 3, 'report', 'generate', 'report', 3, 'إنشاء تقرير أمني مفصل', 1800, 'PDF Report', NULL, '2024-01-26 12:00:00'),
(5, 1, 'tool', 'use', 'tool', 2, 'تشغيل Nessus لفحص الثغرات', 3600, '15 ثغرة مكتشفة', NULL, '2024-01-28 06:00:00'),
(6, 2, 'alert', 'resolve', 'alert', 5, 'حل تنبيه تكوين الخادم', NULL, 'تم تحديث الإعدادات', NULL, '2024-01-28 11:30:00'),
(7, 1, 'vulnerability', 'update', 'vuln', 4, 'تحديث حالة الثغرة', NULL, 'قيد المعالجة', NULL, '2024-01-28 10:15:00'),
(8, 3, 'scan', 'start', 'scan', 3, 'بدء فحص منافذ الشبكة', NULL, NULL, NULL, '2024-01-26 11:00:00'),
(9, 3, 'scan', 'complete', 'scan', 3, 'اكتمال فحص المنافذ', 2700, '22 منفذ مفتوح', NULL, '2024-01-26 11:45:00');

-- --------------------------------------------------------

--
-- بنية الجدول `pentest_projects`
--

CREATE TABLE `pentest_projects` (
  `id` int(11) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','in-progress','completed','cancelled') DEFAULT 'pending',
  `severity` enum('critical','high','medium','low') DEFAULT 'medium',
  `tester_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `vulnerabilities_critical` int(11) DEFAULT 0,
  `vulnerabilities_high` int(11) DEFAULT 0,
  `vulnerabilities_medium` int(11) DEFAULT 0,
  `vulnerabilities_low` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `pentest_projects`
--

INSERT INTO `pentest_projects` (`id`, `project_code`, `project_name`, `client_name`, `status`, `severity`, `tester_id`, `start_date`, `deadline`, `progress`, `vulnerabilities_critical`, `vulnerabilities_high`, `vulnerabilities_medium`, `vulnerabilities_low`, `description`, `created_at`, `updated_at`) VALUES
(1, 'P2024-001', 'نظام البنك الأهلي', 'البنك الأهلي السعودي', 'in-progress', 'critical', 1, '2024-01-25', '2024-02-10', 65, 5, 8, 2, 0, 'فحص أمني شامل للنظام المصرفي يشمل جميع التطبيقات والبنية التحتية', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(2, 'P2024-002', 'منصة الحكومة الإلكترونية', 'وزارة التقنية', 'in-progress', 'high', 2, '2024-01-28', '2024-02-15', 40, 0, 3, 4, 1, 'تقييم أمني لمنصة الخدمات الحكومية', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(3, 'P2024-003', 'موقع التجارة الإلكترونية', 'شركة التجارة', 'completed', 'medium', 3, '2024-01-20', '2024-01-30', 100, 0, 1, 2, 2, 'فحص أمني لموقع تسوق إلكتروني', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(4, 'P2024-004', 'نظام إدارة الموارد البشرية', 'شركة الموارد', 'pending', 'medium', 1, '2024-02-01', '2024-02-20', 0, 0, 0, 0, 0, 'تقييم أمان نظام إدارة الموظفين', '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(5, 'P2024-005', 'تطبيق الهاتف المصرفي', 'بنك الرياض', 'pending', 'high', 2, '2024-02-05', '2024-02-25', 0, 0, 0, 0, 0, 'اختبار اختراق لتطبيق الهاتف المصرفي', '2026-02-18 00:55:52', '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `productivity` decimal(5,2) DEFAULT NULL,
  `quality` decimal(5,2) DEFAULT NULL,
  `speed` decimal(5,2) DEFAULT NULL,
  `employee_count` int(11) DEFAULT NULL,
  `active_projects` int(11) DEFAULT NULL,
  `completed_tasks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `performance_metrics`
--

INSERT INTO `performance_metrics` (`id`, `metric_date`, `unit_id`, `productivity`, `quality`, `speed`, `employee_count`, `active_projects`, `completed_tasks`) VALUES
(1, '2026-02-17', 1, 92.00, 95.00, 88.00, 5, 6, NULL),
(2, '2026-02-17', 2, 85.00, 92.00, 85.00, 12, 15, NULL),
(3, '2026-02-17', 3, 78.00, 85.00, 78.00, 7, 42, NULL),
(4, '2026-02-17', 4, 88.00, 90.00, 85.00, 3, 5, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `permissions`
--

INSERT INTO `permissions` (`id`, `permission_id`, `name`, `category`, `description`, `created_at`) VALUES
(1, 'view_users', 'عرض المستخدمين', 'users', NULL, '2026-02-28 03:16:12'),
(2, 'create_users', 'إنشاء مستخدمين', 'users', NULL, '2026-02-28 03:16:12'),
(3, 'edit_users', 'تعديل المستخدمين', 'users', NULL, '2026-02-28 03:16:12'),
(4, 'delete_users', 'حذف المستخدمين', 'users', NULL, '2026-02-28 03:16:12'),
(5, 'manage_roles', 'إدارة الأدوار', 'users', NULL, '2026-02-28 03:16:12'),
(6, 'view_projects', 'عرض المشاريع', 'projects', NULL, '2026-02-28 03:16:12'),
(7, 'create_projects', 'إنشاء مشاريع', 'projects', NULL, '2026-02-28 03:16:12'),
(8, 'edit_projects', 'تعديل المشاريع', 'projects', NULL, '2026-02-28 03:16:12'),
(9, 'delete_projects', 'حذف المشاريع', 'projects', NULL, '2026-02-28 03:16:12'),
(10, 'assign_projects', 'تعيين المشاريع', 'projects', NULL, '2026-02-28 03:16:12'),
(11, 'view_files', 'عرض الملفات', 'files', NULL, '2026-02-28 03:16:12'),
(12, 'upload_files', 'رفع ملفات', 'files', NULL, '2026-02-28 03:16:12'),
(13, 'download_files', 'تحميل ملفات', 'files', NULL, '2026-02-28 03:16:12'),
(14, 'delete_files', 'حذف ملفات', 'files', NULL, '2026-02-28 03:16:12'),
(15, 'scan_files', 'فحص الملفات', 'files', NULL, '2026-02-28 03:16:12'),
(16, 'view_security', 'عرض الأمان', 'security', NULL, '2026-02-28 03:16:12'),
(17, 'manage_security', 'إدارة الأمان', 'security', NULL, '2026-02-28 03:16:12'),
(18, 'view_logs', 'عرض السجلات', 'security', NULL, '2026-02-28 03:16:12'),
(19, 'manage_alerts', 'إدارة التنبيهات', 'security', NULL, '2026-02-28 03:16:12'),
(20, 'run_scans', 'تشغيل فحوصات', 'security', NULL, '2026-02-28 03:16:12'),
(21, 'view_reports', 'عرض التقارير', 'reports', NULL, '2026-02-28 03:16:12'),
(22, 'create_reports', 'إنشاء تقارير', 'reports', NULL, '2026-02-28 03:16:12'),
(23, 'export_reports', 'تصدير التقارير', 'reports', NULL, '2026-02-28 03:16:12'),
(24, 'schedule_reports', 'جدولة تقارير', 'reports', NULL, '2026-02-28 03:16:12'),
(25, 'view_settings', 'عرض الإعدادات', 'system', NULL, '2026-02-28 03:16:12'),
(26, 'edit_settings', 'تعديل الإعدادات', 'system', NULL, '2026-02-28 03:16:12'),
(27, 'view_audit', 'عرض التدقيق', 'system', NULL, '2026-02-28 03:16:12'),
(28, 'manage_backups', 'إدارة النسخ', 'system', NULL, '2026-02-28 03:16:12');

-- --------------------------------------------------------

--
-- بنية الجدول `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `status` enum('documentation','testing','deployment','completed','delayed') DEFAULT 'documentation',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `progress` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `projects`
--

INSERT INTO `projects` (`id`, `code`, `name`, `client_name`, `unit_id`, `status`, `priority`, `progress`, `start_date`, `deadline`, `manager_id`, `budget`, `description`, `created_at`, `updated_at`) VALUES
(1, 'P-1019', 'بنك الأهلي - ترقية الأمان', 'بنك الأهلي', 4, 'testing', 'critical', 45, NULL, '2024-02-01', 4, 250000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(2, 'P-1023', 'وزارة الصحة - توثيق النظام', 'وزارة الصحة', 1, 'documentation', 'high', 70, NULL, '2024-01-30', 2, 180000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(3, 'P-1025', 'شركة الاتصالات - استضافة الموقع', 'شركة الاتصالات', 2, 'deployment', 'medium', 90, NULL, '2024-02-15', 3, 320000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(4, 'P-1026', 'تطوير بوابة الدفع', 'بنك الرياض', 3, 'testing', 'high', 60, NULL, '2024-02-10', 1, 280000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(5, 'P-1027', 'نظام إدارة المحتوى', 'وزارة التعليم', 2, 'deployment', 'medium', 85, NULL, '2024-01-25', 3, 150000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(6, 'P-1028', 'اختبار اختراق شامل', 'شركة التأمين', 4, 'testing', 'critical', 30, NULL, '2024-02-20', 4, 200000.00, NULL, '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(8, 'po1', 'mohammed ', 'hilal', 2, 'testing', 'high', 0, '2026-02-18', '2024-01-25', 3, 150.00, 'بناء موقع ويب متكامللل ', '2026-02-17 21:52:46', '2026-02-17 21:52:46');

-- --------------------------------------------------------

--
-- بنية الجدول `project_comments`
--

CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `project_files`
--

CREATE TABLE `project_files` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `project_tasks`
--

CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_code` varchar(50) NOT NULL,
  `report_title` varchar(500) NOT NULL,
  `report_type` enum('monthly','security','technical','progress','final','audit','compliance') DEFAULT 'technical',
  `recipient` enum('manager','client','security','storage','pentest','admin','team') DEFAULT 'manager',
  `priority` enum('normal','high','urgent') DEFAULT 'normal',
  `status` enum('preparing','ready','sent','approved','rejected','archived') DEFAULT 'preparing',
  `format` enum('pdf','docx','html','xlsx') DEFAULT 'pdf',
  `file_path` varchar(500) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sent_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `reports`
--

INSERT INTO `reports` (`id`, `report_code`, `report_title`, `report_type`, `recipient`, `priority`, `status`, `format`, `file_path`, `summary`, `notes`, `sent_date`, `approved_date`, `created_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'RPT-MON-001', 'التقرير الشهري - يناير 2024', 'monthly', 'manager', 'high', 'approved', 'pdf', '/reports/monthly/jan-2024.pdf', 'ملخص أداء وحدة التوثيق لشهر يناير', 'تم إنجاز 15 مستند ومراجعة 8', '2024-02-01', '2024-01-31', 1, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(2, 'RPT-SEC-001', 'تقرير الأمن السيبراني - الربع الأول', 'security', 'security', 'urgent', 'sent', 'pdf', '/reports/security/q1-2024.pdf', 'تقييم أمني شامل للأنظمة', 'تم اكتشاف 12 ثغرة وعلاج 8 منها', '2024-03-15', NULL, 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(3, 'RPT-TECH-001', 'تقرير التقدم الفني - مشروع الاستضافة', 'progress', 'client', 'high', 'archived', 'pdf', '/reports/progress/hosting-feb.pdf', 'تقدم مشروع نظام الاستضافة', 'اكتمال مرحلة التصميم وبدء التطوير', '2024-02-28', '2024-02-27', 1, NULL, '2026-02-18 22:19:47', '2026-02-20 20:50:17'),
(4, 'RPT-TECH-002', 'تقرير التقدم - منصة التخزين', 'progress', 'manager', 'normal', 'sent', 'pdf', '/reports/progress/storage-mar.pdf', 'تقدم منصة التخزين السحابي', 'تم الانتهاء من توثيق API', '2026-02-20', NULL, 2, NULL, '2026-02-18 22:19:47', '2026-02-20 20:50:31'),
(5, 'RPT-SEC-002', 'تقرير التدقيق الأمني - البنية التحتية', 'audit', 'security', 'high', 'approved', 'pdf', '/reports/audit/infrastructure-mar.pdf', 'نتائج تدقيق أمن البنية التحتية', 'جميع الأنظمة متوافقة مع المعايير', '2024-03-20', '2024-03-19', 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(6, 'RPT-MON-002', 'التقرير الشهري - فبراير 2024', 'monthly', 'manager', 'normal', 'sent', 'pdf', '/reports/monthly/feb-2024.pdf', 'ملخص أداء وحدة التوثيق لشهر فبراير', 'تم إنجاز 12 مستند ومراجعة 10', '2024-03-01', NULL, 1, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(7, 'RPT-FIN-001', 'التقرير النهائي - مشروع البنك الأهلي', 'final', 'client', 'high', 'approved', 'pdf', '/reports/final/bank-project.pdf', 'تقرير إنجاز مشروع البنك الأهلي', 'تم تسليم جميع المستندات المطلوبة', '2024-02-15', '2024-02-14', 2, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 'RPT-COMP-001', 'تقرير الامتثال - ISO 27001', 'compliance', 'admin', '', 'approved', 'pdf', '/reports/compliance/iso27001.pdf', 'تقييم الامتثال لمعايير ISO 27001', '85% من المتطلبات متحققة', '2024-03-10', '2024-03-09', 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 'RPT-SEC-003', 'تقرير اختبار الاختراق', 'security', 'security', 'urgent', 'sent', 'pdf', '/reports/security/penetration-test.pdf', 'نتائج اختبار الاختراق للنظام', 'تم اكتشاف 5 ثغرات حرجة', '2024-03-18', NULL, 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 'RPT-PROG-001', 'تقرير التقدم - الربع الأول', 'progress', 'manager', 'high', 'ready', 'pdf', '/reports/progress/q1-2024.pdf', 'ملخص إنجازات الربع الأول', '45 مستند تم إنجازها', NULL, NULL, 1, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(11, 'RPT-TECH-003', 'تقرير فني - أداء API', 'technical', 'team', 'normal', 'ready', 'pdf', '/reports/technical/api-performance.pdf', 'تحليل أداء واجهات API', 'متوسط زمن الاستجابة 150ms', NULL, NULL, 2, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 'RPT-SEC-004', 'تقرير الثغرات الأمنية', 'security', 'security', 'high', 'sent', 'pdf', '/reports/security/vulnerabilities.pdf', 'تقرير دوري عن الثغرات المكتشفة', 'تم معالجة 15 ثغرة من أصل 20', '2024-03-05', NULL, 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 'RPT-MON-003', 'التقرير الشهري - مارس 2024', 'monthly', 'manager', 'high', 'preparing', 'pdf', NULL, 'ملخص أداء وحدة التوثيق لشهر مارس', 'قيد الإعداد', NULL, NULL, 1, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 'RPT-AUD-001', 'تقرير تدقيق المستندات', 'audit', 'admin', 'normal', 'ready', 'xlsx', '/reports/audit/document-audit.xlsx', 'تدقيق شامل لجميع المستندات', '98% من المستندات متوافقة', NULL, NULL, 4, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 'RPT-FIN-002', 'التقرير النهائي - مشروع الشبكات', 'final', 'client', 'high', 'approved', 'pdf', '/reports/final/network-project.pdf', 'تقرير إنجاز مشروع إدارة الشبكات', 'تم تسليم جميع المستندات والتدريب', '2024-03-25', '2024-03-24', 4, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `report_documents`
--

CREATE TABLE `report_documents` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `included_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `report_documents`
--

INSERT INTO `report_documents` (`id`, `report_id`, `document_id`, `included_at`) VALUES
(2, 1, 2, '2026-02-18 22:19:47'),
(3, 1, 3, '2026-02-18 22:19:47'),
(4, 1, 4, '2026-02-18 22:19:47'),
(5, 1, 5, '2026-02-18 22:19:47'),
(6, 2, 3, '2026-02-18 22:19:47'),
(7, 2, 8, '2026-02-18 22:19:47'),
(8, 2, 9, '2026-02-18 22:19:47'),
(9, 2, 10, '2026-02-18 22:19:47'),
(10, 2, 11, '2026-02-18 22:19:47'),
(11, 2, 17, '2026-02-18 22:19:47'),
(13, 3, 2, '2026-02-18 22:19:47'),
(14, 3, 3, '2026-02-18 22:19:47'),
(15, 3, 4, '2026-02-18 22:19:47'),
(16, 3, 5, '2026-02-18 22:19:47'),
(17, 4, 6, '2026-02-18 22:19:47'),
(19, 4, 14, '2026-02-18 22:19:47'),
(20, 5, 8, '2026-02-18 22:19:47'),
(21, 5, 9, '2026-02-18 22:19:47'),
(22, 5, 10, '2026-02-18 22:19:47'),
(23, 5, 16, '2026-02-18 22:19:47'),
(24, 5, 17, '2026-02-18 22:19:47'),
(26, 6, 4, '2026-02-18 22:19:47'),
(27, 6, 12, '2026-02-18 22:19:47'),
(28, 6, 13, '2026-02-18 22:19:47'),
(29, 6, 19, '2026-02-18 22:19:47'),
(31, 7, 2, '2026-02-18 22:19:47'),
(32, 7, 3, '2026-02-18 22:19:47'),
(33, 7, 4, '2026-02-18 22:19:47'),
(34, 7, 5, '2026-02-18 22:19:47'),
(36, 8, 3, '2026-02-18 22:19:47'),
(37, 8, 8, '2026-02-18 22:19:47'),
(38, 8, 9, '2026-02-18 22:19:47'),
(39, 8, 10, '2026-02-18 22:19:47'),
(40, 9, 3, '2026-02-18 22:19:47'),
(41, 9, 8, '2026-02-18 22:19:47'),
(42, 9, 11, '2026-02-18 22:19:47'),
(43, 9, 17, '2026-02-18 22:19:47'),
(45, 10, 4, '2026-02-18 22:19:47'),
(46, 10, 6, '2026-02-18 22:19:47'),
(47, 10, 8, '2026-02-18 22:19:47'),
(48, 10, 12, '2026-02-18 22:19:47'),
(49, 10, 15, '2026-02-18 22:19:47'),
(50, 10, 16, '2026-02-18 22:19:47'),
(51, 10, 19, '2026-02-18 22:19:47'),
(52, 11, 6, '2026-02-18 22:19:47'),
(54, 11, 14, '2026-02-18 22:19:47'),
(55, 12, 3, '2026-02-18 22:19:47'),
(56, 12, 8, '2026-02-18 22:19:47'),
(57, 12, 9, '2026-02-18 22:19:47'),
(58, 12, 10, '2026-02-18 22:19:47'),
(59, 12, 11, '2026-02-18 22:19:47'),
(60, 12, 17, '2026-02-18 22:19:47'),
(62, 14, 2, '2026-02-18 22:19:47'),
(63, 14, 3, '2026-02-18 22:19:47'),
(64, 14, 4, '2026-02-18 22:19:47'),
(65, 14, 5, '2026-02-18 22:19:47'),
(66, 14, 6, '2026-02-18 22:19:47'),
(68, 14, 8, '2026-02-18 22:19:47'),
(69, 14, 9, '2026-02-18 22:19:47'),
(70, 14, 10, '2026-02-18 22:19:47'),
(71, 15, 15, '2026-02-18 22:19:47'),
(72, 15, 16, '2026-02-18 22:19:47'),
(73, 15, 17, '2026-02-18 22:19:47'),
(74, 15, 18, '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `report_statistics`
--

CREATE TABLE `report_statistics` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `total_alerts` int(11) DEFAULT 0,
  `total_threats` int(11) DEFAULT 0,
  `total_incidents` int(11) DEFAULT 0,
  `total_servers` int(11) DEFAULT 0,
  `avg_cpu` decimal(5,2) DEFAULT 0.00,
  `avg_memory` decimal(5,2) DEFAULT 0.00,
  `avg_storage` decimal(5,2) DEFAULT 0.00,
  `uptime_percentage` decimal(5,2) DEFAULT 0.00,
  `blocked_attacks` int(11) DEFAULT 0,
  `critical_events` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `report_statistics`
--

INSERT INTO `report_statistics` (`id`, `report_id`, `total_alerts`, `total_threats`, `total_incidents`, `total_servers`, `avg_cpu`, `avg_memory`, `avg_storage`, `uptime_percentage`, `blocked_attacks`, `critical_events`) VALUES
(1, 1, 24, 8, 0, 0, 0.00, 0.00, 0.00, 99.98, 0, 5),
(2, 2, 18, 5, 0, 0, 0.00, 0.00, 0.00, 99.99, 0, 3),
(3, 3, 12, 3, 0, 0, 0.00, 0.00, 0.00, 99.97, 0, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `report_templates`
--

CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('client-summary','technical-detailed','follow-up','compliance','executive') NOT NULL,
  `description` text DEFAULT NULL,
  `template_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_structure`)),
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `default_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_settings`)),
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `report_templates`
--

INSERT INTO `report_templates` (`id`, `name`, `type`, `description`, `template_structure`, `sections`, `default_settings`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'تقرير مختصر للعميل', 'client-summary', 'تقرير موجز للمديرين غير التقنيين', NULL, NULL, NULL, 1, '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(2, 'تقرير فني مفصل', 'technical-detailed', 'تقرير تفصيلي للفرق التقنية مع شروح وأكواد', NULL, NULL, NULL, 1, '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(3, 'تقرير متابعة', 'follow-up', 'تقرير متابعة الإصلاحات والتحسينات', NULL, NULL, NULL, 2, '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(4, 'تقرير الامتثال', 'compliance', 'تقرير مطابقة للمعايير الأمنية', NULL, NULL, NULL, 1, '2026-02-18 00:55:52', '2026-02-18 00:55:52'),
(5, 'تقرير تنفيذي', 'executive', 'تقرير للإدارة العليا', NULL, NULL, NULL, 2, '2026-02-18 00:55:52', '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `repository_files`
--

CREATE TABLE `repository_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `last_accessed` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `repository_files`
--

INSERT INTO `repository_files` (`id`, `file_name`, `file_path`, `file_type`, `file_size`, `mime_type`, `folder_path`, `project_id`, `document_id`, `uploaded_by`, `version`, `checksum`, `is_encrypted`, `download_count`, `last_accessed`, `created_at`, `updated_at`) VALUES
(1, 'requirements_v1.2.pdf', '/repositories/hosting-project/requirements_v1.2.pdf', 'pdf', 2450000, 'application/pdf', '/hosting-project/documents', 1, NULL, 1, '1.2', NULL, 0, 15, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(2, 'installation_v1.0.pdf', '/repositories/hosting-project/installation_v1.0.pdf', 'pdf', 1850000, 'application/pdf', '/hosting-project/documents', 1, 2, 1, '1.0', NULL, 0, 8, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(3, 'security-test_v2.1.pdf', '/repositories/hosting-project/security-test_v2.1.pdf', 'pdf', 3200000, 'application/pdf', '/hosting-project/documents', 1, 3, 3, '2.1', NULL, 0, 12, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(4, 'user-guide_v1.5.pdf', '/repositories/hosting-project/user-guide_v1.5.pdf', 'pdf', 4250000, 'application/pdf', '/hosting-project/documents', 1, 4, 1, '1.5', NULL, 0, 25, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(5, 'admin-guide_v1.2.pdf', '/repositories/hosting-project/admin-guide_v1.2.pdf', 'pdf', 2100000, 'application/pdf', '/hosting-project/documents', 1, 5, 3, '1.2', NULL, 0, 10, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(6, 'api-docs_v0.9.html', '/repositories/cloud-storage/api-docs_v0.9.html', 'html', 950000, 'text/html', '/cloud-storage/api', 2, 6, 2, '0.9', NULL, 0, 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(7, 'examples_v0.5.md', '/repositories/cloud-storage/examples_v0.5.md', 'md', 45000, 'text/markdown', '/cloud-storage/examples', 2, NULL, 2, '0.5', NULL, 0, 3, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(8, 'infrastructure_v1.0.pdf', '/repositories/security-system/infrastructure_v1.0.pdf', 'pdf', 5600000, 'application/pdf', '/security-system/audits', 3, 8, 5, '1.0', NULL, 0, 20, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(9, 'architecture_v2.3.pdf', '/repositories/security-system/architecture_v2.3.pdf', 'pdf', 3800000, 'application/pdf', '/security-system/architecture', 3, 9, 5, '2.3', NULL, 0, 18, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(10, 'configuration_v1.1.pdf', '/repositories/security-system/configuration_v1.1.pdf', 'pdf', 1950000, 'application/pdf', '/security-system/config', 3, 10, 3, '1.1', NULL, 0, 14, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(11, 'penetration-test_v1.0.pdf', '/repositories/security-system/penetration-test_v1.0.pdf', 'pdf', 1250000, 'application/pdf', '/security-system/tests', 3, 11, 3, '1.0', NULL, 0, 9, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(12, 'user-guide_v0.8.pdf', '/repositories/ecommerce-platform/user-guide_v0.8.pdf', 'pdf', 2850000, 'application/pdf', '/ecommerce-platform/guides', 4, 12, 2, '0.8', NULL, 0, 6, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(13, 'requirements_v1.0.pdf', '/repositories/ecommerce-platform/requirements_v1.0.pdf', 'pdf', 3150000, 'application/pdf', '/ecommerce-platform/docs', 4, 13, 4, '1.0', NULL, 0, 7, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(14, 'payment-api_v0.5.md', '/repositories/ecommerce-platform/payment-api_v0.5.md', 'md', 250000, 'text/markdown', '/ecommerce-platform/api', 4, 14, 2, '0.5', NULL, 0, 4, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(15, 'installation_v1.2.pdf', '/repositories/network-management/installation_v1.2.pdf', 'pdf', 1850000, 'application/pdf', '/network-management/install', 5, 15, 4, '1.2', NULL, 0, 11, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(16, 'architecture_v2.0.pdf', '/repositories/network-management/architecture_v2.0.pdf', 'pdf', 4200000, 'application/pdf', '/network-management/design', 5, 16, 4, '2.0', NULL, 0, 16, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(17, 'security-assessment_v1.0.pdf', '/repositories/network-management/security-assessment_v1.0.pdf', 'pdf', 2350000, 'application/pdf', '/network-management/security', 5, 17, 5, '1.0', NULL, 0, 8, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(18, 'troubleshooting_v0.9.pdf', '/repositories/network-management/troubleshooting_v0.9.pdf', 'pdf', 1650000, 'application/pdf', '/network-management/support', 5, 18, 2, '0.9', NULL, 0, 5, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(19, 'q1-progress_v1.0.pdf', '/repositories/reports/q1-progress_v1.0.pdf', 'pdf', 980000, 'application/pdf', '/reports/quarterly', 1, 19, 1, '1.0', NULL, 0, 22, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(20, 'monthly-performance_v1.1.pdf', '/repositories/reports/monthly-performance_v1.1.pdf', 'pdf', 1120000, 'application/pdf', '/reports/monthly', 2, 20, 3, '1.1', NULL, 0, 13, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(21, 'network-diagram.png', '/repositories/network-management/network-diagram.png', 'png', 850000, 'image/png', '/network-management/images', 5, NULL, 4, '1.0', NULL, 0, 30, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(22, 'security-logo.svg', '/repositories/security-system/security-logo.svg', 'svg', 45000, 'image/svg+xml', '/security-system/assets', 3, NULL, 5, '1.0', NULL, 0, 12, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(23, 'api-schema.json', '/repositories/cloud-storage/api-schema.json', 'json', 125000, 'application/json', '/cloud-storage/schemas', 2, NULL, 2, '1.2', NULL, 0, 8, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(24, 'database-schema.sql', '/repositories/hosting-project/database-schema.sql', 'sql', 350000, 'application/sql', '/hosting-project/db', 1, NULL, 1, '2.1', NULL, 0, 15, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(25, 'deployment-script.sh', '/repositories/hosting-project/deployment-script.sh', 'sh', 25000, 'application/x-shellscript', '/hosting-project/scripts', 1, NULL, 3, '1.5', NULL, 0, 20, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(26, 'config-template.yaml', '/repositories/security-system/config-template.yaml', 'yaml', 18000, 'application/x-yaml', '/security-system/templates', 3, NULL, 5, '1.0', NULL, 0, 25, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(27, 'backup-script.ps1', '/repositories/network-management/backup-script.ps1', 'ps1', 32000, 'application/x-powershell', '/network-management/scripts', 5, NULL, 4, '2.0', NULL, 0, 10, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(28, 'training-materials.pptx', '/repositories/ecommerce-platform/training-materials.pptx', 'pptx', 5250000, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '/ecommerce-platform/training', 4, NULL, 2, '1.0', NULL, 0, 7, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(29, 'security-policy.docx', '/repositories/security-system/security-policy.docx', 'docx', 1850000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/security-system/policies', 3, NULL, 5, '3.2', NULL, 0, 18, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(30, 'requirements-template.docx', '/repositories/templates/requirements-template.docx', 'docx', 450000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/technical', NULL, NULL, 1, '2.0', NULL, 0, 45, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(31, 'security-report-template.docx', '/repositories/templates/security-report-template.docx', 'docx', 520000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '1.8', NULL, 0, 38, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(32, 'user-manual-template.docx', '/repositories/templates/user-manual-template.docx', 'docx', 680000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/guides', NULL, NULL, 1, '3.0', NULL, 0, 52, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(33, 'api-doc-template.md', '/repositories/templates/api-doc-template.md', 'md', 35000, 'text/markdown', '/templates/api', NULL, NULL, 2, '1.3', NULL, 0, 27, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(34, 'architecture-template.docx', '/repositories/templates/architecture-template.docx', 'docx', 580000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/technical', NULL, NULL, 4, '1.1', NULL, 0, 19, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(35, 'project-plan-template.xlsx', '/repositories/templates/project-plan-template.xlsx', 'xlsx', 890000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '/templates/management', NULL, NULL, 1, '1.0', NULL, 0, 23, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(36, 'risk-assessment-template.docx', '/repositories/templates/risk-assessment-template.docx', 'docx', 420000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '2.2', NULL, 0, 16, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(37, 'incident-report-template.docx', '/repositories/templates/incident-report-template.docx', 'docx', 380000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/security', NULL, NULL, 5, '1.4', NULL, 0, 12, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(38, 'code-review-template.md', '/repositories/templates/code-review-template.md', 'md', 28000, 'text/markdown', '/templates/development', NULL, NULL, 2, '1.0', NULL, 0, 9, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(39, 'deployment-plan-template.docx', '/repositories/templates/deployment-plan-template.docx', 'docx', 490000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/operations', NULL, NULL, 4, '1.2', NULL, 0, 14, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(40, 'meeting-minutes-template.docx', '/repositories/templates/meeting-minutes-template.docx', 'docx', 320000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/management', NULL, NULL, 1, '2.1', NULL, 0, 31, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(41, 'test-plan-template.xlsx', '/repositories/templates/test-plan-template.xlsx', 'xlsx', 750000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '/templates/testing', NULL, NULL, 3, '1.5', NULL, 0, 17, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(42, 'contract-template.docx', '/repositories/templates/contract-template.docx', 'docx', 610000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/legal', NULL, NULL, 5, '3.0', NULL, 0, 11, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(43, 'rfp-template.docx', '/repositories/templates/rfp-template.docx', 'docx', 720000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '/templates/procurement', NULL, NULL, 5, '1.1', NULL, 0, 6, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(44, 'system-diagram.vsdx', '/repositories/network-management/system-diagram.vsdx', 'vsdx', 1850000, 'application/vnd.visio', '/network-management/diagrams', 5, NULL, 4, '1.0', NULL, 0, 22, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(45, 'api-collection.postman.json', '/repositories/cloud-storage/api-collection.postman.json', 'json', 215000, 'application/json', '/cloud-storage/postman', 2, NULL, 2, '1.2', NULL, 0, 35, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(46, 'load-test-script.js', '/repositories/hosting-project/load-test-script.js', 'js', 42000, 'application/javascript', '/hosting-project/tests', 1, NULL, 3, '1.0', NULL, 0, 13, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(47, 'docker-compose.yml', '/repositories/hosting-project/docker-compose.yml', 'yml', 8500, 'application/x-yaml', '/hosting-project/docker', 1, NULL, 1, '2.3', NULL, 0, 28, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(48, 'jenkinsfile', '/repositories/security-system/jenkinsfile', 'groovy', 12500, 'text/plain', '/security-system/ci-cd', 3, NULL, 5, '1.1', NULL, 0, 19, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(49, 'terraform-config.tf', '/repositories/cloud-storage/terraform-config.tf', 'tf', 28000, 'text/plain', '/cloud-storage/terraform', 2, NULL, 4, '0.12', NULL, 0, 15, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(50, 'ansible-playbook.yml', '/repositories/network-management/ansible-playbook.yml', 'yml', 32000, 'application/x-yaml', '/network-management/ansible', 5, NULL, 4, '2.0', NULL, 0, 21, NULL, '2026-02-18 22:19:47', '2026-02-18 22:19:47'),
(51, 'index.php', '/uploads/repository/2026/02/6996799eb0f4f_20260219_034654.php', 'php', 49589, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 02:46:54', '2026-02-19 02:46:54'),
(52, 'index.php', '/uploads/repository/2026/02/699679a202020_20260219_034658.php', 'php', 49589, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 02:46:58', '2026-02-19 02:46:58'),
(53, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:49', '2026-02-19 02:49:49'),
(55, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:51', '2026-02-19 02:49:51'),
(56, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:51', '2026-02-19 02:49:51'),
(58, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:52', '2026-02-19 02:49:52'),
(59, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:52', '2026-02-19 02:49:52'),
(60, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:52', '2026-02-19 02:49:52'),
(61, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:52', '2026-02-19 02:49:52'),
(62, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:49:53', '2026-02-19 02:49:53'),
(63, 'محمد ', '/محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 02:50:08', '2026-02-19 02:50:08'),
(64, 'documentationDB.sql', '/uploads/repository/2026/02/69967c1e77e89_20260219_035734.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 02:57:34', '2026-02-19 02:57:34'),
(65, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:19', '2026-02-19 03:07:19'),
(66, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:21', '2026-02-19 03:07:21'),
(67, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:22', '2026-02-19 03:07:22'),
(68, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:22', '2026-02-19 03:07:22'),
(69, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:23', '2026-02-19 03:07:23'),
(70, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:23', '2026-02-19 03:07:23'),
(71, 'محمد ', '//محمد ', 'folder', 0, 'folder', '/', NULL, NULL, 1, NULL, NULL, 0, 0, NULL, '2026-02-19 03:07:23', '2026-02-19 03:07:23'),
(72, 'documentationDB.sql', '/uploads/repository/2026/02/69967e8ea7f6b_20260219_040758.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:07:58', '2026-02-19 03:07:58'),
(73, 'documentationDB.sql', '/uploads/repository/2026/02/69967e8f93049_20260219_040759.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:07:59', '2026-02-19 03:07:59'),
(74, 'documentationDB.sql', '/uploads/repository/2026/02/69967e8fca564_20260219_040759.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:07:59', '2026-02-19 03:07:59'),
(75, 'documentationDB.sql', '/uploads/repository/2026/02/69967e909fa3b_20260219_040800.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:08:00', '2026-02-19 03:08:00'),
(76, 'documentationDB.sql', '/uploads/repository/2026/02/69967e910cfc6_20260219_040801.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:08:01', '2026-02-19 03:08:01'),
(77, 'documentationDB.sql', '/uploads/repository/2026/02/69967e913f54f_20260219_040801.sql', 'sql', 101653, 'application/octet-stream', '/', NULL, NULL, 1, '1.0', NULL, 0, 0, NULL, '2026-02-19 03:08:01', '2026-02-19 03:08:01');

-- --------------------------------------------------------

--
-- بنية الجدول `resource_requests`
--

CREATE TABLE `resource_requests` (
  `id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `resource_type` enum('equipment','software','personnel','training','other') NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `status` enum('pending','approved','rejected','in-progress') DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_date` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `resource_requests`
--

INSERT INTO `resource_requests` (`id`, `unit_id`, `requester_id`, `title`, `description`, `resource_type`, `amount`, `status`, `priority`, `request_date`, `review_date`, `reviewed_by`, `notes`) VALUES
(1, 2, 3, 'ترقية خادم قاعدة البيانات', 'شراء خادم جديد لقاعدة البيانات الرئيسية', 'equipment', 50000.00, 'pending', 'high', '2026-02-16 23:30:45', NULL, NULL, NULL),
(2, 4, 4, 'أداة اختبار اختراق جديدة', 'ترخيص سنوي لأداة اختبار متقدمة', 'software', 15000.00, 'pending', 'medium', '2026-02-16 23:30:45', NULL, NULL, NULL),
(3, 3, 1, 'تعيين محلل أمني', 'توظيف محلل أمني إضافي للفريق', 'personnel', 120000.00, 'pending', 'high', '2026-02-16 23:30:45', NULL, NULL, NULL),
(4, 1, 2, 'دورة توثيق فني', 'تدريب فريق التوثيق على أدوات جديدة', 'training', 8000.00, 'approved', 'low', '2026-02-16 23:30:45', NULL, NULL, NULL),
(5, 2, 3, 'ترقية خادم قاعدة البيانات', 'شراء خادم جديد لقاعدة البيانات الرئيسية', 'equipment', 50000.00, 'pending', 'high', '2026-02-17 00:43:53', NULL, NULL, NULL),
(6, 4, 4, 'أداة اختبار اختراق جديدة', 'ترخيص سنوي لأداة اختبار متقدمة', 'software', 15000.00, 'pending', 'medium', '2026-02-17 00:43:53', NULL, NULL, NULL),
(7, 3, 1, 'تعيين محلل أمني', 'توظيف محلل أمني إضافي للفريق', 'personnel', 120000.00, 'pending', 'high', '2026-02-17 00:43:53', NULL, NULL, NULL),
(8, 1, 2, 'دورة توثيق فني', 'تدريب فريق التوثيق على أدوات جديدة', 'training', 8000.00, 'approved', 'low', '2026-02-17 00:43:53', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `roles`
--

INSERT INTO `roles` (`id`, `role_id`, `name`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'مدير النظام', 'جميع الصلاحيات', '[\"*\"]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(2, 'manager', 'مدير', 'إدارة المحتوى والمستخدمين', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(3, 'documentation_staff', 'موظف توثيق', 'إدارة المستندات', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(4, 'cloud_storage_staff', 'موظف تخزين سحابي', 'إدارة التخزين', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(5, 'pentest_staff', 'مختبر اختراق', 'اختبارات الأمان', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(6, 'monitoring_staff', 'موظف مراقبة', 'مراقبة النظام', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(7, 'pms_staff', 'مدير مشاريع', 'إدارة المشاريع', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(8, 'finance_staff', 'موظف مالي', 'إدارة الفواتير', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(9, 'ai_staff', 'موظف ذكاء اصطناعي', 'تحليل البيانات', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12'),
(10, 'client', 'عميل', 'صلاحيات العميل', '[]', 1, '2026-02-28 03:16:12', '2026-02-28 03:16:12');

-- --------------------------------------------------------

--
-- بنية الجدول `security_alerts`
--

CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('critical','warning','info') NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `vulnerability_id` int(11) DEFAULT NULL,
  `scan_id` int(11) DEFAULT NULL,
  `status` enum('new','read','in-progress','resolved','ignored') DEFAULT 'new',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `security_alerts`
--

INSERT INTO `security_alerts` (`id`, `title`, `description`, `type`, `source`, `project_id`, `vulnerability_id`, `scan_id`, `status`, `is_read`, `created_at`, `resolved_at`, `resolved_by`) VALUES
(1, 'ثغرة SQL Injection حرجة', 'تم اكتشاف ثغرة SQL Injection في نظام الدفع', 'critical', 'Nessus Scan', 1, 1, NULL, 'new', 0, '2026-02-18 00:40:52', NULL, NULL),
(2, 'محاولة وصول غير مصرح', '10 محاولات فاشلة من IP 192.168.1.100', 'warning', 'IDS', NULL, NULL, NULL, 'new', 0, '2026-02-18 00:25:52', NULL, NULL),
(3, 'تحديثات أمنية متوفرة', '5 تحديثات أمنية لم يتم تثبيتها', 'info', 'System', NULL, NULL, NULL, 'read', 1, '2026-02-17 22:55:52', NULL, NULL),
(4, 'ثغرة XSS مكتشفة', 'ثغرة XSS في صفحة تسجيل الدخول', '', 'Burp Suite', 1, 2, NULL, 'new', 0, '2026-02-17 23:55:52', NULL, NULL),
(5, 'تكوين خادم غير آمن', 'إعدادات خادم الويب تسمح بكشف معلومات', 'warning', 'Nikto', 1, 3, NULL, 'read', 1, '2026-02-17 21:55:52', NULL, NULL),
(6, 'اكتمال فحص', 'اكتمال فحص الثغرات للمنصة الحكومية', 'info', 'OpenVAS', 2, NULL, NULL, 'resolved', 1, '2026-02-17 00:55:52', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `security_policies`
--

CREATE TABLE `security_policies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('password','access','backup','encryption','network','compliance') NOT NULL,
  `priority` enum('high','medium','low') NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `compliance_percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','draft','archived') DEFAULT 'active',
  `version` varchar(20) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `security_policies`
--

INSERT INTO `security_policies` (`id`, `name`, `description`, `category`, `priority`, `scope`, `compliance_percentage`, `status`, `version`, `content`, `created_by`, `approved_by`, `effective_date`, `review_date`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'سياسة كلمات المرور', 'تحدد متطلبات إنشاء كلمات المرور وتغييرها', 'password', 'high', 'all', 98.50, 'active', 'v2.1', 'كلمات المرور يجب أن تكون 8 أحرف على الأقل، تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز', 1, NULL, '2026-02-16', '2026-08-16', '2026-02-16 04:07:22', '2026-02-16 04:07:22', NULL),
(2, 'سياسة الوصول', 'تحدد صلاحيات الوصول للأنظمة المختلفة', 'access', 'high', 'all', 95.20, 'active', 'v3.0', 'صلاحيات الوصول تعتمد على مبدأ least privilege', 1, NULL, '2026-02-16', '2026-05-16', '2026-02-16 04:07:22', '2026-02-16 04:07:22', NULL),
(3, 'سياسة النسخ الاحتياطي', 'تحدد جدول وإجراءات النسخ الاحتياطي', 'backup', 'high', 'servers', 85.00, 'active', 'v1.5', 'نسخ احتياطي يومي كامل، نسخ أسبوعي لأرشفة', 2, NULL, '2026-02-16', '2026-08-16', '2026-02-16 04:07:22', '2026-02-16 04:07:22', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `security_recommendations`
--

CREATE TABLE `security_recommendations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('network','application','data','compliance','training') NOT NULL,
  `priority` enum('critical','high','medium','low') NOT NULL,
  `status` enum('pending','in-progress','implemented','scheduled','cancelled') DEFAULT 'pending',
  `project_id` int(11) DEFAULT NULL,
  `vulnerability_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `implemented_date` date DEFAULT NULL,
  `effort_hours` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `security_recommendations`
--

INSERT INTO `security_recommendations` (`id`, `title`, `description`, `category`, `priority`, `status`, `project_id`, `vulnerability_id`, `assigned_to`, `due_date`, `implemented_date`, `effort_hours`, `created_by`, `created_at`) VALUES
(1, 'تطبيق إعدادات أمان قواعد البيانات', 'تأمين قواعد البيانات لمنع هجمات SQL Injection', 'data', 'critical', 'in-progress', 1, 1, 1, '2024-02-05', NULL, 8, 1, '2026-02-18 00:55:52'),
(2, 'تحديث سياسة كلمات المرور', 'تطبيق متطلبات كلمات مرور قوية', 'compliance', 'high', 'pending', 2, 5, 2, '2024-02-10', NULL, 4, 1, '2026-02-18 00:55:52'),
(3, 'تدريب الموظفين على الأمن السيبراني', 'دورة تدريبية للمطورين حول أمن التطبيقات', 'training', 'medium', 'scheduled', NULL, NULL, 3, '2024-02-20', NULL, 16, 1, '2026-02-18 00:55:52'),
(4, 'تشفير جميع البيانات الحساسة', 'تطبيق تشفير SSL/TLS على جميع الاتصالات', 'application', 'high', 'in-progress', 1, 4, 1, '2024-02-03', NULL, 6, 2, '2026-02-18 00:55:52'),
(5, 'تحديث خادم الويب', 'تحديث إعدادات خادم الويب لمنع كشف المعلومات', 'application', 'medium', 'pending', 1, 3, 1, '2024-02-08', NULL, 3, 2, '2026-02-18 00:55:52'),
(6, 'تنفيذ حماية CSRF', 'إضافة رموز حماية CSRF لجميع النماذج', 'application', 'medium', 'pending', 2, 6, 2, '2024-02-12', NULL, 5, 1, '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `security_scans`
--

CREATE TABLE `security_scans` (
  `id` int(11) NOT NULL,
  `scan_name` varchar(255) NOT NULL,
  `scan_type` enum('comprehensive','vulnerability','port','web','network') NOT NULL,
  `target` varchar(255) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `tool_id` int(11) DEFAULT NULL,
  `status` enum('pending','in-progress','completed','failed','stopped') DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `findings_count` int(11) DEFAULT 0,
  `critical_count` int(11) DEFAULT 0,
  `scan_configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scan_configuration`)),
  `report_path` varchar(500) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `security_scans`
--

INSERT INTO `security_scans` (`id`, `scan_name`, `scan_type`, `target`, `project_id`, `tool_id`, `status`, `started_at`, `completed_at`, `duration`, `findings_count`, `critical_count`, `scan_configuration`, `report_path`, `performed_by`, `created_at`) VALUES
(1, 'فحص شامل - البنك الأهلي', 'comprehensive', 'bank.example.com', 1, 2, 'in-progress', '2024-01-28 07:30:00', NULL, NULL, 15, 5, NULL, NULL, 1, '2026-02-18 00:55:52'),
(2, 'فحص ثغرات - الحكومة', 'vulnerability', 'gov.example.com', 2, 5, 'completed', '2024-01-27 06:00:00', '2024-01-27 08:30:00', 9000, 8, 0, NULL, NULL, 2, '2026-02-18 00:55:52'),
(3, 'فحص منافذ - الشبكة الداخلية', 'port', '192.168.1.0/24', NULL, 1, 'completed', '2024-01-26 11:00:00', '2024-01-26 11:45:00', 2700, 12, 0, NULL, NULL, 1, '2026-02-18 00:55:52'),
(4, 'فحص تطبيقات ويب - التجارة', 'web', 'shop.example.com', 3, 4, 'completed', '2024-01-25 06:30:00', '2024-01-25 09:30:00', 10800, 6, 1, NULL, NULL, 3, '2026-02-18 00:55:52'),
(5, 'فحص شبكة - بنك', 'network', '192.168.10.0/24', 1, 7, 'completed', '2024-01-24 07:00:00', '2024-01-24 10:00:00', 10800, 22, 2, NULL, NULL, 1, '2026-02-18 00:55:52'),
(6, 'فحص استغلال - بنك', 'vulnerability', 'bank.example.com', 1, 3, 'in-progress', '2024-01-28 06:00:00', NULL, NULL, 5, 2, NULL, NULL, 1, '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `security_statistics`
--

CREATE TABLE `security_statistics` (
  `id` bigint(20) NOT NULL,
  `stat_date` date NOT NULL,
  `stat_hour` tinyint(4) DEFAULT NULL,
  `total_attacks` int(11) DEFAULT 0,
  `blocked_attacks` int(11) DEFAULT 0,
  `ddos_attacks` int(11) DEFAULT 0,
  `brute_force_attacks` int(11) DEFAULT 0,
  `sql_injection_attacks` int(11) DEFAULT 0,
  `xss_attacks` int(11) DEFAULT 0,
  `avg_response_time` decimal(10,2) DEFAULT NULL,
  `total_alerts` int(11) DEFAULT 0,
  `critical_alerts` int(11) DEFAULT 0,
  `warning_alerts` int(11) DEFAULT 0,
  `info_alerts` int(11) DEFAULT 0,
  `active_threats` int(11) DEFAULT 0,
  `mitigated_threats` int(11) DEFAULT 0,
  `system_uptime` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `security_statistics`
--

INSERT INTO `security_statistics` (`id`, `stat_date`, `stat_hour`, `total_attacks`, `blocked_attacks`, `ddos_attacks`, `brute_force_attacks`, `sql_injection_attacks`, `xss_attacks`, `avg_response_time`, `total_alerts`, `critical_alerts`, `warning_alerts`, `info_alerts`, `active_threats`, `mitigated_threats`, `system_uptime`, `created_at`) VALUES
(1, '2026-02-16', 0, 45, 43, 0, 0, 0, 0, 1.20, 0, 0, 0, 0, 0, 0, 99.99, '2026-02-16 04:07:23'),
(2, '2026-02-16', 1, 32, 31, 0, 0, 0, 0, 1.10, 0, 0, 0, 0, 0, 0, 99.99, '2026-02-16 04:07:23'),
(3, '2026-02-15', 0, 52, 49, 0, 0, 0, 0, 1.50, 0, 0, 0, 0, 0, 0, 99.98, '2026-02-16 04:07:23'),
(4, '2026-02-14', 0, 68, 64, 0, 0, 0, 0, 1.80, 0, 0, 0, 0, 0, 0, 99.97, '2026-02-16 04:07:23');

-- --------------------------------------------------------

--
-- بنية الجدول `servers`
--

CREATE TABLE `servers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('web','database','application','cache','loadbalancer') NOT NULL,
  `status` enum('online','warning','offline','maintenance') DEFAULT 'online',
  `ip_address` varchar(45) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `cpu_usage` decimal(5,2) DEFAULT 0.00,
  `memory_usage` decimal(5,2) DEFAULT 0.00,
  `storage_usage` decimal(5,2) DEFAULT 0.00,
  `uptime` int(11) DEFAULT 0,
  `last_check` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `servers`
--

INSERT INTO `servers` (`id`, `name`, `type`, `status`, `ip_address`, `location`, `cpu_usage`, `memory_usage`, `storage_usage`, `uptime`, `last_check`) VALUES
(1, 'Web-Server-01', 'web', 'online', '192.168.1.100', 'الرياض', 65.50, 45.20, 32.80, 518400, '2026-02-16 04:07:21'),
(2, 'DB-Server-02', 'database', 'warning', '192.168.1.101', 'الرياض', 85.30, 78.60, 92.40, 432000, '2026-02-16 04:07:21'),
(3, 'App-Server-03', 'application', 'online', '192.168.1.102', 'جدة', 45.80, 60.20, 40.10, 604800, '2026-02-16 04:07:21'),
(4, 'Cache-Server-01', 'cache', 'online', '192.168.1.103', 'الرياض', 72.40, 55.30, 28.50, 345600, '2026-02-16 04:07:21'),
(5, 'Web-Server-02', 'web', 'online', '192.168.1.104', 'جدة', 38.20, 42.80, 35.60, 259200, '2026-02-16 04:07:21'),
(6, 'DB-Server-01', 'database', 'online', '192.168.1.105', 'الرياض', 52.70, 48.90, 55.30, 691200, '2026-02-16 04:07:21'),
(7, 'LoadBalancer-01', 'loadbalancer', 'online', '192.168.1.106', 'الرياض', 25.40, 35.20, 22.10, 777600, '2026-02-16 04:07:21'),
(8, 'App-Server-02', 'application', 'offline', '192.168.1.107', 'الرياض', 0.00, 0.00, 65.80, 0, '2026-02-16 04:07:21');

-- --------------------------------------------------------

--
-- بنية الجدول `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `price` varchar(100) DEFAULT NULL,
  `setup_time` varchar(100) DEFAULT NULL,
  `features` longtext DEFAULT NULL,
  `sla` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `popular` tinyint(1) DEFAULT 0,
  `icon` varchar(50) DEFAULT 'fa-server',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `services`
--

INSERT INTO `services` (`id`, `name`, `category_id`, `description`, `price`, `setup_time`, `features`, `sla`, `status`, `popular`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'استضافة مواقع مشتركة', 1, 'حل استضافة مثالي للمواقع الشخصية والصغيرة مع موارد مشتركة وأداء ممتاز', '49 ريال/شهر', '24 ساعة', '[\"مساحة تخزين: 10GB SSD\", \"نقل بيانات: 100GB/شهر\", \"قواعد بيانات: 5 قواعد\", \"بريد إلكتروني: 10 حسابات\", \"دعم PHP و MySQL\", \"نسخ احتياطي أسبوعي\"]', '99.9%', 'active', 1, 'fa-globe', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(2, 'استضافة VPS مخصصة', 1, 'خوادم افتراضية مخصصة مع موارد مضمونة وتحكم كامل في بيئة الاستضافة', '199 ريال/شهر', '4 ساعات', '[\"معالج: 2 Core vCPU\", \"ذاكرة: 4GB RAM\", \"تخزين: 80GB SSD\", \"نطاق: 4TB/شهر\", \"نظام: اختيار أي نظام\", \"تحكم كامل Root\"]', '99.99%', 'active', 1, 'fa-server', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(3, 'استضافة WordPress محسنة', 1, 'استضافة مخصصة لـ WordPress مع أداء مُحسن وأمان متقدم وتحديثات تلقائية', '99 ريال/شهر', '2 ساعة', '[\"مخبأ متقدم لـ WordPress\", \"تحديثات تلقائية\", \"نسخ احتياطي يومي\", \"تثبيت بنقرة واحدة\", \"قوالب وإضافات مجانية\", \"تحسين للأداء\"]', '99.95%', 'active', 1, 'fab fa-wordpress', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(4, 'استضافة متاجر إلكترونية', 1, 'استضافة متخصصة للمتاجر الإلكترونية مع دعم أنظمة الدفع وحماية المعاملات', '149 ريال/شهر', '6 ساعات', '[\"شهادة SSL مجانية\", \"دعم جميع منصات المتاجر\", \"حماية المعاملات\", \"أداء عالي\", \"نسخ احتياطي يومي\", \"دعم فني 24/7\"]', '99.95%', 'active', 1, 'fa-shopping-cart', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(5, 'استضافة تطبيقات Node.js', 1, 'بيئة استضافة مخصصة لتطبيقات Node.js مع دعم WebSocket وتطبيقات الوقت الحقيقي', '299 ريال/شهر', '4 ساعات', '[\"دعم Node.js 16+\", \"WebSocket مدمج\", \"NPM مثبت مسبقاً\", \"PM2 لإدارة العمليات\", \"نطاق غير محدود\", \"قواعد بيانات MongoDB\"]', '99.9%', 'active', 0, 'fab fa-node-js', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(6, 'استضافة قواعد البيانات', 1, 'استضافة مخصصة لقواعد البيانات مع نسخ احتياطي تلقائي وأداء مُحسن للاستعلامات', '199 ريال/شهر', '2 ساعة', '[\"MySQL / MariaDB\", \"phpMyAdmin\", \"نسخ احتياطي يومي\", \"استعادة سريعة\", \"مراقبة الأداء\", \"تحسين الاستعلامات\"]', '99.99%', 'active', 0, 'fa-database', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(7, 'استضافة تطبيقات Python', 1, 'بيئة استضافة متكاملة لتطبيقات Python مع دعم Django و Flask', '279 ريال/شهر', '4 ساعات', '[\"Python 3.9+\", \"Django / Flask\", \"pip مثبت\", \"بيئة افتراضية\", \"قواعد بيانات PostgreSQL\", \"Celery للمهام\"]', '99.9%', 'active', 0, 'fab fa-python', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(8, 'استضافة تطبيقات Java', 1, 'استضافة متخصصة لتطبيقات Java مع دعم Spring Boot و Tomcat', '349 ريال/شهر', '6 ساعات', '[\"Java 11/17\", \"Apache Tomcat\", \"Spring Boot\", \"Maven/Gradle\", \"ذاكرة مخصصة\", \"قواعد بيانات Oracle\"]', '99.95%', 'active', 0, 'fab fa-java', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(9, 'استضافة خوادم مخصصة', 1, 'خوادم فعلية مخصصة بالكامل مع أعلى أداء وأمان', '999 ريال/شهر', '24 ساعة', '[\"معالج Intel Xeon\", \"ذاكرة 32GB RAM\", \"تخزين 2TB SSD\", \"نطاق غير محدود\", \"IP مخصص\", \"إدارة كاملة\"]', '99.99%', 'active', 0, 'fa-server', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(10, 'استضافة مواقع إعلامية', 1, 'استضافة عالية الأداء للمواقع الإعلامية والمواقع ذات الزيارات العالية', '399 ريال/شهر', '8 ساعات', '[\"CDN مدمج\", \"نطاق غير محدود\", \"ذاكرة تخزين مؤقت\", \"ضغط الصور\", \"تحسين الفيديو\", \"تحليلات متقدمة\"]', '99.95%', 'active', 0, 'fa-newspaper', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(11, 'استضافة Laravel', 1, 'استضافة محسنة لتطبيقات Laravel مع بيئة متكاملة', '249 ريال/شهر', '3 ساعات', '[\"PHP 8.2\", \"Composer\", \"Redis Cache\", \"Horizon\", \"Queue Workers\", \"Envoy\"]', '99.9%', 'active', 0, 'fab fa-laravel', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(12, 'استضافة ألعاب', 1, 'استضافة عالية الأداء لخوادم الألعاب مع تأخير منخفض', '599 ريال/شهر', '12 ساعة', '[\"Minecraft\", \"CS:GO\", \"Ark\", \"Rust\", \"DDoS protection\", \"لوحة تحكم مخصصة\"]', '99.5%', 'active', 0, 'fa-gamepad', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(13, 'جدار حماية متقدم (WAF)', 2, 'جدار حماية تطبيقات ويب متقدم يحمي من الهجمات الشائعة', '99 ريال/شهر', '6 ساعات', '[\"حماية من SQL Injection\", \"حماية من XSS\", \"حماية من CSRF\", \"قوالب حماية مخصصة\", \"سجلات تفصيلية\", \"تقارير شهرية\"]', '100%', 'active', 1, 'fa-shield-alt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(14, 'حماية من هجمات DDoS', 2, 'حماية متقدمة من هجمات حجب الخدمة الموزعة مع اكتشاف وتخفيف فوري', '199 ريال/شهر', '4 ساعات', '[\"حماية حتى 1 Tbps\", \"اكتشاف تلقائي\", \"تخفيف فوري\", \"مراقبة 24/7\", \"تقارير الهجمات\", \"SLA مضمون\"]', '99.99%', 'active', 1, 'fa-bolt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(15, 'فحص الثغرات الأمنية', 2, 'فحص شامل لاكتشاف الثغرات الأمنية في التطبيقات والشبكات والأنظمة', '499 ريال/فحص', '2-3 أيام', '[\"فحص OWASP Top 10\", \"فحص الشبكات\", \"تقرير مفصل\", \"توصيات أمنية\", \"إعادة فحص مجاني\", \"شهادة أمان\"]', '100%', 'active', 1, 'fa-search', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(16, 'اختبار اختراق متقدم', 2, 'محاكاة هجمات حقيقية لتقييم قوة أنظمتك الأمنية واكتشاف نقاط الضعف', '1499 ريال/اختبار', '5-7 أيام', '[\"اختبار تطبيقات ويب\", \"اختبار شبكات\", \"هندسة اجتماعية\", \"تقرير مفصل\", \"جلسة مناقشة\", \"توصيات المعالجة\"]', '100%', 'active', 1, 'fa-user-secret', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(17, 'مراقبة الأمان 24/7', 2, 'مراقبة مستمرة لأنظمتك مع كشف التهديدات والاستجابة الفورية', '299 ريال/شهر', '24 ساعة', '[\"مراقبة على مدار الساعة\", \"كشف التسلل\", \"تنبيهات فورية\", \"تقارير أسبوعية\", \"تحليل سلوكي\", \"استجابة للحوادث\"]', '99.9%', 'active', 0, 'fa-eye', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(18, 'تشفير SSL/TLS', 2, 'شهادات SSL/TLS لتشفير اتصالات موقعك وحماية بيانات الزوار', '199 ريال/سنة', '24 ساعة', '[\"شهادة موثوقة\", \"تشفير 256 بت\", \"دعم جميع المتصفحات\", \"تجديد تلقائي\", \"ختم أمان\", \"ضمان مالي\"]', '100%', 'active', 1, 'fa-lock', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(19, 'حماية التطبيقات (RASP)', 2, 'حماية متقدمة للتطبيقات أثناء التشغيل مع اكتشاف ومنع الهجمات', '399 ريال/شهر', '8 ساعات', '[\"حماية أثناء التشغيل\", \"اكتشاف التهديدات\", \"منع تلقائي\", \"تحليل سلوكي\", \"تقارير لحظية\", \"توافق مع جميع اللغات\"]', '99.9%', 'active', 0, 'fa-code', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(20, 'كشف التسلل (IDS/IPS)', 2, 'نظام متقدم لكشف ومنع محاولات الاختراق على مستوى الشبكة', '249 ريال/شهر', '12 ساعة', '[\"كشف التسلل\", \"منع تلقائي\", \"قواعد محدثة\", \"تحليل حركة المرور\", \"تنبيهات فورية\", \"سجلات مفصلة\"]', '99.5%', 'active', 0, 'fa-network-wired', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(21, 'إدارة الهوية والوصول', 2, 'نظام متكامل لإدارة هويات المستخدمين وصلاحيات الوصول', '449 ريال/شهر', '24 ساعة', '[\"مصادقة متعددة العوامل\", \"إدارة المستخدمين\", \"صلاحيات متقدمة\", \"تسجيل الدخول الموحد\", \"تقارير الوصول\", \"التكامل مع Active Directory\"]', '99.9%', 'active', 0, 'fa-id-card', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(22, 'أمان الشبكات', 2, 'حماية متكاملة للشبكات الداخلية مع تقسيم ومراقبة مستمرة', '599 ريال/شهر', '48 ساعة', '[\"VPN آمن\", \"تقسيم الشبكات\", \"مراقبة الحركة\", \"جدار حماية متقدم\", \"كشف التسلل\", \"تحليل الحزم\"]', '99.9%', 'active', 0, 'fa-project-diagram', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(23, 'تخزين سحابي مشفر', 3, 'مساحات تخزين سحابية مشفرة بنهاية إلى نهاية مع وصول آمن من أي مكان', '49 ريال/شهر', '1 ساعة', '[\"تشفير AES-256\", \"مساحة 100GB\", \"مشاركة آمنة\", \"تزامن تلقائي\", \"تطبيقات جوال\", \"نسخ احتياطي\"]', '99.99%', 'active', 1, 'fa-cloud', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(24, 'نسخ احتياطي تلقائي', 3, 'نسخ احتياطي تلقائي لبياناتك مع إمكانية الاستعادة الفورية عند الحاجة', '99 ريال/شهر', '2 ساعة', '[\"نسخ يومي\", \"احتفاظ 30 يوم\", \"استعادة بنقرة واحدة\", \"ضغط البيانات\", \"تشفير النسخ\", \"تقارير الحالة\"]', '99.99%', 'active', 1, 'fa-save', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(25, 'مشاركة ملفات آمنة', 3, 'مشاركة ملفات آمنة مع تحكم كامل في الصلاحيات ومدة الوصول', '29 ريال/شهر', '1 ساعة', '[\"روابط آمنة\", \"صلاحيات متقدمة\", \"انتهاء تلقائي\", \"حماية بكلمة مرور\", \"تتبع التنزيلات\", \"تعليقات على الملفات\"]', '99.5%', 'active', 0, 'fa-share-alt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(26, 'تزامن عبر الأجهزة', 3, 'تزامن تلقائي لملفاتك عبر جميع أجهزتك مع حفظ أحدث نسخة دائماً', '39 ريال/شهر', '1 ساعة', '[\"تزامن فوري\", \"جميع الأجهزة\", \"حل النزاعات\", \"مجموعات مشاركة\", \"إصدارات الملفات\", \"مجلدات ذكية\"]', '99.5%', 'active', 0, 'fa-sync-alt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(27, 'استعادة البيانات السريعة', 3, 'استعادة سريعة للبيانات المحذوفة أو التالفة من النسخ الاحتياطية', '149 ريال/استعادة', '24 ساعة', '[\"استعادة كاملة\", \"استعادة جزئية\", \"دعم فني متخصص\", \"ضمان الاستعادة\", \"فحص سلامة البيانات\", \"تقرير الاستعادة\"]', '99%', 'active', 1, 'fa-undo', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(28, 'تخزين الأرشيف الطويل', 3, 'تخزين اقتصادي للبيانات الأرشيفية مع إمكانية الوصول عند الحاجة', '9 ريال/GB/شهر', '24 ساعة', '[\"تخزين بارد\", \"استرجاع خلال 24 ساعة\", \"احتفاظ طويل الأمد\", \"تكلفة منخفضة\", \"تشفير البيانات\", \"متوافق مع معايير الأرشفة\"]', '99.5%', 'active', 0, 'fa-archive', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(29, 'تخزين كائنات (Object Storage)', 3, 'تخزين عالي التوسع للملفات والوسائط والنسخ الاحتياطي', '149 ريال/شهر', '4 ساعات', '[\"سعة 1TB\", \"API متوافق مع S3\", \"CDN مدمج\", \"تحميل متعدد الأجزاء\", \"إصدارات الكائنات\", \"سياسات دورة الحياة\"]', '99.95%', 'active', 0, 'fa-cubes', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(30, 'تخزين الكتل (Block Storage)', 3, 'تخزين عالي الأداء للخوادم الافتراضية والتطبيقات', '199 ريال/شهر', '2 ساعة', '[\"أداء عالي IOPS\", \"حتى 500GB\", \"لقطات فورية\", \"توسع مرن\", \"تشفير\", \"نسخ احتياطي تلقائي\"]', '99.99%', 'active', 0, 'fa-hdd', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(31, 'CDN وتسريع المحتوى', 4, 'شبكة تسليم محتوى عالمية لتسريع تحميل موقعك وتحسين تجربة المستخدم', '99 ريال/شهر', '4 ساعات', '[\"أكثر من 50 نقطة تواجد\", \"تسريع الصور\", \"ضغط تلقائي\", \"شهادة SSL مجانية\", \"تحليلات الأداء\", \"تخزين مؤقت ذكي\"]', '99.99%', 'active', 1, 'fa-tachometer-alt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(32, 'موازنة الحمل', 4, 'توزيع الحمل على عدة خوادم لضمان التوافر العالي والأداء الممتاز', '299 ريال/شهر', '8 ساعات', '[\"خوارزميات متعددة\", \"فحص الصحة\", \"توسع تلقائي\", \"تجاوز الأعطال\", \"جلسات مستمرة\", \"مراقبة الأداء\"]', '99.99%', 'active', 1, 'fa-balance-scale', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(33, 'تحليل الأداء', 4, 'مراقبة وتحليل أداء تطبيقاتك مع تقارير مفصلة وتوصيات للتحسين', '149 ريال/شهر', '24 ساعة', '[\"مراقبة 24/7\", \"تقارير الأداء\", \"تحديد الاختناقات\", \"توصيات التحسين\", \"تنبيهات ذكية\", \"لوحة تحكم تفاعلية\"]', '99.5%', 'active', 0, 'fa-chart-line', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(34, 'واجهات برمجة API', 4, 'واجهات برمجة متقدمة للتحكم في خدماتك برمجياً وأتمتة العمليات', '499 ريال/شهر', '48 ساعة', '[\"RESTful API\", \"توثيق كامل\", \"مفاتيح API\", \"محددات معدل\", \"Webhooks\", \"SDKs للغات متعددة\"]', '99.9%', 'active', 1, 'fa-code', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(35, 'تطبيق إدارة جوال', 4, 'تطبيق جوال لإدارة خدماتك ومراقبة أدائها من أي مكان', 'مجاني', 'فوري', '[\"iOS و Android\", \"إشعارات فورية\", \"مراقبة لحظية\", \"إدارة سريعة\", \"تقارير مبسطة\", \"دعم فني\"]', '99%', 'active', 1, 'fa-mobile-alt', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(36, 'دعم فني متميز', 4, 'دعم فني متميز مع وقت استجابة أسرع وأولوية في معالجة طلباتك', '199 ريال/شهر', 'فوري', '[\"دعم 24/7\", \"رد خلال 15 دقيقة\", \"مدير حساب مخصص\", \"استشارات فنية\", \"مراجعات دورية\", \"تدريب الفريق\"]', '100%', 'active', 1, 'fa-headset', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(37, 'استشارات تقنية', 4, 'استشارات متخصصة في البنية التحتية وتحسين الأداء والأمان', '599 ريال/جلسة', 'حسب الاتفاق', '[\"تقييم البنية التحتية\", \"تخطيط التوسع\", \"تحسين الأداء\", \"مراجعة أمنية\", \"توصيات مخصصة\", \"تقرير مفصل\"]', '100%', 'active', 0, 'fa-users-cog', '2026-02-22 23:00:35', '2026-02-22 23:00:35'),
(38, 'تدريب فريقك', 4, 'برامج تدريبية متخصصة لتطوير مهارات فريقك التقنية', '999 ريال/دورة', 'حسب الاتفاق', '[\"مواد تدريبية\", \"تدريب عملي\", \"شهادات معتمدة\", \"متابعة بعد الدورة\", \"تقييم المستوى\", \"دعم مستمر\"]', '100%', 'active', 0, 'fa-chalkboard-teacher', '2026-02-22 23:00:35', '2026-02-22 23:00:35');

-- --------------------------------------------------------

--
-- بنية الجدول `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `request_code` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `request_details` text NOT NULL,
  `status` enum('pending','contacted','in_progress','completed','cancelled') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'نظام الاستضافة الحماية', 'text', NULL, '2026-02-28 03:56:49'),
(2, 'site_url', 'http://localhost/Hosting-Security', 'text', NULL, '2026-02-28 03:56:49'),
(3, 'admin_email', 'admin@example.com', 'text', NULL, '2026-02-28 03:56:49'),
(4, 'timezone', 'Asia/Riyadh', 'text', NULL, '2026-02-28 03:56:49'),
(5, 'date_format', 'Y-m-d', 'text', NULL, '2026-02-28 03:56:49'),
(6, 'language', 'en', 'text', NULL, '2026-02-28 03:56:49'),
(7, 'maintenance_mode', '0', 'text', NULL, '2026-02-28 03:56:49'),
(8, 'debug_mode', '0', 'text', NULL, '2026-02-28 03:56:49'),
(9, 'social_facebook', 'https://facebook.com/', 'text', NULL, '2026-02-28 03:56:01'),
(10, 'social_twitter', 'https://twitter.com/', 'text', NULL, '2026-02-28 03:56:01'),
(11, 'social_instagram', 'https://instagram.com/', 'text', NULL, '2026-02-28 03:56:01'),
(12, 'social_linkedin', 'https://linkedin.com/company/', 'text', NULL, '2026-02-28 03:56:01'),
(13, 'social_youtube', 'https://youtube.com/', 'text', NULL, '2026-02-28 03:56:01'),
(14, 'social_whatsapp', 'https://wa.me/', 'text', NULL, '2026-02-28 03:56:01'),
(15, 'social_telegram', 'https://t.me/', 'text', NULL, '2026-02-28 03:56:01'),
(16, 'social_tiktok', 'https://tiktok.com/@', 'text', NULL, '2026-02-28 03:56:01'),
(17, 'social_snapchat', 'https://snapchat.com/add/', 'text', NULL, '2026-02-28 03:56:01'),
(19, 'site_description', 'نظام متكامل لإدارة الحماية والاستضافة', 'text', NULL, '2026-02-28 03:56:49'),
(20, 'site_keywords', 'حماية, استضافة, أمن معلومات', 'text', NULL, '2026-02-28 03:56:49'),
(21, 'site_logo', '/assets/images/logo.png', 'text', NULL, '2026-02-28 03:56:49'),
(22, 'site_favicon', '/assets/images/favicon.ico', 'text', NULL, '2026-02-28 03:56:49'),
(25, 'support_email', 'support@example.com', 'text', NULL, '2026-02-28 03:56:49'),
(26, 'contact_phone', '+966500000000', 'text', NULL, '2026-02-28 03:56:49'),
(27, 'contact_address', 'الرياض، المملكة العربية السعودية', 'text', NULL, '2026-02-28 03:56:49'),
(30, 'time_format', 'H:i:s', 'text', NULL, '2026-02-28 03:56:49'),
(31, 'week_start', 'saturday', 'text', NULL, '2026-02-28 03:56:49'),
(34, 'maintenance_message', 'الموقع تحت الصيانة حالياً، نعتذر عن الإزعاج', 'text', NULL, '2026-02-28 03:56:49'),
(36, 'registration_enabled', '1', 'text', NULL, '2026-02-28 03:56:49'),
(37, 'email_verification', '1', 'text', NULL, '2026-02-28 03:56:49'),
(38, 'mfa_required', '1', 'text', NULL, '2026-02-28 06:10:26'),
(39, 'password_min_length', '13', 'text', NULL, '2026-02-28 06:17:23'),
(40, 'password_require_uppercase', '1', 'text', NULL, '2026-02-28 06:10:26'),
(41, 'password_require_lowercase', '1', 'text', NULL, '2026-02-28 06:10:26'),
(42, 'password_require_numbers', '1', 'text', NULL, '2026-02-28 06:10:26'),
(43, 'password_require_special', '1', 'text', NULL, '2026-02-28 06:10:26'),
(44, 'password_expiry_days', '90', 'text', NULL, '2026-02-28 06:10:26'),
(45, 'max_login_attempts', '5', 'text', NULL, '2026-02-28 06:10:26'),
(46, 'lockout_duration', '15', 'text', NULL, '2026-02-28 06:10:26'),
(47, 'session_lifetime', '7200', 'text', NULL, '2026-02-28 06:10:26'),
(48, 'remember_me_days', '30', 'text', NULL, '2026-02-28 06:10:26');

-- --------------------------------------------------------

--
-- بنية الجدول `site_stats`
--

CREATE TABLE `site_stats` (
  `id` int(11) NOT NULL,
  `stat_key` varchar(100) NOT NULL,
  `stat_value` varchar(255) NOT NULL,
  `stat_label` varchar(255) DEFAULT NULL,
  `stat_icon` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `site_stats`
--

INSERT INTO `site_stats` (`id`, `stat_key`, `stat_value`, `stat_label`, `stat_icon`, `updated_at`) VALUES
(1, 'uptime', '99.99%', 'نسبة التوافر', 'fa-chart-line', '2026-02-21 04:24:31'),
(2, 'monitoring', '24/7', 'مراقبة أمنية', 'fa-shield-alt', '2026-02-21 04:24:31'),
(3, 'services_count', '30+', 'خدمة متخصصة', 'fa-cubes', '2026-02-21 04:24:31'),
(4, 'clients_count', '1500+', 'عميل نشط', 'fa-users', '2026-02-21 04:24:31'),
(5, 'support_hours', '24/7', 'الدعم الفني', 'fa-headset', '2026-02-21 04:24:31'),
(6, 'backup_daily', 'يومي', 'نسخ احتياطي', 'fa-database', '2026-02-21 04:24:31');

-- --------------------------------------------------------

--
-- بنية الجدول `support_team`
--

CREATE TABLE `support_team` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `support_team`
--

INSERT INTO `support_team` (`id`, `name`, `position`, `department`, `phone`, `email`, `avatar`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'أحمد محمد', 'مدير الدعم الفني', 'الدعم', '+966 50 123 4567', 'ahmed@xcyber.com', NULL, 1, 1, '2026-02-21 04:24:31'),
(2, 'سارة عبدالله', 'مهندسة أمن معلومات', 'الأمان', '+966 55 234 5678', 'sara@xcyber.com', NULL, 2, 1, '2026-02-21 04:24:31'),
(3, 'خالد العتيبي', 'فني استضافة', 'الاستضافة', '+966 54 345 6789', 'khalid@xcyber.com', NULL, 3, 1, '2026-02-21 04:24:31'),
(4, 'نورة السالم', 'استشارية تقنية', 'الاستشارات', '+966 56 456 7890', 'noura@xcyber.com', NULL, 4, 1, '2026-02-21 04:24:31');

-- --------------------------------------------------------

--
-- بنية الجدول `system_audits`
--

CREATE TABLE `system_audits` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','in-progress','completed','cancelled') DEFAULT 'scheduled',
  `auditor_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `system_audits`
--

INSERT INTO `system_audits` (`id`, `code`, `name`, `description`, `scope`, `status`, `auditor_id`, `created_by`, `scheduled_date`, `completed_date`, `created_at`, `updated_at`) VALUES
(1, 'AUD-2024-001', 'تدقيق أمن المعلومات الربعي', 'تدقيق شامل لأنظمة أمن المعلومات', 'جميع الأنظمة', 'completed', 1, 1, '2024-01-15', NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(2, 'AUD-2024-002', 'تدقيق أداء النظام', 'مراجعة أداء الخوادم وقواعد البيانات', 'البنية التحتية', 'in-progress', 2, 1, '2024-02-01', NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(3, 'AUD-2024-003', 'تدقيق الامتثال للمعايير', 'التحقق من الامتثال لمعايير ISO 27001', 'جميع الأنظمة', 'scheduled', 3, 1, '2024-03-10', NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(4, 'AUD-2024-004', 'تدقيق صلاحيات الوصول', 'مراجعة صلاحيات المستخدمين', 'نظام المستخدمين', 'scheduled', 1, 1, '2024-03-15', NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12'),
(5, 'AUD-2024-005', 'تدقيق التوثيق الفني', 'مراجعة توثيق الأنظمة والإجراءات', 'وثائق النظام', 'in-progress', 4, 1, '2024-02-20', NULL, '2026-02-18 00:20:12', '2026-02-18 00:20:12');

-- --------------------------------------------------------

--
-- بنية الجدول `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json','array') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'site_name', 'نظام التوثيق الفني', 'text', 'اسم الموقع', 5, '2026-02-18 22:19:47'),
(2, 'company_name', 'شركة الاستضافة والحماية', 'text', 'اسم الشركة', 5, '2026-02-18 22:19:47'),
(3, 'date_format', 'Y-m-d', 'text', 'تنسيق التاريخ', 5, '2026-02-18 22:19:47'),
(4, 'timezone', 'Asia/Riyadh', 'text', 'المنطقة الزمنية', 5, '2026-02-18 22:19:47'),
(5, 'items_per_page', '20', 'number', 'عدد العناصر في الصفحة', 5, '2026-02-18 22:19:47'),
(6, 'enable_notifications', 'true', 'boolean', 'تفعيل الإشعارات', 5, '2026-02-18 22:19:47'),
(7, 'default_document_status', 'draft', 'text', 'الحالة الافتراضية للمستندات', 5, '2026-02-18 22:19:47'),
(8, 'max_file_size', '10485760', 'number', 'الحد الأقصى لحجم الملف (بايت)', 5, '2026-02-18 22:19:47'),
(9, 'allowed_file_types', '[\"pdf\",\"docx\",\"xlsx\",\"pptx\",\"txt\",\"md\",\"html\",\"png\",\"jpg\"]', 'json', 'أنواع الملفات المسموح بها', 5, '2026-02-18 22:19:47'),
(10, 'repository_path', '/repositories', 'text', 'مسار المستودع الرئيسي', 5, '2026-02-18 22:19:47'),
(11, 'backup_enabled', 'true', 'boolean', 'تفعيل النسخ الاحتياطي', 5, '2026-02-18 22:19:47'),
(12, 'backup_frequency', 'daily', 'text', 'تكرار النسخ الاحتياطي', 5, '2026-02-18 22:19:47'),
(13, 'retention_period', '30', 'number', 'فترة الاحتفاظ بالنسخ (يوم)', 5, '2026-02-18 22:19:47'),
(14, 'mail_driver', 'smtp', 'text', 'نوع بريد الإرسال', 5, '2026-02-18 22:19:47'),
(15, 'mail_host', 'smtp.gmail.com', 'text', 'خادم البريد', 5, '2026-02-18 22:19:47'),
(16, 'mail_port', '587', 'number', 'منفذ البريد', 5, '2026-02-18 22:19:47'),
(17, 'mail_username', 'notifications@example.com', 'text', 'اسم مستخدم البريد', 5, '2026-02-18 22:19:47'),
(18, 'mail_encryption', 'tls', 'text', 'تشفير البريد', 5, '2026-02-18 22:19:47'),
(19, 'notification_email', 'admin@example.com', 'text', 'البريد الإلكتروني للإشعارات', 5, '2026-02-18 22:19:47'),
(20, 'default_language', 'ar', 'text', 'اللغة الافتراضية', 5, '2026-02-18 22:19:47'),
(21, 'rtl_enabled', 'true', 'boolean', 'تفعيل الاتجاه من اليمين لليسار', 5, '2026-02-18 22:19:47'),
(22, 'enable_versioning', 'true', 'boolean', 'تفعيل إدارة الإصدارات', 5, '2026-02-18 22:19:47'),
(23, 'auto_archive_days', '365', 'number', 'أرشفة تلقائية بعد (يوم)', 5, '2026-02-18 22:19:47'),
(24, 'require_review', 'true', 'boolean', 'طلب مراجعة قبل الاعتماد', 5, '2026-02-18 22:19:47'),
(25, 'max_review_rounds', '3', 'number', 'الحد الأقصى لمرات المراجعة', 5, '2026-02-18 22:19:47'),
(26, 'session_timeout', '3600', 'number', 'مهلة الجلسة (ثانية)', 5, '2026-02-18 22:19:47'),
(27, 'maintenance_mode', 'false', 'boolean', 'وضع الصيانة', 5, '2026-02-18 22:19:47'),
(28, 'google_analytics_id', 'UA-12345678-1', 'text', 'معرف Google Analytics', 5, '2026-02-18 22:19:47'),
(29, 'meta_description', 'نظام متكامل لإدارة التوثيق الفني', 'text', 'وصف الموقع', 5, '2026-02-18 22:19:47'),
(30, 'meta_keywords', 'توثيق, تقني, مستندات, مشاريع', 'text', 'كلمات مفتاحية', 5, '2026-02-18 22:19:47'),
(31, 'contact_email', 'support@example.com', 'text', 'بريد التواصل', 5, '2026-02-18 22:19:47'),
(32, 'support_phone', '+966123456789', 'text', 'رقم الدعم', 5, '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `system_status`
--

CREATE TABLE `system_status` (
  `id` int(11) NOT NULL,
  `component` varchar(100) NOT NULL,
  `status` enum('active','warning','error','maintenance') DEFAULT 'active',
  `health_percentage` int(11) DEFAULT 100,
  `last_check` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `system_status`
--

INSERT INTO `system_status` (`id`, `component`, `status`, `health_percentage`, `last_check`) VALUES
(1, 'جدار الحماية', 'active', 100, '2026-02-16 23:30:45'),
(2, 'أنظمة الكشف', 'active', 95, '2026-02-16 23:30:45'),
(3, 'النسخ الاحتياطي', 'active', 100, '2026-02-16 23:30:45');

-- --------------------------------------------------------

--
-- بنية الجدول `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT 'blue',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tags`
--

INSERT INTO `tags` (`id`, `name`, `color`, `description`, `created_at`) VALUES
(1, 'requirements', 'blue', 'متطلبات النظام', '2026-02-18 22:19:47'),
(2, 'security', 'red', 'المستندات الأمنية', '2026-02-18 22:19:47'),
(3, 'api', 'purple', 'توثيق واجهات API', '2026-02-18 22:19:47'),
(4, 'user-guide', 'green', 'أدلة المستخدم', '2026-02-18 22:19:47'),
(5, 'installation', 'yellow', 'أدلة التثبيت', '2026-02-18 22:19:47'),
(6, 'production', 'orange', 'بيئة الإنتاج', '2026-02-18 22:19:47'),
(7, 'testing', 'cyan', 'اختبارات', '2026-02-18 22:19:47'),
(8, 'audit', 'indigo', 'تدقيق أمني', '2026-02-18 22:19:47'),
(9, 'architecture', 'pink', 'هيكلية النظام', '2026-02-18 22:19:47'),
(10, 'configuration', 'gray', 'تكوين النظام', '2026-02-18 22:19:47'),
(11, 'performance', 'teal', 'الأداء', '2026-02-18 22:19:47'),
(12, 'deployment', 'brown', 'نشر النظام', '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `template_variables`
--

CREATE TABLE `template_variables` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `variable_name` varchar(100) NOT NULL,
  `variable_key` varchar(100) NOT NULL,
  `variable_type` enum('text','number','date','select','boolean','user','project') DEFAULT 'text',
  `default_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `order_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `template_variables`
--

INSERT INTO `template_variables` (`id`, `template_id`, `variable_name`, `variable_key`, `variable_type`, `default_value`, `description`, `is_required`, `options`, `order_number`, `created_at`) VALUES
(1, 1, 'اسم المشروع', 'project_name', 'text', NULL, 'الاسم الكامل للمشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(2, 1, 'اسم العميل', 'client_name', 'text', NULL, 'اسم العميل أو الشركة', 1, NULL, 2, '2026-02-18 22:19:47'),
(3, 1, 'الإصدار', 'version', 'text', '1.0.0', 'نسخة المستند', 0, NULL, 3, '2026-02-18 22:19:47'),
(4, 1, 'التاريخ', 'date', 'date', NULL, 'تاريخ إنشاء المستند', 1, NULL, 4, '2026-02-18 22:19:47'),
(5, 1, 'المؤلف', 'author', 'user', NULL, 'منشئ المستند', 1, NULL, 5, '2026-02-18 22:19:47'),
(6, 2, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(7, 2, 'مستوى الأمان', 'security_level', 'select', 'normal', 'مستوى حساسية المشروع', 1, '[\"normal\",\"sensitive\",\"critical\"]', 2, '2026-02-18 22:19:47'),
(8, 2, 'عدد الثغرات', 'findings', 'number', '0', 'عدد الثغرات المكتشفة', 1, NULL, 3, '2026-02-18 22:19:47'),
(9, 2, 'تاريخ التقييم', 'assessment_date', 'date', NULL, 'تاريخ إجراء التقييم', 1, NULL, 4, '2026-02-18 22:19:47'),
(10, 2, 'المدقق', 'auditor', 'user', NULL, 'الشخص الذي أجرى التقييم', 1, NULL, 5, '2026-02-18 22:19:47'),
(11, 3, 'اسم المنتج', 'product_name', 'text', NULL, 'اسم المنتج أو النظام', 1, NULL, 1, '2026-02-18 22:19:47'),
(12, 3, 'الإصدار', 'version', 'text', '1.0.0', 'إصدار المنتج', 1, NULL, 2, '2026-02-18 22:19:47'),
(13, 3, 'الجمهور المستهدف', 'audience', 'text', 'المستخدمين النهائيين', 'الفئة المستهدفة من الدليل', 0, NULL, 3, '2026-02-18 22:19:47'),
(14, 4, 'اسم API', 'api_name', 'text', NULL, 'اسم واجهة البرمجة', 1, NULL, 1, '2026-02-18 22:19:47'),
(15, 4, 'الإصدار', 'version', 'text', 'v1', 'إصدار API', 1, NULL, 2, '2026-02-18 22:19:47'),
(16, 4, 'الرابط الأساسي', 'base_url', 'text', 'https://api.example.com', 'الرابط الأساسي للAPI', 1, NULL, 3, '2026-02-18 22:19:47'),
(17, 5, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام', 1, NULL, 1, '2026-02-18 22:19:47'),
(18, 5, 'المكونات', 'components', '', '[]', 'قائمة المكونات الرئيسية', 1, NULL, 2, '2026-02-18 22:19:47'),
(19, 6, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(20, 6, 'تاريخ البداية', 'start_date', 'date', NULL, 'بداية الفترة', 1, NULL, 2, '2026-02-18 22:19:47'),
(21, 6, 'تاريخ النهاية', 'end_date', 'date', NULL, 'نهاية الفترة', 1, NULL, 3, '2026-02-18 22:19:47'),
(22, 6, 'نسبة التقدم', 'progress', 'number', '0', 'نسبة الإنجاز', 1, NULL, 4, '2026-02-18 22:19:47'),
(23, 7, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام المراد تثبيته', 1, NULL, 1, '2026-02-18 22:19:47'),
(24, 7, 'المتطلبات', 'requirements', '', '[]', 'متطلبات التثبيت', 1, NULL, 2, '2026-02-18 22:19:47'),
(25, 7, 'الخطوات', 'steps', '', '[]', 'خطوات التثبيت', 1, NULL, 3, '2026-02-18 22:19:47'),
(26, 8, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(27, 8, 'حالات الاختبار', 'test_cases', '', '[]', 'قائمة حالات الاختبار', 1, NULL, 2, '2026-02-18 22:19:47'),
(28, 9, 'الفترة', 'period', 'text', NULL, 'الفترة المشمولة بالتقرير', 1, NULL, 1, '2026-02-18 22:19:47'),
(29, 9, 'المقاييس', 'metrics', '', '[]', 'مقاييس الأداء', 1, NULL, 2, '2026-02-18 22:19:47'),
(30, 10, 'اسم النظام', 'system_name', 'text', NULL, 'اسم النظام', 1, NULL, 1, '2026-02-18 22:19:47'),
(31, 10, 'الأوامر', 'commands', '', '[]', 'أوامر الإدارة', 1, NULL, 2, '2026-02-18 22:19:47'),
(32, 11, 'اسم المعيار', 'standard_name', 'text', NULL, 'اسم معيار الامتثال', 1, NULL, 1, '2026-02-18 22:19:47'),
(33, 11, 'المتطلبات', 'requirements', '', '[]', 'متطلبات الامتثال', 1, NULL, 2, '2026-02-18 22:19:47'),
(34, 11, 'مستوى الامتثال', 'compliance_level', 'select', 'partial', 'مستوى المطابقة', 1, '[\"none\",\"partial\",\"full\"]', 3, '2026-02-18 22:19:47'),
(35, 12, 'اسم العميل', 'client_name', 'text', NULL, 'اسم العميل', 1, NULL, 1, '2026-02-18 22:19:47'),
(36, 12, 'اسم المشروع', 'project_name', 'text', NULL, 'اسم المشروع', 1, NULL, 2, '2026-02-18 22:19:47'),
(37, 12, 'قيمة العقد', 'contract_value', 'number', '0', 'قيمة العقد', 1, NULL, 3, '2026-02-18 22:19:47'),
(38, 13, 'وصف التغيير', 'change_description', 'text', NULL, 'وصف التغيير المطلوب', 1, NULL, 1, '2026-02-18 22:19:47'),
(39, 13, 'الأثر', 'impact', 'text', NULL, 'أثر التغيير على المشروع', 1, NULL, 2, '2026-02-18 22:19:47'),
(40, 14, 'التسليمات', 'deliverables', '', '[]', 'قائمة بالتسليمات', 1, NULL, 1, '2026-02-18 22:19:47'),
(41, 15, 'تقدير التكلفة', 'cost_estimate', 'number', '0', 'تقدير تكلفة المشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(42, 16, 'المعايير', 'standards', '', '[]', 'معايير الجودة المطبقة', 1, NULL, 1, '2026-02-18 22:19:47'),
(43, 17, 'المخاطر', 'risks', '', '[]', 'قائمة المخاطر', 1, NULL, 1, '2026-02-18 22:19:47'),
(44, 18, 'أنواع الحوادث', 'incident_types', '', '[]', 'أنواع الحوادث المشمولة', 1, NULL, 1, '2026-02-18 22:19:47'),
(45, 19, 'الأسئلة الشائعة', 'faq', '', '[]', 'أسئلة وأجوبة شائعة', 1, NULL, 1, '2026-02-18 22:19:47'),
(46, 20, 'الجداول', 'tables', '', '[]', 'قائمة جداول قاعدة البيانات', 1, NULL, 1, '2026-02-18 22:19:47'),
(47, 21, 'أمثلة الكود', 'code_examples', '', '[]', 'أمثلة على الكود', 0, NULL, 1, '2026-02-18 22:19:47'),
(48, 22, 'معرف الحادث', 'incident_id', 'text', NULL, 'معرف فريد للحادث', 1, NULL, 1, '2026-02-18 22:19:47'),
(49, 22, 'الخطورة', 'severity', 'select', 'medium', 'مستوى خطورة الحادث', 1, '[\"low\",\"medium\",\"high\",\"critical\"]', 2, '2026-02-18 22:19:47'),
(50, 23, 'مشاكل أمنية', 'security_issues', '', '[]', 'المشاكل الأمنية المكتشفة', 1, NULL, 1, '2026-02-18 22:19:47'),
(51, 24, 'البيئة', 'environment', 'select', 'development', 'بيئة النشر', 1, '[\"development\",\"staging\",\"production\"]', 1, '2026-02-18 22:19:47'),
(52, 25, 'احتياجات التدريب', 'training_needs', '', '[]', 'الاحتياجات التدريبية', 1, NULL, 1, '2026-02-18 22:19:47'),
(53, 26, 'الخدمات الحرجة', 'critical_services', '', '[]', 'الخدمات التي يجب استعادتها أولاً', 1, NULL, 1, '2026-02-18 22:19:47'),
(54, 27, 'نقاط النقاش', 'discussion_points', '', '[]', 'نقاط تمت مناقشتها', 1, NULL, 1, '2026-02-18 22:19:47'),
(55, 28, 'معايير التقييم', 'criteria', '', '[]', 'معايير تقييم البائع', 1, NULL, 1, '2026-02-18 22:19:47'),
(56, 29, 'الأهداف', 'objectives', '', '[]', 'أهداف المشروع', 1, NULL, 1, '2026-02-18 22:19:47'),
(57, 30, 'النطاق', 'scope', 'text', NULL, 'نطاق العمل', 1, NULL, 1, '2026-02-18 22:19:47');

-- --------------------------------------------------------

--
-- بنية الجدول `testing_tools`
--

CREATE TABLE `testing_tools` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('recon','vulnerability','exploitation','web','network','reporting') NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','needs-update','installing') DEFAULT 'active',
  `license_type` enum('open-source','free','paid','trial') DEFAULT 'open-source',
  `last_used` date DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `testing_tools`
--

INSERT INTO `testing_tools` (`id`, `name`, `category`, `version`, `status`, `license_type`, `last_used`, `last_updated`, `configuration`, `description`, `created_at`) VALUES
(1, 'Nmap', 'recon', '7.94', 'active', 'open-source', '2024-01-28', '2024-01-15', NULL, 'أداة فحص المنافذ واكتشاف الخدمات', '2026-02-18 00:55:52'),
(2, 'Nessus', 'vulnerability', '10.5', 'active', 'paid', '2024-01-27', '2024-01-20', NULL, 'ماسح ثغرات متقدم', '2026-02-18 00:55:52'),
(3, 'Metasploit', 'exploitation', '6.3', 'active', 'open-source', '2024-01-26', '2024-01-10', NULL, 'إطار عمل لاختبار الاختراق', '2026-02-18 00:55:52'),
(4, 'Burp Suite', 'web', '2024.1', 'active', 'paid', '2024-01-28', '2024-01-25', NULL, 'أداة فحص تطبيقات الويب', '2026-02-18 00:55:52'),
(5, 'OpenVAS', 'vulnerability', '22.4', 'active', 'open-source', '2024-01-25', '2024-01-05', NULL, 'ماسح ثغرات مفتوح المصدر', '2026-02-18 00:55:52'),
(6, 'Sublist3r', 'recon', '1.2', 'needs-update', 'open-source', '2024-01-20', '2023-12-01', NULL, 'اكتشاف النطاقات الفرعية', '2026-02-18 00:55:52'),
(7, 'Wireshark', 'network', '4.2', 'active', 'open-source', '2024-01-24', '2024-01-10', NULL, 'تحليل حركة الشبكة', '2026-02-18 00:55:52'),
(8, 'Sqlmap', 'exploitation', '1.7', 'active', 'open-source', '2024-01-26', '2024-01-15', NULL, 'أداة اختبار SQL Injection', '2026-02-18 00:55:52'),
(9, 'Nikto', 'web', '2.5', 'active', 'open-source', '2024-01-23', '2024-01-05', NULL, 'ماسح ضعف خوادم الويب', '2026-02-18 00:55:52'),
(10, 'Hydra', 'exploitation', '9.5', 'active', 'open-source', '2024-01-22', '2024-01-01', NULL, 'أداة تخمين كلمات المرور', '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `threats`
--

CREATE TABLE `threats` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('ddos','brute_force','sql_injection','xss','malware','phishing') NOT NULL,
  `source_ip` varchar(45) DEFAULT NULL,
  `target_server_id` int(11) DEFAULT NULL,
  `target_url` text DEFAULT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL,
  `status` enum('active','mitigated','blocked','investigating') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `attack_pattern` text DEFAULT NULL,
  `first_seen` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mitigated_at` timestamp NULL DEFAULT NULL,
  `mitigated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `threats`
--

INSERT INTO `threats` (`id`, `name`, `type`, `source_ip`, `target_server_id`, `target_url`, `severity`, `status`, `description`, `attack_pattern`, `first_seen`, `last_seen`, `mitigated_at`, `mitigated_by`) VALUES
(1, 'هجوم DDoS على خادم الويب', 'ddos', '45.123.67.89', 1, 'https://example.com', 'critical', 'active', 'هجوم حجب خدمة من عدة مصادر', NULL, '2026-02-16 02:07:22', '2026-02-16 04:07:22', NULL, NULL),
(2, 'محاولات تخمين كلمات المرور', 'brute_force', '103.21.244.0', 5, 'https://example.com/login', 'high', 'active', 'محاولات دخول متكررة من 20 IP مختلف', NULL, '2026-02-16 01:07:22', '2026-02-16 04:07:22', NULL, NULL),
(3, 'حقن SQL على صفحة المنتجات', 'sql_injection', '78.45.123.22', 3, 'https://example.com/products', 'critical', 'mitigated', 'محاولة حقن SQL لاكتشاف قاعدة البيانات', NULL, '2026-02-15 23:07:22', '2026-02-16 00:07:22', NULL, NULL),
(4, 'هجوم XSS على نموذج البحث', 'xss', '56.78.90.123', 1, 'https://example.com/search', 'medium', 'blocked', 'محاولة إدخال سكريبت ضار', NULL, '2026-02-15 22:07:22', '2026-02-15 23:07:22', NULL, NULL),
(5, 'محاولة رفع ملف ضار', 'malware', '112.67.34.56', 5, 'https://example.com/upload', 'high', 'mitigated', 'محاولة رفع ملف PHP ضار', NULL, '2026-02-15 21:07:22', '2026-02-15 22:07:22', NULL, NULL),
(6, 'هجوم DDoS على API', 'ddos', '89.123.45.67', 3, 'https://api.example.com', 'critical', 'active', 'هجوم على واجهة البرمجة', NULL, '2026-02-16 00:07:22', '2026-02-16 04:07:22', NULL, NULL),
(7, 'محاولة تصيد احتيالي', 'phishing', '34.56.78.90', NULL, 'fake-login-page.com', 'medium', 'blocked', 'نطاق مشبوه يحاكي صفحة الدخول', NULL, '2026-02-15 20:07:22', '2026-02-15 21:07:22', NULL, NULL),
(8, 'هجوم SQL على لوحة التحكم', 'sql_injection', '67.89.123.45', 2, 'https://admin.example.com', 'critical', 'investigating', 'محاولة استغلال ثغرة SQL في لوحة التحكم', NULL, '2026-02-16 03:07:22', '2026-02-16 04:07:22', NULL, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `head_name` varchar(255) DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `employee_count` int(11) DEFAULT 0,
  `max_employees` int(11) DEFAULT 10,
  `budget` decimal(15,2) DEFAULT 0.00,
  `color` varchar(20) DEFAULT 'blue',
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `units`
--

INSERT INTO `units` (`id`, `name`, `code`, `head_name`, `head_id`, `employee_count`, `max_employees`, `budget`, `color`, `status`, `created_at`, `updated_at`) VALUES
(1, 'وحدة التوثيق', 'DOC', 'علي محمد', NULL, 5, 6, 120000.00, 'blue', 'active', '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(2, 'وحدة التخزين', 'STR', 'فاطمة أحمد', NULL, 12, 15, 850000.00, 'purple', 'active', '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(3, 'وحدة الحماية', 'SEC', 'خالد سعود', NULL, 7, 9, 600000.00, 'green', 'active', '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(4, 'وحدة الاختبار', 'PEN', 'سارة القحطاني', NULL, 3, 5, 350000.00, 'yellow', 'active', '2026-02-16 23:30:44', '2026-02-16 23:30:44'),
(15, 'وحدة السيرفرات', 'U-HOST-01', 'أحمد محمد', NULL, 3, 8, 150000.00, 'green', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(16, 'وحدة النطاقات', 'U-HOST-02', 'سارة عبدالله', NULL, 2, 5, 75000.00, 'green', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(17, 'وحدة التخزين السحابي', 'U-STOR-01', 'فهد خالد', NULL, 5, 10, 200000.00, 'blue', 'active', '2026-02-22 23:20:56', '2026-02-22 23:29:02'),
(18, 'وحدة قواعد البيانات', 'U-STOR-02', 'نورة سعد', NULL, 3, 6, 120000.00, 'blue', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(19, 'وحدة جدران الحماية', 'U-SEC-01', 'عبدالعزيز العتيبي', NULL, 5, 8, 250000.00, 'yellow', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(20, 'وحدة مراقبة التهديدات', 'U-SEC-02', 'فيصل الحربي', NULL, 4, 7, 180000.00, 'yellow', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(21, 'وحدة اختبار الاختراق', 'U-PEN-01', 'تركي القحطاني', NULL, 3, 6, 220000.00, 'purple', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(22, 'وحدة تحليل الثغرات', 'U-PEN-02', 'هيا المطيري', NULL, 2, 5, 160000.00, 'purple', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(23, 'وحدة التوثيق الفني', 'U-DOC-01', 'سارة العنزي', NULL, 3, 6, 90000.00, 'pink', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(24, 'وحدة إدارة المحتوى', 'U-DOC-02', 'نوف السبيعي', NULL, 2, 4, 60000.00, 'pink', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(25, 'وحدة المشاريع', 'U-MAN-01', 'خالد الحارثي', NULL, 4, 8, 300000.00, 'indigo', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56'),
(26, 'وحدة الموارد البشرية', 'U-MAN-02', 'لمى الدوسري', NULL, 3, 5, 120000.00, 'indigo', 'active', '2026-02-22 23:20:56', '2026-02-22 23:20:56');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `role` enum('management','hosting','technical_writer','admin','manager','analyst','viewer','storage','security','pentest','documentation') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hire_date` date DEFAULT NULL,
  `can_manage` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password_hash`, `full_name`, `job_title`, `role`, `department`, `unit_id`, `last_login`, `is_active`, `created_at`, `hire_date`, `can_manage`) VALUES
(1, 'ahmed.ali', 'ahmed.ali@company.com', NULL, 'hash123', 'أحمد العلي', NULL, 'manager', 'وحدة الحماية', 3, NULL, 1, '2026-02-16 04:07:21', NULL, 0),
(2, 'sara.mohammed', 'sara.m@company.com', NULL, 'hash123', 'سارة محمد', NULL, 'analyst', 'وحدة الحماية', 1, NULL, 1, '2026-02-16 04:07:21', NULL, 0),
(3, 'khaled.omar', 'khaled.o@company.com', NULL, 'hash123', 'خالد عمر', NULL, 'analyst', 'وحدة الحماية', 4, NULL, 1, '2026-02-16 04:07:21', NULL, 0),
(4, 'nora.ahmed', 'nora.a@company.com', NULL, 'hash123', 'نورا أحمد', NULL, 'analyst', 'وحدة الحماية', 2, NULL, 1, '2026-02-16 04:07:21', NULL, 0),
(5, 'fahad.saud', 'fahad.s@company.com', NULL, 'hash123', 'فهد سعود', NULL, 'admin', 'الإدارة العليا', NULL, NULL, 1, '2026-02-16 04:07:21', NULL, 1),
(6, 'sara.abdullah', 'sara.abdullah@example.com', NULL, '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سارة عبدالله', NULL, 'technical_writer', 'قسم التوثيق', NULL, NULL, 1, '2026-02-18 22:19:47', NULL, 0),
(7, 'ahmed.ali', 'ahmed.ali@example.com', NULL, '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'أحمد العلي', NULL, '', 'قسم المراجعة', NULL, NULL, 1, '2026-02-18 22:19:47', NULL, 0),
(8, 'mohammed.omari', 'mohammed.omari@example.com', NULL, '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'محمد العمري', NULL, 'technical_writer', 'قسم التوثيق', NULL, NULL, 1, '2026-02-18 22:19:47', NULL, 0),
(9, 'noura.dosari', 'noura.dosari@example.com', NULL, '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'نورة الدوسري', NULL, 'manager', 'الإدارة', NULL, NULL, 1, '2026-02-18 22:19:47', NULL, 0),
(10, 'khalid.rashid', 'khalid.rashid@example.com', NULL, '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'خالد الرشيد', NULL, 'admin', 'تقنية المعلومات', NULL, NULL, 1, '2026-02-18 22:19:47', NULL, 0),
(12, 'ahmed.hosting', 'ahmed.hosting@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'أحمد محمد الجهني', NULL, 'hosting', 'hosting', 1, '2026-02-27 00:42:22', 1, '2026-02-22 22:10:43', NULL, 1),
(13, 'saeed.hosting', 'saeed.hosting@xcyper.com', NULL, '$2y$10$YourHashedPasswordHere', 'سعيد علي الغامدي', NULL, '', 'hosting', 1, NULL, 1, '2026-02-22 22:10:43', NULL, 0),
(14, 'noura.storage', 'noura.storage@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'نورة عبدالله الشمري', NULL, 'storage', 'storage', 2, '2026-02-23 01:00:26', 1, '2026-02-22 22:10:43', NULL, 1),
(15, 'fahad.storage', 'fahad.storage@xcyper.com', NULL, '$2y$10$YourHashedPasswordHere', 'فهد خالد الدوسري', NULL, '', 'storage', 2, NULL, 1, '2026-02-22 22:10:43', NULL, 0),
(16, 'aziz.security', 'aziz.security@xcyper.com', NULL, '$2y$10$YourHashedPasswordHere', 'عبدالعزيز محمد العتيبي', NULL, '', 'security', 3, NULL, 1, '2026-02-22 22:10:43', NULL, 1),
(17, 'faisal.security', 'faisal.security@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'فيصل عبدالرحمن الحربي', NULL, 'security', 'security', 3, '2026-02-23 01:58:15', 1, '2026-02-22 22:10:43', NULL, 0),
(18, 'turki.pentest', 'turki.pentest@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'تركي سعد القحطاني', NULL, 'pentest', 'pentest', 4, '2026-02-23 10:31:05', 1, '2026-02-22 22:10:43', NULL, 1),
(19, 'haya.pentest', 'haya.pentest@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'هيا عبدالله المطيري', NULL, 'pentest', 'pentest', 4, '2026-02-23 01:38:14', 1, '2026-02-22 22:10:43', NULL, 0),
(20, 'sara.docs', 'sara.docs@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'سارة محمد العنزي', NULL, 'documentation', 'documentation', 5, '2026-02-23 10:38:13', 1, '2026-02-22 22:10:43', NULL, 1),
(21, 'nouf.docs', 'nouf.docs@xcyper.com', NULL, '$2y$10$YourHashedPasswordHere', 'نوف بندر السبيعي', NULL, 'technical_writer', 'documentation', 5, NULL, 1, '2026-02-22 22:10:43', NULL, 0),
(22, 'khalid.admin', 'khalid.admin@xcyper.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'خالد ابراهيم الحارثي', NULL, 'admin', 'management', 6, '2026-02-28 01:46:00', 1, '2026-02-22 22:10:43', NULL, 1),
(23, 'lama.manager', 'lama.manager@xcyper.com', NULL, 'fatomalariqi@gmail.com', 'لمى سعد الدوسري', NULL, 'manager', 'management', 6, NULL, 1, '2026-02-22 22:10:43', NULL, 1),
(24, 'nasser.analyst', 'nasser.analyst@xcyper.com', NULL, '$2y$10$YourHashedPasswordHere', 'ناصر عبدالله القحطاني', NULL, 'analyst', 'management', 6, NULL, 1, '2026-02-22 22:10:43', NULL, 0),
(38, 'Fato Alariqi', 'fatomalariqi@gmail.com', NULL, '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'Fato  Alariqi', NULL, 'admin', 'admin', 17, '2026-02-23 00:17:28', 1, '2026-02-22 23:29:02', NULL, 0);

-- --------------------------------------------------------

--
-- بنية الجدول `users_all`
--

CREATE TABLE `users_all` (
  `id` int(11) NOT NULL COMMENT 'معرف المستخدم الفريد',
  `uuid` varchar(36) NOT NULL COMMENT 'معرف عالمي فريد',
  `username` varchar(50) NOT NULL COMMENT 'اسم المستخدم',
  `user_source` enum('user','client','client_account','support_team','other') DEFAULT 'other' COMMENT 'مصدر المستخدم',
  `source_id` int(11) DEFAULT NULL COMMENT 'المعرف في الجدول الأصلي',
  `email` varchar(100) NOT NULL COMMENT 'البريد الإلكتروني',
  `password` varchar(255) NOT NULL COMMENT 'كلمة المرور (مشفرة)',
  `full_name` varchar(100) NOT NULL COMMENT 'الاسم الكامل',
  `phone` varchar(20) DEFAULT NULL COMMENT 'رقم الهاتف',
  `phone_verified` tinyint(1) DEFAULT 0 COMMENT 'هل تم التحقق من الهاتف',
  `id_number` varchar(20) DEFAULT NULL COMMENT 'رقم الهوية/الإقامة',
  `birth_date` date DEFAULT NULL COMMENT 'تاريخ الميلاد',
  `gender` enum('male','female','other') DEFAULT 'other' COMMENT 'الجنس',
  `nationality` varchar(50) DEFAULT 'SA' COMMENT 'الجنسية',
  `address` text DEFAULT NULL COMMENT 'العنوان',
  `city` varchar(50) DEFAULT NULL COMMENT 'المدينة',
  `country` varchar(50) DEFAULT 'السعودية' COMMENT 'الدولة',
  `user_type` enum('admin','manager','assistant_manager','documentation_staff','cloud_storage_staff','pentest_staff','monitoring_staff','pms_staff','finance_staff','ai_staff','client') NOT NULL COMMENT 'نوع المستخدم',
  `role_id` varchar(50) DEFAULT 'client' COMMENT 'معرف الدور',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'صلاحيات إضافية' CHECK (json_valid(`permissions`)),
  `unit` varchar(50) DEFAULT NULL COMMENT 'الوحدة التابع لها',
  `department` varchar(50) DEFAULT NULL COMMENT 'القسم',
  `job_title` varchar(100) DEFAULT NULL COMMENT 'المسمى الوظيفي',
  `manager_id` int(11) DEFAULT NULL COMMENT 'معرف المدير المباشر',
  `employee_id` varchar(50) DEFAULT NULL COMMENT 'رقم الموظف',
  `hire_date` date DEFAULT NULL COMMENT 'تاريخ التعيين',
  `salary` decimal(10,2) DEFAULT NULL COMMENT 'الراتب',
  `client_type` enum('individual','company','government') DEFAULT 'individual' COMMENT 'نوع العميل',
  `company_name` varchar(100) DEFAULT NULL COMMENT 'اسم الشركة',
  `commercial_registration` varchar(50) DEFAULT NULL COMMENT 'السجل التجاري',
  `vat_number` varchar(50) DEFAULT NULL COMMENT 'الرقم الضريبي',
  `account_manager_id` int(11) DEFAULT NULL COMMENT 'معرف مدير الحساب',
  `credit_limit` decimal(10,2) DEFAULT 0.00 COMMENT 'حد الائتمان',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'طريقة الدفع المفضلة',
  `subscription_plan` varchar(50) DEFAULT NULL COMMENT 'الباقة',
  `subscription_start` date DEFAULT NULL COMMENT 'بداية الاشتراك',
  `subscription_end` date DEFAULT NULL COMMENT 'نهاية الاشتراك',
  `auto_renew` tinyint(1) DEFAULT 1 COMMENT 'تجديد تلقائي',
  `mfa_enabled` tinyint(1) DEFAULT 0 COMMENT 'مفعل MFA',
  `mfa_method` enum('none','google_authenticator','email','sms','whatsapp') DEFAULT 'none' COMMENT 'طريقة MFA',
  `mfa_secret` varchar(255) DEFAULT NULL COMMENT 'سر MFA',
  `backup_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'رموز احتياطية' CHECK (json_valid(`backup_codes`)),
  `password_changed_at` timestamp NULL DEFAULT NULL COMMENT 'آخر تغيير لكلمة المرور',
  `password_reset_token` varchar(255) DEFAULT NULL COMMENT 'رمز إعادة تعيين كلمة المرور',
  `password_reset_expires` timestamp NULL DEFAULT NULL COMMENT 'انتهاء صلاحية رمز إعادة التعيين',
  `login_attempts` int(11) DEFAULT 0 COMMENT 'محاولات تسجيل الدخول الفاشلة',
  `locked_until` timestamp NULL DEFAULT NULL COMMENT 'مقفل حتى',
  `last_login` timestamp NULL DEFAULT NULL COMMENT 'آخر تسجيل دخول',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT 'آخر IP',
  `last_login_agent` text DEFAULT NULL COMMENT 'آخر متصفح',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'معرف الجلسة الحالية',
  `notifications_email` tinyint(1) DEFAULT 1 COMMENT 'إشعارات البريد',
  `notifications_sms` tinyint(1) DEFAULT 0 COMMENT 'إشعارات SMS',
  `notifications_whatsapp` tinyint(1) DEFAULT 0 COMMENT 'إشعارات واتساب',
  `notifications_browser` tinyint(1) DEFAULT 1 COMMENT 'إشعارات المتصفح',
  `language` varchar(10) DEFAULT 'ar' COMMENT 'اللغة',
  `theme` varchar(20) DEFAULT 'light' COMMENT 'السمة',
  `timezone` varchar(50) DEFAULT 'Asia/Riyadh' COMMENT 'المنطقة الزمنية',
  `date_format` varchar(20) DEFAULT 'Y-m-d' COMMENT 'تنسيق التاريخ',
  `items_per_page` int(11) DEFAULT 25 COMMENT 'عدد العناصر في الصفحة',
  `total_projects` int(11) DEFAULT 0 COMMENT 'إجمالي المشاريع',
  `total_storage` bigint(20) DEFAULT 0 COMMENT 'إجمالي التخزين المستخدم (بايت)',
  `total_files` int(11) DEFAULT 0 COMMENT 'إجمالي الملفات',
  `total_logins` int(11) DEFAULT 0 COMMENT 'إجمالي مرات الدخول',
  `total_actions` int(11) DEFAULT 0 COMMENT 'إجمالي الإجراءات',
  `status` enum('active','inactive','suspended','locked','pending_verification','pending_approval','deleted') DEFAULT 'pending_verification' COMMENT 'حالة الحساب',
  `status_reason` text DEFAULT NULL COMMENT 'سبب تغيير الحالة',
  `status_changed_by` int(11) DEFAULT NULL COMMENT 'من قام بتغيير الحالة',
  `status_changed_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ تغيير الحالة',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ التحقق من البريد',
  `phone_verified_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ التحقق من الهاتف',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ الموافقة',
  `approved_by` int(11) DEFAULT NULL COMMENT 'تمت الموافقة بواسطة',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'تاريخ الإنشاء',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'تاريخ التحديث',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ الحذف (soft delete)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المستخدمين الشامل';

--
-- إرجاع أو استيراد بيانات الجدول `users_all`
--

INSERT INTO `users_all` (`id`, `uuid`, `username`, `user_source`, `source_id`, `email`, `password`, `full_name`, `phone`, `phone_verified`, `id_number`, `birth_date`, `gender`, `nationality`, `address`, `city`, `country`, `user_type`, `role_id`, `permissions`, `unit`, `department`, `job_title`, `manager_id`, `employee_id`, `hire_date`, `salary`, `client_type`, `company_name`, `commercial_registration`, `vat_number`, `account_manager_id`, `credit_limit`, `payment_method`, `subscription_plan`, `subscription_start`, `subscription_end`, `auto_renew`, `mfa_enabled`, `mfa_method`, `mfa_secret`, `backup_codes`, `password_changed_at`, `password_reset_token`, `password_reset_expires`, `login_attempts`, `locked_until`, `last_login`, `last_login_ip`, `last_login_agent`, `session_id`, `notifications_email`, `notifications_sms`, `notifications_whatsapp`, `notifications_browser`, `language`, `theme`, `timezone`, `date_format`, `items_per_page`, `total_projects`, `total_storage`, `total_files`, `total_logins`, `total_actions`, `status`, `status_reason`, `status_changed_by`, `status_changed_at`, `email_verified_at`, `phone_verified_at`, `approved_at`, `approved_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '96392111-1437-11f1-9498-ace2d3d13774', 'admin', 'other', NULL, 'admin@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'مدير النظام العام', '0500000001', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'admin', 'admin', NULL, 'الإدارة العليا', NULL, 'مدير عام', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(2, '963a0911-1437-11f1-9498-ace2d3d13774', 'manager1', 'other', NULL, 'manager@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'أحمد محمد', '0500000002', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'manager', 'manager', NULL, 'إدارة الاستضافة', NULL, 'مدير نظام الاستضافة والحماية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(3, '963a0f03-1437-11f1-9498-ace2d3d13774', 'assistant_ops', 'other', NULL, 'assistant.ops@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'سارة عبدالله', '0500000003', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'assistant_manager', 'assistant_manager', NULL, 'العمليات', NULL, 'مساعد مدير العمليات', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(4, '963a13d6-1437-11f1-9498-ace2d3d13774', 'doc_head', 'other', NULL, 'doc.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'محمد علي', '0500000004', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'documentation_head', NULL, 'التوثيق', NULL, 'رئيس وحدة التوثيق', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(5, '963a2179-1437-11f1-9498-ace2d3d13774', 'doc_analyst1', 'other', NULL, 'doc.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'نورة أحمد', '0500000005', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'documentation_staff', NULL, 'التوثيق', NULL, 'محلل توثيق', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(6, '963b797b-1437-11f1-9498-ace2d3d13774', 'contract_spec', 'other', NULL, 'contracts@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عمر حسن', '0500000006', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'documentation_staff', NULL, 'التوثيق', NULL, 'مختص عقود وأرشفة', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(7, '963b7db1-1437-11f1-9498-ace2d3d13774', 'cloud_head', 'other', NULL, 'cloud.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'خالد إبراهيم', '0500000007', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'cloud_storage_staff', 'cloud_storage_head', NULL, 'التخزين السحابي', NULL, 'رئيس وحدة التخزين', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(8, '963b8088-1437-11f1-9498-ace2d3d13774', 'cloud_eng1', 'other', NULL, 'cloud.eng1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'فيصل المالكي', '0500000008', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'cloud_storage_staff', 'cloud_storage_staff', NULL, 'التخزين السحابي', NULL, 'مهندس بنية تحتية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(9, '963b8388-1437-11f1-9498-ace2d3d13774', 'cloud_admin1', 'other', NULL, 'cloud.admin1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالله القحطاني', '0500000009', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'cloud_storage_staff', 'cloud_storage_staff', NULL, 'التخزين السحابي', NULL, 'مسؤول تخزين واستضافة', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(10, '963b868e-1437-11f1-9498-ace2d3d13774', 'pentest_head', 'other', NULL, 'pentest.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'نايف الحربي', '0500000010', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'pentest_staff', 'pentest_head', NULL, 'اختبار الاختراق', NULL, 'رئيس وحدة اختبار الاختراق', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(11, '963b8976-1437-11f1-9498-ace2d3d13774', 'pentest_senior1', 'other', NULL, 'pentest.senior1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالعزيز الشمري', '0500000011', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'pentest_staff', 'pentest_senior', NULL, 'اختبار الاختراق', NULL, 'محلل أمني متقدم', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(12, '963b8c60-1437-11f1-9498-ace2d3d13774', 'pentest_analyst1', 'other', NULL, 'pentest.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'أحمد الزهراني', '0500000012', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'pentest_staff', 'pentest_staff', NULL, 'اختبار الاختراق', NULL, 'محلل أمني', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(13, '963b8f5f-1437-11f1-9498-ace2d3d13774', 'monitor_head', 'other', NULL, 'monitor.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'بدر العتيبي', '0500000013', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'monitoring_staff', 'monitoring_head', NULL, 'المراقبة', NULL, 'رئيس وحدة الحماية والمراقبة', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(14, '963b9234-1437-11f1-9498-ace2d3d13774', 'monitor_analyst1', 'other', NULL, 'monitor.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'سعود الدوسري', '0500000014', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'monitoring_staff', 'monitoring_staff', NULL, 'المراقبة', NULL, 'محلل أمني ومراقبة', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(15, '963b9530-1437-11f1-9498-ace2d3d13774', 'network_eng1', 'other', NULL, 'network.eng1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'مشعل العنزي', '0500000015', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'monitoring_staff', 'monitoring_staff', NULL, 'المراقبة', NULL, 'مهندس أمن الشبكات', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(16, '963b97ff-1437-11f1-9498-ace2d3d13774', 'pms_head', 'other', NULL, 'pms.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'فهد الغامدي', '0500000016', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'pms_staff', 'pms_head', NULL, 'إدارة المشاريع', NULL, 'رئيس إدارة المشاريع', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(17, '963b9b69-1437-11f1-9498-ace2d3d13774', 'pms_coord1', 'other', NULL, 'pms.coord1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'ريم الشهراني', '0500000017', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'pms_staff', 'pms_staff', NULL, 'إدارة المشاريع', NULL, 'منسق مشاريع', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(18, '963b9e62-1437-11f1-9498-ace2d3d13774', 'finance_head', 'other', NULL, 'finance.head@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'عبدالرحمن السبيعي', '0500000018', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'finance_staff', 'finance_head', NULL, 'المالية', NULL, 'رئيس النظام المالي', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(19, '963ba05b-1437-11f1-9498-ace2d3d13774', 'accountant1', 'other', NULL, 'accountant1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'منى العبدالله', '0500000019', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'finance_staff', 'finance_staff', NULL, 'المالية', NULL, 'محاسب', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(20, '963ba207-1437-11f1-9498-ace2d3d13774', 'ai_analyst1', 'other', NULL, 'ai.analyst1@hosting-security.com', '$2y$10$YourHashedPasswordHere', 'لمى القاسم', '0500000020', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'ai_staff', 'ai_staff', NULL, 'الذكاء الاصطناعي', NULL, 'محلل ذكاء اصطناعي', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(21, '963ba50f-1437-11f1-9498-ace2d3d13774', 'client1', 'other', NULL, 'client1@example.com', '$2y$10$YourHashedPasswordHere', 'شركة الأمان للتقنية', '0500000021', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(22, '963ba6ef-1437-11f1-9498-ace2d3d13774', 'client2', 'other', NULL, 'client2@example.com', '$2y$10$YourHashedPasswordHere', 'مؤسسة الحلول الرقمية', '0500000022', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(23, '963ba8a7-1437-11f1-9498-ace2d3d13774', 'client3', 'other', NULL, 'client3@example.com', '$2y$10$YourHashedPasswordHere', 'شركة الابتكارات الحديثة', '0500000023', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(24, '963baa4a-1437-11f1-9498-ace2d3d13774', 'client4', 'other', NULL, 'client4@example.com', '$2y$10$YourHashedPasswordHere', 'مؤسسة البرمجيات المتقدمة', '0500000024', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(25, '963babfc-1437-11f1-9498-ace2d3d13774', 'client5', 'other', NULL, 'client5@example.com', '$2y$10$YourHashedPasswordHere', 'شركة البيانات الآمنة', '0500000025', 1, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-27 23:54:00', NULL, NULL, NULL, '2026-02-27 23:54:00', '2026-02-27 23:54:00', NULL),
(40, '38b36c65-143f-11f1-9498-ace2d3d13774', 'info@tech-sa.com', 'client', 1, 'info@tech-sa.com', '', 'محمد العمري', '0555123456', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'company', 'شركة التقنية المتطورة', NULL, NULL, NULL, 0.00, NULL, 'enterprise', NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:22', '2026-02-28 00:48:39', NULL),
(41, '38b38549-143f-11f1-9498-ace2d3d13774', 'contact@digital-security.com', 'client', 2, 'contact@digital-security.com', '', 'أحمد الجهني', '0555234567', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'company', 'مؤسسة الأمان الرقمي', NULL, NULL, NULL, 0.00, NULL, 'professional', NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:22', '2026-02-28 00:48:39', NULL),
(42, '38b38886-143f-11f1-9498-ace2d3d13774', 'support@electronics-store.com', 'client', 3, 'support@electronics-store.com', '', 'سارة القحطاني', '0555345678', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'company', 'متجر الإلكترونيات', NULL, NULL, NULL, 0.00, NULL, 'basic', NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:22', '2026-02-28 00:48:39', NULL),
(57, '3a9fd400-1440-11f1-9498-ace2d3d13774', 'ahmed.ali', 'user', 1, 'ahmed.ali@company.com', 'hash123', 'أحمد العلي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'manager', 'manager', NULL, 'وحدة الحماية', 'وحدة الحماية', 'وحدة الحماية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:21', '2026-02-28 00:55:51', NULL),
(58, '3a9fe8c3-1440-11f1-9498-ace2d3d13774', 'sara.mohammed', 'user', 2, 'sara.m@company.com', 'hash123', 'سارة محمد', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'analyst', NULL, 'وحدة الحماية', 'وحدة الحماية', 'وحدة الحماية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:21', '2026-02-28 00:55:51', NULL),
(59, '3a9fedd2-1440-11f1-9498-ace2d3d13774', 'khaled.omar', 'user', 3, 'khaled.o@company.com', 'hash123', 'خالد عمر', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'analyst', NULL, 'وحدة الحماية', 'وحدة الحماية', 'وحدة الحماية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:21', '2026-02-28 00:55:51', NULL),
(60, '3a9ff26c-1440-11f1-9498-ace2d3d13774', 'nora.ahmed', 'user', 4, 'nora.a@company.com', 'hash123', 'نورا أحمد', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'analyst', NULL, 'وحدة الحماية', 'وحدة الحماية', 'وحدة الحماية', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:21', '2026-02-28 00:55:51', NULL),
(61, '3a9ff6ce-1440-11f1-9498-ace2d3d13774', 'fahad.saud', 'user', 5, 'fahad.s@company.com', 'hash123', 'فهد سعود', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'admin', 'admin', NULL, 'الإدارة العليا', 'الإدارة العليا', 'الإدارة العليا', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-16 04:07:21', '2026-02-28 00:55:51', NULL),
(62, '3a9ffb6a-1440-11f1-9498-ace2d3d13774', 'sara.abdullah', 'user', 6, 'sara.abdullah@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سارة عبدالله', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'technical_writer', NULL, 'قسم التوثيق', 'قسم التوثيق', 'قسم التوثيق', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 22:19:47', '2026-02-28 00:55:51', NULL),
(63, '3aa004ba-1440-11f1-9498-ace2d3d13774', 'mohammed.omari', 'user', 8, 'mohammed.omari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'محمد العمري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'technical_writer', NULL, 'قسم التوثيق', 'قسم التوثيق', 'قسم التوثيق', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 22:19:47', '2026-02-28 00:55:51', NULL),
(64, '3aa1166c-1440-11f1-9498-ace2d3d13774', 'noura.dosari', 'user', 9, 'noura.dosari@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'نورة الدوسري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'manager', 'manager', NULL, 'الإدارة', 'الإدارة', 'الإدارة', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 22:19:47', '2026-02-28 00:55:51', NULL),
(65, '3aa11c0e-1440-11f1-9498-ace2d3d13774', 'khalid.rashid', 'user', 10, 'khalid.rashid@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'خالد الرشيد', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'admin', 'admin', NULL, 'تقنية المعلومات', 'تقنية المعلومات', 'تقنية المعلومات', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-18 22:19:47', '2026-02-28 00:55:51', NULL),
(66, '3aa12076-1440-11f1-9498-ace2d3d13774', 'ahmed.hosting', 'user', 12, 'ahmed.hosting@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'أحمد محمد الجهني', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'hosting', NULL, 'hosting', 'hosting', 'hosting', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-27 00:42:22', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(67, '3aa12551-1440-11f1-9498-ace2d3d13774', 'saeed.hosting', 'user', 13, 'saeed.hosting@xcyper.com', '$2y$10$YourHashedPasswordHere', 'سعيد علي الغامدي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', '', NULL, 'hosting', 'hosting', 'hosting', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(68, '3aa129a1-1440-11f1-9498-ace2d3d13774', 'noura.storage', 'user', 14, 'noura.storage@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'نورة عبدالله الشمري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'storage', NULL, 'storage', 'storage', 'storage', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 01:00:26', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(69, '3aa12daf-1440-11f1-9498-ace2d3d13774', 'fahad.storage', 'user', 15, 'fahad.storage@xcyper.com', '$2y$10$YourHashedPasswordHere', 'فهد خالد الدوسري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', '', NULL, 'storage', 'storage', 'storage', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(70, '3aa13196-1440-11f1-9498-ace2d3d13774', 'aziz.security', 'user', 16, 'aziz.security@xcyper.com', '$2y$10$YourHashedPasswordHere', 'عبدالعزيز محمد العتيبي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', '', NULL, 'security', 'security', 'security', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(71, '3aa13561-1440-11f1-9498-ace2d3d13774', 'faisal.security', 'user', 17, 'faisal.security@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'فيصل عبدالرحمن الحربي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'security', NULL, 'security', 'security', 'security', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 01:58:15', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(72, '3aa13991-1440-11f1-9498-ace2d3d13774', 'turki.pentest', 'user', 18, 'turki.pentest@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'تركي سعد القحطاني', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'pentest', NULL, 'pentest', 'pentest', 'pentest', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 10:31:05', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(73, '3aa14f7c-1440-11f1-9498-ace2d3d13774', 'haya.pentest', 'user', 19, 'haya.pentest@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'هيا عبدالله المطيري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'pentest', NULL, 'pentest', 'pentest', 'pentest', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 01:38:14', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(74, '3aa151ee-1440-11f1-9498-ace2d3d13774', 'sara.docs', 'user', 20, 'sara.docs@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'سارة محمد العنزي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'documentation', NULL, 'documentation', 'documentation', 'documentation', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 10:38:13', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(75, '3aa153f7-1440-11f1-9498-ace2d3d13774', 'nouf.docs', 'user', 21, 'nouf.docs@xcyper.com', '$2y$10$YourHashedPasswordHere', 'نوف بندر السبيعي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'technical_writer', NULL, 'documentation', 'documentation', 'documentation', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(76, '3aa155e4-1440-11f1-9498-ace2d3d13774', 'khalid.admin', 'user', 22, 'khalid.admin@xcyper.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'خالد ابراهيم الحارثي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'admin', 'admin', NULL, 'management', 'management', 'management', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-27 00:47:07', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(77, '3aa157d0-1440-11f1-9498-ace2d3d13774', 'lama.manager', 'user', 23, 'lama.manager@xcyper.com', 'fatomalariqi@gmail.com', 'لمى سعد الدوسري', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'manager', 'manager', NULL, 'management', 'management', 'management', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(78, '3aa159b3-1440-11f1-9498-ace2d3d13774', 'nasser.analyst', 'user', 24, 'nasser.analyst@xcyper.com', '$2y$10$YourHashedPasswordHere', 'ناصر عبدالله القحطاني', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'documentation_staff', 'analyst', NULL, 'management', 'management', 'management', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 22:10:43', '2026-02-28 00:55:51', NULL),
(79, '3aa15b98-1440-11f1-9498-ace2d3d13774', 'Fato Alariqi', 'user', 38, 'fatomalariqi@gmail.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'Fato  Alariqi', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'admin', 'admin', NULL, 'admin', 'admin', 'admin', NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-23 00:17:28', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-22 23:29:02', '2026-02-28 00:55:51', NULL),
(91, '88a39323-1441-11f1-9498-ace2d3d13774', 'ahmed.alali@example.com', 'client_account', 1, 'ahmed.alali@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'أحمد محمد العلي', '0501234567', 0, NULL, NULL, 'other', 'SA', 'الرياض - حي النخيل', 'الرياض', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'شركة التقنية المتطورة', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-15 07:30:00', NULL, NULL, NULL, 1, 1, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 5, 0, 15, 0, 2, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(92, '88a3993a-1441-11f1-9498-ace2d3d13774', 'sara.alqahtani@example.com', 'client_account', 2, 'sara.alqahtani@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سارة عبدالله القحطاني', '0552345678', 0, NULL, NULL, 'other', 'SA', 'جدة - شارع التحلية', 'جدة', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'مؤسسة الأمان الرقمي', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-14 06:15:00', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 2, 0, 2, 0, 1, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(93, '88a3a3b7-1441-11f1-9498-ace2d3d13774', 'fahad.qahtani@example.com', 'client_account', 5, 'fahad.qahtani@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'فهد خالد القحطاني', '0545678901', 0, NULL, NULL, 'other', 'SA', 'مكة - العزيزية', 'مكة', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'شركة الحلول المتكاملة', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-10 05:30:00', NULL, NULL, NULL, 0, 0, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 1, 0, 1, 0, 0, 'suspended', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(94, '88a3a5e9-1441-11f1-9498-ace2d3d13774', 'reem.shamri@example.com', 'client_account', 6, 'reem.shamri@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'ريم عبدالعزيز الشمري', '0586789012', 0, NULL, NULL, 'other', 'SA', 'تبوك - النهضة', 'تبوك', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'مؤسسة الشمري للتجارة', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-14 13:10:00', NULL, NULL, NULL, 1, 1, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 1, 0, 1, 0, 0, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(95, '88a3a813-1441-11f1-9498-ace2d3d13774', 'abdulrahman.harthy@example.com', 'client_account', 7, 'abdulrahman.harthy@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'عبدالرحمن إبراهيم الحارثي', '0597890123', 0, NULL, NULL, 'other', 'SA', 'الطائف - الشهداء', 'الطائف', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'شركة الحارثي للتطوير', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-12 10:40:00', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 1, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(96, '88a3aa30-1441-11f1-9498-ace2d3d13774', 'hindi.otaibi@example.com', 'client_account', 8, 'hindi.otaibi@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'هند صالح العتيبي', '0508901234', 0, NULL, NULL, 'other', 'SA', 'بريدة - الرحاب', 'بريدة', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'مؤسسة العتيبي للاستشارات', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 1, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(97, '88a3ac2f-1441-11f1-9498-ace2d3d13774', 'sami.mutairi@example.com', 'client_account', 9, 'sami.mutairi@example.com', '$2y$10$HkzYcQ8K3X5Z7J9L1N3P5R7T9V1X3Z5B7N9L1P3R5T7V9X1Z3B5N7', 'سامي فهد المطيري', '0559012345', 0, NULL, NULL, 'other', 'SA', 'حائل - المطار', 'حائل', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'شركة المطيري للتجارة', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-11 07:15:00', NULL, NULL, NULL, 1, 1, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(98, '88a3ae3a-1441-11f1-9498-ace2d3d13774', 'lama.subaie@example.com', 'client_account', 10, 'lama.subaie@example.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'لمى بندر السبيعي', '0560123456', 0, NULL, NULL, 'other', 'SA', 'جيزان - الكورنيش', 'جيزان', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'مؤسسة السبيعي للتسويق', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2024-03-05 06:30:00', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'dark', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'inactive', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:08:24', NULL),
(99, '88a3b034-1441-11f1-9498-ace2d3d13774', 'mohammed.alali@example.com', 'client_account', 11, 'mohammed.alali@example.com', '$2y$10$5oLt1rHgv.XZj1uRJNrSLuNQeB2lQOx0LQIBkFb3KxCz1OieTyPWO', 'محمد العلي', '0509876543', 0, NULL, NULL, 'other', 'SA', 'الرياض - حي الملقا', 'الرياض', 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'شركة العلي للتجارة', NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-02-27 00:52:36', NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-28 01:05:12', NULL, NULL, NULL, '2026-02-28 01:05:12', '2026-02-28 01:05:12', NULL),
(109, 'fb281c19-1441-11f1-9498-ace2d3d13774', 'ahmed@xcyber.com', 'support_team', 1, 'ahmed@xcyber.com', '', 'أحمد محمد', '+966 50 123 4567', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'support', NULL, NULL, 'الدعم', NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 01:08:24', '2026-02-28 01:08:24', NULL),
(110, 'fb282e30-1441-11f1-9498-ace2d3d13774', 'sara@xcyber.com', 'support_team', 2, 'sara@xcyber.com', '', 'سارة عبدالله', '+966 55 234 5678', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'support', NULL, NULL, 'الأمان', NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 01:08:24', '2026-02-28 01:08:24', NULL),
(111, 'fb2830e2-1441-11f1-9498-ace2d3d13774', 'khalid@xcyber.com', 'support_team', 3, 'khalid@xcyber.com', '', 'خالد العتيبي', '+966 54 345 6789', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'support', NULL, NULL, 'الاستضافة', NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 01:08:24', '2026-02-28 01:08:24', NULL),
(112, 'fb28330a-1441-11f1-9498-ace2d3d13774', 'noura@xcyber.com', 'support_team', 4, 'noura@xcyber.com', '', 'نورة السالم', '+966 56 456 7890', 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'support', NULL, NULL, 'الاستشارات', NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 01:08:24', '2026-02-28 01:08:24', NULL),
(116, '8c95bddd-1453-11f1-9498-ace2d3d13774', 'manager', '', 0, 'manager@example.com', '$2y$10$aXV0yVpI/IPnP89nZYQEOO8o9ZZ2A/JVUOTuqzFWCxHkZeNTmLn4i', 'مدير الاستضافة', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'manager', 'manager', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-28 03:14:09', NULL, NULL, NULL, '2026-02-28 03:14:09', '2026-02-28 03:14:09', NULL),
(117, '8ca7fff2-1453-11f1-9498-ace2d3d13774', 'client', '', 0, 'client@example.com', '$2y$10$eMRYoz0MHtic.4AeFU3kDOP1HHSlmdddAr4YcKehqInOJPt6m4oh.', 'عميل تجريبي', NULL, 0, NULL, NULL, 'other', 'SA', NULL, NULL, 'السعودية', 'client', 'client', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, NULL, 1, 0, 'none', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, 1, 'ar', 'light', 'Asia/Riyadh', 'Y-m-d', 25, 0, 0, 0, 0, 0, 'active', NULL, NULL, NULL, '2026-02-28 03:14:09', NULL, NULL, NULL, '2026-02-28 03:14:09', '2026-02-28 03:14:09', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `users_login`
--

CREATE TABLE `users_login` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(200) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `role` enum('admin','manager','editor') DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users_login`
--

INSERT INTO `users_login` (`id`, `username`, `password`, `email`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$YourHashedPasswordHere12345678901234567890', 'admin@xcyber.com', 'مدير النظام', 'admin', '2026-02-22 22:40:38'),
(2, 'FatomAlariqi', '$2y$10$6Eq3M811Y7tjdMGOxjxHIedSDpoBUOoYGhfDjdW4gWc7hzrlSTc9O', 'fatomalariqi@gmail.com', 'FatomAlariqi', 'editor', '2026-02-23 10:53:58'),
(3, 'MOHAMMED', '$2y$10$udqsEsL4wexkxkL2WwncguHR27x9Qn2IKmpjaQoc3FuRpYHWrKg1K', 'MOHAMMED@gmail.com', 'MOHAMMED', 'editor', '2026-02-23 11:30:11'),
(4, 'Mohammed Hilal', '$2y$10$qtq8qKsuSj4YCFfR92JTduGbEX.6Ku8NwiqN//A0faZHW42jPUTvy', 'xcypera@gmail.com', 'Mohammed Hilal', 'editor', '2026-02-27 01:16:34'),
(5, 'Admin Xcyper', '$2y$10$F0v41F6KlAJjVYyvQP/MpucpUeZfgylDDkRIybAmo6Hi7DUYUW5em', 'atomalariqi@gmail.com', 'moh.1', 'editor', '2026-02-27 01:18:22');

-- --------------------------------------------------------

--
-- بنية الجدول `user_events`
--

CREATE TABLE `user_events` (
  `id` bigint(20) NOT NULL,
  `event_id` varchar(36) NOT NULL COMMENT 'معرف الحدث الفريد',
  `user_id` int(11) NOT NULL COMMENT 'معرف المستخدم',
  `event_type` enum('login','logout','login_failed','password_change','password_reset','profile_update','settings_change','mfa_enabled','mfa_disabled','mfa_verified','file_upload','file_download','file_delete','file_scan','project_create','project_update','project_delete','backup_create','backup_restore','backup_delete','container_create','container_isolate','container_release','permission_change','role_change','security_alert','threat_detected','malware_found','api_call','export_data','import_data','payment_made','invoice_created','contract_signed','contract_approved','support_ticket','support_response') NOT NULL COMMENT 'نوع الحدث',
  `action` varchar(100) NOT NULL COMMENT 'الإجراء',
  `description` text DEFAULT NULL COMMENT 'وصف الحدث',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'تفاصيل إضافية' CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'عنوان IP',
  `user_agent` text DEFAULT NULL COMMENT 'المتصفح',
  `device` varchar(50) DEFAULT NULL COMMENT 'الجهاز',
  `location` varchar(100) DEFAULT NULL COMMENT 'الموقع',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'معرف الجلسة',
  `request_id` varchar(36) DEFAULT NULL COMMENT 'معرف الطلب',
  `request_method` varchar(10) DEFAULT NULL COMMENT 'طريقة الطلب',
  `request_url` text DEFAULT NULL COMMENT 'رابط الطلب',
  `response_time` int(11) DEFAULT NULL COMMENT 'وقت الاستجابة (مللي ثانية)',
  `response_code` int(11) DEFAULT NULL COMMENT 'رمز الاستجابة',
  `resource_type` varchar(50) DEFAULT NULL COMMENT 'نوع المورد',
  `resource_id` varchar(100) DEFAULT NULL COMMENT 'معرف المورد',
  `resource_name` varchar(255) DEFAULT NULL COMMENT 'اسم المورد',
  `client_id` int(11) DEFAULT NULL COMMENT 'معرف العميل',
  `project_id` int(11) DEFAULT NULL COMMENT 'معرف المشروع',
  `container_id` varchar(100) DEFAULT NULL COMMENT 'معرف الحاوية',
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `security_level` int(11) DEFAULT 1,
  `status` varchar(50) DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='سجل أحداث المستخدمين';

--
-- إرجاع أو استيراد بيانات الجدول `user_events`
--

INSERT INTO `user_events` (`id`, `event_id`, `user_id`, `event_type`, `action`, `description`, `details`, `ip_address`, `user_agent`, `device`, `location`, `session_id`, `request_id`, `request_method`, `request_url`, `response_time`, `response_code`, `resource_type`, `resource_id`, `resource_name`, `client_id`, `project_id`, `container_id`, `severity`, `security_level`, `status`, `error_message`, `created_at`) VALUES
(1, '9644fb4f-1437-11f1-9498-ace2d3d13774', 1, 'login', 'تسجيل دخول', 'تسجيل دخول المدير العام', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(2, '9645133b-1437-11f1-9498-ace2d3d13774', 2, 'login', 'تسجيل دخول', 'تسجيل دخول مدير الاستضافة', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(3, '96451510-1437-11f1-9498-ace2d3d13774', 7, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس التخزين', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(4, '96451633-1437-11f1-9498-ace2d3d13774', 10, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس اختبار الاختراق', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(5, '96451760-1437-11f1-9498-ace2d3d13774', 13, 'login', 'تسجيل دخول', 'تسجيل دخول رئيس المراقبة', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(6, '9645188e-1437-11f1-9498-ace2d3d13774', 21, 'login', 'تسجيل دخول', 'تسجيل دخول العميل 1', NULL, '127.0.0.1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 23:54:00'),
(7, 'a389008e-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'project_deployed', 'المشروع P-1025 تم نشره بنجاح', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(8, 'a3899ac1-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'project_deployed', 'المشروع P-1025 تم نشره بنجاح', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(9, 'a3899db3-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'documentation_completed', 'تقرير توثيق مكتمل للمشروع P-1022', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(10, 'a389a078-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'documentation_completed', 'تقرير توثيق مكتمل للمشروع P-1022', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(11, 'a389aa36-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'security_alert', 'تم رصد محاولة وصول غير مصرح', NULL, '192.168.1.103', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(12, 'a389ac6b-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'security_alert', 'تم رصد محاولة وصول غير مصرح', NULL, '192.168.1.103', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(13, 'a38abec5-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'vulnerability_found', 'ثغرة حرجة تم اكتشافها في P-1019', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(14, 'a38ac0e4-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'vulnerability_found', 'ثغرة حرجة تم اكتشافها في P-1019', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(22, 'a391bb79-1443-11f1-9498-ace2d3d13774', 40, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 23:05:59'),
(23, 'a391c988-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:05:59'),
(24, 'a391cd3b-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'download', 'تحميل تقرير', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:35:59'),
(25, 'a391d026-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '11', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(26, 'a391d2a2-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '12', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(27, 'a391d4e9-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(28, 'a391d727-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '14', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(29, 'a391d959-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '15', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(30, 'a391db8d-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '16', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(31, 'a391dd9d-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'إنشاء مجلد: محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:15'),
(32, 'a391dfc6-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '17', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:43'),
(33, 'a391e1ed-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:02:11'),
(34, 'a391e416-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: applications.html', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '18', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-23 10:40:56'),
(35, 'a391e63f-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: deepseek_mermaid_20260105_6dacad.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '19', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:03:32'),
(36, 'a391e869-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: تنبيه_قانوني.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:04:58'),
(37, 'a391ea7a-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'حذف ملف', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:19'),
(38, 'a391eca9-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'إنشاء مجلد: قاسم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:30'),
(39, 'a391eeba-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: الثغرات.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '21', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(40, 'a391f0ef-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: ثغرات_الويب.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '22', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(41, 'a391f33b-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: ملخص_جدار_الحماية.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(42, 'a391f54e-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:06:28'),
(43, 'a391f777-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:29:54'),
(44, 'a391f9b2-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:20'),
(45, 'a391fbd9-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:25'),
(46, 'a391fdfa-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:32:40'),
(47, 'a3920019-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:34:47'),
(48, 'a3920284-1443-11f1-9498-ace2d3d13774', 41, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 20:05:59'),
(49, 'a39204d7-1443-11f1-9498-ace2d3d13774', 41, 'api_call', 'view', 'عرض الفاتورة', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'invoice', '6', NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 21:05:59'),
(50, 'a39207e1-1443-11f1-9498-ace2d3d13774', 42, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 01:05:59'),
(51, 'a3920b44-1443-11f1-9498-ace2d3d13774', 42, 'api_call', 'upload', 'رفع ملف', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '6', NULL, 3, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 02:05:59'),
(52, 'a3921026-1443-11f1-9498-ace2d3d13774', 91, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 23:05:59'),
(53, 'a39212c4-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:05:59'),
(54, 'a392156d-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'download', 'تحميل تقرير', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:35:59'),
(55, 'a3921804-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '11', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(56, 'a3921a72-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '12', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(57, 'a3921ccb-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(58, 'a3921ee6-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '14', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(59, 'a39220ee-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '15', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(60, 'a3922323-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '16', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(61, 'a3922547-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'إنشاء مجلد: محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:15'),
(62, 'a392276e-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '17', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:43'),
(63, 'a3922996-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:02:11'),
(64, 'a3922bce-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: applications.html', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '18', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-23 10:40:56'),
(65, 'a3922e0f-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: deepseek_mermaid_20260105_6dacad.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '19', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:03:32'),
(66, 'a392303c-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: تنبيه_قانوني.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:04:58'),
(67, 'a3923268-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'حذف ملف', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:19'),
(68, 'a392349c-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'إنشاء مجلد: قاسم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:30'),
(69, 'a39236bb-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: الثغرات.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '21', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(70, 'a39238db-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: ثغرات_الويب.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '22', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(71, 'a3923b02-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: ملخص_جدار_الحماية.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(72, 'a3923d4f-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:06:28'),
(73, 'a3923f79-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:29:54'),
(74, 'a3924181-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:20'),
(75, 'a3924383-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:25'),
(76, 'a3924585-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:32:40'),
(77, 'a392477b-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:34:47'),
(78, 'a3924999-1443-11f1-9498-ace2d3d13774', 92, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 20:05:59'),
(79, 'a3924beb-1443-11f1-9498-ace2d3d13774', 92, 'api_call', 'view', 'عرض الفاتورة', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'invoice', '6', NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 21:05:59'),
(80, 'a392509b-1443-11f1-9498-ace2d3d13774', 93, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 01:05:59'),
(85, 'a39a15f9-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'create', 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'deployment', '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 00:21:43'),
(86, 'a39a1a3d-1443-11f1-9498-ace2d3d13774', 57, 'api_call', '', 'إعادة تشغيل الخادم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'server', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 00:46:07'),
(87, 'a39a1bf1-1443-11f1-9498-ace2d3d13774', 57, 'api_call', '', 'تطبيق تحديث أمني', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 01:18:48'),
(88, 'a39a1e4e-1443-11f1-9498-ace2d3d13774', 66, 'api_call', 'create', 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'deployment', '7', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 00:43:19'),
(89, 'a39a1fba-1443-11f1-9498-ace2d3d13774', 66, 'api_call', 'restore', 'استعادة نسخة احتياطية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'backup', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 00:44:20'),
(92, 'a3a09a80-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء مستند متطلبات نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-04 22:19:47'),
(93, 'a3a0a88a-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث متطلبات نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-05 22:19:47'),
(94, 'a3a0aa8a-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'download', 'تحميل مستند المتطلبات', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-09 22:19:47'),
(95, 'a3a0ac22-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء مشروع نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-14 22:19:47'),
(96, 'a3a0ada4-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'export', 'تصدير دليل التثبيت', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-18 22:19:47'),
(97, 'a3a0af26-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء قالب متطلبات النظام', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-22 22:19:47'),
(98, 'a3a0b087-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث قالب المتطلبات', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-27 22:19:47'),
(99, 'a3a0b1e2-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'view', 'عرض قالب التقرير الأمني', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-01 22:19:47'),
(100, 'a3a0b351-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'upload', 'رفع مخطط توضيحي', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-05 22:19:47'),
(101, 'a3a0b4d2-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'download', 'تحميل قالب دليل المستخدم', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-10 22:19:47'),
(102, 'a3a0b639-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'import', 'استيراد قالب هيكلية', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-15 22:19:47'),
(103, 'a3a0b798-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'comment', 'إضافة تعليق على هيكلية الشبكة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 21:49:47'),
(104, 'a3a0b902-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'export', 'تصدير كافة مستندات المشروع', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-15 22:19:47'),
(105, 'a3a0ba5e-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إضافة مستند جديد: ؤ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:22:59'),
(106, 'a3a0bbc2-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: needs_work', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:30:37'),
(107, 'a3a0bd20-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:31:39'),
(108, 'a3a0be7b-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:31:41'),
(109, 'a3a1055d-1443-11f1-9498-ace2d3d13774', 57, 'project_delete', 'delete', 'حذف مستند: ؤ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:32:06'),
(110, 'a3a10797-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة متطلبات النظام', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-07 22:19:47'),
(111, 'a3a10925-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'comment', 'إضافة تعليق على المستند', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-10 22:19:47'),
(112, 'a3a10a84-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة التقرير الأمني', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-13 22:19:47'),
(113, 'a3a10beb-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'view', 'عرض دليل المستخدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-16 22:19:47'),
(114, 'a3a10d43-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'approve', 'الموافقة على دليل المستخدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-21 22:19:47'),
(115, 'a3a10e8f-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'share', 'مشاركة دليل المشرف مع الفريق', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-26 22:19:47'),
(116, 'a3a10fbb-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'create', 'إنشاء تقرير التقدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-30 22:19:47'),
(117, 'a3a110e6-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة تقرير التقدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-04 22:19:47'),
(118, 'a3a1121b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'comment', 'إضافة تعليق على التقرير', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-09 22:19:47'),
(119, 'a3a1134b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'approve', 'الموافقة على التقرير بعد التحديث', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-14 22:19:47'),
(120, 'a3a11480-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(121, 'a3a115b4-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'بدء مراجعة تقييم الأمن', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 22:19:47'),
(122, 'a3a11704-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'create', 'إنشاء تقرير الاختبارات الأمنية', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-06 22:19:47'),
(123, 'a3a11847-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'update', 'تحديث تقرير الاختبارات', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-11 22:19:47'),
(124, 'a3a1198e-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'upload', 'رفع ملف تقرير أمني', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-17 22:19:47'),
(125, 'a3a11ada-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'download', 'تحميل التقرير الأمني', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-23 22:19:47'),
(126, 'a3a11c18-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'comment', 'إضافة تعليق على التقرير', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-28 22:19:47'),
(127, 'a3a11d52-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'export', 'تصدير توثيق API', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-02 22:19:47'),
(128, 'a3a11e7e-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'create', 'إنشاء تقرير اختبار الاختراق', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-07 22:19:47'),
(129, 'a3a11fa8-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'archive', 'أرشفة خطة الاختبار', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-12 22:19:47'),
(130, 'a3a120e4-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'share', 'مشاركة التقرير مع فريق الأمان', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 22:19:47'),
(131, 'a3a1220e-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'update', 'تحديث نتائج الاختبارات', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 17:19:47'),
(132, 'a3a1235f-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'create', 'إنشاء مستند متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-12 22:19:47'),
(133, 'a3a124a0-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'comment', 'إضافة تعليق على المتطلبات', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-19 22:19:47'),
(134, 'a3a125da-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'review', 'مراجعة متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-25 22:19:47'),
(135, 'a3a12710-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'update', 'تحديث معلومات المشروع', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-31 22:19:47'),
(136, 'a3a12846-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'approve', 'الموافقة على متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-06 22:19:47'),
(137, 'a3a1297d-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'create', 'إنشاء دليل استكشاف الأخطاء', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '18', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-11 22:19:47'),
(138, 'a3a12aaa-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'update', 'تحديث دليل استكشاف الأخطاء', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '18', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 22:19:47'),
(139, 'a3a12bd8-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'download', 'تحميل دليل التثبيت', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 20:19:47'),
(140, 'a3a12d0c-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'upload', 'رفع شهادة أمان', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-13 22:19:47'),
(141, 'a3a12eb2-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على متطلبات النظام', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-08 22:19:47'),
(142, 'a3a12ffb-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'إنشاء مشروع نظام الحماية', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-15 22:19:47'),
(143, 'a3a13130-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'update', 'تحديث هيكلية النظام', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-20 22:19:47'),
(144, 'a3a13334-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'إنشاء تقرير الأمن السيبراني', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-24 22:19:47'),
(145, 'a3a13472-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على التقرير', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-29 22:19:47'),
(146, 'a3a135b4-1443-11f1-9498-ace2d3d13774', 61, 'project_delete', 'delete', 'حذف تعليق غير مناسب', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comment', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-03 22:19:47'),
(147, 'a3a136e5-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'update', 'تحديث قالب الامتثال', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-08 22:19:47'),
(148, 'a3a13811-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'review', 'مراجعة تقرير الاختراق', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-13 22:19:47'),
(149, 'a3a13949-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'بدء إنشاء مستند جديد', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(150, 'a3a13a7f-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على قالب تقييم المخاطر', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-14 22:19:47'),
(155, 'a3a64bea-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'start', 'بدء فحص شامل لنظام البنك الأهلي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 07:30:00'),
(156, 'a3a64fb4-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'discover', 'اكتشاف ثغرة SQL Injection حرجة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 07:45:00'),
(157, 'a3a6514b-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'use', 'تشغيل Nessus لفحص الثغرات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 06:00:00'),
(158, 'a3a652ba-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'update', 'تحديث حالة الثغرة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 10:15:00'),
(159, 'a3a68ad4-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'complete', 'اكتمال فحص الثغرات لمنصة الحكومة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-27 08:30:00'),
(160, 'a3a68c84-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'resolve', 'حل تنبيه تكوين الخادم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 11:30:00'),
(161, 'a3a68e04-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'generate', 'إنشاء تقرير أمني مفصل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 12:00:00'),
(162, 'a3a68f52-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'start', 'بدء فحص منافذ الشبكة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 11:00:00'),
(163, 'a3a690a1-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'complete', 'اكتمال فحص المنافذ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 11:45:00'),
(170, 'a3ac8fef-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'Access Denied', 'محاولة وصول مرفوضة من IP 45.123.67.89', NULL, '45.123.67.89', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Firewall', '1', NULL, NULL, NULL, NULL, 'warning', 1, 'success', NULL, '2026-02-16 03:37:23'),
(175, 'f984bf52-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'project_deployed', 'المشروع P-1025 تم نشره بنجاح', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(176, 'f984d3b6-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'project_deployed', 'المشروع P-1025 تم نشره بنجاح', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(177, 'f984d7d1-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'documentation_completed', 'تقرير توثيق مكتمل للمشروع P-1022', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(178, 'f984dae7-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'documentation_completed', 'تقرير توثيق مكتمل للمشروع P-1022', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(179, 'f984de2f-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'security_alert', 'تم رصد محاولة وصول غير مصرح', NULL, '192.168.1.103', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(180, 'f984e144-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'security_alert', 'تم رصد محاولة وصول غير مصرح', NULL, '192.168.1.103', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'system', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(181, 'f984e446-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'vulnerability_found', 'ثغرة حرجة تم اكتشافها في P-1019', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 23:30:45'),
(182, 'f984e734-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'vulnerability_found', 'ثغرة حرجة تم اكتشافها في P-1019', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 00:43:53'),
(190, 'f98b6107-1443-11f1-9498-ace2d3d13774', 40, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 23:05:59'),
(191, 'f98b747d-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:05:59'),
(192, 'f98b772b-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'download', 'تحميل تقرير', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:35:59'),
(193, 'f98b7932-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '11', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(194, 'f98b7b0e-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '12', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(195, 'f98b7cdd-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(196, 'f98b801d-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '14', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(197, 'f98b8202-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '15', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(198, 'f98b83a6-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '16', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(199, 'f98b862c-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'إنشاء مجلد: محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:15'),
(200, 'f98b8859-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '17', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:43'),
(201, 'f98b8b65-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:02:11'),
(202, 'f98b8e2f-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: applications.html', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '18', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-23 10:40:56'),
(203, 'f98b91dd-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: deepseek_mermaid_20260105_6dacad.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '19', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:03:32'),
(204, 'f98b9c20-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: تنبيه_قانوني.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:04:58'),
(205, 'f98c965c-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'حذف ملف', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:19'),
(206, 'f98c9a74-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'إنشاء مجلد: قاسم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:30'),
(207, 'f98c9dd3-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: الثغرات.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '21', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(208, 'f98ca0da-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: ثغرات_الويب.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '22', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(209, 'f98ca3bd-1443-11f1-9498-ace2d3d13774', 40, 'api_call', 'upload', 'رفع ملف: ملخص_جدار_الحماية.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(210, 'f98ca6a6-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:06:28'),
(211, 'f98ca982-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:29:54'),
(212, 'f98cac5c-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:20'),
(213, 'f98caf38-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:25'),
(214, 'f98cb20b-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:32:40'),
(215, 'f98cb4c6-1443-11f1-9498-ace2d3d13774', 40, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:34:47'),
(216, 'f98cb7d2-1443-11f1-9498-ace2d3d13774', 41, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 20:05:59'),
(217, 'f98cbad9-1443-11f1-9498-ace2d3d13774', 41, 'api_call', 'view', 'عرض الفاتورة', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'invoice', '6', NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 21:05:59'),
(218, 'f98cbe7f-1443-11f1-9498-ace2d3d13774', 42, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 01:05:59'),
(219, 'f98cc19b-1443-11f1-9498-ace2d3d13774', 42, 'api_call', 'upload', 'رفع ملف', NULL, '192.168.1.102', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '6', NULL, 3, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 02:05:59'),
(220, 'f98cc755-1443-11f1-9498-ace2d3d13774', 91, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 23:05:59'),
(221, 'f98cca57-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:05:59'),
(222, 'f98ccd77-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'download', 'تحميل تقرير', NULL, '192.168.1.100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 00:35:59'),
(223, 'f98cd0ef-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.14_786d16ca.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '11', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08');
INSERT INTO `user_events` (`id`, `event_id`, `user_id`, `event_type`, `action`, `description`, `details`, `ip_address`, `user_agent`, `device`, `location`, `session_id`, `request_id`, `request_method`, `request_url`, `response_time`, `response_code`, `resource_type`, `resource_id`, `resource_name`, `client_id`, `project_id`, `container_id`, `severity`, `security_level`, `status`, `error_message`, `created_at`) VALUES
(224, 'f98cd3ee-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.15_88a83433.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '12', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(225, 'f98cd94b-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(226, 'f98cdbc9-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '14', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:09:08'),
(227, 'f98cdda8-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_65b2935d.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '15', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(228, 'f98cdf65-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp Image 2025-09-17 at 23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '16', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 03:41:16'),
(229, 'f98ce111-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'إنشاء مجلد: محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '1', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:15'),
(230, 'f98ce2be-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: WhatsApp_Image_2025-09-17_at_23.47.16_de4db3e5.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '17', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:01:43'),
(231, 'f98ce46b-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /محمد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '13', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 04:02:11'),
(232, 'f98ce623-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: applications.html', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '18', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-23 10:40:56'),
(233, 'f98ce7d4-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: deepseek_mermaid_20260105_6dacad.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '19', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:03:32'),
(234, 'f98ce976-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: تنبيه_قانوني.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:04:58'),
(235, 'f98ceb1e-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'حذف ملف', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '20', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:19'),
(236, 'f98cecc1-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'إنشاء مجلد: قاسم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:30'),
(237, 'f98cee60-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: الثغرات.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '21', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(238, 'f98cf004-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: ثغرات_الويب.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '22', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(239, 'f98cf1ac-1443-11f1-9498-ace2d3d13774', 91, 'api_call', 'upload', 'رفع ملف: ملخص_جدار_الحماية.docx', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:05:59'),
(240, 'f98cf353-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:06:28'),
(241, 'f98cf4f3-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:29:54'),
(242, 'f98cf697-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:20'),
(243, 'f98cf845-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:31:25'),
(244, 'f98cfa59-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:32:40'),
(245, 'f98cfc56-1443-11f1-9498-ace2d3d13774', 91, 'api_call', '', 'نقل ملف إلى /', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', '23', NULL, 1, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 01:34:47'),
(246, 'f98cfe2a-1443-11f1-9498-ace2d3d13774', 92, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 20:05:59'),
(247, 'f98cfffa-1443-11f1-9498-ace2d3d13774', 92, 'api_call', 'view', 'عرض الفاتورة', NULL, '192.168.1.101', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'invoice', '6', NULL, 2, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 21:05:59'),
(248, 'f98d0219-1443-11f1-9498-ace2d3d13774', 93, 'login', 'login', 'تسجيل دخول', NULL, '192.168.1.104', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 01:05:59'),
(253, 'f992cd1f-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'create', 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'deployment', '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 00:21:43'),
(254, 'f992dd58-1443-11f1-9498-ace2d3d13774', 57, 'api_call', '', 'إعادة تشغيل الخادم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'server', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 00:46:07'),
(255, 'f992e01f-1443-11f1-9498-ace2d3d13774', 57, 'api_call', '', 'تطبيق تحديث أمني', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-20 01:18:48'),
(256, 'f992e348-1443-11f1-9498-ace2d3d13774', 66, 'api_call', 'create', 'إنشاء عملية نشر جديدة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'deployment', '7', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 00:43:19'),
(257, 'f992e543-1443-11f1-9498-ace2d3d13774', 66, 'api_call', 'restore', 'استعادة نسخة احتياطية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'backup', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-27 00:44:20'),
(260, 'f997b753-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء مستند متطلبات نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-04 22:19:47'),
(261, 'f997c947-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث متطلبات نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-05 22:19:47'),
(262, 'f997cbb0-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'download', 'تحميل مستند المتطلبات', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-09 22:19:47'),
(263, 'f997cdaa-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء مشروع نظام الاستضافة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-14 22:19:47'),
(264, 'f997cfb2-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'export', 'تصدير دليل التثبيت', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-18 22:19:47'),
(265, 'f997d184-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إنشاء قالب متطلبات النظام', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-22 22:19:47'),
(266, 'f997d334-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث قالب المتطلبات', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-27 22:19:47'),
(267, 'f997d4dd-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'view', 'عرض قالب التقرير الأمني', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-01 22:19:47'),
(268, 'f997d698-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'upload', 'رفع مخطط توضيحي', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-05 22:19:47'),
(269, 'f997d850-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'download', 'تحميل قالب دليل المستخدم', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-10 22:19:47'),
(270, 'f997da02-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'import', 'استيراد قالب هيكلية', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-15 22:19:47'),
(271, 'f997dbb4-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'comment', 'إضافة تعليق على هيكلية الشبكة', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 21:49:47'),
(272, 'f997dd57-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'export', 'تصدير كافة مستندات المشروع', NULL, '192.168.1.10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-15 22:19:47'),
(273, 'f997deed-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'create', 'إضافة مستند جديد: ؤ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:22:59'),
(274, 'f997e089-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: needs_work', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:30:37'),
(275, 'f997e21d-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:31:39'),
(276, 'f997e62a-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'update', 'تحديث حالة المستند إلى: under_review', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:31:41'),
(277, 'f997e8f2-1443-11f1-9498-ace2d3d13774', 57, 'project_delete', 'delete', 'حذف مستند: ؤ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '21', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 00:32:06'),
(278, 'f997eaaf-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة متطلبات النظام', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-07 22:19:47'),
(279, 'f997ec4d-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'comment', 'إضافة تعليق على المستند', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-10 22:19:47'),
(280, 'f997edca-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة التقرير الأمني', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-13 22:19:47'),
(281, 'f997ef4b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'view', 'عرض دليل المستخدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-16 22:19:47'),
(282, 'f997f0ca-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'approve', 'الموافقة على دليل المستخدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-21 22:19:47'),
(283, 'f997f246-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'share', 'مشاركة دليل المشرف مع الفريق', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-26 22:19:47'),
(284, 'f997f398-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'create', 'إنشاء تقرير التقدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-30 22:19:47'),
(285, 'f997f5de-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'مراجعة تقرير التقدم', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-04 22:19:47'),
(286, 'f997f753-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'comment', 'إضافة تعليق على التقرير', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-09 22:19:47'),
(287, 'f997f8bd-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'approve', 'الموافقة على التقرير بعد التحديث', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-14 22:19:47'),
(288, 'f997fa1b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'view', 'عرض تفاصيل المشروع', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(289, 'f997fb7d-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'review', 'بدء مراجعة تقييم الأمن', NULL, '192.168.1.11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 22:19:47'),
(290, 'f997fcec-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'create', 'إنشاء تقرير الاختبارات الأمنية', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-06 22:19:47'),
(291, 'f997fe64-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'update', 'تحديث تقرير الاختبارات', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-11 22:19:47'),
(292, 'f997ffd4-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'upload', 'رفع ملف تقرير أمني', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-17 22:19:47'),
(293, 'f998013d-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'download', 'تحميل التقرير الأمني', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-23 22:19:47'),
(294, 'f99802a9-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'comment', 'إضافة تعليق على التقرير', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-28 22:19:47'),
(295, 'f9980415-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'export', 'تصدير توثيق API', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-02 22:19:47'),
(296, 'f9980569-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'create', 'إنشاء تقرير اختبار الاختراق', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-07 22:19:47'),
(297, 'f99806b8-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'archive', 'أرشفة خطة الاختبار', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-12 22:19:47'),
(298, 'f9980803-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'share', 'مشاركة التقرير مع فريق الأمان', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-17 22:19:47'),
(299, 'f9980955-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'update', 'تحديث نتائج الاختبارات', NULL, '192.168.1.12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 17:19:47'),
(300, 'f9980b17-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'create', 'إنشاء مستند متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-12 22:19:47'),
(301, 'f9980c9c-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'comment', 'إضافة تعليق على المتطلبات', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-19 22:19:47'),
(302, 'f9980e06-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'review', 'مراجعة متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-25 22:19:47'),
(303, 'f9980f77-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'update', 'تحديث معلومات المشروع', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-31 22:19:47'),
(304, 'f99814ed-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'approve', 'الموافقة على متطلبات المنصة', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-06 22:19:47'),
(305, 'f998167e-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'create', 'إنشاء دليل استكشاف الأخطاء', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '18', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-11 22:19:47'),
(306, 'f99817ce-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'update', 'تحديث دليل استكشاف الأخطاء', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '18', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 22:19:47'),
(307, 'f99819fb-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'download', 'تحميل دليل التثبيت', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 20:19:47'),
(308, 'f9981cae-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'upload', 'رفع شهادة أمان', NULL, '192.168.1.14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'file', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-13 22:19:47'),
(309, 'f9981e79-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على متطلبات النظام', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-08 22:19:47'),
(310, 'f9982000-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'إنشاء مشروع نظام الحماية', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'project', '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-15 22:19:47'),
(311, 'f998216c-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'update', 'تحديث هيكلية النظام', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-20 22:19:47'),
(312, 'f99822d7-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'إنشاء تقرير الأمن السيبراني', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-24 22:19:47'),
(313, 'f9982434-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على التقرير', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-01-29 22:19:47'),
(314, 'f998263d-1443-11f1-9498-ace2d3d13774', 61, 'project_delete', 'delete', 'حذف تعليق غير مناسب', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'comment', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-03 22:19:47'),
(315, 'f9982797-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'update', 'تحديث قالب الامتثال', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-08 22:19:47'),
(316, 'f99828eb-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'review', 'مراجعة تقرير الاختراق', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'report', '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-13 22:19:47'),
(317, 'f9982a44-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'create', 'بدء إنشاء مستند جديد', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'document', NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(318, 'f9982b8c-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'approve', 'الموافقة على قالب تقييم المخاطر', NULL, '192.168.1.15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'template', '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-14 22:19:47'),
(323, 'f99c7bcd-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'start', 'بدء فحص شامل لنظام البنك الأهلي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 07:30:00'),
(324, 'f99c8ec1-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'discover', 'اكتشاف ثغرة SQL Injection حرجة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 07:45:00'),
(325, 'f99c90d9-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'use', 'تشغيل Nessus لفحص الثغرات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 06:00:00'),
(326, 'f99c926d-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'update', 'تحديث حالة الثغرة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 10:15:00'),
(327, 'f99c9438-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'complete', 'اكتمال فحص الثغرات لمنصة الحكومة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-27 08:30:00'),
(328, 'f99c95c4-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'resolve', 'حل تنبيه تكوين الخادم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-28 11:30:00'),
(329, 'f99c9750-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'generate', 'إنشاء تقرير أمني مفصل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 12:00:00'),
(330, 'f99c98be-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'start', 'بدء فحص منافذ الشبكة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 11:00:00'),
(331, 'f99c9a29-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'complete', 'اكتمال فحص المنافذ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-26 11:45:00'),
(338, 'f9a14e66-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'Access Denied', 'محاولة وصول مرفوضة من IP 45.123.67.89', NULL, '45.123.67.89', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Firewall', '1', NULL, NULL, NULL, NULL, 'warning', 1, 'success', NULL, '2026-02-16 03:37:23'),
(345, 'f9bcf42f-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'هجوم DDoS', 'هجوم حجب خدمة على خوادم الويب', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 02:07:23'),
(346, 'f9bd0330-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'انقطاع خادم التطبيقات', 'خادم التطبيقات 02 توقف عن العمل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 03:07:23'),
(347, 'f9bd05a5-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'محاولات اختراق متكررة', 'محاولات تخمين كلمات مرور من مصادر متعددة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-16 01:07:23'),
(349, 'f9c4ffeb-1443-11f1-9498-ace2d3d13774', 57, 'support_response', 'رد على تذكرة', 'وعليكم السلام، نعم يمكن زيادة السرعة. الرجاء تحديد الخطة المناسبة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(350, 'f9c510a7-1443-11f1-9498-ace2d3d13774', 57, 'support_response', 'رد على تذكرة', 'تظهر رسالة خطأ عند رفع الملفات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(351, 'f9c512cb-1443-11f1-9498-ace2d3d13774', 57, 'support_response', 'رد على تذكرة', 'نعم، نرغب في إضافة جلسة استشارية إضافية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '7', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(352, 'f9c514a3-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'تمت زيادة السرعة إلى 100 Mbps، يرجى التأكد من الأداء', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(353, 'f9c51647-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'تم حل المشكلة، كان هناك خطأ في الصلاحيات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(354, 'f9c517d6-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'الموعد المتوقع للتسليم هو 30 مايو 2024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(355, 'f9c5193b-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'الفاتورة تشمل رسوم التطوير الشهرية وخدمات إضافية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(356, 'f9c51a9a-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'تم تحويل طلبكم للإدارة للنظر في التمديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(357, 'f9c51bfe-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'سنقوم بإضافة نظام التقارير في الإصدار القادم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(358, 'f9c51d52-1443-11f1-9498-ace2d3d13774', 58, 'support_response', 'رد على تذكرة', 'تم إعادة تعيين كلمة المرور، يرجى التحقق من بريدك الإلكتروني', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-21 01:05:58'),
(366, 'f9d0f37f-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'إنشاء مستند', 'دليل تثبيت نظام الاستضافة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(367, 'f9d10266-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'إنشاء مستند', 'دليل مستخدم نظام الاستضافة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(368, 'f9d104a7-1443-11f1-9498-ace2d3d13774', 57, 'project_create', 'إنشاء مستند', 'تقرير التقدم - الربع الأول', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(369, 'f9d10696-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'إنشاء مستند', 'توثيق API - REST', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(370, 'f9d10855-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'إنشاء مستند', 'دليل مستخدم التطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(371, 'f9d109f1-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'إنشاء مستند', 'توثيق API - الدفع', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(372, 'f9d10b6f-1443-11f1-9498-ace2d3d13774', 58, 'project_create', 'إنشاء مستند', 'دليل استكشاف الأخطاء', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '18', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(373, 'f9d10d05-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'إنشاء مستند', 'تقرير الاختبارات الأمنية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(374, 'f9d10e7f-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'إنشاء مستند', 'دليل المشرف', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(375, 'f9d10fea-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'إنشاء مستند', 'دليل تكوين النظام', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(376, 'f9d11159-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'إنشاء مستند', 'خطة اختبار الاختراق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(377, 'f9d11398-1443-11f1-9498-ace2d3d13774', 59, 'project_create', 'إنشاء مستند', 'تقرير الأداء الشهري', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(378, 'f9d11552-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'إنشاء مستند', 'متطلبات المنصة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(379, 'f9d116f5-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'إنشاء مستند', 'دليل تثبيت الشبكة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(380, 'f9d11889-1443-11f1-9498-ace2d3d13774', 60, 'project_create', 'إنشاء مستند', 'هيكلية الشبكة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(381, 'f9d11a46-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'إنشاء مستند', 'تقرير أمني - البنية التحتية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(382, 'f9d11be9-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'إنشاء مستند', 'هيكلية النظام الأمني', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(383, 'f9d11d81-1443-11f1-9498-ace2d3d13774', 61, 'project_create', 'إنشاء مستند', 'تقييم أمن الشبكة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(397, 'f9d4c1bc-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-18 08:15:00'),
(398, 'f9d4d04e-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-20 06:00:00'),
(399, 'f9d4d399-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-25 13:30:00'),
(400, 'f9d51983-1443-11f1-9498-ace2d3d13774', 57, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-03-25 06:00:00'),
(401, 'f9d51d53-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-22 10:20:00'),
(402, 'f9d51fe3-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-23 12:30:00'),
(403, 'f9d52256-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-22 11:15:00'),
(404, 'f9d525b1-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-23 08:30:00'),
(405, 'f9d56ecf-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-24 06:20:00'),
(406, 'f9d57217-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-16 12:45:00'),
(407, 'f9d57460-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-15 08:20:00'),
(408, 'f9d57692-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-17 11:30:00'),
(409, 'f9d578d5-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-03-01 08:30:00'),
(410, 'f9d57b23-1443-11f1-9498-ace2d3d13774', 59, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-03-03 12:20:00'),
(411, 'f9d57dec-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-02-16 06:30:00'),
(412, 'f9d58024-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-02-18 08:45:00'),
(413, 'f9d58286-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-02-18 10:20:00'),
(414, 'f9d584f5-1443-11f1-9498-ace2d3d13774', 60, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-02-20 07:15:00'),
(415, 'f9d58760-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-24 07:45:00'),
(416, 'f9d589a6-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-10 05:45:00'),
(417, 'f9d58bc6-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-12 07:30:00'),
(418, 'f9d58e0a-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-01-14 10:15:00'),
(419, 'f9d5903c-1443-11f1-9498-ace2d3d13774', 61, 'project_update', 'إصدار جديد', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2024-02-20 11:30:00'),
(428, 'f9d8ddbc-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'تعليق جديد', 'تمت إضافة نتائج الاختبارات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(429, 'f9d8ed2b-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'تعليق جديد', 'الصفحة الرئيسية للدليل ممتازة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(430, 'f9d8ef6c-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'نحتاج توثيق أكثر للثغرات المكتشفة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(431, 'f9d8f10d-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'صياغة المقدمة ممتازة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(432, 'f9d8f2a1-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'قسم أوامر الإدارة غير مكتمل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(433, 'f9d8f425-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'تقرير أمني شامل', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(434, 'f9d8f5a5-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'نحتاج أمثلة على التكوين', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(435, 'f9d8f719-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'متطلبات قاعدة البيانات غير واضحة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(436, 'f9d8f88b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'خطوات التثبيت واضحة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(437, 'f9d8f9f4-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'نتائج التقييم دقيقة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(438, 'f9d8fb62-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'تعليق جديد', 'أرقام الأداء تحتاج تدقيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(439, 'f9d8fce8-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'تعليق جديد', 'تم إضافة الأوامر المطلوبة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(440, 'f9d8fe6d-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'تعليق جديد', 'تم تدقيق الأرقام وتحديثها', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(441, 'f9d90003-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'تعليق جديد', 'تم توضيح المتطلبات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(442, 'f9d9017a-1443-11f1-9498-ace2d3d13774', 60, 'api_call', 'تعليق جديد', 'تم تحديث الرسم', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(443, 'f9d90305-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'تعليق جديد', 'قسم المستخدم بحاجة إلى إعادة صياغة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(444, 'f9d90480-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'تعليق جديد', 'يوجد خطأ إملائي في الصفحة 15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(445, 'f9d905f8-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'تعليق جديد', 'رسم الهيكلية يحتاج توضيح', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(446, 'f9d9076d-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'تعليق جديد', 'رسم الشبكة يحتاج تحديث', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(447, 'f9d908d2-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'تعليق جديد', 'إحصائيات التقدم ممتازة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(459, 'f9dcad5b-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-19 02:10:36'),
(460, 'f9dcbf9f-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-20 20:48:00'),
(461, 'f9dcc1f3-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'in_progress', NULL, '2026-02-18 22:19:47'),
(462, 'f9dcc37b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(463, 'f9dcc4e8-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(464, 'f9dcc63f-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(465, 'f9dcc7c5-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(466, 'f9dcc91a-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(467, 'f9dcca5d-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(468, 'f9dccb9a-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '17', NULL, NULL, NULL, NULL, 'info', 1, 'in_progress', NULL, '2026-02-18 22:19:47'),
(469, 'f9dcccd7-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '20', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(470, 'f9dcce68-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(471, 'f9dccfba-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(472, 'f9dcd109-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '16', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(473, 'f9dcd252-1443-11f1-9498-ace2d3d13774', 61, 'api_call', 'مراجعة مستند', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '19', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-18 22:19:47'),
(474, 'f9e09051-1443-11f1-9498-ace2d3d13774', 57, 'export_data', 'تقرير جديد', 'التقرير الشهري - يناير 2024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(475, 'f9e09ec2-1443-11f1-9498-ace2d3d13774', 57, 'export_data', 'تقرير جديد', 'تقرير التقدم الفني - مشروع الاستضافة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(476, 'f9e0a0e0-1443-11f1-9498-ace2d3d13774', 57, 'export_data', 'تقرير جديد', 'التقرير الشهري - فبراير 2024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(477, 'f9e0a28e-1443-11f1-9498-ace2d3d13774', 57, 'export_data', 'تقرير جديد', 'تقرير التقدم - الربع الأول', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47');
INSERT INTO `user_events` (`id`, `event_id`, `user_id`, `event_type`, `action`, `description`, `details`, `ip_address`, `user_agent`, `device`, `location`, `session_id`, `request_id`, `request_method`, `request_url`, `response_time`, `response_code`, `resource_type`, `resource_id`, `resource_name`, `client_id`, `project_id`, `container_id`, `severity`, `security_level`, `status`, `error_message`, `created_at`) VALUES
(478, 'f9e0a42c-1443-11f1-9498-ace2d3d13774', 57, 'export_data', 'تقرير جديد', 'التقرير الشهري - مارس 2024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '13', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(479, 'f9e0a5e1-1443-11f1-9498-ace2d3d13774', 58, 'export_data', 'تقرير جديد', 'تقرير التقدم - منصة التخزين', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(480, 'f9e0a775-1443-11f1-9498-ace2d3d13774', 58, 'export_data', 'تقرير جديد', 'التقرير النهائي - مشروع البنك الأهلي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '7', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(481, 'f9e0a8fd-1443-11f1-9498-ace2d3d13774', 58, 'export_data', 'تقرير جديد', 'تقرير فني - أداء API', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '11', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(482, 'f9e0aad0-1443-11f1-9498-ace2d3d13774', 60, 'export_data', 'تقرير جديد', 'تقرير تدقيق المستندات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '14', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(483, 'f9e0ac5a-1443-11f1-9498-ace2d3d13774', 60, 'export_data', 'تقرير جديد', 'التقرير النهائي - مشروع الشبكات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '15', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(484, 'f9e0adf2-1443-11f1-9498-ace2d3d13774', 61, 'export_data', 'تقرير جديد', 'تقرير الأمن السيبراني - الربع الأول', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(485, 'f9e0af7d-1443-11f1-9498-ace2d3d13774', 61, 'export_data', 'تقرير جديد', 'تقرير التدقيق الأمني - البنية التحتية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(486, 'f9e0b0fd-1443-11f1-9498-ace2d3d13774', 61, 'export_data', 'تقرير جديد', 'تقرير الامتثال - ISO 27001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(487, 'f9e0b273-1443-11f1-9498-ace2d3d13774', 61, 'export_data', 'تقرير جديد', 'تقرير اختبار الاختراق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(488, 'f9e0b3f0-1443-11f1-9498-ace2d3d13774', 61, 'export_data', 'تقرير جديد', 'تقرير الثغرات الأمنية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '12', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 22:19:47'),
(489, 'f9e466d7-1443-11f1-9498-ace2d3d13774', 58, 'backup_create', 'نسخ احتياطي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-19 22:11:06'),
(490, 'f9e478ca-1443-11f1-9498-ace2d3d13774', 58, 'backup_create', 'نسخ احتياطي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-19 22:11:06'),
(491, 'f9e47ada-1443-11f1-9498-ace2d3d13774', 58, 'backup_create', 'نسخ احتياطي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-19 22:11:06'),
(492, 'f9e47cbc-1443-11f1-9498-ace2d3d13774', 59, 'backup_create', 'نسخ احتياطي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2026-02-19 22:11:06'),
(493, 'f9e47e63-1443-11f1-9498-ace2d3d13774', 59, 'backup_create', 'نسخ احتياطي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'failed', NULL, '2026-02-19 22:11:06'),
(496, 'f9e83da2-1443-11f1-9498-ace2d3d13774', 57, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'pending', NULL, '2026-02-20 00:21:43'),
(497, 'f9e84c3b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 22:11:06'),
(498, 'f9e84e4f-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 22:11:06'),
(499, 'f9e8500b-1443-11f1-9498-ace2d3d13774', 58, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'rolled_back', NULL, '2026-02-19 22:11:06'),
(500, 'f9e851d5-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-19 22:11:06'),
(501, 'f9e85373-1443-11f1-9498-ace2d3d13774', 59, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'failed', NULL, '2026-02-19 22:11:06'),
(502, 'f9e855a4-1443-11f1-9498-ace2d3d13774', 66, 'api_call', 'نشر تطبيق', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '7', NULL, NULL, NULL, NULL, 'info', 1, 'pending', NULL, '2026-02-27 00:43:19'),
(503, 'f9ec3ca1-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'فحص شامل - البنك الأهلي', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'in-progress', NULL, '2024-01-28 07:30:00'),
(504, 'f9ec4b55-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'فحص منافذ - الشبكة الداخلية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2024-01-26 11:00:00'),
(505, 'f9ec4d69-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'فحص شبكة - بنك', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2024-01-24 07:00:00'),
(506, 'f9ec4f20-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'فحص استغلال - بنك', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'in-progress', NULL, '2024-01-28 06:00:00'),
(507, 'f9ec50fb-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'فحص ثغرات - الحكومة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2024-01-27 06:00:00'),
(508, 'f9ec52b5-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'فحص تطبيقات ويب - التجارة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'completed', NULL, '2024-01-25 06:30:00'),
(510, 'f9f04a91-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'SQL Injection في نظام الدفع', 'ثغرة تسمح بتنفيذ أوامر SQL غير مصرح بها في نظام الدفع', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(511, 'f9f0593c-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'Cross-Site Scripting (XSS)', 'ثغرة XSS في صفحة تسجيل الدخول تسمح بحقن سكربتات ضارة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(512, 'f9f05b5b-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'تكوين خادم ويب غير آمن', 'إعدادات خادم الويب تسمح بكشف معلومات حساسة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(513, 'f9f05cfe-1443-11f1-9498-ace2d3d13774', 57, 'threat_detected', 'نقص في تشفير البيانات', 'البيانات الحساسة تنتقل بدون تشفير', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(514, 'f9f05ed5-1443-11f1-9498-ace2d3d13774', 58, 'threat_detected', 'سياسة كلمات المرور ضعيفة', 'سياسة كلمات المرور تسمح بكلمات مرور سهلة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(515, 'f9f06061-1443-11f1-9498-ace2d3d13774', 58, 'threat_detected', 'CSRF Vulnerability', 'نقص في حماية CSRF في نموذج تغيير الإعدادات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(516, 'f9f061f0-1443-11f1-9498-ace2d3d13774', 58, 'threat_detected', 'كشف معلومات في headers', 'رؤوس HTTP تكشف معلومات عن الإصدار', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '7', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(517, 'f9f06458-1443-11f1-9498-ace2d3d13774', 59, 'threat_detected', 'نقص في التحقق من المدخلات', 'عدم التحقق من صحة المدخلات في نموذج البحث', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '8', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(518, 'f9f065f1-1443-11f1-9498-ace2d3d13774', 59, 'threat_detected', 'جلسات غير آمنة', 'معرفات الجلسة يمكن تخمينها', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '9', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(519, 'f9f06770-1443-11f1-9498-ace2d3d13774', 59, 'threat_detected', 'رابط معطل', 'روابط تؤدي إلى صفحات غير موجودة', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '10', NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-18 00:55:52'),
(525, 'f9f4746c-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'تطبيق إعدادات أمان قواعد البيانات', 'تأمين قواعد البيانات لمنع هجمات SQL Injection', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, NULL, 'info', 1, 'in-progress', NULL, '2026-02-18 00:55:52'),
(526, 'f9f47897-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'تشفير جميع البيانات الحساسة', 'تطبيق تشفير SSL/TLS على جميع الاتصالات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4', NULL, NULL, NULL, NULL, 'info', 1, 'in-progress', NULL, '2026-02-18 00:55:52'),
(527, 'f9f47a5a-1443-11f1-9498-ace2d3d13774', 57, 'security_alert', 'تحديث خادم الويب', 'تحديث إعدادات خادم الويب لمنع كشف المعلومات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5', NULL, NULL, NULL, NULL, 'info', 1, 'pending', NULL, '2026-02-18 00:55:52'),
(528, 'f9f47bdf-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'تحديث سياسة كلمات المرور', 'تطبيق متطلبات كلمات مرور قوية', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2', NULL, NULL, NULL, NULL, 'info', 1, 'pending', NULL, '2026-02-18 00:55:52'),
(529, 'f9f47d52-1443-11f1-9498-ace2d3d13774', 58, 'security_alert', 'تنفيذ حماية CSRF', 'إضافة رموز حماية CSRF لجميع النماذج', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '6', NULL, NULL, NULL, NULL, 'info', 1, 'pending', NULL, '2026-02-18 00:55:52'),
(530, 'f9f47ec1-1443-11f1-9498-ace2d3d13774', 59, 'security_alert', 'تدريب الموظفين على الأمن السيبراني', 'دورة تدريبية للمطورين حول أمن التطبيقات', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3', NULL, NULL, NULL, NULL, 'info', 1, 'scheduled', NULL, '2026-02-18 00:55:52'),
(535, '290ce584-1454-11f1-9498-ace2d3d13774', 1, '', 'settings_updated', '{\"type\":\"general\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 03:18:32'),
(536, '5a6d0ad1-1454-11f1-9498-ace2d3d13774', 1, '', 'user_updated', '{\"user_id\":\"117\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 03:19:55'),
(537, '5a6d8a45-1454-11f1-9498-ace2d3d13774', 1, '', 'bulk_action', '{\"action\":\"activate\",\"count\":1}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 03:19:55'),
(538, '65ddee28-1459-11f1-9498-ace2d3d13774', 1, '', 'settings_updated', '{\"type\":\"social\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 03:56:01'),
(539, '824fc668-1459-11f1-9498-ace2d3d13774', 1, '', 'settings_updated', '{\"type\":\"site\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 03:56:49'),
(540, '2ce6b877-146c-11f1-9498-ace2d3d13774', 1, '', 'settings_updated', '{\"type\":\"authentication\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 06:10:26'),
(541, '2591b941-146d-11f1-9498-ace2d3d13774', 1, '', 'settings_updated', '{\"type\":\"authentication\"}', NULL, '::1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'info', 1, 'success', NULL, '2026-02-28 06:17:23');

-- --------------------------------------------------------

--
-- بنية الجدول `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `granted` tinyint(1) DEFAULT 1 COMMENT 'true = منح، false = منع',
  `resource_type` varchar(50) DEFAULT NULL COMMENT 'نوع المورد',
  `resource_id` varchar(100) DEFAULT NULL COMMENT 'معرف المورد',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'تاريخ انتهاء الصلاحية',
  `granted_by` int(11) DEFAULT NULL COMMENT 'من قام بالمنح',
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL COMMENT 'سبب المنح'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='الصلاحيات المخصصة للمستخدمين';

-- --------------------------------------------------------

--
-- بنية الجدول `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` bigint(20) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL COMMENT 'mobile, tablet, desktop',
  `browser` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='جلسات المستخدمين النشطة';

-- --------------------------------------------------------

--
-- بنية الجدول `violations`
--

CREATE TABLE `violations` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `standard_id` int(11) DEFAULT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL,
  `status` enum('open','in-progress','resolved') DEFAULT 'open',
  `detected_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `violations`
--

INSERT INTO `violations` (`id`, `title`, `description`, `standard_id`, `severity`, `status`, `detected_date`, `resolved_date`, `assigned_to`, `resolution_notes`, `created_at`) VALUES
(1, 'عدم وجود تسجيل للوصول', 'خادم DB-02 لا يسجل محاولات الوصول', 1, 'critical', 'open', '2024-01-25', NULL, 1, NULL, '2026-02-16 23:30:45'),
(2, 'كلمات مرور ضعيفة', '12 حساب بإعدادات أمان ضعيفة', 2, 'high', 'in-progress', '2024-01-23', NULL, 4, NULL, '2026-02-16 23:30:45'),
(3, 'تحديثات أمنية متأخرة', '3 خوادم تحتاج لتحديثات أمنية', 1, 'medium', 'open', '2024-01-21', NULL, 3, NULL, '2026-02-16 23:30:45'),
(4, 'توثيق غير مكتمل', 'توثيق عمليات المراقبة غير مكتمل', 3, 'medium', 'in-progress', '2024-01-18', NULL, 2, NULL, '2026-02-16 23:30:45');

-- --------------------------------------------------------

--
-- بنية الجدول `vulnerabilities`
--

CREATE TABLE `vulnerabilities` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('web','network','application','configuration','authentication','injection','xss','csrf') NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL,
  `cvss_score` decimal(3,1) DEFAULT NULL,
  `status` enum('open','in-progress','fixed','false-positive','accepted') DEFAULT 'open',
  `discovered_by` int(11) DEFAULT NULL,
  `discovered_date` date DEFAULT NULL,
  `fixed_date` date DEFAULT NULL,
  `remediation_notes` text DEFAULT NULL,
  `affected_component` varchar(255) DEFAULT NULL,
  `cve_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `vulnerabilities`
--

INSERT INTO `vulnerabilities` (`id`, `project_id`, `name`, `description`, `type`, `severity`, `cvss_score`, `status`, `discovered_by`, `discovered_date`, `fixed_date`, `remediation_notes`, `affected_component`, `cve_id`, `created_at`) VALUES
(1, 1, 'SQL Injection في نظام الدفع', 'ثغرة تسمح بتنفيذ أوامر SQL غير مصرح بها في نظام الدفع', 'injection', 'critical', 9.8, 'open', 1, '2024-01-28', NULL, NULL, 'منصة الدفع', 'CVE-2024-1234', '2026-02-18 00:55:52'),
(2, 1, 'Cross-Site Scripting (XSS)', 'ثغرة XSS في صفحة تسجيل الدخول تسمح بحقن سكربتات ضارة', 'xss', 'high', 7.5, 'open', 1, '2024-01-28', NULL, NULL, 'صفحة تسجيل الدخول', NULL, '2026-02-18 00:55:52'),
(3, 1, 'تكوين خادم ويب غير آمن', 'إعدادات خادم الويب تسمح بكشف معلومات حساسة', 'configuration', 'medium', 6.2, 'open', 1, '2024-01-28', NULL, NULL, 'خادم الويب', NULL, '2026-02-18 00:55:52'),
(4, 1, 'نقص في تشفير البيانات', 'البيانات الحساسة تنتقل بدون تشفير', 'application', 'high', 8.0, 'in-progress', 1, '2024-01-27', NULL, NULL, 'API', NULL, '2026-02-18 00:55:52'),
(5, 2, 'سياسة كلمات المرور ضعيفة', 'سياسة كلمات المرور تسمح بكلمات مرور سهلة', 'authentication', 'high', 8.2, 'in-progress', 2, '2024-01-27', NULL, NULL, 'نظام المصادقة', NULL, '2026-02-18 00:55:52'),
(6, 2, 'CSRF Vulnerability', 'نقص في حماية CSRF في نموذج تغيير الإعدادات', 'csrf', 'medium', 6.5, 'open', 2, '2024-01-27', NULL, NULL, 'لوحة الإعدادات', NULL, '2026-02-18 00:55:52'),
(7, 2, 'كشف معلومات في headers', 'رؤوس HTTP تكشف معلومات عن الإصدار', 'web', 'low', 4.0, 'open', 2, '2024-01-27', NULL, NULL, 'خادم الويب', NULL, '2026-02-18 00:55:52'),
(8, 3, 'نقص في التحقق من المدخلات', 'عدم التحقق من صحة المدخلات في نموذج البحث', 'web', 'high', 7.8, 'fixed', 3, '2024-01-25', NULL, NULL, 'نموذج البحث', NULL, '2026-02-18 00:55:52'),
(9, 3, 'جلسات غير آمنة', 'معرفات الجلسة يمكن تخمينها', 'authentication', 'medium', 6.8, 'fixed', 3, '2024-01-25', NULL, NULL, 'نظام الجلسات', NULL, '2026-02-18 00:55:52'),
(10, 3, 'رابط معطل', 'روابط تؤدي إلى صفحات غير موجودة', 'web', 'low', 3.5, 'accepted', 3, '2024-01-24', NULL, NULL, 'التطبيق', NULL, '2026-02-18 00:55:52');

-- --------------------------------------------------------

--
-- بنية الجدول `websites`
--

CREATE TABLE `websites` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `type` enum('website','webapp','api','mobile') DEFAULT 'website',
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `websites`
--

INSERT INTO `websites` (`id`, `client_id`, `name`, `url`, `type`, `status`, `created_at`) VALUES
(1, 1, 'الموقع الرئيسي', 'https://example.com', 'website', 'active', '2026-02-23 02:51:36'),
(2, 1, 'المتجر الإلكتروني', 'https://shop.example.com', 'webapp', 'active', '2026-02-23 02:51:36'),
(3, 2, 'منصة تعليمية', 'https://learn.example.com', 'website', 'active', '2026-02-23 02:51:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type` (`action_type`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `acknowledged_by` (`acknowledged_by`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `alert_rules`
--
ALTER TABLE `alert_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `archived_reports`
--
ALTER TABLE `archived_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_type` (`report_type`),
  ADD KEY `idx_date` (`report_date`);

--
-- Indexes for table `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_id` (`audit_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_key` (`category_key`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `client_activity_log`
--
ALTER TABLE `client_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_type` (`activity_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `client_attachments`
--
ALTER TABLE `client_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `client_clients`
--
ALTER TABLE `client_clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_code` (`client_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`client_code`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `client_contracts`
--
ALTER TABLE `client_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_code` (`contract_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`contract_code`);

--
-- Indexes for table `client_domains`
--
ALTER TABLE `client_domains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_domain` (`domain_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `client_files`
--
ALTER TABLE `client_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `client_folders`
--
ALTER TABLE `client_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `client_invoices`
--
ALTER TABLE `client_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_code` (`invoice_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`invoice_code`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `client_payments`
--
ALTER TABLE `client_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_code` (`payment_code`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_invoice` (`invoice_id`),
  ADD KEY `idx_code` (`payment_code`);

--
-- Indexes for table `client_projects`
--
ALTER TABLE `client_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`project_code`);

--
-- Indexes for table `client_reports`
--
ALTER TABLE `client_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`report_code`);

--
-- Indexes for table `client_requests`
--
ALTER TABLE `client_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `client_service_requests`
--
ALTER TABLE `client_service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`request_code`);

--
-- Indexes for table `client_settings`
--
ALTER TABLE `client_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`);

--
-- Indexes for table `client_stats`
--
ALTER TABLE `client_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_client_date` (`client_id`,`stat_date`);

--
-- Indexes for table `client_support_tickets`
--
ALTER TABLE `client_support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_code` (`ticket_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_code` (`ticket_code`);

--
-- Indexes for table `client_ticket_replies`
--
ALTER TABLE `client_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket` (`ticket_id`);

--
-- Indexes for table `client_websites`
--
ALTER TABLE `client_websites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_domain` (`domain`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `server_id` (`server_id`);

--
-- Indexes for table `cloud_activity_log`
--
ALTER TABLE `cloud_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`activity_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `cloud_backups`
--
ALTER TABLE `cloud_backups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `backup_code` (`backup_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`backup_type`),
  ADD KEY `idx_code` (`backup_code`);

--
-- Indexes for table `cloud_backup_schedules`
--
ALTER TABLE `cloud_backup_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_frequency` (`frequency`);

--
-- Indexes for table `cloud_deployments`
--
ALTER TABLE `cloud_deployments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `deployment_code` (`deployment_code`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_environment` (`environment`),
  ADD KEY `idx_code` (`deployment_code`);

--
-- Indexes for table `cloud_files`
--
ALTER TABLE `cloud_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_folder` (`folder_path`),
  ADD KEY `idx_type` (`file_type`),
  ADD KEY `idx_server` (`server_id`);

--
-- Indexes for table `cloud_file_types_stats`
--
ALTER TABLE `cloud_file_types_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_server` (`server_id`),
  ADD KEY `idx_extension` (`file_extension`);

--
-- Indexes for table `cloud_projects`
--
ALTER TABLE `cloud_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`project_type`),
  ADD KEY `idx_code` (`project_code`);

--
-- Indexes for table `cloud_reports`
--
ALTER TABLE `cloud_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `idx_type` (`report_type`),
  ADD KEY `idx_period` (`period`),
  ADD KEY `idx_code` (`report_code`);

--
-- Indexes for table `cloud_security_updates`
--
ALTER TABLE `cloud_security_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `update_code` (`update_code`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`update_code`);

--
-- Indexes for table `cloud_servers`
--
ALTER TABLE `cloud_servers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `server_code` (`server_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`server_type`),
  ADD KEY `idx_code` (`server_code`);

--
-- Indexes for table `cloud_server_services`
--
ALTER TABLE `cloud_server_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_server` (`server_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cloud_server_stats`
--
ALTER TABLE `cloud_server_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_server` (`server_id`),
  ADD KEY `idx_recorded` (`recorded_at`);

--
-- Indexes for table `cloud_settings`
--
ALTER TABLE `cloud_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `cloud_storage_alerts`
--
ALTER TABLE `cloud_storage_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`),
  ADD KEY `idx_resolved` (`is_resolved`),
  ADD KEY `idx_type` (`alert_type`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `cloud_storage_monitoring`
--
ALTER TABLE `cloud_storage_monitoring`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_server` (`server_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `compliance_standards`
--
ALTER TABLE `compliance_standards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `responsible_unit` (`responsible_unit`);

--
-- Indexes for table `custom_scripts`
--
ALTER TABLE `custom_scripts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_daily_report` (`report_date`,`report_type`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `documentation_activity_log`
--
ALTER TABLE `documentation_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`activity_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Indexes for table `documentation_projects`
--
ALTER TABLE `documentation_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_type` (`project_type`),
  ADD KEY `idx_code` (`project_code`);

--
-- Indexes for table `documentation_stats`
--
ALTER TABLE `documentation_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`stat_date`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_code` (`document_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `parent_document_id` (`parent_document_id`),
  ADD KEY `idx_type` (`document_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`document_code`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `document_comments`
--
ALTER TABLE `document_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_resolved` (`resolved`);

--
-- Indexes for table `document_reviews`
--
ALTER TABLE `document_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_reviewer` (`reviewer_id`);

--
-- Indexes for table `document_sections`
--
ALTER TABLE `document_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_order` (`order_number`);

--
-- Indexes for table `document_tags`
--
ALTER TABLE `document_tags`
  ADD PRIMARY KEY (`document_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_code` (`template_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_code` (`template_code`);

--
-- Indexes for table `document_updates`
--
ALTER TABLE `document_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_version` (`version_number`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hosting_access_logs`
--
ALTER TABLE `hosting_access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_accessed` (`accessed_at`);

--
-- Indexes for table `hosting_backups`
--
ALTER TABLE `hosting_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `hosting_databases`
--
ALTER TABLE `hosting_databases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_db` (`site_id`,`db_name`),
  ADD KEY `idx_site` (`site_id`);

--
-- Indexes for table `hosting_ftp_accounts`
--
ALTER TABLE `hosting_ftp_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD KEY `idx_site` (`site_id`);

--
-- Indexes for table `hosting_plans`
--
ALTER TABLE `hosting_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`),
  ADD KEY `idx_type` (`plan_type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `hosting_security_logs`
--
ALTER TABLE `hosting_security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_type` (`event_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `hosting_sites`
--
ALTER TABLE `hosting_sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `domain_id` (`domain_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `hosting_stats`
--
ALTER TABLE `hosting_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_date` (`stat_date`);

--
-- Indexes for table `hosting_support_requests`
--
ALTER TABLE `hosting_support_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site` (`site_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `kpi_metrics`
--
ALTER TABLE `kpi_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_metric` (`metric_date`,`unit_id`,`metric_name`);

--
-- Indexes for table `live_threats`
--
ALTER TABLE `live_threats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `server_id` (`server_id`);

--
-- Indexes for table `network_events`
--
ALTER TABLE `network_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `server_id` (`server_id`);

--
-- Indexes for table `pending_approvals`
--
ALTER TABLE `pending_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `pentest_activity_log`
--
ALTER TABLE `pentest_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`activity_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `pentest_projects`
--
ALTER TABLE `pentest_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `tester_id` (`tester_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_id` (`permission_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `project_files`
--
ALTER TABLE `project_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_assigned` (`assigned_to`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_code` (`report_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_type` (`report_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_code` (`report_code`);

--
-- Indexes for table `report_documents`
--
ALTER TABLE `report_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_report_document` (`report_id`,`document_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `report_statistics`
--
ALTER TABLE `report_statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `repository_files`
--
ALTER TABLE `repository_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_document` (`document_id`),
  ADD KEY `idx_type` (`file_type`),
  ADD KEY `idx_folder` (`folder_path`);

--
-- Indexes for table `resource_requests`
--
ALTER TABLE `resource_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_id` (`role_id`);

--
-- Indexes for table `security_alerts`
--
ALTER TABLE `security_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `vulnerability_id` (`vulnerability_id`),
  ADD KEY `scan_id` (`scan_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `security_policies`
--
ALTER TABLE `security_policies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `security_recommendations`
--
ALTER TABLE `security_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `vulnerability_id` (`vulnerability_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `security_scans`
--
ALTER TABLE `security_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`scan_type`);

--
-- Indexes for table `security_statistics`
--
ALTER TABLE `security_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date_hour` (`stat_date`,`stat_hour`);

--
-- Indexes for table `servers`
--
ALTER TABLE `servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_code` (`request_code`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `site_stats`
--
ALTER TABLE `site_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_key` (`stat_key`);

--
-- Indexes for table `support_team`
--
ALTER TABLE `support_team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_audits`
--
ALTER TABLE `system_audits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `auditor_id` (`auditor_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_date`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `system_status`
--
ALTER TABLE `system_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `template_variables`
--
ALTER TABLE `template_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_variable` (`template_id`,`variable_key`),
  ADD KEY `idx_template` (`template_id`);

--
-- Indexes for table `testing_tools`
--
ALTER TABLE `testing_tools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `threats`
--
ALTER TABLE `threats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_server_id` (`target_server_id`),
  ADD KEY `mitigated_by` (`mitigated_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_last_seen` (`last_seen`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `head_id` (`head_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `unit_id` (`unit_id`);

--
-- Indexes for table `users_all`
--
ALTER TABLE `users_all`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `status_changed_by` (`status_changed_by`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_role` (`role_id`),
  ADD KEY `idx_unit` (`unit`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_last_login` (`last_login`),
  ADD KEY `idx_manager` (`manager_id`),
  ADD KEY `idx_account_manager` (`account_manager_id`),
  ADD KEY `idx_client_type` (`client_type`);
ALTER TABLE `users_all` ADD FULLTEXT KEY `ft_search` (`full_name`,`email`,`username`,`company_name`);

--
-- Indexes for table `users_login`
--
ALTER TABLE `users_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_events`
--
ALTER TABLE `user_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_type` (`event_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_resource` (`resource_type`,`resource_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_session` (`session_id`);
ALTER TABLE `user_events` ADD FULLTEXT KEY `ft_search` (`description`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`user_id`,`permission_name`,`resource_type`,`resource_id`),
  ADD KEY `granted_by` (`granted_by`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_permission` (`permission_name`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `standard_id` (`standard_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `vulnerabilities`
--
ALTER TABLE `vulnerabilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `discovered_by` (`discovered_by`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `websites`
--
ALTER TABLE `websites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `alert_rules`
--
ALTER TABLE `alert_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `archived_reports`
--
ALTER TABLE `archived_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_findings`
--
ALTER TABLE `audit_findings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `client_activity_log`
--
ALTER TABLE `client_activity_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `client_attachments`
--
ALTER TABLE `client_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_clients`
--
ALTER TABLE `client_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `client_contracts`
--
ALTER TABLE `client_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_domains`
--
ALTER TABLE `client_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_files`
--
ALTER TABLE `client_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `client_folders`
--
ALTER TABLE `client_folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_invoices`
--
ALTER TABLE `client_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_notifications`
--
ALTER TABLE `client_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_payments`
--
ALTER TABLE `client_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_projects`
--
ALTER TABLE `client_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_reports`
--
ALTER TABLE `client_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_requests`
--
ALTER TABLE `client_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `client_service_requests`
--
ALTER TABLE `client_service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_settings`
--
ALTER TABLE `client_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_stats`
--
ALTER TABLE `client_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_support_tickets`
--
ALTER TABLE `client_support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_ticket_replies`
--
ALTER TABLE `client_ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `client_websites`
--
ALTER TABLE `client_websites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cloud_activity_log`
--
ALTER TABLE `cloud_activity_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloud_backups`
--
ALTER TABLE `cloud_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloud_backup_schedules`
--
ALTER TABLE `cloud_backup_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloud_deployments`
--
ALTER TABLE `cloud_deployments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cloud_files`
--
ALTER TABLE `cloud_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `cloud_file_types_stats`
--
ALTER TABLE `cloud_file_types_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cloud_projects`
--
ALTER TABLE `cloud_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloud_reports`
--
ALTER TABLE `cloud_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `cloud_security_updates`
--
ALTER TABLE `cloud_security_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cloud_servers`
--
ALTER TABLE `cloud_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cloud_server_services`
--
ALTER TABLE `cloud_server_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cloud_server_stats`
--
ALTER TABLE `cloud_server_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cloud_settings`
--
ALTER TABLE `cloud_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cloud_storage_alerts`
--
ALTER TABLE `cloud_storage_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cloud_storage_monitoring`
--
ALTER TABLE `cloud_storage_monitoring`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `compliance_standards`
--
ALTER TABLE `compliance_standards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `custom_scripts`
--
ALTER TABLE `custom_scripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `daily_reports`
--
ALTER TABLE `daily_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documentation_activity_log`
--
ALTER TABLE `documentation_activity_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `documentation_projects`
--
ALTER TABLE `documentation_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `documentation_stats`
--
ALTER TABLE `documentation_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `document_comments`
--
ALTER TABLE `document_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `document_reviews`
--
ALTER TABLE `document_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `document_sections`
--
ALTER TABLE `document_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `document_updates`
--
ALTER TABLE `document_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hosting_access_logs`
--
ALTER TABLE `hosting_access_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hosting_backups`
--
ALTER TABLE `hosting_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `hosting_databases`
--
ALTER TABLE `hosting_databases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `hosting_ftp_accounts`
--
ALTER TABLE `hosting_ftp_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `hosting_plans`
--
ALTER TABLE `hosting_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hosting_security_logs`
--
ALTER TABLE `hosting_security_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `hosting_sites`
--
ALTER TABLE `hosting_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `hosting_stats`
--
ALTER TABLE `hosting_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hosting_support_requests`
--
ALTER TABLE `hosting_support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kpi_metrics`
--
ALTER TABLE `kpi_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `live_threats`
--
ALTER TABLE `live_threats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `network_events`
--
ALTER TABLE `network_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pending_approvals`
--
ALTER TABLE `pending_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pentest_activity_log`
--
ALTER TABLE `pentest_activity_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pentest_projects`
--
ALTER TABLE `pentest_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `project_comments`
--
ALTER TABLE `project_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_files`
--
ALTER TABLE `project_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_tasks`
--
ALTER TABLE `project_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `report_documents`
--
ALTER TABLE `report_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `report_statistics`
--
ALTER TABLE `report_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `repository_files`
--
ALTER TABLE `repository_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `resource_requests`
--
ALTER TABLE `resource_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `security_alerts`
--
ALTER TABLE `security_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_policies`
--
ALTER TABLE `security_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `security_recommendations`
--
ALTER TABLE `security_recommendations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_scans`
--
ALTER TABLE `security_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `security_statistics`
--
ALTER TABLE `security_statistics`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `servers`
--
ALTER TABLE `servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `site_stats`
--
ALTER TABLE `site_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_team`
--
ALTER TABLE `support_team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_audits`
--
ALTER TABLE `system_audits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `system_status`
--
ALTER TABLE `system_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `template_variables`
--
ALTER TABLE `template_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `testing_tools`
--
ALTER TABLE `testing_tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `threats`
--
ALTER TABLE `threats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users_all`
--
ALTER TABLE `users_all`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'معرف المستخدم الفريد', AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `users_login`
--
ALTER TABLE `users_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_events`
--
ALTER TABLE `user_events`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=542;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vulnerabilities`
--
ALTER TABLE `vulnerabilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `websites`
--
ALTER TABLE `websites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `alert_rules`
--
ALTER TABLE `alert_rules`
  ADD CONSTRAINT `alert_rules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `archived_reports`
--
ALTER TABLE `archived_reports`
  ADD CONSTRAINT `archived_reports_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `archived_reports_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD CONSTRAINT `audit_findings_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `system_audits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_findings_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_findings_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_activity_log`
--
ALTER TABLE `client_activity_log`
  ADD CONSTRAINT `client_activity_log_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_attachments`
--
ALTER TABLE `client_attachments`
  ADD CONSTRAINT `client_attachments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_contracts`
--
ALTER TABLE `client_contracts`
  ADD CONSTRAINT `client_contracts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_contracts_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_domains`
--
ALTER TABLE `client_domains`
  ADD CONSTRAINT `client_domains_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_domains_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_files`
--
ALTER TABLE `client_files`
  ADD CONSTRAINT `client_files_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_files_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_folders`
--
ALTER TABLE `client_folders`
  ADD CONSTRAINT `client_folders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_invoices`
--
ALTER TABLE `client_invoices`
  ADD CONSTRAINT `client_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_invoices_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `client_invoices_ibfk_3` FOREIGN KEY (`contract_id`) REFERENCES `client_contracts` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD CONSTRAINT `client_notifications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_payments`
--
ALTER TABLE `client_payments`
  ADD CONSTRAINT `client_payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_payments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `client_invoices` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_projects`
--
ALTER TABLE `client_projects`
  ADD CONSTRAINT `client_projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_reports`
--
ALTER TABLE `client_reports`
  ADD CONSTRAINT `client_reports_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_reports_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_requests`
--
ALTER TABLE `client_requests`
  ADD CONSTRAINT `client_requests_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_service_requests`
--
ALTER TABLE `client_service_requests`
  ADD CONSTRAINT `client_service_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_settings`
--
ALTER TABLE `client_settings`
  ADD CONSTRAINT `client_settings_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_stats`
--
ALTER TABLE `client_stats`
  ADD CONSTRAINT `client_stats_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_support_tickets`
--
ALTER TABLE `client_support_tickets`
  ADD CONSTRAINT `client_support_tickets_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `client_clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_support_tickets_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `client_projects` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `client_ticket_replies`
--
ALTER TABLE `client_ticket_replies`
  ADD CONSTRAINT `client_ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `client_support_tickets` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `client_websites`
--
ALTER TABLE `client_websites`
  ADD CONSTRAINT `client_websites_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_websites_ibfk_2` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `cloud_backups`
--
ALTER TABLE `cloud_backups`
  ADD CONSTRAINT `cloud_backups_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `cloud_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cloud_backups_ibfk_2` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `cloud_backup_schedules`
--
ALTER TABLE `cloud_backup_schedules`
  ADD CONSTRAINT `cloud_backup_schedules_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `cloud_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cloud_backup_schedules_ibfk_2` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `cloud_deployments`
--
ALTER TABLE `cloud_deployments`
  ADD CONSTRAINT `cloud_deployments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `cloud_projects` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_files`
--
ALTER TABLE `cloud_files`
  ADD CONSTRAINT `cloud_files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `cloud_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cloud_files_ibfk_2` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `cloud_file_types_stats`
--
ALTER TABLE `cloud_file_types_stats`
  ADD CONSTRAINT `cloud_file_types_stats_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_projects`
--
ALTER TABLE `cloud_projects`
  ADD CONSTRAINT `cloud_projects_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `cloud_security_updates`
--
ALTER TABLE `cloud_security_updates`
  ADD CONSTRAINT `cloud_security_updates_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cloud_security_updates_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `cloud_projects` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_server_services`
--
ALTER TABLE `cloud_server_services`
  ADD CONSTRAINT `cloud_server_services_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_server_stats`
--
ALTER TABLE `cloud_server_stats`
  ADD CONSTRAINT `cloud_server_stats_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_storage_alerts`
--
ALTER TABLE `cloud_storage_alerts`
  ADD CONSTRAINT `cloud_storage_alerts_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `cloud_storage_monitoring`
--
ALTER TABLE `cloud_storage_monitoring`
  ADD CONSTRAINT `cloud_storage_monitoring_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `cloud_servers` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `compliance_standards`
--
ALTER TABLE `compliance_standards`
  ADD CONSTRAINT `compliance_standards_ibfk_1` FOREIGN KEY (`responsible_unit`) REFERENCES `units` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `custom_scripts`
--
ALTER TABLE `custom_scripts`
  ADD CONSTRAINT `custom_scripts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `daily_reports`
--
ALTER TABLE `daily_reports`
  ADD CONSTRAINT `daily_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `documentation_activity_log`
--
ALTER TABLE `documentation_activity_log`
  ADD CONSTRAINT `documentation_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `documentation_projects`
--
ALTER TABLE `documentation_projects`
  ADD CONSTRAINT `documentation_projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `documentation_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_4` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_6` FOREIGN KEY (`template_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_7` FOREIGN KEY (`parent_document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `document_comments`
--
ALTER TABLE `document_comments`
  ADD CONSTRAINT `document_comments_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_comments_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `document_reviews`
--
ALTER TABLE `document_reviews`
  ADD CONSTRAINT `document_reviews_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `document_sections`
--
ALTER TABLE `document_sections`
  ADD CONSTRAINT `document_sections_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `document_tags`
--
ALTER TABLE `document_tags`
  ADD CONSTRAINT `document_tags_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `document_templates`
--
ALTER TABLE `document_templates`
  ADD CONSTRAINT `document_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `document_updates`
--
ALTER TABLE `document_updates`
  ADD CONSTRAINT `document_updates_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_updates_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_updates_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `document_versions`
--
ALTER TABLE `document_versions`
  ADD CONSTRAINT `document_versions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `document_versions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `hosting_access_logs`
--
ALTER TABLE `hosting_access_logs`
  ADD CONSTRAINT `hosting_access_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `hosting_backups`
--
ALTER TABLE `hosting_backups`
  ADD CONSTRAINT `hosting_backups_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `hosting_databases`
--
ALTER TABLE `hosting_databases`
  ADD CONSTRAINT `hosting_databases_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `hosting_ftp_accounts`
--
ALTER TABLE `hosting_ftp_accounts`
  ADD CONSTRAINT `hosting_ftp_accounts_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `hosting_security_logs`
--
ALTER TABLE `hosting_security_logs`
  ADD CONSTRAINT `hosting_security_logs_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `hosting_stats`
--
ALTER TABLE `hosting_stats`
  ADD CONSTRAINT `hosting_stats_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hosting_sites` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `project_comments`
--
ALTER TABLE `project_comments`
  ADD CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `project_files`
--
ALTER TABLE `project_files`
  ADD CONSTRAINT `project_files_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD CONSTRAINT `project_tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `users_all`
--
ALTER TABLE `users_all`
  ADD CONSTRAINT `users_all_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users_all` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_all_ibfk_2` FOREIGN KEY (`account_manager_id`) REFERENCES `users_all` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_all_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users_all` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_all_ibfk_4` FOREIGN KEY (`status_changed_by`) REFERENCES `users_all` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `user_events`
--
ALTER TABLE `user_events`
  ADD CONSTRAINT `user_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_all` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_events_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users_all` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_all` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `users_all` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_all` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
