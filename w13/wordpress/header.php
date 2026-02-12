<?php
/**
 * The header for our theme
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    
    <!-- 添加内联样式确保导航栏正常 -->
    <style>
        /* 确保导航栏布局正确 */
        .main-header .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        /* 主菜单样式 */
        .main-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .main-menu li {
            margin: 0;
        }
        
        .main-menu a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .main-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ff6b6b;
        }
        
        /* 用户区域样式 */
        .user-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* 隐藏移动菜单按钮 */
        .menu-toggle {
            display: none;
        }
        
        /* 隐藏移动菜单 */
        .mobile-nav {
            display: none;
        }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="main-header">
    <div class="header-container">
        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
            Game<span>Hub</span>
        </a>

        <!-- 主导航菜单 -->
        <nav class="desktop-nav">
            <ul class="main-menu">
                <!-- 首页 -->
                <li><a href="https://simple666.free.nf/513/w13/index.php">
                    <i class="fas fa-home"></i> Home
                </a></li>
                
                <!-- 游戏商店 -->
                <li><a href="https://simple666.free.nf/513/w13/products.php">
                    <i class="fas fa-gamepad"></i> Games
                </a></li>
                
                
                
                <!-- 论坛 -->
                <li><a href="https://simple666.free.nf/513/w13/login.php">
                    <i class="fas fa-comments"></i> Forum
                </a></li>
                
                <!-- 关于我们 -->
                <li><a href="https://simple666.free.nf/513/w13/about.php">
                    <i class="fas fa-info-circle"></i> About
                </a></li>
                
                
                
                <!-- 职业 -->
                <li><a href="https://simple666.free.nf/513/w13/careers.php">
                    <i class="fas fa-briefcase"></i> Careers
                </a></li>
                
               
                
                <!-- 联系 -->
                <li><a href="https://simple666.free.nf/513/w13/contact.php">
                    <i class="fas fa-envelope"></i> Contact
                </a></li>
                
                <!-- 购物车 -->
                <li><a href="https://simple666.free.nf/513/w13/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <span class="cart-count">0</span>
                </a></li>
            </ul>
        </nav>

        <!-- 用户区域 -->
        <div class="user-section">
            <?php if (is_user_logged_in()) : ?>
                <?php $current_user = wp_get_current_user(); ?>
                
                <!-- 我的账户 -->
                <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>" 
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-user"></i> My Account
                </a>
                
                <!-- 登出 -->
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                   class="btn btn-outline btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else : ?>
                <!-- 登录 -->
                <a href="https://simple666.free.nf/513/w13/login.php" 
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                
                <!-- 注册 -->
                <a href="<?php echo esc_url(wp_registration_url()); ?>" 
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- 消息提示 -->
<?php if (isset($_SESSION['message'])) : ?>
    <div class="container">
        <div class="alert alert-<?php echo esc_attr($_SESSION['message_type'] ?? 'info'); ?>">
            <?php 
            echo esc_html($_SESSION['message']);
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    </div>
<?php endif; ?>

<main class="main-content">