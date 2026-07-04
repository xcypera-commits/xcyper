<?php
// =============================================
// documentation-unit/pages/updates.php
// صفحة تحديثات وإصدارات المستندات
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
            case 'create_update':
                // إنشاء تحديث جديد
                $sql = "INSERT INTO document_updates (
                    document_id, update_type, old_version, new_version,
                    changes_summary, detailed_changes, created_by, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['document_id'],
                    $_POST['update_type'],
                    $_POST['old_version'],
                    $_POST['new_version'],
                    $_POST['changes_summary'],
                    $_POST['detailed_changes'],
                    $_SESSION['user_id'] ?? 1
                ]);
                
                // تحديث إصدار المستند
                $update_doc = $db->prepare("UPDATE documents SET version = ? WHERE id = ?");
                $update_doc->execute([$_POST['new_version'], $_POST['document_id']]);
                
                logActivity($db, 'create', 'update', $db->lastInsertId(), 'إنشاء تحديث جديد للمستند');
                
                $response['success'] = true;
                $response['message'] = '✅ تم إنشاء التحديث بنجاح';
                break;
                
            case 'apply_update':
                // تطبيق تحديث
                $sql = "UPDATE document_updates SET status = 'applied', applied_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['update_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم تطبيق التحديث';
                break;
                
            case 'rollback_update':
                // التراجع عن تحديث
                $sql = "UPDATE document_updates SET status = 'rolled_back' WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_POST['update_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم التراجع عن التحديث';
                break;
                
            case 'delete_update':
                // حذف تحديث
                $db->prepare("DELETE FROM document_updates WHERE id = ?")->execute([$_POST['update_id']]);
                $response['success'] = true;
                $response['message'] = '✅ تم حذف التحديث';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // الفلاتر
    $status_filter = $_GET['status'] ?? '';
    $type_filter = $_GET['type'] ?? '';
    $doc_filter = $_GET['document'] ?? '';
    $date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['to'] ?? date('Y-m-d');
    
    // جلب التحديثات
    $sql = "
        SELECT u.*, 
               d.title as document_title,
               d.document_code,
               p.project_name,
               creator.full_name as creator_name,
               reviewer.full_name as reviewer_name
        FROM document_updates u
        LEFT JOIN documents d ON u.document_id = d.id
        LEFT JOIN documentation_projects p ON d.project_id = p.id
        LEFT JOIN users creator ON u.created_by = creator.id
        LEFT JOIN users reviewer ON u.reviewed_by = reviewer.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status_filter) {
        $sql .= " AND u.status = ?";
        $params[] = $status_filter;
    }
    
    if ($type_filter) {
        $sql .= " AND u.update_type = ?";
        $params[] = $type_filter;
    }
    
    if ($doc_filter) {
        $sql .= " AND u.document_id = ?";
        $params[] = $doc_filter;
    }
    
    if ($date_from && $date_to) {
        $sql .= " AND DATE(u.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY u.created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $updates = $stmt->fetchAll();
    
    // إحصائيات التحديثات
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied,
            SUM(CASE WHEN status = 'rolled_back' THEN 1 ELSE 0 END) as rolled_back,
            SUM(CASE WHEN update_type = 'major' THEN 1 ELSE 0 END) as major,
            SUM(CASE WHEN update_type = 'minor' THEN 1 ELSE 0 END) as minor,
            SUM(CASE WHEN update_type = 'critical' THEN 1 ELSE 0 END) as critical,
            SUM(CASE WHEN update_type = 'security' THEN 1 ELSE 0 END) as security
        FROM document_updates
    ")->fetch();
    
    // إحصائيات إضافية للرسوم البيانية
    $monthly_stats = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'applied' THEN 1 ELSE 0 END) as applied
        FROM document_updates
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
    
    // قائمة المستندات للفلتر
    $documents = $db->query("
        SELECT id, title, document_code 
        FROM documents 
        WHERE status != 'archived'
        ORDER BY title
        LIMIT 50
    ")->fetchAll();
    
    // آخر 5 تحديثات
    $recent_updates = $db->query("
        SELECT u.*, d.title as document_title
        FROM document_updates u
        LEFT JOIN documents d ON u.document_id = d.id
        ORDER BY u.created_at DESC
        LIMIT 5
    ")->fetchAll();
    
    // إحصائيات المستندات الأكثر تحديثاً
    $top_documents = $db->query("
        SELECT d.id, d.title, d.document_code, COUNT(u.id) as update_count
        FROM documents d
        LEFT JOIN document_updates u ON d.id = u.document_id
        GROUP BY d.id
        ORDER BY update_count DESC
        LIMIT 5
    ")->fetchAll();
    
} catch (Exception $e) {
    $updates = [];
    $recent_updates = [];
    $top_documents = [];
    $monthly_stats = [];
    $documents = [];
    $stats = [
        'total' => 0,
        'pending' => 0,
        'applied' => 0,
        'rolled_back' => 0,
        'major' => 0,
        'minor' => 0,
        'critical' => 0,
        'security' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// دوال مساعدة
function getUpdateTypeBadge($type) {
    $classes = [
        'major' => 'bg-purple-600 bg-opacity-20 text-purple-400',
        'minor' => 'bg-blue-600 bg-opacity-20 text-blue-400',
        'critical' => 'bg-red-600 bg-opacity-20 text-red-400',
        'security' => 'bg-orange-600 bg-opacity-20 text-orange-400',
        'bugfix' => 'bg-green-600 bg-opacity-20 text-green-400'
    ];
    
    $texts = [
        'major' => 'رئيسي',
        'minor' => 'ثانوي',
        'critical' => 'حرج',
        'security' => 'أمني',
        'bugfix' => 'إصلاح'
    ];
    
    $class = $classes[$type] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$type] ?? $type;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}

function getUpdateStatusBadge($status) {
    $classes = [
        'pending' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'applied' => 'bg-green-600 bg-opacity-20 text-green-400',
        'rolled_back' => 'bg-red-600 bg-opacity-20 text-red-400',
        'failed' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'pending' => 'معلق',
        'applied' => 'مطبق',
        'rolled_back' => 'متراجع',
        'failed' => 'فشل'
    ];
    
    $class = $classes[$status] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
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
<!-- رأس الصفحة -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-2xl p-8 mb-8 cyber-border">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 space-x-reverse">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center">
                <span class="text-3xl text-white">🔄</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">تحديثات التوثيق</h1>
                <p class="text-gray-400 mt-1">سجل الإصدارات والتغييرات على المستندات</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3 space-x-reverse">
            <button onclick="openCreateUpdateModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-semibold transition-all flex items-center">
                <span class="ml-2">+</span>
                تحديث جديد
            </button>
        </div>
    </div>
    
    <!-- شريط الفلاتر -->
    <div class="mt-6 flex flex-wrap items-center gap-3 bg-slate-900 rounded-xl p-4">
        <div class="flex-1 relative">
            <input type="text" id="search-input" placeholder="بحث في التحديثات..." 
                   class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
        </div>
        
        <select id="filter-status" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            <option value="">جميع الحالات</option>
            <option value="pending">معلق</option>
            <option value="applied">مطبق</option>
            <option value="rolled_back">متراجع</option>
        </select>
        
        <select id="filter-type" onchange="applyFilters()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm focus:outline-none focus:border-blue-500">
            <option value="">جميع الأنواع</option>
            <option value="major">رئيسي</option>
            <option value="minor">ثانوي</option>
            <option value="critical">حرج</option>
            <option value="security">أمني</option>
        </select>
        
        <button onclick="resetFilters()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
            إعادة تعيين
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">إجمالي التحديثات</p>
                <p class="text-2xl font-bold text-blue-400"><?php echo $stats['total']; ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-blue-400">📊</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">تحديثات معلقة</p>
                <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['pending']; ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-yellow-400">⏳</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">تحديثات مطبقة</p>
                <p class="text-2xl font-bold text-green-400"><?php echo $stats['applied']; ?></p>
            </div>
            <div class="w-10 h-10 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-green-400">✓</span>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 mb-1">تحديثات حرجة</p>
                <p class="text-2xl font-bold text-red-400"><?php echo $stats['critical']; ?></p>
            </div>
            <div class="w-10 h-10 bg-red-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <span class="text-xl text-red-400">!</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الرسم البياني للتحديثات -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- رسم بياني شهري -->
    <div class="lg:col-span-2 bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">النشاط الشهري للتحديثات</h3>
        <div style="position: relative; width: 100%; height: 250px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    
    <!-- إحصائيات إضافية -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">توزيع التحديثات</h3>
        
        <div class="space-y-4">
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">رئيسي</span>
                    <span class="text-purple-400"><?php echo $stats['major']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-purple-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['major'] / $stats['total']) * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">ثانوي</span>
                    <span class="text-blue-400"><?php echo $stats['minor']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-blue-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['minor'] / $stats['total']) * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">حرج</span>
                    <span class="text-red-400"><?php echo $stats['critical']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-red-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['critical'] / $stats['total']) * 100) : 0; ?>%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-400">أمني</span>
                    <span class="text-orange-400"><?php echo $stats['security']; ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill bg-orange-500" style="width: <?php echo $stats['total'] > 0 ? round(($stats['security'] / $stats['total']) * 100) : 0; ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- المستندات الأكثر تحديثاً -->
        <?php if (!empty($top_documents)): ?>
        <div class="mt-6 pt-6 border-t border-slate-700">
            <h4 class="font-semibold mb-3">الأكثر تحديثاً</h4>
            
            <div class="space-y-2">
                <?php foreach ($top_documents as $doc): ?>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400"><?php echo htmlspecialchars($doc['title']); ?></span>
                    <span class="bg-blue-600 bg-opacity-20 text-blue-400 px-2 py-1 rounded-full text-xs">
                        <?php echo $doc['update_count']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة التحديثات -->
