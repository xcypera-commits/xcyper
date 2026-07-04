<?php
namespace HostingSystem\Tests\Security;

use PHPUnit\Framework\TestCase;
use HostingSystem\Security\Authentication\AuthManager;
use HostingSystem\Security\Encryption\EncryptionService;
use HostingSystem\Security\Validation\InputValidator;
use HostingSystem\Security\Firewall\RequestValidator;
use HostingSystem\Security\Monitoring\ThreatDetector;

class SecurityTest extends TestCase {
    private $authManager;
    private $encryptionService;
    private $inputValidator;
    private $requestValidator;
    private $threatDetector;
    
    protected function setUp(): void {
        $this->authManager = new AuthManager();
        $this->encryptionService = new EncryptionService();
        $this->inputValidator = new InputValidator();
        $this->requestValidator = new RequestValidator();
        $this->threatDetector = new ThreatDetector();
    }
    
    /**
     * اختبارات المصادقة
     */
    public function testAuthentication(): void {
        // اختبار تسجيل دخول ناجح
        $result = $this->authManager->authenticate('admin@system.com', 'SecurePass123!@#');
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('session_token', $result);
        
        // اختبار تسجيل دخول فاشل
        $result = $this->authManager->authenticate('admin@system.com', 'WrongPassword');
        $this->assertFalse($result['success']);
        $this->assertEquals('INVALID_CREDENTIALS', $result['error']);
        
        // اختبار تجاوز محاولات الدخول
        for ($i = 0; $i < 6; $i++) {
            $this->authManager->authenticate('test@system.com', 'WrongPassword');
        }
        $result = $this->authManager->authenticate('test@system.com', 'AnyPassword');
        $this->assertEquals('ACCOUNT_LOCKED', $result['error']);
    }
    
    /**
     * اختبارات التشفير
     */
    public function testEncryption(): void {
        $plaintext = 'This is a secret message';
        
        // تشفير
        $encrypted = $this->encryptionService->encrypt($plaintext);
        $this->assertNotEquals($plaintext, $encrypted['ciphertext']);
        $this->assertArrayHasKey('key_id', $encrypted);
        $this->assertArrayHasKey('iv', $encrypted);
        
        // فك التشفير
        $decrypted = $this->encryptionService->decrypt(
            $encrypted['ciphertext'],
            $encrypted['key_id'],
            $encrypted['iv']
        );
        $this->assertEquals($plaintext, $decrypted['plaintext']);
        
        // اختبار سلامة التشفير
        $this->assertTrue($decrypted['verified']);
        
        // اختبار تشفير متعدد
        $data1 = $this->encryptionService->encrypt('Data1');
        $data2 = $this->encryptionService->encrypt('Data2');
        $this->assertNotEquals($data1['ciphertext'], $data2['ciphertext']);
    }
    
    /**
     * اختبارات تحقق المدخلات
     */
    public function testInputValidation(): void {
        // بيانات صحيحة
        $validData = [
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'age' => '25'
        ];
        
        $result = $this->inputValidator->validate($validData, 'registration');
        $this->assertTrue($result);
        $this->assertEmpty($this->inputValidator->getErrors());
        
        // بيانات غير صحيحة
        $invalidData = [
            'username' => 'jo', // قصير جداً
            'email' => 'not-an-email',
            'password' => '123', // ضعيف
            'age' => 'not-a-number'
        ];
        
        $result = $this->inputValidator->validate($invalidData, 'registration');
        $this->assertFalse($result);
        $errors = $this->inputValidator->getErrors();
        $this->assertCount(4, $errors);
        
        // اختبار XSS
        $xssData = ['input' => '<script>alert("XSS")</script>'];
        $this->assertTrue($this->inputValidator->detectXSS($xssData['input']));
        
        // اختبار SQL Injection
        $sqlData = ['input' => "'; DROP TABLE users; --"];
        $this->assertTrue($this->inputValidator->detectSQLInjection($sqlData['input']));
    }
    
    /**
     * اختبارات الجدار الناري
     */
    public function testFirewall(): void {
        // طلب شرعي
        $legitRequest = [
            'method' => 'GET',
            'url' => '/api/users',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json'
            ],
            'ip' => '192.168.1.100'
        ];
        
        $result = $this->requestValidator->validate($legitRequest);
        $this->assertTrue($result['valid']);
        
        // طلب مشبوه
        $suspiciousRequest = [
            'method' => 'POST',
            'url' => '/api/users?id=1 OR 1=1',
            'headers' => [
                'User-Agent' => 'sqlmap/1.5.8'
            ],
            'ip' => '185.220.101.5' // IP معروف خبيث
        ];
        
