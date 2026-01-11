<?php
http_response_code(403);
$page_title = "403 - Access Forbidden";
require_once 'includes/header.php';
?>

<div class="error-page">
    <div class="error-content">
        <h1>403</h1>
        <h2>Access Forbidden</h2>
        <p>You don't have permission to access this page.</p>
        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>