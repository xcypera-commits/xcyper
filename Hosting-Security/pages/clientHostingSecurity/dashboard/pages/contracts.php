<?php
// =============================================
// client-unit/pages/contracts.php
// صفحة العقود والموافقات
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

// =============================================
// معالجة العمليات
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'sign_contract':
                // توقيع عقد
                $stmt = $db->prepare("
                    UPDATE client_contracts 
                    SET signed_by_client = 1, signed_at = NOW(), status = 'signed'
                    WHERE id = ? AND client_id = ?
                ");
                $stmt->execute([$_POST['contract_id'], $current_client['id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم توقيع العقد بنجاح';
                break;
                
            case 'reject_contract':
                // رفض عقد
                $stmt = $db->prepare("
                    UPDATE client_contracts SET status = 'cancelled' 
                    WHERE id = ? AND client_id = ?
                ");
                $stmt->execute([$_POST['contract_id'], $current_client['id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم رفض العقد';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات
// =============================================
try {
    // العقود
    $contracts = $db->prepare("
        SELECT c.*, p.project_name, p.project_code
        FROM client_contracts c
        LEFT JOIN client_projects p ON c.project_id = p.id
        WHERE c.client_id = ?
        ORDER BY 
            CASE c.status
                WHEN 'under_review' THEN 1
                WHEN 'signed' THEN 2
                WHEN 'active' THEN 3
                ELSE 4
            END,
            c.created_at DESC
    ");
    $contracts->execute([$current_client['id']]);
    $contracts = $contracts->fetchAll();
    
    // إحصائيات العقود
    $stats = [
        'total' => count($contracts),
        'under_review' => 0,
        'pending_signature' => 0,
        'active' => 0,
        'expired' => 0
    ];
    
    foreach ($contracts as $contract) {
        if ($contract['status'] == 'under_review') $stats['under_review']++;
        if ($contract['status'] == 'signed' && !$contract['signed_by_client']) $stats['pending_signature']++;
        if ($contract['status'] == 'active') $stats['active']++;
        if ($contract['status'] == 'expired') $stats['expired']++;
    }
    
    // المشاريع النشطة (لربط العقود)
    $projects = $db->prepare("
        SELECT id, project_name, project_code 
        FROM client_projects 
        WHERE client_id = ? AND status IN ('in_progress', 'pending', 'contract_pending')
        ORDER BY project_name
    ");
    $projects->execute([$current_client['id']]);
    $projects = $projects->fetchAll();
    
} catch (Exception $e) {
    $contracts = [];
    $projects = [];
    $stats = [
        'total' => 0,
        'under_review' => 0,
        'pending_signature' => 0,
        'active' => 0,
        'expired' => 0
    ];
}
?>

<!-- ============================================= -->
<!-- الهيدر -->
<!-- ============================================= -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 mb-8 border border-slate-700">
    <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:50px_50px]"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/50 to-transparent"></div>
    
    <div class="relative z-10">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="w-16 h-16 rounded-2xl bg-purple-500/20 flex items-center justify-center backdrop-blur-sm border border-purple-500/30">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-l from-purple-400 to-pink-400 bg-clip-text text-transparent">العقود والموافقات</h1>
                    <p class="text-slate-400 mt-1">إدارة ومتابعة العقود والاتفاقيات</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- إحصائيات سريعة -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-purple-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">إجمالي العقود</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 group-hover:bg-purple-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-yellow-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">قيد المراجعة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['under_review']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-yellow-500/10 group-hover:bg-yellow-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">بانتظار التوقيع</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['pending_signature']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 group-hover:bg-blue-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-green-500/50 transition-all group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-400">عقود نشطة</p>
                <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['active']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-500/10 group-hover:bg-green-500/20 transition-all flex items-center justify-center">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- قائمة العقود -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
    <!-- رأس الجدول -->
    <div class="grid grid-cols-12 gap-4 p-4 bg-slate-900/50 border-b border-slate-700 text-sm font-medium text-slate-400">
        <div class="col-span-3">العقد</div>
        <div class="col-span-2">المشروع</div>
        <div class="col-span-2">القيمة</div>
        <div class="col-span-2">المدة</div>
        <div class="col-span-2">الحالة</div>
        <div class="col-span-1"></div>
    </div>
    
    <!-- محتوى الجدول -->
    <div class="divide-y divide-slate-700">
        <?php if (empty($contracts)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-slate-400">لا توجد عقود حالياً</p>
        </div>
        <?php else: ?>
            <?php foreach ($contracts as $contract): ?>
            <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
                <!-- العقد -->
                <div class="col-span-3">
                    <div class="font-medium text-white"><?php echo htmlspecialchars($contract['title']); ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?php echo $contract['contract_code']; ?></div>
                </div>
                
                <!-- المشروع -->
                <div class="col-span-2">
                    <div class="text-white"><?php echo $contract['project_name'] ?? 'عام'; ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?php echo $contract['project_code'] ?? ''; ?></div>
                </div>
                
                <!-- القيمة -->
                <div class="col-span-2">
                    <div class="text-white font-medium"><?php echo number_format($contract['value'], 2); ?> ر.س</div>
                </div>
                
                <!-- المدة -->
                <div class="col-span-2">
                    <?php if ($contract['start_date'] && $contract['end_date']): ?>
                    <div class="text-white text-sm"><?php echo date('Y-m-d', strtotime($contract['start_date'])); ?></div>
                    <div class="text-xs text-slate-500">إلى <?php echo date('Y-m-d', strtotime($contract['end_date'])); ?></div>
                    <?php else: ?>
                    <span class="text-slate-500">-</span>
                    <?php endif; ?>
                </div>
                
                <!-- الحالة -->
                <div class="col-span-2">
                    <?php
                    $status_colors = [
                        'draft' => 'bg-slate-500/20 text-slate-400',
                        'sent' => 'bg-blue-500/20 text-blue-400',
                        'under_review' => 'bg-yellow-500/20 text-yellow-400',
                        'signed' => 'bg-green-500/20 text-green-400',
                        'active' => 'bg-emerald-500/20 text-emerald-400',
                        'expired' => 'bg-red-500/20 text-red-400',
                        'cancelled' => 'bg-slate-500/20 text-slate-400'
                    ];
                    
                    $status_texts = [
                        'draft' => 'مسودة',
                        'sent' => 'مرسل',
                        'under_review' => 'قيد المراجعة',
                        'signed' => 'موقع',
                        'active' => 'نشط',
                        'expired' => 'منتهي',
                        'cancelled' => 'ملغي'
                    ];
                    
                    $color = $status_colors[$contract['status']] ?? 'bg-slate-500/20 text-slate-400';
                    $text = $status_texts[$contract['status']] ?? $contract['status'];
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                        <?php echo $text; ?>
                    </span>
                    
                    <?php if ($contract['status'] == 'signed' && !$contract['signed_by_client']): ?>
                    <div class="text-xs text-yellow-400 mt-1">بانتظار توقيعك</div>
                    <?php endif; ?>
                </div>
                
                <!-- الإجراءات -->
                <div class="col-span-1 flex items-center justify-end space-x-2 space-x-reverse">
                    <button onclick="viewContract(<?php echo $contract['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="عرض">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    
                    <button onclick="downloadContract(<?php echo $contract['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="تحميل">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </button>
                    
                    <?php if ($contract['status'] == 'signed' && !$contract['signed_by_client']): ?>
                    <button onclick="signContract(<?php echo $contract['id']; ?>)" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded-lg text-xs font-medium">
                        توقيع
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة عرض العقد -->
<!-- ============================================= -->
<div id="view-contract-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-slate-700">
        <div class="flex items-center justify-between mb-6 sticky top-0 bg-slate-800 py-2">
            <button onclick="closeViewModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">تفاصيل العقد</h3>
        </div>
        
        <div id="contract-details" class="space-y-6">
            <!-- المحتوى سيتم تحميله عبر JavaScript -->
            <div class="text-center text-slate-400 py-12">
                جاري تحميل بيانات العقد...
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
let currentContractId = null;

// عرض العقد
function viewContract(id) {
    currentContractId = id;
    
    // محاكاة جلب بيانات العقد
    const mockContract = {
        id: id,
        code: 'CON-HOST-001',
        title: 'عقد استضافة موقع التجارة الإلكترونية',
        project: 'موقع التجارة الإلكترونية',
        type: 'استضافة',
        value: '15000.00',
        start_date: '2024-01-20',
        end_date: '2025-01-20',
        status: 'active',
        terms: `
            <div class="space-y-4">
                <p>هذا العقد يوثق اتفاقية استضافة موقع التجارة الإلكترونية بين شركة التقنية المتطورة (العميل) وشركة الاستضافة السحابي (مزود الخدمة).</p>
                
                <h4 class="font-bold text-white mt-4">بنود الاتفاقية:</h4>
                <ul class="list-disc list-inside space-y-2 text-slate-300">
                    <li>مدة العقد: سنة واحدة قابلة للتجديد</li>
                    <li>قيمة العقد: 15,000 ريال سعودي</li>
                    <li>شروط الدفع: 50% عند التوقيع و 50% بعد 6 أشهر</li>
                    <li>ضمان الخدمة: 99.9% وقت تشغيل</li>
                    <li>الدعم الفني: 24/7 على مدار الأسبوع</li>
                </ul>
                
                <h4 class="font-bold text-white mt-4">التزامات مزود الخدمة:</h4>
                <ul class="list-disc list-inside space-y-2 text-slate-300">
                    <li>توفير مساحة تخزين 50GB</li>
                    <li>نسخ احتياطي يومي للبيانات</li>
                    <li>تحديثات أمنية دورية</li>
                    <li>مراقبة الأداء على مدار الساعة</li>
                </ul>
            </div>
        `
    };
    
    document.getElementById('contract-details').innerHTML = `
        <div class="bg-slate-900 rounded-lg p-6 space-y-4">
            <div class="flex items-center justify-between pb-4 border-b border-slate-700">
                <span class="text-sm text-slate-500">${mockContract.code}</span>
                <h2 class="text-xl font-bold text-white">${mockContract.title}</h2>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-slate-500">المشروع</p>
                    <p class="text-white">${mockContract.project}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">نوع العقد</p>
                    <p class="text-white">${mockContract.type}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">قيمة العقد</p>
                    <p class="text-white font-bold">${Number(mockContract.value).toLocaleString()} ر.س</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">مدة العقد</p>
                    <p class="text-white">من ${mockContract.start_date} إلى ${mockContract.end_date}</p>
                </div>
            </div>
            
            <div class="pt-4 border-t border-slate-700">
                <h3 class="font-bold text-white mb-3">بنود العقد</h3>
                ${mockContract.terms}
            </div>
            
            <div class="flex items-center justify-end space-x-3 space-x-reverse pt-4 border-t border-slate-700">
                <button onclick="closeViewModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm">
                    إغلاق
                </button>
                <button onclick="downloadContract(${id})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                    تحميل العقد
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('view-contract-modal').classList.remove('hidden');
}

function closeViewModal() {
    document.getElementById('view-contract-modal').classList.add('hidden');
}

// تحميل العقد
function downloadContract(id) {
    showNotification('جاري تحميل العقد...', 'info');
    setTimeout(() => {
        showNotification('تم تحميل العقد بنجاح', 'success');
    }, 1500);
}

// توقيع العقد
function signContract(id) {
    if (confirm('هل أنت متأكد من توقيع هذا العقد؟')) {
        const formData = new FormData();
        formData.append('action', 'sign_contract');
        formData.append('contract_id', id);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        });
    }
}

// رفض العقد
function rejectContract(id) {
    if (confirm('هل أنت متأكد من رفض هذا العقد؟')) {
        const formData = new FormData();
        formData.append('action', 'reject_contract');
        formData.append('contract_id', id);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        });
    }
}

// الإشعارات
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 left-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ' + 
        (type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600');
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>