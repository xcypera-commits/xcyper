<?php
// =============================================
// client-unit/pages/support.php
// صفحة الدعم والملاحظات - التصميم الشامل النهائي
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// التأكد من وجود معرف العميل
if (!isset($current_client) || !isset($current_client['id'])) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">خطأ: العميل غير محدد</div>';
    return;
}

$client_id = $current_client['id'];

try {
    // =========================================
    // 1. جلب تذاكر الدعم مع التفاصيل
    // =========================================
    $tickets = $db->prepare("
        SELECT 
            t.*,
            p.project_name,
            (SELECT COUNT(*) FROM client_ticket_replies WHERE ticket_id = t.id) as replies_count,
            (SELECT created_at FROM client_ticket_replies WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_reply_at
        FROM client_support_tickets t
        LEFT JOIN client_projects p ON t.project_id = p.id
        WHERE t.client_id = ?
        ORDER BY 
            CASE t.status 
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'waiting' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'closed' THEN 5
            END,
            t.created_at DESC
    ");
    $tickets->execute([$client_id]);
    $tickets = $tickets->fetchAll();

    // =========================================
    // 2. جلب مشاريع العميل للتذاكر
    // =========================================
    $projects = $db->prepare("
        SELECT id, project_name, project_code
        FROM client_projects 
        WHERE client_id = ? AND status IN ('in_progress', 'completed', 'testing')
        ORDER BY project_name
    ");
    $projects->execute([$client_id]);
    $projects = $projects->fetchAll();

    // =========================================
    // 3. جلب آخر الردود لكل تذكرة
    // =========================================
    $replies = [];
    if (!empty($tickets)) {
        $ticket_ids = array_column($tickets, 'id');
        $placeholders = implode(',', array_fill(0, count($ticket_ids), '?'));
        
        $replies_query = $db->prepare("
            SELECT r.*, 
                   CASE WHEN r.is_staff = 1 THEN 'فريق الدعم' ELSE 'أنت' END as sender_name
            FROM client_ticket_replies r
            WHERE r.ticket_id IN ($placeholders)
            ORDER BY r.created_at ASC
        ");
        $replies_query->execute($ticket_ids);
        
        foreach ($replies_query->fetchAll() as $reply) {
            $replies[$reply['ticket_id']][] = $reply;
        }
    }

    // =========================================
    // 4. إحصائيات سريعة
    // =========================================
    $stats = [
        'total' => count($tickets),
        'open' => count(array_filter($tickets, fn($t) => $t['status'] == 'open')),
        'in_progress' => count(array_filter($tickets, fn($t) => $t['status'] == 'in_progress')),
        'waiting' => count(array_filter($tickets, fn($t) => $t['status'] == 'waiting')),
        'resolved' => count(array_filter($tickets, fn($t) => $t['status'] == 'resolved')),
        'closed' => count(array_filter($tickets, fn($t) => $t['status'] == 'closed')),
        'urgent' => count(array_filter($tickets, fn($t) => $t['priority'] == 'urgent'))
    ];

    // =========================================
    // 5. الأسئلة الشائعة
    // =========================================
    $faqs = [
        [
            'question' => 'كيف يمكنني تغيير كلمة مرور FTP؟',
            'answer' => 'يمكنك تغيير كلمة مرور FTP من خلال صفحة الاستضافة، اختر الموقع ثم اضغط على "بيانات FTP" ثم "تغيير كلمة المرور".'
        ],
        [
            'question' => 'ماذا أفعل إذا كان موقعي بطيئاً؟',
            'answer' => 'يمكنك تجربة: 1) مسح الكاش من لوحة التحكم 2) التأكد من عدم تجاوز المساحة التخزينية 3) التواصل مع الدعم لفحص الأداء.'
        ],
        [
            'question' => 'كيف أرقي خطة الاستضافة؟',
            'answer' => 'من صفحة الاستضافة، اختر "خطط الاستضافة" ثم اختر الخطة الجديدة واضغط "ترقية". سيتم خصم المبلغ المتبقي من خطتك الحالية.'
        ],
        [
            'question' => 'كم تستغرق معالجة التذكرة؟',
            'answer' => 'التذاكر العادية: 24 ساعة، التذاكر العاجلة: 4 ساعات، التذاكر الحرجة: ساعة واحدة.'
        ],
        [
            'question' => 'كيف أنشئ قاعدة بيانات جديدة؟',
            'answer' => 'من صفحة الاستضافة، اختر الموقع ثم "إدارة الموقع" ثم "قواعد البيانات" ثم "إنشاء قاعدة بيانات جديدة".'
        ]
    ];

} catch (Exception $e) {
    $tickets = [];
    $projects = [];
    $replies = [];
    $stats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'waiting' => 0, 'resolved' => 0, 'closed' => 0, 'urgent' => 0];
    $faqs = [];
}

// =============================================
// دوال مساعدة للتنسيق
// =============================================
function getPriorityBadge($priority) {
    $colors = [
        'urgent' => 'bg-red-500/20 text-red-400 border-red-500/30',
        'high' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'medium' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'low' => 'bg-blue-500/20 text-blue-400 border-blue-500/30'
    ];
    $texts = [
        'urgent' => 'عاجل جداً',
        'high' => 'عالي',
        'medium' => 'متوسط',
        'low' => 'منخفض'
    ];
    $color = $colors[$priority] ?? 'bg-slate-500/20 text-slate-400 border-slate-500/30';
    $text = $texts[$priority] ?? $priority;
    
    return "<span class='px-3 py-1 rounded-full text-xs border $color'>$text</span>";
}

function getStatusBadge($status) {
    $colors = [
        'open' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'in_progress' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        'waiting' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'resolved' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        'closed' => 'bg-slate-500/20 text-slate-400 border-slate-500/30'
    ];
    $texts = [
        'open' => 'مفتوحة',
        'in_progress' => 'قيد المعالجة',
        'waiting' => 'بانتظار ردك',
        'resolved' => 'تم الحل',
        'closed' => 'مغلقة'
    ];
    $color = $colors[$status] ?? 'bg-slate-500/20 text-slate-400 border-slate-500/30';
    $text = $texts[$status] ?? $status;
    
    return "<span class='px-3 py-1 rounded-full text-xs border $color'>$text</span>";
}

function getCategoryText($category) {
    $texts = [
        'technical' => 'دعم فني',
        'billing' => 'فواتير ومدفوعات',
        'sales' => 'مبيعات واشتراكات',
        'general' => 'استفسار عام'
    ];
    return $texts[$category] ?? $category;
}
?>

<!-- ============================================= -->
<!-- الهيدر - تصميم جديد -->
<!-- ============================================= -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 mb-8 border border-slate-700">
    <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent"></div>
    
    <div class="relative z-10">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="w-16 h-16 rounded-2xl bg-green-500/20 flex items-center justify-center backdrop-blur-sm border border-green-500/30">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5m2 4H5m10-4h-2m2 4h-2"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-l from-green-400 to-emerald-400 bg-clip-text text-transparent">الدعم والملاحظات</h1>
                    <p class="text-slate-400 mt-1">فريق الدعم الفني جاهز لمساعدتك على مدار الساعة</p>
                </div>
            </div>
            
            <button onclick="openNewTicketModal()" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-xl font-semibold text-white shadow-lg transition-all hover:shadow-green-500/25 flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                تذكرة جديدة
            </button>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة - 7 كروت -->
<!-- ============================================= -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-green-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">الإجمالي</p>
            <p class="text-2xl font-bold text-white"><?php echo $stats['total']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-green-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">مفتوحة</p>
            <p class="text-2xl font-bold text-green-400"><?php echo $stats['open']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-blue-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">قيد المعالجة</p>
            <p class="text-2xl font-bold text-blue-400"><?php echo $stats['in_progress']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-yellow-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">بانتظار ردك</p>
            <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['waiting']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-purple-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">تم الحل</p>
            <p class="text-2xl font-bold text-purple-400"><?php echo $stats['resolved']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-slate-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">مغلقة</p>
            <p class="text-2xl font-bold text-slate-400"><?php echo $stats['closed']; ?></p>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-4 border border-slate-700 hover:border-red-500/50 transition-all">
        <div class="text-center">
            <p class="text-xs text-slate-400 mb-1">عاجلة</p>
            <p class="text-2xl font-bold text-red-400"><?php echo $stats['urgent']; ?></p>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويبات التنقل -->
<!-- ============================================= -->
<div class="border-b border-slate-700 mb-6">
    <div class="flex space-x-8 space-x-reverse overflow-x-auto pb-1">
        <button onclick="switchTab('tickets')" id="tab-tickets-btn" class="tab-btn active pb-4 px-1 border-b-2 border-green-500 text-green-400 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                جميع التذاكر (<?php echo $stats['total']; ?>)
            </span>
        </button>
        <button onclick="switchTab('new')" id="tab-new-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                </svg>
                تذكرة جديدة
            </span>
        </button>
        <button onclick="switchTab('faq')" id="tab-faq-btn" class="tab-btn pb-4 px-1 border-b-2 border-transparent text-slate-400 hover:text-slate-300 font-medium whitespace-nowrap">
            <span class="flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                الأسئلة الشائعة
            </span>
        </button>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويب 1: جميع التذاكر -->
<!-- ============================================= -->
<div id="tab-tickets" class="tab-content">
    <?php if (empty($tickets)): ?>
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-12 text-center border border-slate-700">
        <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
            <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-white mb-2">لا توجد تذاكر دعم</h3>
        <p class="text-slate-400 mb-6">يمكنك فتح تذكرة جديدة وسنقوم بالرد عليك في أقرب وقت</p>
        <button onclick="switchTab('new')" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg text-white font-medium transition-all">
            فتح تذكرة جديدة
        </button>
    </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tickets as $ticket): ?>
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-green-500/50 transition-all cursor-pointer" onclick="viewTicket(<?php echo $ticket['id']; ?>)">
                <!-- الصف الأول: رقم التذكرة + الحالة + الأولوية -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <span class="text-sm font-mono text-slate-500">#<?php echo $ticket['ticket_code']; ?></span>
                        <span class="text-sm text-slate-400"><?php echo getCategoryText($ticket['category']); ?></span>
                    </div>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <?php echo getStatusBadge($ticket['status']); ?>
                        <?php echo getPriorityBadge($ticket['priority']); ?>
                    </div>
                </div>
                
                <!-- الصف الثاني: الموضوع + المشروع -->
                <div class="mb-3">
                    <h3 class="text-lg font-semibold text-white mb-1"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                    <div class="flex items-center text-sm">
                        <?php if ($ticket['project_name']): ?>
                        <span class="text-slate-400 ml-3">المشروع: <?php echo $ticket['project_name']; ?></span>
                        <?php endif; ?>
                        <span class="text-slate-500">تاريخ الفتح: <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- الصف الثالث: آخر نشاط + عدد الردود -->
                <div class="flex items-center justify-between text-sm pt-3 border-t border-slate-700">
                    <div class="flex items-center text-slate-400">
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <span><?php echo $ticket['replies_count']; ?> ردود</span>
                    </div>
                    <div class="text-slate-500">
                        آخر نشاط: <?php echo $ticket['last_reply_at'] ? date('Y-m-d H:i', strtotime($ticket['last_reply_at'])) : 'لم يتم الرد بعد'; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- تبويب 2: تذكرة جديدة -->
<!-- ============================================= -->
<div id="tab-new" class="tab-content hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- نموذج إنشاء تذكرة -->
        <div class="lg:col-span-2">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-4">فتح تذكرة دعم جديدة</h3>
                
                <form id="new-ticket-form" onsubmit="handleNewTicket(event)" class="space-y-4">
                    <!-- نوع التذكرة -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">نوع التذكرة</label>
                        <select id="ticket-category" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white">
                            <option value="technical">دعم فني</option>
                            <option value="billing">فواتير ومدفوعات</option>
                            <option value="sales">مبيعات واشتراكات</option>
                            <option value="general">استفسار عام</option>
                        </select>
                    </div>
                    
                    <!-- الأولوية -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">الأولوية</label>
                        <select id="ticket-priority" required class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white">
                            <option value="low">منخفضة</option>
                            <option value="medium" selected>متوسطة</option>
                            <option value="high">عالية</option>
                            <option value="urgent">عاجلة جداً</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">التذاكر العاجلة تحصل على رد خلال 4 ساعات</p>
                    </div>
                    
                    <!-- المشروع (اختياري) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">المشروع (اختياري)</label>
                        <select id="ticket-project" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white">
                            <option value="">-- غير مرتبط بمشروع --</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- الموضوع -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">الموضوع</label>
                        <input type="text" id="ticket-subject" required maxlength="255" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white" placeholder="ملخص مختصر للمشكلة">
                    </div>
                    
                    <!-- الرسالة -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">الرسالة</label>
                        <textarea id="ticket-message" required rows="6" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white" placeholder="اشرح المشكلة بالتفصيل..."></textarea>
                    </div>
                    
                    <!-- المرفقات -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">المرفقات (اختياري)</label>
                        <div class="border-2 border-dashed border-slate-700 hover:border-green-500/50 rounded-lg p-4 text-center transition-all cursor-pointer"
                             onclick="document.getElementById('ticket-attachments').click()">
                            
                            <input type="file" id="ticket-attachments" class="hidden" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.zip,.txt">
                            
                            <div class="flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span class="text-sm text-slate-400">اضغط لإضافة مرفقات</span>
                            </div>
                        </div>
                        <div id="attachments-list" class="mt-2 hidden">
                            <p class="text-xs text-slate-500">الملفات المرفقة:</p>
                            <div id="attachments-container" class="flex flex-wrap gap-2 mt-1"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg font-medium transition-all">
                        إرسال التذكرة
                    </button>
                </form>
            </div>
        </div>
        
        <!-- نصائح وإرشادات -->
        <div class="lg:col-span-1">
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
                <h3 class="text-lg font-semibold text-white mb-4">نصائح قبل الإرسال</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                            <span class="text-green-400 text-xs">✓</span>
                        </div>
                        <p class="text-sm text-slate-300">اشرح المشكلة بالتفصيل لتساعد فريق الدعم على فهمها بشكل أسرع</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                            <span class="text-green-400 text-xs">✓</span>
                        </div>
                        <p class="text-sm text-slate-300">إذا كان الخطأ يظهر في الموقع، أرسل لنا صورة توضيحية</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                            <span class="text-green-400 text-xs">✓</span>
                        </div>
                        <p class="text-sm text-slate-300">اختر الأولوية المناسبة - العاجلة للمشاكل الحرجة فقط</p>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                            <span class="text-green-400 text-xs">✓</span>
                        </div>
                        <p class="text-sm text-slate-300">اربط التذكرة بالمشروع المناسب ليسهل تتبع المشكلة</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-slate-700">
                    <p class="text-sm text-slate-400">متوسط وقت الرد:</p>
                    <div class="mt-2 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-400">عادي:</span>
                            <span class="text-white">24 ساعة</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-400">عالي:</span>
                            <span class="text-white">12 ساعة</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-400">عاجل:</span>
                            <span class="text-white">4 ساعات</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- تبويب 3: الأسئلة الشائعة -->
<!-- ============================================= -->
<div id="tab-faq" class="tab-content hidden">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-white mb-4">الأسئلة الشائعة</h3>
        
        <div class="space-y-4">
            <?php foreach ($faqs as $index => $faq): ?>
            <div class="border border-slate-700 rounded-lg overflow-hidden">
                <button onclick="toggleFAQ(<?php echo $index; ?>)" class="w-full px-4 py-3 bg-slate-900/50 hover:bg-slate-800 flex items-center justify-between text-right transition-all">
                    <span class="text-white font-medium"><?php echo $faq['question']; ?></span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform" id="faq-icon-<?php echo $index; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="faq-answer-<?php echo $index; ?>" class="hidden px-4 py-3 bg-slate-800/50 text-slate-300 text-sm border-t border-slate-700">
                    <?php echo $faq['answer']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
            <div class="flex items-start">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center ml-3 flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-white font-medium mb-1">لم تجد إجابة لسؤالك؟</p>
                    <p class="text-sm text-slate-400">يمكنك فتح تذكرة دعم وسيقوم فريقنا بالرد عليك</p>
                    <button onclick="switchTab('new')" class="mt-3 text-sm text-blue-400 hover:text-blue-300">
                انتقل إلى تذكرة جديدة ←
            </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض تفاصيل التذكرة -->
<!-- ============================================= -->
<div id="ticket-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-slate-700">
        <div class="flex items-center justify-between mb-4 sticky top-0 bg-slate-800 py-2">
            <button onclick="closeTicketModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-xl font-bold text-white" id="ticket-modal-title">تفاصيل التذكرة</h3>
        </div>
        
        <div id="ticket-details" class="space-y-4">
            <!-- محتوى التذكرة يتحمل ديناميكياً -->
        </div>
        
        <!-- نموذج الرد -->
        <div class="mt-4 pt-4 border-t border-slate-700">
            <form id="reply-form" onsubmit="handleReply(event)" class="space-y-3">
                <input type="hidden" id="reply-ticket-id">
                <textarea id="reply-message" rows="3" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-green-500 text-white" placeholder="اكتب ردك هنا..."></textarea>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button type="button" onclick="document.getElementById('reply-attachments').click()" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg transition-all">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </button>
                        <input type="file" id="reply-attachments" class="hidden" multiple>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-lg text-sm font-medium transition-all">
                        إرسال الرد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
// =============================================
// التبديل بين التبويبات
// =============================================
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'text-green-400', 'border-green-500');
        btn.classList.add('text-slate-400', 'border-transparent');
    });
    
    document.getElementById('tab-' + tab + '-btn').classList.add('active', 'text-green-400', 'border-green-500');
}

