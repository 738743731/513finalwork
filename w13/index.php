<?php
session_start();
$page_title = "Home";
require_once 'includes/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <h1>Welcome to Game<span>Hub</span></h1>
        <p class="tagline">Your Ultimate Destination for Gaming Excellence</p>
        <p>Discover the latest and greatest games at unbeatable prices</p>
        <div class="hero-buttons">
            <a href="products.php" class="btn btn-primary">Browse Games</a>
            <a href="register.php" class="btn btn-secondary">Join Now</a>
        </div>
    </div>
</div>

<div class="features">
    <div class="feature">
        <i class="fas fa-shipping-fast"></i>
        <h3>Fast Delivery</h3>
        <p>Instant digital downloads</p>
    </div>
    <div class="feature">
        <i class="fas fa-shield-alt"></i>
        <h3>Secure Payment</h3>
        <p>100% secure transactions</p>
    </div>
    <div class="feature">
        <i class="fas fa-headset"></i>
        <h3>24/7 Support</h3>
        <p>Always here to help</p>
    </div>
    <div class="feature">
        <i class="fas fa-gamepad"></i>
        <h3>1000+ Games</h3>
        <p>Massive collection</p>
    </div>
</div>

<div class="highlighted-games">
    <h2>Featured Games</h2>
    <div class="games-grid" id="featured-games">
        <!-- Games will be loaded via JavaScript -->
    </div>
</div>

<script>
// Load featured games from JSON
fetch('data/games.json')
    .then(response => response.json())
    .then(games => {
        const featuredContainer = document.getElementById('featured-games');
        // Show first 4 games as featured
        games.slice(0, 4).forEach(game => {
            featuredContainer.innerHTML += `
                <div class="game-card">
                    <img src="${game.image}" alt="${game.name}">
                    <h3>${game.name}</h3>
                    <p class="price">$${game.price}</p>
                    <a href="product_detail.php?id=${game.id}" class="btn">View Details</a>
                </div>
            `;
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>