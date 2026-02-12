<?php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$db = new Database();
$response = ['success' => false, 'message' => ''];

try {
    // Get all products from database
    $products = $db->fetchAll("SELECT * FROM products ORDER BY id DESC");
    
    // Transform to JSON format matching games.json
    $games = [];
    foreach ($products as $product) {
        $games[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'discount' => (float)$product['discount'],
            'category' => $product['category'],
            'short_description' => $product['short_description'],
            'long_description' => $product['long_description'],
            'image' => $product['image'],
            'developer' => $product['developer'],
            'publisher' => $product['publisher'],
            'release_date' => $product['release_date'],
            'platforms' => json_decode($product['platforms'], true) ?? ['PC'],
            'rating' => (float)$product['rating'],
            'features' => json_decode($product['features'], true) ?? []
        ];
    }
    
    // Path to JSON file
    $json_file = dirname(__DIR__) . '/data/games.json';
    
    // Ensure directory exists
    $json_dir = dirname($json_file);
    if (!is_dir($json_dir)) {
        mkdir($json_dir, 0755, true);
    }
    
    // Write to JSON file
    $json_data = json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($json_file, $json_data)) {
        $response['success'] = true;
        $response['message'] = 'JSON file synchronized successfully with ' . count($games) . ' products';
    } else {
        $response['message'] = 'Failed to write JSON file';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);