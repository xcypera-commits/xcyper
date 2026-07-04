<?php
require_once __DIR__ . '/../../includes/security/encryption/EncryptionService.php';
require_once __DIR__ . '/../../includes/security/encryption/HashManager.php';

class EncryptionTest {
    
    public function testEncryptionDecryption() {
        $service = new EncryptionService();
        $original = 'Hello, World! هذا نص عربي للتشفير';
        
        $encrypted = $service->encrypt($original);
        $decrypted = $service->decrypt($encrypted);
        
        if ($decrypted === $original) {
            echo "✅ testEncryptionDecryption: PASSED\n";
            return true;
        } else {
            echo "❌ testEncryptionDecryption: FAILED\n";
            echo "Original: $original\n";
            echo "Decrypted: $decrypted\n";
            return false;
        }
    }
    
    public function testPasswordHashing() {
        $password = 'MySecurePassword@123';
        
        $hash = HashManager::hashPassword($password);
        $verify = HashManager::verifyPassword($password, $hash);
        
        if ($verify) {
            echo "✅ testPasswordHashing: PASSED\n";
            return true;
        } else {
            echo "❌ testPasswordHashing: FAILED\n";
            return false;
        }
    }
    
    public function testWrongPassword() {
        $password = 'CorrectPassword@123';
        $wrongPassword = 'WrongPassword@123';
        
        $hash = HashManager::hashPassword($password);
        $verify = HashManager::verifyPassword($wrongPassword, $hash);
        
        if (!$verify) {
            echo "✅ testWrongPassword: PASSED\n";
            return true;
        } else {
            echo "❌ testWrongPassword: FAILED\n";
            return false;
        }
    }
    
    public function testTokenGeneration() {
        $token1 = HashManager::generateToken();
        $token2 = HashManager::generateToken();
        
        if (strlen($token1) === 64 && $token1 !== $token2) {
            echo "✅ testTokenGeneration: PASSED\n";
            return true;
        } else {
            echo "❌ testTokenGeneration: FAILED\n";
            return false;
        }
    }
    
    public function runAllTests() {
        echo "=== تشغيل اختبارات التشفير ===\n\n";
        
        $results = [
            $this->testEncryptionDecryption(),
            $this->testPasswordHashing(),
            $this->testWrongPassword(),
            $this->testTokenGeneration()
        ];
        
        $passed = count(array_filter($results));
        $total = count($results);
        
        echo "\n=== النتائج: {$passed}/{$total} نجحت ===\n";
        
        return $passed == $total;
    }
}

// تشغيل الاختبارات
$test = new EncryptionTest();
$test->runAllTests();
?>