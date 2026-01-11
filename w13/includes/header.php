<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>GameHub</title>
    
    <!-- Local CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Fallback styles if local CSS doesn't exist -->
    <?php if (!file_exists('assets/css/style.css')): ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 1rem 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .logo { color: white; font-size: 1.8rem; text-decoration: none; font-weight: bold; }
        .logo span { color: #4CAF50; }
        .nav-menu { list-style: none; display: flex; gap: 1.5rem; margin: 0; padding: 0; align-items: center; }
        .nav-menu li { margin: 0; }
        .nav-menu a { color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .nav-menu a:hover { color: #4CAF50; }
        .user-info { display: flex; align-items: center; gap: 1rem; margin-left: auto; }
        .user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #4CAF50; display: flex; align-items: center; justify-content: center; }
        .user-name { color: #4CAF50; font-weight: bold; }
        .alert { padding: 12px; margin: 15px 0; border-radius: 4px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Game<span>Hub</span></a>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="products.php"><i class="fas fa-gamepad"></i> Games</a></li>
                <li><a href="forum.php"><i class="fas fa-comments"></i> Forum</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="careers.php"><i class="fas fa-briefcase"></i> Careers</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                
                <!-- 购物车链接 - 始终显示 -->
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- 用户已登录 -->
                    <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <?php endif; ?>
                    
                    <!-- 订阅者页面链接 - 登录后可见 -->
                    <li><a href="subscribers.php"><i class="fas fa-users"></i> Subscribers</a></li>

                    <li class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="user-name">
                            <?php 
                            if (isset($_SESSION['display_name'])) {
                                echo htmlspecialchars($_SESSION['display_name']);
                            } elseif (isset($_SESSION['first_name'])) {
                                echo htmlspecialchars($_SESSION['first_name']);
                            }
                            ?>
                        </span>
                    </li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <!-- 用户未登录 -->
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="http://simple666.free.nf/wordpress2/subscribe/"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="container">
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="container main-content">