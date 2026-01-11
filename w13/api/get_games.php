<?php
header('Content-Type: application/json');
require_once '../includes/database.php';

$db = new Database();
$conn = $db->getConnection();

$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;

// Load games from JSON file (or database in production)
$games = json_decode(file_get_contents('../data/games.json'), true);

// Filter games
if ($category) {
    $games = array_filter($games, function($game) use ($category) {
        return strtolower($game['category']) === strtolower($category);
    });
}

if ($search) {
    $search = strtolower($search);
    $games = array_filter($games, function($game) use ($search) {
        return strpos(strtolower($game['name']), $search) !== false ||
               strpos(strtolower($game['category']), $search) !== false ||
               strpos(strtolower($game['short_description']), $search) !== false;
    });
}

// Filter by price
$games = array_filter($games, function($game) use ($min_price, $max_price) {
    $price = $game['discount'] > 0 ? 
        $game['price'] * (100 - $game['discount']) / 100 : 
        $game['price'];
    return $price >= $min_price && $price <= $max_price;
});

// Re-index array
$games = array_values($games);

echo json_encode($games);
?>