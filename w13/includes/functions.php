<?php
// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Redirect with message
function redirectWithMessage($url, $type, $message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Display message if exists
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        echo "<div class='alert alert-$type'>$message</div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Calculate discount price
function calculateDiscountPrice($price, $discount) {
    if ($discount > 0) {
        return $price * (100 - $discount) / 100;
    }
    return $price;
}
?>