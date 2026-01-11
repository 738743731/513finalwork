<?php
session_start();
require_once 'includes/header.php';

// Load games data
$games = json_decode(file_get_contents('data/games.json'), true);

// Get game ID from URL
$game_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Find the game
$game = null;
foreach ($games as $g) {
    if ($g['id'] == $game_id) {
        $game = $g;
        break;
    }
}

if (!$game) {
    header("Location: products.php");
    exit();
}

$page_title = $game['name'];
?>

<div class="product-detail">
    <div class="product-header">
        <div class="product-image">
            <img src="<?php echo $game['image']; ?>" alt="<?php echo $game['name']; ?>">
            <?php if($game['discount'] > 0): ?>
                <div class="discount-badge">-<?php echo $game['discount']; ?>%</div>
            <?php endif; ?>
        </div>
        
        <div class="product-info">
            <h1><?php echo $game['name']; ?></h1>
            <p class="product-developer">By <?php echo $game['developer']; ?></p>
            
            <div class="product-meta">
                <span class="category"><?php echo $game['category']; ?></span>
                <span class="rating">
                    <?php for($i = 0; $i < floor($game['rating']); $i++): ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                    <?php if($game['rating'] - floor($game['rating']) >= 0.5): ?>
                        <i class="fas fa-star-half-alt"></i>
                    <?php endif; ?>
                    (<?php echo $game['rating']; ?>)
                </span>
            </div>
            
            <div class="product-price">
                <?php if($game['discount'] > 0): ?>
                    <span class="original-price">$<?php echo $game['price']; ?></span>
                    <span class="current-price">
                        $<?php echo number_format($game['price'] * (100 - $game['discount']) / 100, 2); ?>
                    </span>
                    <span class="discount-text">Save <?php echo $game['discount']; ?>%</span>
                <?php else: ?>
                    <span class="current-price">$<?php echo $game['price']; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="product-actions">
                <button class="btn btn-primary add-to-cart" data-game='<?php echo json_encode($game); ?>'>
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
                <button class="btn btn-outline wishlist-btn">
                    <i class="far fa-heart"></i> Add to Wishlist
                </button>
            </div>
            
            <div class="product-specs">
                <div class="spec">
                    <span class="spec-label">Release Date:</span>
                    <span class="spec-value"><?php echo date('F j, Y', strtotime($game['release_date'])); ?></span>
                </div>
                <div class="spec">
                    <span class="spec-label">Publisher:</span>
                    <span class="spec-value"><?php echo $game['publisher']; ?></span>
                </div>
                <div class="spec">
                    <span class="spec-label">Platforms:</span>
                    <span class="spec-value"><?php echo implode(', ', $game['platforms']); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="product-content">
        <div class="product-description">
            <h2>Description</h2>
            <p><?php echo $game['long_description']; ?></p>
            
            <h2>Key Features</h2>
            <ul class="features-list">
                <?php foreach($game['features'] as $feature): ?>
                    <li><i class="fas fa-check-circle"></i> <?php echo $feature; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="product-sidebar">
            <div class="sidebar-card">
                <h3>System Requirements</h3>
                <div class="requirements">
                    <div class="req-category">
                        <h4>Minimum:</h4>
                        <p>OS: Windows 10</p>
                        <p>Processor: Intel Core i5</p>
                        <p>Memory: 8 GB RAM</p>
                        <p>Graphics: NVIDIA GTX 1060</p>
                        <p>Storage: 50 GB available space</p>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-card">
                <h3>Support</h3>
                <p>Need help with your purchase?</p>
                <a href="contact.php" class="btn btn-outline">Contact Support</a>
            </div>
        </div>
    </div>
</div>

<script>
// Add to cart functionality
document.querySelector('.add-to-cart').addEventListener('click', function() {
    const game = <?php echo json_encode($game); ?>;
    
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItem = cart.find(item => item.id === game.id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        game.quantity = 1;
        cart.push(game);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Show notification
    showNotification(`${game.name} added to cart!`);
});

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