<?php
require_once __DIR__ . '/../../includes/security/authentication/AuthManager.php';

class AuthenticationTest {
    
    private $db;
    private $authManager;
    
    public function __construct() {
        $this->db = new PDO('sqlite::memory:');
        $this->setupTestDatabase();
        $this->authManager = new AuthManager($this->db);
    }
    
    private function setupTestDatabase() {
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                username TEXT,
                email TEXT,
                password_hash TEXT,
                role TEXT,
                status TEXT DEFAULT 'active',
                failed_login_attempts INTEGER DEFAULT 0,
                last_failed_login DATETIME
            )
        ");
        
        // إضافة مستخدم تجريبي
        $password_hash = password_hash('Test@123456', PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, role) 
            VALUES ('testuser', 'test@example.com', ?, 'client')
        ");
        $stmt->execute([$password_hash]);
    }
    
    public function testSuccessfulLogin() {
        $result = $this->authManager->login('testuser', 'Test@123456');
        
        if ($result['success']) {
            echo "✅ testSuccessfulLogin: PASSED\n";
            return true;
        } else {
            echo "❌ testSuccessfulLogin: FAILED - " . $result['message'] . "\n";
            return false;
        }
    }
    
    public function testFailedLogin() {
        $result = $this->authManager->login('testuser', 'WrongPassword');
        
        if (!$result['success'] && $result['error'] == 1001) {
            echo "✅ testFailedLogin: PASSED\n";
            return true;
        } else {
            echo "❌ testFailedLogin: FAILED\n";
            return false;
        }
    }
    
    public function testAccountLockout() {
        // محاولات متعددة فاشلة
        for ($i = 0; $i < 6; $i++) {
            $this->authManager->login('testuser', 'WrongPassword');
        }
        
        $result = $this->authManager->login('testuser', 'Test@123456');
        
        if (!$result['success'] && strpos($result['message'], 'مقفل') !== false) {
            echo "✅ testAccountLockout: PASSED\n";
            return true;
        } else {
            echo "❌ testAccountLockout: FAILED\n";
            return false;
        }
    }
    
    public function runAllTests() {
        echo "=== تشغيل اختبارات المصادقة ===\n\n";
        
        $results = [
            $this->testSuccessfulLogin(),
            $this->testFailedLogin(),
            $this->testAccountLockout()
        ];
        
        $passed = count(array_filter($results));
        $total = count($results);
        
        echo "\n=== النتائج: {$passed}/{$total} نجحت ===\n";
        
        return $passed == $total;
    }
}

// تشغيل الاختبارات
$test = new AuthenticationTest();
$test->runAllTests();
?>