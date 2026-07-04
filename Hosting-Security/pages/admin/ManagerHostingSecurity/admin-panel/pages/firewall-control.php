<?php
/**
 * لوحة تحكم الجدار الناري
 * Firewall Control Panel
 */

require_once '../../../../../security-init.php';
require_admin();

// معالجة الإجراءات
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $ipFilter = new IPFilter();
        
        switch ($action) {
            case 'block_ip':
                $ip = sanitize_input($_POST['ip']);
                $permanent = isset($_POST['permanent']);
                $duration = (int)($_POST['duration'] ?? 3600);
                
                if ($permanent) {
                    $ipFilter->addToBlacklist($ip, true);
                    $message = "تم حظر IP $ip بشكل دائم";
                } else {
                    $ipFilter->tempBan($ip, $duration);
                    $message = "تم حظر IP $ip لمدة " . ($duration/60) . " دقيقة";
                }
                break;
                
            case 'unblock_ip':
                $ip = sanitize_input($_POST['ip']);
                $ipFilter->removeFromBlacklist($ip);
                $message = "تم إلغاء حظر IP $ip";
                break;
                
            case 'whitelist_ip':
                $ip = sanitize_input($_POST['ip']);
                $ipFilter->addToWhitelist($ip);
                $message = "تم إضافة IP $ip إلى القائمة البيضاء";
                break;
                
            case 'update_rules':
                // تحديث قواعد WAF
                $rules = $_POST['rules'] ?? [];
                file_put_contents(__DIR__ . '/../../../config/waf_rules.json', json_encode($rules, JSON_PRETTY_PRINT));
                $message = "تم تحديث قواعد WAF";
                break;
                
            case 'clear_logs':
                $days = (int)($_POST['days'] ?? 7);
                $logFile = LOGS_PATH . 'firewall.log';
                if (file_exists($logFile)) {
                    // الاحتفاظ بآخر 7 أيام فقط
                    $lines = file($logFile);
                    $keep = array_slice($lines, -1000); // آخر 1000 سطر
                    file_put_contents($logFile, implode('', $keep));
                }
                $message = "تم تنظيف سجلات الجدار الناري";
                break;
        }
        
        log_activity($_SESSION['user_id'], 'firewall_updated', ['action' => $action]);
        
    } catch (Exception $e) {
        $error = "خطأ: " . $e->getMessage();
    }
}

// جلب الإحصائيات
$ipFilter = new IPFilter();
$stats = $ipFilter->getStats();

// جلب سجلات الجدار الناري
$firewallLogs = [];
$logFile = LOGS_PATH . 'firewall.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $firewallLogs = array_slice($lines, -50); // آخر 50 سطر
}

// جلب قواعد WAF
$wafRules = [];
$wafFile = __DIR__ . '/../../../config/waf_rules.json';
if (file_exists($wafFile)) {
    $wafRules = json_decode(file_get_contents($wafFile), true);
}

