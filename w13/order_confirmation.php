<?php
session_start();
require_once 'includes/auth.php';

// 检查是否有订单信息
if (!isset($_SESSION['last_order'])) {
    header("Location: products.php");
    exit();
}

$order = $_SESSION['last_order'];
unset($_SESSION['last_order']); // 清除订单信息

$page_title = "Order Confirmation";
require_once 'includes/header.php';
?>

<div class="order-confirmation">
    <div class="confirmation-header">
        <i class="fas fa-check-circle fa-3x success-icon"></i>
        <h1>Order Confirmed!</h1>
        <p class="subtitle">Thank you for your purchase</p>
    </div>
    
    <div class="confirmation-details">
        <div class="order-info">
            <h2>Order Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Order ID:</span>
                    <span class="value">#<?php echo $order['order_id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Order Date:</span>
                    <span class="value"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Customer Name:</span>
                    <span class="value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Shipping Address:</span>
                    <span class="value"><?php echo htmlspecialchars($order['address']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="order-items">
            <h2>Order Items</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): 
                        $price = isset($item['discount']) && $item['discount'] > 0 ? 
                            $item['price'] * (100 - $item['discount']) / 100 : 
                            $item['price'];
                        $total = $price * $item['quantity'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($price, 2); ?></td>
                        <td>$<?php echo number_format($total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="order-totals">
            <h2>Order Summary</h2>
            <div class="totals-grid">
                <div class="total-item">
                    <span class="label">Subtotal:</span>
                    <span class="value">$<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                <div class="total-item">
                    <span class="label">Tax (10%):</span>
                    <span class="value">$<?php echo number_format($order['tax'], 2); ?></span>
                </div>
                <div class="total-item">
                    <span class="label">Shipping:</span>
                    <span class="value"><?php echo $order['shipping'] === 0 ? 'FREE' : '$' . number_format($order['shipping'], 2); ?></span>
                </div>
                <div class="total-item grand-total">
                    <span class="label">Total:</span>
                    <span class="value">$<?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="confirmation-actions">
            <p class="instructions">You will receive an email confirmation shortly. Your order will be processed and shipped within 2-3 business days.</p>
            <div class="action-buttons">
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                
            </div>
        </div>
    </div>
</div>

<script>
// 清空本地购物车
document.addEventListener('DOMContentLoaded', function() {
    localStorage.removeItem('cart');
    // 更新购物车数量显示
    if (window.GameHub && window.GameHub.updateCartCount) {
        window.GameHub.updateCartCount(0);
    }
});
</script>

<style>
.order-confirmation {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.confirmation-header {
    text-align: center;
    margin-bottom: 40px;
}

.success-icon {
    color: #28a745;
    margin-bottom: 20px;
}

.confirmation-header h1 {
    margin-bottom: 10px;
    color: #333;
}

.subtitle {
    color: #666;
    font-size: 18px;
}

.confirmation-details {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.confirmation-details h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.info-grid, .totals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-item, .total-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.total-item.grand-total {
    border-top: 2px solid #007bff;
    margin-top: 10px;
    padding-top: 15px;
    font-weight: bold;
    font-size: 18px;
}

.label {
    font-weight: 600;
    color: #555;
}

.value {
    color: #333;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
}

.items-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.items-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.items-table tbody tr:hover {
    background: #f8f9fa;
}

.confirmation-actions {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.instructions {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
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

.btn-primary {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
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

@media (max-width: 768px) {
    .confirmation-details {
        padding: 20px;
    }
    
    .items-table {
        font-size: 14px;
    }
    
    .items-table th,
    .items-table td {
        padding: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>