<?php
require_once __DIR__ . '/Database.php';

class ClientRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // جلب بيانات لوحة التحكم
    public function getDashboardData($customerId) {
        $data = [];
        
        // 1. عدد المشاريع النشطة
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM projects WHERE customer_id = ? AND status != 'مكتمل'");
        $stmt->execute([$customerId]);
        $data['active_projects'] = $stmt->fetch()['count'];

        // 2. الفواتير المعلقة
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM invoices WHERE customer_id = ? AND status = 'مستحقة'");
        $stmt->execute([$customerId]);
        $data['pending_invoices'] = $stmt->fetch()['count'];
        
        // 3. آخر النشاطات
        $stmt = $this->db->prepare("SELECT * FROM activity_log WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$customerId]);
        $data['recent_activity'] = $stmt->fetchAll();

        return $data;
    }

    // جلب قائمة المشاريع
    public function getProjects($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    // إنشاء مشروع جديد
    public function createProject($customerId, $data) {
        $sql = "INSERT INTO projects (customer_id, name, type, description, deadline, start_date) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $customerId, 
            $data['project_name'], 
            $data['service_type'], 
            $data['project_description'], 
            $data['project_deadline'], 
            date('Y-m-d')
        ]);
    }

    // دوال أخرى للعقود والفواتير يمكن إضافتها بنفس النمط...
    public function getContracts($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM contracts WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
    
    public function getInvoices($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
}
?>