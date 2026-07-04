<?php
/**
 * مدير النسخ الاحتياطي
 * Backup Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class BackupManager {
    
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
        $this->config = require __DIR__ . '/../config/security-config.php';
    }
    
    /**
     * إنشاء نسخة احتياطية لعميل
     */
    public function createClientBackup($clientId, $type = 'daily') {
        $clientPath = HOSTING_PATH . 'clients/' . $clientId;
        
        if (!is_dir($clientPath)) {
            return ['error' => 'Client not found'];
        }
        
        $backupId = 'backup_' . $clientId . '_' . date('Ymd_His') . '_' . uniqid();
        $backupPath = BACKUPS_PATH . $type . '/' . $clientId . '/';
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $backupFile = $backupPath . $backupId . '.tar.gz';
        
        // إنشاء قائمة الملفات المراد نسخها
        $filesToBackup = [
            'www',
            'databases',
            'config',
            'logs'
        ];
        
        // إنشاء ملف مؤقت بقائمة الملفات
        $tempList = tempnam(sys_get_temp_dir(), 'backup_list');
        $listHandle = fopen($tempList, 'w');
        
        foreach ($filesToBackup as $dir) {
            $fullPath = $clientPath . '/' . $dir;
            if (is_dir($fullPath)) {
                fwrite($listHandle, $fullPath . "\n");
            }
        }
        fclose($listHandle);
        
        // تنفيذ الضغط
        $command = "tar -czf " . escapeshellarg($backupFile) . " -T " . escapeshellarg($tempList) . " 2>&1";
        $output = [];
        $returnVar = 0;
        
        if (function_exists('exec')) {
            exec($command, $output, $returnVar);
        } else {
            // محاكاة بديلة
            $returnVar = $this->simulateBackup($clientPath, $backupFile, $filesToBackup);
        }
        
        unlink($tempList);
        
        if ($returnVar !== 0) {
            $this->logger->log('backup', "Backup failed for client $clientId", ['output' => $output]);
            return ['error' => 'Backup failed', 'output' => $output];
        }
        
        // تشفير النسخة الاحتياطية
        if ($this->config['backup']['encrypt_backups']) {
            $this->encryptBackup($backupFile);
        }
        
        // التحقق من النسخة
        if ($this->config['backup']['verify_backup']) {
            $this->verifyBackup($backupFile);
        }
        
        $backupInfo = [
            'id' => $backupId,
            'client_id' => $clientId,
            'type' => $type,
            'path' => $backupFile,
            'size' => filesize($backupFile),
            'created_at' => date('Y-m-d H:i:s'),
            'files' => $filesToBackup
        ];
        
        $this->saveBackupInfo($backupInfo);
        $this->logger->log('backup', "Backup created for client $clientId", $backupInfo);
        
        return $backupInfo;
    }
    
    /**
     * محاكاة النسخ الاحتياطي (للبيئات بدون exec)
     */
    private function simulateBackup($source, $destination, $directories) {
        $tar = new PharData($destination);
        
        foreach ($directories as $dir) {
            $fullPath = $source . '/' . $dir;
            if (is_dir($fullPath)) {
                $tar->buildFromDirectory($fullPath);
            }
        }
        
        return 0;
    }
    
    /**
     * تشفير النسخة الاحتياطية
     */
    private function encryptBackup($file) {
        $encryptedFile = $file . '.enc';
        $key = $this->config['encryption']['master_key'] ?? 'default_key_change_this';
        
        $data = file_get_contents($file);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        
        file_put_contents($encryptedFile, $iv . $encrypted);
        unlink($file);
        
        rename($encryptedFile, $file);
    }
    
    /**
     * فك تشفير النسخة الاحتياطية
     */
    private function decryptBackup($file) {
        $data = file_get_contents($file);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $key = $this->config['encryption']['master_key'] ?? 'default_key_change_this';
        
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }
    
    /**
     * التحقق من صحة النسخة
     */
    private function verifyBackup($file) {
        if (!file_exists($file)) {
            return false;
        }
        
        // محاولة فتح الملف
        if (substr($file, -7) === '.tar.gz') {
            $phar = new PharData($file);
            $count = 0;
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                $count++;
            }
            return $count > 0;
        }
        
        return filesize($file) > 0;
    }
    
    /**
     * حفظ معلومات النسخة
     */
    private function saveBackupInfo($info) {
        $infoFile = BACKUPS_PATH . 'backups.json';
        
        $backups = [];
        if (file_exists($infoFile)) {
            $backups = json_decode(file_get_contents($infoFile), true) ?: [];
        }
        
        $backups[$info['id']] = $info;
        
        file_put_contents($infoFile, json_encode($backups, JSON_PRETTY_PRINT));
    }
    
    /**
     * استعادة نسخة احتياطية
     */
    public function restoreBackup($backupId, $targetClientId = null) {
        $infoFile = BACKUPS_PATH . 'backups.json';
        
        if (!file_exists($infoFile)) {
            return ['error' => 'No backup information found'];
        }
        
        $backups = json_decode(file_get_contents($infoFile), true);
        
        if (!isset($backups[$backupId])) {
            return ['error' => 'Backup not found'];
        }
        
        $backup = $backups[$backupId];
        $backupFile = $backup['path'];
        
        if (!file_exists($backupFile)) {
            return ['error' => 'Backup file not found'];
        }
        
        $targetClient = $targetClientId ?? $backup['client_id'];
        $targetPath = HOSTING_PATH . 'clients/' . $targetClient;
        
        // فك التشفير إذا كان مشفراً
        if ($this->config['backup']['encrypt_backups']) {
            $data = $this->decryptBackup($backupFile);
            $tempFile = tempnam(sys_get_temp_dir(), 'restore');
            file_put_contents($tempFile, $data);
            $backupFile = $tempFile;
        }
        
        // استعادة الملفات
        $phar = new PharData($backupFile);
        $phar->extractTo($targetPath, null, true);
        
        if (isset($tempFile)) {
            unlink($tempFile);
        }
        
        $this->logger->log('restore', "Backup $backupId restored to client $targetClient");
        
        return [
            'success' => true,
            'backup' => $backup,
            'restored_to' => $targetClient
        ];
    }
    
    /**
     * الحصول على قائمة النسخ الاحتياطية
     */
    public function getBackups($clientId = null) {
        $infoFile = BACKUPS_PATH . 'backups.json';
        
        if (!file_exists($infoFile)) {
            return [];
        }
        
        $backups = json_decode(file_get_contents($infoFile), true) ?: [];
        
        if ($clientId) {
            $backups = array_filter($backups, function($backup) use ($clientId) {
                return $backup['client_id'] == $clientId;
            });
        }
        
        // ترتيب حسب التاريخ
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * تنظيف النسخ القديمة
     */
    public function cleanOldBackups() {
        $retention = $this->config['backup']['retention_days'];
        $cutoff = strtotime("-$retention days");
        
        $backups = $this->getBackups();
        
        foreach ($backups as $id => $backup) {
            if (strtotime($backup['created_at']) < $cutoff) {
                // حذف الملف
                if (file_exists($backup['path'])) {
                    unlink($backup['path']);
                }
                
                // حذف من السجل
                unset($backups[$id]);
                
                $this->logger->log('backup', "Old backup deleted: $id");
            }
        }
        
        // حفظ التحديث
        file_put_contents(BACKUPS_PATH . 'backups.json', json_encode($backups, JSON_PRETTY_PRINT));
    }
}
?>