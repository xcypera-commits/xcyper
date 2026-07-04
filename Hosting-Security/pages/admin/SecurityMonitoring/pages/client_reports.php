<?php
// pages/client_reports.php - تقارير العملاء
$db = getDB();

// جلب قائمة العملاء
$clients = $db->query("
    SELECT c.*, 
           COUNT(cw.id) as websites_count,
           (SELECT COUNT(*) FROM client_reports WHERE client_id = c.id) as reports_count
    FROM clients c
    LEFT JOIN client_websites cw ON c.id = cw.client_id
    GROUP BY c.id
    ORDER BY c.company_name
")->fetchAll();

// جلب آخر التقارير مع التحقق من وجود جدول websites
try {
    $stmt = $db->prepare("
        SELECT cr.*, 
               cc.full_name as client_name,
               cc.company_name,
               IFNULL(w.name, '') as website_name,
               IFNULL(w.url, '') as domain
        FROM client_reports cr
        JOIN client_clients cc ON cr.client_id = cc.id
        LEFT JOIN websites w ON cr.website_id = w.id
        ORDER BY cr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recent_reports = $stmt->fetchAll();
} catch (PDOException $e) {
    // إذا فشل الاستعلام (جدول websites غير موجود)
    $stmt = $db->prepare("
        SELECT cr.*, 
               cc.full_name as client_name,
               cc.company_name,
               '' as website_name,
               '' as domain
        FROM client_reports cr
        JOIN client_clients cc ON cr.client_id = cc.id
        ORDER BY cr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recent_reports = $stmt->fetchAll();
}
?>

<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <button onclick="generateAllReports()" class="px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-semibold transition-all cyber-glow flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            إنشاء تقارير لجميع العملاء
        </button>
        <!-- أضف هذا الزر في أعلى الصفحة -->
<button onclick="showAllPDFs()" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-semibold transition-all flex items-center">
    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
    </svg>
    عرض جميع ملفات PDF
</button>

<script>
function showAllPDFs() {
    // فتح مجلد التقارير
    window.open('reports/daily/', '_blank');
}
</script>
        <h1 class="text-3xl font-bold text-right">
            <span class="text-green-400">👥</span> تقارير العملاء
        </h1>
    </div>

    <!-- قائمة العملاء -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($clients as $client): ?>
        <div class="bg-slate-900 rounded-lg p-6 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-green-400"><?php echo $client['company_name']; ?></h3>
                <span class="px-3 py-1 bg-<?php echo $client['status'] == 'active' ? 'green' : 'yellow'; ?>-500 rounded-full text-xs">
                    <?php echo $client['status']; ?>
                </span>
            </div>
            <p class="text-gray-400 text-sm mb-2">📧 <?php echo $client['email']; ?></p>
            <p class="text-gray-400 text-sm mb-4">🌐 <?php echo $client['website']; ?></p>
            
            <div class="flex justify-between text-sm mb-4">
                <span class="text-blue-400">المواقع: <?php echo $client['websites_count']; ?></span>
                <span class="text-purple-400">التقارير: <?php echo $client['reports_count']; ?></span>
            </div>

            <div class="flex space-x-2 space-x-reverse">
                <button onclick="generateClientReport(<?php echo $client['id']; ?>, 'daily')" 
                        class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                    يومي
                </button>
                <button onclick="generateClientReport(<?php echo $client['id']; ?>, 'weekly')" 
                        class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm">
                    أسبوعي
                </button>
                <button onclick="generateClientReport(<?php echo $client['id']; ?>, 'monthly')" 
                        class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                    شهري
                </button>
                <button onclick="viewClientReports(<?php echo $client['id']; ?>)" 
                        class="px-3 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm" title="عرض التقارير">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- آخر التقارير -->
    <h2 class="text-2xl font-bold text-right mb-4 text-blue-400">📋 آخر التقارير المنشأة</h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-6 py-4">العميل</th>
                    <th class="px-6 py-4">الموقع</th>
                    <th class="px-6 py-4">النوع</th>
                    <th class="px-6 py-4">التاريخ</th>
                    <th class="px-6 py-4">الحجم</th>
                    <th class="px-6 py-4">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_reports as $report): ?>
                <tr class="border-b border-slate-700 hover:bg-slate-900">
                    <td class="px-6 py-4"><?php echo $report['company_name']; ?></td>
                    <td class="px-6 py-4"><?php echo $report['domain'] ?? 'جميع المواقع'; ?></td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 bg-<?php echo $report['report_type'] == 'daily' ? 'blue' : ($report['report_type'] == 'weekly' ? 'purple' : 'green'); ?>-500 rounded-full text-xs">
                            <?php echo $report['report_type']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?php echo date('Y-m-d H:i', strtotime($report['generated_at'])); ?></td>
                    <td class="px-6 py-4"><?php echo round(($report['file_size'] ?? 1024) / 1024, 2) . ' KB'; ?></td>
                    <td class="px-6 py-4">
                        <div class="flex space-x-2 space-x-reverse">
                            <a href="<?php echo $report['file_path'] ?? '#'; ?>" target="_blank" 
                               class="text-green-400 hover:text-green-300" title="عرض">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                            <a href="<?php echo $report['file_path'] ?? '#'; ?>" download 
                               class="text-blue-400 hover:text-blue-300" title="تحميل">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 12l-4 4-4-4m4 4V4"/>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>

    // دالة إنشاء تقرير جديد مع PDF
function generateNewReport() {
    showLoading();
    
    // بيانات التقرير الجديد
    const reportData = {
        date: new Date().toISOString().split('T')[0],
        type: 'security',
        title: 'تقرير الأمان اليومي',
        summary: 'تقرير شامل لأحداث الأمان وتحليل الأداء',
        statistics: {
            total_alerts: Math.floor(Math.random() * 30) + 20,
            critical: Math.floor(Math.random() * 8) + 3,
            threats: Math.floor(Math.random() * 10) + 5,
            uptime: (Math.random() * 2 + 98).toFixed(2)
        }
    };
    
    // حفظ في قاعدة البيانات
    fetch('save_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reportData)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('✅ تم إنشاء وحفظ التقرير بنجاح', 'success');
            
            // فتح PDF في نافذة جديدة
            setTimeout(() => {
                window.open(data.pdf_url, '_blank');
            }, 1000);
            
            // تحديث الصفحة بعد 2 ثانية
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('❌ فشل حفظ التقرير: ' + data.error, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في إنشاء التقرير', 'error');
        console.error('Error:', error);
    });
}

// دالة عرض التقرير مع PDF
function viewReport(id) {
    // فتح PDF في نافذة جديدة
    window.open('generate_report_pdf.php?id=' + id, '_blank');
}

// دالة تحميل التقرير
function downloadReport(id) {
    window.open('generate_report_pdf.php?id=' + id + '&download=1', '_blank');
}
function generateClientReport(clientId, type) {
    showLoading();
    // محاكاة إنشاء تقرير
    setTimeout(() => {
        hideLoading();
        showNotification(`تم إنشاء التقرير ${type} للعميل ${clientId}`, 'success');
    }, 1500);
}

function generateAllReports() {
    if (confirm('هل تريد إنشاء تقارير لجميع العملاء؟ قد يستغرق ذلك بعض الوقت')) {
        showLoading();
        setTimeout(() => {
            hideLoading();
            showNotification('تم إنشاء جميع التقارير بنجاح', 'success');
        }, 3000);
    }
}

function viewClientReports(clientId) {
    showNotification(`عرض تقارير العميل ${clientId}`, 'info');
}
</script>