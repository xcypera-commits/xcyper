<?php
/**
 * cloud_functions.php
 * دوال خاصة بوحدة الاستضافة والتخزين السحابي
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر إلى هذا الملف');
}

/**
 * =============================================
 * Class Cloud
 * إدارة عمليات الاستضافة والتخزين
 * =============================================
 */
class Cloud {
    private $db;
    private $auth;
    
    /**
     * Constructor
     */
    public function __construct($db, $auth = null) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * =============================================
     * دوال الخوادم (Servers)
     * =============================================
     */
    
    /**
     * الحصول على جميع الخوادم
     * @param array $filters
     * @return array
     */
    public function getServers($filters = []) {
        $sql = "SELECT s.*, 
                       COUNT(DISTINCT p.id) as projects_count
                FROM cloud_servers s
                LEFT JOIN cloud_projects p ON s.id = p.server_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND s.server_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.server_name LIKE ? OR s.ip_address LIKE ? OR s.hostname LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $sql .= " GROUP BY s.id ORDER BY s.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على خادم محدد
     * @param int $server_id
     * @return array|null
     */
    public function getServer($server_id) {
        $sql = "SELECT s.*, 
                       COUNT(DISTINCT p.id) as projects_count
                FROM cloud_servers s
                LEFT JOIN cloud_projects p ON s.id = p.server_id
                WHERE s.id = ?
                GROUP BY s.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$server_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء خادم جديد
     * @param array $data
     * @return array
     */
    public function createServer($data) {
        $response = [
            'success' => false,
            'message' => '',
            'server_id' => null
        ];
        
        $server_code = $this->generateServerCode($data['server_type']);
        
        $sql = "INSERT INTO cloud_servers (
                    server_name, server_code, server_type, ip_address, hostname,
                    os, cpu_cores, ram_gb, storage_gb, location, provider,
                    monthly_cost, purchase_date, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['server_name'],
            $server_code,
            $data['server_type'],
            $data['ip_address'] ?? null,
            $data['hostname'] ?? null,
            $data['os'] ?? 'Ubuntu 22.04',
            $data['cpu_cores'] ?? 2,
            $data['ram_gb'] ?? 4,
            $data['storage_gb'] ?? 100,
            $data['location'] ?? null,
            $data['provider'] ?? null,
            $data['monthly_cost'] ?? 0,
            $data['purchase_date'] ?? date('Y-m-d'),
            $data['notes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $server_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'server', $server_id, 'إنشاء خادم جديد');
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء الخادم بنجاح';
            $response['server_id'] = $server_id;
        } else {
            $response['message'] = 'حدث خطأ في إنشاء الخادم';
        }
        
        return $response;
    }
    
    /**
     * تحديث خادم
     * @param int $server_id
     * @param array $data
     * @return array
     */
    public function updateServer($server_id, $data) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key != 'id' && $key != 'server_code') {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        $params[] = $server_id;
        
        $sql = "UPDATE cloud_servers SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // تسجيل النشاط
            $this->logActivity('update', 'server', $server_id, 'تحديث معلومات الخادم');
            
            $response['success'] = true;
            $response['message'] = 'تم تحديث الخادم بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في تحديث الخادم';
        }
        
        return $response;
    }
    
