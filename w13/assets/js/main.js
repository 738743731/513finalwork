// Main JavaScript file for GameHub

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
    setupEventListeners();
    loadCartCount();
});

// Initialize all components
function initializeComponents() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize dropdowns
    initDropdowns();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Initialize image lazy loading
    initLazyLoading();
    
    // Initialize form validation
    initFormValidation();
}

// Setup global event listeners
function setupEventListeners() {
    // Add to cart buttons (for dynamically loaded content)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart')) {
            const button = e.target.closest('.add-to-cart');
            const gameData = button.dataset.game;
            if (gameData) {
                const game = JSON.parse(gameData);
                addToCart(game);
            }
        }
        
        // Remove from cart
        if (e.target.closest('.remove-from-cart')) {
            const button = e.target.closest('.remove-from-cart');
            const gameId = button.dataset.gameId;
            removeFromCart(gameId);
        }
        
        // Quantity updates
        if (e.target.closest('.quantity-update')) {
            const button = e.target.closest('.quantity-update');
            const gameId = button.dataset.gameId;
            const change = button.dataset.change;
            updateCartQuantity(gameId, parseInt(change));
        }
    });
    
    // Search functionality
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }
}

// Cart Functions
function loadCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    
    // Update cart count in header
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = cartCount;
        element.style.display = cartCount > 0 ? 'inline' : 'none';
    });
    
    return cartCount;
}

function addToCart(game) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === game.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        game.quantity = 1;
        cart.push(game);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCartCount();
    showNotification(`${game.name} added to cart!`, 'success');
    
    // Update cart page if it's open
    if (document.getElementById('cart-container')) {
        loadCartPage();
    }
}

function removeFromCart(gameId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== parseInt(gameId));
    localStorage.setItem('cart', JSON.stringify(cart));
    loadCartCount();
    showNotification('Item removed from cart!', 'info');
    
    if (document.getElementById('cart-container')) {
        loadCartPage();
    }
}

function updateCartQuantity(gameId, change) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemIndex = cart.findIndex(item => item.id === parseInt(gameId));
    
    if (itemIndex !== -1) {
        cart[itemIndex].quantity += change;
        
        if (cart[itemIndex].quantity <= 0) {
            cart.splice(itemIndex, 1);
            showNotification('Item removed from cart!', 'info');
        } else {
            showNotification('Cart updated!', 'success');
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        loadCartCount();
        
        if (document.getElementById('cart-container')) {
            loadCartPage();
        }
    }
}

function loadCartPage() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const container = document.getElementById('cart-container');
    const summary = document.getElementById('cart-summary');
    
    if (!container) return;
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some games to get started!</p>
                <a href="products.php" class="btn btn-primary">Browse Games</a>
            </div>
        `;
        if (summary) summary.innerHTML = '';
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
                <div class="item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="item-details">
                    <h4>${item.name}</h4>
                    <p class="item-category">${item.category}</p>
                    <p class="item-price">$${price.toFixed(2)} each</p>
                </div>
                <div class="item-quantity">
                    <button class="btn btn-sm quantity-update" data-game-id="${item.id}" data-change="-1">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="btn btn-sm quantity-update" data-game-id="${item.id}" data-change="1">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="item-total">
                    <strong>$${total.toFixed(2)}</strong>
                </div>
                <div class="item-remove">
                    <button class="btn btn-danger btn-sm remove-from-cart" data-game-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    const tax = subtotal * 0.1;
    const shipping = subtotal > 100 ? 0 : 9.99;
    const grandTotal = subtotal + tax + shipping;
    
    container.innerHTML = html;
    
    if (summary) {
        summary.innerHTML = `
            <div class="summary-card">
                <h4>Order Summary</h4>
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
                <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
            </div>
        `;
    }
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto remove after 5 seconds
    const autoRemove = setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
    
    // Close button
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', function() {
        clearTimeout(autoRemove);
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

// Search Functions
function performSearch(query) {
    if (!query) return;
    
    // Here you would typically make an AJAX request to search the server
    // For now, we'll just show a notification
    if (query.length >= 3) {
        console.log(`Searching for: ${query}`);
        // You can implement actual search logic here
    }
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

// Component Initializers
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltipText = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                dropdown.classList.toggle('open');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
}

function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            this.classList.toggle('open');
        });
    }
}

function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
}

function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            markInvalid(input, 'This field is required');
            isValid = false;
        } else {
            markValid(input);
            
            // Additional validation based on input type
            switch(input.type) {
                case 'email':
                    if (!isValidEmail(input.value)) {
                        markInvalid(input, 'Please enter a valid email address');
                        isValid = false;
                    }
                    break;
                case 'tel':
                    if (!isValidPhone(input.value)) {
                        markInvalid(input, 'Please enter a valid phone number');
                        isValid = false;
                    }
                    break;
                case 'number':
                    if (input.min && parseFloat(input.value) < parseFloat(input.min)) {
                        markInvalid(input, `Value must be at least ${input.min}`);
                        isValid = false;
                    }
                    if (input.max && parseFloat(input.value) > parseFloat(input.max)) {
                        markInvalid(input, `Value must be at most ${input.max}`);
                        isValid = false;
                    }
                    break;
            }
        }
    });
    
    return isValid;
}

function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function isValidPhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(String(phone).replace(/[\s\-\(\)]/g, ''));
}

function markInvalid(input, message) {
    input.classList.add('invalid');
    input.classList.remove('valid');
    
    // Remove existing error message
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const error = document.createElement('span');
    error.className = 'error-message';
    error.textContent = message;
    input.parentNode.appendChild(error);
}

function markValid(input) {
    input.classList.add('valid');
    input.classList.remove('invalid');
    
    // Remove existing error message
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
}

// AJAX Functions
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        if (data && method === 'POST') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(`Request failed with status ${xhr.status}`));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        if (data && method === 'POST') {
            const formData = new URLSearchParams();
            for (const key in data) {
                formData.append(key, data[key]);
            }
            xhr.send(formData);
        } else {
            xhr.send();
        }
    });
}

// Cookie Functions
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    document.cookie = name + '=; Max-Age=-99999999;';
}

// Export functions for use in other scripts
window.GameHub = {
    addToCart,
    removeFromCart,
    updateCartQuantity,
    loadCartCount,
    showNotification,
    ajaxRequest,
    formatPrice,
    setCookie,
    getCookie,
    eraseCookie
};

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeComponents);
} else {
    initializeComponents();
}