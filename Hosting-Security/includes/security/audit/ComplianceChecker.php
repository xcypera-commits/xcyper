<?php
/**
 * مدقق الامتثال
 * Compliance Checker
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class ComplianceChecker {
    
    private $auditQuery;
    private $logger;
    
    // معايير الامتثال
    const STANDARD_GDPR = 'gdpr';
    const STANDARD_ISO27001 = 'iso27001';
    const STANDARD_PCI_DSS = 'pci_dss';
    const STANDARD_HIPAA = 'hipaa';
    const STANDARD_SOX = 'sox';
    
    public function __construct() {
        $this->auditQuery = new AuditQuery();
        $this->logger = new SecurityLogger();
    }
    
    /**
     * فحص الامتثال لمعيار معين
     */
    public function checkCompliance($standard, $period = 30) {
        switch ($standard) {
            case self::STANDARD_GDPR:
                return $this->checkGDPRCompliance($period);
            case self::STANDARD_ISO27001:
                return $this->checkISO27001Compliance($period);
            case self::STANDARD_PCI_DSS:
                return $this->checkPCIDSSCompliance($period);
            case self::STANDARD_HIPAA:
                return $this->checkHIPAACompliance($period);
            case self::STANDARD_SOX:
                return $this->checkSOXCompliance($period);
            default:
                return ['error' => 'Standard not supported'];
        }
    }
    
    /**
     * فحص الامتثال للائحة العامة لحماية البيانات (GDPR)
     */
    private function checkGDPRCompliance($days) {
        $events = $this->auditQuery->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $findings = [];
        $violations = [];
        
        // فحص موافقة المستخدمين
        $consentEvents = array_filter($events, function($e) {
            return $e['action'] === 'consent_given' || $e['action'] === 'consent_withdrawn';
        });
        
        if (count($consentEvents) == 0) {
            $violations[] = 'لا توجد سجلات موافقة المستخدمين';
        }
        
        // فحص حق الوصول للبيانات
        $accessEvents = array_filter($events, function($e) {
            return $e['action'] === 'data_access_request';
        });
        
        $findings['data_access_requests'] = count($accessEvents);
        
        // فحص حذف البيانات
        $deleteEvents = array_filter($events, function($e) {
            return $e['action'] === 'data_deletion';
        });
        
        $findings['data_deletions'] = count($deleteEvents);
        
        // فحص خروقات البيانات
        $breaches = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_SECURITY && 
                   $e['level'] === AuditTrail::LEVEL_CRITICAL;
        });
        
        if (count($breaches) > 0) {
            $violations[] = 'تم اكتشاف خروقات أمنية يجب الإبلاغ عنها';
        }
        
        return [
            'standard' => 'GDPR',
            'period_days' => $days,
            'compliant' => count($violations) === 0,
            'findings' => $findings,
            'violations' => $violations,
            'recommendations' => $this->getGDPRRecommendations($violations)
        ];
    }
    
    /**
     * فحص الامتثال لمعيار ISO 27001
     */
    private function checkISO27001Compliance($days) {
        $events = $this->auditQuery->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $findings = [];
        $violations = [];
        
        // فحص التحكم في الوصول (A.9)
        $accessEvents = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_AUTHORIZATION;
        });
        
        if (count($accessEvents) == 0) {
            $violations[] = 'لا توجد سجلات كافية للتحكم في الوصول';
        }
        
        // فحص إدارة الحوادث (A.16)
        $incidentEvents = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_SECURITY;
        });
        
        $findings['security_incidents'] = count($incidentEvents);
        
        // فحص استمرارية الأعمال (A.17)
        $backupEvents = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_BACKUP;
        });
        
        if (count($backupEvents) < 5) {
            $violations[] = 'نسخ احتياطية غير كافية';
        }
        
        // فحص التدقيق (A.12.7)
        $auditEvents = array_filter($events, function($e) {
            return strpos($e['action'], 'audit') !== false;
        });
        
        $findings['audit_events'] = count($auditEvents);
        
        return [
            'standard' => 'ISO 27001',
            'period_days' => $days,
            'compliant' => count($violations) < 2,
            'findings' => $findings,
            'violations' => $violations,
            'recommendations' => $this->getISORecommendations($violations)
        ];
    }
    
    /**
     * فحص الامتثال لمعيار PCI DSS
     */
    private function checkPCIDSSCompliance($days) {
        $events = $this->auditQuery->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $findings = [];
        $violations = [];
        
        // فحص تشفير البيانات (المتطلب 3)
        $encryptionEvents = array_filter($events, function($e) {
            return $e['category'] === 'encryption';
        });
        
        $findings['encryption_events'] = count($encryptionEvents);
        
        // فحص جدران الحماية (المتطلب 1)
        $firewallEvents = array_filter($events, function($e) {
            return $e['category'] === 'firewall';
        });
        
        if (count($firewallEvents) == 0) {
            $violations[] = 'لا توجد سجلات لجدار الحماية';
        }
        
        // فحص كشف الاختراق (المتطلب 11)
        $idsEvents = array_filter($events, function($e) {
            return $e['category'] === 'ids' || $e['category'] === 'ips';
        });
        
        if (count($idsEvents) == 0) {
            $violations[] = 'لا توجد سجلات لأنظمة كشف الاختراق';
        }
        
        return [
            'standard' => 'PCI DSS',
            'period_days' => $days,
            'compliant' => count($violations) === 0,
            'findings' => $findings,
            'violations' => $violations,
            'recommendations' => $this->getPCIRecommendations($violations)
        ];
    }
    
    /**
     * فحص الامتثال لمعيار HIPAA
     */
    private function checkHIPAACompliance($days) {
        $events = $this->auditQuery->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $findings = [];
        $violations = [];
        
        // فحص الوصول للبيانات الصحية
        $accessEvents = array_filter($events, function($e) {
            return $e['category'] === 'phi_access';
        });
        
        $findings['phi_access'] = count($accessEvents);
        
        // فحص سجلات التدقيق
        $auditEvents = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_AUDIT;
        });
        
        if (count($auditEvents) < 10) {
            $violations[] = 'سجلات تدقيق غير كافية';
        }
        
        return [
            'standard' => 'HIPAA',
            'period_days' => $days,
            'compliant' => count($violations) === 0,
            'findings' => $findings,
            'violations' => $violations,
            'recommendations' => $this->getHIPAARecommendations($violations)
        ];
    }
    
    /**
     * فحص الامتثال لمعيار SOX
     */
    private function checkSOXCompliance($days) {
        $events = $this->auditQuery->search([
            'date_from' => date('Y-m-d', strtotime("-$days days"))
        ]);
        
        $findings = [];
        $violations = [];
        
        // فحص التحكم المالي
        $financialEvents = array_filter($events, function($e) {
            return $e['category'] === 'financial';
        });
        
        $findings['financial_events'] = count($financialEvents);
        
        // فحص فصل الصلاحيات
        $authEvents = array_filter($events, function($e) {
            return $e['category'] === AuditTrail::CATEGORY_AUTHORIZATION;
        });
        
        if (count($authEvents) < 5) {
            $violations[] = 'سجلات صلاحيات غير كافية';
        }
        
        return [
            'standard' => 'SOX',
            'period_days' => $days,
            'compliant' => count($violations) === 0,
            'findings' => $findings,
            'violations' => $violations,
            'recommendations' => $this->getSOXRecommendations($violations)
        ];
    }
    
    /**
     * الحصول على توصيات GDPR
     */
    private function getGDPRRecommendations($violations) {
        $recommendations = [];
        
        if (in_array('لا توجد سجلات موافقة المستخدمين', $violations)) {
            $recommendations[] = 'تطبيق نظام لتسجيل موافقة المستخدمين على معالجة البيانات';
        }
        
        if (in_array('تم اكتشاف خروقات أمنية يجب الإبلاغ عنها', $violations)) {
            $recommendations[] = 'الإبلاغ عن خروقات البيانات للسلطات خلال 72 ساعة';
        }
        
        return $recommendations;
    }
    
    /**
     * الحصول على توصيات ISO
     */
    private function getISORecommendations($violations) {
        $recommendations = [];
        
        if (in_array('لا توجد سجلات كافية للتحكم في الوصول', $violations)) {
            $recommendations[] = 'تعزيز نظام التحكم في الوصول وتسجيل جميع المحاولات';
        }
        
        if (in_array('نسخ احتياطية غير كافية', $violations)) {
            $recommendations[] = 'زيادة تواتر النسخ الاحتياطية واختبار الاستعادة';
        }
        
        return $recommendations;
    }
    
    /**
     * الحصول على توصيات PCI
     */
    private function getPCIRecommendations($violations) {
        $recommendations = [];
        
        if (in_array('لا توجد سجلات لجدار الحماية', $violations)) {
            $recommendations[] = 'تفعيل تسجيل جميع أحداث جدار الحماية';
        }
        
        if (in_array('لا توجد سجلات لأنظمة كشف الاختراق', $violations)) {
            $recommendations[] = 'تثبيت وتفعيل أنظمة كشف الاختراق وتسجيل الأحداث';
        }
        
        return $recommendations;
    }
    
    /**
     * الحصول على توصيات HIPAA
     */
    private function getHIPAARecommendations($violations) {
        $recommendations = [];
        
        if (in_array('سجلات تدقيق غير كافية', $violations)) {
            $recommendations[] = 'تعزيز نظام التدقيق لتسجيل كل وصول للبيانات الصحية';
        }
        
        return $recommendations;
    }
    
    /**
     * الحصول على توصيات SOX
     */
    private function getSOXRecommendations($violations) {
        $recommendations = [];
        
        if (in_array('سجلات صلاحيات غير كافية', $violations)) {
            $recommendations[] = 'توثيق جميع تغييرات الصلاحيات والموافقات';
        }
        
        return $recommendations;
    }
    
    /**
     * إنشاء تقرير امتثال شامل
     */
    public function generateComplianceReport($standards = []) {
        if (empty($standards)) {
            $standards = [
                self::STANDARD_GDPR,
                self::STANDARD_ISO27001,
                self::STANDARD_PCI_DSS
            ];
        }
        
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'period' => '30 days',
            'standards' => []
        ];
        
        foreach ($standards as $standard) {
            $report['standards'][$standard] = $this->checkCompliance($standard, 30);
        }
        
        return $report;
    }
}
?>