// =============================================
// الأسئلة الشائعة
// =============================================
function toggleFAQ(index) {
    const answer = document.getElementById('faq-answer-' + index);
    const icon = document.getElementById('faq-icon-' + index);
    
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        answer.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// =============================================
// المرفقات
// =============================================
document.getElementById('ticket-attachments')?.addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const container = document.getElementById('attachments-container');
    const listDiv = document.getElementById('attachments-list');
    
    container.innerHTML = '';
    
    if (files.length > 0) {
        files.forEach(file => {
            const div = document.createElement('div');
            div.className = 'px-3 py-1 bg-slate-700 rounded-full text-xs text-white';
            div.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            container.appendChild(div);
        });
        listDiv.classList.remove('hidden');
    } else {
        listDiv.classList.add('hidden');
    }
});

// =============================================
// إنشاء تذكرة جديدة
// =============================================
function openNewTicketModal() {
    switchTab('new');
}

function handleNewTicket(e) {
    e.preventDefault();
    
    const category = document.getElementById('ticket-category').value;
    const priority = document.getElementById('ticket-priority').value;
    const subject = document.getElementById('ticket-subject').value;
    const message = document.getElementById('ticket-message').value;
    
    showNotification('جاري إرسال التذكرة...', 'info');
    
    setTimeout(() => {
        // إعادة تعيين النموذج
        document.getElementById('new-ticket-form').reset();
        document.getElementById('attachments-list').classList.add('hidden');
        document.getElementById('attachments-container').innerHTML = '';
        
        // التبديل إلى تبويب التذاكر
        switchTab('tickets');
        
        showNotification('تم إرسال التذكرة بنجاح، سنقوم بالرد في أقرب وقت', 'success');
    }, 1500);
}

