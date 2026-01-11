<?php
session_start();
$page_title = "Games";
require_once 'includes/header.php';
?>

<div class="products-section">
    <h1>Our Games Collection</h1>
    
    <div class="filters">
        <input type="text" id="search-input" placeholder="Search games...">
        <select id="category-filter">
            <option value="">All Categories</option>
            <option value="action">Action</option>
            <option value="rpg">RPG</option>
            <option value="strategy">Strategy</option>
            <option value="sports">Sports</option>
        </select>
        <select id="price-filter">
            <option value="">All Prices</option>
            <option value="0-20">Under $20</option>
            <option value="20-50">$20 - $50</option>
            <option value="50-100">$50 - $100</option>
        </select>
    </div>
    
    <div class="games-container" id="games-container">
        <!-- Games will be loaded via JavaScript -->
    </div>
</div>

<script>
let allGames = [];

// Load games from JSON
fetch('data/games.json')
    .then(response => response.json())
    .then(games => {
        allGames = games;
        displayGames(games);
        
        // Setup filters
        document.getElementById('search-input').addEventListener('input', filterGames);
        document.getElementById('category-filter').addEventListener('change', filterGames);
        document.getElementById('price-filter').addEventListener('change', filterGames);
    });

function displayGames(games) {
    const container = document.getElementById('games-container');
    container.innerHTML = '';
    
    games.forEach(game => {
        container.innerHTML += `
            <div class="game-card">
                <div class="game-image">
                    <img src="${game.image}" alt="${game.name}">
                    ${game.discount > 0 ? `<span class="discount-badge">-${game.discount}%</span>` : ''}
                </div>
                <div class="game-info">
                    <h3>${game.name}</h3>
                    <p class="game-category">${game.category}</p>
                    <p class="game-description">${game.short_description}</p>
                    <div class="game-price">
                        ${game.discount > 0 ? `
                            <span class="original-price">$${game.price}</span>
                            <span class="discounted-price">$${(game.price * (100 - game.discount) / 100).toFixed(2)}</span>
                        ` : `<span class="current-price">$${game.price}</span>`}
                    </div>
                    <div class="game-actions">
                        <a href="product_detail.php?id=${game.id}" class="btn btn-outline">View Details</a>
                        <button class="btn btn-primary add-to-cart" data-game='${JSON.stringify(game)}'>
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Add event listeners to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const game = JSON.parse(this.getAttribute('data-game'));
            addToCart(game);
        });
    });
}

function filterGames() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const category = document.getElementById('category-filter').value;
    const priceRange = document.getElementById('price-filter').value;
    
    let filtered = allGames.filter(game => {
        // Search filter
        if (searchTerm && !game.name.toLowerCase().includes(searchTerm) && 
            !game.category.toLowerCase().includes(searchTerm)) {
            return false;
        }
        
        // Category filter
        if (category && game.category.toLowerCase() !== category) {
            return false;
        }
        
        // Price filter
        if (priceRange) {
            const [min, max] = priceRange.split('-').map(Number);
            const price = game.discount > 0 ? 
                game.price * (100 - game.discount) / 100 : 
                game.price;
            
            if (max && (price < min || price > max)) {
                return false;
            } else if (!max && price > min) {
                return false;
            }
        }
        
        return true;
    });
    
    displayGames(filtered);
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
    showNotification(`${game.name} added to cart!`);
}

function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>