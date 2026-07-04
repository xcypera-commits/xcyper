<?php
require_once __DIR__ . '/../../../includes/security/core/SecurityUtils.php';
require_once __DIR__ . '/../../../includes/security/encryption/EncryptionService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://admin.hostingsystem.com');
header('Access-Control-Allow-Credentials: true');

// التحقق من التوكن
if (!SecurityUtils::validateAPIToken()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $encryptionService = new EncryptionService();
    
    switch ($action) {
        case 'encrypt':
            handleEncrypt($encryptionService);
            break;
            
        case 'decrypt':
            handleDecrypt($encryptionService);
            break;
            
        case 'generate_key':
            handleGenerateKey();
            break;
            
        case 'rotate_keys':
            handleRotateKeys();
            break;
            
        case 'get_key_info':
            handleGetKeyInfo();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Encryption error',
        'message' => $e->getMessage()
    ]);
}

function handleEncrypt(EncryptionService $service) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['plaintext'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing plaintext']);
        return;
    }
    
    $keyId = $data['key_id'] ?? 'default';
    $context = $data['context'] ?? 'general';
    
    $result = $service->encrypt($data['plaintext'], $keyId, $context);
    
    echo json_encode([
        'success' => true,
        'ciphertext' => $result['ciphertext'],
        'key_id' => $result['key_id'],
        'algorithm' => $result['algorithm'],
        'timestamp' => $result['timestamp']
    ]);
}

function handleDecrypt(EncryptionService $service) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ciphertext'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ciphertext']);
        return;
    }
    
    $keyId = $data['key_id'] ?? 'default';
    
    $result = $service->decrypt($data['ciphertext'], $keyId);
    
    echo json_encode([
        'success' => true,
        'plaintext' => $result['plaintext'],
        'key_id' => $result['key_id'],
        'verified' => $result['verified'],
        'timestamp' => $result['timestamp']
    ]);
}

function handleGenerateKey() {
    $keyManager = new KeyManager();
    
    $purpose = $_POST['purpose'] ?? 'data';
    $algorithm = $_POST['algorithm'] ?? 'aes-256-gcm';
    
    $key = $keyManager->generateKey($purpose, $algorithm);
    
    echo json_encode([
        'success' => true,
        'key_id' => $key['id'],
        'algorithm' => $key['algorithm'],
        'purpose' => $key['purpose'],
        'created_at' => $key['created_at'],
        'expires_at' => $key['expires_at']
    ]);
}

function handleRotateKeys() {
    SecurityUtils::requireRole(['admin']);
    
    $keyManager = new KeyManager();
    $result = $keyManager->rotateKeys();
    
    echo json_encode([
        'success' => true,
        'message' => 'Keys rotated successfully',
        'new_keys' => $result['new_keys'],
        'old_keys' => $result['old_keys'],
        're_encrypted' => $result['re_encrypted_count']
    ]);
}

function handleGetKeyInfo() {
    $keyManager = new KeyManager();
    $keyId = $_GET['key_id'] ?? 'current';
    
    $info = $keyManager->getKeyInfo($keyId);
    
    echo json_encode([
        'success' => true,
        'key_info' => $info
    ]);
}

// WebSocket endpoint للتشفير الآمن
if ($_SERVER['REQUEST_URI'] === '/api/security/encryption/ws') {
    require_once __DIR__ . '/../../../includes/security/encryption/WebSocketEncryption.php';
    $ws = new WebSocketEncryption();
    $ws->run();
}