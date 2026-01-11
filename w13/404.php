<?php
http_response_code(404);
$page_title = "404 - Page Not Found";
require_once 'includes/header.php';
?>

<div class="error-page">
    <div class="error-content">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>