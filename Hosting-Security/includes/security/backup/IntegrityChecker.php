<?php
namespace HostingSystem\Security\Backup;

class IntegrityChecker {
    /**
     * التحقق من سلامة النسخ الاحتياطية
     */
    public function verifyBackupIntegrity(string $backupPath): array {
        $results = [
            'file_exists' => file_exists($backupPath),
            'file_size' => filesize($backupPath),
            'checksum_match' => false,
            'encryption_valid' => false,
            'structure_valid' => false,
            'overall_integrity' => false
        ];
        
        if (!$results['file_exists']) {
            return $results;
        }
        
        // التحقق من checksum
        $expectedChecksum = $this->getExpectedChecksum($backupPath);
        $actualChecksum = md5_file($backupPath);
        $results['checksum_match'] = hash_equals($expectedChecksum, $actualChecksum);
        
        // التحقق من التشفير
        $results['encryption_valid'] = $this->verifyEncryption($backupPath);
        
        // التحقق من الهيكل
        $results['structure_valid'] = $this->verifyStructure($backupPath);
        
        // النتيجة الإجمالية
        $results['overall_integrity'] = $results['checksum_match'] && 
                                       $results['encryption_valid'] && 
                                       $results['structure_valid'];
        
        return $results;
    }
    
    /**
     * التحقق من سلامة نظام الملفات
     */
    public function verifyFilesystemIntegrity(string $directory): array {
        $issues = [];
        
        // فحص permissions
        $permissionIssues = $this->checkPermissions($directory);
        if (!empty($permissionIssues)) {
            $issues['permissions'] = $permissionIssues;
        }
        
        // فحص ownership
        $ownershipIssues = $this->checkOwnership($directory);
        if (!empty($ownershipIssues)) {
            $issues['ownership'] = $ownershipIssues;
        }
        
        // فحص integrity של הקבצים
        $fileIntegrityIssues = $this->checkFileIntegrity($directory);
        if (!empty($fileIntegrityIssues)) {
            $issues['file_integrity'] = $fileIntegrityIssues;
        }
        
        // فحص symbolic links
        $symlinkIssues = $this->checkSymlinks($directory);
        if (!empty($symlinkIssues)) {
            $issues['symlinks'] = $symlinkIssues;
        }
        
        return [
            'directory' => $directory,
            'issues' => $issues,
            'is_healthy' => empty($issues)
        ];
    }
    
    /**
     * التحقق من سلامة قاعدة البيانات
     */
    public function verifyDatabaseIntegrity(string $databaseName): array {
        $db = Database::getInstance();
        
        $results = [
            'tables' => [],
            'corrupt_tables' => [],
            'orphaned_records' => [],
            'foreign_key_violations' => [],
            'overall_integrity' => true
        ];
        
        // التحقق من كل جدول
        $tables = $db->query("SHOW TABLES IN {$databaseName}");
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            // CHECK TABLE
            $checkResult = $db->query("CHECK TABLE {$databaseName}.{$tableName} FAST");
            
            if ($checkResult[0]['Msg_text'] !== 'OK') {
                $results['corrupt_tables'][] = [
                    'table' => $tableName,
                    'issue' => $checkResult[0]['Msg_text']
                ];
                $results['overall_integrity'] = false;
            }
            
            // التحقق من orphaned records
            $orphaned = $this->findOrphanedRecords($databaseName, $tableName);
            if (!empty($orphaned)) {
                $results['orphaned_records'][] = [
                    'table' => $tableName,
                    'count' => count($orphaned)
                ];
            }
            
            $results['tables'][] = $tableName;
        }
        
        // التحقق من foreign keys
        $fkViolations = $this->checkForeignKeys($databaseName);
        if (!empty($fkViolations)) {
            $results['foreign_key_violations'] = $fkViolations;
            $results['overall_integrity'] = false;
        }
        
        return $results;
    }
    
    /**
     * فحص integrity للتطبيق
     */
    public function verifyApplicationIntegrity(): array {
        $results = [
            'core_files' => $this->verifyCoreFiles(),
            'dependencies' => $this->verifyDependencies(),
            'configuration' => $this->verifyConfiguration(),
            'permissions' => $this->verifyApplicationPermissions(),
            'overall_integrity' => true
        ];
        
        // حساب النتيجة الإجمالية
        foreach ($results as $key => $value) {
            if ($key !== 'overall_integrity' && isset($value['is_valid'])) {
                $results['overall_integrity'] = $results['overall_integrity'] && $value['is_valid'];
            }
        }
        
        return $results;
    }
}