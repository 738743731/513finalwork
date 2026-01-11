<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($game_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// In a real application, you would save to database
// For now, we'll use session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if game exists in cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['game_id'] == $game_id) {
        $item['quantity'] += $quantity;
        $found = true;
        break;
    }
}

if (!$found) {
    $_SESSION['cart'][] = [
        'game_id' => $game_id,
        'quantity' => $quantity,
        'added_at' => date('Y-m-d H:i:s')
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Item added to cart',
    'cart_count' => count($_SESSION['cart'])
]);
?>