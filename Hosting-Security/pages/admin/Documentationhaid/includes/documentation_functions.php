<?php
/**
 * documentation_functions.php
 * دوال خاصة بوحدة التوثيق
 */

// منع الوصول المباشر للملف
if (!defined('BASE_PATH')) {
    exit('لا يمكن الوصول المباشر إلى هذا الملف');
}

/**
 * =============================================
 * Class Documentation
 * إدارة عمليات التوثيق
 * =============================================
 */
class Documentation {
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
     * دوال المشاريع
     * =============================================
     */
    
    /**
     * الحصول على جميع المشاريع
     * @param array $filters
     * @return array
     */
    public function getProjects($filters = []) {
        $sql = "SELECT p.*, 
                       COUNT(DISTINCT d.id) as documents_count,
                       SUM(d.pages) as total_pages,
                       u.full_name as manager_name
                FROM documentation_projects p
                LEFT JOIN documents d ON p.id = d.project_id
                LEFT JOIN users u ON p.project_manager_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // تطبيق الفلاتر
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND p.project_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.project_name LIKE ? OR p.client_name LIKE ?)";
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
                       COUNT(DISTINCT d.id) as documents_count,
                       SUM(d.pages) as total_pages,
                       u.full_name as manager_name
                FROM documentation_projects p
                LEFT JOIN documents d ON p.id = d.project_id
                LEFT JOIN users u ON p.project_manager_id = u.id
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
        
        $sql = "INSERT INTO documentation_projects (
                    project_code, project_name, client_name, client_company,
                    project_type, priority, status, assigned_team,
                    project_manager, technical_lead, start_date, deadline,
                    description, security_level, repository_path, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $project_code = $this->generateProjectCode($data['project_type']);
        
        $params = [
            $project_code,
            $data['project_name'],
            $data['client_name'] ?? null,
            $data['client_company'] ?? null,
            $data['project_type'],
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'new',
            $data['assigned_team'] ?? null,
            $data['project_manager'] ?? null,
            $data['technical_lead'] ?? null,
            $data['start_date'] ?? date('Y-m-d'),
            $data['deadline'] ?? null,
            $data['description'] ?? null,
            $data['security_level'] ?? 'normal',
            $data['repository_path'] ?? null,
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
        
        $sql = "UPDATE documentation_projects SET " . implode(', ', $fields) . " WHERE id = ?";
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
        
        // التحقق من وجود مستندات مرتبطة
        $sql = "SELECT COUNT(*) as count FROM documents WHERE project_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $response['message'] = 'لا يمكن حذف المشروع لوجود مستندات مرتبطة به';
            return $response;
        }
        