<!-- ============================================= -->
<div class="bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold">سجل التحديثات</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <span class="text-sm text-gray-400"><?php echo count($updates); ?> تحديث</span>
        </div>
    </div>
    
    <?php if (empty($updates)): ?>
        <div class="text-center py-12">
            <div class="text-5xl text-gray-600 mb-4">📭</div>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد تحديثات</h3>
            <p class="text-gray-500">لم يتم تسجيل أي تحديثات بعد</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($updates as $update): ?>
            <div class="bg-slate-900 rounded-xl p-5 hover:bg-slate-800 transition-colors">
                
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <?php echo getUpdateTypeBadge($update['update_type']); ?>
                        <?php echo getUpdateStatusBadge($update['status']); ?>
                    </div>
                    <span class="text-sm text-gray-400">#<?php echo $update['id']; ?></span>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <!-- معلومات المستند -->
                    <div class="lg:col-span-2">
                        <h4 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($update['document_title']); ?></h4>
                        <p class="text-sm text-gray-400"><?php echo $update['document_code']; ?></p>
                    </div>
                    
                    <!-- معلومات الإصدار -->
                    <div>
                        <div class="flex items-center space-x-2 space-x-reverse bg-slate-800 rounded-lg p-2">
                            <span class="text-sm text-gray-400">من:</span>
                            <span class="font-mono text-red-400"><?php echo $update['old_version']; ?></span>
                            <span class="text-gray-600">→</span>
                            <span class="font-mono text-green-400"><?php echo $update['new_version']; ?></span>
                        </div>
                    </div>
                    
                    <!-- معلومات إضافية -->
                    <div class="text-left">
                        <p class="text-sm text-gray-400">بواسطة: <?php echo htmlspecialchars($update['creator_name'] ?? 'النظام'); ?></p>
                        <p class="text-xs text-gray-500"><?php echo timeAgo($update['created_at']); ?></p>
                    </div>
                </div>
                
                <!-- ملخص التغييرات -->
                <?php if ($update['changes_summary']): ?>
                <div class="mt-3 p-3 bg-slate-800 rounded-lg">
                    <p class="text-sm text-gray-300"><?php echo nl2br(htmlspecialchars($update['changes_summary'])); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- أزرار الإجراءات -->
                <div class="flex items-center justify-end space-x-2 space-x-reverse mt-4 pt-3 border-t border-slate-700">
                    <?php if ($update['status'] == 'pending'): ?>
                    <button onclick="applyUpdate(<?php echo $update['id']; ?>)" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded-lg text-xs">
                        تطبيق
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($update['status'] == 'applied'): ?>
                    <button onclick="rollbackUpdate(<?php echo $update['id']; ?>)" class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-xs">
                        تراجع
                    </button>
                    <?php endif; ?>
                    
                    <button onclick="viewDocument(<?php echo $update['document_id']; ?>)" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-lg text-xs">
                        عرض
                    </button>
                    
                    <button onclick="deleteUpdate(<?php echo $update['id']; ?>)" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded-lg text-xs">
                        حذف
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- آخر التحديثات -->
<!-- ============================================= -->
<?php if (!empty($recent_updates)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
    
    <!-- آخر 5 تحديثات -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">آخر التحديثات</h3>
        
        <div class="space-y-3">
            <?php foreach ($recent_updates as $update): ?>
            <div class="flex items-center justify-between p-3 bg-slate-700 rounded-lg hover:bg-slate-600 transition-colors cursor-pointer"
                 onclick="viewDocument(<?php echo $update['document_id']; ?>)">
                <span class="text-xs <?php echo $update['status'] == 'applied' ? 'text-green-400' : 'text-yellow-400'; ?>">
                    <?php echo $update['status'] == 'applied' ? '✓' : '⏳'; ?>
                </span>
                <div class="flex-1 mr-3">
                    <p class="text-sm font-semibold"><?php echo htmlspecialchars($update['document_title']); ?></p>
                    <p class="text-xs text-gray-400"><?php echo timeAgo($update['created_at']); ?></p>
                </div>
                <span class="text-xs bg-slate-600 px-2 py-1 rounded-full">
                    <?php echo $update['new_version']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- تقويم بسيط -->
    <div class="bg-slate-800 rounded-xl p-6">
        <h3 class="text-lg font-bold mb-4">أيام النشاط</h3>
        
        <div class="grid grid-cols-7 gap-1 text-center mb-2">
            <span class="text-xs text-gray-400">س</span>
            <span class="text-xs text-gray-400">ن</span>
            <span class="text-xs text-gray-400">ث</span>
            <span class="text-xs text-gray-400">ر</span>
            <span class="text-xs text-gray-400">خ</span>
            <span class="text-xs text-gray-400">ج</span>
            <span class="text-xs text-gray-400">س</span>
        </div>
        
        <div class="grid grid-cols-7 gap-1">
            <?php
            $today = date('Y-m-d');
            $first_day = date('Y-m-01');
            $start_offset = date('w', strtotime($first_day));
            
            for ($i = 0; $i < $start_offset; $i++) {
                echo '<div class="aspect-square bg-slate-900 rounded-lg opacity-30"></div>';
            }
            
            for ($day = 1; $day <= date('t'); $day++) {
                $date = date('Y-m') . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $has_update = false;
                
                foreach ($updates as $update) {
                    if (substr($update['created_at'], 0, 10) == $date) {
                        $has_update = true;
                        break;
                    }
                }
                
                $bg_class = $has_update ? 'bg-green-600 bg-opacity-30' : 'bg-slate-900';
                $text_class = ($date == $today) ? 'text-yellow-400 font-bold' : 'text-gray-400';
                
                echo "<div class='aspect-square $bg_class rounded-lg flex items-center justify-center text-sm $text_class'>$day</div>";
            }
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================= -->
<!-- نافذة إنشاء تحديث جديد -->
<!-- ============================================= -->
<div id="create-update-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeCreateUpdateModal()" class="text-gray-400 hover:text-white">
                ✕
            </button>
            <h3 class="text-xl font-bold text-right">إنشاء تحديث جديد</h3>
        </div>
        
        <form id="create-update-form" onsubmit="handleCreateUpdate(event)">
            <input type="hidden" name="action" value="create_update">
            
            <div class="space-y-4">
                <!-- اختيار المستند -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المستند</label>
                    <select name="document_id" required class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">اختر المستند</option>
                        <?php foreach ($documents as $doc): ?>
                        <option value="<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- نوع التحديث والإصدارات -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">النوع</label>
                        <select name="update_type" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                            <option value="minor">ثانوي</option>
                            <option value="major">رئيسي</option>
                            <option value="critical">حرج</option>
                            <option value="security">أمني</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار القديم</label>
                        <input type="text" name="old_version" value="1.0.0" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار الجديد</label>
                        <input type="text" name="new_version" value="1.1.0" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                    </div>
                </div>
                
                <!-- ملخص التغييرات -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">ملخص التغييرات</label>
                    <input type="text" name="changes_summary" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg">
                </div>
                
                <!-- تفاصيل التغييرات -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تفاصيل التغييرات</label>
                    <textarea name="detailed_changes" rows="4" class="w-full px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg"></textarea>
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeCreateUpdateModal()" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg text-sm">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                    إنشاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// بيانات الرسم البياني
const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;

// تهيئة الرسم البياني
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'إجمالي التحديثات',
                    data: monthlyData.map(d => d.total),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'التحديثات المطبقة',
                    data: monthlyData.map(d => d.applied),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
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
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
});

