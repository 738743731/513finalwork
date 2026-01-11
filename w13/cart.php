<?php
session_start();
$page_title = "Shopping Cart";
require_once 'includes/header.php';
?>

<div class="cart-section">
    <h1>Your Shopping Cart</h1>
    
    <div id="cart-container">
        <!-- Cart items will be loaded via JavaScript -->
    </div>
    
    <div class="cart-summary" id="cart-summary">
        <!-- Summary will be calculated via JavaScript -->
    </div>
    
    <div class="cart-actions">
        <a href="products.php" class="btn btn-outline">Continue Shopping</a>
        <a href="checkout.php" class="btn btn-primary" id="checkout-btn">Proceed to Checkout</a>
    </div>
</div>

<script>
function loadCart() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const container = document.getElementById('cart-container');
    const summary = document.getElementById('cart-summary');
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><p>Your cart is empty</p></div>';
        summary.innerHTML = '';
        document.getElementById('checkout-btn').style.display = 'none';
        return;
    }
    
    let html = '';
    let subtotal = 0;
    
    cart.forEach((item, index) => {
        const price = item.discount > 0 ? 
            item.price * (100 - item.discount) / 100 : 
            item.price;
        const total = price * item.quantity;
        subtotal += total;
        
        html += `
            <div class="cart-item" data-index="${index}">
                <img src="${item.image}" alt="${item.name}">
                <div class="item-info">
                    <h3>${item.name}</h3>
                    <p class="item-category">${item.category}</p>
                </div>
                <div class="item-price">
                    $${price.toFixed(2)}
                </div>
                <div class="item-quantity">
                    <button class="quantity-btn minus">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn plus">+</button>
                </div>
                <div class="item-total">
                    $${total.toFixed(2)}
                </div>
                <button class="remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });
    
    const tax = subtotal * 0.1; // 10% tax
    const shipping = subtotal > 100 ? 0 : 10; // Free shipping over $100
    const grandTotal = subtotal + tax + shipping;
    
    container.innerHTML = html;
    summary.innerHTML = `
        <h3>Order Summary</h3>
        <div class="summary-row">
            <span>Subtotal</span>
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
    
    // Add event listeners
    document.querySelectorAll('.quantity-btn').forEach(btn => {
        btn.addEventListener('click', updateQuantity);
    });
    
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', removeItem);
    });
}

function updateQuantity(e) {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const index = parseInt(e.target.closest('.cart-item').getAttribute('data-index'));
    const isPlus = e.target.classList.contains('plus');
    
    if (isPlus) {
        cart[index].quantity += 1;
    } else {
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
        } else {
            cart.splice(index, 1);
        }
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCart();
}

function removeItem(e) {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const index = parseInt(e.target.closest('.cart-item').getAttribute('data-index'));
    
    cart.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCart();
}

// Initial load
loadCart();
</script>

<?php require_once 'includes/footer.php'; ?>