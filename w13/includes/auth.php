<?php
// 启动会话（如果尚未启动）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 检查用户是否已认证
 */
function checkAuthentication() {
    // 确保会话已启动
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 检查用户是否登录
    if (!isset($_SESSION['user_id'])) {
        // 存储当前URL以便登录后重定向
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // 设置错误消息
        $_SESSION['message'] = "Please login to access this page.";
        $_SESSION['message_type'] = 'error';
        
        // 重定向到登录页面
        header("Location: login.php");
        exit();
    }
    
    // 检查会话超时（1小时）
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // 会话超时，销毁会话
        session_unset();
        session_destroy();
        
        // 重定向到登录页面并显示会话过期消息
        header("Location: login.php?session=expired");
        exit();
    }
    
    // 更新最后活动时间
    $_SESSION['last_activity'] = time();
}

/**
 * 检查用户是否已登录
 * @return bool
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * 检查用户是否是管理员
 * @return bool
 */
function isAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * 获取当前登录用户的信息
 * @return array|null 用户信息数组或null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'display_name' => $_SESSION['display_name'] ?? null,
        'is_admin' => $_SESSION['is_admin'] ?? false
    ];
}

/**
 * 注销用户
 */
function logout() {
    // 清除所有会话变量
    $_SESSION = array();
    
    // 清除记住我cookie
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
    
    // 销毁会话
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // 重定向到登录页面
    header("Location: login.php?logged_out=success");
    exit();
}

/**
 * 设置会话消息
 * @param string $message 消息内容
 * @param string $type 消息类型（error, success, warning, info）
 */
function setMessage($message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * 获取并清除会话消息
 * @return array|null 包含消息和类型的数组，或null
 */
function getMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['message'])) {
        $message = [
            'text' => $_SESSION['message'],
            'type' => $_SESSION['message_type'] ?? 'info'
        ];
        
        // 清除消息
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return $message;
    }
    
    return null;
}
?>