<?php
session_start();
require_once 'includes/auth.php';
checkAuthentication();

$page_title = "Checkout";
require_once 'includes/header.php';

// 从本地存储或session获取购物车数据
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// 检查购物车是否为空
if (empty($cart)) {
    echo '<script>';
    echo 'if (localStorage.getItem("cart")) {';
    echo '  const localCart = JSON.parse(localStorage.getItem("cart"));';
    echo '  if (localCart.length > 0) {';
    echo '    // 同步到session';
    echo '    fetch("cart_sync.php", {';
    echo '      method: "POST",';
    echo '      headers: {';
    echo '        "Content-Type": "application/json",';
    echo '      },';
    echo '      body: JSON.stringify({ cart: localCart })';
    echo '    }).then(() => {';
    echo '      location.reload();';
    echo '    });';
    echo '  } else {';
    echo '    alert("Your cart is empty!");';
    echo '    window.location.href = "cart.php";';
    echo '  }';
    echo '} else {';
    echo '  alert("Your cart is empty!");';
    echo '  window.location.href = "cart.php";';
    echo '}';
    echo '</script>';
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 验证表单数据
        $required_fields = ['full_name', 'email', 'address', 'city', 'zip', 'country'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // 获取并清理表单数据
        $full_name = htmlspecialchars(trim($_POST['full_name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $address = htmlspecialchars(trim($_POST['address']));
        $city = htmlspecialchars(trim($_POST['city']));
        $zip = htmlspecialchars(trim($_POST['zip']));
        $country = htmlspecialchars(trim($_POST['country']));
        
        // 验证邮箱
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // 计算总金额
        $subtotal = 0;
        foreach ($cart as $item) {
            $price = isset($item['discount']) && $item['discount'] > 0 ? 
                $item['price'] * (100 - $item['discount']) / 100 : 
                $item['price'];
            $subtotal += $price * $item['quantity'];
        }
        
        $tax = $subtotal * 0.1;
        $shipping = $subtotal > 100 ? 0 : 10;
        $total = $subtotal + $tax + $shipping;
        
        // 获取用户ID
        $user_id = $_SESSION['user_id'];
        
        // 连接到数据库
        require_once 'includes/database.php';
        $db = new Database();
        
        // 创建订单
        $order_id = $db->createOrder(
            $user_id,
            $total,
            $full_name,
            $email,
            "$address, $city, $zip, $country"
        );
        
        // 添加订单项
        foreach ($cart as $item) {
            $item_price = isset($item['discount']) && $item['discount'] > 0 ? 
                $item['price'] * (100 - $item['discount']) / 100 : 
                $item['price'];
            
            $db->addOrderItem(
                $order_id,
                $item['id'],
                $item['name'],
                $item['quantity'],
                $item_price
            );
        }
        
        // 清空购物车
        unset($_SESSION['cart']);
        
        // 保存订单信息到session用于显示确认页面
        $_SESSION['last_order'] = [
            'order_id' => $order_id,
            'total' => $total,
            'customer_name' => $full_name,
            'customer_email' => $email,
            'address' => "$address, $city, $zip, $country",
            'items' => $cart,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'order_date' => date('Y-m-d H:i:s')
        ];
        
        // 重定向到订单确认页面
        header("Location: order_confirmation.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 从session获取用户信息
$current_user = getCurrentUser();
?>

<div class="checkout-section">
    <h1>Checkout</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="checkout-content">
        <div class="checkout-form">
            <h2>Billing Information</h2>
            <form id="checkout-form" method="POST" action="checkout.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip">ZIP Code *</label>
                        <input type="text" id="zip" name="zip" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="country">Country *</label>
                    <select id="country" name="country" required>
                        <option value="">Select Country</option>
                        <option value="US">United States</option>
                        <option value="UK">United Kingdom</option>
                        <option value="CA">Canada</option>
                        <option value="AU">Australia</option>
                        <option value="CN">China</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Complete Purchase</button>
            </form>
        </div>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div id="checkout-summary">
                <?php if (empty($cart)): ?>
                    <p>Your cart is empty</p>
                <?php else: 
                    $subtotal = 0;
                    foreach ($cart as $item):
                        $price = isset($item['discount']) && $item['discount'] > 0 ? 
                            $item['price'] * (100 - $item['discount']) / 100 : 
                            $item['price'];
                        $total = $price * $item['quantity'];
                        $subtotal += $total;
                ?>
                    <div class="checkout-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                <?php 
                    endforeach;
                    
                    $tax = $subtotal * 0.1;
                    $shipping = $subtotal > 100 ? 0 : 10;
                    $grandTotal = $subtotal + $tax + $shipping;
                ?>
                <div class="checkout-total">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax (10%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span><?php echo $shipping === 0 ? 'FREE' : '$' . number_format($shipping, 2); ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span>$<?php echo number_format($grandTotal, 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 在页面加载时同步购物车到session
document.addEventListener('DOMContentLoaded', function() {
    const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (localCart.length > 0) {
        // 同步到服务器session
        fetch('cart_sync.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ cart: localCart })
        });
    }
});

// 表单提交前清除本地购物车
document.getElementById('checkout-form').addEventListener('submit', function() {
    // 清空本地购物车
    localStorage.removeItem('cart');
    // 更新购物车数量显示
    if (window.GameHub && window.GameHub.updateCartCount) {
        window.GameHub.updateCartCount(0);
    }
});
</script>

<style>
.checkout-section {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.checkout-section h1 {
    margin-bottom: 30px;
    color: #333;
}

.checkout-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 40px;
}

.checkout-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.order-summary {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.checkout-form h2, .order-summary h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #555;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    border: none;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
    color: white;
    width: 100%;
}

.btn-primary:hover {
    background: #0056b3;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.checkout-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.checkout-total {
    margin-top: 20px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.total-row.grand-total {
    border-top: 2px solid #007bff;
    margin-top: 10px;
    padding-top: 15px;
    font-weight: bold;
    font-size: 18px;
}

@media (max-width: 992px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        position: static;
    }
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .checkout-section {
        padding: 0 15px;
        margin: 20px auto;
    }
    
    .checkout-form,
    .order-summary {
        padding: 20px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>