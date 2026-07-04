<?php
// =============================================
// client-unit/pages/billing.php
// صفحة الفواتير والمدفوعات
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
            case 'pay_invoice':
                // دفع فاتورة
                $stmt = $db->prepare("
                    UPDATE client_invoices 
                    SET status = 'paid', paid_date = NOW()
                    WHERE id = ? AND client_id = ?
                ");
                $stmt->execute([$_POST['invoice_id'], $current_client['id']]);
                
                $response['success'] = true;
                $response['message'] = 'تم دفع الفاتورة بنجاح';
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
    // الفواتير
    $invoices = $db->prepare("
        SELECT i.*, p.project_name
        FROM client_invoices i
        LEFT JOIN client_projects p ON i.project_id = p.id
        WHERE i.client_id = ?
        ORDER BY 
            CASE i.status
                WHEN 'pending' THEN 1
                WHEN 'overdue' THEN 2
                WHEN 'paid' THEN 3
                ELSE 4
            END,
            i.due_date ASC
    ");
    $invoices->execute([$current_client['id']]);
    $invoices = $invoices->fetchAll();
    
    // المدفوعات
    $payments = $db->prepare("
        SELECT p.*, i.invoice_code, i.title as invoice_title
        FROM client_payments p
        LEFT JOIN client_invoices i ON p.invoice_id = i.id
        WHERE p.client_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $payments->execute([$current_client['id']]);
    $payments = $payments->fetchAll();
    
    // ملخص الفواتير
    $summary = [
        'total_pending' => 0,
        'total_overdue' => 0,
        'total_paid' => 0,
        'amount_pending' => 0,
        'amount_overdue' => 0,
        'amount_paid' => 0,
        'next_due' => null,
        'next_amount' => 0
    ];
    
    foreach ($invoices as $invoice) {
        if ($invoice['status'] == 'pending') {
            $summary['total_pending']++;
            $summary['amount_pending'] += $invoice['total_amount'];
            if (!$summary['next_due'] || strtotime($invoice['due_date']) < strtotime($summary['next_due'])) {
                $summary['next_due'] = $invoice['due_date'];
                $summary['next_amount'] = $invoice['total_amount'];
            }
        } elseif ($invoice['status'] == 'overdue') {
            $summary['total_overdue']++;
            $summary['amount_overdue'] += $invoice['total_amount'];
        } elseif ($invoice['status'] == 'paid') {
            $summary['total_paid']++;
            $summary['amount_paid'] += $invoice['total_amount'];
        }
    }
    
    // رصيد العميل
    $balance = $current_client['balance'] ?? 0;
    
} catch (Exception $e) {
    $invoices = [];
    $payments = [];
    $summary = [
        'total_pending' => 0,
        'total_overdue' => 0,
        'total_paid' => 0,
        'amount_pending' => 0,
        'amount_overdue' => 0,
        'amount_paid' => 0,
        'next_due' => null,
        'next_amount' => 0
    ];
    $balance = 0;
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
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 flex items-center justify-center backdrop-blur-sm border border-emerald-500/30">
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-l from-emerald-400 to-green-400 bg-clip-text text-transparent">الفواتير والمدفوعات</h1>
                    <p class="text-slate-400 mt-1">إدارة فواتيرك وتتبع المدفوعات</p>
                </div>
            </div>
            
            <div class="text-left">
                <p class="text-sm text-slate-400">الرصيد المتاح</p>
                <p class="text-2xl font-bold text-emerald-400"><?php echo number_format($balance, 2); ?> ر.س</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- بطاقات الملخص -->
<!-- ============================================= -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <!-- الفواتير المستحقة -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-yellow-500/50 transition-all group">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-400">فواتير مستحقة</p>
            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 group-hover:bg-yellow-500/20 transition-all flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $summary['total_pending']; ?></p>
        <p class="text-sm text-yellow-400"><?php echo number_format($summary['amount_pending'], 2); ?> ر.س</p>
    </div>
    
    <!-- فواتير متأخرة -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-red-500/50 transition-all group">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-400">فواتير متأخرة</p>
            <div class="w-10 h-10 rounded-xl bg-red-500/10 group-hover:bg-red-500/20 transition-all flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $summary['total_overdue']; ?></p>
        <p class="text-sm text-red-400"><?php echo number_format($summary['amount_overdue'], 2); ?> ر.س</p>
    </div>
    
    <!-- فواتير مدفوعة -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-green-500/50 transition-all group">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-400">فواتير مدفوعة</p>
            <div class="w-10 h-10 rounded-xl bg-green-500/10 group-hover:bg-green-500/20 transition-all flex items-center justify-center">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $summary['total_paid']; ?></p>
        <p class="text-sm text-green-400"><?php echo number_format($summary['amount_paid'], 2); ?> ر.س</p>
    </div>
    
    <!-- أقرب استحقاق -->
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-6 border border-slate-700 hover:border-blue-500/50 transition-all group">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-slate-400">أقرب استحقاق</p>
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 group-hover:bg-blue-500/20 transition-all flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-white mb-1"><?php echo $summary['next_due'] ? date('d/m', strtotime($summary['next_due'])) : '-'; ?></p>
        <p class="text-sm text-blue-400"><?php echo number_format($summary['next_amount'], 2); ?> ر.س</p>
    </div>
</div>

<!-- ============================================= -->
<!-- جدول الفواتير -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden mb-8">
    <div class="p-4 bg-slate-900/50 border-b border-slate-700">
        <h3 class="text-lg font-semibold text-white">الفواتير</h3>
    </div>
    
    <!-- رأس الجدول -->
    <div class="grid grid-cols-12 gap-4 p-4 bg-slate-900/30 border-b border-slate-700 text-sm font-medium text-slate-400">
        <div class="col-span-2">رقم الفاتورة</div>
        <div class="col-span-3">الوصف</div>
        <div class="col-span-2">المشروع</div>
        <div class="col-span-1">المبلغ</div>
        <div class="col-span-1">تاريخ الإصدار</div>
        <div class="col-span-1">تاريخ الاستحقاق</div>
        <div class="col-span-1">الحالة</div>
        <div class="col-span-1"></div>
    </div>
    
    <!-- محتوى الجدول -->
    <div class="divide-y divide-slate-700">
        <?php if (empty($invoices)): ?>
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-700/50 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-slate-400">لا توجد فواتير</p>
        </div>
        <?php else: ?>
            <?php foreach ($invoices as $invoice): ?>
            <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
                <!-- رقم الفاتورة -->
                <div class="col-span-2">
                    <span class="text-white font-medium"><?php echo $invoice['invoice_code']; ?></span>
                </div>
                
                <!-- الوصف -->
                <div class="col-span-3">
                    <span class="text-white"><?php echo htmlspecialchars($invoice['title']); ?></span>
                </div>
                
                <!-- المشروع -->
                <div class="col-span-2">
                    <span class="text-slate-300"><?php echo $invoice['project_name'] ?? 'عام'; ?></span>
                </div>
                
                <!-- المبلغ -->
                <div class="col-span-1">
                    <span class="text-white font-medium"><?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
                
                <!-- تاريخ الإصدار -->
                <div class="col-span-1">
                    <span class="text-slate-300"><?php echo date('Y-m-d', strtotime($invoice['issue_date'])); ?></span>
                </div>
                
                <!-- تاريخ الاستحقاق -->
                <div class="col-span-1">
                    <span class="text-slate-300 <?php echo $invoice['status'] == 'overdue' ? 'text-red-400' : ''; ?>">
                        <?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?>
                    </span>
                </div>
                
                <!-- الحالة -->
                <div class="col-span-1">
                    <?php
                    $status_colors = [
                        'draft' => 'bg-slate-500/20 text-slate-400',
                        'sent' => 'bg-blue-500/20 text-blue-400',
                        'pending' => 'bg-yellow-500/20 text-yellow-400',
                        'paid' => 'bg-green-500/20 text-green-400',
                        'overdue' => 'bg-red-500/20 text-red-400',
                        'cancelled' => 'bg-slate-500/20 text-slate-400'
                    ];
                    
                    $status_texts = [
                        'draft' => 'مسودة',
                        'sent' => 'مرسلة',
                        'pending' => 'مستحقة',
                        'paid' => 'مدفوعة',
                        'overdue' => 'متأخرة',
                        'cancelled' => 'ملغاة'
                    ];
                    
                    $color = $status_colors[$invoice['status']] ?? 'bg-slate-500/20 text-slate-400';
                    $text = $status_texts[$invoice['status']] ?? $invoice['status'];
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                        <?php echo $text; ?>
                    </span>
                </div>
                
                <!-- الإجراءات -->
                <div class="col-span-1 flex items-center justify-end space-x-2 space-x-reverse">
                    <button onclick="viewInvoice(<?php echo $invoice['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="عرض">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    
                    <button onclick="downloadInvoice(<?php echo $invoice['id']; ?>)" class="p-2 hover:bg-slate-600 rounded-lg transition-all" title="تحميل">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </button>
                    
                    <?php if ($invoice['status'] == 'pending' || $invoice['status'] == 'overdue'): ?>
                    <button onclick="payInvoice(<?php echo $invoice['id']; ?>)" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-xs font-medium">
                        دفع
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- آخر المدفوعات -->
<!-- ============================================= -->
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700 overflow-hidden">
    <div class="p-4 bg-slate-900/50 border-b border-slate-700">
        <h3 class="text-lg font-semibold text-white">آخر المدفوعات</h3>
    </div>
    
    <?php if (empty($payments)): ?>
    <div class="p-8 text-center">
        <p class="text-slate-400">لا توجد مدفوعات سابقة</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-slate-700">
        <?php foreach ($payments as $payment): ?>
        <div class="grid grid-cols-12 gap-4 p-4 hover:bg-slate-700/50 transition-all">
            <div class="col-span-2">
                <span class="text-white font-medium"><?php echo $payment['payment_code']; ?></span>
            </div>
            <div class="col-span-3">
                <span class="text-white"><?php echo htmlspecialchars($payment['invoice_title'] ?? 'دفعة'); ?></span>
            </div>
            <div class="col-span-2">
                <span class="text-white font-medium"><?php echo number_format($payment['amount'], 2); ?> ر.س</span>
            </div>
            <div class="col-span-2">
                <span class="text-slate-300 <?php echo $invoice['status'] == 'overdue' ? 'text-red-400' : ''; ?>">
    <?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?>
</span>
            </div>
            <div class="col-span-2">
                <span class="text-slate-300"><?php echo $payment['payment_method']; ?></span>
            </div>
            <div class="col-span-1">
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                    مكتمل
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================= -->
<!-- نافذة دفع الفاتورة -->
<!-- ============================================= -->
<div id="payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4 border border-slate-700">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closePaymentModal()" class="text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-white">دفع الفاتورة</h3>
        </div>
        
        <div id="payment-details" class="mb-6 p-4 bg-slate-900 rounded-lg">
            <!-- التفاصيل ستتحمل عبر JavaScript -->
        </div>
        
        <form id="payment-form" onsubmit="handlePayment(event)" class="space-y-4">
            <input type="hidden" id="payment-invoice-id" name="invoice_id">
            
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">طريقة الدفع</label>
                <select id="payment-method" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg focus:outline-none focus:border-blue-500 text-white">
                    <option value="card">بطاقة ائتمان</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="cash">نقداً</option>
                </select>
            </div>
            
            <button type="submit" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 rounded-lg font-medium transition-all">
                تأكيد الدفع
            </button>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
let currentInvoiceId = null;

// عرض الفاتورة
function viewInvoice(id) {
    // محاكاة عرض الفاتورة
    showNotification('جاري تحميل الفاتورة...', 'info');
    setTimeout(() => {
        window.open('#', '_blank');
    }, 500);
}

// تحميل الفاتورة
function downloadInvoice(id) {
    showNotification('جاري تحميل الفاتورة...', 'info');
    setTimeout(() => {
        showNotification('تم تحميل الفاتورة', 'success');
    }, 1500);
}

// فتح نافذة الدفع
function payInvoice(id) {
    currentInvoiceId = id;
    
    // البحث عن الفاتورة في البيانات
    const invoices = <?php echo json_encode($invoices); ?>;
    const invoice = invoices.find(i => i.id == id);
    
    if (invoice) {
        document.getElementById('payment-details').innerHTML = `
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">رقم الفاتورة:</span>
                    <span class="text-white font-medium">${invoice.invoice_code}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">الوصف:</span>
                    <span class="text-white">${invoice.title}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">المبلغ:</span>
                    <span class="text-white font-bold text-lg">${Number(invoice.total_amount).toLocaleString()} ر.س</span>
                </div>
            </div>
        `;
        document.getElementById('payment-invoice-id').value = id;
        document.getElementById('payment-modal').classList.remove('hidden');
    }
}

function closePaymentModal() {
    document.getElementById('payment-modal').classList.add('hidden');
}

function handlePayment(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'pay_invoice');
    formData.append('invoice_id', document.getElementById('payment-invoice-id').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closePaymentModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    });
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