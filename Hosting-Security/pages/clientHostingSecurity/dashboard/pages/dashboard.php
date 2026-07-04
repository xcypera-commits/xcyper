<?php
// =============================================
// client-unit/pages/dashboard.php
// اللوحة الرئيسية للعميل
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: العميل غير محدد</div>';
    return;
}

// =============================================
// تعريف الدوال المساعدة المفقودة
// =============================================

/**
 * الحصول على لون حالة المشروع
 */

/**
 * الحصول على نص حالة المشروع
 */
function getProjectStatusText($status) {
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
 * الحصول على أيقونة النشاط
 */
function getActivityIcon($type) {
    $icons = [
        'login' => '🔑',
        'logout' => '🚪',
        'view' => '👁️',
        'download' => '⬇️',
        'upload' => '⬆️',
        'payment' => '💰',
        'ticket' => '🎫',
        'contract' => '📄',
        'report' => '📊',
        'create' => '➕',
        'update' => '✏️',
        'delete' => '🗑️'
    ];
    
    return $icons[$type] ?? '📌';
}

// =============================================
// باقي كود الصفحة هنا
// ...
try {
    // =============================================
    // 1. إحصائيات سريعة
    // =============================================
    
    // المشاريع النشطة
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM client_projects 
        WHERE client_id = ? AND status IN ('pending', 'under_study', 'in_progress', 'testing')
    ");
    $stmt->execute([$current_client['id']]);
    $active_projects = $stmt->fetchColumn() ?: 0;
    
    // إجمالي المشاريع
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_projects WHERE client_id = ?");
    $stmt->execute([$current_client['id']]);
    $total_projects = $stmt->fetchColumn() ?: 0;
    
    // الفواتير المستحقة
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM client_invoices 
        WHERE client_id = ? AND status = 'pending'
    ");
    $stmt->execute([$current_client['id']]);
    $pending_invoices = $stmt->fetchColumn() ?: 0;
    
    // إجمالي الفواتير
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) FROM client_invoices 
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $total_invoices = $stmt->fetchColumn() ?: 0;
    
    // التقارير الجاهزة
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM client_reports 
        WHERE client_id = ? AND status = 'ready'
    ");
    $stmt->execute([$current_client['id']]);
    $ready_reports = $stmt->fetchColumn() ?: 0;
    
    // إجمالي التقارير
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_reports WHERE client_id = ?");
    $stmt->execute([$current_client['id']]);
    $total_reports = $stmt->fetchColumn() ?: 0;
    
    // مساحة التخزين المستخدمة
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(file_size), 0) FROM client_files 
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $used_storage = $stmt->fetchColumn() ?: 0;
    $total_storage = 100 * 1024 * 1024 * 1024; // 100 GB
    $storage_percent = $total_storage > 0 ? round(($used_storage / $total_storage) * 100, 1) : 0;
    $used_storage_gb = round($used_storage / (1024 * 1024 * 1024), 1);
    
    // إجمالي الملفات
    $stmt = $db->prepare("SELECT COUNT(*) FROM client_files WHERE client_id = ?");
    $stmt->execute([$current_client['id']]);
    $total_files = $stmt->fetchColumn() ?: 0;
    
    // =============================================
    // 2. آخر المشاريع
    // =============================================
    
    $stmt = $db->prepare("
        SELECT * FROM client_projects 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 4
    ");
    $stmt->execute([$current_client['id']]);
    $recent_projects = $stmt->fetchAll();
    
    // =============================================
    // 3. آخر الفواتير
    // =============================================
    
    $stmt = $db->prepare("
        SELECT i.*, p.project_name 
        FROM client_invoices i
        LEFT JOIN client_projects p ON i.project_id = p.id
        WHERE i.client_id = ? 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_client['id']]);
    $recent_invoices = $stmt->fetchAll();
    
    // =============================================
    // 4. آخر التذاكر
    // =============================================
    
    $stmt = $db->prepare("
        SELECT * FROM client_support_tickets 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 4
    ");
    $stmt->execute([$current_client['id']]);
    $recent_tickets = $stmt->fetchAll();
    
    // =============================================
    // 5. آخر النشاطات
    // =============================================
    
    $stmt = $db->prepare("
        SELECT * FROM client_activity_log 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$current_client['id']]);
    $recent_activities = $stmt->fetchAll();
    
    // =============================================
    // 6. إحصائيات العقود
    // =============================================
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'signed' AND signed_by_client = 0 THEN 1 ELSE 0 END) as pending_signature
        FROM client_contracts
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $contract_stats = $stmt->fetch();
    
    // =============================================
    // 7. إحصائيات الفواتير (ملخص)
    // =============================================
    
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) as due_now,
            COALESCE(SUM(CASE WHEN status = 'paid' AND MONTH(paid_date) = MONTH(NOW()) THEN total_amount ELSE 0 END), 0) as paid_this_month,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'overdue') THEN total_amount ELSE 0 END), 0) as total_due,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_paid
        FROM client_invoices
        WHERE client_id = ?
    ");
    $stmt->execute([$current_client['id']]);
    $invoice_summary = $stmt->fetch();
    
} catch (Exception $e) {
    // في حالة الخطأ، نستخدم قيم افتراضية
    $active_projects = 0;
    $total_projects = 0;
    $pending_invoices = 0;
    $total_invoices = 0;
    $ready_reports = 0;
    $total_reports = 0;
    $used_storage = 0;
    $used_storage_gb = 0;
    $storage_percent = 0;
    $total_files = 0;
    $recent_projects = [];
    $recent_invoices = [];
    $recent_tickets = [];
    $recent_activities = [];
    $contract_stats = ['total' => 0, 'active' => 0, 'pending_signature' => 0];
    $invoice_summary = ['due_now' => 0, 'paid_this_month' => 0, 'total_due' => 0, 'total_paid' => 0];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}






