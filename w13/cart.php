<?php
session_start();

// 定义函数
function calculateCartTotal($cart) {
    $total = 0;
    if (!empty($cart)) {
        foreach ($cart as $item) {
            $price = isset($item['discount']) && $item['discount'] > 0 ? 
                $item['price'] * (100 - $item['discount']) / 100 : 
                $item['price'];
            $total += $price * $item['quantity'];
        }
    }
    return $total;
}

// 处理获取购物车数据的请求 - 必须在任何输出之前
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['get_cart'])) {
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    header('Content-Type: application/json');
    echo json_encode($cart);
    exit();
}

// 处理所有POST请求 - 必须在任何输出之前
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 确保返回JSON
    header('Content-Type: application/json');
    
    // 初始化购物车
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if ($_POST['action'] === 'add_to_cart') {
        // 验证必要参数
        if (!isset($_POST['product_id'], $_POST['product_name'], $_POST['product_price'], $_POST['quantity'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            exit();
        }
        
        // 添加商品到购物车
        $product = [
            'id' => intval($_POST['product_id']),
            'name' => htmlspecialchars($_POST['product_name']),
            'price' => floatval($_POST['product_price']),
            'discount' => isset($_POST['product_discount']) ? floatval($_POST['product_discount']) : 0,
            'image' => isset($_POST['product_image']) ? htmlspecialchars($_POST['product_image']) : '',
            'category' => isset($_POST['product_category']) ? htmlspecialchars($_POST['product_category']) : '',
            'quantity' => intval($_POST['quantity'])
        ];
        
        // 检查商品是否已存在
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $product['id']) {
                $item['quantity'] += $product['quantity'];
                $found = true;
                break;
            }
        }
        
        // 如果商品不存在，添加到购物车
        if (!$found) {
            $_SESSION['cart'][] = $product;
        }
        
        // 返回成功响应
        echo json_encode([
            'success' => true, 
            'cart_count' => count($_SESSION['cart']),
            'cart_total' => calculateCartTotal($_SESSION['cart']),
            'cart' => $_SESSION['cart']
        ]);
        exit();
    }
    elseif ($_POST['action'] === 'sync_cart' && isset($_POST['cart_data'])) {
        // 同步购物车数据
        $cart_data = json_decode($_POST['cart_data'], true);
        if ($cart_data !== null) {
            $_SESSION['cart'] = $cart_data;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid cart data']);
        }
        exit();
    }
    elseif ($_POST['action'] === 'update_quantity') {
        if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            exit();
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $product_id) {
                if ($quantity <= 0) {
                    // 移除商品
                    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
                        return $item['id'] !== $product_id;
                    });
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // 重新索引数组
                } else {
                    $item['quantity'] = $quantity;
                }
                break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'cart_count' => count($_SESSION['cart']),
            'cart_total' => calculateCartTotal($_SESSION['cart']),
            'cart' => $_SESSION['cart']
        ]);
        exit();
    }
    elseif ($_POST['action'] === 'remove_item') {
        if (!isset($_POST['product_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing product_id']);
            exit();
        }
        
        $product_id = intval($_POST['product_id']);
        
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['id'] !== $product_id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']); // 重新索引数组
        
        echo json_encode([
            'success' => true,
            'cart_count' => count($_SESSION['cart']),
            'cart_total' => calculateCartTotal($_SESSION['cart']),
            'cart' => $_SESSION['cart']
        ]);
        exit();
    }
}

// 如果不是API请求，则正常显示页面
$page_title = "Shopping Cart";
require_once 'includes/header.php';

// 初始化购物车session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 获取当前购物车数据用于显示
$cart = $_SESSION['cart'];
?>

<div class="cart-section">
    <h1>Your Shopping Cart</h1>
    
    <?php if (empty($cart)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-3x"></i>
            <p>Your cart is empty</p>
            <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form id="cart-form" method="POST" action="cart.php" style="display: none;">
            <input type="hidden" name="action" value="sync_cart">
            <input type="hidden" id="cart-data" name="cart_data" value="">
        </form>
        
        <div id="cart-container">
            <!-- Cart items will be loaded via JavaScript -->
        </div>
        
        <div class="cart-summary" id="cart-summary">
            <!-- Summary will be calculated via JavaScript -->
        </div>
        
        <div class="cart-actions">
            <a href="products.php" class="btn btn-outline">Continue Shopping</a>
            <button type="button" class="btn btn-primary" id="checkout-btn">Proceed to Checkout</button>
        </div>
    <?php endif; ?>
</div>

<script>
// 全局添加购物车函数，可以在其他页面调用
function addToCart(product) {
    // 如果产品没有quantity属性，设置为1
    if (!product.quantity) {
        product.quantity = 1;
    }
    
    // 确保所有必需字段都存在
    if (!product.id || !product.name || !product.price) {
        console.error('Missing required product fields:', product);
        showNotification('Error: Missing product information', 'error');
        return;
    }
    
    // 发送AJAX请求添加商品
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'add_to_cart',
            product_id: product.id,
            product_name: product.name,
            product_price: product.price,
            product_discount: product.discount || 0,
            product_image: product.image || '',
            product_category: product.category || '',
            quantity: product.quantity
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 更新购物车数量显示
            updateCartCount(data.cart_count);
            
            // 更新本地存储
            if (data.cart) {
                localStorage.setItem('cart', JSON.stringify(data.cart));
            }
            
            // 显示成功消息
            showNotification('Item added to cart!', 'success');
            
            // 如果当前在购物车页面，重新加载购物车显示
            if (document.getElementById('cart-container')) {
                loadCart();
            }
        } else {
            console.error('Server error:', data.error);
            showNotification('Failed to add item to cart: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error. Please try again.', 'error');
    });
}

