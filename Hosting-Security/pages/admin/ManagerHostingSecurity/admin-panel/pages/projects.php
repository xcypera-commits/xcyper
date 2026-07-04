<?php
/**
 * إدارة المشاريع
 * Projects Management Page
 */

/**
 * إدارة المشاريع
 * Projects Management Page
 */

// تعريف ثابت للوصول
define('ADMIN_ACCESS', true);
require_once '../../../../../security-init.php';
// تضمين الملفات الأساسية
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// طلب تسجيل الدخول وصلاحية المدير


// معالجة الإجراءات
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_project':
                // إضافة مشروع جديد
                $projectData = [
                    'project_code' => sanitize_input($_POST['project_code']),
                    'project_name' => sanitize_input($_POST['project_name']),
                    'project_type' => sanitize_input($_POST['project_type']),
                    'description' => sanitize_input($_POST['description']),
                    'status' => sanitize_input($_POST['status']),
                    'priority' => sanitize_input($_POST['priority']),
                    'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                    'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                    'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : 0,
                    'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null
                ];
                
                $result = add_project($projectData);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'project_created', ['code' => $projectData['project_code']]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'edit_project':
                // تعديل مشروع
                $projectId = (int)$_POST['project_id'];
                $projectData = [
                    'project_name' => sanitize_input($_POST['project_name']),
                    'project_type' => sanitize_input($_POST['project_type']),
                    'description' => sanitize_input($_POST['description']),
                    'status' => sanitize_input($_POST['status']),
                    'priority' => sanitize_input($_POST['priority']),
                    'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                    'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                    'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : 0,
                    'manager_id' => !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null,
                    'progress' => (int)$_POST['progress']
                ];
                
                $result = update_project($projectId, $projectData);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'project_updated', ['id' => $projectId]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'delete_project':
                // حذف مشروع
                $projectId = (int)$_POST['project_id'];
                $result = delete_project($projectId);
                
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'project_deleted', ['id' => $projectId]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'update_progress':
                // تحديث نسبة التقدم
                $projectId = (int)$_POST['project_id'];
                $progress = (int)$_POST['progress'];
                
                $stmt = $db->prepare("UPDATE projects SET progress = ? WHERE id = ?");
                $stmt->execute([$progress, $projectId]);
                
                log_activity($_SESSION['user_id'], 'progress_updated', ['id' => $projectId, 'progress' => $progress]);
                set_success('تم تحديث نسبة التقدم بنجاح');
                break;
                
            case 'add_task':
                // إضافة مهمة لمشروع
                $taskData = [
                    'project_id' => (int)$_POST['project_id'],
                    'task_name' => sanitize_input($_POST['task_name']),
                    'description' => sanitize_input($_POST['description']),
                    'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                    'priority' => sanitize_input($_POST['priority']),
                    'status' => sanitize_input($_POST['status']),
                    'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null
                ];
                
                $result = add_task($taskData);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'task_created', ['project' => $taskData['project_id']]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'update_task':
                // تحديث مهمة
                $taskId = (int)$_POST['task_id'];
                $taskData = [
                    'task_name' => sanitize_input($_POST['task_name']),
                    'description' => sanitize_input($_POST['description']),
                    'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
                    'priority' => sanitize_input($_POST['priority']),
                    'status' => sanitize_input($_POST['status']),
                    'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null
                ];
                
                $result = update_task($taskId, $taskData);
                if ($result['success']) {
                    log_activity($_SESSION['user_id'], 'task_updated', ['id' => $taskId]);
                    set_success($result['message']);
                } else {
                    set_error($result['message']);
                }
                break;
                
            case 'delete_task':
                // حذف مهمة
                $taskId = (int)$_POST['task_id'];
                
                $stmt = $db->prepare("DELETE FROM project_tasks WHERE id = ?");
                $stmt->execute([$taskId]);
                
                log_activity($_SESSION['user_id'], 'task_deleted', ['id' => $taskId]);
                set_success('تم حذف المهمة بنجاح');
                break;
        }
    } catch (PDOException $e) {
        set_error('خطأ في قاعدة البيانات: ' . $e->getMessage());
    } catch (Exception $e) {
        set_error('حدث خطأ: ' . $e->getMessage());
    }
    
    redirect('projects.php');
}