        $sql = "DELETE FROM documentation_projects WHERE id = ?";
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
            'hosting' => 'HOST',
            'storage' => 'STOR',
            'security' => 'SEC',
            'ecommerce' => 'ECOMM',
            'cloud' => 'CLOUD',
            'network' => 'NET'
        ];
        
        $prefix = $prefixes[$type] ?? 'PROJ';
        $year = date('Y');
        $month = date('m');
        
        // الحصول على آخر رقم
        $sql = "SELECT COUNT(*) as count FROM documentation_projects WHERE project_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * =============================================
     * دوال المستندات
     * =============================================
     */
    
    /**
     * الحصول على جميع المستندات
     * @param array $filters
     * @return array
     */
    public function getDocuments($filters = []) {
        $sql = "SELECT d.*, 
                       p.project_name,
                       u.full_name as creator_name,
                       r.full_name as reviewer_name
                FROM documents d
                LEFT JOIN documentation_projects p ON d.project_id = p.id
                LEFT JOIN users u ON d.created_by = u.id
                LEFT JOIN users r ON d.reviewed_by = r.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['project_id'])) {
            $sql .= " AND d.project_id = ?";
            $params[] = $filters['project_id'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND d.document_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['created_by'])) {
            $sql .= " AND d.created_by = ?";
            $params[] = $filters['created_by'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (d.title LIKE ? OR d.document_code LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * الحصول على مستند محدد
     * @param int $document_id
     * @return array|null
     */
    public function getDocument($document_id) {
        $sql = "SELECT d.*, 
                       p.project_name,
                       u.full_name as creator_name,
                       r.full_name as reviewer_name,
                       a.full_name as approver_name,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM documents d
                LEFT JOIN documentation_projects p ON d.project_id = p.id
                LEFT JOIN users u ON d.created_by = u.id
                LEFT JOIN users r ON d.reviewed_by = r.id
                LEFT JOIN users a ON d.approved_by = a.id
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE d.id = ?
                GROUP BY d.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$document_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * إنشاء مستند جديد
     * @param array $data
     * @return array
     */
    public function createDocument($data) {
        $response = [
            'success' => false,
            'message' => '',
            'document_id' => null
        ];
        
        $document_code = $this->generateDocumentCode($data['document_type']);
        
        $sql = "INSERT INTO documents (
                    document_code, title, project_id, document_type, format,
                    version, status, content, executive_summary, introduction,
                    file_path, file_size, pages, word_count, created_by,
                    created_date, tags, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $document_code,
            $data['title'],
            $data['project_id'] ?? null,
            $data['document_type'],
            $data['format'] ?? 'pdf',
            $data['version'] ?? '1.0.0',
            $data['status'] ?? 'draft',
            $data['content'] ?? null,
            $data['executive_summary'] ?? null,
            $data['introduction'] ?? null,
            $data['file_path'] ?? null,
            $data['file_size'] ?? 0,
            $data['pages'] ?? 0,
            $data['word_count'] ?? 0,
            $_SESSION['user_id'] ?? 1,
            $data['created_date'] ?? date('Y-m-d'),
            $data['tags'] ?? null,
            $data['description'] ?? null
        ];
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $document_id = $this->db->lastInsertId();
            
            // إضافة الإصدار الأول
            $this->createDocumentVersion($document_id, [
                'version_number' => $data['version'] ?? '1.0.0',
                'changes' => 'الإصدار الأولي'
            ]);
            
            // إضافة الوسوم
            if (!empty($data['tag_ids'])) {
                $this->addDocumentTags($document_id, $data['tag_ids']);
            }
            
            // تحديث عدد المستندات في المشروع
            $this->updateProjectStats($data['project_id']);
            
            // تسجيل النشاط
            $this->logActivity('create', 'document', $document_id, 'إنشاء مستند جديد');
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء المستند بنجاح';
            $response['document_id'] = $document_id;
        } else {
            $response['message'] = 'حدث خطأ في إنشاء المستند';
        }
        
        return $response;
    }
    
    /**
     * تحديث مستند
     * @param int $document_id
     * @param array $data
     * @return array
     */
    public function updateDocument($document_id, $data) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // الحصول على الإصدار الحالي
        $current = $this->getDocument($document_id);
        
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if ($key != 'id' && $key != 'document_code') {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        $params[] = $document_id;
        
        $sql = "UPDATE documents SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            // إذا تغير الإصدار
            if (isset($data['version']) && $data['version'] != $current['version']) {
                $this->createDocumentVersion($document_id, [
                    'version_number' => $data['version'],
                    'changes' => $data['changes_summary'] ?? 'تحديث المستند'
                ]);
            }
            
            // تحديث عدد الكلمات
            if (isset($data['content'])) {
                $this->updateWordCount($document_id, $data['content']);
            }
            
            // تسجيل النشاط
            $this->logActivity('update', 'document', $document_id, 'تحديث المستند');
            
            $response['success'] = true;
            $response['message'] = 'تم تحديث المستند بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في تحديث المستند';
        }
        
        return $response;
    }
    
    /**
     * حذف مستند
     * @param int $document_id
     * @return array
     */
    public function deleteDocument($document_id) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        // الحصول على معلومات المستند قبل الحذف
        $document = $this->getDocument($document_id);
        
        $sql = "DELETE FROM documents WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$document_id])) {
            // تحديث إحصائيات المشروع
            if ($document && $document['project_id']) {
                $this->updateProjectStats($document['project_id']);
            }
            
            // تسجيل النشاط
            $this->logActivity('delete', 'document', $document_id, 'حذف المستند');
            
            $response['success'] = true;
            $response['message'] = 'تم حذف المستند بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في حذف المستند';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز مستند فريد
     * @param string $type
     * @return string
     */
    private function generateDocumentCode($type) {
        $prefixes = [
            'technical' => 'TECH',
            'security' => 'SEC',
            'api' => 'API',
            'user_guide' => 'UG',
            'requirements' => 'REQ',
            'report' => 'REP'
        ];
        
        $prefix = $prefixes[$type] ?? 'DOC';
        $year = date('Y');
        
        // الحصول على آخر رقم
        $sql = "SELECT COUNT(*) as count FROM documents WHERE document_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * إنشاء إصدار جديد
     * @param int $document_id
     * @param array $data
     * @return bool
     */
    private function createDocumentVersion($document_id, $data) {
        $sql = "INSERT INTO document_versions (
                    document_id, version_number, changes, created_by, created_at
                ) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $document_id,
            $data['version_number'],
            $data['changes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ]);
    }
    
    /**
     * تحديث عدد الكلمات
     * @param int $document_id
     * @param string $content
     */
    private function updateWordCount($document_id, $content) {
        $word_count = str_word_count(strip_tags($content));
        
        $sql = "UPDATE documents SET word_count = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$word_count, $document_id]);
    }
    
    /**
     * تحديث إحصائيات المشروع
     * @param int $project_id
     */
    private function updateProjectStats($project_id) {
        if (!$project_id) return;
        
        $sql = "UPDATE documentation_projects p
                SET 
                    documents_count = (SELECT COUNT(*) FROM documents WHERE project_id = p.id),
                    pages_count = (SELECT COALESCE(SUM(pages), 0) FROM documents WHERE project_id = p.id)
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$project_id]);
    }
    
    /**
     * =============================================
     * دوال الوسوم
     * =============================================
     */
    
    /**
     * الحصول على جميع الوسوم
     * @return array
     */
    public function getTags() {
        $sql = "SELECT * FROM tags ORDER BY name";
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * إضافة وسم جديد
     * @param string $name
     * @param string $color
     * @return array
     */
    public function createTag($name, $color = 'blue') {
        $response = [
            'success' => false,
            'message' => '',
            'tag_id' => null
        ];
        
        $sql = "INSERT INTO tags (name, color) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$name, $color])) {
            $response['success'] = true;
            $response['message'] = 'تم إنشاء الوسم بنجاح';
            $response['tag_id'] = $this->db->lastInsertId();
        } else {
            $response['message'] = 'حدث خطأ في إنشاء الوسم';
        }
        
        return $response;
    }
    
    /**
     * إضافة وسوم لمستند
     * @param int $document_id
     * @param array $tag_ids
     */
    private function addDocumentTags($document_id, $tag_ids) {
        $sql = "INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($tag_ids as $tag_id) {
            $stmt->execute([$document_id, $tag_id]);
        }
    }
    
    /**
     * =============================================
     * دوال المراجعات
     * =============================================
     */
    
    /**
     * إنشاء مراجعة جديدة
     * @param array $data
     * @return array
     */
    public function createReview($data) {
        $response = [
            'success' => false,
            'message' => '',
            'review_id' => null
        ];
        
        $sql = "INSERT INTO document_reviews (
                    document_id, reviewer_id, review_type, status,
                    review_date, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['document_id'],
            $data['reviewer_id'],
            $data['review_type'],
            'pending',
            date('Y-m-d')
        ])) {
            $review_id = $this->db->lastInsertId();
            
            // تحديث حالة المستند
            $this->updateDocument($data['document_id'], [
                'status' => 'under_review',
                'reviewed_by' => $data['reviewer_id']
            ]);
            
            // تسجيل النشاط
            $this->logActivity('create', 'review', $review_id, 'إنشاء مراجعة جديدة');
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء المراجعة بنجاح';
            $response['review_id'] = $review_id;
        } else {
            $response['message'] = 'حدث خطأ في إنشاء المراجعة';
        }
        
        return $response;
    }
    
    /**
     * إكمال المراجعة
     * @param int $review_id
     * @param array $data
     * @return array
     */
    public function completeReview($review_id, $data) {
        $response = [
            'success' => false,
            'message' => ''
        ];
        
        $sql = "UPDATE document_reviews SET
                    status = 'completed',
                    comments = ?,
                    feedback = ?,
                    checklist = ?,
                    rating = ?,
                    decision = ?,
                    completed_date = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['comments'] ?? null,
            $data['feedback'] ?? null,
            json_encode($data['checklist'] ?? []),
            $data['rating'] ?? null,
            $data['decision'],
            date('Y-m-d'),
            $review_id
        ])) {
            // الحصول على معرف المستند
            $sql = "SELECT document_id FROM document_reviews WHERE id = ?";
            $stmt2 = $this->db->prepare($sql);
            $stmt2->execute([$review_id]);
            $review = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            // تحديث حالة المستند حسب القرار
            $status = ($data['decision'] == 'approve') ? 'approved' : 'needs_work';
            $this->updateDocument($review['document_id'], ['status' => $status]);
            
            // تسجيل النشاط
            $this->logActivity('complete', 'review', $review_id, 'إكمال المراجعة');
            
            $response['success'] = true;
            $response['message'] = 'تم إكمال المراجعة بنجاح';
        } else {
            $response['message'] = 'حدث خطأ في إكمال المراجعة';
        }
        
        return $response;
    }
    
    /**
     * =============================================
     * دوال التقارير
     * =============================================
     */
    
    /**
     * إنشاء تقرير
     * @param array $data
     * @return array
     */
    public function createReport($data) {
        $response = [
            'success' => false,
            'message' => '',
            'report_id' => null
        ];
        
        $report_code = $this->generateReportCode($data['report_type']);
        
        $sql = "INSERT INTO reports (
                    report_code, report_title, report_type, recipient,
                    priority, status, format, summary, notes, created_by,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $report_code,
            $data['report_title'],
            $data['report_type'],
            $data['recipient'],
            $data['priority'] ?? 'normal',
            'preparing',
            $data['format'] ?? 'pdf',
            $data['summary'] ?? null,
            $data['notes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ])) {
            $report_id = $this->db->lastInsertId();
            
            // إضافة المستندات للتقرير
            if (!empty($data['document_ids'])) {
                $this->addReportDocuments($report_id, $data['document_ids']);
            }
            
            // تسجيل النشاط
            $this->logActivity('create', 'report', $report_id, 'إنشاء تقرير جديد');
            
            $response['success'] = true;
            $response['message'] = 'تم إنشاء التقرير بنجاح';
            $response['report_id'] = $report_id;
        } else {
            $response['message'] = 'حدث خطأ في إنشاء التقرير';
        }
        
        return $response;
    }
    
    /**
     * توليد رمز تقرير فريد
     * @param string $type
     * @return string
     */
    private function generateReportCode($type) {
        $prefixes = [
            'monthly' => 'RPT-MON',
            'security' => 'RPT-SEC',
            'technical' => 'RPT-TECH',
            'progress' => 'RPT-PROG',
            'final' => 'RPT-FIN'
        ];
        
        $prefix = $prefixes[$type] ?? 'RPT';
        $year = date('Y');
        $month = date('m');
        
        // الحصول على آخر رقم
        $sql = "SELECT COUNT(*) as count FROM reports WHERE report_code LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["{$prefix}-{$year}-%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $number = str_pad($result['count'] + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$number}";
    }
    
    /**
     * إضافة مستندات للتقرير
     * @param int $report_id
     * @param array $document_ids
     */
    private function addReportDocuments($report_id, $document_ids) {
        $sql = "INSERT INTO report_documents (report_id, document_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($document_ids as $doc_id) {
            $stmt->execute([$report_id, $doc_id]);
        }
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
        $stats = [];
        
        // إجمالي المشاريع
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new
                FROM documentation_projects";
        $stmt = $this->db->query($sql);
        $stats['projects'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // إجمالي المستندات
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'needs_work' THEN 1 ELSE 0 END) as needs_work,
                    COALESCE(SUM(pages), 0) as total_pages
                FROM documents";
        $stmt = $this->db->query($sql);
        $stats['documents'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // إجمالي المراجعات
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM document_reviews";
        $stmt = $this->db->query($sql);
        $stats['reviews'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // حجم المستودع
        $sql = "SELECT COALESCE(SUM(file_size), 0) as total_size FROM repository_files";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['repository_size'] = $result['total_size'];
        
        return $stats;
    }
    
    /**
     * الحصول على آخر النشاطات
     * @param int $limit
     * @return array
     */
    public function getRecentActivity($limit = 10) {
        $sql = "SELECT a.*, u.full_name as user_name
                FROM documentation_activity_log a
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
        $sql = "INSERT INTO documentation_activity_log (
                    user_id, activity_type, target_type, target_id, description, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
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
        $status_colors = [
            'new' => 'blue',
            'in_progress' => 'yellow',
            'completed' => 'green',
            'on_hold' => 'orange',
            'cancelled' => 'red'
        ];
        
        $priority_colors = [
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'critical' => 'red'
        ];
        
        $color = $status_colors[$project['status']] ?? 'gray';
        $priority_color = $priority_colors[$project['priority']] ?? 'gray';
        
        return "
        <div class='card-hover cyber-border bg-slate-800 rounded-xl p-6'>
            <div class='flex items-center justify-between mb-4'>
                <span class='px-3 py-1 bg-{$priority_color}-600 bg-opacity-20 text-{$priority_color}-400 rounded-full text-xs'>
                    " . $this->getPriorityLabel($project['priority']) . "
                </span>
                <span class='text-sm text-gray-400'>{$project['project_code']}</span>
            </div>
            
            <h3 class='text-xl font-bold mb-2'>{$project['project_name']}</h3>
            <p class='text-sm text-gray-400 mb-4'>{$project['client_name']}</p>
            
            <div class='flex items-center justify-between mb-4'>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'/>
                    </svg>
                    <span class='text-sm'>{$project['documents_count']} مستند</span>
                </div>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z'/>
                    </svg>
                    <span class='text-sm'>{$project['total_pages']} صفحة</span>
                </div>
            </div>
            
            <div class='mb-4'>
                <div class='flex items-center justify-between text-sm mb-1'>
                    <span class='text-gray-400'>التقدم</span>
                    <span class='text-{$color}-400'>{$project['progress']}%</span>
                </div>
                <div class='progress-bar'>
                    <div class='progress-fill bg-{$color}-400' style='width: {$project['progress']}%'></div>
                </div>
            </div>
            
            <div class='flex items-center justify-between text-sm text-gray-400 mb-4'>
                <span>من: " . formatDate($project['start_date']) . "</span>
                <span>إلى: " . formatDate($project['deadline']) . "</span>
            </div>
            
            <div class='flex items-center justify-between'>
                <span class='px-3 py-1 bg-{$color}-600 bg-opacity-20 text-{$color}-400 rounded-full text-xs'>
                    " . $this->getStatusLabel($project['status']) . "
                </span>
                <a href='/project.php?id={$project['id']}' class='text-blue-400 hover:text-blue-300 text-sm flex items-center'>
                    عرض التفاصيل
                    <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M14 5l7 7m0 0l-7 7m7-7H3'/>
                    </svg>
                </a>
            </div>
        </div>";
    }
    
    /**
     * عرض بطاقة مستند
     * @param array $document
     * @return string
     */
    public function renderDocumentCard($document) {
        $type_labels = [
            'technical' => 'توثيق تقني',
            'security' => 'توثيق أمني',
            'api' => 'توثيق API',
            'user_guide' => 'دليل مستخدم',
            'requirements' => 'متطلبات',
            'report' => 'تقرير'
        ];
        
        $type_colors = [
            'technical' => 'blue',
            'security' => 'red',
            'api' => 'purple',
            'user_guide' => 'green',
            'requirements' => 'yellow',
            'report' => 'orange'
        ];
        
        $color = $type_colors[$document['document_type']] ?? 'gray';
        
        return "
        <div class='card-hover document-card bg-slate-800 rounded-xl p-6'>
            <div class='flex items-center justify-between mb-3'>
                <span class='text-sm text-gray-400'>{$document['document_code']}</span>
                <span class='version-badge'>{$document['version']}</span>
            </div>
            
            <h4 class='text-lg font-bold mb-2'>{$document['title']}</h4>
            <p class='text-sm text-gray-400 mb-3'>{$document['project_name']}</p>
            
            <div class='flex items-center justify-between mb-4'>
                <span class='px-3 py-1 bg-{$color}-600 bg-opacity-20 text-{$color}-400 rounded-full text-xs'>
                    " . ($type_labels[$document['document_type']] ?? $document['document_type']) . "
                </span>
                " . buildStatusBadge($document['status']) . "
            </div>
            
            <div class='flex items-center justify-between text-sm text-gray-400'>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'/>
                    </svg>
                    <span>{$document['creator_name']}</span>
                </div>
                <div class='flex items-center'>
                    <svg class='w-4 h-4 text-gray-400 ml-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/>
                    </svg>
                    <span>" . formatDate($document['created_date']) . "</span>
                </div>
            </div>
        </div>";
    }
    
    /**
     * الحصول على تسمية الحالة
     * @param string $status
     * @return string
     */
    private function getStatusLabel($status) {
        $labels = [
            'new' => 'جديد',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'on_hold' => 'معلق',
            'cancelled' => 'ملغي',
            'draft' => 'مسودة',
            'under_review' => 'قيد المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'needs_work' => 'بحاجة لعمل',
            'archived' => 'مؤرشف',
            'obsolete' => 'قديم'
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
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'عالية',
            'critical' => 'حرجة'
        ];
        
        return $labels[$priority] ?? $priority;
    }
}

/**
 * =============================================
 * دوال مساعدة للتوثيق
 * =============================================
 */

/**
 * إنشاء كائن Documentation
 * @param PDO $db
 * @param Auth|null $auth
 * @return Documentation
 */
function documentation($db, $auth = null) {
    static $doc = null;
    
    if ($doc === null) {
        $doc = new Documentation($db, $auth);
    }
    
    return $doc;
}

/**
 * حساب عدد الكلمات في النص
 * @param string $text
 * @return int
 */
function countWords($text) {
    $text = strip_tags($text);
    $text = preg_replace('/[^\p{Arabic}\p{Latin}\s]/u', '', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
    return count($words);
}

/**
 * إنشاء ملخص للنص
 * @param string $text
 * @param int $length
 * @return string
 */
function generateSummary($text, $length = 200) {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    $summary = mb_substr($text, 0, $length);
    $last_space = mb_strrpos($summary, ' ');
    
    if ($last_space !== false) {
        $summary = mb_substr($summary, 0, $last_space);
    }
    
    return $summary . '...';
}

/**
 * =============================================
 * نهاية الملف
 * =============================================
 */
?>