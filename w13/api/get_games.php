<?php
header('Content-Type: application/json');
error_reporting(0); // 在生产环境关闭错误显示，但记录到日志

// 设置JSON文件路径 - 使用绝对路径
$baseDir = dirname(__DIR__);
$jsonFilePath = $baseDir . '/data/games.json';

// 如果使用数据库，取消注释以下代码
// require_once $baseDir . '/includes/database.php';
// $db = new Database();
// $conn = $db->getConnection();

// 获取查询参数
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;

try {
    // 检查JSON文件是否存在
    if (!file_exists($jsonFilePath)) {
        throw new Exception('Games data file not found');
    }
    
    // 读取JSON文件内容
    $jsonContent = file_get_contents($jsonFilePath);
    if ($jsonContent === false) {
        throw new Exception('Unable to read games data file');
    }
    
    // 解析JSON
    $games = json_decode($jsonContent, true);
    if ($games === null) {
        throw new Exception('Invalid JSON format in games data file');
    }
    
    // 过滤游戏
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
    
    // 按价格过滤
    $games = array_filter($games, function($game) use ($min_price, $max_price) {
        $price = $game['discount'] > 0 ? 
            $game['price'] * (100 - $game['discount']) / 100 : 
            $game['price'];
        return $price >= $min_price && $price <= $max_price;
    });
    
    // 重新索引数组
    $games = array_values($games);
    
    // 返回成功的响应
    $response = [
        'success' => true,
        'count' => count($games),
        'data' => $games,
        'message' => 'Games loaded successfully'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // 返回错误响应
    $response = [
        'success' => false,
        'count' => 0,
        'data' => [],
        'message' => 'Error loading games: ' . $e->getMessage(),
        'debug' => [
            'json_file_path' => $jsonFilePath,
            'file_exists' => file_exists($jsonFilePath),
            'category_filter' => $category,
            'search_filter' => $search,
            'price_range' => [$min_price, $max_price]
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}
?>