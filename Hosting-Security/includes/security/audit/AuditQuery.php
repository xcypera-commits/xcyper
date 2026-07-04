<?php
/**
 * استعلامات التدقيق
 * Audit Query
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class AuditQuery {
    
    private $auditTrail;
    
    public function __construct() {
        $this->auditTrail = new AuditTrail();
    }
    
    /**
     * البحث في سجلات التدقيق
     */
    public function search($criteria = []) {
        $results = [];
        $months = $this->getMonthsToSearch($criteria['months'] ?? 1);
        
        foreach ($months as $month) {
            $events = $this->loadMonthFile($month, $criteria['type'] ?? null);
            $results = array_merge($results, $this->filterEvents($events, $criteria));
        }
        
        // ترتيب حسب الوقت
        usort($results, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // تطبيق الحد
        if (isset($criteria['limit'])) {
            $results = array_slice($results, 0, $criteria['limit']);
        }
        
        return $results;
    }
    
    /**
     * الحصول على الأشهر للبحث
     */
    private function getMonthsToSearch($monthsCount) {
        $months = [];
        for ($i = 0; $i < $monthsCount; $i++) {
            $months[] = date('Y-m', strtotime("-$i months"));
        }
        return $months;
    }
    
    /**
     * تحميل ملف شهر
     */
    private function loadMonthFile($month, $type = null) {
        $files = [];
        
        if ($type) {
            $files[] = LOGS_PATH . "audit/{$type}_{$month}.json";
        } else {
            $files = [
                LOGS_PATH . "audit/audit_{$month}.json",
                LOGS_PATH . "audit/security_{$month}.json",
                LOGS_PATH . "audit/access_{$month}.json"
            ];
        }
        
        $events = [];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $fileEvents = json_decode($content, true) ?: [];
                $events = array_merge($events, $fileEvents);
            }
        }
        
        return $events;
    }
    
    /**
     * تصفية الأحداث
     */
    private function filterEvents($events, $criteria) {
        return array_filter($events, function($event) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($key === 'months' || $key === 'limit' || $key === 'type') {
                    continue;
                }
                
                if ($key === 'user_id' && isset($event['user']['id'])) {
                    if ($event['user']['id'] != $value) {
                        return false;
                    }
                } elseif ($key === 'username' && isset($event['user']['username'])) {
                    if (stripos($event['user']['username'], $value) === false) {
                        return false;
                    }
                } elseif ($key === 'date_from') {
                    if (strtotime($event['timestamp']) < strtotime($value)) {
                        return false;
                    }
                } elseif ($key === 'date_to') {
                    if (strtotime($event['timestamp']) > strtotime($value)) {
                        return false;
                    }
                } elseif ($key === 'ip' && isset($event['user']['ip'])) {
                    if ($event['user']['ip'] !== $value) {
                        return false;
                    }
                } elseif (isset($event[$key]) && $event[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * الحصول على أحداث مستخدم
     */
    public function getUserActivity($userId, $days = 7) {
        return $this->search([
            'user_id' => $userId,
            'date_from' => date('Y-m-d', strtotime("-$days days")),
            'limit' => 1000
        ]);
    }
    
    /**
     * الحصول على أحداث أمنية
     */
    public function getSecurityEvents($days = 1, $level = null) {
        $criteria = [
            'date_from' => date('Y-m-d', strtotime("-$days days")),
            'type' => 'security'
        ];
        
        if ($level) {
            $criteria['level'] = $level;
        }
        
        return $this->search($criteria);
    }
    
    /**
     * الحصول على أحداث ملفات
     */
    public function getFileEvents($filename = null, $days = 7) {
        $criteria = [
            'category' => AuditTrail::CATEGORY_FILE,
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ];
        
        if ($filename) {
            $criteria['details.filename'] = $filename;
        }
        
        return $this->search($criteria);
    }
    
    /**
     * الحصول على إحصائيات
     */
    public function getStatistics($days = 30) {
        $events = $this->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $stats = [
            'total' => count($events),
            'by_category' => [],
            'by_level' => [],
            'by_user' => [],
            'daily' => [],
            'hourly' => []
        ];
        
        foreach ($events as $event) {
            // حسب الفئة
            $cat = $event['category'] ?? 'unknown';
            $stats['by_category'][$cat] = ($stats['by_category'][$cat] ?? 0) + 1;
            
            // حسب المستوى
            $level = $event['level'] ?? 'info';
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            // حسب المستخدم
            $user = $event['user']['username'] ?? 'system';
            $stats['by_user'][$user] = ($stats['by_user'][$user] ?? 0) + 1;
            
            // يومي
            $date = substr($event['timestamp'], 0, 10);
            $stats['daily'][$date] = ($stats['daily'][$date] ?? 0) + 1;
            
            // ساعي
            $hour = substr($event['timestamp'], 11, 2);
            $stats['hourly'][$hour] = ($stats['hourly'][$hour] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    /**
     * تصدير النتائج
     */
    public function export($events, $format = 'json') {
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($events);
            case 'json':
                return json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            case 'html':
                return $this->exportToHTML($events);
            default:
                return $events;
        }
    }
    
    /**
     * تصدير إلى CSV
     */
    private function exportToCSV($events) {
        $output = fopen('php://temp', 'r+');
        
        // رؤوس الأعمدة
        fputcsv($output, [
            'التاريخ', 'المستخدم', 'الفئة', 'الإجراء', 'المستوى', 'IP', 'التفاصيل'
        ]);
        
        foreach ($events as $event) {
            fputcsv($output, [
                $event['timestamp'],
                $event['user']['username'] ?? 'system',
                $event['category'] ?? '',
                $event['action'] ?? '',
                $event['level'] ?? '',
                $event['user']['ip'] ?? '',
                json_encode($event['details'] ?? [])
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * تصدير إلى HTML
     */
    private function exportToHTML($events) {
        $html = '<table border="1" style="border-collapse: collapse; width: 100%;">';
        $html .= '<thead><tr>';
        $html .= '<th>التاريخ</th><th>المستخدم</th><th>الفئة</th><th>الإجراء</th><th>المستوى</th><th>IP</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($events as $event) {
            $levelClass = $this->getLevelClass($event['level'] ?? '');
            $html .= "<tr class='$levelClass'>";
            $html .= "<td>{$event['timestamp']}</td>";
            $html .= "<td>{$event['user']['username']}</td>";
            $html .= "<td>{$event['category']}</td>";
            $html .= "<td>{$event['action']}</td>";
            $html .= "<td>{$event['level']}</td>";
            $html .= "<td>{$event['user']['ip']}</td>";
            $html .= "</tr>";
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    /**
     * الحصول على class للمستوى
     */
    private function getLevelClass($level) {
        switch ($level) {
            case 'critical':
                return 'style="background-color: #ffcccc;"';
            case 'error':
                return 'style="background-color: #ffe6cc;"';
            case 'warning':
                return 'style="background-color: #ffffcc;"';
            default:
                return '';
        }
    }
}
?>