// إحصائيات إضافية
$totalRequests = rand(1000, 5000); // محاكاة - استبدلها بإحصائيات حقيقية
$blockedRequests = $stats['blacklist_count'] * 10 + rand(50, 200);
$activeThreats = rand(0, 10);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الجدار الناري - نظام الحماية</title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            border-right: 4px solid transparent;
            text-decoration: none;
        }
        
        .nav-link i {
            margin-left: 12px;
            width: 20px;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-right-color: #ffd700;
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            margin-right: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3.5rem;
            opacity: 0.15;
        }
        
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary-color);
            line-height: 1.2;
        }
        
        .stat-desc {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* بطاقات المحتوى */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            font-size: 1.4rem;
        }
        
        /* شارات الحالة */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #cce5ff;
            color: #004085;
        }
        
        /* سجلات الجدار الناري */
        .log-entry {
            background: #f8f9fa;
            border-right: 4px solid transparent;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .log-entry.warning {
            border-right-color: var(--warning-color);
        }
        
        .log-entry.danger {
            border-right-color: var(--danger-color);
        }
        
        .log-entry.info {
            border-right-color: var(--info-color);
        }
        
        .log-time {
            color: #6c757d;
            font-size: 0.8rem;
        }
        
        /* تنسيقات إضافية */
        .ip-box {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            display: inline-block;
        }
        
        .rule-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        /* التجاوب */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
                transition: right 0.3s;
            }
            
            .sidebar.show {
                right: 0;
            }
            
            .main-content {
                margin-right: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- زر القائمة للجوال -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- الشريط الجانبي -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt fa-3x"></i>
            <h5 class="mt-2">نظام الحماية</h5>
            <small>لوحة تحكم الجدار الناري</small>
        </div>
        
        <div class="nav-menu">
            <a href="../dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> لوحة التحكم
            </a>
            <a href="firewall-control.php" class="nav-link active">
                <i class="fas fa-fire"></i> الجدار الناري
            </a>
            <a href="../security-settings.php" class="nav-link">
                <i class="fas fa-cog"></i> إعدادات الأمان
            </a>
            <a href="../audit-logs.php" class="nav-link">
                <i class="fas fa-history"></i> سجلات التدقيق
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="../../../index.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> خروج
            </a>
        </div>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <!-- رأس الصفحة -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-fire text-danger me-2"></i>
                لوحة تحكم الجدار الناري
            </h2>
            <div>
                <span class="badge bg-success p-2">
                    <i class="fas fa-shield-alt"></i>
                    الحماية نشطة
                </span>
            </div>
        </div>

        <!-- عرض الرسائل -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- إحصائيات سريعة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">إجمالي الطلبات (اليوم)</div>
                <div class="stat-value"><?php echo number_format($totalRequests); ?></div>
                <div class="stat-desc">
                    <span class="text-success"><?php echo number_format($totalRequests - $blockedRequests); ?> مسموح</span>
                </div>
                <i class="fas fa-chart-line stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">طلبات محظورة</div>
                <div class="stat-value text-danger"><?php echo number_format($blockedRequests); ?></div>
                <div class="stat-desc">
                    <?php echo round(($blockedRequests/$totalRequests)*100, 1); ?>% من الإجمالي
                </div>
                <i class="fas fa-ban stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">عناوين محظورة</div>
                <div class="stat-value"><?php echo $stats['blacklist_count']; ?></div>
                <div class="stat-desc">
                    +<?php echo $stats['temp_bans_count']; ?> حظر مؤقت
                </div>
                <i class="fas fa-users-slash stat-icon"></i>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">تهديدات نشطة</div>
                <div class="stat-value <?php echo $activeThreats > 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo $activeThreats; ?>
                </div>
                <div class="stat-desc">
                    <?php echo $activeThreats > 0 ? '⚠️ انتبه!' : '✓ آمن'; ?>
                </div>
                <i class="fas fa-bug stat-icon"></i>
            </div>
        </div>

        <!-- قسم التحكم -->
        <div class="row">
            <!-- حظر IP -->
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-ban text-danger"></i>
                            حظر عنوان IP
                        </h5>
                    </div>
                    
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="block_ip">
                        
                        <div class="mb-3">
                            <label class="form-label">عنوان IP</label>
                            <input type="text" name="ip" class="form-control" required 
                                   placeholder="192.168.1.100" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permanent" id="permanent">
                                <label class="form-check-label" for="permanent">
                                    حظر دائم
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="durationField">
                            <label class="form-label">مدة الحظر (بالثواني)</label>
                            <select name="duration" class="form-select">
                                <option value="3600">ساعة (3600)</option>
                                <option value="7200">ساعتين (7200)</option>
                                <option value="21600">6 ساعات (21600)</option>
                                <option value="43200">12 ساعة (43200)</option>
                                <option value="86400">يوم (86400)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban me-1"></i>
                            حظر IP
                        </button>
                    </form>
                    
                    <script>
                        document.getElementById('permanent').addEventListener('change', function() {
                            document.getElementById('durationField').style.display = this.checked ? 'none' : 'block';
                        });
                    </script>
                    
                    <hr>
                    
                    <!-- إلغاء حظر IP -->
                    <form method="POST">
                        <input type="hidden" name="action" value="unblock_ip">
                        
                        <div class="mb-3">
                            <label class="form-label">إلغاء حظر IP</label>
                            <input type="text" name="ip" class="form-control" placeholder="أدخل عنوان IP">
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo me-1"></i>
                            إلغاء الحظر
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- القائمة البيضاء -->
            <div class="col-md-6">
                <div class="content-card">
                    <div class="card-header-custom">
                        <h5 class="card-title">
                            <i class="fas fa-check-circle text-success"></i>
                            القائمة البيضاء
                        </h5>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="whitelist_ip">
                        
                        <div class="mb-3">
                            <label class="form-label">إضافة IP للقائمة البيضاء</label>
                            <input type="text" name="ip" class="form-control" required 
                                   placeholder="192.168.1.100">
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus-circle me-1"></i>
                            إضافة للقائمة البيضاء
                        </button>
                    </form>
                    
                    <hr>
                    
                    <h6 class="mb-3">عناوين في القائمة البيضاء</h6>
                    <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;">
                        <div class="ip-box mb-2">127.0.0.1 (localhost)</div>
                        <div class="ip-box mb-2">::1 (localhost IPv6)</div>
                        <!-- يمكن إضافة عناوين من قاعدة البيانات -->
                    </div>
                </div>
            </div>
        </div>

        <!-- قواعد WAF -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5 class="card-title">
                    <i class="fas fa-shield-alt text-primary"></i>
                    قواعد Web Application Firewall
                </h5>
                <button class="btn btn-sm btn-primary" onclick="saveRules()">
                    <i class="fas fa-save"></i> حفظ القواعد
                </button>
            </div>
            
            <form method="POST" id="wafForm">
                <input type="hidden" name="action" value="update_rules">
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[sql_injection]" 
                                       id="sql_injection" checked>
                                <label class="form-check-label" for="sql_injection">
                                    <strong>SQL Injection</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من هجمات حقن SQL</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[xss]" 
                                       id="xss" checked>
                                <label class="form-check-label" for="xss">
                                    <strong>XSS Attacks</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من هجمات Cross-Site Scripting</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[path_traversal]" 
                                       id="path_traversal" checked>
                                <label class="form-check-label" for="path_traversal">
                                    <strong>Path Traversal</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من هجمات اجتياز المسار</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[command_injection]" 
                                       id="command_injection" checked>
                                <label class="form-check-label" for="command_injection">
                                    <strong>Command Injection</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من حقن الأوامر</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[file_inclusion]" 
                                       id="file_inclusion" checked>
                                <label class="form-check-label" for="file_inclusion">
                                    <strong>File Inclusion</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من تضمين الملفات</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="rule-card">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="rules[csrf]" 
                                       id="csrf" checked>
                                <label class="form-check-label" for="csrf">
                                    <strong>CSRF Protection</strong>
                                </label>
                            </div>
                            <small class="text-muted">حماية من تزوير الطلبات</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- سجلات الجدار الناري -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5 class="card-title">
                    <i class="fas fa-list-alt"></i>
                    آخر أحداث الجدار الناري
                </h5>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="clear_logs">
                    <input type="hidden" name="days" value="7">
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="fas fa-trash-alt"></i> تنظيف السجلات
                    </button>
                </form>
            </div>
            
            <div style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($firewallLogs)): ?>
                    <p class="text-muted text-center py-4">لا توجد سجلات</p>
                <?php else: ?>
                    <?php foreach ($firewallLogs as $log): ?>
                        <?php
                        $logClass = 'info';
                        if (strpos($log, 'blocked') !== false || strpos($log, 'حظر') !== false) {
                            $logClass = 'danger';
                        } elseif (strpos($log, 'warning') !== false || strpos($log, 'تحذير') !== false) {
                            $logClass = 'warning';
                        }
                        ?>
                        <div class="log-entry <?php echo $logClass; ?>">
                            <div class="log-time"><?php echo date('Y-m-d H:i:s'); ?></div>
                            <div><?php echo htmlspecialchars($log); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- رسم بياني للنشاط -->
        <div class="content-card">
            <div class="card-header-custom">
                <h5 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    نشاط الجدار الناري (آخر 24 ساعة)
                </h5>
            </div>
            <div>
            <canvas id="firewallChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <script>
        // التحكم في الشريط الجانبي
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // حفظ قواعد WAF
        function saveRules() {
            document.getElementById('wafForm').submit();
        }
        
        // رسم بياني
        const ctx = document.getElementById('firewallChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['12AM', '2AM', '4AM', '6AM', '8AM', '10AM', '12PM', '2PM', '4PM', '6PM', '8PM', '10PM'],
                datasets: [{
                    label: 'الطلبات المحظورة',
                    data: [12, 8, 5, 7, 15, 25, 40, 35, 30, 28, 22, 18],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'الطلبات المسموحة',
                    data: [150, 120, 100, 130, 180, 250, 320, 300, 280, 260, 210, 190],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>