// 从服务器加载购物车数据
function loadCartFromServer() {
    return fetch('cart.php?get_cart=1')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // 保存到本地存储作为备份
            localStorage.setItem('cart', JSON.stringify(data));
            return data;
        })
        .catch(error => {
            console.error('Error loading cart from server:', error);
            const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
            return localCart;
        });
}

// 更新购物车数量显示
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        element.style.display = count > 0 ? 'inline' : 'none';
    });
}

// 显示通知
function showNotification(message, type = 'success') {
    // 移除现有的通知
    const existingNotification = document.querySelector('.cart-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // 创建新通知
    const notification = document.createElement('div');
    notification.className = `cart-notification ${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // 添加到页面
    document.body.appendChild(notification);
    
    // 3秒后自动消失
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 3000);
}

// 初始化购物车数量显示
document.addEventListener('DOMContentLoaded', function() {
    // 从服务器获取购物车数量
    loadCartFromServer()
        .then(cartData => {
            updateCartCount(cartData.length);
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
            // 如果服务器失败，使用本地存储
            const localCart = JSON.parse(localStorage.getItem('cart') || '[]');
            updateCartCount(localCart.length);
        });
});
</script>

<?php if (!empty($cart)): ?>
<script>
let cartData = <?php echo json_encode($cart); ?>;

function loadCart() {
    const container = document.getElementById('cart-container');
    const summary = document.getElementById('cart-summary');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    if (!cartData || cartData.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart fa-3x"></i>
                <p>Your cart is empty</p>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        `;
        summary.innerHTML = '';
        if (checkoutBtn) checkoutBtn.style.display = 'none';
        return;
    }
    
    let html = '';
    let subtotal = 0;
    
    cartData.forEach((item, index) => {
        const price = item.discount > 0 ? 
            item.price * (100 - item.discount) / 100 : 
            item.price;
        const total = price * item.quantity;
        subtotal += total;
        
        html += `
            <div class="cart-item" data-index="${index}" data-product-id="${item.id}">
                <div class="item-image">
                    <img src="${item.image}" alt="${item.name}" onerror="this.onerror=null; this.src='assets/images/game_default.jpg'">
                </div>
                <div class="item-info">
                    <h3>${item.name}</h3>
                    ${item.category ? `<p class="item-category">${item.category}</p>` : ''}
                    <p class="item-price">$${price.toFixed(2)} each</p>
                </div>
                <div class="item-quantity">
                    <button class="quantity-btn minus" type="button" onclick="updateCartItem(${item.id}, -1)">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn plus" type="button" onclick="updateCartItem(${item.id}, 1)">+</button>
                </div>
                <div class="item-total">
                    $${total.toFixed(2)}
                </div>
                <button class="remove-item" type="button" onclick="removeCartItem(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    
    const tax = subtotal * 0.1;
    const shipping = subtotal > 100 ? 0 : 10;
    const grandTotal = subtotal + tax + shipping;
    
    container.innerHTML = html;
    summary.innerHTML = `
        <h3>Order Summary</h3>
        <div class="summary-row">
            <span>Subtotal (${cartData.length} items)</span>
            <span>$${subtotal.toFixed(2)}</span>
        </div>
        <div class="summary-row">
            <span>Tax (10%)</span>
            <span>$${tax.toFixed(2)}</span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span>${shipping === 0 ? 'FREE' : `$${shipping.toFixed(2)}`}</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span>$${grandTotal.toFixed(2)}</span>
        </div>
    `;
    
    // 更新购物车数量显示
    updateCartCount(cartData.length);
}

function updateCartItem(productId, change) {
    // 在当前购物车数据中查找商品
    const itemIndex = cartData.findIndex(item => item.id === productId);
    
    if (itemIndex !== -1) {
        const newQuantity = cartData[itemIndex].quantity + change;
        
        if (newQuantity <= 0) {
            // 移除商品
            removeCartItem(productId);
            return;
        }
        
        // 更新数量
        cartData[itemIndex].quantity = newQuantity;
        
        // 发送更新请求到服务器
        fetch('cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_quantity',
                product_id: productId,
                quantity: newQuantity
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 更新本地数据
                if (data.cart) {
                    cartData = data.cart;
                }
                updateCartCount(data.cart_count);
                // 重新加载购物车显示
                loadCart();
                showNotification('Cart updated!', 'success');
            } else {
                throw new Error(data.error || 'Update failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to update cart', 'error');
            // 重新从服务器加载数据
            loadCartFromServer().then(data => {
                cartData = data;
                loadCart();
            });
        });
    }
}

function removeCartItem(productId) {
    // 从本地数据中移除
    cartData = cartData.filter(item => item.id !== productId);
    
    // 发送移除请求到服务器
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'remove_item',
            product_id: productId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 更新本地数据
            if (data.cart) {
                cartData = data.cart;
            }
            updateCartCount(data.cart_count);
            // 重新加载购物车显示
            loadCart();
            showNotification('Item removed from cart!', 'success');
        } else {
            throw new Error(data.error || 'Remove failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to remove item', 'error');
        // 重新从服务器加载数据
        loadCartFromServer().then(data => {
            cartData = data;
            loadCart();
        });
    });
}

// 检查是否登录，如果没有则提示
document.getElementById('checkout-btn')?.addEventListener('click', function(e) {
    if (!cartData || cartData.length === 0) {
        e.preventDefault();
        alert('Your cart is empty!');
        return;
    }
    
    // 确保session已更新
    updateSessionCart();
    
    // 检查用户是否登录
    fetch('includes/auth_check.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.logged_in) {
                // 用户已登录，跳转到结账页面
                window.location.href = 'checkout.php';
            } else {
                // 用户未登录，提示并跳转到登录页面
                if (confirm('You need to login to checkout. Go to login page?')) {
                    window.location.href = 'login.php?redirect=checkout.php';
                }
            }
        })
        .catch(error => {
            console.error('Error checking auth:', error);
            window.location.href = 'checkout.php';
        });
});

function updateSessionCart() {
    // 使用AJAX更新，避免页面刷新
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'sync_cart',
            cart_data: JSON.stringify(cartData)
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Failed to sync cart:', data.error);
        }
    })
    .catch(error => {
        console.error('Error syncing cart:', error);
    });
    
    // 更新本地存储
    localStorage.setItem('cart', JSON.stringify(cartData));
}

// 保存购物车数据到本地存储的函数
function saveCartToLocalStorage() {
    localStorage.setItem('cart', JSON.stringify(cartData));
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    // 优先使用服务器数据，如果失败则使用本地存储
    loadCartFromServer().then(data => {
        cartData = data;
        loadCart();
    });
});
</script>

<style>
.cart-section {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.cart-section h1 {
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

#cart-container {
    margin-bottom: 30px;
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 2fr 1fr 150px 100px 50px;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background: white;
    border-radius: 8px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.item-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #333;
}

.item-category {
    margin: 0;
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.item-price {
    font-size: 14px;
    color: #888;
}

.item-price, .item-total {
    font-weight: 600;
    color: #333;
}

.item-total {
    font-size: 16px;
    text-align: right;
}

.item-quantity {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #333;
}

.quantity-btn:hover {
    background: #f5f5f5;
    border-color: #007bff;
}

.quantity {
    font-weight: 600;
    min-width: 30px;
    text-align: center;
    font-size: 16px;
}

.remove-item {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 16px;
    padding: 5px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-item:hover {
    background: #f8d7da;
    color: #c82333;
}

.cart-summary {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    max-width: 400px;
    margin-left: auto;
}

.cart-summary h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.summary-row.total {
    border-bottom: none;
    border-top: 2px solid #007bff;
    margin-top: 10px;
    padding-top: 15px;
    font-weight: bold;
    font-size: 18px;
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-width: 500px;
    margin: 0 auto;
}

.empty-cart i {
    color: #ddd;
    margin-bottom: 20px;
}

.empty-cart p {
    font-size: 18px;
    margin-bottom: 20px;
}

.cart-actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
    text-align: center;
    border: 2px solid transparent;
    font-size: 16px;
}

.btn-outline {
    background: white;
    color: #007bff;
    border-color: #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

.btn-primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
}

/* 购物车通知样式 */
.cart-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 6px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease-out;
}

.cart-notification.success {
    background-color: #28a745;
    border-left: 4px solid #218838;
}

.cart-notification.error {
    background-color: #dc3545;
    border-left: 4px solid #c82333;
}

.cart-notification.info {
    background-color: #17a2b8;
    border-left: 4px solid #138496;
}

.cart-notification button {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    margin-left: 15px;
    opacity: 0.8;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-notification button:hover {
    opacity: 1;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
        padding: 15px;
    }
    
    .item-image img {
        width: 100%;
        height: 150px;
        margin: 0 auto;
    }
    
    .item-quantity {
        justify-content: center;
    }
    
    .item-total {
        text-align: center;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .cart-notification {
        min-width: auto;
        left: 20px;
        right: 20px;
    }
    
    .cart-summary {
        max-width: 100%;
    }
}
</style>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>