// دوال المشاريع
function add_project($data) {
    global $db;
    
    try {
        // التحقق من عدم تكرار كود المشروع
        $check = $db->prepare("SELECT id FROM projects WHERE project_code = ?");
        $check->execute([$data['project_code']]);
        
        if ($check->fetch()) {
            return ['success' => false, 'message' => 'كود المشروع موجود بالفعل'];
        }
        
        $stmt = $db->prepare("
            INSERT INTO projects (
                project_code, project_name, project_type, description,
                status, priority, start_date, deadline, budget, manager_id,
                progress, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())
        ");
        
        $stmt->execute([
            $data['project_code'],
            $data['project_name'],
            $data['project_type'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['start_date'],
            $data['deadline'],
            $data['budget'],
            $data['manager_id'],
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'message' => 'تم إضافة المشروع بنجاح', 'id' => $db->lastInsertId()];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

function update_project($id, $data) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE projects SET
                project_name = ?,
                project_type = ?,
                description = ?,
                status = ?,
                priority = ?,
                start_date = ?,
                deadline = ?,
                budget = ?,
                manager_id = ?,
                progress = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['project_name'],
            $data['project_type'],
            $data['description'],
            $data['status'],
            $data['priority'],
            $data['start_date'],
            $data['deadline'],
            $data['budget'],
            $data['manager_id'],
            $data['progress'],
            $id
        ]);
        
        return ['success' => true, 'message' => 'تم تحديث المشروع بنجاح'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

function delete_project($id) {
    global $db;
    
    try {
        // حذف المهام المرتبطة أولاً
        $db->prepare("DELETE FROM project_tasks WHERE project_id = ?")->execute([$id]);
        
        // حذف المشروع
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        
        return ['success' => true, 'message' => 'تم حذف المشروع بنجاح'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

function add_task($data) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO project_tasks (
                project_id, task_name, description, assigned_to,
                priority, status, due_date, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['project_id'],
            $data['task_name'],
            $data['description'],
            $data['assigned_to'],
            $data['priority'],
            $data['status'],
            $data['due_date'],
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'message' => 'تم إضافة المهمة بنجاح'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

function update_task($id, $data) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            UPDATE project_tasks SET
                task_name = ?,
                description = ?,
                assigned_to = ?,
                priority = ?,
                status = ?,
                due_date = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['task_name'],
            $data['description'],
            $data['assigned_to'],
            $data['priority'],
            $data['status'],
            $data['due_date'],
            $id
        ]);
        
        return ['success' => true, 'message' => 'تم تحديث المهمة بنجاح'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
    }
}

// إنشاء الجداول إذا لم تكن موجودة
try {
    // جدول المشاريع
    $db->query("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_code VARCHAR(50) UNIQUE NOT NULL,
            project_name VARCHAR(255) NOT NULL,
            project_type ENUM('hosting', 'storage', 'security', 'pentest', 'consultation', 'development') DEFAULT 'development',
            description TEXT,
            status ENUM('pending', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            progress INT DEFAULT 0,
            start_date DATE NULL,
            deadline DATE NULL,
            completion_date DATE NULL,
            budget DECIMAL(15,2) DEFAULT 0,
            manager_id INT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_manager (manager_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // جدول مهام المشاريع
    $db->query("
        CREATE TABLE IF NOT EXISTS project_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            task_name VARCHAR(255) NOT NULL,
            description TEXT,
            assigned_to INT NULL,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            due_date DATE NULL,
            completed_at TIMESTAMP NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project (project_id),
            INDEX idx_assigned (assigned_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // جدول تعليقات المشاريع
    $db->query("
        CREATE TABLE IF NOT EXISTS project_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // جدول ملفات المشاريع
    $db->query("
        CREATE TABLE IF NOT EXISTS project_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            uploaded_by INT NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            INDEX idx_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
} catch (PDOException $e) {
    error_log("Table creation error: " . $e->getMessage());
}

// جلب المشاريع
try {
    // استعلام بدون client_id
    $stmt = $db->query("
        SELECT p.*, 
               u.full_name as manager_name,
               (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id) as tasks_count,
               (SELECT COUNT(*) FROM project_tasks WHERE project_id = p.id AND status = 'completed') as completed_tasks
        FROM projects p
        LEFT JOIN users_all u ON p.manager_id = u.id
        ORDER BY p.created_at DESC
    ");
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
    set_error('خطأ في جلب المشاريع: ' . $e->getMessage());
}

// جلب العملاء للقوائم المنسدلة
try {
    $clients = $db->query("SELECT id, full_name, company_name FROM client_clients ORDER BY full_name")->fetchAll();
} catch (PDOException $e) {
    $clients = [];
}

// جلب المستخدمين (المدراء والموظفين)
try {
    $users = $db->query("
        SELECT id, username, full_name 
        FROM users_all 
        WHERE user_type IN ('admin', 'manager', 'pms_staff') 
        AND deleted_at IS NULL 
        ORDER BY full_name
    ")->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

// إحصائيات المشاريع
$stats = [
    'total' => count($projects),
    'active' => count(array_filter($projects, function($p) { return $p['status'] === 'active'; })),
    'pending' => count(array_filter($projects, function($p) { return $p['status'] === 'pending'; })),
    'completed' => count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })),
    'cancelled' => count(array_filter($projects, function($p) { return $p['status'] === 'cancelled'; })),
    'total_budget' => array_sum(array_column($projects, 'budget'))
];

// الحصول على المستخدم الحالي
$currentUser = current_user();
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشاريع - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-right: 4px solid transparent;
            text-decoration: none;
        }
        
        .nav-link i {
            margin-left: 12px;
            width: 20px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.15;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        /* بطاقة المشروع */
        .project-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            border-right: 4px solid transparent;
        }
        
        .project-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .project-card.status-pending { border-right-color: #ffc107; }
        .project-card.status-active { border-right-color: #28a745; }
        .project-card.status-completed { border-right-color: #17a2b8; }
        .project-card.status-cancelled { border-right-color: #dc3545; }
        
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .project-code {
            font-size: 0.8rem;
            color: #6c757d;
            font-family: monospace;
        }
        
        .project-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .project-client {
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .project-meta {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meta-item i {
            width: 16px;
            color: #6c757d;
        }
        
        /* شريط التقدم */
        .progress-wrapper {
            margin: 15px 0;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to left, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        /* شارات */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-paused { background: #e2e3e5; color: #383d41; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .priority-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-critical { background: #dc3545; color: white; }
        
        /* شارة النوع */
        .type-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            background: #e9ecef;
            color: #495057;
        }
        
        /* بطاقة المهمة */
        .task-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-right: 3px solid transparent;
        }
        
        .task-item.priority-low { border-right-color: #28a745; }
        .task-item.priority-medium { border-right-color: #ffc107; }
        .task-item.priority-high { border-right-color: #fd7e14; }
        .task-item.priority-critical { border-right-color: #dc3545; }
        
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }
        
        .task-name {
            font-weight: 600;
        }
        
        .task-meta {
            display: flex;
            gap: 15px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* التجاوب */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
                transition: right 0.3s;
            }
            
            .sidebar.show {
                right: 0;
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .project-meta {
                flex-wrap: wrap;
            }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        /* شاشة التحميل */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading.show {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- شاشة التحميل -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- زر القائمة للجوال -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- الشريط الجانبي -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-3x"></i>
            <h4 class="mt-2"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['username']); ?></h4>
            <small>مدير النظام</small>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> لوحة التحكم
            </a>
            <a href="users-management.php" class="nav-link">
                <i class="fas fa-users"></i> إدارة المستخدمين
            </a>
            <a href="roles-permissions.php" class="nav-link">
                <i class="fas fa-key"></i> الأدوار والصلاحيات
            </a>
            <a href="audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <a href="security-settings.php" class="nav-link">
                <i class="fas fa-cog"></i> إعدادات الأمان
            </a>
            <a href="projects.php" class="nav-link active">
                <i class="fas fa-project-diagram"></i> إدارة المشاريع
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="../../index.php" class="nav-link">
                <i class="fas fa-globe"></i> الموقع الرئيسي
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> تسجيل خروج
            </a>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-project-diagram text-primary me-2"></i>
                إدارة المشاريع
            </h2>
            <div>
                <button class="btn btn-primary" onclick="openAddProjectModal()">
                    <i class="fas fa-plus-circle me-1"></i>
                    مشروع جديد
                </button>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php echo display_messages(); ?>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">إجمالي المشاريع</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <i class="fas fa-diagram-project stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">مشاريع نشطة</div>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <i class="fas fa-play-circle stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">مشاريع مكتملة</div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <i class="fas fa-check-circle stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">الميزانية الإجمالية</div>
                <div class="stat-value"><?php echo number_format($stats['total_budget'], 2); ?></div>
                <i class="fas fa-coins stat-icon"></i>
            </div>
        </div>
<!-- قائمة المشاريع -->
<div class="row">
    <?php 
    // التأكد من أن $projects موجود ومصفوفة
    if (isset($projects) && is_array($projects) && !empty($projects)): 
        foreach ($projects as $project): 
            // التأكد من أن $project مصفوفة
            if (!is_array($project)) continue;
            
            // استخدام قيم افتراضية لكل متغير
            $progress = isset($project['progress']) ? (int)$project['progress'] : 0;
            $statusClass = $project['status'] ?? 'pending';
            $priorityClass = $project['priority'] ?? 'medium';
            $projectCode = htmlspecialchars($project['project_code'] ?? 'N/A');
            $projectName = htmlspecialchars($project['project_name'] ?? 'بدون اسم');
            $clientName = htmlspecialchars($project['client_name'] ?? 'عميل غير محدد');
            $startDate = !empty($project['start_date']) ? date('Y-m-d', strtotime($project['start_date'])) : 'غير محدد';
            $deadline = !empty($project['deadline']) ? date('Y-m-d', strtotime($project['deadline'])) : 'غير محدد';
            $completedTasks = $project['completed_tasks'] ?? 0;
            $tasksCount = $project['tasks_count'] ?? 0;
            $managerName = htmlspecialchars($project['manager_name'] ?? 'غير محدد');
            $description = htmlspecialchars($project['description'] ?? '');
            $projectId = $project['id'] ?? 0;
            
            // تجاهل المشاريع بدون ID
            if ($projectId == 0) continue;
    ?>
    <div class="col-md-6">
        <div class="project-card status-<?php echo $statusClass; ?>">
            <div class="project-header">
                <div>
                    <span class="project-code"><?php echo $projectCode; ?></span>
                    <h4 class="project-name"><?php echo $projectName; ?></h4>
                    <div class="project-client">
                        <i class="fas fa-building me-1"></i>
                        <?php echo $clientName; ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <span class="status-badge status-<?php echo $statusClass; ?>">
                        <?php 
                        echo match($statusClass) {
                            'pending' => 'معلق',
                            'active' => 'نشط',
                            'paused' => 'متوقف',
                            'completed' => 'مكتمل',
                            'cancelled' => 'ملغي',
                            default => $statusClass
                        };
                        ?>
                    </span>
                    <span class="priority-badge priority-<?php echo $priorityClass; ?>">
                        <?php 
                        echo match($priorityClass) {
                            'low' => 'منخفضة',
                            'medium' => 'متوسطة',
                            'high' => 'عالية',
                            'critical' => 'حرجة',
                            default => $priorityClass
                        };
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="project-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>البداية: <?php echo $startDate; ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-hourglass-end"></i>
                    <span>النهاية: <?php echo $deadline; ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-tasks"></i>
                    <span><?php echo $completedTasks; ?>/<?php echo $tasksCount; ?> مهام</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user-tie"></i>
                    <span><?php echo $managerName; ?></span>
                </div>
            </div>
            
            <div class="progress-wrapper">
                <div class="progress-info">
                    <span>نسبة الإنجاز</span>
                    <span><?php echo $progress; ?>%</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                </div>
            </div>
            
            <div class="mt-3">
                <p class="text-muted small">
                    <?php 
                    if (!empty($description)) {
                        echo nl2br(htmlspecialchars(mb_substr($description, 0, 100)));
                        if (strlen($description) > 100) echo '...';
                    } else {
                        echo 'لا يوجد وصف';
                    }
                    ?>
                </p>
            </div>
            
            <div class="mt-3 d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-info" onclick="viewProject(<?php echo $projectId; ?>)">
                    <i class="fas fa-eye"></i> تفاصيل
                </button>
                <button class="btn btn-sm btn-warning" onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                    <i class="fas fa-edit"></i> تعديل
                </button>
                <button class="btn btn-sm btn-success" onclick="manageTasks(<?php echo $projectId; ?>, '<?php echo htmlspecialchars($projectName); ?>')">
                    <i class="fas fa-tasks"></i> مهام
                </button>
                <?php if ($statusClass !== 'completed'): ?>
                <button class="btn btn-sm btn-primary" onclick="updateProgress(<?php echo $projectId; ?>, <?php echo $progress; ?>)">
                    <i class="fas fa-chart-line"></i> تقدم
                </button>
                <?php endif; ?>
                <button class="btn btn-sm btn-danger" onclick="deleteProject(<?php echo $projectId; ?>, '<?php echo htmlspecialchars($projectName); ?>')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php 
        endforeach; 
    else: 
    ?>
    <div class="col-12">
        <div class="text-center text-muted py-5">
            <i class="fas fa-folder-open fa-4x mb-3"></i>
            <h5>لا توجد مشاريع بعد</h5>
            <p>قم بإضافة مشروع جديد للبدء</p>
            <button class="btn btn-primary mt-3" onclick="openAddProjectModal()">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة مشروع
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>
    <!-- مودال إضافة مشروع -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        إضافة مشروع جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_project">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كود المشروع <span class="text-danger">*</span></label>
                                <input type="text" name="project_code" class="form-control" required 
                                       placeholder="PRJ-2024-001">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المشروع <span class="text-danger">*</span></label>
                                <input type="text" name="project_name" class="form-control" required 
                                       placeholder="نظام الحماية المتكامل">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">العميل</label>
                                <select name="client_id" class="form-select select2">
                                    <option value="">اختر العميل</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['full_name'] . ($client['company_name'] ? ' - ' . $client['company_name'] : '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">نوع المشروع</label>
                                <select name="project_type" class="form-select">
                                    <option value="hosting">استضافة</option>
                                    <option value="storage">تخزين سحابي</option>
                                    <option value="security">أمن معلومات</option>
                                    <option value="pentest">اختبار اختراق</option>
                                    <option value="consultation">استشارات</option>
                                    <option value="development">تطوير</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="pending">معلق</option>
                                    <option value="active">نشط</option>
                                    <option value="paused">متوقف</option>
                                    <option value="completed">مكتمل</option>
                                    <option value="cancelled">ملغي</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الأولوية</label>
                                <select name="priority" class="form-select">
                                    <option value="low">منخفضة</option>
                                    <option value="medium" selected>متوسطة</option>
                                    <option value="high">عالية</option>
                                    <option value="critical">حرجة</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مدير المشروع</label>
                                <select name="manager_id" class="form-select select2">
                                    <option value="">اختر المدير</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الموعد النهائي</label>
                                <input type="date" name="deadline" class="form-control">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الميزانية</label>
                                <input type="number" name="budget" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> إضافة المشروع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال تعديل مشروع -->
    <div class="modal fade" id="editProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        تعديل المشروع
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit_project">
                    <input type="hidden" name="project_id" id="edit_project_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المشروع</label>
                                <input type="text" name="project_name" id="edit_project_name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">العميل</label>
                                <select name="client_id" id="edit_client_id" class="form-select select2">
                                    <option value="">اختر العميل</option>
                                    <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['full_name'] . ($client['company_name'] ? ' - ' . $client['company_name'] : '')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع المشروع</label>
                                <select name="project_type" id="edit_project_type" class="form-select">
                                    <option value="hosting">استضافة</option>
                                    <option value="storage">تخزين سحابي</option>
                                    <option value="security">أمن معلومات</option>
                                    <option value="pentest">اختبار اختراق</option>
                                    <option value="consultation">استشارات</option>
                                    <option value="development">تطوير</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الحالة</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="pending">معلق</option>
                                    <option value="active">نشط</option>
                                    <option value="paused">متوقف</option>
                                    <option value="completed">مكتمل</option>
                                    <option value="cancelled">ملغي</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الأولوية</label>
                                <select name="priority" id="edit_priority" class="form-select">
                                    <option value="low">منخفضة</option>
                                    <option value="medium">متوسطة</option>
                                    <option value="high">عالية</option>
                                    <option value="critical">حرجة</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نسبة التقدم</label>
                                <input type="range" name="progress" id="edit_progress" class="form-range" min="0" max="100" step="1" value="0">
                                <span id="progress_value" class="badge bg-primary">0%</span>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مدير المشروع</label>
                                <select name="manager_id" id="edit_manager_id" class="form-select select2">
                                    <option value="">اختر المدير</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">الميزانية</label>
                                <input type="number" name="budget" id="edit_budget" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">تاريخ البداية</label>
                                <input type="date" name="start_date" id="edit_start_date" class="form-control">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الموعد النهائي</label>
                                <input type="date" name="deadline" id="edit_deadline" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> تحديث المشروع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال إدارة المهام -->
    <div class="modal fade" id="tasksModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks me-2"></i>
                        إدارة المهام - <span id="tasks_project_name"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="tasks_project_id">
                    
                    <!-- نموذج إضافة مهمة -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">إضافة مهمة جديدة</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="taskForm">
                                <input type="hidden" name="action" value="add_task">
                                <input type="hidden" name="project_id" id="task_project_id">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">اسم المهمة</label>
                                        <input type="text" name="task_name" class="form-control" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">مسندة إلى</label>
                                        <select name="assigned_to" class="form-select select2">
                                            <option value="">اختر الموظف</option>
                                            <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['id']; ?>">
                                                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">الوصف</label>
                                    <textarea name="description" class="form-control" rows="2"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">الأولوية</label>
                                        <select name="priority" class="form-select">
                                            <option value="low">منخفضة</option>
                                            <option value="medium" selected>متوسطة</option>
                                            <option value="high">عالية</option>
                                            <option value="critical">حرجة</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">الحالة</label>
                                        <select name="status" class="form-select">
                                            <option value="pending">معلق</option>
                                            <option value="in_progress">قيد التنفيذ</option>
                                            <option value="completed">مكتمل</option>
                                            <option value="cancelled">ملغي</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">تاريخ الاستحقاق</label>
                                        <input type="date" name="due_date" class="form-control">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> إضافة المهمة
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- قائمة المهام -->
                    <div id="tasks_list">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">جاري التحميل...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال تحديث التقدم -->
    <div class="modal fade" id="progressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-line me-2"></i>
                        تحديث نسبة التقدم
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_progress">
                    <input type="hidden" name="project_id" id="progress_project_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">نسبة التقدم</label>
                            <input type="range" name="progress" id="progress_slider" class="form-range" min="0" max="100" step="1" value="0">
                            <div class="text-center mt-2">
                                <span id="progress_display" class="badge bg-primary fs-6">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مودال حذف مشروع -->
    <div class="modal fade" id="deleteProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        تأكيد الحذف
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف المشروع <strong id="delete_project_name"></strong>؟</p>
                    <p class="text-danger">سيتم حذف جميع المهام والملفات المرتبطة بهذا المشروع!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_project">
                        <input type="hidden" name="project_id" id="delete_project_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">حذف المشروع</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // تهيئة Select2
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });
        
        // التحكم في الشريط الجانبي
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // إخفاء شاشة التحميل
        window.addEventListener('load', function() {
            document.getElementById('loading').classList.remove('show');
        });
        
        // فتح مودال إضافة مشروع
        function openAddProjectModal() {
            new bootstrap.Modal(document.getElementById('addProjectModal')).show();
        }
        
        // تعديل مشروع
        function editProject(project) {
            document.getElementById('edit_project_id').value = project.id;
            document.getElementById('edit_project_name').value = project.project_name;
            document.getElementById('edit_client_id').value = project.client_id || '';
            document.getElementById('edit_description').value = project.description || '';
            document.getElementById('edit_project_type').value = project.project_type;
            document.getElementById('edit_status').value = project.status;
            document.getElementById('edit_priority').value = project.priority;
            document.getElementById('edit_progress').value = project.progress || 0;
            document.getElementById('progress_value').innerText = (project.progress || 0) + '%';
            document.getElementById('edit_manager_id').value = project.manager_id || '';
            document.getElementById('edit_budget').value = project.budget || 0;
            document.getElementById('edit_start_date').value = project.start_date || '';
            document.getElementById('edit_deadline').value = project.deadline || '';
            
            // تحديث Select2
            $('#edit_client_id').trigger('change');
            $('#edit_manager_id').trigger('change');
            
            new bootstrap.Modal(document.getElementById('editProjectModal')).show();
        }
        
        // عرض شريط التقدم
        document.getElementById('edit_progress')?.addEventListener('input', function() {
            document.getElementById('progress_value').innerText = this.value + '%';
        });
        
        // إدارة المهام
        function manageTasks(projectId, projectName) {
            document.getElementById('tasks_project_id').value = projectId;
            document.getElementById('task_project_id').value = projectId;
            document.getElementById('tasks_project_name').innerText = projectName;
            
            // تحميل المهام
            loadTasks(projectId);
            
            new bootstrap.Modal(document.getElementById('tasksModal')).show();
        }
        
        // تحميل المهام
        function loadTasks(projectId) {
            fetch(`get_tasks.php?project_id=${projectId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('tasks_list').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('tasks_list').innerHTML = '<div class="alert alert-danger">حدث خطأ في تحميل المهام</div>';
                });
        }
        
        // تحديث التقدم
        function updateProgress(projectId, currentProgress) {
            document.getElementById('progress_project_id').value = projectId;
            document.getElementById('progress_slider').value = currentProgress;
            document.getElementById('progress_display').innerText = currentProgress + '%';
            
            new bootstrap.Modal(document.getElementById('progressModal')).show();
        }
        
        // تحديث عرض التقدم
        document.getElementById('progress_slider')?.addEventListener('input', function() {
            document.getElementById('progress_display').innerText = this.value + '%';
        });
        
        // حذف مشروع
        function deleteProject(id, name) {
            document.getElementById('delete_project_id').value = id;
            document.getElementById('delete_project_name').innerText = name;
            new bootstrap.Modal(document.getElementById('deleteProjectModal')).show();
        }
        
        // تحديث البيانات كل 30 ثانية
        setInterval(() => {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>