    /**
     * حذف خادم
     * @param int $server_id
     * @return array
     */
    public function deleteServer($server_id) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // التحقق من وجود مشاريع مرتبطة
        $sql = "SELECT COUNT(*) as count FROM cloud_projects WHERE server_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$server_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $response['message'] = 'لا يمكن حذف الخادم لوجود مشاريع مرتبطة به';
            return $response;
        }
        
        $sql = "DELETE FROM cloud_servers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$server_id])) {
            // تسجيل النشاط
            $this->logActivity('delete', 'server', $server_id, 'حذف خادم');
            
            $response['success'] = true;
            $response['message'] = 'تم حذف الخادم بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في حذف الخادم';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز خادم فريد
     * @param string $type
     * @return string
     */
    private function generateServerCode($type) {
        $prefixes = [
            'web' => 'SRV-WEB',
            'database' => 'SRV-DB',
            'backup' => 'SRV-BAK',
            'storage' => 'SRV-STR',
            'mail' => 'SRV-MAIL',
            'dns' => 'SRV-DNS'
        ];
        
        $prefix = $prefixes[$type] ?? 'SRV';
        $year = date('Y');
        
        $sql = "SELECT COUNT(*) as count FROM cloud_servers WHERE server_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال المشاريع المستضافة
     * =============================================
     */
    
    /**
     * الحصول على جميع المشاريع
     * @param array $filters
     * @return array
     */
    public function getProjects($filters = []) {
        $sql = "SELECT p.*, 
                       s.server_name,
                       COUNT(DISTINCT f.id) as files_count,
                       COALESCE(SUM(f.file_size), 0) as total_size
                FROM cloud_projects p
                LEFT JOIN cloud_servers s ON p.server_id = s.id
                LEFT JOIN cloud_files f ON p.id = f.project_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['server_id'])) {
            $sql .= " AND p.server_id = ?";
            $params[] = $filters['server_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.project_name LIKE ? OR p.domain LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على مشروع محدد
     * @param int $project_id
     * @return array|null
     */
    public function getProject($project_id) {
        $sql = "SELECT p.*, 
                       s.server_name, s.ip_address,
                       COUNT(DISTINCT f.id) as files_count,
                       COALESCE(SUM(f.file_size), 0) as total_size
                FROM cloud_projects p
                LEFT JOIN cloud_servers s ON p.server_id = s.id
                LEFT JOIN cloud_files f ON p.id = f.project_id
                WHERE p.id = ?
                GROUP BY p.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء مشروع جديد
     * @param array $data
     * @return array
     */
    public function createProject($data) {
        $response = [
            'success' => false,
            'message' => '',
            'project_id' => null
        ];
        
        $project_code = $this->generateProjectCode($data['project_type']);
        
        $sql = "INSERT INTO cloud_projects (
                    project_name, project_code, domain, server_id, project_type,
                    framework, language, git_repo, deploy_path, status, priority,
                    backup_enabled, monitoring_enabled, client_name, client_email,
                    notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['project_name'],
            $project_code,
            $data['domain'] ?? null,
            $data['server_id'] ?? null,
            $data['project_type'] ?? 'website',
            $data['framework'] ?? null,
            $data['language'] ?? null,
            $data['git_repo'] ?? null,
            $data['deploy_path'] ?? null,
            $data['status'] ?? 'active',
            $data['priority'] ?? 'medium',
            isset($data['backup_enabled']) ? 1 : 1,
            isset($data['monitoring_enabled']) ? 1 : 1,
            $data['client_name'] ?? null,
            $data['client_email'] ?? null,
            $data['notes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $project_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'project', $project_id, 'إنشاء مشروع جديد');
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء المشروع بنجاح';
            $response['project_id'] = $project_id;
        } else {
            $response['message'] = 'حدث خطأ في إنشاء المشروع';
        }
        
        return $response;
    }
    
    /**
     * تحديث مشروع
     * @param int $project_id
     * @param array $data
     * @return array
     */
    public function updateProject($project_id, $data) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key != 'id' && $key != 'project_code') {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        $params[] = $project_id;
        
        $sql = "UPDATE cloud_projects SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // تسجيل النشاط
            $this->logActivity('update', 'project', $project_id, 'تحديث معلومات المشروع');
            
            $response['success'] = true;
            $response['message'] = 'تم تحديث المشروع بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في تحديث المشروع';
        }
        
        return $response;
    }
    
    /**
     * حذف مشروع
     * @param int $project_id
     * @return array
     */
    public function deleteProject($project_id) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // التحقق من وجود ملفات مرتبطة
        $sql = "SELECT COUNT(*) as count FROM cloud_files WHERE project_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $response['message'] = 'لا يمكن حذف المشروع لوجود ملفات مرتبطة به';
            return $response;
        }
        
        $sql = "DELETE FROM cloud_projects WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$project_id])) {
            // تسجيل النشاط
            $this->logActivity('delete', 'project', $project_id, 'حذف مشروع');
            
            $response['success'] = true;
            $response['message'] = 'تم حذف المشروع بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في حذف المشروع';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز مشروع فريد
     * @param string $type
     * @return string
     */
    private function generateProjectCode($type) {
        $prefixes = [
            'website' => 'PRJ-WEB',
            'application' => 'PRJ-APP',
            'database' => 'PRJ-DB',
            'storage' => 'PRJ-STR',
            'email' => 'PRJ-MAIL'
        ];
        
        $prefix = $prefixes[$type] ?? 'PRJ';
        $year = date('Y');
        
        $sql = "SELECT COUNT(*) as count FROM cloud_projects WHERE project_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال الملفات
     * =============================================
     */
    
    /**
     * الحصول على ملفات المشروع
     * @param int $project_id
     * @param string $folder_path
     * @return array
     */
    public function getProjectFiles($project_id, $folder_path = '/') {
        $sql = "SELECT * FROM cloud_files 
                WHERE project_id = ? AND folder_path = ?
                ORDER BY 
                    CASE WHEN is_folder = 1 THEN 0 ELSE 1 END,
                    file_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id, $folder_path]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إضافة ملف
     * @param array $data
     * @return array
     */
    public function addFile($data) {
        $response = [
            'success' => false,
            'message' => '',
            'file_id' => null
        ];
        
        $sql = "INSERT INTO cloud_files (
                    file_name, file_path, file_type, file_size, mime_type,
                    folder_path, project_id, server_id, is_folder, is_public,
                    uploaded_by, version, permissions, owner, group_owner
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['file_name'],
            $data['file_path'],
            $data['file_type'] ?? null,
            $data['file_size'] ?? 0,
            $data['mime_type'] ?? null,
            $data['folder_path'] ?? '/',
            $data['project_id'] ?? null,
            $data['server_id'] ?? null,
            $data['is_folder'] ?? 0,
            $data['is_public'] ?? 0,
            $_SESSION['user_id'] ?? 1,
            $data['version'] ?? '1.0',
            $data['permissions'] ?? '644',
            $data['owner'] ?? 'www-data',
            $data['group_owner'] ?? 'www-data'
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $file_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'file', $file_id, 'إضافة ملف: ' . $data['file_name']);
            
            $response['success'] = true;
            $response['message'] = 'تم إضافة الملف بنجاح';
            $response['file_id'] = $file_id;
        } else {
            $response['message'] = 'حدث خطأ في إضافة الملف';
        }
        
        return $response;
    }
    
    /**
     * حذف ملف
     * @param int $file_id
     * @return array
     */
    public function deleteFile($file_id) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // الحصول على معلومات الملف
        $file = $this->getFile($file_id);
        
        if (!$file) {
            $response['message'] = 'الملف غير موجود';
            return $response;
        }
        
        $sql = "DELETE FROM cloud_files WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$file_id])) {
            // تسجيل النشاط
            $this->logActivity('delete', 'file', $file_id, 'حذف ملف: ' . $file['file_name']);
            
            $response['success'] = true;
            $response['message'] = 'تم حذف الملف بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في حذف الملف';
        }
        
        return $response;
    }
    
    /**
     * الحصول على ملف محدد
     * @param int $file_id
     * @return array|null
     */
    public function getFile($file_id) {
        $sql = "SELECT * FROM cloud_files WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$file_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * =============================================
     * دوال النشر (Deployments)
     * =============================================
     */
    
    /**
     * إنشاء نشر جديد
     * @param array $data
     * @return array
     */
    public function createDeployment($data) {
        $response = [
            'success' => false,
            'message' => '',
            'deployment_id' => null
        ];
        
        $deployment_code = $this->generateDeploymentCode();
        
        $sql = "INSERT INTO cloud_deployments (
                    deployment_code, project_id, deployment_type, environment,
                    status, version, commit_hash, branch, files_count, size_mb,
                    started_at, deployed_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        
        $params = [
            $deployment_code,
            $data['project_id'],
            $data['deployment_type'] ?? 'full',
            $data['environment'] ?? 'development',
            'in_progress',
            $data['version'] ?? '1.0.0',
            $data['commit_hash'] ?? null,
            $data['branch'] ?? 'main',
            $data['files_count'] ?? 0,
            $data['size_mb'] ?? 0,
            $_SESSION['user_id'] ?? 1
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $deployment_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'deployment', $deployment_id, 'بدء عملية نشر جديدة');
            
            $response['success'] = true;
            $response['message'] = 'تم بدء عملية النشر';
            $response['deployment_id'] = $deployment_id;
        } else {
            $response['message'] = 'حدث خطأ في بدء النشر';
        }
        
        return $response;
    }
    
    /**
     * تحديث حالة النشر
     * @param int $deployment_id
     * @param string $status
     * @param string $logs
     * @return array
     */
    public function updateDeploymentStatus($deployment_id, $status, $logs = null) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        $sql = "UPDATE cloud_deployments SET 
                status = ?, 
                completed_at = NOW(),
                logs = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$status, $logs, $deployment_id])) {
            $response['success'] = true;
            $response['message'] = 'تم تحديث حالة النشر';
        } else {
            $response['message'] = 'حدث خطأ في تحديث الحالة';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز نشر فريد
     * @return string
     */
    private function generateDeploymentCode() {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM cloud_deployments WHERE deployment_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["DEP-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "DEP-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال النسخ الاحتياطي
     * =============================================
     */
    
    /**
     * إنشاء نسخة احتياطية
     * @param array $data
     * @return array
     */
    public function createBackup($data) {
        $response = [
            'success' => false,
            'message' => '',
            'backup_id' => null
        ];
        
        $backup_code = $this->generateBackupCode();
        $backup_name = $data['backup_name'] ?? 'نسخة احتياطية ' . date('Y-m-d H:i');
        
        $sql = "INSERT INTO cloud_backups (
                    backup_code, backup_name, project_id, server_id, backup_type,
                    destination, status, started_at, retention_days, is_automated,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
        
        $params = [
            $backup_code,
            $backup_name,
            $data['project_id'] ?? null,
            $data['server_id'] ?? null,
            $data['backup_type'] ?? 'full',
            $data['destination'] ?? 'local',
            'in_progress',
            $data['retention_days'] ?? 30,
            $data['is_automated'] ?? 0,
            $_SESSION['user_id'] ?? 1
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $backup_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'backup', $backup_id, 'بدء نسخ احتياطي');
            
            $response['success'] = true;
            $response['message'] = 'تم بدء النسخ الاحتياطي';
            $response['backup_id'] = $backup_id;
        } else {
            $response['message'] = 'حدث خطأ في بدء النسخ الاحتياطي';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز نسخ احتياطي فريد
     * @return string
     */
    private function generateBackupCode() {
        $year = date('Y');
        
        $sql = "SELECT COUNT(*) as count FROM cloud_backups WHERE backup_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["BAK-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "BAK-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال المراقبة والإحصائيات
     * =============================================
     */
    
    /**
     * الحصول على إحصائيات سريعة
     * @return array
     */
    public function getDashboardStats() {
        $stats = [];
        
        // إحصائيات الخوادم
        $sql = "SELECT 
                    COUNT(*) as total_servers,
                    SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_servers,
                    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_servers,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_servers,
                    SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_servers
                FROM cloud_servers";
        $stmt = $this->db->query($sql);
        $stats['servers'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // إحصائيات المشاريع
        $sql = "SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_projects,
                    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_projects
                FROM cloud_projects";
        $stmt = $this->db->query($sql);
        $stats['projects'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // إحصائيات التخزين
        $sql = "SELECT 
                    COALESCE(SUM(storage_gb), 0) as total_storage,
                    COALESCE(SUM(storage_used_gb), 0) as used_storage
                FROM cloud_servers";
        $stmt = $this->db->query($sql);
        $storage = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['storage'] = $storage;
        $stats['storage_percent'] = $storage['total_storage'] > 0 
            ? round(($storage['used_storage'] / $storage['total_storage']) * 100, 1) 
            : 0;
        
        // إحصائيات النسخ الاحتياطي
        $sql = "SELECT 
                    COUNT(*) as total_backups,
                    COALESCE(SUM(size_mb), 0) as total_backup_size
                FROM cloud_backups WHERE status = 'completed'";
        $stmt = $this->db->query($sql);
        $stats['backups'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    /**
     * الحصول على مراقبة التخزين لخادم
     * @param int $server_id
     * @return array
     */
    public function getStorageMonitoring($server_id) {
        $sql = "SELECT * FROM cloud_storage_monitoring 
                WHERE server_id = ? 
                ORDER BY created_at DESC 
                LIMIT 24";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$server_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على تنبيهات التخزين
     * @param int $limit
     * @return array
     */
    public function getStorageAlerts($limit = 10) {
        $sql = "SELECT a.*, s.server_name 
                FROM cloud_storage_alerts a
                LEFT JOIN cloud_servers s ON a.server_id = s.id
                WHERE a.is_resolved = 0
                ORDER BY 
                    CASE a.severity
                        WHEN 'high' THEN 1
                        WHEN 'medium' THEN 2
                        WHEN 'low' THEN 3
                        ELSE 4
                    END,
                    a.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * =============================================
     * دوال التحديثات الأمنية
     * =============================================
     */
    
    /**
     * الحصول على التحديثات الأمنية
     * @param array $filters
     * @return array
     */
    public function getSecurityUpdates($filters = []) {
        $sql = "SELECT u.*, s.server_name 
                FROM cloud_security_updates u
                LEFT JOIN cloud_servers s ON u.server_id = s.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['severity'])) {
            $sql .= " AND u.severity = ?";
            $params[] = $filters['severity'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['server_id'])) {
            $sql .= " AND u.server_id = ?";
            $params[] = $filters['server_id'];
        }
        
        $sql .= " ORDER BY 
                    CASE u.severity
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END,
                    u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * =============================================
     * دوال سجل النشاطات
     * =============================================
     */
    
    /**
     * الحصول على آخر النشاطات
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 20) {
        $sql = "SELECT a.*, u.full_name as user_name
                FROM cloud_activity_log a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تسجيل النشاط
     * @param string $activity_type
     * @param string $target_type
     * @param int $target_id
     * @param string $description
     */
    private function logActivity($activity_type, $target_type, $target_id, $description) {
        $sql = "INSERT INTO cloud_activity_log (
                    user_id, activity_type, target_type, target_id, description, 
                    ip_address, user_agent, metadata, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $activity_type,
            $target_type,
            $target_id,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode($_POST)
        ]);
    }
    
    /**
     * =============================================
     * دوال مساعدة للواجهة
     * =============================================
     */
    
    /**
     * عرض بطاقة خادم
     * @param array $server
     * @return string
     */
    public function renderServerCard($server) {
        $storage_percent = $server['storage_gb'] > 0 
            ? round(($server['storage_used_gb'] / $server['storage_gb']) * 100, 1) 
            : 0;
        
        $status_colors = [
            'online' => 'green',
            'offline' => 'red',
            'maintenance' => 'yellow',
            'warning' => 'orange'
        ];
        
        $color = $status_colors[$server['status']] ?? 'gray';
        
        return "
        <div class='card-hover cyber-border bg-slate-800 rounded-xl p-6 server-card'>
            <div class='flex items-center justify-between mb-4'>
                <span class='px-3 py-1 bg-{$color}-600 bg-opacity-20 text-{$color}-400 rounded-full text-xs'>
                    " . $this->getServerStatusLabel($server['status']) . "
                </span>
                <span class='text-sm text-gray-400'>{$server['server_code']}</span>
            </div>
            
            <h3 class='text-xl font-bold mb-2'>{$server['server_name']}</h3>
            <p class='text-sm text-gray-400 mb-4'>{$server['ip_address']}</p>
            
            <div class='grid grid-cols-3 gap-2 mb-4 text-center'>
                <div class='bg-slate-900 rounded-lg p-2'>
                    <p class='text-xs text-gray-400'>CPU</p>
                    <p class='text-lg font-bold text-blue-400'>{$server['cpu_cores']} نوى</p>
                </div>
                <div class='bg-slate-900 rounded-lg p-2'>
                    <p class='text-xs text-gray-400'>RAM</p>
                    <p class='text-lg font-bold text-green-400'>{$server['ram_gb']} GB</p>
                </div>
                <div class='bg-slate-900 rounded-lg p-2'>
                    <p class='text-xs text-gray-400'>مشاريع</p>
                    <p class='text-lg font-bold text-purple-400'>{$server['projects_count']}</p>
                </div>
            </div>
            
            <div class='mb-4'>
                <div class='flex items-center justify-between text-sm mb-1'>
                    <span class='text-gray-400'>التخزين</span>
                    <span class='text-{$color}-400'>{$storage_percent}%</span>
                </div>
                <div class='progress-bar'>
                    <div class='progress-fill bg-{$color}-400' style='width: {$storage_percent}%'></div>
                </div>
                <p class='text-xs text-gray-400 mt-1'>{$server['storage_used_gb']}GB / {$server['storage_gb']}GB مستخدم</p>
            </div>
            
            <div class='flex items-center justify-between'>
                <span class='text-sm text-gray-400'>نوع: " . $this->getServerTypeLabel($server['server_type']) . "</span>
                <a href='/cloud-unit/pages/servers.php?view={$server['id']}' class='text-blue-400 hover:text-blue-300 text-sm flex items-center'>
                    عرض التفاصيل
                    <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M14 5l7 7m0 0l-7 7m7-7H3'/>
                    </svg>
                </a>
            </div>
        </div>";
    }
    
    /**
     * عرض بطاقة مشروع
     * @param array $project
     * @return string
     */public function renderProjectCard($project) {
    $status_colors = [
        'active' => 'green',
        'inactive' => 'gray',
        'suspended' => 'red',
        'maintenance' => 'yellow',
        'deploying' => 'blue'
    ];
    
    $priority_colors = [
        'critical' => 'red',
        'high' => 'orange',
        'medium' => 'blue',
        'low' => 'green'
    ];
    
    $color = isset($status_colors[$project['status']]) ? $status_colors[$project['status']] : 'gray';
    $priority_color = isset($priority_colors[$project['priority']]) ? $priority_colors[$project['priority']] : 'gray';
    
    return "
    <div class='card-hover cyber-border bg-slate-800 rounded-xl p-6'>
        <div class='flex items-center justify-between mb-4'>
            <span class='px-3 py-1 bg-{$priority_color}-600 bg-opacity-20 text-{$priority_color}-400 rounded-full text-xs'>
                " . $this->getPriorityLabel($project['priority']) . "
            </span>
            <span class='text-sm text-gray-400'>{$project['project_code']}</span>
        </div>
        
        <h3 class='text-xl font-bold mb-2'>{$project['project_name']}</h3>
        <p class='text-sm text-gray-400 mb-4'>" . (isset($project['domain']) ? $project['domain'] : 'لا يوجد نطاق') . "</p>
        
        <div class='flex items-center justify-between mb-4'>
            <div class='flex items-center'>
                <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'/>
                </svg>
                <span class='text-sm'>" . (isset($project['server_name']) ? $project['server_name'] : 'غير مرتبط') . "</span>
            </div>
            <div class='flex items-center'>
                <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'/>
                </svg>
                <span class='text-sm'>{$project['files_count']} ملف</span>
            </div>
        </div>
        
        <div class='flex items-center justify-between text-sm text-gray-400 mb-4'>
            <span>العميل: " . (isset($project['client_name']) ? $project['client_name'] : 'غير محدد') . "</span>
            <span>الحجم: " . formatFileSize($project['total_size']) . "</span>
        </div>
        
        <div class='flex items-center justify-between'>
            <span class='px-3 py-1 bg-{$color}-600 bg-opacity-20 text-{$color}-400 rounded-full text-xs'>
                " . $this->getProjectStatusLabel($project['status']) . "
            </span>
            <a href='/cloud-unit/pages/projects.php?view={$project['id']}' class='text-blue-400 hover:text-blue-300 text-sm flex items-center'>
                عرض التفاصيل
                <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M14 5l7 7m0 0l-7 7m7-7H3'/>
                </svg>
            </a>
        </div>
    </div>";
}
    /**
     * الحصول على تسمية حالة الخادم
     * @param string $status
     * @return string
     */
    private function getServerStatusLabel($status) {
        $labels = [
            'online' => 'نشط',
            'offline' => 'متوقف',
            'maintenance' => 'صيانة',
            'warning' => 'تحذير',
            'provisioning' => 'تجهيز'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * الحصول على تسمية نوع الخادم
     * @param string $type
     * @return string
     */
    private function getServerTypeLabel($type) {
        $labels = [
            'web' => 'ويب',
            'database' => 'قاعدة بيانات',
            'backup' => 'نسخ احتياطي',
            'storage' => 'تخزين',
            'mail' => 'بريد',
            'dns' => 'DNS'
        ];
        
        return $labels[$type] ?? $type;
    }
    
    /**
     * الحصول على تسمية حالة المشروع
     * @param string $status
     * @return string
     */
    private function getProjectStatusLabel($status) {
        $labels = [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'موقوف',
            'maintenance' => 'صيانة',
            'deploying' => 'نشر'
        ];
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * الحصول على تسمية الأولوية
     * @param string $priority
     * @return string
     */
    private function getPriorityLabel($priority) {
        $labels = [
            'critical' => 'حرج',
            'high' => 'عالي',
            'medium' => 'متوسط',
            'low' => 'منخفض'
        ];
        
        return $labels[$priority] ?? $priority;
    }
}

/**
 * =============================================
 * دوال مساعدة للوحدة
 * =============================================
 */

/**
 * إنشاء كائن Cloud
 * @param PDO $db
 * @param Auth|null $auth
 * @return Cloud
 */
function cloud($db, $auth = null) {
    static $cloud = null;
    
    if ($cloud === null) {
        $cloud = new Cloud($db, $auth);
    }
    
    return $cloud;
}

/**
 * حساب حجم المجلد
 * @param string $path
 * @return int
 */
function getFolderSize($path) {
    $total = 0;
    $path = realpath($path);
    
    if ($path !== false && $path != '' && file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $total += $object->getSize();
        }
    }
    
    return $total;
}

/**
 * الحصول على أيقونة الملف حسب النوع
 * @param string $extension
 * @return string
 */
function getFileIcon($extension) {
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'ppt' => '📽️',
        'pptx' => '📽️',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️',
        'mp4' => '🎥',
        'mp3' => '🎵',
        'zip' => '📦',
        'rar' => '📦',
        'tar' => '📦',
        'gz' => '📦',
        'txt' => '📃',
        'html' => '🌐',
        'css' => '🎨',
        'js' => '⚙️',
        'php' => '🐘',
        'sql' => '🗄️',
        'json' => '📋',
        'xml' => '📋'
    ];
    
    return $icons[strtolower($extension)] ?? '📄';
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */