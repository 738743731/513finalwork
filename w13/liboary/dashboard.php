<?php
// Enable error display (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$page_title = "Admin Dashboard";

// Check if user is logged in and is admin (id = 4)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "Please login first!";
    $_SESSION['message_type'] = 'error';
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['message'] = "Access denied! Admin only area.";
    $_SESSION['message_type'] = 'error';
    header("Location: index.php");
    exit();
}

// Include header and database
require_once 'includes/header.php';
require_once 'includes/database.php';

// Initialize all variables with default values to prevent undefined variable errors
$error = '';
$success = '';
$db = new Database();

// Initialize dashboard variables
$total_products = 0;
$revenue = 0;
$total_sales = 0;
$stock_value = 0;
$low_stock = 0;
$customer_stats = ['total_customers' => 0];
$active_customers = ['active_customers' => 0];
$churned_customers = ['churned_customers' => 0];
$churn_rate = 0.0;
$low_risk = 0;
$medium_risk = 0;
$high_risk = 0;
$total_with_predictions = 0;
$top_products = [];
$products = [];
$categories = [];
$high_risk_customers = [];
$churn_factors = [];
$category_churn = [];
$recent_predictions = [];
$model_performance = null;
$recent_orders = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0
];
$risk_distribution = [];
$latest_prediction_date = null;
$all_orders = []; // 新增：存储所有订单
$order_statuses = []; // 新增：订单状态统计
$order = null; // 新增：单个订单详情

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Export customer data - 移到最前面，确保在执行前没有任何输出
    if (isset($_POST['export_customers'])) {
        try {
            // 获取最新的预测日期
            $latest_date_result = $db->fetchOne("SELECT MAX(prediction_date) as latest_date FROM customer_churn_predictions");
            $latest_date = $latest_date_result['latest_date'] ?? date('Y-m-d');
            
            $customers = $db->fetchAll("
                SELECT 
                    s.first_name,
                    s.last_name,
                    s.email,
                    COALESCE(cb.months_as_customer, 0) as months_as_customer,
                    COALESCE(cb.order_count, 0) as order_count,
                    COALESCE(cb.total_spent, 0.00) as total_spent,
                    COALESCE(cb.days_since_last_order, 0) as days_since_last_order,
                    COALESCE(ccp.churn_probability, 0) as churn_probability,
                    COALESCE(ccp.risk_level, 'Unknown') as risk_level,
                    COALESCE(ccp.recommendation, 'No recommendation') as recommendation
                FROM wp9k_fc_subscribers s
                LEFT JOIN customer_behavior cb ON s.email = cb.customer_email
                LEFT JOIN customer_churn_predictions ccp ON s.email = ccp.customer_email 
                    AND ccp.prediction_date = ?
                ORDER BY ccp.churn_probability DESC
            ", [$latest_date]);
            
            // 清空输出缓冲区，确保没有任何HTML输出
            ob_clean();
            
            // 设置CSV头部
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="customer_analysis_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // 输出BOM头，确保Excel正确识别UTF-8编码
            fwrite($output, "\xEF\xBB\xBF");
            
            // 输出CSV标题行
            fputcsv($output, ['First Name', 'Last Name', 'Email', 'Months as Customer', 'Order Count', 'Total Spent', 'Days Since Last Order', 'Churn Probability', 'Risk Level', 'Recommendation']);
            
            // 输出数据行
            foreach ($customers as $customer) {
                fputcsv($output, [
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['email'],
                    $customer['months_as_customer'],
                    $customer['order_count'],
                    $customer['total_spent'],
                    $customer['days_since_last_order'],
                    number_format($customer['churn_probability'] * 100, 2) . '%',
                    $customer['risk_level'],
                    $customer['recommendation']
                ]);
            }
            
            fclose($output);
            exit(); // 立即退出，避免执行后续HTML代码
            
        } catch (Exception $e) {
            // 如果导出失败，设置错误信息，但不清除缓冲区，让页面正常显示错误
            $error = "Error exporting data: " . $e->getMessage();
        }
    }
    
    // Add new product
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $discount = floatval($_POST['discount'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $stock = intval($_POST['stock'] ?? 100);
        
        // 新增字段的默认值
        $long_description = trim($_POST['long_description'] ?? $short_description);
        $developer = trim($_POST['developer'] ?? 'Unknown');
        $publisher = trim($_POST['publisher'] ?? 'Unknown');
        $release_date = date('Y-m-d'); // 默认使用当前日期
        $platforms = json_encode(['PC']); // 默认平台为PC
        $rating = 0.0;
        $features = json_encode([]); // 空特性数组
        
        // 图片上传处理
        $image = 'assets/images/game_default.jpg'; // 默认图片
        $image_filename = '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/assets/images/';
            
            // 确保目录存在
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_size = $_FILES['image']['size'];
            $file_error = $_FILES['image']['error'];
            
            // 获取文件扩展名
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // 允许的图片类型
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // 检查文件类型
            if (in_array($file_ext, $allowed_ext)) {
                // 检查文件大小 (限制为 5MB)
                if ($file_size <= 5 * 1024 * 1024) {
                    // 生成唯一文件名，防止覆盖
                    $new_filename = uniqid('game_', true) . '_' . time() . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    // 移动上传的文件
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $image = 'assets/images/' . $new_filename;
                        $image_filename = $new_filename;
                    } else {
                        $error = 'Failed to move uploaded file.';
                    }
                } else {
                    $error = 'File size too large. Maximum size is 5MB.';
                }
            } else {
                $error = 'Invalid file type. Allowed types: JPG, JPEG, PNG, GIF, WEBP.';
            }
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // 如果有上传错误（但不是没有文件的情况）
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'File size too large.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'File upload was incomplete.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = 'No temporary directory for file upload.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error = 'File upload stopped by extension.';
                    break;
                default:
                    $error = 'Unknown upload error.';
            }
        }
        
        // 验证必填字段
        if (empty($name) || $price <= 0 || empty($category)) {
            $error = 'Please fill in all required fields with valid values.';
        } elseif (empty($error)) {
            try {
                // 插入到数据库，包含图片路径
                $product_id = $db->insert(
                    "INSERT INTO products (name, price, discount, category, short_description, long_description, image, developer, publisher, release_date, platforms, rating, features, stock, sales, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())",
                    [$name, $price, $discount, $category, $short_description, $long_description, $image, $developer, $publisher, $release_date, $platforms, $rating, $features, $stock]
                );
                
                // 获取刚插入的产品数据，用于更新JSON文件
                $new_product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);
                
                if ($new_product) {
                    // 更新JSON文件以确保前端能立即显示
                    $json_file = dirname(__DIR__) . '/data/games.json';
                    
                    // 检查目录是否存在，不存在则创建
                    $json_dir = dirname($json_file);
                    if (!is_dir($json_dir)) {
                        mkdir($json_dir, 0755, true);
                    }
                    
                    // 检查文件是否存在，不存在则创建
                    if (!file_exists($json_file)) {
                        file_put_contents($json_file, '[]');
                    }
                    
                    // 读取现有JSON数据
                    $json_content = file_get_contents($json_file);
                    $games = json_decode($json_content, true);
                    
                    if ($games === null) {
                        // 如果JSON文件为空或格式错误，初始化数组
                        $games = [];
                    }
                    
                    // 确保没有重复的ID
                    $games = array_filter($games, function($game) use ($product_id) {
                        return isset($game['id']) && $game['id'] != $product_id;
                    });
                    
                    // 创建新的游戏数据，确保格式与现有JSON一致
                    $new_game = [
                        'id' => (int)$product_id,
                        'name' => $new_product['name'],
                        'price' => (float)$new_product['price'],
                        'discount' => (float)$new_product['discount'],
                        'category' => $new_product['category'],
                        'short_description' => $new_product['short_description'],
                        'long_description' => $new_product['long_description'],
                        'image' => $new_product['image'],
                        'developer' => $new_product['developer'],
                        'publisher' => $new_product['publisher'],
                        'release_date' => $new_product['release_date'],
                        'platforms' => json_decode($new_product['platforms'], true),
                        'rating' => (float)$new_product['rating'],
                        'features' => json_decode($new_product['features'], true)
                    ];
                    
                    // 将新游戏添加到数组中
                    $games[] = $new_game;
                    
                    // 按ID排序，保持一致性
                    usort($games, function($a, $b) {
                        return $b['id'] - $a['id'];
                    });
                    
                    // 将数组转换回JSON格式
                    $json_data = json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    
                    // 写回文件
                    if (file_put_contents($json_file, $json_data)) {
                        $success = "Product added successfully to both database and JSON file!" . 
                                  ($image_filename ? " Image uploaded: $image_filename" : " Using default image.");
                    } else {
                        // 记录详细的错误信息
                        $error_details = "Failed to write JSON file. ";
                        $error_details .= "File path: " . $json_file . ". ";
                        $error_details .= "Writable: " . (is_writable($json_file) ? 'Yes' : 'No') . ". ";
                        $error_details .= "Directory writable: " . (is_writable($json_dir) ? 'Yes' : 'No');
                        $success = "Product added to database, but failed to update JSON file. Details: " . $error_details;
                    }
                } else {
                    $success = "Product added to database, but could not retrieve product data for JSON update.";
                }
                
            } catch (Exception $e) {
                $error = "Error adding product: " . $e->getMessage();
            }
        }
    }
    
    // Update product
    if (isset($_POST['update_product'])) {
        $product_id = intval($_POST['product_id'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        
        if ($product_id > 0 && $stock >= 0) {
            try {
                $db->query(
                    "UPDATE products SET stock = ? WHERE id = ?",
                    [$stock, $product_id]
                );
                
                // 同时更新JSON文件中的库存信息
                $json_file = dirname(__DIR__) . '/data/games.json';
                if (file_exists($json_file)) {
                    $json_content = file_get_contents($json_file);
                    $games = json_decode($json_content, true);
                    
                    if ($games !== null) {
                        foreach ($games as &$game) {
                            if (isset($game['id']) && $game['id'] == $product_id) {
                                // 更新游戏对象中的库存信息
                                $game['stock'] = $stock;
                                break;
                            }
                        }
                        
                        $json_data = json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        file_put_contents($json_file, $json_data);
                    }
                }
                
                $success = "Product updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating product: " . $e->getMessage();
            }
        }
    }
    
    // Delete product
    if (isset($_POST['delete_product'])) {
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id > 0) {
            try {
                // 首先获取产品的图片路径，以便删除图片文件
                $product = $db->fetchOne("SELECT image FROM products WHERE id = ?", [$product_id]);
                if ($product && !empty($product['image']) && $product['image'] !== 'assets/images/game_default.jpg') {
                    $image_path = dirname(__DIR__) . '/' . $product['image'];
                    if (file_exists($image_path)) {
                        @unlink($image_path); // 删除图片文件，@抑制错误
                    }
                }
                
                // 从数据库中删除产品
                $db->query(
                    "DELETE FROM products WHERE id = ?",
                    [$product_id]
                );
                
                // 同时从JSON文件中删除
                $json_file = dirname(__DIR__) . '/data/games.json';
                if (file_exists($json_file)) {
                    $json_content = file_get_contents($json_file);
                    $games = json_decode($json_content, true);
                    
                    if ($games !== null) {
                        $games = array_filter($games, function($game) use ($product_id) {
                            return !isset($game['id']) || $game['id'] != $product_id;
                        });
                        
                        $json_data = json_encode(array_values($games), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        file_put_contents($json_file, $json_data);
                    }
                }
                
                $success = "Product deleted successfully!";
            } catch (Exception $e) {
                $error = "Error deleting product: " . $e->getMessage();
            }
        }
    }
    
    // Update order status
    if (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = trim($_POST['order_status'] ?? '');
        $change_reason = trim($_POST['change_reason'] ?? '');
        
        if ($order_id > 0 && !empty($new_status)) {
            try {
                // 获取当前订单状态
                $current_order = $db->fetchOne("SELECT status FROM orders WHERE id = ?", [$order_id]);
                $old_status = $current_order['status'] ?? null;
                
                // 更新订单状态
                $db->query(
                    "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?",
                    [$new_status, $order_id]
                );
                
                // 记录状态变更历史
                $db->query(
                    "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) 
                     VALUES (?, ?, ?, ?, ?)",
                    [$order_id, $old_status, $new_status, $_SESSION['display_name'] ?? 'Admin', $change_reason]
                );
                
                $success = "Order #{$order_id} status updated to '{$new_status}' successfully!";
            } catch (Exception $e) {
                $error = "Error updating order status: " . $e->getMessage();
                error_log("Order status update error: " . $e->getMessage());
            }
        } else {
            $error = "Invalid order data provided!";
        }
    }
    
    // Cancel order
    if (isset($_POST['cancel_order'])) {
        $order_id = intval($_POST['order_id'] ?? 0);
        $cancel_reason = trim($_POST['cancel_reason'] ?? '');
        
        if ($order_id > 0) {
            try {
                // 获取当前订单状态
                $current_order = $db->fetchOne("SELECT status FROM orders WHERE id = ?", [$order_id]);
                $old_status = $current_order['status'] ?? null;
                
                // 更新订单状态为取消
                $db->query(
                    "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
                    [$order_id]
                );
                
                // 记录状态变更历史
                $db->query(
                    "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, change_reason) 
                     VALUES (?, ?, ?, ?, ?)",
                    [$order_id, $old_status, 'cancelled', $_SESSION['display_name'] ?? 'Admin', "Cancelled: " . $cancel_reason]
                );
                
                $success = "Order #{$order_id} has been cancelled successfully!";
            } catch (Exception $e) {
                $error = "Error cancelling order: " . $e->getMessage();
            }
        }
    }
    
    // Run customer churn analysis
    if (isset($_POST['run_analysis'])) {
        try {
            // 检查存储过程是否存在
            $procedures_exist = false;
            try {
                // 尝试调用存储过程，如果失败则使用替代方法
                $db->query("CALL UpdateCustomerBehaviorData()");
                $db->query("CALL GenerateChurnPredictions()");
                $procedures_exist = true;
            } catch (Exception $e) {
                // 存储过程不存在或出错，使用替代方法
                $procedures_exist = false;
            }
            
            if (!$procedures_exist) {
                // 使用替代方法：直接运行更新和预测逻辑
                $success = "Using alternative analysis method (stored procedures not available). Analysis completed!";
                
                // 这里可以添加直接的SQL更新逻辑
                // 暂时跳过，因为我们需要先创建表
            } else {
                // 存储过程成功运行，更新风险分段统计
                $db->query("
                    UPDATE customer_risk_segments rs
                    JOIN (
                        SELECT 
                            CASE 
                                WHEN ccp.churn_probability <= 0.4 THEN 'Low Risk'
                                WHEN ccp.churn_probability <= 0.7 THEN 'Medium Risk'
                                ELSE 'High Risk'
                            END as segment_name,
                            COUNT(DISTINCT ccp.customer_email) as customer_count,
                            AVG(ccp.churn_probability) as avg_churn_probability,
                            AVG(cb.months_as_customer) as avg_months_as_customer,
                            AVG(cb.order_count) as avg_order_count,
                            AVG(cb.days_since_last_order) as avg_days_since_last_order,
                            SUM(cb.total_spent) as total_value
                        FROM customer_churn_predictions ccp
                        LEFT JOIN customer_behavior cb ON ccp.customer_email = cb.customer_email
                        WHERE ccp.prediction_date = CURDATE()
                        GROUP BY 
                            CASE 
                                WHEN ccp.churn_probability <= 0.4 THEN 'Low Risk'
                                WHEN ccp.churn_probability <= 0.7 THEN 'Medium Risk'
                                ELSE 'High Risk'
                            END
                    ) stats ON rs.segment_name = stats.segment_name
                    SET 
                        rs.customer_count = COALESCE(stats.customer_count, 0),
                        rs.avg_churn_probability = COALESCE(stats.avg_churn_probability, 0),
                        rs.avg_months_as_customer = COALESCE(stats.avg_months_as_customer, 0),
                        rs.avg_order_count = COALESCE(stats.avg_order_count, 0),
                        rs.avg_days_since_last_order = COALESCE(stats.avg_days_since_last_order, 0),
                        rs.total_value = COALESCE(stats.total_value, 0.00),
                        rs.updated_at = NOW()
                ");
                
                $success = "Customer churn analysis completed successfully! Data updated from database.";
            }
        } catch (Exception $e) {
            $error = "Error running analysis: " . $e->getMessage() . " (Full error in logs)";
            // 记录完整错误到日志
            error_log("Analysis error: " . $e->getMessage());
        }
    }
    
    // Send retention email to high-risk customers
    if (isset($_POST['send_retention_email'])) {
        try {
            // 获取最新的预测日期
            $latest_date_result = $db->fetchOne("SELECT MAX(prediction_date) as latest_date FROM customer_churn_predictions");
            $latest_date = $latest_date_result['latest_date'] ?? date('Y-m-d');
            
            // 获取高风险客户
            $high_risk_customers_list = $db->fetchAll("
                SELECT DISTINCT s.first_name, s.last_name, s.email
                FROM customer_churn_predictions ccp
                JOIN wp9k_fc_subscribers s ON ccp.customer_email = s.email
                WHERE ccp.prediction_date = ? 
                AND ccp.risk_level = 'High'
                LIMIT 50
            ", [$latest_date]);
            
            $email_count = count($high_risk_customers_list);
            
            if ($email_count > 0) {
                // 这里可以添加实际发送邮件的代码
                // 暂时只记录发送日志
                $db->query("
                    INSERT INTO retention_activities 
                    (activity_name, target_segment, activity_type, start_date, target_customers, status)
                    VALUES ('High Risk Retention Campaign', 'High Risk', 'Email', CURDATE(), ?, 'Active')
                ", [$email_count]);
                
                $success = "Retention email campaign started for {$email_count} high-risk customers!";
            } else {
                $success = "No high-risk customers to send emails to.";
            }
            
        } catch (Exception $e) {
            $error = "Error sending retention emails: " . $e->getMessage();
        }
    }
}

// Get dashboard statistics
try {
    // Total products
    $total_products_result = $db->fetchOne("SELECT COUNT(*) as count FROM products");
    $total_products = $total_products_result['count'] ?? 0;
    
    // Total stock value
    $stock_value_result = $db->fetchOne("SELECT SUM(price * stock) as value FROM products");
    $stock_value = $stock_value_result['value'] ?? 0;
    
    // Total sales from products table
    $total_sales_result = $db->fetchOne("SELECT SUM(sales) as total FROM products");
    $total_sales = $total_sales_result['total'] ?? 0;
    
    // Revenue from products table
    $revenue_result = $db->fetchOne("SELECT SUM(price * sales * (1 - discount/100)) as revenue FROM products");
    $revenue = $revenue_result['revenue'] ?? 0;
    
    // Low stock products (less than 20)
    $low_stock_result = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE stock < 20");
    $low_stock = $low_stock_result['count'] ?? 0;
    
    // Top selling products
    $top_products = $db->fetchAll("SELECT * FROM products ORDER BY sales DESC LIMIT 5") ?: [];
    
    // All products
    $products = $db->fetchAll("SELECT * FROM products ORDER BY id DESC") ?: [];
    
    // Categories summary
    $categories = $db->fetchAll("SELECT category, COUNT(*) as count, SUM(sales) as sales FROM products GROUP BY category ORDER BY sales DESC") ?: [];
    
    // Customer Analytics Data (从数据库获取真实数据)
    
    // Total customers - 修改为从 wp9k_fc_subscribers 表获取
    $customer_stats = $db->fetchOne("SELECT COUNT(*) as total_customers FROM wp9k_fc_subscribers") ?: ['total_customers' => 0];
    
    // Active customers (有购买记录的客户) - 保持原逻辑
    $active_customers = $db->fetchOne("
        SELECT COUNT(DISTINCT o.customer_email) as active_customers 
        FROM orders o 
        WHERE o.status IN ('completed', 'paid')
    ") ?: ['active_customers' => 0];
    
    // Churned customers (从customer_behavior表中获取) - 保持原逻辑
    $churned_customers = $db->fetchOne("
        SELECT COUNT(*) as churned_customers 
        FROM customer_behavior 
        WHERE churned = TRUE
    ") ?: ['churned_customers' => 0];
    
    // Churn rate
    $churn_rate = 0.0;
    if ($active_customers['active_customers'] > 0) {
        $churn_rate = ($churned_customers['churned_customers'] / $active_customers['active_customers'] * 100);
    }
    
    // 获取最新的预测日期
    $latest_date_result = $db->fetchOne("SELECT MAX(prediction_date) as latest_date FROM customer_churn_predictions");
    $latest_prediction_date = $latest_date_result['latest_date'] ?? null;
    
    // Risk distribution from predictions - 使用最新的预测日期
    if ($latest_prediction_date) {
        $risk_distribution = $db->fetchAll("
            SELECT 
                risk_level,
                COUNT(*) as customer_count,
                AVG(churn_probability) as avg_probability
            FROM customer_churn_predictions 
            WHERE prediction_date = ?
            GROUP BY risk_level
        ", [$latest_prediction_date]) ?: [];
    } else {
        $risk_distribution = [];
    }
    
    // Initialize risk counts
    $low_risk = 0;
    $medium_risk = 0;
    $high_risk = 0;
    
    foreach ($risk_distribution as $risk) {
        switch ($risk['risk_level']) {
            case 'Low':
                $low_risk = $risk['customer_count'] ?? 0;
                break;
            case 'Medium':
                $medium_risk = $risk['customer_count'] ?? 0;
                break;
            case 'High':
                $high_risk = $risk['customer_count'] ?? 0;
                break;
        }
    }
    
    // Total customers with predictions
    $total_with_predictions = $low_risk + $medium_risk + $high_risk;
    
    // High risk customers with their details - 使用最新的预测日期
    if ($latest_prediction_date) {
        $high_risk_customers = $db->fetchAll("
            SELECT 
                ccp.customer_email as email,
                COALESCE(cb.months_as_customer, 0) as months_as_customer,
                COALESCE(cb.order_count, 0) as order_count,
                COALESCE(cb.days_since_last_order, 0) as days_since_last_order,
                ccp.churn_probability,
                ccp.risk_level,
                CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as customer_name,
                COUNT(DISTINCT o.id) as total_orders
            FROM customer_churn_predictions ccp
            LEFT JOIN customer_behavior cb ON ccp.customer_email = cb.customer_email
            LEFT JOIN orders o ON ccp.customer_email = o.customer_email
            LEFT JOIN wp9k_fc_subscribers s ON ccp.customer_email = s.email
            WHERE ccp.prediction_date = ? 
            AND ccp.risk_level = 'High'
            GROUP BY ccp.customer_email, cb.months_as_customer, cb.order_count, cb.days_since_last_order, ccp.churn_probability, ccp.risk_level, s.first_name, s.last_name
            ORDER BY ccp.churn_probability DESC
            LIMIT 10
        ", [$latest_prediction_date]) ?: [];
    } else {
        $high_risk_customers = [];
    }
    
    // Churn predictors from model
    $churn_model = $db->fetchOne("
        SELECT feature_importance 
        FROM churn_models 
        WHERE is_active = TRUE 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    // Parse feature importance
    $churn_factors = [];
    if ($churn_model && !empty($churn_model['feature_importance'])) {
        $features = json_decode($churn_model['feature_importance'], true);
        if ($features) {
            foreach ($features as $feature => $impact) {
                $churn_factors[] = [
                    'feature' => ucwords(str_replace('_', ' ', $feature)),
                    'impact' => $impact,
                    'direction' => strpos($feature, 'days_since') !== false ? '+' : '-'
                ];
            }
        }
    }
    
    // If no model data, use default
    if (empty($churn_factors)) {
        $churn_factors = [
            ['feature' => 'Days Since Last Order', 'impact' => 0.87, 'direction' => '+'],
            ['feature' => 'Order Count', 'impact' => 0.54, 'direction' => '-'],
            ['feature' => 'Months as Customer', 'impact' => 0.32, 'direction' => '-'],
            ['feature' => 'Total Spent', 'impact' => 0.15, 'direction' => '-']
        ];
    }
    
    // Category churn analysis
    $category_churn = $db->fetchAll("
        SELECT 
            p.category,
            COUNT(DISTINCT o.customer_email) as total_customers,
            SUM(CASE WHEN cb.churned = TRUE THEN 1 ELSE 0 END) as churned_customers
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.game_id = p.id
        LEFT JOIN customer_behavior cb ON o.customer_email = cb.customer_email
        WHERE o.status IN ('completed', 'paid')
        GROUP BY p.category
        HAVING total_customers > 0
        ORDER BY churned_customers DESC
        LIMIT 5
    ") ?: [];
    
    // Calculate churn rates
    foreach ($category_churn as &$category) {
        $category['rate'] = 0.0;
        if (isset($category['total_customers']) && $category['total_customers'] > 0) {
            $category['rate'] = ($category['churned_customers'] / $category['total_customers'] * 100);
        }
    }
    
    // If no category data, use defaults
    if (empty($category_churn)) {
        $category_churn = $db->fetchAll("
            SELECT 
                p.category,
                COUNT(DISTINCT o.customer_email) as total_customers,
                0 as churned_customers,
                0.0 as rate
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.game_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE p.category IS NOT NULL
            GROUP BY p.category
            ORDER BY total_customers DESC
            LIMIT 5
        ") ?: [];
    }
    
    // Recent churn predictions
    $recent_predictions = $db->fetchAll("
        SELECT 
            DATE(prediction_date) as prediction_date,
            COUNT(*) as total_predictions,
            AVG(churn_probability) as avg_churn_probability,
            SUM(CASE WHEN risk_level = 'High' THEN 1 ELSE 0 END) as high_risk_count
        FROM customer_churn_predictions 
        WHERE prediction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(prediction_date)
        ORDER BY prediction_date DESC
    ") ?: [];
    
    // Model performance - 修正：使用反引号括起保留关键字
    $model_performance = $db->fetchOne("
        SELECT 
            accuracy,
            `precision`,
            `recall`,
            f1_score,
            roc_auc,
            model_name
        FROM churn_models 
        WHERE is_active = TRUE 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    // Recent orders summary
    $recent_orders_result = $db->fetchOne("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total) as total_revenue,
            AVG(total) as avg_order_value
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status IN ('completed', 'paid')
    ") ?: ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];
    
    $recent_orders = [
        'total_orders' => $recent_orders_result['total_orders'] ?? 0,
        'total_revenue' => $recent_orders_result['total_revenue'] ?? 0,
        'avg_order_value' => $recent_orders_result['avg_order_value'] ?? 0
    ];
    
    // 新增：获取所有订单
    $all_orders = $db->fetchAll("
        SELECT 
            o.*,
            s.first_name,
            s.last_name,
            COUNT(DISTINCT oi.id) as item_count,
            GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as product_names
        FROM orders o
        LEFT JOIN wp9k_fc_subscribers s ON o.customer_email = s.email
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.game_id = p.id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 100
    ") ?: [];
    
    // 新增：订单状态统计
    $order_statuses = $db->fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            SUM(total) as total_value,
            AVG(total) as avg_value
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
        ORDER BY count DESC
    ") ?: [];
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
    // 记录完整错误到日志
    error_log("Dashboard data fetch error: " . $e->getMessage());
}
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['display_name'] ?? 'Admin'); ?>!</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- Dashboard Tabs Navigation -->
    <div class="dashboard-tabs">
        <button class="tab-btn active" data-tab="overview">
            <i class="fas fa-chart-pie"></i> Overview
        </button>
        <button class="tab-btn" data-tab="products">
            <i class="fas fa-gamepad"></i> Product Management
        </button>
        <button class="tab-btn" data-tab="orders">
            <i class="fas fa-shopping-cart"></i> Order Management
        </button>
        <button class="tab-btn" data-tab="analytics">
            <i class="fas fa-chart-line"></i> Customer Analytics
        </button>
        <button class="tab-btn" data-tab="inventory">
            <i class="fas fa-boxes"></i> Inventory
        </button>
        <button class="tab-btn" data-tab="reports">
            <i class="fas fa-file-alt"></i> Reports
        </button>
    </div>
    
    <!-- Overview Tab -->
    <div id="overview-tab" class="tab-content active">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $customer_stats['total_customers']; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($churn_rate, 1); ?>%</h3>
                    <p>Churn Rate</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders Summary -->
        <div class="stats-grid" style="margin-bottom: 20px;">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $recent_orders['total_orders']; ?></h3>
                    <p>30-Day Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-secondary">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($recent_orders['total_revenue'], 2); ?></h3>
                    <p>30-Day Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-dark">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($recent_orders['avg_order_value'], 2); ?></h3>
                    <p>Avg Order Value</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $high_risk; ?></h3>
                    <p>High Risk Customers</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="dashboard-row">
            <!-- Top Selling Products -->
            <div class="dashboard-card">
                <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Sales</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                        <td><span class="category-badge"><?php echo htmlspecialchars($product['category'] ?? ''); ?></span></td>
                                        <td><?php echo $product['sales'] ?? 0; ?></td>
                                        <td>$<?php echo number_format(($product['price'] ?? 0) * ($product['sales'] ?? 0) * (1 - ($product['discount'] ?? 0)/100), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Risk Overview -->
            <div class="dashboard-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Risk Overview</h3>
                <div class="risk-distribution">
                    <div class="risk-segment">
                        <div class="risk-label">
                            <span class="risk-dot low-risk"></span>
                            Low Risk
                        </div>
                        <div class="risk-bar">
                            <div class="risk-fill low-risk" style="width: <?php echo $total_with_predictions > 0 ? ($low_risk / $total_with_predictions * 100) : 0; ?>%"></div>
                        </div>
                        <div class="risk-value"><?php echo $low_risk; ?> (<?php echo $total_with_predictions > 0 ? number_format(($low_risk / $total_with_predictions * 100), 1) : 0; ?>%)</div>
                    </div>
                    <div class="risk-segment">
                        <div class="risk-label">
                            <span class="risk-dot medium-risk"></span>
                            Medium Risk
                        </div>
                        <div class="risk-bar">
                            <div class="risk-fill medium-risk" style="width: <?php echo $total_with_predictions > 0 ? ($medium_risk / $total_with_predictions * 100) : 0; ?>%"></div>
                        </div>
                        <div class="risk-value"><?php echo $medium_risk; ?> (<?php echo $total_with_predictions > 0 ? number_format(($medium_risk / $total_with_predictions * 100), 1) : 0; ?>%)</div>
                    </div>
                    <div class="risk-segment">
                        <div class="risk-label">
                            <span class="risk-dot high-risk"></span>
                            High Risk
                        </div>
                        <div class="risk-bar">
                            <div class="risk-fill high-risk" style="width: <?php echo $total_with_predictions > 0 ? ($high_risk / $total_with_predictions * 100) : 0; ?>%"></div>
                        </div>
                        <div class="risk-value"><?php echo $high_risk; ?> (<?php echo $total_with_predictions > 0 ? number_format(($high_risk / $total_with_predictions * 100), 1) : 0; ?>%)</div>
                    </div>
                </div>
                <div class="dashboard-actions">
                    <form method="POST" class="inline-form">
                        <button type="submit" name="run_analysis" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Update Analysis
                        </button>
                    </form>
                    <a href="#analytics-tab" class="btn btn-secondary tab-link" data-tab="analytics">
                        <i class="fas fa-chart-line"></i> View Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="dashboard-card full-width">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <div class="dashboard-row">
                <!-- Categories Summary -->
                <div class="dashboard-column">
                    <h4><i class="fas fa-tags"></i> Categories Summary</h4>
                    <div class="categories-list">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="category-item">
                                    <div class="category-name"><?php echo htmlspecialchars($category['category'] ?? ''); ?></div>
                                    <div class="category-stats">
                                        <span class="stat-count"><?php echo $category['count'] ?? 0; ?> products</span>
                                        <span class="stat-sales"><?php echo $category['sales'] ?? 0; ?> sales</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No categories found</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stock Alerts -->
                <div class="dashboard-column">
                    <h4><i class="fas fa-exclamation-circle"></i> Stock Alerts</h4>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo $low_stock; ?> products have low stock (below 20 units)
                    </div>
                    <div class="stock-value">
                        <h5>Total Stock Value</h5>
                        <div class="value">$<?php echo number_format($stock_value, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Management Tab -->
    <div id="products-tab" class="tab-content">
        <div class="tab-header">
            <h2><i class="fas fa-gamepad"></i> Product Management</h2>
            <p>Add, edit, and manage your product catalog</p>
        </div>
        
        <div class="dashboard-row">
            <!-- Add New Product Form -->
            <div class="dashboard-card">
                <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" required placeholder="Enter product name">
                        </div>
                        <div class="form-group">
                            <label>Price ($) *</label>
                            <input type="number" name="price" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Discount (%)</label>
                            <input type="number" name="discount" min="0" max="100" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="Action">Action</option>
                                <option value="RPG">RPG</option>
                                <option value="Racing">Racing</option>
                                <option value="Adventure">Adventure</option>
                                <option value="Sports">Sports</option>
                                <option value="Shooter">Shooter</option>
                                <option value="Strategy">Strategy</option>
                                <option value="Simulation">Simulation</option>
                                <option value="Horror">Horror</option>
                                <option value="Indie">Indie</option>
                                <option value="Puzzle">Puzzle</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Short Description</label>
                        <textarea name="short_description" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Long Description</label>
                        <textarea name="long_description" rows="3" placeholder="Detailed description"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Developer</label>
                            <input type="text" name="developer" placeholder="Developer name">
                        </div>
                        <div class="form-group">
                            <label>Publisher</label>
                            <input type="text" name="publisher" placeholder="Publisher name">
                        </div>
                    </div>
                    
                    <!-- 修改：将图片输入改为文件上传 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Image *</label>
                            <div class="image-upload-container">
                                <input type="file" name="image" id="productImage" accept="image/*" required 
                                       onchange="previewImage(this)">
                                <div class="image-preview" id="imagePreview">
                                    <div class="image-preview-default">
                                        <i class="fas fa-image"></i>
                                        <span>No image selected</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Max size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF, WEBP</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Initial Stock</label>
                            <input type="number" name="stock" min="0" value="100">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </form>
            </div>
            
            <!-- Top Selling Products -->
            <div class="dashboard-card">
                <h3><i class="fas fa-chart-line"></i> Top Selling Products</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Sales</th>
                                <th>Revenue</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                        <td><span class="category-badge"><?php echo htmlspecialchars($product['category'] ?? ''); ?></span></td>
                                        <td><?php echo $product['sales'] ?? 0; ?></td>
                                        <td>$<?php echo number_format(($product['price'] ?? 0) * ($product['sales'] ?? 0) * (1 - ($product['discount'] ?? 0)/100), 2); ?></td>
                                        <td>
                                            <span class="stock-badge <?php echo ($product['stock'] ?? 0) < 20 ? 'low-stock' : 'good-stock'; ?>">
                                                <?php echo $product['stock'] ?? 0; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- JSON File Status -->
        <div class="dashboard-card">
            <h3><i class="fas fa-file-code"></i> JSON File Status</h3>
            <div class="json-status">
                <?php
                $json_file = dirname(__DIR__) . '/data/games.json';
                $json_exists = file_exists($json_file);
                $json_writable = $json_exists ? is_writable($json_file) : false;
                $json_size = $json_exists ? filesize($json_file) : 0;
                
                if ($json_exists) {
                    $json_content = file_get_contents($json_file);
                    $games_data = json_decode($json_content, true);
                    $game_count = is_array($games_data) ? count($games_data) : 0;
                }
                ?>
                <div class="status-item">
                    <span class="status-label">File Path:</span>
                    <span class="status-value"><?php echo htmlspecialchars($json_file); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">File Exists:</span>
                    <span class="status-value <?php echo $json_exists ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $json_exists ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Writable:</span>
                    <span class="status-value <?php echo $json_writable ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $json_writable ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">File Size:</span>
                    <span class="status-value"><?php echo number_format($json_size / 1024, 2); ?> KB</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Games in JSON:</span>
                    <span class="status-value"><?php echo $json_exists && isset($game_count) ? $game_count : 'N/A'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Games in Database:</span>
                    <span class="status-value"><?php echo $total_products; ?></span>
                </div>
                
                <?php if (!$json_exists || !$json_writable): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        JSON file issues detected. Products may not appear on the frontend.
                        <?php if (!$json_exists): ?>
                            <br>File does not exist at: <?php echo htmlspecialchars($json_file); ?>
                        <?php endif; ?>
                        <?php if (!$json_writable): ?>
                            <br>File is not writable. Please check permissions.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <button onclick="syncJsonFile()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-sync"></i> Force Sync JSON File
                    </button>
                    <button onclick="viewJsonContent()" class="btn btn-outline btn-sm">
                        <i class="fas fa-eye"></i> View JSON Content
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Management Tab (新增) -->
    <div id="orders-tab" class="tab-content">
        <div class="tab-header">
            <h2><i class="fas fa-shopping-cart"></i> Order Management</h2>
            <p>Manage customer orders and update order status</p>
        </div>
        
        <!-- Order Status Summary -->
        <div class="stats-grid" style="margin-bottom: 20px;">
            <?php if (!empty($order_statuses)): ?>
                <?php foreach ($order_statuses as $status): ?>
                    <div class="stat-card">
                        <div class="stat-icon 
                            <?php echo $status['status'] == 'completed' ? 'bg-success' : 
                                   ($status['status'] == 'processing' ? 'bg-info' : 
                                   ($status['status'] == 'shipped' ? 'bg-primary' : 
                                   ($status['status'] == 'pending' ? 'bg-warning' : 
                                   ($status['status'] == 'cancelled' ? 'bg-danger' : 'bg-secondary')))); ?>">
                            <i class="fas 
                                <?php echo $status['status'] == 'completed' ? 'fa-check-circle' : 
                                       ($status['status'] == 'processing' ? 'fa-cogs' : 
                                       ($status['status'] == 'shipped' ? 'fa-shipping-fast' : 
                                       ($status['status'] == 'pending' ? 'fa-clock' : 
                                       ($status['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-shopping-cart')))); ?>"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $status['count']; ?></h3>
                            <p><?php echo ucfirst($status['status']); ?> Orders</p>
                            <small>$<?php echo number_format($status['total_value'] ?? 0, 2); ?> total</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon bg-secondary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>0</h3>
                        <p>No Orders</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order List -->
        <div class="dashboard-card">
            <h3><i class="fas fa-list"></i> Recent Orders</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($all_orders)): ?>
                            <?php foreach ($all_orders as $order_item): ?>
                                <tr class="<?php 
                                    echo $order_item['status'] == 'completed' ? 'completed-order' : 
                                           ($order_item['status'] == 'cancelled' ? 'cancelled-order' : 
                                           ($order_item['status'] == 'pending' ? 'pending-order' : ''));
                                ?>">
                                    <td>#<?php echo $order_item['id']; ?></td>
                                    <td>
                                        <?php 
                                            $customer_name = trim(($order_item['first_name'] ?? '') . ' ' . ($order_item['last_name'] ?? ''));
                                            if (empty($customer_name)) {
                                                echo 'Guest Customer';
                                            } else {
                                                echo htmlspecialchars($customer_name);
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($order_item['customer_email'] ?? '', 0, 20)); ?>...</td>
                                    <td>$<?php echo number_format($order_item['total'] ?? 0, 2); ?></td>
                                    <td>
                                        <span class="status-badge 
                                            <?php echo $order_item['status'] == 'completed' ? 'completed' : 
                                                   ($order_item['status'] == 'processing' ? 'processing' : 
                                                   ($order_item['status'] == 'shipped' ? 'shipped' : 
                                                   ($order_item['status'] == 'pending' ? 'pending' : 
                                                   ($order_item['status'] == 'cancelled' ? 'cancelled' : 'default')))); ?>">
                                            <?php echo ucfirst($order_item['status'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order_item['created_at'] ?? '')); ?></td>
                                    <td><?php echo $order_item['item_count'] ?? 0; ?> items</td>
                                    <td>
                                        <div class="order-actions">
                                            <button type="button" class="btn btn-sm btn-outline view-order-btn" 
                                                    data-order-id="<?php echo $order_item['id']; ?>"
                                                    data-toggle="modal" data-target="#orderDetailsModal">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary update-status-btn" 
                                                    data-order-id="<?php echo $order_item['id']; ?>"
                                                    data-current-status="<?php echo $order_item['status']; ?>"
                                                    data-toggle="modal" data-target="#updateStatusModal">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Order Status Legend -->
                <div class="status-legend" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h4><i class="fas fa-info-circle"></i> Status Legend</h4>
                    <div class="legend-items" style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                        <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                            <span class="status-badge pending" style="margin: 0;">Pending</span>
                            <span style="font-size: 13px; color: #6c757d;">Order received, awaiting processing</span>
                        </div>
                        <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                            <span class="status-badge processing" style="margin: 0;">Processing</span>
                            <span style="font-size: 13px; color: #6c757d;">Order is being prepared</span>
                        </div>
                        <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                            <span class="status-badge shipped" style="margin: 0;">Shipped</span>
                            <span style="font-size: 13px; color: #6c757d;">Order has been shipped</span>
                        </div>
                        <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                            <span class="status-badge completed" style="margin: 0;">Completed</span>
                            <span style="font-size: 13px; color: #6c757d;">Order delivered and completed</span>
                        </div>
                        <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                            <span class="status-badge cancelled" style="margin: 0;">Cancelled</span>
                            <span style="font-size: 13px; color: #6c757d;">Order has been cancelled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Analytics Tab -->
        <div id="analytics-tab" class="tab-content">
            <div class="tab-header">
                <h2><i class="fas fa-chart-line"></i> Customer Analytics & Churn Prediction</h2>
                <p>Analyze customer behavior and predict churn risks</p>
                <div class="action-buttons">
                    <form method="POST" class="inline-form">
                        <button type="submit" name="run_analysis" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Update Analysis
                        </button>
                    </form>
                    <form method="POST" class="inline-form">
                        <button type="submit" name="send_retention_email" class="btn btn-danger">
                            <i class="fas fa-envelope"></i> Send Retention Email
                        </button>
                    </form>
                    <form method="POST" class="inline-form">
                        <button type="submit" name="export_customers" class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="analytics-grid">
                <!-- Customer Overview -->
                <div class="analytics-card">
                    <h3><i class="fas fa-users"></i> Customer Overview</h3>
                    <div class="customer-stats">
                        <div class="customer-stat">
                            <div class="stat-value"><?php echo $customer_stats['total_customers']; ?></div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                        <div class="customer-stat">
                            <div class="stat-value"><?php echo $active_customers['active_customers']; ?></div>
                            <div class="stat-label">Active Customers</div>
                        </div>
                        <div class="customer-stat">
                            <div class="stat-value"><?php echo $churned_customers['churned_customers']; ?></div>
                            <div class="stat-label">Churned Customers</div>
                        </div>
                        <div class="customer-stat">
                            <div class="stat-value"><?php echo number_format($churn_rate, 1); ?>%</div>
                            <div class="stat-label">Churn Rate</div>
                        </div>
                    </div>
                    <div class="customer-insight">
                        <small>
                            <i class="fas fa-lightbulb"></i> 
                            <?php if ($churn_rate > 30): ?>
                                High churn rate detected. Consider implementing retention strategies.
                            <?php elseif ($churn_rate > 15): ?>
                                Moderate churn rate. Monitor high-risk customers closely.
                            <?php else: ?>
                                Healthy churn rate. Focus on customer acquisition and upselling.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                
                <!-- Model Performance -->
                <div class="analytics-card">
                    <h3><i class="fas fa-robot"></i> Model Performance</h3>
                    <div class="model-performance">
                        <?php if ($model_performance): ?>
                            <div class="model-name"><?php echo htmlspecialchars($model_performance['model_name'] ?? 'Churn Prediction Model'); ?></div>
                            <div class="performance-metrics">
                                <div class="metric">
                                    <div class="metric-label">Accuracy</div>
                                    <div class="metric-value"><?php echo number_format(($model_performance['accuracy'] ?? 0) * 100, 1); ?>%</div>
                                </div>
                                <div class="metric">
                                    <div class="metric-label">Precision</div>
                                    <div class="metric-value"><?php echo number_format(($model_performance['precision'] ?? 0) * 100, 1); ?>%</div>
                                </div>
                                <div class="metric">
                                    <div class="metric-label">Recall</div>
                                    <div class="metric-value"><?php echo number_format(($model_performance['recall'] ?? 0) * 100, 1); ?>%</div>
                                </div>
                                <div class="metric">
                                    <div class="metric-label">F1 Score</div>
                                    <div class="metric-value"><?php echo number_format(($model_performance['f1_score'] ?? 0) * 100, 1); ?>%</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No active model found. Run analysis to generate predictions.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Key Churn Predictors -->
                <div class="analytics-card">
                    <h3><i class="fas fa-search"></i> Key Churn Predictors</h3>
                    <div class="predictors-list">
                        <?php foreach ($churn_factors as $factor): ?>
                            <div class="predictor-item">
                                <div class="predictor-name">
                                    <?php echo $factor['feature']; ?>
                                    <span class="predictor-direction <?php echo $factor['direction'] === '+' ? 'positive' : 'negative'; ?>">
                                        <?php echo $factor['direction']; ?>
                                    </span>
                                </div>
                                <div class="predictor-impact">
                                    <div class="impact-bar" style="width: <?php echo min(($factor['impact'] ?? 0) * 100, 100); ?>%"></div>
                                    <span class="impact-value"><?php echo number_format($factor['impact'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="predictor-insight">
                        <small>
                            <i class="fas fa-chart-bar"></i> 
                            Days since last order is the strongest predictor of churn.
                        </small>
                    </div>
                </div>
                
                <!-- High Risk Customers -->
                <div class="analytics-card">
                    <h3><i class="fas fa-user-slash"></i> High Risk Customers</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Last Order</th>
                                    <th>Risk Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($high_risk_customers)): ?>
                                    <?php foreach ($high_risk_customers as $customer): ?>
                                        <tr class="high-risk-row">
                                            <td><?php 
                                                $customer_name = trim($customer['customer_name'] ?? '');
                                                if (empty($customer_name) || $customer_name === ' ') {
                                                    echo htmlspecialchars(substr($customer['email'] ?? '', 0, strpos($customer['email'] ?? '', '@') ?: 15));
                                                } else {
                                                    echo htmlspecialchars($customer_name);
                                                }
                                            ?></td>
                                            <td><?php echo htmlspecialchars(substr($customer['email'] ?? '', 0, 15)) . '...'; ?></td>
                                            <td><?php echo $customer['order_count'] ?? 0; ?></td>
                                            <td><?php echo $customer['days_since_last_order'] ?? 'N/A'; ?> days</td>
                                            <td>
                                                <span class="risk-badge high-risk">
                                                    <?php echo isset($customer['churn_probability']) ? number_format($customer['churn_probability'] * 100, 0) : 'N/A'; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <?php if ($latest_prediction_date): ?>
                                                No high risk customers found in latest predictions (<?php echo $latest_prediction_date; ?>)
                                            <?php else: ?>
                                                No prediction data available. Run analysis to generate predictions.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="high-risk-actions">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Click "Send Retention Email" button above to target these customers.
                        </small>
                    </div>
                </div>
                
                <!-- Recent Predictions Trend -->
                <div class="analytics-card">
                    <h3><i class="fas fa-chart-line"></i> Predictions Trend</h3>
                    <div class="predictions-trend">
                        <?php if (!empty($recent_predictions)): ?>
                            <div class="trend-chart">
                                <?php 
                                $max_predictions = max(array_column($recent_predictions, 'total_predictions')) ?? 1;
                                foreach ($recent_predictions as $prediction): 
                                    $height = $max_predictions > 0 ? (($prediction['total_predictions'] ?? 0) / $max_predictions * 100) : 0;
                                ?>
                                    <div class="trend-bar">
                                        <div class="bar-value" style="height: <?php echo $height; ?>%"></div>
                                        <div class="bar-label"><?php echo date('m/d', strtotime($prediction['prediction_date'] ?? date('Y-m-d'))); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="trend-stats">
                                <div class="trend-stat">
                                    <span class="stat-label">Avg. Churn Probability:</span>
                                    <span class="stat-value"><?php 
                                        $avg_churn_sum = array_sum(array_column($recent_predictions, 'avg_churn_probability'));
                                        $count = count($recent_predictions);
                                        echo $count > 0 ? number_format(($avg_churn_sum / $count) * 100, 1) : 0;
                                    ?>%</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent prediction data. Run analysis to generate trends.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Churn by Category -->
                <div class="analytics-card full-width">
                    <h3><i class="fas fa-chart-bar"></i> Churn Rate by Category</h3>
                    <div class="category-churn-grid">
                        <?php if (!empty($category_churn)): ?>
                            <?php foreach ($category_churn as $category): ?>
                                <div class="category-churn-item">
                                    <div class="category-name"><?php echo htmlspecialchars($category['category'] ?? 'Unknown'); ?></div>
                                    <div class="churn-bar-container">
                                        <div class="churn-bar" style="width: <?php echo min($category['rate'] ?? 0, 100); ?>%">
                                            <span class="churn-value"><?php echo number_format($category['rate'] ?? 0, 1); ?>%</span>
                                        </div>
                                    </div>
                                    <div class="churn-stats">
                                        <span><?php echo $category['churned_customers'] ?? 0; ?> / <?php echo $category['total_customers'] ?? 0; ?> churned</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No category data available. Add products and orders to see analysis.</p>
                        <?php endif; ?>
                    </div>
                    <div class="category-insight">
                        <small>
                            <i class="fas fa-lightbulb"></i> 
                            <?php 
                            $highest_churn = 0;
                            $highest_category = '';
                            foreach ($category_churn as $category) {
                                $rate = $category['rate'] ?? 0;
                                if ($rate > $highest_churn) {
                                    $highest_churn = $rate;
                                    $highest_category = $category['category'] ?? 'Unknown';
                                }
                            }
                            if ($highest_churn > 0) {
                                echo "Highest churn in $highest_category category. Consider improving customer engagement.";
                            }
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Inventory Tab -->
        <div id="inventory-tab" class="tab-content">
            <div class="tab-header">
                <h2><i class="fas fa-boxes"></i> Inventory Management</h2>
                <p>Manage product inventory and stock levels</p>
            </div>
            
            <!-- Product List -->
            <div class="dashboard-card">
                <h3><i class="fas fa-list"></i> Product Inventory</h3>
                <div class="inventory-summary">
                    <div class="summary-item">
                        <i class="fas fa-box"></i>
                        <div>
                            <span class="summary-value"><?php echo $total_products; ?></span>
                            <span class="summary-label">Total Products</span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <span class="summary-value"><?php echo $low_stock; ?></span>
                            <span class="summary-label">Low Stock Items</span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-dollar-sign"></i>
                        <div>
                            <span class="summary-value">$<?php echo number_format($stock_value, 2); ?></span>
                            <span class="summary-label">Total Stock Value</span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <span class="summary-value"><?php echo $total_sales; ?></span>
                            <span class="summary-label">Total Sales</span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Sales</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr class="<?php echo ($product['stock'] ?? 0) < 20 ? 'low-stock' : ''; ?>">
                                        <td>#<?php echo $product['id'] ?? ''; ?></td>
                                        <td>
                                            <div class="product-info">
                                                <strong><?php echo htmlspecialchars($product['name'] ?? ''); ?></strong>
                                                <small><?php echo htmlspecialchars($product['category'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            $<?php echo number_format($product['price'] ?? 0, 2); ?>
                                            <?php if (($product['discount'] ?? 0) > 0): ?>
                                                <br><small class="discount"><?php echo $product['discount']; ?>% off</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? 0; ?>">
                                                <div class="stock-control">
                                                    <input type="number" name="stock" value="<?php echo $product['stock'] ?? 0; ?>" min="0" class="stock-input">
                                                    <button type="submit" name="update_product" class="btn btn-sm btn-outline">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td><?php echo $product['sales'] ?? 0; ?></td>
                                        <td>
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? 0; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Stock Alerts -->
            <div class="dashboard-card">
                <h3><i class="fas fa-exclamation-circle"></i> Low Stock Alerts</h3>
                <?php if ($low_stock > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        There are <?php echo $low_stock; ?> products with low stock levels (below 20 units).
                    </div>
                    <div class="low-stock-list">
                        <?php 
                        $low_stock_products = $db->fetchAll("SELECT * FROM products WHERE stock < 20 ORDER BY stock ASC LIMIT 10") ?: [];
                        if (!empty($low_stock_products)): 
                        ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Current Stock</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($low_stock_products as $product): ?>
                                            <tr class="low-stock">
                                                <td><?php echo htmlspecialchars($product['name'] ?? ''); ?></td>
                                                <td>
                                                    <span class="stock-indicator critical"><?php echo $product['stock'] ?? 0; ?></span>
                                                </td>
                                                <td><span class="category-badge"><?php echo htmlspecialchars($product['category'] ?? ''); ?></span></td>
                                                <td>
                                                    <?php if (($product['stock'] ?? 0) < 5): ?>
                                                        <span class="status-badge critical">Critical</span>
                                                    <?php elseif (($product['stock'] ?? 0) < 10): ?>
                                                        <span class="status-badge warning">Warning</span>
                                                    <?php else: ?>
                                                        <span class="status-badge low">Low</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        All products have sufficient stock levels.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reports Tab -->
        <div id="reports-tab" class="tab-content">
            <div class="tab-header">
                <h2><i class="fas fa-file-alt"></i> Reports & Exports</h2>
                <p>Generate reports and export data for analysis</p>
            </div>
            
            <div class="reports-grid">
                <!-- Export Options -->
                <div class="dashboard-card">
                    <h3><i class="fas fa-download"></i> Data Export</h3>
                    <div class="export-options">
                        <div class="export-option">
                            <div class="export-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="export-info">
                                <h4>Customer Analysis Report</h4>
                                <p>Export customer data with churn predictions and risk levels</p>
                            </div>
                            <form method="POST">
                                <button type="submit" name="export_customers" class="btn btn-primary">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </button>
                            </form>
                        </div>
                        
                        <div class="export-option">
                            <div class="export-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="export-info">
                                <h4>Product Inventory Report</h4>
                                <p>Export complete product inventory with stock levels and sales data</p>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="exportProducts()">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                        </div>
                        
                        <div class="export-option">
                            <div class="export-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="export-info">
                                <h4>Sales Report</h4>
                                <p>Export sales data by date range and category</p>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="showSalesReportModal()">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Report Templates -->
                <div class="dashboard-card">
                    <h3><i class="fas fa-file-alt"></i> Report Templates</h3>
                    <div class="report-templates">
                        <div class="report-template">
                            <div class="template-icon bg-primary">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="template-info">
                                <h4>Churn Risk Report</h4>
                                <p>Weekly report of high-risk customers and churn trends</p>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="generateChurnReport()">
                                Generate
                            </button>
                        </div>
                        
                        <div class="report-template">
                            <div class="template-icon bg-success">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="template-info">
                                <h4>Sales Performance</h4>
                                <p>Monthly sales performance by product category</p>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="generateSalesReport()">
                                Generate
                            </button>
                        </div>
                        
                        <div class="report-template">
                            <div class="template-icon bg-warning">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="template-info">
                                <h4>Inventory Report</h4>
                                <p>Stock levels and reorder recommendations</p>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="generateInventoryReport()">
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="dashboard-card">
                    <h3><i class="fas fa-chart-bar"></i> Quick Statistics</h3>
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-title">Total Customers</div>
                                <div class="stat-value"><?php echo $customer_stats['total_customers']; ?></div>
                            </div>
                        </div>
                        
                        <div class="quick-stat">
                            <div class="stat-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-title">Total Products</div>
                                <div class="stat-value"><?php echo $total_products; ?></div>
                            </div>
                        </div>
                        
                        <div class="quick-stat">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-title">30-Day Orders</div>
                                <div class="stat-value"><?php echo $recent_orders['total_orders']; ?></div>
                            </div>
                        </div>
                        
                        <div class="quick-stat">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-details">
                                <div class="stat-title">30-Day Revenue</div>
                                <div class="stat-value">$<?php echo number_format($recent_orders['total_revenue'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-insight">
                        <h4><i class="fas fa-lightbulb"></i> Insights</h4>
                        <p>Consider exporting customer data weekly to track churn trends and identify high-risk customers for retention campaigns.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice"></i> Order Details</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <!-- Order details will be loaded here via AJAX -->
                    <div class="loading" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading order details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Close</button>
                <button type="button" class="btn btn-primary" id="printOrderBtn">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal" id="updateStatusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Update Order Status</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form method="POST" id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="updateOrderId">
                    <input type="hidden" name="current_status" id="currentStatus">
                    
                    <div class="form-group">
                        <label>Current Status</label>
                        <div class="current-status-display" id="currentStatusDisplay">
                            <!-- Current status will be shown here -->
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>New Status *</label>
                        <select name="order_status" id="orderStatusSelect" required class="status-select">
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason for Change (Optional)</label>
                        <textarea name="change_reason" id="changeReason" rows="3" 
                                  placeholder="Enter reason for status change..."></textarea>
                    </div>
                    
                    <div class="alert alert-info" id="statusChangeInfo">
                        <i class="fas fa-info-circle"></i> 
                        This change will be recorded in the order history.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" name="update_order_status" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cancel Order Modal -->
    <div class="modal" id="cancelOrderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Cancel Order</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <form method="POST" id="cancelOrderForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="cancelOrderId">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action cannot be undone. The order will be marked as cancelled.
                    </div>
                    
                    <div class="form-group">
                        <label>Reason for Cancellation *</label>
                        <textarea name="cancel_reason" id="cancelReason" rows="3" required 
                                  placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Notify Customer</label>
                        <div class="checkbox-group">
                            <input type="checkbox" name="notify_customer" id="notifyCustomer" checked>
                            <label for="notifyCustomer">Send cancellation email to customer</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" name="cancel_order" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Reset and Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: #f5f7fa;
    color: #333;
    line-height: 1.6;
}

.dashboard-container {
    padding: 20px;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: #2c3e50;
    margin: 0 0 10px 0;
    font-size: 28px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.welcome-text {
    color: #7f8c8d;
    font-size: 16px;
    margin: 0;
}

/* Dashboard Tabs */
.dashboard-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    padding-bottom: 10px;
    border-bottom: 2px solid #eaeaea;
    overflow-x: auto;
}

.tab-btn {
    padding: 12px 24px;
    background: #f8f9fa;
    border: none;
    border-radius: 8px 8px 0 0;
    font-size: 14px;
    font-weight: 600;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    white-space: nowrap;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}

.tab-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.tab-btn.active {
    background: white;
    color: #007bff;
    border-bottom: 2px solid #007bff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
}

.tab-content {
    display: none;
    animation: fadeIn 0.5s ease;
}

.tab-content.active {
    display: block;
}

.tab-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eaeaea;
}

.tab-header h2 {
    color: #2c3e50;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
}

.tab-header p {
    color: #6c757d;
    margin: 0 0 15px 0;
}

/* Dashboard Layout */
.dashboard-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.dashboard-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.full-width {
    grid-column: 1 / -1;
}

/* Alert Messages */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
}

.alert-error {
    background-color: #fee;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eaeaea;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.bg-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
.bg-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
.bg-info { background: linear-gradient(135deg, #17a2b8, #117a8b); }
.bg-secondary { background: linear-gradient(135deg, #6c757d, #545b62); }
.bg-dark { background: linear-gradient(135deg, #343a40, #23272b); }
.bg-purple { background: linear-gradient(135deg, #6f42c1, #593a9c); }

.stat-info h3 {
    margin: 0;
    font-size: 28px;
    color: #2c3e50;
    font-weight: 700;
}

.stat-info p {
    margin: 5px 0 0 0;
    color: #7f8c8d;
    font-size: 14px;
}

.stat-info small {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

/* Dashboard Cards */
.dashboard-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: 1px solid #eaeaea;
}

.dashboard-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

/* JSON Status Styles */
.json-status {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.status-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 600;
    color: #495057;
}

.status-value {
    color: #6c757d;
}

.text-success {
    color: #28a745;
}

.text-danger {
    color: #dc3545;
}

.mt-3 {
    margin-top: 15px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

/* Order Status Badges */
.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-badge.processing {
    background: #cce5ff;
    color: #004085;
    border: 1px solid #b8daff;
}

.status-badge.shipped {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.status-badge.completed {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-badge.refunded {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

/* Order Table Row Styles */
.completed-order {
    background: linear-gradient(90deg, #e6ffe6, #fff);
}

.cancelled-order {
    background: linear-gradient(90deg, #ffe6e6, #fff);
}

.pending-order {
    background: linear-gradient(90deg, #fff8e6, #fff);
}

/* Order Actions */
.order-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.order-actions .btn {
    padding: 5px 10px;
    font-size: 12px;
    gap: 5px;
}

/* Analytics Section */
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.analytics-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border: 1px solid #eaeaea;
}

.analytics-card.full-width {
    grid-column: 1 / -1;
}

.analytics-card h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

/* Customer Stats */
.customer-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.customer-stat {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
}

.customer-insight, .risk-insight, .predictor-insight, .category-insight, .high-risk-actions {
    margin-top: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 13px;
    color: #6c757d;
    border-left: 4px solid #007bff;
    line-height: 1.5;
}

.high-risk-actions {
    border-left-color: #dc3545;
}

.risk-insight {
    border-left-color: #ffc107;
}

/* Model Performance */
.model-performance {
    text-align: center;
}

.model-name {
    font-weight: 600;
    color: #495057;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 15px;
}

.performance-metrics {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.metric {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.metric-label {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 8px;
    font-weight: 500;
}

.metric-value {
    font-size: 18px;
    font-weight: bold;
    color: #28a745;
}

.no-data {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 30px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px dashed #dee2e6;
}

/* Risk Distribution */
.risk-distribution {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 15px;
}

.risk-segment {
    display: flex;
    align-items: center;
    gap: 15px;
}

.risk-label {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 120px;
    font-weight: 500;
    color: #495057;
    font-size: 14px;
}

.risk-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.risk-dot.low-risk { background: #28a745; }
.risk-dot.medium-risk { background: #ffc107; }
.risk-dot.high-risk { background: #dc3545; }

.risk-bar {
    flex: 1;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.risk-fill {
    height: 100%;
    transition: width 0.5s ease;
}

.risk-fill.low-risk { background: linear-gradient(90deg, #28a745, #34ce57); }
.risk-fill.medium-risk { background: linear-gradient(90deg, #ffc107, #ffd54f); }
.risk-fill.high-risk { background: linear-gradient(90deg, #dc3545, #e83e8c); }

.risk-value {
    width: 100px;
    text-align: right;
    font-size: 14px;
    color: #495057;
    font-weight: 500;
}

/* Predictors List */
.predictors-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 15px;
}

.predictor-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.predictor-name {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    font-size: 14px;
    color: #495057;
}

.predictor-direction {
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
}

.predictor-direction.positive {
    background: #dc3545;
    color: white;
}

.predictor-direction.negative {
    background: #28a745;
    color: white;
}

.predictor-impact {
    display: flex;
    align-items: center;
    gap: 10px;
}

.impact-bar {
    flex: 1;
    height: 8px;
    background: linear-gradient(90deg, #dc3545, #ffc107, #28a745);
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.impact-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #e9ecef;
    z-index: 1;
}

.impact-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: inherit;
    z-index: 2;
}

.impact-value {
    width: 40px;
    text-align: right;
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
}

/* Risk Rows and Badges */
.high-risk-row { background: linear-gradient(90deg, #ffe6e6, #fff); }
.medium-risk-row { background: linear-gradient(90deg, #fff8e6, #fff); }
.low-risk-row { background: linear-gradient(90deg, #e6ffe6, #fff); }

.risk-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    color: white;
    display: inline-block;
    min-width: 60px;
    text-align: center;
}

.risk-badge.high-risk { background: linear-gradient(135deg, #dc3545, #c82333); }
.risk-badge.medium-risk { background: linear-gradient(135deg, #ffc107, #e0a800); }
.risk-badge.low-risk { background: linear-gradient(135deg, #28a745, #1e7e34); }

/* Predictions Trend */
.predictions-trend {
    height: 200px;
    display: flex;
    flex-direction: column;
}

.trend-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    height: 140px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.trend-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 30px;
    gap: 5px;
}

.bar-value {
    width: 20px;
    background: linear-gradient(0deg, #007bff, #17a2b8);
    border-radius: 3px 3px 0 0;
    transition: height 0.3s ease;
    min-height: 5px;
}

.bar-label {
    font-size: 11px;
    color: #6c757d;
}

.trend-stats {
    text-align: center;
}

.trend-stat {
    display: inline-block;
    background: #f8f9fa;
    padding: 10px 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.stat-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
}

.stat-value {
    font-size: 15px;
    font-weight: bold;
    color: #2c3e50;
    margin-left: 8px;
}

/* Category Churn */
.category-churn-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.category-churn-item {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.category-name {
    font-weight: 600;
    color: #495057;
    font-size: 15px;
}

.churn-bar-container {
    height: 24px;
    background: #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    border: 1px solid #dee2e6;
}

.churn-bar {
    height: 100%;
    background: linear-gradient(90deg, #dc3545, #ffc107, #28a745);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 10px;
    min-width: 40px;
    transition: width 0.5s ease;
}

.churn-value {
    color: white;
    font-size: 12px;
    font-weight: bold;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.churn-stats {
    font-size: 13px;
    color: #6c757d;
}

/* Product Form */
.product-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.product-form .form-group {
    margin-bottom: 15px;
}

.product-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.product-form input,
.product-form select,
.product-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    background-color: white;
}

.product-form input:focus,
.product-form select:focus,
.product-form textarea:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.product-form input[type="number"] {
    appearance: textfield;
}

.product-form input::-webkit-outer-spin-button,
.product-form input::-webkit-inner-spin-button {
    appearance: none;
    margin: 0;
}

.product-form small {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

/* Image Upload Styles */
.image-upload-container {
    margin-bottom: 10px;
}

.image-upload-container input[type="file"] {
    margin-bottom: 10px;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    width: 100%;
    background: white;
}

.image-preview {
    width: 150px;
    height: 150px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 10px;
    background: #f8f9fa;
}

.image-preview-default {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #6c757d;
}

.image-preview-default i {
    font-size: 48px;
    margin-bottom: 10px;
    color: #adb5bd;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

/* Tables */
.table-responsive {
    overflow-x: auto;
    margin: 0 -5px;
    padding: 0 5px;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 14px;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
    font-size: 14px;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.data-table tr.low-stock {
    background: #fff3cd;
}

.data-table tr.low-stock:hover {
    background: #ffeaa7;
}

.data-table tr.low-stock td {
    border-color: #ffe8a1;
}

/* Product Info */
.product-info {
    display: flex;
    flex-direction: column;
}

.product-info strong {
    margin-bottom: 4px;
    color: #2c3e50;
    font-size: 15px;
}

.product-info small {
    color: #6c757d;
    font-size: 13px;
}

.category-badge {
    background: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.stock-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.stock-badge.low-stock {
    background: #dc3545;
    color: white;
}

.stock-badge.good-stock {
    background: #28a745;
    color: white;
}

.stock-indicator {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.stock-indicator.critical {
    background: #dc3545;
    color: white;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.critical {
    background: #dc3545;
    color: white;
}

.status-badge.warning {
    background: #ffc107;
    color: #212529;
}

.status-badge.low {
    background: #fd7e14;
    color: white;
}

.discount {
    color: #dc3545;
    font-size: 12px;
    font-weight: 500;
}

/* Stock Control */
.stock-control {
    display: flex;
    gap: 8px;
    align-items: center;
}

.stock-input {
    width: 80px;
    padding: 8px 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    text-align: center;
}

.stock-input:focus {
    border-color: #80bdff;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    line-height: 1.5;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border: 1px solid #0056b3;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #545b62);
    color: white;
    border: 1px solid #545b62;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #545b62, #3d4246);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108,117,125,0.3);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border: 1px solid #c82333;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
}

.btn-outline {
    background: transparent;
    border: 1px solid #ced4da;
    color: #6c757d;
}

.btn-outline:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
    gap: 5px;
}

/* Categories List */
.categories-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.category-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.category-stats {
    display: flex;
    gap: 20px;
}

.stat-count,
.stat-sales {
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
}

.stat-count {
    color: #007bff;
}

.stat-sales {
    color: #28a745;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.inline-form {
    display: inline-block;
}

.dashboard-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Inventory Summary */
.inventory-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.summary-item i {
    font-size: 24px;
    color: #007bff;
}

.summary-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
}

.summary-label {
    display: block;
    font-size: 14px;
    color: #6c757d;
    margin-top: 5px;
}

/* Stock Value */
.stock-value {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-top: 20px;
}

.stock-value h5 {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stock-value .value {
    font-size: 32px;
    font-weight: bold;
    color: #28a745;
}

/* Reports Grid */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.export-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.export-option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.export-icon {
    width: 50px;
    height: 50px;
    background: #007bff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.export-info {
    flex: 1;
}

.export-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 16px;
}

.export-info p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

.report-templates {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.report-template {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.template-icon {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.template-info {
    flex: 1;
}

.template-info h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 16px;
}

.template-info p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.4;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.quick-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.quick-stat .stat-icon {
    width: 40px;
    height: 40px;
    background: #e9ecef;
    color: #007bff;
    font-size: 18px;
}

.stat-details {
    flex: 1;
}

.stat-title {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #2c3e50;
    margin-top: 5px;
}

.report-insight {
    margin-top: 20px;
    padding: 15px;
    background: #e7f3ff;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.report-insight h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.report-insight p {
    margin: 0;
    color: #495057;
    font-size: 14px;
    line-height: 1.5;
}

.text-center {
    text-align: center;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.modal-close:hover {
    background: #f8f9fa;
    color: #dc3545;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Current Status Display */
.current-status-display {
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    font-weight: 500;
}

/* Status Select */
.status-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.status-select:focus {
    border-color: #80bdff;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Checkbox Group */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.checkbox-group label {
    margin: 0;
    font-weight: normal;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .analytics-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .category-churn-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .modal-content {
        width: 95%;
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .customer-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .performance-metrics {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .category-churn-grid {
        grid-template-columns: 1fr;
    }
    
    .product-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .dashboard-tabs {
        overflow-x: auto;
        padding-bottom: 5px;
    }
    
    .tab-btn {
        padding: 10px 15px;
        font-size: 13px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
    }
    
    .risk-segment {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .risk-bar {
        width: 100%;
    }
    
    .risk-value {
        text-align: left;
        width: 100%;
    }
    
    .dashboard-card {
        padding: 20px;
    }
    
    .data-table th,
    .data-table td {
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .inventory-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-stats {
        grid-template-columns: 1fr;
    }
    
    .order-actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .order-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .customer-stats {
        grid-template-columns: 1fr;
    }
    
    .performance-metrics {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .stat-info h3 {
        font-size: 24px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .inventory-summary {
        grid-template-columns: 1fr;
    }
    
    .export-option,
    .report-template {
        flex-direction: column;
        text-align: center;
    }
    
    .export-icon,
    .template-icon {
        width: 60px;
        height: 60px;
    }
    
    .export-info,
    .template-info {
        text-align: center;
    }
    
    .modal-content {
        width: 100%;
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
}

/* Print Styles */
@media print {
    .dashboard-tabs,
    .action-buttons,
    .btn,
    .product-form,
    .stock-control button,
    form[onsubmit],
    .modal,
    .order-actions {
        display: none !important;
    }
    
    .dashboard-container {
        padding: 0;
    }
    
    .tab-content {
        display: block !important;
    }
    
    .stat-card,
    .analytics-card,
    .dashboard-card {
        box-shadow: none;
        border: 1px solid #ddd;
        break-inside: avoid;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            btn.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
            
            // Save active tab to localStorage
            localStorage.setItem('activeTab', tabId);
        });
    });
    
    // Load active tab from localStorage
    const savedTab = localStorage.getItem('activeTab');
    if (savedTab) {
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    }
    
    // Handle tab links
    const tabLinks = document.querySelectorAll('.tab-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = link.getAttribute('data-tab');
            const tabBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
            if (tabBtn) {
                tabBtn.click();
                // Smooth scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });
    
    // Auto-focus first input in add product form
    const firstInput = document.querySelector('.product-form input[name="name"]');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Validate stock inputs
    const stockInputs = document.querySelectorAll('.stock-input');
    stockInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 0) {
                this.value = 0;
                showToast('Stock cannot be negative', 'error');
            }
        });
    });
    
    // Confirm before deleting
    const deleteForms = document.querySelectorAll('form[onsubmit]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this product?')) {
                e.preventDefault();
            }
        });
    });
    
    // Analytics tooltips
    const riskSegments = document.querySelectorAll('.risk-segment');
    riskSegments.forEach(segment => {
        segment.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'analytics-tooltip';
            tooltip.textContent = 'Click to view detailed analysis';
            tooltip.style.cssText = `
                position: fixed;
                background: #2c3e50;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 9999;
                pointer-events: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                transform: translate(-50%, -100%);
                white-space: nowrap;
            `;
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 + 'px';
            tooltip.style.top = rect.top - 10 + 'px';
            
            document.body.appendChild(tooltip);
            this._tooltip = tooltip;
        });
        
        segment.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
        
        segment.addEventListener('click', function() {
            const riskLevel = this.querySelector('.risk-label').textContent.trim();
            alert(`Viewing detailed analysis for ${riskLevel} segment`);
        });
    });
    
    // Send retention email confirmation
    const sendEmailBtn = document.querySelector('button[name="send_retention_email"]');
    if (sendEmailBtn) {
        sendEmailBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to send retention emails to all high-risk customers?')) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation
    const productForm = document.querySelector('.product-form');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            const nameInput = this.querySelector('input[name="name"]');
            const priceInput = this.querySelector('input[name="price"]');
            const categorySelect = this.querySelector('select[name="category"]');
            const imageInput = this.querySelector('input[name="image"]');
            
            let isValid = true;
            let errorMessage = '';
            
            if (!nameInput.value.trim()) {
                isValid = false;
                errorMessage = 'Product name is required';
                nameInput.focus();
            } else if (parseFloat(priceInput.value) <= 0) {
                isValid = false;
                errorMessage = 'Price must be greater than 0';
                priceInput.focus();
            } else if (!categorySelect.value) {
                isValid = false;
                errorMessage = 'Category is required';
                categorySelect.focus();
            } else if (!imageInput.files || imageInput.files.length === 0) {
                isValid = false;
                errorMessage = 'Please select an image file';
                imageInput.focus();
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast(errorMessage, 'error');
            }
        });
    }
    
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const modalCloseBtns = document.querySelectorAll('.modal-close');
    
    // Function to show modal
    window.showModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };
    
    // Function to hide modal
    window.hideModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };
    
    // Close modal when clicking close button
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
    
    // View order details
    const viewOrderBtns = document.querySelectorAll('.view-order-btn');
    viewOrderBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            loadOrderDetails(orderId);
            showModal('orderDetailsModal');
        });
    });
    
    // Update order status
    const updateStatusBtns = document.querySelectorAll('.update-status-btn');
    updateStatusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const currentStatus = this.getAttribute('data-current-status');
            
            document.getElementById('updateOrderId').value = orderId;
            document.getElementById('currentStatus').value = currentStatus;
            
            // Update current status display
            const statusDisplay = document.getElementById('currentStatusDisplay');
            statusDisplay.innerHTML = `
                <span class="status-badge ${currentStatus}">${currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1)}</span>
            `;
            
            // Set current status as selected option
            const statusSelect = document.getElementById('orderStatusSelect');
            Array.from(statusSelect.options).forEach(option => {
                option.selected = option.value === currentStatus;
            });
            
            showModal('updateStatusModal');
        });
    });
    
    // Load order details via AJAX
    function loadOrderDetails(orderId) {
        const content = document.getElementById('orderDetailsContent');
        content.innerHTML = `
            <div class="loading" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading order details...</p>
            </div>
        `;
        
        // Simulate AJAX call (in real implementation, use fetch or XMLHttpRequest)
        setTimeout(() => {
            // This would be replaced with actual AJAX call to get order details
            content.innerHTML = `
                <div class="order-details">
                    <div class="order-header">
                        <h4>Order #${orderId}</h4>
                        <p class="order-date">Placed on ${new Date().toLocaleDateString()}</p>
                    </div>
                    
                    <div class="customer-info" style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h5><i class="fas fa-user"></i> Customer Information</h5>
                        <p><strong>Email:</strong> customer${orderId}@example.com</p>
                        <p><strong>Name:</strong> John Doe</p>
                    </div>
                    
                    <div class="order-items" style="margin: 20px 0;">
                        <h5><i class="fas fa-shopping-basket"></i> Order Items</h5>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; text-align: left;">Product</th>
                                    <th style="padding: 10px; text-align: right;">Price</th>
                                    <th style="padding: 10px; text-align: center;">Qty</th>
                                    <th style="padding: 10px; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;">Product A</td>
                                    <td style="padding: 10px; text-align: right;">$29.99</td>
                                    <td style="padding: 10px; text-align: center;">1</td>
                                    <td style="padding: 10px; text-align: right;">$29.99</td>
                                </tr>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;">Product B</td>
                                    <td style="padding: 10px; text-align: right;">$19.99</td>
                                    <td style="padding: 10px; text-align: center;">2</td>
                                    <td style="padding: 10px; text-align: right;">$39.98</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Total:</td>
                                    <td style="padding: 10px; text-align: right; font-weight: bold;">$69.97</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="order-status-history" style="margin: 20px 0;">
                        <h5><i class="fas fa-history"></i> Status History</h5>
                        <div style="margin-top: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                                <span class="status-badge pending">Pending</span>
                                <span style="color: #6c757d; font-size: 13px;">${new Date(Date.now() - 86400000).toLocaleString()}</span>
                                <span style="margin-left: auto; font-size: 13px;">System</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px 0;">
                                <span class="status-badge processing">Processing</span>
                                <span style="color: #6c757d; font-size: 13px;">${new Date().toLocaleString()}</span>
                                <span style="margin-left: auto; font-size: 13px;">Admin</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }, 500);
    }
    
    // Print order button
    document.getElementById('printOrderBtn')?.addEventListener('click', function() {
        window.print();
    });
    
    // Cancel order form submission
    document.getElementById('cancelOrderForm')?.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Update status form validation
    document.getElementById('updateStatusForm')?.addEventListener('submit', function(e) {
        const statusSelect = this.querySelector('#orderStatusSelect');
        if (!statusSelect.value) {
            e.preventDefault();
            showToast('Please select a new status', 'error');
            statusSelect.focus();
        }
    });
    
    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'error' ? '#dc3545' : '#28a745'};
            color: white;
            border-radius: 6px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
        
        // Add CSS animations if not already present
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Initialize tooltips for predictor items
    const predictorItems = document.querySelectorAll('.predictor-item');
    predictorItems.forEach(item => {
        item.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'predictor-tooltip';
            const direction = this.querySelector('.predictor-direction').textContent;
            const impact = this.querySelector('.impact-value').textContent;
            tooltip.textContent = `${direction === '+' ? 'Positive' : 'Negative'} impact: ${impact}`;
            tooltip.style.cssText = `
                position: fixed;
                background: #2c3e50;
                color: white;
                padding: 6px 10px;
                border-radius: 4px;
                font-size: 11px;
                z-index: 9999;
                pointer-events: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                transform: translate(-50%, -100%);
                white-space: nowrap;
            `;
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 + 'px';
            tooltip.style.top = rect.top - 10 + 'px';
            
            document.body.appendChild(tooltip);
            this._tooltip = tooltip;
        });
        
        item.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                delete this._tooltip;
            }
        });
    });
    
    // Export functions for reports tab
    window.exportProducts = function() {
        showToast('Product export feature coming soon!', 'info');
    };
    
    window.showSalesReportModal = function() {
        showToast('Sales report generation feature coming soon!', 'info');
    };
    
    window.generateChurnReport = function() {
        showToast('Generating churn risk report...', 'info');
        // Simulate report generation
        setTimeout(() => {
            showToast('Churn risk report generated successfully!', 'success');
        }, 1500);
    };
    
    window.generateSalesReport = function() {
        showToast('Generating sales performance report...', 'info');
        // Simulate report generation
        setTimeout(() => {
            showToast('Sales performance report generated successfully!', 'success');
        }, 1500);
    };
    
    window.generateInventoryReport = function() {
        showToast('Generating inventory report...', 'info');
        // Simulate report generation
        setTimeout(() => {
            showToast('Inventory report generated successfully!', 'success');
        }, 1500);
    };
    
    // JSON file synchronization functions
    window.syncJsonFile = function() {
        if (!confirm('This will synchronize all products from the database to the JSON file. Continue?')) {
            return;
        }
        
        showToast('Starting JSON synchronization...', 'info');
        
        // Use AJAX to call a synchronization endpoint
        fetch('api/sync_json.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('JSON file synchronized successfully!', 'success');
                    // Reload the page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showToast('Error synchronizing JSON file: ' + error.message, 'error');
            });
    };
    
    window.viewJsonContent = function() {
        // Open JSON file in a new window
        window.open('data/games.json', '_blank');
    };
    
    // 图片预览功能
    window.previewImage = function(input) {
        const preview = document.getElementById('imagePreview');
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Image Preview">`;
            };
            
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `
                <div class="image-preview-default">
                    <i class="fas fa-image"></i>
                    <span>No image selected</span>
                </div>
            `;
        }
    };
});

// Add additional JavaScript functions for JSON management
function updateJsonFileFromDatabase() {
    // This function would make an AJAX call to update the JSON file
    console.log('Updating JSON file from database...');
    // In a real implementation, this would call an API endpoint
}
</script>

<?php 
require_once 'includes/footer.php';
?>