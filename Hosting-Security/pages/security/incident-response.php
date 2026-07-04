<?php
require_once __DIR__ . '/../../includes/security/core/SecurityUtils.php';
require_once __DIR__ . '/../../includes/security/monitoring/AlertSystem.php';
require_once __DIR__ . '/../../includes/security/audit/ReportGenerator.php';

SecurityUtils::requireRole(['admin', 'incident_response']);

$alertSystem = new AlertSystem();
$reportGenerator = new ReportGenerator();

// الحوادث النشطة
$activeIncidents = $alertSystem->getActiveIncidents();
$recentIncidents = $alertSystem->getRecentIncidents(7);
$incidentStats = $alertSystem->getIncidentStatistics();

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $incidentId = $_POST['incident_id'] ?? '';
    
    switch ($action) {
        case 'acknowledge':
            $alertSystem->acknowledgeIncident($incidentId, $_SESSION['user_id']);
            break;
            
        case 'escalate':
            $alertSystem->escalateIncident($incidentId, $_POST['reason'] ?? '');
            break;
            
        case 'resolve':
            $alertSystem->resolveIncident($incidentId, $_POST['resolution'] ?? '');
            break;
            
        case 'assign':
            $alertSystem->assignIncident($incidentId, $_POST['assignee_id']);
            break;
    }
    
    header('Location: incident-response.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استجابة الحوادث - نظام الاستضافة</title>
    <link rel="stylesheet" href="/assets/css/security-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .incident-card {
            border-left: 5px solid;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .incident-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .incident-critical { border-left-color: #dc3545; }
        .incident-high { border-left-color: #fd7e14; }
        .incident-medium { border-left-color: #ffc107; }
        .incident-low { border-left-color: #28a745; }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #007bff;
            border: 2px solid white;
        }
        .playbook-step {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .playbook-step.completed {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        .playbook-step.current {
            background: #fff3cd;
            border-color: #ffeaa7;
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="incident-response">
    <div class="container-fluid">
        <!-- شريط الطوارئ -->
        <div class="emergency-bar" id="emergencyBar" 
             style="display: <?= count($activeIncidents) > 0 ? 'block' : 'none' ?>">
            <div class="emergency-content">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="emergency-text">
                    هناك <?= count($activeIncidents) ?> حادث نشط يتطلب الاهتمام
                </span>
                <button class="btn btn-emergency" onclick="showActiveIncidents()">
                    عرض الحوادث
                </button>
            </div>
        </div>

        <div class="row">
            <!-- الشريط الجانبي -->
            <div class="col-md-3">
                <!-- إحصائيات الحوادث -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> إحصائيات</h5>
                    </div>
                    <div class="card-body">
                        <div class="stat-item">
                            <span class="stat-label">حوادث نشطة</span>
                            <span class="stat-value text-danger">
                                <?= $incidentStats['active'] ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">تم حلها اليوم</span>
                            <span class="stat-value text-success">
                                <?= $incidentStats['resolved_today'] ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">متوسط وقت الحل</span>
                            <span class="stat-value">
                                <?= $incidentStats['avg_resolution_time'] ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">أكثر نوع تكراراً</span>
                            <span class="stat-value">
                                <?= $incidentStats['most_common_type'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- أدوات سريعة -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> أدوات سريعة</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-block btn-primary mb-2" 
                                onclick="newIncident()">
                            <i class="fas fa-plus"></i> حادث جديد
                        </button>
                        <button class="btn btn-block btn-info mb-2" 
                                onclick="generateReport()">
                            <i class="fas fa-file-alt"></i> تقرير أسبوعي
                        </button>
                        <button class="btn btn-block btn-warning mb-2" 
                                onclick="runDrill()">
                            <i class="fas fa-fire"></i> تمرين استجابة
                        </button>
                        <button class="btn btn-block btn-danger" 
                                onclick="declareEmergency()">
                            <i class="fas fa-siren-on"></i> حالة طوارئ
                        </button>
                    </div>
                </div>

                <!-- فرق الاستجابة -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> فرق الاستجابة</h5>
                    </div>
                    <div class="card-body">
                        <div class="team-list">
                            <div class="team-item">
                                <span class="team-name">الفريق الأحمر</span>
                                <span class="team-status online">مستعد</span>
                            </div>
                            <div class="team-item">
                                <span class="team-name">الفريق الأزرق</span>
                                <span class="team-status busy">مشغول</span>
                            </div>
                            <div class="team-item">
                                <span class="team-name">الفريق الأخضر</span>
                                <span class="team-status online">مستعد</span>
                            </div>
                            <div class="team-item">
                                <span class="team-name">دعم فني</span>
                                <span class="team-status away">غير متاح</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المحتوى الرئيسي -->
            <div class="col-md-9">
                <!-- حوادث نشطة -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="fas fa-exclamation-circle"></i> حوادث نشطة</h5>
                    </div>
                    <div class="card-body">
                        <div id="activeIncidents">
                            <?php if (empty($activeIncidents)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                لا توجد حوادث نشطة حالياً
                            </div>
                            <?php else: ?>
                            <?php foreach ($activeIncidents as $incident): ?>
                            <div class="incident-card incident-<?= $incident['severity'] ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="incident-title">
                                                <?= htmlspecialchars($incident['title']) ?>
                                                <span class="badge badge-<?= $incident['severity'] ?>">
                                                    <?= $incident['severity'] ?>
                                                </span>
                                            </h5>
                                            <p class="incident-description">
                                                <?= htmlspecialchars($incident['description']) ?>
                                            </p>
                                            <div class="incident-meta">
                                                <small>
                                                    <i class="fas fa-clock"></i>
                                                    بدأ: <?= $incident['start_time'] ?>
                                                </small>
                                                <small class="ml-3">
                                                    <i class="fas fa-user"></i>
                                                    مسؤول: <?= $incident['assigned_to'] ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="incident-actions">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="viewIncident(<?= $incident['id'] ?>)">
                                                    <i class="fas fa-eye"></i> عرض
                                                </button>
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="acknowledgeIncident(<?= $incident['id'] ?>)">
                                                    <i class="fas fa-check"></i> تأكيد
                                                </button>
                                                <button class="btn btn-sm btn-warning" 
                                                        onclick="escalateIncident(<?= $incident['id'] ?>)">
                                                    <i class="fas fa-level-up-alt"></i> تصعيد
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- حوادث حديثة -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> حوادث حديثة</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الوقت</th>
                                    <th>النوع</th>
                                    <th>الوصف</th>
                                    <th>الحالة</th>
                                    <th>مدة الحل</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentIncidents as $incident): ?>
                                <tr onclick="viewIncident(<?= $incident['id'] ?>)"
                                    style="cursor: pointer;">
                                    <td>
                                        <small><?= $incident['time'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $incident['type_class'] ?>">
                                            <?= $incident['type'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(substr($incident['description'], 0, 50)) ?>...
                                    </td>
                                    <td>
                                        <span class="status-badge badge-<?= $incident['status'] ?>">
                                            <?= $incident['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $incident['resolution_time'] ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- خطط الاستجابة (Playbooks) -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-book"></i> خطط الاستجابة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="playbook-card" onclick="loadPlaybook('ddos')">
                                    <div class="playbook-icon">
                                        <i class="fas fa-network-wired"></i>
                                    </div>
                                    <div class="playbook-info">
                                        <h6>هجوم DDoS</h6>
                                        <small>خطة استجابة لهجمات الحجب</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="playbook-card" onclick="loadPlaybook('data_breach')">
                                    <div class="playbook-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div class="playbook-info">
                                        <h6>اختراق بيانات</h6>
                                        <small>خطة استجابة لانتهاك البيانات</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="playbook-card" onclick="loadPlaybook('malware')">
                                    <div class="playbook-icon">
                                        <i class="fas fa-bug"></i>
                                    </div>
                                    <div class="playbook-info">
                                        <h6>برامج خبيثة</h6>
                                        <small>خطة استجابة للبرامج الضارة</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="playbook-card" onclick="loadPlaybook('ransomware')">
                                    <div class="playbook-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="playbook-info">
                                        <h6>برامج الفدية</h6>
                                        <small>خطة استجابة لبرامج الفدية</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal لحادث جديد -->
    <div class="modal fade" id="newIncidentModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تسجيل حادث جديد</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" action="/api/security/incident.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>عنوان الحادث *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>النوع *</label>
                            <select name="type" class="form-control" required>
                                <option value="">اختر نوع الحادث</option>
                                <option value="brute_force">هجوم Brute Force</option>
                                <option value="ddos">هجوم DDoS</option>
                                <option value="sql_injection">حقن SQL</option>
                                <option value="xss">هجوم XSS</option>
                                <option value="malware">برامج خبيثة</option>
                                <option value="data_breach">انتهاك بيانات</option>
                                <option value="insider_threat">تهديد داخلي</option>
                                <option value="physical">تهديد مادي</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>مستوى الخطورة *</label>
                            <select name="severity" class="form-control" required>
                                <option value="low">منخفض</option>
                                <option value="medium">متوسط</option>
                                <option value="high">مرتفع</option>
                                <option value="critical">حرج</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>الوصف التفصيلي *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>الأدلة (اختياري)</label>
                            <input type="file" name="evidence" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>تعيين إلى</label>
                            <select name="assignee" class="form-control">
                                <option value="">اختر مسؤولاً</option>
                                <option value="security_team">فريق الأمن</option>
                                <option value="network_team">فريق الشبكات</option>
                                <option value="response_team">فريق الاستجابة</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">تسجيل الحادث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal لعرض الحادث -->
    <div class="modal fade" id="incidentModal">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الحادث</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="incidentDetails">
                    <!-- سيتم تحميل المحتوى هنا -->
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/security/incident-response.js"></script>
    <script>
        function newIncident() {
            $('#newIncidentModal').modal('show');
        }

        function viewIncident(incidentId) {
            fetch(`/api/security/incident.php?action=get&id=${incidentId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('incidentDetails').innerHTML = `
                        <div class="row">
                            <div class="col-md-8">
                                <h3>${data.title}</h3>
                                <p>${data.description}</p>
                                
                                <div class="timeline">
                                    ${data.timeline.map(item => `
                                        <div class="timeline-item">
                                            <strong>${item.time}</strong>
                                            <p>${item.action}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>تفاصيل الحادث</h6>
                                        <table class="table table-sm">
                                            <tr><td>النوع:</td><td>${data.type}</td></tr>
                                            <tr><td>الخطورة:</td><td><span class="badge badge-${data.severity}">${data.severity}</span></td></tr>
                                            <tr><td>الحالة:</td><td>${data.status}</td></tr>
                                            <tr><td>بدأ في:</td><td>${data.start_time}</td></tr>
                                            <tr><td>مسؤول:</td><td>${data.assigned_to}</td></tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h6>خطة الاستجابة</h6>
                                        ${data.playbook_steps.map((step, index) => `
                                            <div class="playbook-step ${step.completed ? 'completed' : ''} ${step.current ? 'current' : ''}">
                                                <strong>خطوة ${index + 1}:</strong> ${step.description}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#incidentModal').modal('show');
                });
        }

        function acknowledgeIncident(incidentId) {
            if (confirm('هل تريد تأكيد استلام هذا الحادث؟')) {
                fetch('/api/security/incident.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'acknowledge',
                        incident_id: incidentId
                    })
                }).then(() => location.reload());
            }
        }

        function escalateIncident(incidentId) {
            const reason = prompt('أدخل سبب التصعيد:');
            if (reason) {
                fetch('/api/security/incident.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'escalate',
                        incident_id: incidentId,
                        reason: reason
                    })
                }).then(() => location.reload());
            }
        }

        function loadPlaybook(playbookType) {
            window.open(`/playbooks/${playbookType}.pdf`, '_blank');
        }

        function declareEmergency() {
            if (confirm('هل تريد تفعيل حالة الطوارئ؟ سيتم إشعار جميع الفرق.')) {
                fetch('/api/security/incident.php?action=declare_emergency')
                    .then(() => {
                        document.getElementById('emergencyBar').style.display = 'block';
                        alert('تم تفعيل حالة الطوارئ');
                    });
            }
        }

        // تحديث تلقائي كل 30 ثانية
        setInterval(() => {
            fetch('/api/security/incident.php?action=refresh')
                .then(response => response.json())
                .then(data => {
                    if (data.new_incidents > 0) {
                        document.getElementById('emergencyBar').style.display = 'block';
                    }
                });
        }, 30000);
    </script>
</body>
</html>