        $result = $this->requestValidator->validate($suspiciousRequest);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('threats', $result);
    }
    
    /**
     * اختبارات اكتشاف التهديدات
     */
    public function testThreatDetection(): void {
        // ملف آمن
        $safeFile = [
            'name' => 'document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/php123.tmp',
            'content' => '%PDF-1.4\n...'
        ];
        
        $result = $this->threatDetector->scanFile($safeFile);
        $this->assertTrue($result['safe']);
        
        // ملف خبيث
        $maliciousFile = [
            'name' => 'malware.exe',
            'type' => 'application/x-msdownload',
            'tmp_name' => '/tmp/php456.tmp',
            'content' => 'MZ\x90\x00\x03\x00...' // توقيع PE
        ];
        
        $result = $this->threatDetector->scanFile($maliciousFile);
        $this->assertFalse($result['safe']);
        $this->assertContains('executable_detected', $result['threats']);
        
        // اكتشاف DDoS
        $logs = array_fill(0, 1000, ['ip' => '192.0.2.1', 'timestamp' => time()]);
        $result = $this->threatDetector->detectDDoS($logs);
        $this->assertTrue($result['detected']);
        $this->assertEquals('192.0.2.1', $result['source_ip']);
    }
    
    /**
     * اختبارات أداء التشفير
     */
    public function testEncryptionPerformance(): void {
        $sizes = [100, 1000, 10000, 100000]; // بايت
        
        foreach ($sizes as $size) {
            $data = random_bytes($size);
            
            $start = microtime(true);
            $encrypted = $this->encryptionService->encrypt($data);
            $encryptTime = microtime(true) - $start;
            
            $start = microtime(true);
            $decrypted = $this->encryptionService->decrypt(
                $encrypted['ciphertext'],
                $encrypted['key_id'],
                $encrypted['iv']
            );
            $decryptTime = microtime(true) - $start;
            
            $this->assertEquals($data, $decrypted['plaintext']);
            
            // التحقق من أن الأداء مقبول
            $this->assertLessThan(0.1, $encryptTime, "Encryption too slow for {$size} bytes");
            $this->assertLessThan(0.1, $decryptTime, "Decryption too slow for {$size} bytes");
        }
    }
    
    /**
     * اختبارات تحمل النظام
     */
    public function testStressTest(): void {
        // محاولات تسجيل دخول متعددة متزامنة
        $users = array_fill(0, 100, [
            'username' => 'stress_test_user',
            'password' => 'TestPass123!'
        ]);
        
        $results = [];
        foreach ($users as $user) {
            $results[] = $this->authManager->authenticate(
                $user['username'],
                $user['password']
            );
        }
        
        // يجب أن تفشل معظم المحاولات بسبب Rate Limiting
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->assertLessThan(5, $successCount); // أقل من 5 نجاحات
        
        // اختبار حمل على التشفير
        $concurrentEncryptions = 50;
        $data = 'test_data';
        
        for ($i = 0; $i < $concurrentEncryptions; $i++) {
            $result = $this->encryptionService->encrypt($data . $i);
            $this->assertArrayHasKey('ciphertext', $result);
        }
    }
    
    /**
     * اختبارات التعافي من الأخطاء
     */
    public function testErrorRecovery(): void {
        // اختبار مع مفتاح غير صالح
        $this->expectException(\Exception::class);
        $this->encryptionService->decrypt('invalid_ciphertext', 'invalid_key_id');
        
        // اختبار استعادة بعد فشل
        $result = $this->encryptionService->encrypt('recovery_test');
        $this->assertArrayHasKey('ciphertext', $result);
        
        // اختبار Rollback
        $this->authManager->beginTransaction();
        try {
            $this->authManager->createUser(['invalid_data']);
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->authManager->rollback();
            // يجب أن ينجح Rollback
            $this->assertTrue(true);
        }
    }
}

// تشغيل الاختبارات من سطر الأوامر
if (php_sapi_name() === 'cli') {
    $testSuite = new SecurityTest();
    
    echo "Running Security Tests...\n";
    echo "========================\n\n";
    
    $methods = [
        'testAuthentication',
        'testEncryption', 
        'testInputValidation',
        'testFirewall',
        'testThreatDetection',
        'testEncryptionPerformance',
        'testStressTest',
        'testErrorRecovery'
    ];
    
    $passed = 0;
    $failed = 0;
    
    foreach ($methods as $method) {
        try {
            $testSuite->setUp();
            $testSuite->$method();
            echo "✓ {$method}\n";
            $passed++;
        } catch (\Exception $e) {
            echo "✗ {$method}: {$e->getMessage()}\n";
            $failed++;
        }
    }
    
    echo "\n========================\n";
    echo "Results: {$passed} passed, {$failed} failed\n";
    
    if ($failed > 0) {
        exit(1);
    }
}