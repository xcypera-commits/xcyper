<?php
/**
 * مدير التوكنات
 * Token Manager
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

class TokenManager {
    
    private $logger;
    
    public function __construct() {
        $this->logger = new SecurityLogger();
    }
    
    /**
     * إنشاء توكن جديد
     */
    public function createToken($userId, $type = 'api', $expiresIn = 3600) {
        $tokenId = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        
        $tokenData = [
            'id' => $tokenId,
            'user_id' => $userId,
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'type' => $type,
            'created_at' => time(),
            'expires_at' => time() + $expiresIn,
            'last_used' => null,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        
        // حفظ التوكن
        $this->saveToken($tokenData);
        
        $this->logger->log('token', "Token created for user $userId, type: $type");
        
        return [
            'token_id' => $tokenId,
            'token' => $token,
            'expires_at' => $tokenData['expires_at']
        ];
    }
    
    /**
     * التحقق من التوكن
     */
    public function validateToken($tokenId, $token) {
        $tokenData = $this->getToken($tokenId);
        
        if (!$tokenData) {
            return false;
        }
        
        // التحقق من انتهاء الصلاحية
        if ($tokenData['expires_at'] < time()) {
            $this->deleteToken($tokenId);
            return false;
        }
        
        // التحقق من صحة التوكن
        if (!password_verify($token, $tokenData['token'])) {
            return false;
        }
        
        // تحديث آخر استخدام
        $this->updateTokenUsage($tokenId);
        
        return $tokenData;
    }
    
    /**
     * إبطال توكن
     */
    public function revokeToken($tokenId) {
        $this->deleteToken($tokenId);
        $this->logger->log('token', "Token $tokenId revoked");
        return true;
    }
    
    /**
     * إبطال جميع توكنات مستخدم
     */
    public function revokeAllUserTokens($userId) {
        $count = $this->deleteUserTokens($userId);
        $this->logger->log('token', "All tokens for user $userId revoked ($count tokens)");
        return $count;
    }
    
    /**
     * تنظيف التوكنات المنتهية
     */
    public function cleanExpiredTokens() {
        $count = $this->deleteExpiredTokens();
        $this->logger->log('token', "Cleaned $count expired tokens");
        return $count;
    }
    
    /**
     * حفظ التوكن (محاكاة)
     */
    private function saveToken($tokenData) {
        $_SESSION['tokens'][$tokenData['id']] = $tokenData;
    }
    
    /**
     * الحصول على توكن
     */
    private function getToken($tokenId) {
        return $_SESSION['tokens'][$tokenId] ?? null;
    }
    
    /**
     * حذف توكن
     */
    private function deleteToken($tokenId) {
        unset($_SESSION['tokens'][$tokenId]);
    }
    
    /**
     * تحديث استخدام التوكن
     */
    private function updateTokenUsage($tokenId) {
        if (isset($_SESSION['tokens'][$tokenId])) {
            $_SESSION['tokens'][$tokenId]['last_used'] = time();
        }
    }
    
    /**
     * حذف توكنات المستخدم
     */
    private function deleteUserTokens($userId) {
        $count = 0;
        foreach ($_SESSION['tokens'] ?? [] as $id => $token) {
            if ($token['user_id'] == $userId) {
                unset($_SESSION['tokens'][$id]);
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * حذف التوكنات المنتهية
     */
    private function deleteExpiredTokens() {
        $count = 0;
        $now = time();
        
        foreach ($_SESSION['tokens'] ?? [] as $id => $token) {
            if ($token['expires_at'] < $now) {
                unset($_SESSION['tokens'][$id]);
                $count++;
            }
        }
        
        return $count;
    }
}
?>