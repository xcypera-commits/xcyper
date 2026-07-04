<?php
// pages/policies.php - إدارة السياسات الأمنية
$db = getDB();

// =============================================
// 1. إحصائيات السياسات
// =============================================
$stats = $db->query("
    SELECT 
        COUNT(*) as total_policies,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_policies,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_policies,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_policies,
        ROUND(AVG(CASE WHEN status = 'active' THEN compliance_percentage ELSE NULL END), 1) as avg_compliance,
        SUM(CASE WHEN compliance_percentage >= 90 THEN 1 ELSE 0 END) as high_compliance,
        SUM(CASE WHEN compliance_percentage < 70 THEN 1 ELSE 0 END) as low_compliance
    FROM security_policies
")->fetch();

// =============================================
// 2. جميع السياسات مع تفاصيلها
// =============================================
$policies = $db->query("
    SELECT p.*, 
           u1.full_name as created_by_name,
           u2.full_name as approved_by_name,
           (SELECT COUNT(*) FROM servers WHERE 
                FIND_IN_SET(type, p.scope) OR p.scope = 'all') as affected_servers
    FROM security_policies p
    LEFT JOIN users u1 ON p.created_by = u1.id
    LEFT JOIN users u2 ON p.approved_by = u2.id
    WHERE p.status != 'archived'
    ORDER BY 
        CASE p.priority
            WHEN 'high' THEN 1
            WHEN 'medium' THEN 2
            WHEN 'low' THEN 3
        END,
        p.created_at DESC
")->fetchAll();

// =============================================
// 3. إحصائيات حسب الفئة
// =============================================
$category_stats = $db->query("
    SELECT 
        category,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        ROUND(AVG(compliance_percentage), 1) as avg_compliance
    FROM security_policies
    GROUP BY category
    ORDER BY total DESC
")->fetchAll();

// =============================================
// 4. آخر التحديثات على السياسات
// =============================================
$recent_updates = $db->query("
    SELECT p.name, p.updated_at, u.full_name as updated_by
    FROM security_policies p
    LEFT JOIN users u ON p.updated_by = u.id
    WHERE p.updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY p.updated_at DESC
    LIMIT 10
")->fetchAll();

// =============================================
// 5. السياسات منتهية الصلاحية أو القريبة من الانتهاء
// =============================================
$expiring_policies = $db->query("
    SELECT name, review_date
    FROM security_policies
    WHERE review_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND status = 'active'
    ORDER BY review_date ASC
")->fetchAll();

// دوال مساعدة
function getCategoryBadge($category) {
    $colors = [
        'password' => 'bg-purple-500',
        'access' => 'bg-blue-500',
        'backup' => 'bg-green-500',
        'encryption' => 'bg-yellow-500',
        'network' => 'bg-red-500',
        'compliance' => 'bg-indigo-500'
    ];
    $texts = [
        'password' => 'كلمات المرور',
        'access' => 'الوصول',
        'backup' => 'النسخ الاحتياطي',
        'encryption' => 'التشفير',
        'network' => 'الشبكة',
        'compliance' => 'الامتثال'
    ];
    $color = $colors[$category] ?? 'bg-gray-500';
    $text = $texts[$category] ?? $category;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getPriorityBadge($priority) {
    $colors = [
        'high' => 'bg-red-500',
        'medium' => 'bg-yellow-500',
        'low' => 'bg-blue-500'
    ];
    $texts = [
        'high' => 'عالية',
        'medium' => 'متوسطة',
        'low' => 'منخفضة'
    ];
    $color = $colors[$priority] ?? 'bg-gray-500';
    $text = $texts[$priority] ?? $priority;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getStatusBadge($status) {
    $colors = [
        'active' => 'bg-green-500',
        'draft' => 'bg-yellow-500',
        'archived' => 'bg-gray-500'
    ];
    $texts = [
        'active' => 'نشط',
        'draft' => 'مسودة',
        'archived' => 'مؤرشف'
    ];
    $color = $colors[$status] ?? 'bg-gray-500';
    $text = $texts[$status] ?? $status;
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $color'>$text</span>";
}

function getComplianceColor($percentage) {
    if ($percentage >= 90) return 'text-green-400';
    if ($percentage >= 70) return 'text-yellow-400';
    return 'text-red-400';
}
?>

<!-- ==================== الصفحة الرئيسية ==================== -->
<div class="space-y-6">

    <!-- عنوان الصفحة مع إحصائيات سريعة -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <button onclick="addNewPolicy()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    إضافة سياسة جديدة
                </button>
                <button onclick="importPolicies()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                    </svg>
                    استيراد سياسات
                </button>
            </div>
            <h1 class="text-3xl font-bold text-right">
                <span class="text-green-400">🛡️</span> إدارة السياسات الأمنية
            </h1>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-slate-900 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-blue-400 mb-2"><?php echo $stats['total_policies'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">إجمالي السياسات</div>
            </div>
            <div class="bg-green-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-green-400 mb-2"><?php echo $stats['active_policies'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">نشطة</div>
            </div>
            <div class="bg-yellow-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-yellow-400 mb-2"><?php echo $stats['draft_policies'] ?? 0; ?></div>
                <div class="text-sm text-gray-400">مسودة</div>
            </div>
            <div class="bg-purple-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-purple-400 mb-2"><?php echo $stats['avg_compliance'] ?? 0; ?>%</div>
                <div class="text-sm text-gray-400">متوسط الامتثال</div>
            </div>
            <div class="bg-red-900 bg-opacity-20 rounded-lg p-4 text-center transform hover:scale-105 transition-all">
                <div class="text-4xl font-bold text-red-400 mb-2"><?php echo count($expiring_policies); ?></div>
                <div class="text-sm text-gray-400">توشك على الانتهاء</div>
            </div>
        </div>
    </div>

    <!-- سياسات على وشك الانتهاء -->
    <?php if (!empty($expiring_policies)): ?>
    <div class="bg-red-900 bg-opacity-20 border-r-4 border-red-500 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="font-semibold text-red-400">سياسات تحتاج مراجعة:</span>
            </div>
            <div class="flex space-x-2 space-x-reverse">
                <?php foreach ($expiring_policies as $policy): ?>
                <span class="px-3 py-1 bg-red-500 rounded-full text-xs">
                    <?php echo $policy['name']; ?> (<?php echo date('Y-m-d', strtotime($policy['review_date'])); ?>)
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- إحصائيات حسب الفئة -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($category_stats as $cat): ?>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <?php echo getCategoryBadge($cat['category']); ?>
                <span class="text-sm text-gray-400"><?php echo $cat['active']; ?>/<?php echo $cat['total']; ?> نشطة</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-400">نسبة الامتثال</span>
                <span class="text-lg font-bold <?php echo getComplianceColor($cat['avg_compliance']); ?>">
                    <?php echo $cat['avg_compliance']; ?>%
                </span>
            </div>
            <div class="progress-bar mt-2">
                <div class="progress-fill" style="width: <?php echo $cat['avg_compliance']; ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- جدول السياسات -->
    <div class="cyber-border bg-slate-800 rounded-xl p-6">
        <h2 class="text-xl font-bold text-right mb-6 text-blue-400">📋 قائمة السياسات الأمنية</h2>

        <!-- شريط البحث والتصفية -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="relative">
                    <input type="text" id="search-policies" placeholder="بحث في السياسات..." 
                           class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 pr-10 focus:outline-none focus:border-green-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select id="category-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الفئات</option>
                    <option value="password">كلمات المرور</option>
                    <option value="access">الوصول</option>
                    <option value="backup">النسخ الاحتياطي</option>
                    <option value="encryption">التشفير</option>
                    <option value="network">الشبكة</option>
                    <option value="compliance">الامتثال</option>
                </select>
                <select id="status-filter" class="bg-slate-900 border border-slate-700 rounded-lg px-4 py-2 focus:outline-none focus:border-green-500">
                    <option value="all">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="draft">مسودة</option>
                </select>
            </div>
            <div class="text-sm text-gray-400">
                إجمالي: <?php echo count($policies); ?> سياسة
            </div>
        </div>

        <!-- الجدول -->
        <div class="overflow-x-auto">
            <table class="w-full" id="policies-table">
                <thead>
                    <tr class="table-header text-right">
                        <th class="px-6 py-4 text-sm font-semibold">الإجراءات</th>
                        <th class="px-6 py-4 text-sm font-semibold">الحالة</th>
                        <th class="px-6 py-4 text-sm font-semibold">الفئة</th>
                        <th class="px-6 py-4 text-sm font-semibold">الأولوية</th>
                        <th class="px-6 py-4 text-sm font-semibold">نسبة الامتثال</th>
                        <th class="px-6 py-4 text-sm font-semibold">الخوادم المتأثرة</th>
                        <th class="px-6 py-4 text-sm font-semibold">تاريخ المراجعة</th>
                        <th class="px-6 py-4 text-sm font-semibold">النسخة</th>
                        <th class="px-6 py-4 text-sm font-semibold">اسم السياسة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($policies)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">
                            لا توجد سياسات أمنية حالياً
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($policies as $policy): ?>
                        <tr class="border-b border-slate-700 hover:bg-slate-900 transition-colors policy-row" 
                            data-category="<?php echo $policy['category']; ?>"
                            data-status="<?php echo $policy['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button onclick="viewPolicy(<?php echo $policy['id']; ?>)" 
                                            class="text-blue-400 hover:text-blue-300 transition-all transform hover:scale-110" 
                                            title="عرض التفاصيل">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button onclick="editPolicy(<?php echo $policy['id']; ?>)" 
                                            class="text-green-400 hover:text-green-300 transition-all transform hover:scale-110" 
                                            title="تعديل">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <?php if ($policy['status'] == 'draft'): ?>
                                    <button onclick="publishPolicy(<?php echo $policy['id']; ?>)" 
                                            class="text-purple-400 hover:text-purple-300 transition-all transform hover:scale-110" 
                                            title="نشر">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php echo getStatusBadge($policy['status']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php echo getCategoryBadge($policy['category']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php echo getPriorityBadge($policy['priority']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center">
                                    <span class="ml-2 font-semibold <?php echo getComplianceColor($policy['compliance_percentage']); ?>">
                                        <?php echo $policy['compliance_percentage']; ?>%
                                    </span>
                                    <div class="progress-bar w-16">
                                        <div class="progress-fill" style="width: <?php echo $policy['compliance_percentage']; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-300">
                                <?php echo $policy['affected_servers'] ?? 0; ?> خادم
                            </td>
                            <td class="px-6 py-4 text-right text-gray-300">
                                <?php echo date('Y-m-d', strtotime($policy['review_date'] ?? $policy['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-300">
                                <?php echo $policy['version'] ?? 'v1.0'; ?>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-green-400">
                                <?php echo $policy['name']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- آخر التحديثات -->
    <?php if (!empty($recent_updates)): ?>
    <div class="bg-slate-800 rounded-lg p-6">
        <h3 class="text-lg font-bold text-cyan-400 mb-4 text-right">🔄 آخر التحديثات (آخر 7 أيام)</h3>
        <div class="space-y-3">
            <?php foreach ($recent_updates as $update): ?>
            <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                <span class="text-sm text-gray-400"><?php echo date('Y-m-d H:i', strtotime($update['updated_at'])); ?></span>
                <span class="text-sm text-gray-300"><?php echo $update['name']; ?></span>
                <span class="text-xs text-blue-400">بواسطة: <?php echo $update['updated_by'] ?? 'النظام'; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- نافذة عرض/تعديل السياسة -->
<div id="policy-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closePolicyModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400" id="modal-title">📄 تفاصيل السياسة</h3>
        </div>
        
        <div id="policy-content" class="space-y-6">
            <!-- محتوى السياسة يتم تحميله هنا -->
        </div>
    </div>
</div>

<!-- نافذة إضافة سياسة جديدة -->
<div id="add-policy-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-screen-90 overflow-y-auto scrollbar-custom">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeAddPolicyModal()" class="text-gray-400 hover:text-white transition-all transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-green-400">➕ إضافة سياسة جديدة</h3>
        </div>
        
        <form id="add-policy-form" class="space-y-6" onsubmit="saveNewPolicy(event)">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">اسم السياسة</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الفئة</label>
                    <select name="category" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="password">كلمات المرور</option>
                        <option value="access">الوصول</option>
                        <option value="backup">النسخ الاحتياطي</option>
                        <option value="encryption">التشفير</option>
                        <option value="network">الشبكة</option>
                        <option value="compliance">الامتثال</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الأولوية</label>
                    <select name="priority" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                        <option value="high">عالية</option>
                        <option value="medium">متوسطة</option>
                        <option value="low">منخفضة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النسخة</label>
                    <input type="text" name="version" value="v1.0" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">تاريخ المراجعة</label>
                    <input type="date" name="review_date" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">النطاق</label>
                    <input type="text" name="scope" placeholder="all, web, database, ..." class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">الوصف</label>
                <textarea name="description" rows="3" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-2 text-right">محتوى السياسة</label>
                <textarea name="content" rows="6" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-right"></textarea>
            </div>
            <div class="flex space-x-4 space-x-reverse pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all">
                    حفظ السياسة
                </button>
                <button type="button" onclick="closeAddPolicyModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}
.policy-row {
    transition: all 0.3s ease;
}
.policy-row:hover {
    background-color: rgba(30, 41, 59, 0.8);
}
</style>

<script>
// دوال JavaScript خاصة بصفحة السياسات

function addNewPolicy() {
    document.getElementById('add-policy-modal').classList.remove('hidden');
    document.getElementById('add-policy-modal').classList.add('flex');
}

function closeAddPolicyModal() {
    document.getElementById('add-policy-modal').classList.add('hidden');
    document.getElementById('add-policy-modal').classList.remove('flex');
}

function saveNewPolicy(event) {
    event.preventDefault();
    showLoading();
    
    // محاكاة حفظ البيانات
    setTimeout(() => {
        hideLoading();
        closeAddPolicyModal();
        showNotification('تم إضافة السياسة بنجاح', 'success');
    }, 1500);
}

function importPolicies() {
    showNotification('جاري فتح نافذة استيراد السياسات...', 'info');
}

function viewPolicy(id) {
    showLoading();
    
    // محاكاة جلب بيانات السياسة
    setTimeout(() => {
        const policyContent = `
            <div class="space-y-4">
                <div class="p-4 bg-slate-900 rounded-lg">
                    <h4 class="text-xl font-bold text-white mb-2">سياسة كلمات المرور</h4>
                    <p class="text-gray-300 mb-4">تحدد متطلبات إنشاء كلمات المرور وتغييرها</p>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="p-3 bg-slate-800 rounded-lg text-center">
                            <p class="text-sm text-gray-400">الحالة</p>
                            <p class="font-semibold text-green-400">نشط</p>
                        </div>
                        <div class="p-3 bg-slate-800 rounded-lg text-center">
                            <p class="text-sm text-gray-400">الامتثال</p>
                            <p class="font-semibold text-green-400">98%</p>
                        </div>
                        <div class="p-3 bg-slate-800 rounded-lg text-center">
                            <p class="text-sm text-gray-400">الخوادم</p>
                            <p class="font-semibold text-blue-400">12 خادم</p>
                        </div>
                    </div>

                    <h5 class="text-lg font-semibold text-green-400 mb-2">محتوى السياسة</h5>
                    <div class="p-4 bg-slate-800 rounded-lg mb-4">
                        <p class="text-gray-300">1. يجب أن تكون كلمة المرور 8 أحرف على الأقل</p>
                        <p class="text-gray-300">2. تحتوي على حرف كبير وصغير</p>
                        <p class="text-gray-300">3. تحتوي على رقم واحد على الأقل</p>
                        <p class="text-gray-300">4. تحتوي على رمز خاص</p>
                        <p class="text-gray-300">5. تغيير كلمة المرور كل 90 يوم</p>
                    </div>

                    <div class="flex justify-between pt-4 border-t border-slate-700">
                        <button onclick="editPolicy(${id})" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg">تعديل</button>
                        <button onclick="publishPolicy(${id})" class="px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg">نشر</button>
                        <button onclick="closePolicyModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg">إغلاق</button>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('policy-content').innerHTML = policyContent;
        document.getElementById('modal-title').textContent = '📄 تفاصيل السياسة';
        hideLoading();
        document.getElementById('policy-modal').classList.remove('hidden');
        document.getElementById('policy-modal').classList.add('flex');
    }, 1000);
}

function closePolicyModal() {
    document.getElementById('policy-modal').classList.add('hidden');
    document.getElementById('policy-modal').classList.remove('flex');
}

function editPolicy(id) {
    closePolicyModal();
    showNotification('فتح نافذة تعديل السياسة', 'info');
}

function publishPolicy(id) {
    if (confirm('هل أنت متأكد من نشر هذه السياسة؟')) {
        showNotification('تم نشر السياسة بنجاح', 'success');
    }
}

// البحث المباشر في الجدول
document.getElementById('search-policies')?.addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.policy-row');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// تصفية حسب الفئة
document.getElementById('category-filter')?.addEventListener('change', filterPolicies);
document.getElementById('status-filter')?.addEventListener('change', filterPolicies);

function filterPolicies() {
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('.policy-row');
    
    rows.forEach(row => {
        const rowCategory = row.dataset.category;
        const rowStatus = row.dataset.status;
        
        const categoryMatch = category === 'all' || rowCategory === category;
        const statusMatch = status === 'all' || rowStatus === status;
        
        if (categoryMatch && statusMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>