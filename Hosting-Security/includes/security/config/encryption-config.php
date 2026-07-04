<?php
/**
 * إعدادات التشفير المتقدمة
 * Advanced Encryption Configuration
 */

if (!defined('SECURITY_ACCESS')) {
    die('Direct access not allowed');
}

return [
    // مفاتيح التشفير (يجب تغييرها في الإنتاج)
    'keys' => [
        'master_key' => 'CHANGE_THIS_TO_RANDOM_32_CHARS_12345678',
        'hmac_key' => 'CHANGE_THIS_TO_RANDOM_KEY_FOR_HMAC_789',
        'iv_length' => 16,
    ],
    
    // خوارزميات التشفير المدعومة
    'ciphers' => [
        'aes_256_gcm' => [
            'method' => 'aes-256-gcm',
            'key_length' => 32,
            'tag_length' => 16,
        ],
        'aes_256_cbc' => [
            'method' => 'aes-256-cbc',
            'key_length' => 32,
            'iv_length' => 16,
        ],
        'chacha20_poly1305' => [
            'method' => 'chacha20-poly1305',
            'key_length' => 32,
            'iv_length' => 12,
        ],
    ],
    
    // البيانات الحساسة التي يجب تشفيرها
    'sensitive_fields' => [
        'password',
        'credit_card',
        'ssn',
        'bank_account',
        'phone',
        'email',
        'address',
    ],
    
    // إعدادات Hashing
    'hashing' => [
        'default' => 'bcrypt',
        'bcrypt' => [
            'cost' => 12,
        ],
        'argon2' => [
            'memory_cost' => 2048,
            'time_cost' => 4,
            'threads' => 3,
        ],
    ],
];
?>