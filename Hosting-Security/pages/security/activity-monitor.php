<?php
require_once __DIR__ . '/../../includes/security/core/SecurityUtils.php';
require_once __DIR__ . '/../../includes/security/monitoring/ActivityMonitor.php';

SecurityUtils::requireRole(['admin', 'security_monitor']);

$monitor = new ActivityMonitor();
$timeRange = $_GET['range'] ?? 'hour'; // hour, day, week, month

// بيانات المراقبة الحية
$liveData = $monitor->getLiveActivity($timeRange);
$alerts = $monitor->getActiveAlerts();
$topUsers = $monitor->getTopActiveUsers();
$systemHealth = $monitor->getSystemHealth();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراقبة النشاط - نظام الاستضافة</title>
    <link rel="stylesheet" href="/assets/css/security-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .live-indicator {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .activity-stream {
            max-height: 500px;
            overflow-y: auto;
        }
        .activity-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        .activity-item:hover {
            background: #f8f9fa;
        }
        .metric-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body class="activity-monitor">
    <div class="container-fluid">
        <!-- شريط الحالة -->
        <div class="status-bar">
            <div class="status-item">
                <span class="status-indicator live-indicator"></span>
                <span class="status-text">مراقبة حية</span>
            </div>
            <div class="status-item">
                <i class="fas fa-server"></i>
                <span>السيرفر: <?= gethostname() ?></span>
            </div>
            <div class="status-item">
                <i class="fas fa-clock"></i>
                <span id="liveClock"><?= date('H:i:s') ?></span>
            </div>
            <div class="status-item">
                <i class="fas fa-users"></i>
                <span>مستخدمون نشطون: <span id="activeUsers"><?= count($topUsers) ?></span></span>
            </div>
        </div>

        <div class="row">
            <!-- المقاييس الرئيسية -->
            <div class="col-12">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card bg-primary text-white">
                            <div class="metric-value" id="totalRequests">
                                <?= $liveData['total_requests'] ?>
                            </div>
                            <div class="metric-label">طلبات/ساعة</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card bg-success text-white">
                            <div class="metric-value" id="successRate">
                                <?= $liveData['success_rate'] ?>%
                            </div>
                            <div class="metric-label">نسبة النجاح</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card bg-warning text-white">
                            <div class="metric-value" id="threatsDetected">
                                <?= $liveData['threats_detected'] ?>
                            </div>
                            <div class="metric-label">تهديدات</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card bg-danger text-white">
                            <div class="metric-value" id="activeAlerts">
                                <?= count($alerts) ?>
                            </div>
                            <div class="metric-label">تنبيهات نشطة</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تيار النشاط الحي -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-stream"></i> تيار النشاط الحي</h5>
                        <div class="time-range">
                            <select id="timeRange" class="form-control form-control-sm">
                                <option value="hour" <?= $timeRange == 'hour' ? 'selected' : '' ?>>آخر ساعة</option>
                                <option value="day" <?= $timeRange == 'day' ? 'selected' : '' ?>>آخر 24 ساعة</option>
                                <option value="week" <?= $timeRange == 'week' ? 'selected' : '' ?>>آخر أسبوع</option>
                                <option value="month" <?= $timeRange == 'month' ? 'selected' : '' ?>>آخر شهر</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="activity-stream" id="activityStream">
                            <?php foreach ($liveData['activities'] as $activity): ?>
                            <div class="activity-item">
                                <div class="row">
                                    <div class="col-2">
                                        <span class="badge badge-<?= $activity['severity'] ?>">
                                            <?= $activity['type'] ?>
                                        </span>
                                    </div>
                                    <div class="col-3">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($activity['user']) ?>
                                    </div>
                                    <div class="col-4">
                                        <?= htmlspecialchars($activity['action']) ?>
                                    </div>
                                    <div class="col-3 text-left">
                                        <small class="text-muted">
                                            <?= date('H:i:s', strtotime($activity['timestamp'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- خريطة النشاط الجغرافي -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-globe"></i> خريطة النشاط الجغرافي</h5>
                    </div>
                    <div class="card-body">
                        <div id="activityMap" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- التنبيهات والمستخدمين -->
            <div class="col-md-4">
                <!-- التنبيهات النشطة -->
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> التنبيهات النشطة</h5>
                    </div>
                    <div class="card-body">
                        <div id="alertsList">
                            <?php foreach ($alerts as $alert): ?>
                            <div class="alert-item alert-<?= $alert['severity'] ?>">
                                <div class="alert-header">
                                    <strong><?= htmlspecialchars($alert['title']) ?></strong>
                                    <span class="alert-time"><?= $alert['time'] ?></span>
                                </div>
                                <div class="alert-body">
                                    <?= htmlspecialchars($alert['message']) ?>
                                </div>
                                <div class="alert-actions">
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="acknowledgeAlert('<?= $alert['id'] ?>')">
                                        تأكيد
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="escalateAlert('<?= $alert['id'] ?>')">
                                        تصعيد
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- المستخدمون الأكثر نشاطاً -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> المستخدمون النشطون</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>الإجراءات</th>
                                    <th>آخر نشاط</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topUsers as $user): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-circle"></i>
                                        <?= htmlspecialchars($user['username']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= $user['activity_count'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('H:i', strtotime($user['last_activity'])) ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- صحة النظام -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-heartbeat"></i> صحة النظام</h5>
                    </div>
                    <div class="card-body">
                        <div class="health-item">
                            <span>وحدة المعالجة المركزية</span>
                            <div class="progress">
                                <div class="progress-bar" 
                                     style="width: <?= $systemHealth['cpu'] ?>%">
                                    <?= $systemHealth['cpu'] ?>%
                                </div>
                            </div>
                        </div>
                        <div class="health-item">
                            <span>الذاكرة</span>
                            <div class="progress">
                                <div class="progress-bar" 
                                     style="width: <?= $systemHealth['memory'] ?>%">
                                    <?= $systemHealth['memory'] ?>%
                                </div>
                            </div>
                        </div>
                        <div class="health-item">
                            <span>التخزين</span>
                            <div class="progress">
                                <div class="progress-bar" 
                                     style="width: <?= $systemHealth['storage'] ?>%">
                                    <?= $systemHealth['storage'] ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الـ WebSocket للبيانات الحية -->
    <script>
        const ws = new WebSocket('wss://monitor.hostingsystem.com/live');
        
        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            updateLiveData(data);
        };

        ws.onopen = function() {
            console.log('Connected to live monitoring');
        };

        function updateLiveData(data) {
            // تحديث المقاييس
            document.getElementById('totalRequests').textContent = data.total_requests;
            document.getElementById('successRate').textContent = data.success_rate + '%';
            document.getElementById('threatsDetected').textContent = data.threats_detected;
            document.getElementById('activeAlerts').textContent = data.active_alerts;
            document.getElementById('activeUsers').textContent = data.active_users;

            // تحديث تيار النشاط
            if (data.new_activity) {
                const activityStream = document.getElementById('activityStream');
                const activityItem = createActivityItem(data.new_activity);
                activityStream.prepend(activityItem);
                
                // حصر النشاطات لـ 100 عنصر
                if (activityStream.children.length > 100) {
                    activityStream.removeChild(activityStream.lastChild);
                }
            }

            // تحديث التنبيهات
            if (data.new_alert) {
                addNewAlert(data.new_alert);
            }

            // تحديث الوقت الحي
            document.getElementById('liveClock').textContent = new Date().toLocaleTimeString();
        }

        function createActivityItem(activity) {
            const div = document.createElement('div');
            div.className = 'activity-item';
            div.innerHTML = `
                <div class="row">
                    <div class="col-2">
                        <span class="badge badge-${activity.severity}">
                            ${activity.type}
                        </span>
                    </div>
                    <div class="col-3">
                        <i class="fas fa-user"></i>
                        ${activity.user}
                    </div>
                    <div class="col-4">
                        ${activity.action}
                    </div>
                    <div class="col-3 text-left">
                        <small class="text-muted">
                            ${new Date().toLocaleTimeString()}
                        </small>
                    </div>
                </div>
            `;
            return div;
        }

        function addNewAlert(alert) {
            const alertsList = document.getElementById('alertsList');
            const alertItem = document.createElement('div');
            alertItem.className = `alert-item alert-${alert.severity}`;
            alertItem.innerHTML = `
                <div class="alert-header">
                    <strong>${alert.title}</strong>
                    <span class="alert-time">${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="alert-body">
                    ${alert.message}
                </div>
                <div class="alert-actions">
                    <button class="btn btn-sm btn-primary" 
                            onclick="acknowledgeAlert('${alert.id}')">
                        تأكيد
                    </button>
                    <button class="btn btn-sm btn-danger" 
                            onclick="escalateAlert('${alert.id}')">
                        تصعيد
                    </button>
                </div>
            `;
            alertsList.prepend(alertItem);
        }

        // تحديث النطاق الزمني
        document.getElementById('timeRange').addEventListener('change', function() {
            const range = this.value;
            window.location.href = `?range=${range}`;
        });

        // تحديث تلقائي كل 30 ثانية
        setInterval(() => {
            fetch('/api/security/monitoring.php?action=refresh')
                .then(response => response.json())
                .then(data => updateLiveData(data));
        }, 30000);
    </script>

    <!-- خريطة Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // تهيئة الخريطة
        const map = L.map('activityMap').setView([24.7136, 46.6753], 3); // مركز السعودية
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // إضافة markers للنشاط
        <?php foreach ($liveData['geo_activities'] as $geo): ?>
        L.marker([<?= $geo['lat'] ?>, <?= $geo['lon'] ?>])
            .addTo(map)
            .bindPopup(`
                <strong>${<?= json_encode($geo['city']) ?>}</strong><br>
                نشاطات: ${<?= $geo['count'] ?>}<br>
                آخر نشاط: ${<?= json_encode($geo['last_activity']) ?>}
            `);
        <?php endforeach; ?>
    </script>
</body>
</html>