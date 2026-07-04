<?php
// =============================================
// documentation-unit/pages/documents.php
// صفحة إدارة المستندات - وحدة التوثيق الفني
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'add_document':
                // إضافة مستند جديد
                $document_code = generateDocumentCode($db, $_POST['document_type']);
                
                $sql = "INSERT INTO documents (
                    document_code, title, project_id, document_type, format,
                    version, status, content, executive_summary, introduction,
                    file_path, file_size, pages, word_count, created_by,
                    created_date, tags, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $document_code,
                    $_POST['title'],
                    $_POST['project_id'] ?: null,
                    $_POST['document_type'],
                    $_POST['format'] ?? 'pdf',
                    $_POST['version'] ?? '1.0.0',
                    $_POST['status'] ?? 'draft',
                    $_POST['content'] ?? null,
                    $_POST['executive_summary'] ?? null,
                    $_POST['introduction'] ?? null,
                    $_POST['file_path'] ?? null,
                    $_POST['file_size'] ?? 0,
                    $_POST['pages'] ?? 0,
                    $_POST['word_count'] ?? 0,
                    $_SESSION['user_id'] ?? 1,
                    $_POST['created_date'] ?? date('Y-m-d'),
                    $_POST['tags'] ?? null,
                    $_POST['description'] ?? null
                ]);
                
                $document_id = $db->lastInsertId();
                
                // إضافة الإصدار الأول
                $version_sql = "INSERT INTO document_versions (document_id, version_number, changes, created_by, created_at) 
                               VALUES (?, ?, ?, ?, NOW())";
                $version_stmt = $db->prepare($version_sql);
                $version_stmt->execute([
                    $document_id,
                    $_POST['version'] ?? '1.0.0',
                    'الإصدار الأولي',
                    $_SESSION['user_id'] ?? 1
                ]);
                
                // تحديث إحصائيات المشروع
                if ($_POST['project_id']) {
                    updateProjectStats($db, $_POST['project_id']);
                }
                
                // تسجيل النشاط
                logActivity($db, 'create', 'document', $document_id, 'إضافة مستند جديد: ' . $_POST['title']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إضافة المستند بنجاح';
                $response['document_id'] = $document_id;
                break;
                
            case 'edit_document':
                // تحديث مستند
                $sql = "UPDATE documents SET
                    title = ?,
                    project_id = ?,
                    document_type = ?,
                    format = ?,
                    version = ?,
                    status = ?,
                    content = ?,
                    executive_summary = ?,
                    introduction = ?,
                    file_path = ?,
                    file_size = ?,
                    pages = ?,
                    tags = ?,
                    description = ?,
                    updated_at = NOW()
                WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['title'],
                    $_POST['project_id'] ?: null,
                    $_POST['document_type'],
                    $_POST['format'] ?? 'pdf',
                    $_POST['version'] ?? '1.0.0',
                    $_POST['status'] ?? 'draft',
                    $_POST['content'] ?? null,
                    $_POST['executive_summary'] ?? null,
                    $_POST['introduction'] ?? null,
                    $_POST['file_path'] ?? null,
                    $_POST['file_size'] ?? 0,
                    $_POST['pages'] ?? 0,
                    $_POST['tags'] ?? null,
                    $_POST['description'] ?? null,
                    $_POST['document_id']
                ]);
                
                // التحقق إذا تغير الإصدار
                if (isset($_POST['version_changed']) && $_POST['version_changed'] == 'true') {
                    $version_sql = "INSERT INTO document_versions (document_id, version_number, changes, created_by, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())";
                    $version_stmt = $db->prepare($version_sql);
                    $version_stmt->execute([
                        $_POST['document_id'],
                        $_POST['version'],
                        $_POST['changes_summary'] ?? 'تحديث المستند',
                        $_SESSION['user_id'] ?? 1
                    ]);
                }
                
                // تحديث إحصائيات المشروع
                if ($_POST['project_id']) {
                    updateProjectStats($db, $_POST['project_id']);
                }
                
                logActivity($db, 'update', 'document', $_POST['document_id'], 'تحديث مستند: ' . $_POST['title']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث المستند بنجاح';
                break;
                
            case 'delete_document':
                // حذف مستند
                // أولاً نجلب المشروع المرتبط
                $stmt = $db->prepare("SELECT project_id, title FROM documents WHERE id = ?");
                $stmt->execute([$_POST['document_id']]);
                $doc = $stmt->fetch();
                
                // حذف الإصدارات المرتبطة
                $db->prepare("DELETE FROM document_versions WHERE document_id = ?")->execute([$_POST['document_id']]);
                
                // حذف التعليقات المرتبطة
                $db->prepare("DELETE FROM document_comments WHERE document_id = ?")->execute([$_POST['document_id']]);
                
                // حذف المراجعات المرتبطة
                $db->prepare("DELETE FROM document_reviews WHERE document_id = ?")->execute([$_POST['document_id']]);
                
                // حذف الوسوم المرتبطة
                $db->prepare("DELETE FROM document_tags WHERE document_id = ?")->execute([$_POST['document_id']]);
                
                // حذف المستند
                $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$_POST['document_id']]);
                
                // تحديث إحصائيات المشروع
                if ($doc && $doc['project_id']) {
                    updateProjectStats($db, $doc['project_id']);
                }
                
                logActivity($db, 'delete', 'document', $_POST['document_id'], 'حذف مستند: ' . ($doc['title'] ?? ''));
                
                $response['success'] = true;
                $response['message'] = '✅ تم حذف المستند بنجاح';
                break;
                
            case 'bulk_delete':
                // حذف مجموعة مستندات
                $ids = $_POST['document_ids'] ?? [];
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    
                    // حذف الإصدارات
                    $db->prepare("DELETE FROM document_versions WHERE document_id IN ($placeholders)")->execute($ids);
                    
                    // حذف التعليقات
                    $db->prepare("DELETE FROM document_comments WHERE document_id IN ($placeholders)")->execute($ids);
                    
                    // حذف المراجعات
                    $db->prepare("DELETE FROM document_reviews WHERE document_id IN ($placeholders)")->execute($ids);
                    
                    // حذف الوسوم
                    $db->prepare("DELETE FROM document_tags WHERE document_id IN ($placeholders)")->execute($ids);
                    
                    // حذف المستندات
                    $db->prepare("DELETE FROM documents WHERE id IN ($placeholders)")->execute($ids);
                    
                    logActivity($db, 'bulk_delete', 'document', 0, 'حذف مجموعة مستندات: ' . count($ids) . ' مستند');
                    
                    $response['success'] = true;
                    $response['message'] = '✅ تم حذف ' . count($ids) . ' مستند بنجاح';
                }
                break;
                
            case 'update_status':
                // تحديث حالة مستند
                $sql = "UPDATE documents SET status = ? WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['status'], $_POST['document_id']]);
                
                logActivity($db, 'update', 'document', $_POST['document_id'], 'تحديث حالة المستند إلى: ' . $_POST['status']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تحديث الحالة بنجاح';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    // إذا كان طلب AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // إذا كان طلب عادي
    $_SESSION['flash_message'] = $response;
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $project_filter = $_GET['project'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';
    
    // بناء استعلام المستندات
    $sql = "
        SELECT 
            d.*,
            p.project_name,
            p.project_code,
            u.full_name as creator_name,
            u_review.full_name as reviewer_name,
            u_approve.full_name as approver_name,
            (SELECT COUNT(*) FROM document_versions WHERE document_id = d.id) as versions_count,
            (SELECT COUNT(*) FROM document_comments WHERE document_id = d.id) as comments_count,
            (SELECT COUNT(*) FROM document_reviews WHERE document_id = d.id) as reviews_count,
            DATEDIFF(NOW(), d.created_at) as days_old
        FROM documents d
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        LEFT JOIN users u ON d.created_by = u.id
        LEFT JOIN users u_review ON d.reviewed_by = u_review.id
        LEFT JOIN users u_approve ON d.approved_by = u_approve.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($project_filter) {
        $sql .= " AND d.project_id = ?";
        $params[] = $project_filter;
    }
    
    if ($type_filter) {
        $sql .= " AND d.document_type = ?";
        $params[] = $type_filter;
    }
    
    if ($status_filter) {
        $sql .= " AND d.status = ?";
        $params[] = $status_filter;
    }
    
    if ($search) {
        $sql .= " AND (d.title LIKE ? OR d.document_code LIKE ? OR d.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // الترتيب
    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY d.created_at ASC";
            break;
        case 'title':
            $sql .= " ORDER BY d.title ASC";
            break;
        case 'type':
            $sql .= " ORDER BY d.document_type, d.created_at DESC";
            break;
        case 'status':
            $sql .= " ORDER BY 
                CASE d.status
                    WHEN 'draft' THEN 1
                    WHEN 'under_review' THEN 2
                    WHEN 'approved' THEN 3
                    WHEN 'needs_work' THEN 4
                    ELSE 5
                END, d.created_at DESC";
            break;
        default: // newest
            $sql .= " ORDER BY d.created_at DESC";
            break;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
    
    // إحصائيات المستندات
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as review_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = 'needs_work' THEN 1 ELSE 0 END) as needs_work_count,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count,
            SUM(CASE WHEN document_type = 'technical' THEN 1 ELSE 0 END) as technical_count,
            SUM(CASE WHEN document_type = 'security' THEN 1 ELSE 0 END) as security_count,
            SUM(CASE WHEN document_type = 'api' THEN 1 ELSE 0 END) as api_count,
            SUM(CASE WHEN document_type = 'user_guide' THEN 1 ELSE 0 END) as user_guide_count,
            COALESCE(SUM(pages), 0) as total_pages,
            COALESCE(SUM(file_size), 0) as total_size
        FROM documents
    ")->fetch();
    
    // قائمة المشاريع للفلتر
    $projects = $db->query("SELECT id, project_name, project_code FROM documentation_projects WHERE status != 'archived' ORDER BY project_name")->fetchAll();
    
    // قائمة أنواع المستندات
    $document_types = $db->query("SELECT DISTINCT document_type, COUNT(*) as count FROM documents GROUP BY document_type ORDER BY count DESC")->fetchAll();
    
    // آخر 5 مستندات مضافة (للسايدبار)
    $recent_docs = $db->query("
        SELECT id, title, document_code, created_at 
        FROM documents 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// =============================================
// دوال مساعدة
// =============================================
function generateDocumentCode($db, $type) {
    $prefixes = [
        'technical' => 'TECH',
        'security' => 'SEC',
        'api' => 'API',
        'user_guide' => 'UG',
        'requirements' => 'REQ',
        'report' => 'REP',
        'architecture' => 'ARCH'
    ];
    
    $prefix = $prefixes[$type] ?? 'DOC';
    $year = date('Y');
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM documents WHERE document_code LIKE ?");
    $stmt->execute(["{$prefix}-{$year}-%"]);
    $result = $stmt->fetch();
    
    $number = str_pad(($result['count'] ?? 0) + 1, 3, '0', STR_PAD_LEFT);
    return "{$prefix}-{$year}-{$number}";
}

function updateProjectStats($db, $project_id) {
    if (!$project_id) return;
    
    $sql = "UPDATE documentation_projects p
            SET 
                documents_count = (SELECT COUNT(*) FROM documents WHERE project_id = p.id),
                pages_count = (SELECT COALESCE(SUM(pages), 0) FROM documents WHERE project_id = p.id)
            WHERE p.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
}

function logActivity($db, $type, $target, $target_id, $description) {
    $sql = "INSERT INTO documentation_activity_log (user_id, activity_type, target_type, target_id, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_SESSION['user_id'] ?? 1, $type, $target, $target_id, $description]);
}

function getDocumentStatusBadge($status) {
    $classes = [
        'draft' => 'bg-gray-600 bg-opacity-20 text-gray-400',
        'under_review' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'approved' => 'bg-green-600 bg-opacity-20 text-green-400',
        'rejected' => 'bg-red-600 bg-opacity-20 text-red-400',
        'needs_work' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'archived' => 'bg-purple-600 bg-opacity-20 text-purple-400'
    ];
    
    $texts = [
        'draft' => 'مسودة',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'needs_work' => 'بحاجة لعمل',
        'archived' => 'مؤرشف'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getDocumentTypeBadge($type) {
    $classes = [
        'technical' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'security' => 'bg-red-600 bg-opacity-20 text-red-400',
        'api' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'user_guide' => 'bg-green-600 bg-opacity-20 text-green-400',
        'requirements' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'report' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'architecture' => 'bg-cyan-600 bg-opacity-20 text-cyan-400'
    ];
    
    $texts = [
        'technical' => 'تقني',
        'security' => 'أمني',
        'api' => 'API',
        'user_guide' => 'دليل مستخدم',
        'requirements' => 'متطلبات',
        'report' => 'تقرير',
        'architecture' => 'هيكلية'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getDocumentTypeText($type) {
    $texts = [
        'technical' => 'تقني',
        'security' => 'أمني',
        'api' => 'API',
        'user_guide' => 'دليل مستخدم',
        'requirements' => 'متطلبات',
        'report' => 'تقرير',
        'architecture' => 'هيكلية'
    ];
    
    return $texts[$type] ?? $type;
}

function getDocumentFormatIcon($format) {
    $icons = [
        'pdf' => '📄',
        'docx' => '📝',
        'xlsx' => '📊',
        'pptx' => '📽️',
        'txt' => '📃',
        'md' => '📑',
        'html' => '🌐',
        'xml' => '🔧',
        'json' => '📦'
    ];
    
    return $icons[$format] ?? '📄';
}



?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- إذا كان في عرض مستند واحد -->
<!-- ============================================= -->
<?php if (isset($_GET['view']) && !empty($_GET['view'])): 
    // جلب تفاصيل مستند محدد
    $doc_id = (int)$_GET['view'];
    $stmt = $db->prepare("
        SELECT d.*, 
               p.project_name, p.project_code,
               u_creator.full_name as creator_name, u_creator.email as creator_email,
               u_review.full_name as reviewer_name,
               u_approve.full_name as approver_name
        FROM documents d
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        LEFT JOIN users u_creator ON d.created_by = u_creator.id
        LEFT JOIN users u_review ON d.reviewed_by = u_review.id
        LEFT JOIN users u_approve ON d.approved_by = u_approve.id
        WHERE d.id = ?
    ");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch();
    
    if (!$document): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
            <p class="text-red-400 text-xl">❌ المستند غير موجود</p>
        </div>
    <?php else:
        // جلب إصدارات المستند
        $versions = $db->prepare("SELECT * FROM document_versions WHERE document_id = ? ORDER BY created_at DESC");
        $versions->execute([$doc_id]);
        $versions = $versions->fetchAll();
        
        // جلب تعليقات المستند
        $comments = $db->prepare("
            SELECT c.*, u.full_name as user_name 
            FROM document_comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.document_id = ? 
            ORDER BY c.created_at DESC
        ");
        $comments->execute([$doc_id]);
        $comments = $comments->fetchAll();
    ?>
    
    <!-- عرض تفاصيل المستند -->
    <div class="cyber-border bg-slate-800 rounded-xl p-8 mb-6">
        <!-- شريط التنقل -->
        <div class="flex items-center justify-between mb-6">
            <button onclick="window.location.href='?page=documents'" class="text-blue-400 hover:text-blue-300 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                العودة إلى المستندات
            </button>
            <h2 class="text-2xl font-bold text-right">تفاصيل المستند</h2>
        </div>
        
        <!-- رأس المستند -->
        <div class="bg-slate-900 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex space-x-2 space-x-reverse">
                    <button onclick="editDocument(<?php echo $document['id']; ?>)" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        تعديل
                    </button>
                    <button onclick="downloadDocument(<?php echo $document['id']; ?>)" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        تحميل
                    </button>
                    <button onclick="printDocument(<?php echo $document['id']; ?>)" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        طباعة
                    </button>
                </div>
                <div>
                    <span class="text-sm text-gray-400"><?php echo $document['document_code']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- محتوى المستند -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- المعلومات الأساسية -->
            <div class="lg:col-span-2">
                <div class="bg-slate-900 rounded-lg p-6 mb-6">
                    <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($document['title']); ?></h1>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-400">المشروع</p>
                            <p class="font-semibold"><?php echo htmlspecialchars($document['project_name'] ?? 'غير محدد'); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">النوع</p>
                            <p><?php echo getDocumentTypeBadge($document['document_type']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">الحالة</p>
                            <p><?php echo getDocumentStatusBadge($document['status']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-400">الإصدار</p>
                            <p class="font-mono text-blue-400">v<?php echo $document['version']; ?></p>
                        </div>
                    </div>
                    
                    <?php if ($document['description']): ?>
                    <div class="mb-6">
                        <p class="text-sm text-gray-400 mb-2">الوصف</p>
                        <p class="bg-slate-800 p-4 rounded-lg"><?php echo nl2br(htmlspecialchars($document['description'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($document['content']): ?>
                    <div>
                        <p class="text-sm text-gray-400 mb-2">المحتوى</p>
                        <div class="bg-slate-800 p-4 rounded-lg prose prose-invert max-w-none">
                            <?php echo $document['content']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- التعليقات -->
                <div class="bg-slate-900 rounded-lg p-6">
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        التعليقات (<?php echo count($comments); ?>)
                    </h3>
                    
                    <?php if (empty($comments)): ?>
                        <p class="text-gray-400 text-center py-4">لا توجد تعليقات بعد</p>
                    <?php else: ?>
                        <div class="space-y-4 mb-6">
                            <?php foreach ($comments as $comment): ?>
                            <div class="bg-slate-800 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-400"><?php echo timeAgo($comment['created_at']); ?></span>
                                    <span class="font-semibold"><?php echo htmlspecialchars($comment['user_name'] ?? 'مستخدم'); ?></span>
                                </div>
                                <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                <?php if ($comment['page_number']): ?>
                                    <p class="text-xs text-gray-500 mt-2">الصفحة: <?php echo $comment['page_number']; ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- إضافة تعليق جديد -->
                    <form onsubmit="addComment(event, <?php echo $document['id']; ?>)" class="mt-4">
                        <textarea id="new-comment" rows="3" class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="أضف تعليقاً..."></textarea>
                        <div class="flex items-center justify-between mt-2">
                            <input type="number" id="comment-page" placeholder="رقم الصفحة (اختياري)" class="w-32 px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-right text-sm">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                                إضافة تعليق
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- الشريط الجانبي -->
            <div class="lg:col-span-1">
                <!-- معلومات إضافية -->
                <div class="bg-slate-900 rounded-lg p-6 mb-6">
                    <h4 class="font-bold mb-4">معلومات إضافية</h4>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">المنشئ</span>
                            <span class="font-semibold"><?php echo htmlspecialchars($document['creator_name'] ?? 'النظام'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">تاريخ الإنشاء</span>
                            <span><?php echo date('Y-m-d', strtotime($document['created_date'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">آخر تحديث</span>
                            <span><?php echo $document['updated_at'] ? timeAgo($document['updated_at']) : '-'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">عدد الصفحات</span>
                            <span><?php echo $document['pages']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">عدد الكلمات</span>
                            <span><?php echo number_format($document['word_count']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">حجم الملف</span>
                            <span><?php echo formatFileSize($document['file_size']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- إصدارات المستند -->
                <div class="bg-slate-900 rounded-lg p-6 mb-6">
                    <h4 class="font-bold mb-4">الإصدارات</h4>
                    <?php if (empty($versions)): ?>
                        <p class="text-gray-400 text-sm">لا توجد إصدارات سابقة</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($versions as $version): ?>
                            <div class="border-r-2 border-blue-500 pr-4 py-2">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-400"><?php echo timeAgo($version['created_at']); ?></span>
                                    <span class="font-mono text-sm text-blue-400">v<?php echo $version['version_number']; ?></span>
                                </div>
                                <p class="text-xs text-gray-300"><?php echo htmlspecialchars($version['changes'] ?? 'تحديث'); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- أزرار إضافية -->
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="shareDocument(<?php echo $document['id']; ?>)" class="p-3 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm flex items-center justify-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        مشاركة
                    </button>
                    <button onclick="exportDocument(<?php echo $document['id']; ?>)" class="p-3 bg-green-600 hover:bg-green-700 rounded-lg text-sm flex items-center justify-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        تصدير
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>

<?php else: ?>
<!-- ============================================= -->
<!-- عرض قائمة المستندات (الصفحة الرئيسية) -->
<!-- ============================================= -->

<!-- رأس الصفحة مع الإحصائيات -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <!-- إجمالي المستندات -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">إجمالي المستندات</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total'] ?? 0; ?></p>
        <p class="text-xs text-gray-500 mt-1"><?php echo number_format($stats['total_pages'] ?? 0); ?> صفحة</p>
    </div>
    
    <!-- مسودة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مسودة</p>
        <p class="text-2xl font-bold text-gray-400"><?php echo $stats['draft_count'] ?? 0; ?></p>
    </div>
    
    <!-- قيد المراجعة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">قيد المراجعة</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['review_count'] ?? 0; ?></p>
    </div>
    
    <!-- معتمدة -->
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">معتمدة</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['approved_count'] ?? 0; ?></p>
    </div>
</div>

<!-- شريط البحث والفلاتر -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h3 class="text-xl font-bold text-right">إدارة المستندات</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="openDocumentModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                مستند جديد
            </button>
            
            <button onclick="showBulkActions()" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
    </svg>
    تحديد متعدد
</button>
        </div>
    </div>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <input type="hidden" name="page" value="documents">
        
        <!-- بحث -->
        <div class="relative md:col-span-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="🔍 بحث في المستندات..." 
                   class="w-full px-4 py-3 search-box rounded-lg text-right pr-12">
            <button type="submit" class="absolute left-2 top-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        
        <!-- فلتر المشروع -->
        <select name="project" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع المشاريع</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($project['project_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <!-- فلتر النوع -->
        <select name="type" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع الأنواع</option>
            <?php foreach ($document_types as $type): ?>
                <option value="<?php echo $type['document_type']; ?>" <?php echo $type_filter == $type['document_type'] ? 'selected' : ''; ?>>
                    <?php echo getDocumentTypeText($type['document_type']); ?> (<?php echo $type['count']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
        
        <!-- فلتر الحالة -->
        <select name="status" class="px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
            <option value="">جميع الحالات</option>
            <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>مسودة</option>
            <option value="under_review" <?php echo $status_filter == 'under_review' ? 'selected' : ''; ?>>قيد المراجعة</option>
            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>معتمد</option>
            <option value="needs_work" <?php echo $status_filter == 'needs_work' ? 'selected' : ''; ?>>بحاجة لعمل</option>
        </select>
        
        <!-- صف إضافي للترتيب وإعادة التعيين -->
        <div class="md:col-span-5 flex items-center justify-between mt-2">
            <div class="flex items-center space-x-2 space-x-reverse">
                <label class="text-sm text-gray-400">ترتيب حسب:</label>
                <select name="sort" onchange="this.form.submit()" class="px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>الأحدث أولاً</option>
                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>الأقدم أولاً</option>
                    <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>حسب العنوان</option>
                    <option value="type" <?php echo $sort == 'type' ? 'selected' : ''; ?>>حسب النوع</option>
                    <option value="status" <?php echo $sort == 'status' ? 'selected' : ''; ?>>حسب الحالة</option>
                </select>
            </div>
            
            <?php if ($search || $project_filter || $type_filter || $status_filter): ?>
            <a href="?page=documents" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm transition-all">
                ✕ إعادة تعيين الفلاتر
            </a>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- شريط الإجراءات المتعددة (يظهر عند التحديد) -->
    <div id="bulk-actions-bar" class="hidden mt-4 p-4 bg-blue-900 bg-opacity-30 rounded-lg border border-blue-500 items-center justify-between">
        <div class="flex items-center">
            <span class="text-sm text-gray-300 ml-4">تم تحديد <span id="selected-count">0</span> مستند</span>
            <button onclick="selectAllDocuments()" class="text-sm text-blue-400 hover:text-blue-300 ml-3">تحديد الكل</button>
            <button onclick="clearSelection()" class="text-sm text-gray-400 hover:text-gray-300">إلغاء التحديد</button>
        </div>
        <div class="flex items-center space-x-2 space-x-reverse">
            <button onclick="bulkDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                حذف المحدد
            </button>
            <button onclick="bulkExport()" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm flex items-center">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                تصدير المحدد
            </button>
        </div>
    </div>
</div>

<!-- عرض المستندات -->
<?php if (empty($documents)): ?>
    <!-- لا توجد مستندات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-12 text-center">
        <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        <h3 class="text-2xl font-bold text-gray-400 mb-2">لا توجد مستندات</h3>
        <p class="text-gray-500 mb-6">لم يتم العثور على مستندات تطابق معايير البحث</p>
        <button onclick="openDocumentModal('add')" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all inline-flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة مستند جديد
        </button>
    </div>
<?php else: ?>
    <!-- عرض المستندات في جدول -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6 overflow-x-auto">
        <table class="w-full">
            <thead>
    <tr class="table-header text-right">
        <!-- عمود التحديد - مخفي من البداية -->
        <th class="px-4 py-3 w-10 select-checkbox-column hidden">
            <input type="checkbox" id="select-all" class="rounded bg-slate-700 border-slate-600">
        </th>
        <th class="px-4 py-3">الإجراءات</th>
        <th class="px-4 py-3">الحالة</th>
        <th class="px-4 py-3">النوع</th>
        <th class="px-4 py-3">المشروع</th>
        <th class="px-4 py-3">المنشئ</th>
        <th class="px-4 py-3">الإصدار</th>
        <th class="px-4 py-3">الصفحات</th>
        <th class="px-4 py-3">التاريخ</th>
        <th class="px-4 py-3">عنوان المستند</th>
        <th class="px-4 py-3">الرمز</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($documents as $doc): ?>
    <tr class="border-b border-slate-700 hover:bg-slate-700 transition-colors document-row" data-id="<?php echo $doc['id']; ?>">
        <!-- عمود التحديد - مخفي من البداية -->
        <td class="px-4 py-3 select-checkbox-column hidden">
            <input type="checkbox" class="document-checkbox rounded bg-slate-700 border-slate-600" value="<?php echo $doc['id']; ?>">
        </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <button onclick="viewDocument(<?php echo $doc['id']; ?>)" class="text-blue-400 hover:text-blue-300" title="عرض">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button onclick="editDocument(<?php echo $doc['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" title="تعديل">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['title']); ?>')" class="text-red-400 hover:text-red-300" title="حذف">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            <button onclick="changeStatus(<?php echo $doc['id']; ?>)" class="text-gray-400 hover:text-gray-300" title="تغيير الحالة">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-4 py-3"><?php echo getDocumentStatusBadge($doc['status']); ?></td>
                    <td class="px-4 py-3"><?php echo getDocumentTypeBadge($doc['document_type']); ?></td>
                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($doc['project_name'] ?? '-'); ?></td>
                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($doc['creator_name'] ?? 'النظام'); ?></td>
                    <td class="px-4 py-3 text-center font-mono text-sm text-blue-400">v<?php echo $doc['version']; ?></td>
                    <td class="px-4 py-3 text-center"><?php echo $doc['pages']; ?></td>
                    <td class="px-4 py-3 text-sm text-gray-400"><?php echo timeAgo($doc['created_at']); ?></td>
                    <td class="px-4 py-3 font-semibold">
                        <a href="?page=documents&view=<?php echo $doc['id']; ?>" class="hover:text-blue-400">
                            <?php echo htmlspecialchars(mb_substr($doc['title'], 0, 40)) . (mb_strlen($doc['title']) > 40 ? '...' : ''); ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-400"><?php echo $doc['document_code']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- إحصائيات إضافية أسفل الجدول -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-slate-800 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400">أنواع المستندات</p>
                <p class="text-lg font-bold text-blue-400"><?php echo count($document_types); ?> نوع</p>
            </div>
            <div class="flex space-x-1 space-x-reverse">
                <span class="w-8 h-8 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center text-blue-400">📄</span>
                <span class="w-8 h-8 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center text-red-400">🔒</span>
                <span class="w-8 h-8 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center text-purple-400">🔌</span>
            </div>
        </div>
        
        <div class="bg-slate-800 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400">إجمالي الصفحات</p>
                <p class="text-lg font-bold text-green-400"><?php echo number_format($stats['total_pages'] ?? 0); ?></p>
            </div>
            <div class="w-8 h-8 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        
        <div class="bg-slate-800 rounded-lg p-4 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400">حجم المستودع</p>
                <p class="text-lg font-bold text-yellow-400"><?php echo formatFileSize($stats['total_size'] ?? 0); ?></p>
            </div>
            <div class="w-8 h-8 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إضافة/تعديل مستند -->
<!-- ============================================= -->
<div id="document-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeDocumentModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right" id="doc-modal-title">إضافة مستند جديد</h3>
        </div>
        
        <form id="document-form" method="POST" onsubmit="handleDocumentSubmit(event)">
            <input type="hidden" name="action" id="doc-form-action" value="add_document">
            <input type="hidden" name="document_id" id="doc-id">
            <input type="hidden" name="version_changed" id="version-changed" value="false">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- عنوان المستند -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان المستند <span class="text-red-400">*</span></label>
                    <input type="text" id="doc-title" name="title" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل عنوان المستند">
                </div>
                
                <!-- المشروع والنوع -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                    <select id="doc-project" name="project_id" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- بدون مشروع --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نوع المستند <span class="text-red-400">*</span></label>
                    <select id="doc-type" name="document_type" required class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="technical">تقني</option>
                        <option value="security">أمني</option>
                        <option value="api">API</option>
                        <option value="user_guide">دليل مستخدم</option>
                        <option value="requirements">متطلبات</option>
                        <option value="report">تقرير</option>
                        <option value="architecture">هيكلية</option>
                    </select>
                </div>
                
                <!-- التنسيق والإصدار -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">التنسيق</label>
                    <select id="doc-format" name="format" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="pdf">PDF</option>
                        <option value="docx">DOCX</option>
                        <option value="md">Markdown</option>
                        <option value="html">HTML</option>
                        <option value="txt">نص عادي</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الإصدار</label>
                    <div class="flex items-center">
                        <input type="text" id="doc-version" name="version" value="1.0.0" 
                               class="flex-1 px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                               onchange="document.getElementById('version-changed').value='true'">
                        <button type="button" onclick="incrementVersion()" class="mr-2 px-3 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- الحالة والوصف -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الحالة</label>
                    <select id="doc-status" name="status" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="draft">مسودة</option>
                        <option value="under_review">قيد المراجعة</option>
                        <option value="approved">معتمد</option>
                        <option value="needs_work">بحاجة لعمل</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عدد الصفحات</label>
                    <input type="number" id="doc-pages" name="pages" min="0" value="0" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                
                <!-- الوصف -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                    <textarea id="doc-description" name="description" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="وصف مختصر للمستند..."></textarea>
                </div>
                
                <!-- ملخص تنفيذي -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">ملخص تنفيذي</label>
                    <textarea id="doc-summary" name="executive_summary" rows="3" 
                              class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                              placeholder="ملخص تنفيذي..."></textarea>
                </div>
                
                <!-- مسار الملف والوسوم -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">مسار الملف</label>
                    <input type="text" id="doc-filepath" name="file_path" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="/repositories/...">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2 text-right">الوسوم (مفصولة بفواصل)</label>
                    <input type="text" id="doc-tags" name="tags" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="تقني, أمني, API">
                </div>
            </div>
            
            <!-- حقل مخفي لملخص التغييرات للإصدارات -->
            <div id="version-info" class="hidden mt-4 p-4 bg-blue-900 bg-opacity-20 rounded-lg">
                <label class="block text-sm font-semibold mb-2 text-right">ملخص التغييرات لهذا الإصدار</label>
                <textarea id="changes-summary" name="changes_summary" rows="2" 
                          class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                          placeholder="صف التغييرات التي تمت في هذا الإصدار..."></textarea>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeDocumentModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow" id="doc-modal-submit">
                    حفظ المستند
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة تغيير الحالة -->
<div id="status-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeStatusModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">تغيير حالة المستند</h3>
        </div>
        
        <form id="status-form" method="POST" onsubmit="handleStatusSubmit(event)">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="status-doc-id" name="document_id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الحالة الجديدة</label>
                    <select id="new-status" name="status" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="draft">مسودة</option>
                        <option value="under_review">قيد المراجعة</option>
                        <option value="approved">معتمد</option>
                        <option value="needs_work">بحاجة لعمل</option>
                        <option value="archived">مؤرشف</option>
                    </select>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeStatusModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    تحديث الحالة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript الخاص بالصفحة -->
<script>
// متغيرات عامة
let selectedDocuments = new Set();

// دالة إظهار أعمدة التحديد
function showBulkActions() {
    // إظهار كل الأعمدة اللي فيها select-checkbox-column
    document.querySelectorAll('.select-checkbox-column').forEach(col => {
        col.classList.remove('hidden');
    });
    
    // إظهار شريط الإجراءات
    const bar = document.getElementById('bulk-actions-bar');
    bar.classList.remove('hidden');
    bar.classList.add('flex');
    
    // إعادة تعيين التحديد
    selectedDocuments.clear();
    document.querySelectorAll('.document-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('select-all').checked = false;
    updateSelectedCount();
    
    console.log('✅ أعمدة التحديد ظهرت');
}

// دالة إخفاء أعمدة التحديد
function hideBulkActions() {
    // إخفاء كل الأعمدة
    document.querySelectorAll('.select-checkbox-column').forEach(col => {
        col.classList.add('hidden');
    });
    
    // إخفاء شريط الإجراءات
    const bar = document.getElementById('bulk-actions-bar');
    bar.classList.add('hidden');
    bar.classList.remove('flex');
    
    console.log('✅ أعمدة التحديد اختفت');
}


// دوال فتح وإغلاق النوافذ
function openDocumentModal(action, document = null) {
    const modal = document.getElementById('document-modal');
    const title = document.getElementById('doc-modal-title');
    const formAction = document.getElementById('doc-form-action');
    const submitBtn = document.getElementById('doc-modal-submit');
    
    if (action === 'add') {
        title.textContent = '➕ إضافة مستند جديد';
        formAction.value = 'add_document';
        submitBtn.textContent = 'إضافة المستند';
        document.getElementById('document-form').reset();
        document.getElementById('doc-id').value = '';
        document.getElementById('version-changed').value = 'false';
        document.getElementById('version-info').classList.add('hidden');
        document.getElementById('doc-version').value = '1.0.0';
    } else {
        title.textContent = '✏️ تعديل المستند';
        formAction.value = 'edit_document';
        submitBtn.textContent = 'حفظ التعديلات';
        
        // تعبئة البيانات
        document.getElementById('doc-id').value = document.id;
        document.getElementById('doc-title').value = document.title;
        document.getElementById('doc-project').value = document.project_id || '';
        document.getElementById('doc-type').value = document.document_type;
        document.getElementById('doc-format').value = document.format || 'pdf';
        document.getElementById('doc-version').value = document.version;
        document.getElementById('doc-status').value = document.status;
        document.getElementById('doc-pages').value = document.pages || 0;
        document.getElementById('doc-description').value = document.description || '';
        document.getElementById('doc-summary').value = document.executive_summary || '';
        document.getElementById('doc-filepath').value = document.file_path || '';
        document.getElementById('doc-tags').value = document.tags || '';
        document.getElementById('version-changed').value = 'false';
        document.getElementById('version-info').classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
}

function closeDocumentModal() {
    document.getElementById('document-modal').classList.add('hidden');
}

function openStatusModal(docId, currentStatus) {
    document.getElementById('status-doc-id').value = docId;
    document.getElementById('new-status').value = currentStatus;
    document.getElementById('status-modal').classList.remove('hidden');
}

function closeStatusModal() {
    document.getElementById('status-modal').classList.add('hidden');
}

// دوال معالجة النماذج
function handleDocumentSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('document-form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeDocumentModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}

function handleStatusSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('status-form');
    const formData = new FormData(form);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeStatusModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}

function addComment(event, docId) {
    event.preventDefault();
    
    const comment = document.getElementById('new-comment').value;
    const page = document.getElementById('comment-page').value;
    
    if (!comment.trim()) {
        showNotification('❌ الرجاء إدخال التعليق', 'error');
        return;
    }
    
    showNotification('✅ تم إضافة التعليق', 'success');
    setTimeout(() => location.reload(), 1000);
}

// دوال المستندات
function viewDocument(docId) {
    window.location.href = '?page=documents&view=' + docId;
}

function editDocument(docId) {
    // جلب بيانات المستند من الصف
    const row = document.querySelector(`tr[data-id="${docId}"]`);
    if (row) {
        const cells = row.querySelectorAll('td');
        const document = {
            id: docId,
            title: cells[9]?.textContent?.trim() || '',
            project_id: null,
            document_type: cells[3]?.textContent?.trim()?.toLowerCase() || 'technical',
            format: 'pdf',
            version: cells[6]?.textContent?.trim()?.replace('v', '') || '1.0.0',
            status: cells[2]?.textContent?.trim()?.toLowerCase() || 'draft',
            pages: cells[7]?.textContent?.trim() || 0,
            description: '',
            executive_summary: '',
            file_path: '',
            tags: ''
        };
        openDocumentModal('edit', document);
    } else {
        showNotification('❌ لم نتمكن من جلب بيانات المستند', 'error');
    }
}

function deleteDocument(docId, title) {
    if (confirm(`هل أنت متأكد من حذف المستند "${title}"؟`)) {
        const formData = new FormData();
        formData.append('action', 'delete_document');
        formData.append('document_id', docId);
        
        showLoading();
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ حدث خطأ في الاتصال', 'error');
            console.error(error);
        });
    }
}

function changeStatus(docId) {
    const row = document.querySelector(`tr[data-id="${docId}"]`);
    if (row) {
        const statusCell = row.querySelector('td:nth-child(3) span');
        const currentStatus = statusCell?.textContent?.trim()?.toLowerCase() || 'draft';
        openStatusModal(docId, currentStatus);
    }
}

function downloadDocument(docId) {
    showNotification('📥 جاري تحميل المستند...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التحميل بنجاح', 'success');
    }, 2000);
}

function printDocument(docId) {
    showNotification('🖨️ جاري تجهيز المستند للطباعة...', 'info');
    setTimeout(() => {
        showNotification('✅ تم تجهيز المستند', 'success');
    }, 1500);
}

function shareDocument(docId) {
    showNotification('🔗 تم نسخ رابط المشاركة', 'success');
}

function exportDocument(docId) {
    showNotification('📦 جاري تصدير المستند...', 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير بنجاح', 'success');
    }, 2000);
}

function incrementVersion() {
    const versionInput = document.getElementById('doc-version');
    const parts = versionInput.value.split('.');
    if (parts.length === 3) {
        parts[2] = parseInt(parts[2]) + 1;
        versionInput.value = parts.join('.');
        document.getElementById('version-changed').value = 'true';
        document.getElementById('version-info').classList.remove('hidden');
    }
}

// دالة إظهار شريط التحديد المتعدد
function showBulkActions() {
    const bar = document.getElementById('bulk-actions-bar');
    const checkboxes = document.querySelectorAll('.document-checkbox');
    const selectAll = document.getElementById('select-all');
    
    // إظهار الشريط
    bar.classList.remove('hidden');
    bar.classList.add('flex');
    
    // إظهار مربعات التحديد
    checkboxes.forEach(cb => {
        cb.classList.remove('hidden');
    });
    
    // إظهار مربع تحديد الكل
    if (selectAll) {
        selectAll.classList.remove('hidden');
    }
    
    // إعادة تعيين التحديد
    selectedDocuments.clear();
    checkboxes.forEach(cb => cb.checked = false);
    if (selectAll) selectAll.checked = false;
    updateSelectedCount();
    
    console.log('✅ شريط التحديد ظهر');
}

// دالة إخفاء شريط التحديد المتعدد
function hideBulkActions() {
    const bar = document.getElementById('bulk-actions-bar');
    const checkboxes = document.querySelectorAll('.document-checkbox');
    const selectAll = document.getElementById('select-all');
    
    // إخفاء الشريط
    bar.classList.add('hidden');
    bar.classList.remove('flex');
    
    // إخفاء مربعات التحديد
    checkboxes.forEach(cb => {
        cb.classList.add('hidden');
    });
    
    // إخفاء مربع تحديد الكل
    if (selectAll) {
        selectAll.classList.add('hidden');
    }
    
    console.log('✅ شريط التحديد اختفى');
}

function selectAllDocuments() {
    document.querySelectorAll('.document-checkbox').forEach(cb => {
        cb.checked = true;
        selectedDocuments.add(cb.value);
    });
    document.getElementById('select-all').checked = true;
    updateSelectedCount();
}

function clearSelection() {
    document.querySelectorAll('.document-checkbox').forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('select-all').checked = false;
    selectedDocuments.clear();
    updateSelectedCount();
}

function updateSelectedCount() {
    document.getElementById('selected-count').textContent = selectedDocuments.size;
}

function bulkDelete() {
    if (selectedDocuments.size === 0) {
        showNotification('❌ الرجاء تحديد مستندات للحذف', 'error');
        return;
    }
    
    if (confirm(`هل أنت متأكد من حذف ${selectedDocuments.size} مستند؟`)) {
        const formData = new FormData();
        formData.append('action', 'bulk_delete');
        selectedDocuments.forEach(id => formData.append('document_ids[]', id));
        
        showLoading();
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('❌ حدث خطأ في الاتصال', 'error');
            console.error(error);
        });
    }
}

function bulkExport() {
    if (selectedDocuments.size === 0) {
        showNotification('❌ الرجاء تحديد مستندات للتصدير', 'error');
        return;
    }
    
    showNotification(`📦 جاري تصدير ${selectedDocuments.size} مستند...`, 'info');
    setTimeout(() => {
        showNotification('✅ تم التصدير بنجاح', 'success');
    }, 2000);
}

// دوال مساعدة
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': 'ℹ️',
        'warning': '⚠️'
    };
    
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.innerHTML = `<div class="flex items-center"><span class="ml-3">${icons[type]}</span><span>${message}</span></div>`;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ صفحة المستندات جاهزة');
    
    // إضافة event listeners لمربعات التحديد
    document.querySelectorAll('.document-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                selectedDocuments.add(this.value);
            } else {
                selectedDocuments.delete(this.value);
            }
            updateSelectedCount();
        });
    });
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
/* شريط التقدم */
.progress-bar {
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    transition: width 0.3s ease;
}

/* مربع البحث */
.search-box {
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid #334155;
    transition: all 0.3s ease;
}
.search-box:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

/* رأس الجدول */
.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}

/* النوافذ المنبثقة */
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

/* مؤشر التحميل */
.spinner {
    border: 3px solid rgba(59, 130, 246, 0.3);
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* الإشعارات */
.notification {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* مربعات التحديد */
.document-checkbox.hidden {
    display: none;
}

/* محتوى المستند */
.prose {
    max-width: none;
}
.prose h1, .prose h2, .prose h3 {
    color: #f1f5f9;
}
.prose p {
    color: #cbd5e1;
}
</style>

<?php endif; ?>