// =============================================
// عرض تفاصيل التذكرة
// =============================================
let currentTicketId = null;

function viewTicket(ticketId) {
    currentTicketId = ticketId;
    document.getElementById('reply-ticket-id').value = ticketId;
    
    // محاكاة جلب تفاصيل التذكرة
    document.getElementById('ticket-details').innerHTML = `
        <div class="bg-slate-900/50 rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-sm font-mono text-slate-500">#TKT-${ticketId}24</span>
                    <span class="text-sm text-slate-400 mr-3">دعم فني</span>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="px-3 py-1 rounded-full text-xs border bg-green-500/20 text-green-400 border-green-500/30">مفتوحة</span>
                    <span class="px-3 py-1 rounded-full text-xs border bg-yellow-500/20 text-yellow-400 border-yellow-500/30">متوسطة</span>
                </div>
            </div>
            
            <h4 class="text-lg font-semibold text-white mb-2">مشكلة في تسجيل الدخول</h4>
            <p class="text-slate-300 text-sm mb-3">لا أستطيع تسجيل الدخول إلى لوحة التحكم منذ يومين، تظهر لي رسالة خطأ "Invalid credentials" مع أن كلمة المرور صحيحة.</p>
            
            <div class="flex items-center text-xs text-slate-500">
                <span>العميل</span>
                <span class="mx-2">•</span>
                <span>2025-01-20 14:30</span>
            </div>
        </div>
        
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mr-8">
            <div class="flex items-start">
                <div class="w-6 h-6 rounded-full bg-blue-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                    <span class="text-blue-400 text-xs">د</span>
                </div>
                <div class="flex-1">
                    <p class="text-slate-300 text-sm mb-2">مرحباً، جرب إعادة تعيين كلمة المرور من صفحة تسجيل الدخول. إذا استمرت المشكلة، أرسل لنا صورة للخطأ.</p>
                    <div class="flex items-center text-xs">
                        <span class="text-blue-400">فريق الدعم</span>
                        <span class="text-slate-500 mx-2">•</span>
                        <span class="text-slate-500">2025-01-20 15:45</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-900/50 rounded-lg p-4 mr-8">
            <div class="flex items-start">
                <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center ml-2 flex-shrink-0">
                    <span class="text-green-400 text-xs">أ</span>
                </div>
                <div class="flex-1">
                    <p class="text-slate-300 text-sm mb-2">تمت إعادة التعيين والآن أستطيع تسجيل الدخول، شكراً!</p>
                    <div class="flex items-center text-xs">
                        <span class="text-green-400">أحمد</span>
                        <span class="text-slate-500 mx-2">•</span>
                        <span class="text-slate-500">2025-01-20 16:20</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('ticket-modal').classList.remove('hidden');
}

function closeTicketModal() {
    document.getElementById('ticket-modal').classList.add('hidden');
}

function handleReply(e) {
    e.preventDefault();
    
    const message = document.getElementById('reply-message').value;
    if (!message.trim()) {
        showNotification('الرجاء كتابة الرد', 'warning');
        return;
    }
    
    showNotification('جاري إرسال الرد...', 'info');
    
    setTimeout(() => {
        document.getElementById('reply-message').value = '';
        closeTicketModal();
        showNotification('تم إرسال الرد بنجاح', 'success');
    }, 1000);
}

// =============================================
// إشعارات
// =============================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium animate-fade-in-up`;
    
    if (type === 'success') notification.classList.add('bg-green-600');
    else if (type === 'error') notification.classList.add('bg-red-600');
    else if (type === 'warning') notification.classList.add('bg-yellow-600');
    else notification.classList.add('bg-blue-600');
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('animate-fade-out-down');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// CSS للإضافات
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from { opacity: 0; transform: translate(-50%, 20px); }
        to { opacity: 1; transform: translate(-50%, 0); }
    }
    
    @keyframes fadeOutDown {
        from { opacity: 1; transform: translate(-50%, 0); }
        to { opacity: 0; transform: translate(-50%, 20px); }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }
    
    .animate-fade-out-down {
        animation: fadeOutDown 0.3s ease-out forwards;
    }
`;
document.head.appendChild(style);
</script>