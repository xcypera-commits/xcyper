<?php
/**
 * مولد التقارير
 * Report Generator
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class ReportGenerator {
    
    private $auditQuery;
    private $complianceChecker;
    private $logger;
    
    public function __construct() {
        $this->auditQuery = new AuditQuery();
        $this->complianceChecker = new ComplianceChecker();
        $this->logger = new SecurityLogger();
    }
    
    /**
     * إنشاء تقرير أمني
     */
    public function generateSecurityReport($period = 30, $format = 'pdf') {
        $stats = $this->auditQuery->getStatistics($period);
        $securityEvents = $this->auditQuery->getSecurityEvents($period);
        
        $report = [
            'title' => 'التقرير الأمني الشهري',
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => "آخر $period يوم",
            'statistics' => $stats,
            'security_events' => array_slice($securityEvents, 0, 50),
            'compliance' => $this->complianceChecker->generateComplianceReport(),
            'recommendations' => $this->generateRecommendations($stats, $securityEvents)
        ];
        
        return $this->formatReport($report, $format);
    }
    
    /**
     * إنشاء تقرير نشاط المستخدمين
     */
    public function generateUserActivityReport($userId = null, $period = 30, $format = 'pdf') {
        if ($userId) {
            $events = $this->auditQuery->getUserActivity($userId, $period);
            $title = "تقرير نشاط المستخدم";
        } else {
            $events = $this->auditQuery->search(['date_from' => date('Y-m-d', strtotime("-$period days"))]);
            $title = "تقرير نشاط جميع المستخدمين";
        }
        
        // تحليل النشاط حسب المستخدم
        $userStats = [];
        foreach ($events as $event) {
            $user = $event['user']['username'] ?? 'system';
            if (!isset($userStats[$user])) {
                $userStats[$user] = [
                    'total' => 0,
                    'by_action' => [],
                    'last_seen' => $event['timestamp']
                ];
            }
            $userStats[$user]['total']++;
            $action = $event['action'] ?? 'unknown';
            $userStats[$user]['by_action'][$action] = ($userStats[$user]['by_action'][$action] ?? 0) + 1;
            if (strtotime($event['timestamp']) > strtotime($userStats[$user]['last_seen'])) {
                $userStats[$user]['last_seen'] = $event['timestamp'];
            }
        }
        
        $report = [
            'title' => $title,
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => "آخر $period يوم",
            'total_events' => count($events),
            'unique_users' => count($userStats),
            'user_statistics' => $userStats,
            'recent_activity' => array_slice($events, 0, 100)
        ];
        
        return $this->formatReport($report, $format);
    }
    
    /**
     * إنشاء تقرير الملفات
     */
    public function generateFileReport($period = 30, $format = 'pdf') {
        $fileEvents = $this->auditQuery->getFileEvents(null, $period);
        
        $stats = [
            'total_uploads' => 0,
            'total_downloads' => 0,
            'total_deletions' => 0,
            'by_user' => [],
            'by_type' => []
        ];
        
        foreach ($fileEvents as $event) {
            $action = $event['action'];
            $user = $event['user']['username'] ?? 'system';
            $filename = $event['details']['filename'] ?? 'unknown';
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if ($action === 'upload') {
                $stats['total_uploads']++;
            } elseif ($action === 'download') {
                $stats['total_downloads']++;
            } elseif ($action === 'delete') {
                $stats['total_deletions']++;
            }
            
            $stats['by_user'][$user] = ($stats['by_user'][$user] ?? 0) + 1;
            $stats['by_type'][$ext] = ($stats['by_type'][$ext] ?? 0) + 1;
        }
        
        $report = [
            'title' => 'تقرير الملفات',
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => "آخر $period يوم",
            'statistics' => $stats,
            'recent_files' => array_slice($fileEvents, 0, 50)
        ];
        
        return $this->formatReport($report, $format);
    }
    
    /**
     * إنشاء توصيات
     */
    private function generateRecommendations($stats, $securityEvents) {
        $recommendations = [];
        
        // توصيات بناءً على الإحصائيات
        if (($stats['by_level']['critical'] ?? 0) > 5) {
            $recommendations[] = 'يوجد عدد كبير من الأحداث الحرجة - يجب مراجعة النظام فوراً';
        }
        
        if (($stats['by_level']['error'] ?? 0) > 20) {
            $recommendations[] = 'زيادة في عدد الأخطاء - يوصى بمراجعة سجلات النظام';
        }
        
        // توصيات بناءً على الأحداث الأمنية
        $threatTypes = [];
        foreach ($securityEvents as $event) {
            $type = $event['details']['threat_type'] ?? 'unknown';
            $threatTypes[$type] = ($threatTypes[$type] ?? 0) + 1;
        }
        
        if (($threatTypes['brute_force'] ?? 0) > 10) {
            $recommendations[] = 'هجمات قوة عمياء متكررة - يوصى بتعزيز حماية المصادقة';
        }
        
        if (($threatTypes['malware'] ?? 0) > 0) {
            $recommendations[] = 'تم اكتشاف برمجيات خبيثة - يوصى بفحص جميع الملفات';
        }
        
        return $recommendations;
    }
    
    /**
     * تنسيق التقرير
     */
    private function formatReport($report, $format) {
        switch ($format) {
            case 'json':
                return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'html':
                return $this->formatHTML($report);
                
            case 'pdf':
                return $this->formatPDF($report);
                
            default:
                return $report;
        }
    }
    
    /**
     * تنسيق HTML
     */
    private function formatHTML($report) {
        $html = '<!DOCTYPE html><html dir="rtl"><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($report['title']) . '</title>';
        $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            .report-header { background: #f5f5f5; padding: 10px; margin-bottom: 20px; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
            th { background: #4CAF50; color: white; }
            .critical { background: #ffcccc; }
            .warning { background: #ffffcc; }
            .stats { display: flex; gap: 20px; margin-bottom: 20px; }
            .stat-box { background: #e0e0e0; padding: 15px; border-radius: 5px; flex: 1; }
        </style>';
        $html .= '</head><body>';
        
        $html .= '<div class="report-header">';
        $html .= '<h1>' . htmlspecialchars($report['title']) . '</h1>';
        $html .= '<p>تاريخ التقرير: ' . $report['generated_at'] . '</p>';
        $html .= '<p>الفترة: ' . $report['period'] . '</p>';
        $html .= '</div>';
        
        // إحصائيات سريعة
        if (isset($report['statistics'])) {
            $stats = $report['statistics'];
            $html .= '<div class="stats">';
            $html .= '<div class="stat-box">إجمالي الأحداث: ' . ($stats['total'] ?? 0) . '</div>';
            $html .= '<div class="stat-box">الأحداث الحرجة: ' . ($stats['by_level']['critical'] ?? 0) . '</div>';
            $html .= '<div class="stat-box">الأحداث الأمنية: ' . count($report['security_events'] ?? []) . '</div>';
            $html .= '</div>';
        }
        
        // جدول الأحداث الأخيرة
        if (isset($report['recent_activity']) || isset($report['security_events'])) {
            $events = $report['recent_activity'] ?? $report['security_events'] ?? [];
            
            $html .= '<h2>أحدث الأحداث</h2>';
            $html .= '<table>';
            $html .= '<tr><th>التاريخ</th><th>المستخدم</th><th>الفئة</th><th>الإجراء</th><th>المستوى</th><th>التفاصيل</th></tr>';
            
            foreach ($events as $event) {
                $levelClass = '';
                if (($event['level'] ?? '') === 'critical') $levelClass = 'critical';
                elseif (($event['level'] ?? '') === 'warning') $levelClass = 'warning';
                
                $html .= "<tr class='$levelClass'>";
                $html .= '<td>' . htmlspecialchars($event['timestamp'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($event['user']['username'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($event['category'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($event['action'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($event['level'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars(json_encode($event['details'] ?? [])) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        // توصيات
        if (isset($report['recommendations'])) {
            $html .= '<h2>التوصيات</h2>';
            $html .= '<ul>';
            foreach ($report['recommendations'] as $rec) {
                $html .= '<li>' . htmlspecialchars($rec) . '</li>';
            }
            $html .= '</ul>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * تنسيق PDF (محاكاة)
     */
    private function formatPDF($report) {
        // في الإنتاج، استخدم مكتبة مثل mPDF أو TCPDF
        $this->logger->log('report', 'PDF report generated', ['title' => $report['title']]);
        
        // محاكاة إرجاع PDF
        return $this->formatHTML($report);
    }
    
    /**
     * حفظ التقرير
     */
    public function saveReport($report, $filename) {
        $reportsDir = LOGS_PATH . 'reports/';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0750, true);
        }
        
        $path = $reportsDir . $filename . '_' . date('Ymd_His') . '.html';
        file_put_contents($path, $report);
        
        $this->logger->log('report', 'Report saved', ['path' => $path]);
        
        return $path;
    }
    
    /**
     * إرسال التقرير بالبريد
     */
    public function emailReport($report, $email, $subject) {
        $html = is_string($report) ? $report : $this->formatHTML($report);
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        mail($email, $subject, $html, $headers);
        
        $this->logger->log('report', 'Report emailed', ['email' => $email, 'subject' => $subject]);
        
        return true;
    }
}
?>