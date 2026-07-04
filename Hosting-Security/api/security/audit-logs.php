<?php
require_once __DIR__ . '/../../includes/security/core/SecurityUtils.php';
require_once __DIR__ . '/../../includes/security/audit/ComplianceChecker.php';
require_once __DIR__ . '/../../includes/database/AuditDatabase.php';

// التحقق من الصلاحيات
//SecurityUtils::requireRole(['admin', 'auditor']);

$db = new AuditDatabase();
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// الفلاتر
$filters = [
    'event_type' => $_GET['event_type'] ?? null,
    'user_id' => $_GET['user_id'] ?? null,
    'severity' => $_GET['severity'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

// استعلام الأحداث
$events = $db->getEventsByPeriod(
    new DateTime($filters['date_from'] ?? '-7 days'),
    new DateTime($filters['date_to'] ?? 'now'),
    array_filter($filters)
);

// إحصائيات
$stats = [
    'total_events' => count($events),
    'by_type' => array_count_values(array_column($events, 'event_type')),
    'by_severity' => array_count_values(array_column($events, 'severity')),
    'top_users' => array_slice(array_count_values(array_column($events, 'user_id')), 0, 10),
    'top_ips' => array_slice(array_count_values(array_column($events, 'user_ip')), 0, 10)
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجلات التدقيق - نظام الاستضافة</title>
    <link rel="stylesheet" href="/assets/css/security-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="security-dashboard">
    <div class="container-fluid">
        <!-- شريط التنقل -->
        <nav class="navbar navbar-security">
            <div class="navbar-brand">
                <i class="fas fa-shield-alt"></i>
                <span>سجلات التدقيق الأمني</span>
            </div>
            <div class="navbar-actions">
                <button class="btn btn-export" onclick="exportLogs()">
                    <i class="fas fa-download"></i> تصدير
                </button>
                <button class="btn btn-refresh" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt"></i> تحديث
                </button>
            </div>
        </nav>

        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-md-3">
                <div class="card filter-card">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> تصفية السجلات</h5>
                    </div>
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="form-group">
                                <label>نوع الحدث</label>
                                <select name="event_type" class="form-control">
                                    <option value="">الكل</option>
                                    <option value="login">تسجيل دخول</option>
                                    <option value="logout">تسجيل خروج</option>
                                    <option value="file_upload">رفع ملف</option>
                                    <option value="config_change">تغيير إعدادات</option>
                                    <option value="permission_change">تغيير صلاحيات</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>مستوى الخطورة</label>
                                <select name="severity" class="form-control">
                                    <option value="">الكل</option>
                                    <option value="low">منخفض</option>
                                    <option value="medium">متوسط</option>
                                    <option value="high">مرتفع</option>
                                    <option value="critical">حرج</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>من تاريخ</label>
                                <input type="date" name="date_from" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> تطبيق الفلاتر
                            </button>
                        </form>
                    </div>
                </div>

                <!-- إحصائيات -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> إحصائيات</h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label">إجمالي الأحداث</span>
                            <span class="stat-value"><?= number_format($stats['total_events']) ?></span>
                        </div>
                        <?php foreach ($stats['by_severity'] as $severity => $count): ?>
                        <div class="stat-item severity-<?= $severity ?>">
                            <span class="stat-label"><?= $severity ?></span>
                            <span class="stat-value"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- المحتوى الرئيسي -->
            <div class="col-md-9">
                <!-- أداة البحث -->
                <div class="card search-card">
                    <div class="card-body">
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   placeholder="بحث في السجلات (IP, اسم مستخدم, حدث...)">
                            <div class="input-group-append">
                                <button class="btn btn-search">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول السجلات -->
                <div class="card logs-card">
                    <div class="card-header">
                        <h5><i class="fas fa-list-alt"></i> سجلات التدقيق</h5>
                        <div class="card-actions">
                            <span class="badge badge-info">آخر تحديث: <?= date('H:i:s') ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-audit">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>المستخدم</th>
                                        <th>الحدث</th>
                                        <th>IP</th>
                                        <th>المصدر</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($events, $offset, $limit) as $event): ?>
                                    <tr class="severity-<?= $event['severity'] ?>">
                                        <td>
                                            <span class="timestamp">
                                                <?= date('Y-m-d H:i:s', strtotime($event['created_at'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($event['user_id']): ?>
                                            <span class="user-badge">
                                                <i class="fas fa-user"></i>
                                                <?= htmlspecialchars($event['user_id']) ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">غير معروف</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="event-type badge badge-<?= $event['severity'] ?>">
                                                <?= htmlspecialchars($event['event_type']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($event['action']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="ip-address">
                                                <?= htmlspecialchars($event['user_ip']) ?>
                                            </span>
                                            <?php if ($event['user_ip']): ?>
                                            <br>
                                            <small>
                                                <a href="#" onclick="lookupIP('<?= $event['user_ip'] ?>')">
                                                    <i class="fas fa-globe"></i> معلومات
                                                </a>
                                            </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="user-agent">
                                                <?= htmlspecialchars(substr($event['user_agent'], 0, 50)) ?>...
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge badge badge-<?= $event['status'] ?>">
                                                <?= $event['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info" 
                                                        onclick="viewEventDetails(<?= $event['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning" 
                                                        onclick="exportEvent(<?= $event['id'] ?>)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <?php if (in_array($event['severity'], ['high', 'critical'])): ?>
                                                <button class="btn btn-danger" 
                                                        onclick="reportIncident(<?= $event['id'] ?>)">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- الترقيم الصفحي -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="fas fa-chevron-right"></i> السابق
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= ceil(count($events) / $limit); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($page < ceil(count($events) / $limit)): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        التالي <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- التقارير -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie"></i> توزيع الأحداث</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="eventChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card report-card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line"></i> نشاط اليوم</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal لعرض التفاصيل -->
    <div class="modal fade" id="eventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الحدث</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="eventDetails">
                    <!-- سيتم تحميل المحتوى هنا عبر AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/security/audit-logs.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // مخطط توزيع الأحداث
        const eventCtx = document.getElementById('eventChart').getContext('2d');
        new Chart(eventCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($stats['by_type'])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['by_type'])) ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
                }]
            }
        });

        // مخطط النشاط
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'أحداث',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#36A2EB',
                    fill: false
                }]
            }
        });

        function viewEventDetails(eventId) {
            fetch(`/api/security/audit.php?action=get_event&id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('eventDetails').innerHTML = `
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                    $('#eventModal').modal('show');
                });
        }

        function lookupIP(ip) {
            window.open(`https://www.abuseipdb.com/check/${ip}`, '_blank');
        }

        function exportLogs() {
            const filters = new URLSearchParams(document.getElementById('filterForm'));
            window.location.href = `/api/security/audit.php?action=export&${filters}`;
        }
    </script>
</body>
</html>