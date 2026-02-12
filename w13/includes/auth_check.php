<?php
session_start();
header('Content-Type: application/json');

// 确保没有任何输出在JSON之前
ob_clean();

if (isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => true]);
} else {
    echo json_encode(['logged_in' => false]);
}
exit();
?>