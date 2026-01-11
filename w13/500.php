<?php
http_response_code(500);
$page_title = "500 - Server Error";
require_once 'includes/header.php';
?>

<div class="error-page">
    <div class="error-content">
        <h1>500</h1>
        <h2>Internal Server Error</h2>
        <p>Something went wrong on our end. Please try again later.</p>
        <a href="index.php" class="btn btn-primary">Go to Homepage</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>