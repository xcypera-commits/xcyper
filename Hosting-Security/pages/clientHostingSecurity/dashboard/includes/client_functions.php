<?php
/**
 * client_functions.php
 * دوال خاصة بوحدة العميل
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر إلى هذا الملف');
}

/**
 * =============================================
 * Class Client
 * إدارة عمليات العميل
 * =============================================
 */
class Client {
    private $db;
    private $auth;
    private $client_id;
    
    /**
     * Constructor
     */
    public function __construct($db, $auth = null) {
        $this->db = $db;
        $this->auth = $auth;
        
        if ($auth && $auth->check()) {
            $client = $auth->client();
            $this->client_id = $client['id'] ?? null;
        }
    }
    
    /**
     * =============================================
     * دوال المشاريع
     * =============================================
     */
    
    /**
     * الحصول على مشاريع العميل
     * @param array $filters
     * @return array
     */
    public function getProjects($filters = []) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM client_files WHERE project_id = p.id) as files_count,
                       (SELECT COUNT(*) FROM client_invoices WHERE project_id = p.id AND status = 'pending') as pending_invoices
                FROM client_projects p
                WHERE p.client_id = ?";
        
        $params = [$this->client_id];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND p.project_type = ?";
            $params[] = $filters['type'];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
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
        if (!$this->client_id) return null;
        
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM client_files WHERE project_id = p.id) as files_count,
                       (SELECT SUM(file_size) FROM client_files WHERE project_id = p.id) as total_size,
                       (SELECT COUNT(*) FROM client_invoices WHERE project_id = p.id) as invoices_count,
                       (SELECT SUM(amount) FROM client_invoices WHERE project_id = p.id AND status = 'paid') as paid_amount
                FROM client_projects p
                WHERE p.id = ? AND p.client_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id, $this->client_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء مشروع جديد (طلب خدمة)
     * @param array $data
     * @return array
     */
    public function createProject($data) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        $project_code = $this->generateProjectCode($data['project_type']);
        
        $sql = "INSERT INTO client_projects (
                    client_id, project_code, project_name, project_type,
                    description, status, stage, priority, start_date,
                    deadline, budget, manager_name, manager_phone
                ) VALUES (?, ?, ?, ?, ?, 'pending', 1, ?, CURDATE(), ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $this->client_id,
            $project_code,
            $data['project_name'],
            $data['project_type'],
            $data['description'] ?? null,
            $data['priority'] ?? 'medium',
            $data['deadline'] ?? null,
            $data['budget'] ?? 0,
            $data['manager_name'] ?? null,
            $data['manager_phone'] ?? null
        ])) {
            $project_id = $this->db->lastInsertId();
            
            // تسجيل النشاط
            $this->logActivity('create', 'project', $project_id, 'إنشاء مشروع جديد');
            
            return [
                'success' => true,
                'message' => 'تم إنشاء المشروع بنجاح',
                'project_id' => $project_id
            ];
        }
        
        return ['success' => false, 'message' => 'حدث خطأ في إنشاء المشروع'];
    }
    
    /**
     * توليد رمز مشروع فريد
     * @param string $type
     * @return string
     */
    private function generateProjectCode($type) {
        $prefixes = [
            'hosting' => 'PRJ-HOST',
            'storage' => 'PRJ-STOR',
            'security' => 'PRJ-SEC',
            'pentest' => 'PRJ-PENT',
            'consultation' => 'PRJ-CONS',
            'development' => 'PRJ-DEV'
        ];
        
        $prefix = $prefixes[$type] ?? 'PRJ';
        $year = date('Y');
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM client_projects WHERE project_code LIKE ?");
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch();
        
        $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال الملفات
     * =============================================
     */
    
    /**
     * الحصول على ملفات العميل
     * @param int $project_id
     * @return array
     */
    public function getFiles($project_id = null) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT f.*, p.project_name
                FROM client_files f
                LEFT JOIN client_projects p ON f.project_id = p.id
                WHERE f.client_id = ?";
        
        $params = [$this->client_id];
        
        if ($project_id) {
            $sql .= " AND f.project_id = ?";
            $params[] = $project_id;
        }
        
        $sql .= " ORDER BY f.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إضافة ملف
     * @param array $data
     * @return array
     */
    public function addFile($data) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        $sql = "INSERT INTO client_files (
                    client_id, project_id, file_name, file_path,
                    file_type, file_size, mime_type, folder_path,
                    description, version
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $this->client_id,
            $data['project_id'] ?? null,
            $data['file_name'],
            $data['file_path'],
            $data['file_type'],
            $data['file_size'],
            $data['mime_type'],
            $data['folder_path'] ?? '/',
            $data['description'] ?? null,
            $data['version'] ?? '1.0'
        ])) {
            $file_id = $this->db->lastInsertId();
            
            $this->logActivity('upload', 'file', $file_id, 'رفع ملف: ' . $data['file_name']);
            
            return [
                'success' => true,
                'message' => 'تم رفع الملف بنجاح',
                'file_id' => $file_id
            ];
        }
        
        return ['success' => false, 'message' => 'حدث خطأ في رفع الملف'];
    }
    
    /**
     * حذف ملف
     * @param int $file_id
     * @return array
     */
    public function deleteFile($file_id) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        // التحقق من ملكية الملف
        $stmt = $this->db->prepare("SELECT file_path, file_name FROM client_files WHERE id = ? AND client_id = ?");
        $stmt->execute([$file_id, $this->client_id]);
        $file = $stmt->fetch();
        
        if (!$file) {
            return ['success' => false, 'message' => 'الملف غير موجود أو لا تملك صلاحية'];
        }
        
        // حذف الملف الفعلي
        deleteFile($file['file_path']);
        
        // حذف من قاعدة البيانات
        $stmt = $this->db->prepare("DELETE FROM client_files WHERE id = ?");
        $stmt->execute([$file_id]);
        
        $this->logActivity('delete', 'file', $file_id, 'حذف ملف: ' . $file['file_name']);
        
        return ['success' => true, 'message' => 'تم حذف الملف بنجاح'];
    }
    
    /**
     * =============================================
     * دوال الفواتير
     * =============================================
     */
    
    /**
     * الحصول على فواتير العميل
     * @param array $filters
     * @return array
     */
    public function getInvoices($filters = []) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT i.*, p.project_name
                FROM client_invoices i
                LEFT JOIN client_projects p ON i.project_id = p.id
                WHERE i.client_id = ?";
        
        $params = [$this->client_id];
        
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['project_id'])) {
            $sql .= " AND i.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على ملخص الفواتير
     * @return array
     */
    public function getInvoiceSummary() {
        if (!$this->client_id) return [];
        
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as due_now,
                    SUM(CASE WHEN status = 'paid' AND MONTH(paid_date) = MONTH(NOW()) THEN total_amount ELSE 0 END) as paid_this_month,
                    SUM(CASE WHEN status IN ('pending', 'overdue') THEN total_amount ELSE 0 END) as total_due,
                    (SELECT balance FROM client_clients WHERE id = ?) as available_balance
                FROM client_invoices
                WHERE client_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->client_id, $this->client_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * =============================================
     * دوال العقود
     * =============================================
     */
    
    /**
     * الحصول على عقود العميل
     * @return array
     */
    public function getContracts() {
        if (!$this->client_id) return [];
        
        $sql = "SELECT c.*, p.project_name
                FROM client_contracts c
                LEFT JOIN client_projects p ON c.project_id = p.id
                WHERE c.client_id = ?
                ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->client_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على ملخص العقود
     * @return array
     */
    public function getContractSummary() {
        if (!$this->client_id) return [];
        
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'signed' AND signed_by_client = 0 THEN 1 ELSE 0 END) as pending_signature,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                FROM client_contracts
                WHERE client_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->client_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * توقيع عقد
     * @param int $contract_id
     * @return array
     */
    public function signContract($contract_id) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        $sql = "UPDATE client_contracts 
                SET signed_by_client = 1, signed_at = NOW(), status = 'signed'
                WHERE id = ? AND client_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$contract_id, $this->client_id])) {
            $this->logActivity('sign', 'contract', $contract_id, 'توقيع عقد');
            
            return ['success' => true, 'message' => 'تم توقيع العقد بنجاح'];
        }
        
        return ['success' => false, 'message' => 'حدث خطأ في توقيع العقد'];
    }
    
    /**
     * =============================================
     * دوال تذاكر الدعم
     * =============================================
     */
    
    /**
     * الحصول على تذاكر الدعم
     * @param array $filters
     * @return array
     */
    public function getTickets($filters = []) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM client_ticket_replies WHERE ticket_id = t.id) as replies_count
                FROM client_support_tickets t
                WHERE t.client_id = ?";
        
        $params = [$this->client_id];
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = ?";
            $params[] = $filters['priority'];
        }
        
        $sql .= " ORDER BY 
                    CASE t.priority
                        WHEN 'urgent' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END,
                    t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء تذكرة دعم جديدة
     * @param array $data
     * @return array
     */
    public function createTicket($data) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        $ticket_code = $this->generateTicketCode();
        
        $sql = "INSERT INTO client_support_tickets (
                    ticket_code, client_id, project_id, subject, message,
                    priority, category, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'open')";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $ticket_code,
            $this->client_id,
            $data['project_id'] ?? null,
            $data['subject'],
            $data['message'],
            $data['priority'] ?? 'medium',
            $data['category'] ?? 'general'
        ])) {
            $ticket_id = $this->db->lastInsertId();
            
            $this->logActivity('create', 'ticket', $ticket_id, 'إنشاء تذكرة دعم: ' . $data['subject']);
            
            return [
                'success' => true,
                'message' => 'تم إنشاء التذكرة بنجاح',
                'ticket_id' => $ticket_id,
                'ticket_code' => $ticket_code
            ];
        }
        
        return ['success' => false, 'message' => 'حدث خطأ في إنشاء التذكرة'];
    }
    
    /**
     * إضافة رد على تذكرة
     * @param int $ticket_id
     * @param string $message
     * @return array
     */
    public function addTicketReply($ticket_id, $message) {
        if (!$this->client_id) {
            return ['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً'];
        }
        
        // التحقق من ملكية التذكرة
        $stmt = $this->db->prepare("SELECT id FROM client_support_tickets WHERE id = ? AND client_id = ?");
        $stmt->execute([$ticket_id, $this->client_id]);
        
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'التذكرة غير موجودة أو لا تملك صلاحية'];
        }
        
        $sql = "INSERT INTO client_ticket_replies (ticket_id, message, is_staff) VALUES (?, ?, 0)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$ticket_id, $message])) {
            // تحديث حالة التذكرة
            $this->db->prepare("UPDATE client_support_tickets SET status = 'waiting' WHERE id = ?")
                     ->execute([$ticket_id]);
            
            $this->logActivity('reply', 'ticket', $ticket_id, 'إضافة رد على تذكرة');
            
            return ['success' => true, 'message' => 'تم إضافة الرد بنجاح'];
        }
        
        return ['success' => false, 'message' => 'حدث خطأ في إضافة الرد'];
    }
    
    /**
     * توليد رمز تذكرة فريد
     * @return string
     */
    private function generateTicketCode() {
        $year = date('Y');
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM client_support_tickets WHERE ticket_code LIKE ?");
        $stmt->execute(["TCK-{$year}-%"]);
        $result = $stmt->fetch();
        
        $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
        
        return "TCK-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال التقارير
     * =============================================
     */
    
    /**
     * الحصول على تقارير العميل
     * @return array
     */
    public function getReports() {
        if (!$this->client_id) return [];
        
        $sql = "SELECT r.*, p.project_name
                FROM client_reports r
                LEFT JOIN client_projects p ON r.project_id = p.id
                WHERE r.client_id = ?
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->client_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * =============================================
     * دوال الإشعارات
     * =============================================
     */
    
    /**
     * الحصول على إشعارات العميل
     * @param bool $unread_only
     * @return array
     */
    public function getNotifications($unread_only = false) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT * FROM client_notifications WHERE client_id = ?";
        $params = [$this->client_id];
        
        if ($unread_only) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * تحديد إشعار كمقروء
     * @param int $notification_id
     * @return bool
     */
    public function markNotificationAsRead($notification_id) {
        if (!$this->client_id) return false;
        
        $sql = "UPDATE client_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND client_id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$notification_id, $this->client_id]);
    }
    
    /**
     * =============================================
     * دوال لوحة التحكم والإحصائيات
     * =============================================
     */
    
    /**
     * الحصول على إحصائيات سريعة
     * @return array
     */
    public function getDashboardStats() {
        if (!$this->client_id) return [];
        
        $stats = [];
        
        // المشاريع النشطة
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM client_projects WHERE client_id = ? AND status IN ('pending', 'under_study', 'in_progress')");
        $stmt->execute([$this->client_id]);
        $stats['active_projects'] = $stmt->fetchColumn() ?: 0;
        
        // الفواتير المعلقة
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM client_invoices WHERE client_id = ? AND status = 'pending'");
        $stmt->execute([$this->client_id]);
        $stats['pending_invoices'] = $stmt->fetchColumn() ?: 0;
        
        // التقارير الجاهزة
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM client_reports WHERE client_id = ? AND status = 'ready'");
        $stmt->execute([$this->client_id]);
        $stats['ready_reports'] = $stmt->fetchColumn() ?: 0;
        
        // مساحة التخزين
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(file_size), 0) FROM client_files WHERE client_id = ?");
        $stmt->execute([$this->client_id]);
        $stats['used_storage'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
    }
    
    /**
     * الحصول على آخر النشاطات
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 10) {
        if (!$this->client_id) return [];
        
        $sql = "SELECT * FROM client_activity_log 
                WHERE client_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->client_id, $limit]);
        
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
        if (!$this->client_id) return;
        
        $sql = "INSERT INTO client_activity_log (
                    client_id, activity_type, target_type, target_id, description, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->client_id,
            $activity_type,
            $target_type,
            $target_id,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * =============================================
     * دوال مساعدة للواجهة
     * =============================================
     */
    
    /**
     * عرض بطاقة مشروع
     * @param array $project
     * @return string
     */
    public function renderProjectCard($project) {
        $stage_names = [
            1 => 'الطلب',
            2 => 'الدراسة',
            3 => 'العقد',
            4 => 'التنفيذ',
            5 => 'الفحص',
            6 => 'التسليم',
            7 => 'الدعم'
        ];
        
        $stage = $project['stage'] ?? 1;
        $stage_name = $stage_names[$stage] ?? 'غير محدد';
        
        $status_colors = [
            'pending' => 'yellow',
            'under_study' => 'blue',
            'contract_pending' => 'purple',
            'in_progress' => 'green',
            'testing' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red'
        ];
        
        $color = $status_colors[$project['status']] ?? 'gray';
        
        return "
        <div class='card-hover cyber-border bg-slate-800 rounded-xl p-6'>
            <div class='flex items-center justify-between mb-4'>
                <span class='px-3 py-1 bg-{$color}-600 bg-opacity-20 text-{$color}-400 rounded-full text-xs'>
                    " . $this->getProjectStatusText($project['status']) . "
                </span>
                <span class='text-sm text-gray-400'>{$project['project_code']}</span>
            </div>
            
            <h3 class='text-xl font-bold mb-2'>{$project['project_name']}</h3>
            <p class='text-sm text-gray-400 mb-4'>" . $this->getProjectTypeText($project['project_type']) . "</p>
            
            <div class='flex items-center justify-between mb-4'>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/>
                    </svg>
                    <span class='text-sm'>بداية: " . formatDate($project['start_date']) . "</span>
                </div>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'/>
                    </svg>
                    <span class='text-sm'>تسليم: " . formatDate($project['deadline']) . "</span>
                </div>
            </div>
            
            <div class='mb-4'>
                <div class='flex items-center justify-between text-sm mb-1'>
                    <span class='text-gray-400'>مرحلة {$stage_name}</span>
                    <span class='text-{$color}-400'>{$project['progress']}%</span>
                </div>
                <div class='progress-bar'>
                    <div class='progress-fill bg-{$color}-400' style='width: {$project['progress']}%'></div>
                </div>
            </div>
            
            <div class='flex items-center justify-between'>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/>
                    </svg>
                    <span class='text-sm text-gray-400'>{$project['files_count']} ملف</span>
                </div>
                <a href='?page=projects&view={$project['id']}' class='text-blue-400 hover:text-blue-300 text-sm flex items-center'>
                    عرض التفاصيل
                    <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M14 5l7 7m0 0l-7 7m7-7H3'/>
                    </svg>
                </a>
            </div>
        </div>";
    }
    
    /**
     * الحصول على تسمية حالة المشروع
     * @param string $status
     * @return string
     */
    private function getProjectStatusText($status) {
        $texts = [
            'pending' => 'قيد الانتظار',
            'under_study' => 'قيد الدراسة',
            'contract_pending' => 'بانتظار العقد',
            'in_progress' => 'قيد التنفيذ',
            'testing' => 'قيد الاختبار',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي'
        ];
        
        return $texts[$status] ?? $status;
    }
    
    /**
     * الحصول على تسمية نوع المشروع
     * @param string $type
     * @return string
     */
    private function getProjectTypeText($type) {
        $texts = [
            'hosting' => 'استضافة',
            'storage' => 'تخزين سحابي',
            'security' => 'أمن المعلومات',
            'pentest' => 'اختبار اختراق',
            'consultation' => 'استشارة',
            'development' => 'تطوير'
        ];
        
        return $texts[$type] ?? $type;
    }
}

/**
 * =============================================
 * دوال مساعدة للوحدة
 * =============================================
 */

/**
 * إنشاء كائن Client
 * @param PDO $db
 * @param ClientAuth|null $auth
 * @return Client
 */
function client($db, $auth = null) {
    static $client = null;
    
    if ($client === null) {
        $client = new Client($db, $auth);
    }
    
    return $client;
}

/**
 * الحصول على اسم مرحلة المشروع
 * @param int $stage
 * @return string
 */
function getStageName($stage) {
    $stages = [
        1 => 'الطلب',
        2 => 'الدراسة',
        3 => 'العقد',
        4 => 'التنفيذ',
        5 => 'الفحص',
        6 => 'التسليم',
        7 => 'الدعم'
    ];
    
    return $stages[$stage] ?? 'غير محدد';
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */