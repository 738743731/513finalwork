<?php
session_start();
$page_title = "Games";
require_once 'includes/header.php';
?>

<div class="products-section">
    <h1>Our Games Collection</h1>
    
    <div class="filters">
        <div class="search-container" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="search-input" placeholder="Search games..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <button id="search-button" class="btn btn-outline" style="white-space: nowrap; padding: 8px 16px;">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
        <select id="category-filter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">All Categories</option>
            <option value="action">Action</option>
            <option value="rpg">RPG</option>
            <option value="racing">Racing</option>
            <option value="adventure">Adventure</option>
            <option value="sports">Sports</option>
            <option value="shooter">Shooter</option>
            <option value="strategy">Strategy</option>
            <option value="simulation">Simulation</option>
            <option value="horror">Horror</option>
            <option value="indie">Indie</option>
            <option value="puzzle">Puzzle</option>
        </select>
        <select id="price-filter" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">All Prices</option>
            <option value="0-20">Under $20</option>
            <option value="20-50">$20 - $50</option>
            <option value="50-100">$50 - $100</option>
            <option value="100-">Over $100</option>
        </select>
    </div>
    
    <div class="games-container" id="games-container">
        <!-- Games will be loaded via JavaScript from the API -->
    </div>
    
    <div id="loading-indicator" style="text-align: center; padding: 20px;">
        <div class="loading-spinner"></div>
        <p>Loading games...</p>
    </div>
    
    <div id="api-status" style="display: none; text-align: center; padding: 10px; margin-top: 10px; border-radius: 5px; font-size: 0.9rem;">
        <i class="fas fa-info-circle"></i>
        <span id="api-status-text"></span>
    </div>
</div>

<script>
let allGames = [];
let isLoading = false;

// Show loading indicator
function showLoading() {
    document.getElementById('loading-indicator').style.display = 'block';
    isLoading = true;
}

// Hide loading indicator
function hideLoading() {
    document.getElementById('loading-indicator').style.display = 'none';
    isLoading = false;
}

// Update API status message
function updateApiStatus(message, type = 'info') {
    const statusDiv = document.getElementById('api-status');
    const statusText = document.getElementById('api-status-text');
    
    statusText.textContent = message;
    
    // Set color based on type
    switch(type) {
        case 'success':
            statusDiv.style.backgroundColor = '#d4edda';
            statusDiv.style.color = '#155724';
            statusDiv.style.border = '1px solid #c3e6cb';
            break;
        case 'warning':
            statusDiv.style.backgroundColor = '#fff3cd';
            statusDiv.style.color = '#856404';
            statusDiv.style.border = '1px solid #ffeaa7';
            break;
        case 'error':
            statusDiv.style.backgroundColor = '#f8d7da';
            statusDiv.style.color = '#721c24';
            statusDiv.style.border = '1px solid #f5c6cb';
            break;
        default: // info
            statusDiv.style.backgroundColor = '#d1ecf1';
            statusDiv.style.color = '#0c5460';
            statusDiv.style.border = '1px solid #bee5eb';
    }
    
    statusDiv.style.display = 'block';
}

// Hide API status
function hideApiStatus() {
    document.getElementById('api-status').style.display = 'none';
}

// Try to load games from API first, fallback to local JSON if fails
function loadGames(filters = {}) {
    if (isLoading) return; // Prevent multiple simultaneous requests
    
    showLoading();
    hideApiStatus();
    
    console.log('Applying filters:', filters);
    
    // Build query string from filters
    const params = new URLSearchParams();
    
    if (filters.search) {
        params.append('search', filters.search);
    }
    
    if (filters.category) {
        params.append('category', filters.category);
    }
    
    if (filters.priceRange) {
        const [min, max] = filters.priceRange.split('-').map(Number);
        params.append('min_price', min);
        if (max) {
            params.append('max_price', max);
        }
    }
    
    // Try to load from JSON file directly first (for immediate display)
    fetch('data/games.json?' + new Date().getTime()) // Add timestamp to prevent caching
        .then(response => {
            if (!response.ok) {
                throw new Error('JSON file not found');
            }
            return response.json();
        })
        .then(games => {
            console.log('Loaded', games.length, 'games from local JSON');
            allGames = games;
            
            // Apply local filtering
            const filteredGames = applyLocalFilters(games, filters);
            displayGames(filteredGames);
            hideLoading();
            
            // Show success status
            updateApiStatus(`Found ${filteredGames.length} games${filters.search ? ' for "' + filters.search + '"' : ''}`, 'success');
            
            // Auto-hide success message after 3 seconds
            setTimeout(() => {
                hideApiStatus();
            }, 3000);
            
            // Also try to load from API in background to check for updates
            fetchApiData();
        })
        .catch(localError => {
            console.warn('Local JSON failed, trying API:', localError.message);
            
            // Try API as fallback
            fetchApiData(filters);
        });
}

// Fetch data from API (background update)
function fetchApiData(filters = {}) {
    const apiUrl = 'api/get_games.php';
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`API responded with ${response.status}`);
            }
            return response.json();
        })
        .then(apiGames => {
            console.log('API returned', apiGames.length, 'games');
            
            // Compare with local games
            if (apiGames.length !== allGames.length) {
                console.log('API has different game count, updating local display');
                allGames = apiGames;
                const filteredGames = applyLocalFilters(apiGames, filters);
                displayGames(filteredGames);
                
                // Show update notification
                updateApiStatus(`Updated with ${apiGames.length} games from server`, 'info');
            }
        })
        .catch(apiError => {
            console.log('API fetch failed (non-critical):', apiError.message);
        });
}

// Apply local filtering to games
function applyLocalFilters(games, filters) {
    if (!games || games.length === 0) return [];
    
    let filteredGames = [...games];
    
    // Search filter
    if (filters.search) {
        const searchLower = filters.search.toLowerCase();
        filteredGames = filteredGames.filter(game => 
            game.name.toLowerCase().includes(searchLower) || 
            (game.short_description && game.short_description.toLowerCase().includes(searchLower)) ||
            (game.category && game.category.toLowerCase().includes(searchLower))
        );
    }
    
    // Category filter
    if (filters.category) {
        const categoryLower = filters.category.toLowerCase();
        filteredGames = filteredGames.filter(game => 
            game.category && game.category.toLowerCase() === categoryLower
        );
    }
    
    // Price range filter
    if (filters.priceRange) {
        const [min, max] = filters.priceRange.split('-').map(Number);
        filteredGames = filteredGames.filter(game => {
            const price = parseFloat(game.price);
            if (max) {
                return price >= min && price <= max;
            } else {
                return price >= min;
            }
        });
    }
    
    return filteredGames;
}

// Initial load of games
document.addEventListener('DOMContentLoaded', function() {
    console.log('Products page loaded');
    loadGames();
    
    // Setup search button
    const searchButton = document.getElementById('search-button');
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            console.log('Search button clicked');
            e.preventDefault();
            applyFilters();
        });
    }
    
    // Setup search input - with debounce
    const searchInput = document.getElementById('search-input');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                console.log('Search input changed:', this.value);
                applyFilters();
            }, 500); // 500ms delay
        });
        
        // Add event listener for Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                console.log('Enter key pressed in search');
                e.preventDefault();
                applyFilters();
            }
        });
    }
    
    // Setup other filters
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            console.log('Category changed:', this.value);
            applyFilters();
        });
    }
    
    const priceFilter = document.getElementById('price-filter');
    if (priceFilter) {
        priceFilter.addEventListener('change', function() {
            console.log('Price filter changed:', this.value);
            applyFilters();
        });
    }
    
    // Add refresh button functionality
    const refreshBtn = document.createElement('button');
    refreshBtn.className = 'btn btn-outline';
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
    refreshBtn.style.marginLeft = '10px';
    refreshBtn.onclick = function() {
        console.log('Manual refresh');
        loadGames();
        showToast('Refreshing games list...', 'info');
    };
    
    // Add refresh button next to title
    const title = document.querySelector('.products-section h1');
    if (title) {
        title.style.display = 'flex';
        title.style.alignItems = 'center';
        title.style.justifyContent = 'space-between';
        const titleText = document.createElement('span');
        titleText.textContent = title.textContent;
        title.innerHTML = '';
        title.appendChild(titleText);
        title.appendChild(refreshBtn);
    }
});

function applyFilters() {
    console.log('Applying filters...');
    const filters = {
        search: document.getElementById('search-input').value.trim(),
        category: document.getElementById('category-filter').value,
        priceRange: document.getElementById('price-filter').value
    };
    
    console.log('Current filters:', filters);
    loadGames(filters);
}

function displayGames(games) {
    const container = document.getElementById('games-container');
    
    if (!games || games.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-gamepad" style="font-size: 48px; margin-bottom: 20px; color: #ccc;"></i>
                <h3>No Games Found</h3>
                <p>Try adjusting your filters or search term.</p>
                <button onclick="resetFilters()" class="btn btn-primary" style="margin-top: 20px;">Reset Filters</button>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    games.forEach(game => {
        // Calculate discounted price
        const discountedPrice = game.discount > 0 ? 
            (game.price * (100 - game.discount) / 100).toFixed(2) : 
            null;
        
        // Use default image if game.image is not set or empty
        const gameImage = game.image && game.image.trim() !== '' ? 
            game.image : 
            'assets/images/game_default.jpg';
        
        html += `
            <div class="game-card" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; margin-bottom: 20px; background: white;">
                <div class="game-image" style="position: relative; height: 200px; overflow: hidden;">
                    <img src="${gameImage}" alt="${game.name}" style="width: 100%; height: 100%; object-fit: cover;" 
                         onerror="this.onerror=null; this.src='assets/images/game_default.jpg'">
                    ${game.discount > 0 ? `<span class="discount-badge" style="position: absolute; top: 10px; right: 10px; background: #ff4444; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; z-index: 2;">-${game.discount}%</span>` : ''}
                    ${game.rating > 0 ? `<span class="rating-badge" style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: #ffd700; padding: 4px 8px; border-radius: 4px; font-weight: bold; z-index: 2;">
                        <i class="fas fa-star"></i> ${game.rating.toFixed(1)}
                    </span>` : ''}
                </div>
                <div class="game-info" style="padding: 15px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #333;">${game.name}</h3>
                    <p class="game-category" style="color: #666; margin: 0 0 10px 0; font-size: 14px;">
                        <i class="fas fa-tag"></i> ${game.category}
                        ${game.release_date ? `<span style="margin-left: 10px; color: #999;"><i class="far fa-calendar"></i> ${game.release_date.substring(0, 4)}</span>` : ''}
                    </p>
                    <p class="game-description" style="color: #777; margin: 0 0 15px 0; font-size: 14px; line-height: 1.4;">${game.short_description || ''}</p>
                    <div class="game-price" style="margin-bottom: 15px;">
                        ${game.discount > 0 ? `
                            <span class="original-price" style="text-decoration: line-through; color: #999; margin-right: 10px;">$${parseFloat(game.price).toFixed(2)}</span>
                            <span class="discounted-price" style="color: #ff4444; font-weight: bold; font-size: 18px;">$${discountedPrice}</span>
                        ` : `<span class="current-price" style="font-weight: bold; font-size: 18px;">$${parseFloat(game.price).toFixed(2)}</span>`}
                    </div>
                    <div class="game-actions" style="display: flex; gap: 10px;">
                        <a href="product_detail.php?id=${game.id}" class="btn btn-outline" style="flex: 1; text-align: center; padding: 8px; text-decoration: none; border: 1px solid #007bff; color: #007bff;">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                        <button class="btn btn-primary add-to-cart" data-game-id="${game.id}" style="flex: 1; padding: 8px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Use CSS grid for better layout
    container.innerHTML = `
        <div class="games-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            ${html}
        </div>
    `;
    
    // Add event listeners to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const gameId = this.getAttribute('data-game-id');
            const game = allGames.find(g => g.id == gameId);
            if (game) {
                addToCartLocal(game);
            }
        });
    });
}

// Local add to cart function
function addToCartLocal(game) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === game.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        const gameCopy = {...game};
        gameCopy.quantity = 1;
        cart.push(gameCopy);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Show notification
    showToast(`${game.name} added to cart!`, 'success');
    
    // Update cart count
    updateCartCount();
}

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
        ${message}
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; margin-left: 10px; cursor: pointer;">×</button>
    `;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = cart.reduce((total, item) => total + (item.quantity || 1), 0);
    
    // Update cart count in navigation if it exists
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = cartCount;
        element.style.display = cartCount > 0 ? 'inline' : 'none';
    });
}

function resetFilters() {
    console.log('Resetting filters');
    document.getElementById('search-input').value = '';
    document.getElementById('category-filter').value = '';
    document.getElementById('price-filter').value = '';
    loadGames();
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .game-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
    `;
    document.head.appendChild(style);
});
</script>

<style>
.products-section {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.products-section h1 {
    margin-bottom: 30px;
    color: #333;
}

.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-items: center;
}

.search-container {
    flex: 1;
    min-width: 300px;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn:hover {
    background: #f5f5f5;
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
    background: transparent;
    color: #007bff;
    border-color: #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
    }
    
    .search-container {
        min-width: 100%;
    }
    
    .products-section {
        padding: 15px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>