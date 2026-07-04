priority<?php
// =============================================
// documentation-unit/pages/dashboard.php
// لوحة التحكم الرئيسية - وحدة التوثيق الفني
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

try {
    // =============================================
    // 1. إحصائيات سريعة
    // =============================================
    
    // إجمالي المشاريع
    $stmt = $db->query("SELECT COUNT(*) FROM documentation_projects WHERE status != 'archived'");
    $total_projects = $stmt->fetchColumn() ?: 0;
    
    // المشاريع النشطة
    $stmt = $db->query("SELECT COUNT(*) FROM documentation_projects WHERE status IN ('in_progress', 'under_analysis')");
    $active_projects = $stmt->fetchColumn() ?: 0;
    
    // إجمالي المستندات
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE status != 'archived'");
    $total_documents = $stmt->fetchColumn() ?: 0;
    
    // المستندات قيد المراجعة
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE status = 'under_review'");
    $pending_reviews = $stmt->fetchColumn() ?: 0;
    
    // المستندات المعتمدة
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE status = 'approved'");
    $approved_documents = $stmt->fetchColumn() ?: 0;
    
    // إجمالي الصفحات
    $stmt = $db->query("SELECT COALESCE(SUM(pages), 0) FROM documents");
    $total_pages = $stmt->fetchColumn() ?: 0;
    
    // إجمالي المراجعات
    $stmt = $db->query("SELECT COUNT(*) FROM document_reviews");
    $total_reviews = $stmt->fetchColumn() ?: 0;
    
    // المراجعات المكتملة
    $stmt = $db->query("SELECT COUNT(*) FROM document_reviews WHERE status = 'completed'");
    $completed_reviews = $stmt->fetchColumn() ?: 0;
    
    // متوسط التقييم
    $stmt = $db->query("SELECT COALESCE(AVG(rating), 0) FROM document_reviews WHERE rating IS NOT NULL");
    $avg_rating = round($stmt->fetchColumn() ?: 0, 1);
    
    // إجمالي ملفات المستودع
    $stmt = $db->query("SELECT COUNT(*) FROM repository_files");
    $total_files = $stmt->fetchColumn() ?: 0;
    
    // حجم المستودع
    $stmt = $db->query("SELECT COALESCE(SUM(file_size), 0) FROM repository_files");
    $repo_size = $stmt->fetchColumn() ?: 0;
    
    // استخدام القوالب
    $stmt = $db->query("SELECT COALESCE(SUM(usage_count), 0) FROM document_templates");
    $template_usage = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. المشاريع النشطة حالياً
    // =============================================
    
    $active_projects_list = $db->query("
        SELECT 
            p.*,
            COUNT(DISTINCT d.id) as documents_count,
            COUNT(DISTINCT CASE WHEN d.status = 'under_review' THEN d.id END) as pending_review_count,
            u.full_name as manager_name
        FROM documentation_projects p
        LEFT JOIN documents d ON p.id = d.project_id
        LEFT JOIN users u ON p.project_manager = u.full_name
        WHERE p.status IN ('in_progress', 'under_analysis')
        GROUP BY p.id
        ORDER BY 
            CASE p.priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            p.deadline ASC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 3. المشاريع عالية الأولوية
    // =============================================
    
    $high_priority_projects = $db->query("
        SELECT 
            p.*,
            COUNT(DISTINCT d.id) as documents_count,
            COUNT(DISTINCT CASE WHEN d.status = 'draft' THEN d.id END) as draft_count,
            DATEDIFF(p.deadline, CURDATE()) as days_remaining
        FROM documentation_projects p
        LEFT JOIN documents d ON p.id = d.project_id
        WHERE p.priority IN ('critical', 'high') 
        AND p.status IN ('new', 'in_progress')
        GROUP BY p.id
        ORDER BY p.deadline ASC
        LIMIT 3
    ")->fetchAll();
    
    // =============================================
    // 4. آخر المستندات المضافة
    // =============================================
    
    $recent_documents = $db->query("
        SELECT 
            d.*,
            p.project_name,
            u.full_name as creator_name,
            TIMESTAMPDIFF(DAY, d.created_at, NOW()) as days_old
        FROM documents d
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        LEFT JOIN users u ON d.created_by = u.id
        ORDER BY d.created_at DESC
        LIMIT 7
    ")->fetchAll();
    
    // =============================================
    // 5. آخر المراجعات
    // =============================================
    
    $recent_reviews = $db->query("
        SELECT 
            r.*,
            d.title as document_title,
            d.document_code,
            p.project_name,
            u_reviewer.full_name as reviewer_name,
            u_creator.full_name as creator_name
        FROM document_reviews r
        LEFT JOIN documents d ON r.document_id = d.id
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        LEFT JOIN users u_reviewer ON r.reviewer_id = u_reviewer.id
        LEFT JOIN users u_creator ON d.created_by = u_creator.id
        WHERE r.status != 'completed'
        ORDER BY 
            CASE r.priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
            END,
            r.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // =============================================
    // 6. إحصائيات المستندات حسب النوع
    // =============================================
    
    $documents_by_type = $db->query("
        SELECT 
            document_type,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
        FROM documents
        GROUP BY document_type
        ORDER BY count DESC
    ")->fetchAll();
    
    // =============================================
    // 7. آخر النشاطات
    // =============================================
    
    $recent_activities = $db->query("
        SELECT 
            a.*,
            u.full_name as user_name
        FROM documentation_activity_log a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 8
    ")->fetchAll();
    
    // =============================================
    // 8. القوالب الأكثر استخداماً
    // =============================================
    
    $popular_templates = $db->query("
        SELECT 
            name,
            usage_count,
            rating
        FROM document_templates
        ORDER BY usage_count DESC
        LIMIT 4
    ")->fetchAll();
    
    // =============================================
    // 9. إحصائيات أسبوعية للرسم البياني
    // =============================================
    
    $weekly_stats = $db->query("
        SELECT 
            stat_date,
            documents_created,
            documents_reviewed,
            documents_approved
        FROM documentation_stats
        WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY stat_date ASC
    ")->fetchAll();
    
    // إذا ما في بيانات، ننشئ بيانات افتراضية
    if (empty($weekly_stats)) {
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $weekly_stats[] = [
                'stat_date' => $date,
                'documents_created' => rand(3, 10),
                'documents_reviewed' => rand(2, 8),
                'documents_approved' => rand(1, 6)
            ];
        }
    }
    
} catch (Exception $e) {
    echo '<div style="background: #ef4444; color: white; padding: 20px; margin: 20px; border-radius: 10px; text-align: right;">';
    echo '<h3 style="font-size: 20px; margin-bottom: 10px;">❌ خطأ في قاعدة البيانات</h3>';
    echo '<p style="background: #7f1d1d; padding: 10px; border-radius: 5px; font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    return;
}

// =============================================
// دوال مساعدة للتنسيق
// =============================================

function getProjectStatusText($status) {
    return match($status) {
        'new' => 'جديد',
        'under_analysis' => 'قيد التحليل',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'on_hold' => 'معلق',
        'cancelled' => 'ملغي',
        'archived' => 'مؤرشف',
        default => $status
    };
}

function getDocumentStatusText($status) {
    return match($status) {
        'draft' => 'مسودة',
        'under_review' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'needs_work' => 'بحاجة لعمل',
        'archived' => 'مؤرشف',
        default => $status
    };
}

function getDocumentTypeText($type) {
    return match($type) {
        'technical' => 'تقني',
        'security' => 'أمني',
        'api' => 'API',
        'user_guide' => 'دليل مستخدم',
        'requirements' => 'متطلبات',
        'report' => 'تقرير',
        'architecture' => 'هيكلية',
        default => $type
    };
}

function getStatusColor($status) {
    return match($status) {
        'new', 'draft' => 'text-blue-400',
        'under_analysis', 'under_review', 'in_progress' => 'text-yellow-400',
        'completed', 'approved' => 'text-green-400',
        'on_hold' => 'text-orange-400',
        'cancelled', 'rejected', 'needs_work' => 'text-red-400',
        'archived' => 'text-gray-400',
        default => 'text-gray-400'
    };
}

function getPriorityColor($priority) {
    return match($priority) {
        'critical' => 'text-red-400 bg-red-600 bg-opacity-20',
        'high' => 'text-orange-400 bg-orange-600 bg-opacity-20',
        'medium' => 'text-blue-400 bg-blue-600 bg-opacity-20',
        'low' => 'text-gray-400 bg-gray-600 bg-opacity-20',
        default => 'text-gray-400 bg-gray-600 bg-opacity-20'
    };
}

function getTypeClass($type) {
    return match($type) {
        'technical' => 'type-technical',
        'security' => 'type-security',
        'api' => 'type-api',
        'user_guide' => 'type-user-guide',
        'requirements' => 'type-requirements',
        'report' => 'type-report',
        default => 'type-technical'
    };
}



?>

<!-- ============================================= -->
<!-- إحصائيات سريعة - 8 بطاقات (صفين) -->
<!-- ============================================= -->

<!-- الصف الأول من الإحصائيات -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    
    <!-- المشاريع النشطة -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">المشاريع النشطة</p>
                <p class="text-3xl font-bold text-purple-400"><?php echo $active_projects; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-green-500"></span>
            <span class="text-green-400 mr-2"><?php echo $total_projects; ?> إجمالي المشاريع</span>
        </div>
    </div>

    <!-- المستندات قيد المراجعة -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">مستندات قيد المراجعة</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $pending_reviews; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-yellow-500"></span>
            <span class="text-yellow-400 mr-2">تحتاج للمراجعة الفورية</span>
        </div>
    </div>

    <!-- المستندات المعتمدة -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">المستندات المعتمدة</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $approved_documents; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-blue-500"></span>
            <span class="text-blue-400 mr-2">من إجمالي <?php echo $total_documents; ?> مستند</span>
        </div>
    </div>

    <!-- إجمالي الصفحات -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">إجمالي الصفحات</p>
                <p class="text-3xl font-bold text-cyan-400"><?php echo number_format($total_pages); ?></p>
            </div>
            <div class="w-12 h-12 bg-cyan-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <div class="progress-bar">
                <?php $pages_percent = min(100, round(($total_pages / 1000) * 100)); ?>
                <div class="progress-fill" style="width: <?php echo $pages_percent; ?>%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-2 text-left">متوسط <?php echo round($total_pages / max($total_documents, 1)); ?> صفحة/مستند</p>
        </div>
    </div>
</div>

<!-- الصف الثاني من الإحصائيات -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- إجمالي المراجعات -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">إجمالي المراجعات</p>
                <p class="text-3xl font-bold text-orange-400"><?php echo $total_reviews; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-green-500"></span>
            <span class="text-green-400 mr-2"><?php echo $completed_reviews; ?> مراجعة مكتملة</span>
        </div>
    </div>

    <!-- متوسط التقييم -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">متوسط التقييم</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $avg_rating; ?> ⭐</p>
            </div>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <div class="flex space-x-1 space-x-reverse">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg class="w-4 h-4 <?php echo $i <= round($avg_rating) ? 'text-yellow-400' : 'text-gray-600'; ?>" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- ملفات المستودع -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">ملفات المستودع</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $total_files; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-400 mr-2">الحجم: <?php echo formatFileSize($repo_size); ?></span>
        </div>
    </div>

    <!-- استخدام القوالب -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">استخدام القوالب</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $template_usage; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="text-gray-400 mr-2">مرة استخدام للقوالب</span>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- المشاريع النشطة والمستندات الحديثة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- المشاريع النشطة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">المشاريع النشطة</h3>
            <button onclick="navigateTo('projects')" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</button>
        </div>
        
        <?php if (empty($active_projects_list)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد مشاريع نشطة حالياً</p>
        </div>
        <?php else: ?>
            <?php foreach ($active_projects_list as $project): ?>
            <div class="cyber-border bg-slate-900 rounded-lg p-4 mb-4 last:mb-0 hover:bg-slate-800 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-400"><?php echo $project['project_code']; ?></span>
                    <div class="flex items-center">
                        <span class="text-xs px-2 py-1 <?php echo getPriorityColor($project['priority']); ?> rounded-full ml-2">
                            <?php echo $project['priority']; ?>
                        </span>
                        <span class="text-xs <?php echo getStatusColor($project['status']); ?>">
                            <?php echo getProjectStatusText($project['status']); ?>
                        </span>
                    </div>
                </div>
                
                <h4 class="font-bold mb-1"><?php echo htmlspecialchars($project['project_name']); ?></h4>
                <p class="text-xs text-gray-400 mb-3">العميل: <?php echo htmlspecialchars($project['client_name'] ?? 'غير محدد'); ?></p>
                
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="text-gray-400">المستندات: <span class="text-blue-400"><?php echo $project['documents_count']; ?></span></span>
                    <span class="text-gray-400">قيد المراجعة: <span class="text-yellow-400"><?php echo $project['pending_review_count']; ?></span></span>
                    <span class="text-gray-400">المسؤول: <?php echo htmlspecialchars($project['manager_name'] ?? 'غير محدد'); ?></span>
                </div>
                
                <div class="progress-bar mt-2">
                    <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
                </div>
                
                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs text-gray-400">تقدم: <?php echo $project['progress']; ?>%</span>
                    <button onclick="viewProject(<?php echo $project['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300">
                        عرض التفاصيل
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- المستندات الحديثة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">آخر المستندات المضافة</h3>
            <button onclick="navigateTo('documents')" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</button>
        </div>
        
        <?php if (empty($recent_documents)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد مستندات حديثة</p>
        </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_documents as $doc): ?>
                <div class="p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer" onclick="viewDocument(<?php echo $doc['id']; ?>)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <span class="w-2 h-2 rounded-full <?php echo $doc['status'] == 'approved' ? 'bg-green-500' : ($doc['status'] == 'under_review' ? 'bg-yellow-500' : 'bg-blue-500'); ?> ml-2"></span>
                            <span class="font-semibold text-sm"><?php echo htmlspecialchars(mb_substr($doc['title'], 0, 30)) . (mb_strlen($doc['title']) > 30 ? '...' : ''); ?></span>
                        </div>
                        <span class="text-xs px-2 py-1 <?php echo getTypeClass($doc['document_type']); ?> rounded-full">
                            <?php echo getDocumentTypeText($doc['document_type']); ?>
                        </span>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2"><?php echo htmlspecialchars($doc['project_name'] ?? 'غير محدد'); ?></p>
                    
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>👤 <?php echo htmlspecialchars($doc['creator_name'] ?? 'النظام'); ?></span>
                        <span>📄 الصفحات: <?php echo $doc['pages']; ?></span>
                        <span>⏱️ <?php echo timeAgo($doc['created_at']); ?></span>
                    </div>
                    
                    <div class="flex items-center justify-end mt-2 space-x-2 space-x-reverse">
                        <span class="text-xs <?php echo getStatusColor($doc['status']); ?>">
                            ● <?php echo getDocumentStatusText($doc['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني والمشاريع عالية الأولوية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- الرسم البياني للنشاط الأسبوعي -->
    <div class="lg:col-span-2 cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">النشاط الأسبوعي</h3>
            <div class="flex items-center space-x-2 space-x-reverse">
                <span class="text-sm text-gray-400">آخر 7 أيام</span>
                <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
            </div>
        </div>
        <!-- حاوية الرسم البياني بحجم ثابت -->
        <div style="position: relative; width: 100%; height: 250px;">
            <canvas id="weeklyActivityChart" style="display: block; width: 100%; height: 100%;"></canvas>
        </div>
    </div>

    <!-- المشاريع عالية الأولوية -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold text-right mb-6">مشاريع عالية الأولوية</h3>
        
        <?php if (empty($high_priority_projects)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد مشاريع عالية الأولوية</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($high_priority_projects as $project): ?>
                <div class="p-4 <?php echo $project['priority'] == 'critical' ? 'bg-red-900 bg-opacity-20' : 'bg-orange-900 bg-opacity-20'; ?> rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($project['project_name']); ?></p>
                        <span class="text-xs <?php echo $project['priority'] == 'critical' ? 'text-red-400' : 'text-orange-400'; ?>">
                            <?php echo $project['priority'] == 'critical' ? 'حرج' : 'عالي'; ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mb-2">العميل: <?php echo htmlspecialchars($project['client_name'] ?? 'غير محدد'); ?></p>
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="text-gray-400">المستندات: <span class="text-blue-400"><?php echo $project['documents_count']; ?></span></span>
                        <span class="text-gray-400">مسودة: <span class="text-yellow-400"><?php echo $project['draft_count']; ?></span></span>
                        <span class="text-gray-400 <?php echo $project['days_remaining'] < 0 ? 'text-red-400' : ''; ?>">
                            <?php echo $project['days_remaining'] > 0 ? $project['days_remaining'] . ' يوم' : 'م تأخر'; ?>
                        </span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill" style="width: <?php echo $project['progress']; ?>%"></div>
                    </div>
                    <div class="flex items-center justify-end mt-2">
                        <button onclick="viewProject(<?php echo $project['id']; ?>)" class="text-xs text-blue-400 hover:text-blue-300">
                            عرض المشروع
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- المراجعات المعلقة وآخر النشاطات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- المراجعات المعلقة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">المراجعات المعلقة</h3>
            <button onclick="navigateTo('review')" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</button>
        </div>
        
        <?php if (empty($recent_reviews)): ?>
        <div class="text-center py-8">
            <p class="text-green-400">لا توجد مراجعات معلقة ✓</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recent_reviews as $review): ?>
                <div class="p-4 bg-slate-900 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full ml-2 blink"></div>
                            <p class="font-semibold text-sm"><?php echo htmlspecialchars(mb_substr($review['document_title'], 0, 25)) . '...'; ?></p>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo $review['document_code']; ?></span>
                    </div>
                    
                    <p class="text-xs text-gray-400 mb-2">المشروع: <?php echo htmlspecialchars($review['project_name'] ?? 'غير محدد'); ?></p>
                    
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span>المراجع: <?php echo htmlspecialchars($review['reviewer_name'] ?? 'غير معين'); ?></span>
                        <span>الكاتب: <?php echo htmlspecialchars($review['creator_name'] ?? 'غير معين'); ?></span>
                        <span>منذ <?php echo timeAgo($review['created_at']); ?></span>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-2 space-x-reverse">
                        <button onclick="startReview(<?php echo $review['document_id']; ?>)" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-xs">
                            بدء المراجعة
                        </button>
                        <button onclick="viewDocument(<?php echo $review['document_id']; ?>)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-xs">
                            عرض
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- آخر النشاطات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-right">آخر النشاطات</h3>
            <button onclick="navigateTo('history')" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</button>
        </div>
        
        <?php if (empty($recent_activities)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد نشاطات حديثة</p>
        </div>
        <?php else: ?>
            <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-custom pl-2">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="flex items-start border-r-2 border-blue-500 pr-4 py-2">
                    <div class="ml-3 mt-1">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    </div>
                    <div class="flex-1 text-right">
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($activity['description']); ?></p>
                        <div class="flex items-center justify-end text-xs text-gray-500 mt-1">
                            <span><?php echo htmlspecialchars($activity['user_name'] ?? 'النظام'); ?></span>
                            <span class="mx-2">•</span>
                            <span><?php echo timeAgo($activity['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- القوالب الأكثر استخداماً وإحصائيات أنواع المستندات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- القوالب الأكثر استخداماً -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-6 text-right">القوالب الأكثر استخداماً</h3>
        
        <?php if (empty($popular_templates)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد قوالب متاحة</p>
        </div>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($popular_templates as $template): ?>
                <div class="tool-btn p-4 rounded-lg text-center hover:bg-slate-700 transition-all cursor-pointer" onclick="useTemplateByName('<?php echo htmlspecialchars($template['name']); ?>')">
                    <svg class="w-8 h-8 text-blue-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($template['name']); ?></span>
                    <p class="text-xs text-gray-400 mt-1">مستخدم <?php echo $template['usage_count']; ?> مرة</p>
                    <div class="flex items-center justify-center mt-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg class="w-3 h-3 <?php echo $i <= round($template['rating']) ? 'text-yellow-400' : 'text-gray-600'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- إحصائيات أنواع المستندات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-6 text-right">أنواع المستندات</h3>
        
        <?php if (empty($documents_by_type)): ?>
        <div class="text-center py-8">
            <p class="text-gray-400">لا توجد مستندات</p>
        </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($documents_by_type as $stat): 
                    $percentage = $stat['count'] > 0 ? round(($stat['approved_count'] / $stat['count']) * 100, 1) : 0;
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-400"><?php echo getDocumentTypeText($stat['document_type']); ?></span>
                        <span class="text-sm text-blue-400"><?php echo $stat['count']; ?> مستند</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-500">معتمد: <?php echo $stat['approved_count']; ?></span>
                        <span class="text-xs text-gray-500">نسبة الاعتماد: <?php echo $percentage; ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- أدوات التوثيق السريع -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- إنشاء مستند جديد -->
    <div class="tool-btn p-6 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-slate-700 transition-all" onclick="createNewDocument()">
        <svg class="w-12 h-12 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="text-lg font-semibold">مستند جديد</span>
        <span class="text-xs text-gray-400 mt-2">ابدأ مستنداً من الصفر</span>
    </div>
    
    <!-- استخدام قالب -->
    <div class="tool-btn p-6 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-slate-700 transition-all" onclick="useTemplate()">
        <svg class="w-12 h-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
        </svg>
        <span class="text-lg font-semibold">استخدام قالب</span>
        <span class="text-xs text-gray-400 mt-2">اختر من القوالب الجاهزة</span>
    </div>
    
    <!-- رفع ملفات -->
    <div class="tool-btn p-6 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-slate-700 transition-all" onclick="navigateTo('repository')">
        <svg class="w-12 h-12 text-yellow-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
        <span class="text-lg font-semibold">رفع ملفات</span>
        <span class="text-xs text-gray-400 mt-2">إضافة ملفات للمستودع</span>
    </div>
    
    <!-- تقرير جديد -->
    <div class="tool-btn p-6 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-slate-700 transition-all" onclick="navigateTo('reports')">
        <svg class="w-12 h-12 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span class="text-lg font-semibold">تقرير جديد</span>
        <span class="text-xs text-gray-400 mt-2">إنشاء تقرير احترافي</span>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript خاص بالصفحة -->
<!-- ============================================= -->
<script>
// بيانات الرسم البياني
const weeklyData = <?php echo json_encode($weekly_stats); ?>;

// تهيئة الرسم البياني
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weeklyActivityChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: weeklyData.map(d => {
                const date = new Date(d.stat_date);
                return date.toLocaleDateString('ar-EG', { weekday: 'short' });
            }),
            datasets: [
                {
                    label: 'مستندات جديدة',
                    data: weeklyData.map(d => d.documents_created || 0),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'مستندات مراجعة',
                    data: weeklyData.map(d => d.documents_reviewed || 0),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'مستندات معتمدة',
                    data: weeklyData.map(d => d.documents_approved || 0),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    rtl: true,
                    labels: {
                        color: '#f1f5f9',
                        font: { family: 'Cairo', size: 11 },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    rtl: true,
                    backgroundColor: '#1e293b',
                    titleColor: '#f1f5f9',
                    bodyColor: '#94a3b8',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    padding: 8,
                    cornerRadius: 6,
                    titleFont: { family: 'Cairo', size: 12, weight: 'bold' },
                    bodyFont: { family: 'Cairo', size: 11 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: '#94a3b8', font: { family: 'Cairo', size: 10 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { family: 'Cairo', size: 10 } }
                }
            },
            layout: { padding: { top: 10, bottom: 10 } },
            animation: { duration: 800 }
        }
    });
});

// دوال التنقل
function navigateTo(page) {
    window.location.href = '?page=' + page;
}

function viewProject(projectId) {
    window.location.href = '?page=projects&view=' + projectId;
}

function viewDocument(docId) {
    window.location.href = '?page=documents&view=' + docId;
}

function startReview(docId) {
    window.location.href = '?page=review&doc=' + docId;
}

function createNewDocument() {
    window.location.href = '?page=creation';
}

function useTemplate() {
    window.location.href = '?page=templates';
}

function useTemplateByName(templateName) {
    showNotification('📋 تم اختيار القالب: ' + templateName, 'info');
    setTimeout(() => {
        window.location.href = '?page=creation&template=' + encodeURIComponent(templateName);
    }, 1000);
}

// دوال الإشعارات
function showNotification(message, type = 'info') {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm mb-2`;
    notification.innerHTML = `<div class="flex items-center"><span class="ml-3">${type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️'}</span><span>${message}</span></div>`;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// تحديث تلقائي للبيانات (كل دقيقة)
setInterval(function() {
    if (document.visibilityState === 'visible') {
        console.log('🔄 تحديث البيانات...');
        // هنا ممكن نضيف تحديث للبيانات عبر AJAX
    }
}, 60000);
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

/* أزرار الأدوات */
.tool-btn {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border: 1px solid rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}
.tool-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    border-color: #3b82f6;
}

/* مؤشر الحالة */
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* تأثير الوميض */
.blink {
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* أنواع المستندات */
.type-technical { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.type-security { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.type-api { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; }
.type-user-guide { background: rgba(16, 185, 129, 0.2); color: #10b981; }
.type-requirements { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.type-report { background: rgba(236, 72, 153, 0.2); color: #ec4899; }

/* شريط التمرير المخصص */
.scrollbar-custom::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-custom::-webkit-scrollbar-track {
    background: #1e293b;
}
.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 3px;
}
.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: #2563eb;
}
</style>