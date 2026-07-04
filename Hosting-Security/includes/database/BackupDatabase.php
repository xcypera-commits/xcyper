<?php
namespace HostingSystem\Database;

class BackupDatabase {
    private $backupDir;
    private $tempDir;
    private $encryptionKey;
    
    public function __construct() {
        $this->backupDir = getenv('BACKUP_DIR') ?: '/var/backups/hosting/';
        $this->tempDir = sys_get_temp_dir() . '/hosting_backup/';
        $this->encryptionKey = getenv('BACKUP_ENCRYPTION_KEY');
        
        // إنشاء المجلدات إذا لم تكن موجودة
        $this->createDirectories();
    }
    
    /**
     * إنشاء المجلدات المطلوبة
     */
    private function createDirectories(): void {
        $dirs = [
            $this->backupDir,
            $this->tempDir,
            $this->backupDir . 'daily/',
            $this->backupDir . 'weekly/',
            $this->backupDir . 'monthly/',
            $this->backupDir . 'logs/'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0750, true);
            }
        }
    }
    
    /**
     * نسخ احتياطي كامل للنظام
     */
    public function createFullBackup(): array {
        $backupId = 'backup_' . date('Ymd_His');
        $backupInfo = [
            'id' => $backupId,
            'type' => 'full',
            'started_at' => date('Y-m-d H:i:s'),
            'components' => [],
            'status' => 'in_progress'
        ];
        
        try {
            // 1. نسخ قاعدة البيانات الرئيسية
            $backupInfo['components']['database'] = $this->backupDatabase();
            
            // 2. نسخ ملفات التطبيق
            $backupInfo['components']['application'] = $this->backupApplicationFiles();
            
            // 3. نسخ ملفات المستخدمين
            $backupInfo['components']['user_files'] = $this->backupUserFiles();
            
            // 4. نسخ السجلات
            $backupInfo['components']['logs'] = $this->backupLogs();
            
            // 5. نسخ الإعدادات
            $backupInfo['components']['config'] = $this->backupConfiguration();
            
            // 6. إنشاء ملف manifest
            $backupInfo['components']['manifest'] = $this->createManifest($backupInfo);
            
            // 7. ضغط وتشفير النسخة
            $backupInfo['archive'] = $this->compressAndEncrypt($backupInfo);
            
            // 8. رفع للـ Cloud
            $backupInfo['cloud'] = $this->uploadToCloud($backupInfo['archive']);
            
            // 9. تنظيف الملفات المؤقتة
            $this->cleanupTempFiles();
            
            $backupInfo['status'] = 'completed';
            $backupInfo['completed_at'] = date('Y-m-d H:i:s');
            $backupInfo['size'] = filesize($backupInfo['archive']);
            
            // تسجيل النسخة الاحتياطية
            $this->logBackup($backupInfo);
            
            // حذف النسخ القديمة
            $this->cleanOldBackups();
            
        } catch (\Exception $e) {
            $backupInfo['status'] = 'failed';
            $backupInfo['error'] = $e->getMessage();
            $this->logBackupError($backupInfo);
        }
        
        return $backupInfo;
    }
    
    /**
     * نسخ احتياطي لقاعدة البيانات
     */
    private function backupDatabase(): array {
        $database = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $host = getenv('DB_HOST');
        
        $backupFile = $this->tempDir . "database_{$database}_" . date('Ymd_His') . '.sql';
        
        // استخدام mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($host),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("فشل نسخ قاعدة البيانات: " . implode("\n", $output));
        }
        
        // التحقق من سلامة النسخة
        if (!file_exists($backupFile) || filesize($backupFile) === 0) {
            throw new \Exception("ملف النسخة الاحتياطية للقاعدة فارغ أو غير موجود");
        }
        
        return [
            'file' => $backupFile,
            'size' => filesize($backupFile),
            'checksum' => md5_file($backupFile)
        ];
    }
    
    /**
     * نسخ ملفات التطبيق
     */
    private function backupApplicationFiles(): array {
        $appDirs = [
            '/var/www/html/app/',
            '/var/www/html/config/',
            '/var/www/html/src/',
            '/var/www/html/vendor/'
        ];
        
        $backupFiles = [];
        
        foreach ($appDirs as $dir) {
            if (is_dir($dir)) {
                $tarFile = $this->createTarArchive($dir);
                $backupFiles[] = [
                    'source' => $dir,
                    'archive' => $tarFile,
                    'size' => filesize($tarFile),
                    'checksum' => md5_file($tarFile)
                ];
            }
        }
        
        return $backupFiles;
    }
    
    /**
     * نسخ ملفات المستخدمين
     */
    private function backupUserFiles(): array {
        $userFilesDir = '/var/www/html/uploads/';
        
        if (!is_dir($userFilesDir)) {
            return [];
        }
        
        // نسخ ملفات المستخدمين
        $tarFile = $this->createTarArchive($userFilesDir);
        
        return [
            'source' => $userFilesDir,
            'archive' => $tarFile,
            'size' => filesize($tarFile),
            'checksum' => md5_file($tarFile),
            'file_count' => $this->countFilesInDirectory($userFilesDir)
        ];
    }
    
    /**
     * نسخ السجلات
     */
    private function backupLogs(): array {
        $logDirs = [
            '/var/log/apache2/',
            '/var/log/mysql/',
            '/var/log/hosting/'
        ];
        
        $logFiles = [];
        
        foreach ($logDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '*.log');
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        $logFiles[] = [
                            'file' => $file,
                            'size' => filesize($file),
                            'modified' => date('Y-m-d H:i:s', filemtime($file))
                        ];
                    }
                }
            }
        }
        
        return $logFiles;
    }
    
    /**
     * ضغط وتشفير النسخة
     */
    private function compressAndEncrypt(array $backupInfo): string {
        $archiveName = $this->backupDir . 'full/' . $backupInfo['id'] . '.tar.gz';
        
        // إنشاء أرشيف tar من جميع الملفات
        $tarCommand = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($archiveName),
            escapeshellarg($this->tempDir)
        );
        
        exec($tarCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("فشل ضغط النسخة الاحتياطية");
        }
        
        // تشفير الأرشيف
        $encryptedFile = $this->encryptFile($archiveName);
        
        // حذف الأرشيف غير المشفر
        unlink($archiveName);
        
        return $encryptedFile;
    }
    
    /**
     * تشفير الملف
     */
    private function encryptFile(string $filePath): string {
        $encryptedPath = $filePath . '.enc';
        
        $iv = random_bytes(16);
        $cipher = 'aes-256-gcm';
        
        $data = file_get_contents($filePath);
        $encrypted = openssl_encrypt(
            $data,
            $cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        $result = $iv . $tag . $encrypted;
        file_put_contents($encryptedPath, $result);
        
        return $encryptedPath;
    }
    
    /**
     * استعادة من نسخة احتياطية
     */
    public function restoreFromBackup(string $backupId, string $component = 'all'): array {
        $backupFile = $this->findBackupFile($backupId);
        
        if (!$backupFile) {
            throw new \Exception("النسخة الاحتياطية غير موجودة: {$backupId}");
        }
        
        // فك التشفير
        $decryptedFile = $this->decryptFile($backupFile);
        
        // استخراج الأرشيف
        $extractDir = $this->tempDir . 'restore_' . $backupId . '/';
        $this->extractArchive($decryptedFile, $extractDir);
        
        $restoreInfo = [
            'backup_id' => $backupId,
            'components_restored' => [],
            'started_at' => date('Y-m-d H:i:s')
        ];
        
        // استعادة المكونات المطلوبة
        switch ($component) {
            case 'database':
                $restoreInfo['components_restored']['database'] = $this->restoreDatabase($extractDir);
                break;
                
            case 'files':
                $restoreInfo['components_restored']['files'] = $this->restoreFiles($extractDir);
                break;
                
            case 'all':
                $restoreInfo['components_restored']['database'] = $this->restoreDatabase($extractDir);
                $restoreInfo['components_restored']['files'] = $this->restoreFiles($extractDir);
                $restoreInfo['components_restored']['config'] = $this->restoreConfig($extractDir);
                break;
        }
        
        // تنظيف
        unlink($decryptedFile);
        $this->deleteDirectory($extractDir);
        
        $restoreInfo['completed_at'] = date('Y-m-d H:i:s');
        $restoreInfo['status'] = 'completed';
        
        return $restoreInfo;
    }
    
    /**
     * جدولة النسخ الاحتياطي
     */
    public function scheduleBackups(): void {
        // يومياً الساعة 2 صباحاً
        $dailySchedule = '0 2 * * *';
        
        // أسبوعياً يوم الأحد الساعة 3 صباحاً
        $weeklySchedule = '0 3 * * 0';
        
        // شهرياً أول الشهر الساعة 4 صباحاً
        $monthlySchedule = '0 4 1 * *';
        
        $schedules = [
            'daily' => $dailySchedule,
            'weekly' => $weeklySchedule,
            'monthly' => $monthlySchedule
        ];
        
        foreach ($schedules as $type => $schedule) {
            $this->addCronJob($schedule, $type);
        }
    }
    
    /**
     * التحقق من سلامة النسخ الاحتياطية
     */
    public function verifyBackups(): array {
        $backups = $this->listBackups();
        $verificationResults = [];
        
        foreach ($backups as $backup) {
            $result = $this->verifyBackupIntegrity($backup['file']);
            $verificationResults[] = [
                'backup' => $backup,
                'verification' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // إذا فشل التحقق، إرسال تنبيه
            if (!$result['valid']) {
                $this->sendBackupAlert($backup, $result);
            }
        }
        
        return $verificationResults;
    }
}