// دوال التحديثات
function openCreateUpdateModal() {
    document.getElementById('create-update-modal').classList.remove('hidden');
}

function closeCreateUpdateModal() {
    document.getElementById('create-update-modal').classList.add('hidden');
    document.getElementById('create-update-form').reset();
}

function handleCreateUpdate(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('create-update-form'));
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        closeCreateUpdateModal();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(() => {
        hideLoading();
        showNotification('❌ حدث خطأ', 'error');
    });
}

function applyUpdate(id) {
    if (!confirm('تطبيق هذا التحديث؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'apply_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function rollbackUpdate(id) {
    if (!confirm('التراجع عن هذا التحديث؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'rollback_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function deleteUpdate(id) {
    if (!confirm('⚠️ حذف هذا التحديث؟')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_update');
    formData.append('update_id', id);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        }
    });
}

function viewDocument(id) {
    window.location.href = '?page=documents&view=' + id;
}

function applyFilters() {
    const status = document.getElementById('filter-status').value;
    const type = document.getElementById('filter-type').value;
    
    let url = '?page=updates';
    if (status) url += '&status=' + status;
    if (type) url += '&type=' + type;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '?page=updates';
}

// دوال مساعدة
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-4 py-2 rounded-lg shadow-lg text-sm`;
    notification.textContent = message;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}

// إغلاق النوافذ بالـ ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateUpdateModal();
    }
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
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
.notification {
    animation: slideIn 0.3s ease;
}
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
.progress-bar {
    height: 4px;
    background: #334155;
    border-radius: 2px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}
</style>