function getTicketPriorityColor($priority) {
    $colors = [
        'low' => 'gray',
        'medium' => 'blue',
        'high' => 'orange',
        'urgent' => 'red'
    ];
    
    return $colors[$priority] ?? 'gray';
}
?>

<!-- ============================================= -->
<!-- إحصائيات سريعة - 4 بطاقات رئيسية -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- بطاقة المشاريع -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">المشاريع النشطة</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo $active_projects; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-green-500"></span>
            <span class="text-green-400 mr-2"><?php echo $total_projects; ?> إجمالي المشاريع</span>
        </div>
    </div>

    <!-- بطاقة الفواتير -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">فواتير مستحقة</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo $pending_invoices; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-yellow-500"></span>
            <span class="text-yellow-400 mr-2">إجمالي: <?php echo number_format($total_invoices, 2); ?> ر.س</span>
        </div>
    </div>

    <!-- بطاقة التقارير -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">تقارير جاهزة</p>
                <p class="text-3xl font-bold text-green-400"><?php echo $ready_reports; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
            <span class="status-indicator bg-blue-500"></span>
            <span class="text-blue-400 mr-2"><?php echo $total_reports; ?> إجمالي التقارير</span>
        </div>
    </div>

    <!-- بطاقة التخزين -->
    <div class="card-hover cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm mb-2">مساحة التخزين</p>
                <p class="text-3xl font-bold text-cyan-400"><?php echo $used_storage_gb; ?> GB</p>
            </div>
            <div class="w-12 h-12 bg-cyan-600 bg-opacity-20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $storage_percent; ?>%"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-400 mt-2">
                <span><?php echo $total_files; ?> ملف</span>
                <span><?php echo $storage_percent; ?>% من 100GB</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الإجراءات السريعة -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-8">
    <h3 class="text-xl font-bold mb-6 text-right">الإجراءات السريعة</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <button onclick="navigateTo('upload')" class="card-hover cyber-border bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <span class="text-sm font-semibold">رفع ملفات جديدة</span>
        </button>

        <button onclick="requestNewService()" class="card-hover cyber-border bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-semibold">طلب خدمة جديدة</span>
        </button>

        <button onclick="navigateTo('contracts')" class="card-hover cyber-border bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-yellow-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-semibold">مراجعة العقود</span>
        </button>

        <button onclick="navigateTo('support')" class="card-hover cyber-border bg-slate-900 p-4 rounded-lg flex flex-col items-center justify-center hover:bg-slate-800 transition-all">
            <svg class="w-10 h-10 text-purple-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <span class="text-sm font-semibold">تواصل مع الدعم</span>
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الثاني: آخر المشاريع وإحصائيات العقود -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- آخر المشاريع -->
    <div class="lg:col-span-2 cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر المشاريع</h3>
            <a href="?page=projects" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_projects)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد مشاريع حالياً</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_projects as $project): 
                    $color = getProjectStatusColor($project['status']);
                ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
                     onclick="window.location.href='?page=projects&view=<?php echo $project['id']; ?>'">
                    <div class="flex items-center">
                        <span class="w-2 h-2 rounded-full bg-<?php echo $color; ?>-500 ml-3"></span>
                        <div>
                            <h4 class="font-semibold"><?php echo htmlspecialchars($project['project_name']); ?></h4>
                            <p class="text-xs text-gray-400">المرحلة: <?php echo getStageName($project['stage']); ?></p>
                        </div>
                    </div>
                    <div class="text-left">
                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $color; ?>-600 bg-opacity-20 text-<?php echo $color; ?>-400">
                            <?php echo getProjectStatusText($project['status']); ?>
                        </span>
                        <p class="text-xs text-gray-400 mt-1">تقدم: <?php echo $project['progress']; ?>%</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- إحصائيات العقود -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h3 class="text-xl font-bold mb-4 text-right">العقود</h3>
        
        <div class="space-y-4">
            <div class="text-center p-4 bg-slate-900 rounded-lg">
                <p class="text-3xl font-bold text-blue-400"><?php echo $contract_stats['total']; ?></p>
                <p class="text-sm text-gray-400">إجمالي العقود</p>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-slate-900 rounded-lg">
                    <p class="text-xl font-bold text-green-400"><?php echo $contract_stats['active']; ?></p>
                    <p class="text-xs text-gray-400">نشط</p>
                </div>
                <div class="text-center p-3 bg-slate-900 rounded-lg">
                    <p class="text-xl font-bold text-yellow-400"><?php echo $contract_stats['pending_signature']; ?></p>
                    <p class="text-xs text-gray-400">بانتظار التوقيع</p>
                </div>
            </div>
            
            <?php if ($contract_stats['pending_signature'] > 0): ?>
            <div class="p-3 bg-yellow-900 bg-opacity-20 border border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-400">لديك عقود بانتظار التوقيع</p>
                <button onclick="navigateTo('contracts')" class="text-xs bg-yellow-600 hover:bg-yellow-700 px-3 py-1 rounded-lg mt-2">
                    مراجعة العقود
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- الصف الثالث: آخر الفواتير وآخر التذاكر -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- آخر الفواتير -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر الفواتير</h3>
            <a href="?page=billing" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_invoices)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد فواتير</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_invoices as $invoice): 
                    $color = getInvoiceStatusColor($invoice['status']);
                ?>
                <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
                     onclick="window.location.href='?page=billing&view=<?php echo $invoice['id']; ?>'">
                    <div>
                        <h4 class="font-semibold text-sm"><?php echo htmlspecialchars($invoice['title']); ?></h4>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($invoice['project_name'] ?? 'عام'); ?></p>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-<?php echo $color; ?>-400"><?php echo number_format($invoice['total_amount'], 2); ?> ر.س</p>
                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $color; ?>-600 bg-opacity-20 text-<?php echo $color; ?>-400">
                            <?php echo $invoice['status']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- ملخص الفواتير -->
        <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-slate-700">
            <div class="text-center">
                <p class="text-sm text-gray-400">المستحق الآن</p>
                <p class="text-lg font-bold text-red-400"><?php echo number_format($invoice_summary['due_now'], 2); ?> ر.س</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-400">مدفوع هذا الشهر</p>
                <p class="text-lg font-bold text-green-400"><?php echo number_format($invoice_summary['paid_this_month'], 2); ?> ر.س</p>
            </div>
        </div>
    </div>
    
    <!-- آخر تذاكر الدعم -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-right">آخر تذاكر الدعم</h3>
            <a href="?page=support" class="text-sm text-blue-400 hover:text-blue-300">عرض الكل</a>
        </div>
        
        <?php if (empty($recent_tickets)): ?>
            <p class="text-gray-400 text-center py-4">لا توجد تذاكر دعم</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recent_tickets as $ticket): 
                    $status_color = getTicketStatusColor($ticket['status']);
                    $priority_color = getTicketPriorityColor($ticket['priority']);
                ?>
                <div class="p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
                     onclick="window.location.href='?page=support&view=<?php echo $ticket['id']; ?>'">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="font-semibold text-sm"><?php echo htmlspecialchars($ticket['subject']); ?></h4>
                        <span class="px-2 py-1 text-xs rounded-full bg-<?php echo $priority_color; ?>-600 bg-opacity-20 text-<?php echo $priority_color; ?>-400">
                            <?php echo $ticket['priority']; ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">#<?php echo $ticket['ticket_code']; ?></span>
                        <span class="px-2 py-1 rounded-full bg-<?php echo $status_color; ?>-600 bg-opacity-20 text-<?php echo $status_color; ?>-400">
                            <?php echo $ticket['status']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <button onclick="createNewTicket()" class="w-full mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm transition-all">
            إنشاء تذكرة دعم جديدة
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- آخر النشاطات -->
<!-- ============================================= -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-right">آخر النشاطات</h3>
        <span class="text-xs text-gray-400">آخر 5 نشاطات</span>
    </div>
    
    <?php if (empty($recent_activities)): ?>
        <p class="text-gray-400 text-center py-4">لا توجد نشاطات حديثة</p>
    <?php else: ?>
        <div class="space-y-3 max-h-80 overflow-y-auto scrollbar-custom pl-2">
            <?php foreach ($recent_activities as $activity): ?>
            <div class="flex items-start border-r-2 border-blue-500 pr-4 py-2">
                <div class="ml-3 mt-1">
                    <span class="text-xl"><?php echo getActivityIcon($activity['activity_type']); ?></span>
                </div>
                <div class="flex-1 text-right">
                    <p class="text-sm text-gray-300"><?php echo htmlspecialchars($activity['description']); ?></p>
                    <div class="flex items-center justify-end text-xs text-gray-500 mt-1">
                        <span><?php echo timeAgo($activity['created_at']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
function createNewTicket() {
    window.location.href = '?page=support&action=new';
}
</script>