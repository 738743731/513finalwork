<?php
// cart_sync.php - 用于同步本地购物车到服务器
session_start();
header('Content-Type: application/json');

require_once 'includes/database.php';

// 读取JSON输入
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit();
    }
    
    if (!isset($input['cart']) || !is_array($input['cart'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid cart data']);
        exit();
    }
    
    // 将购物车保存到session
    $_SESSION['cart'] = $input['cart'];
    
    echo json_encode(['success' => true, 'message' => 'Cart synced successfully']);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>