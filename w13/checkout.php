<?php
session_start();
require_once 'includes/auth.php';
checkAuthentication();
$page_title = "Checkout";
require_once 'includes/header.php';
?>

<div class="checkout-section">
    <h1>Checkout</h1>
    
    <div class="checkout-content">
        <div class="checkout-form">
            <h2>Billing Information</h2>
            <form id="checkout-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo $_SESSION['email'] ?? ''; ?>" required>
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
                    </select>
                </div>
                
                <h2>Payment Information</h2>
                
                <div class="form-group">
                    <label for="card_name">Name on Card *</label>
                    <input type="text" id="card_name" name="card_name" required>
                </div>
                
                <div class="form-group">
                    <label for="card_number">Card Number *</label>
                    <input type="text" id="card_number" name="card_number" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry">Expiry Date *</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cvv">CVV *</label>
                        <input type="text" id="cvv" name="cvv" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Complete Purchase</button>
            </form>
        </div>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div id="checkout-summary">
                <!-- Will be filled by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function loadCheckoutSummary() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const summary = document.getElementById('checkout-summary');
    
    if (cart.length === 0) {
        summary.innerHTML = '<p>Your cart is empty</p>';
        return;
    }
    
    let html = '';
    let subtotal = 0;
    
    cart.forEach(item => {
        const price = item.discount > 0 ? 
            item.price * (100 - item.discount) / 100 : 
            item.price;
        const total = price * item.quantity;
        subtotal += total;
        
        html += `
            <div class="checkout-item">
                <span>${item.name} x ${item.quantity}</span>
                <span>$${total.toFixed(2)}</span>
            </div>
        `;
    });
    
    const tax = subtotal * 0.1;
    const shipping = subtotal > 100 ? 0 : 10;
    const grandTotal = subtotal + tax + shipping;
    
    summary.innerHTML = html + `
        <div class="checkout-total">
            <div class="total-row">
                <span>Subtotal</span>
                <span>$${subtotal.toFixed(2)}</span>
            </div>
            <div class="total-row">
                <span>Tax</span>
                <span>$${tax.toFixed(2)}</span>
            </div>
            <div class="total-row">
                <span>Shipping</span>
                <span>${shipping === 0 ? 'FREE' : `$${shipping.toFixed(2)}`}</span>
            </div>
            <div class="total-row grand-total">
                <span>Total</span>
                <span>$${grandTotal.toFixed(2)}</span>
            </div>
        </div>
    `;
}

document.getElementById('checkout-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    // Create purchase data
    const purchaseData = {
        customer: {
            name: document.getElementById('full_name').value,
            email: document.getElementById('email').value,
            address: document.getElementById('address').value,
            city: document.getElementById('city').value,
            zip: document.getElementById('zip').value,
            country: document.getElementById('country').value
        },
        items: cart,
        total: calculateTotal(cart),
        date: new Date().toISOString()
    };
    
    // Save purchase to localStorage for demo
    localStorage.setItem('last_purchase', JSON.stringify(purchaseData));
    
    // Clear cart
    localStorage.removeItem('cart');
    
    // Show success message
    alert('Purchase successful! Thank you for your order.');
    
    // Redirect to homepage
    window.location.href = 'index.php';
});

function calculateTotal(cart) {
    let subtotal = 0;
    cart.forEach(item => {
        const price = item.discount > 0 ? 
            item.price * (100 - item.discount) / 100 : 
            item.price;
        subtotal += price * item.quantity;
    });
    const tax = subtotal * 0.1;
    const shipping = subtotal > 100 ? 0 : 10;
    return subtotal + tax + shipping;
}

// Initial load
loadCheckoutSummary();
</script>

<?php require_once 'includes/footer.php'; ?>