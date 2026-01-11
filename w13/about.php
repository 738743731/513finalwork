<?php
session_start();
$page_title = "About Us";
require_once 'includes/header.php';
?>

<div class="about-section">
    <h1>About Game<span>Hub</span></h1>
    
    <div class="about-content">
        <div class="about-text">
            <h2>Our Mission</h2>
            <p>At GameHub, we're passionate about delivering exceptional gaming experiences to players worldwide. Founded in 2010, we've grown from a small startup to one of the leading digital game distributors.</p>
            
            <h2>Who We Are</h2>
            <p>We are a team of gamers, developers, and industry professionals dedicated to creating the ultimate gaming marketplace. With over 50 employees across 3 continents, we serve millions of customers globally.</p>
            
            <h2>Our Values</h2>
            <ul>
                <li><strong>Customer First:</strong> Your satisfaction is our priority</li>
                <li><strong>Quality Assurance:</strong> Every game is thoroughly tested</li>
                <li><strong>Innovation:</strong> Constantly evolving our platform</li>
                <li><strong>Community Focus:</strong> Building strong gaming communities</li>
            </ul>
            
            <h2>Company Statistics</h2>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number">1M+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">10K+</span>
                    <span class="stat-label">Games Available</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Countries Served</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">12</span>
                    <span class="stat-label">Years Experience</span>
                </div>
            </div>
            
            <h2>Visit Our Headquarters</h2>
            <div class="map-container">
                <iframe 
                    src="assets/images/map.jpg" 
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                <p class="map-address">
                    <strong>Address:</strong> 123 Gaming Street, New York, NY 10001, United States<br>
                    <strong>Business Hours:</strong> Monday - Friday, 9:00 AM - 6:00 PM EST<br>
                    <strong>Phone:</strong> +1 (555) 123-4567
                </p>
            </div>
        </div>
        
        <div class="about-image">
            <img src="assets/images/office.jpg" alt="Our Office">
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>