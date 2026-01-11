<?php
session_start();
$page_title = "Careers";
require_once 'includes/header.php';
require_once 'includes/database.php';

$success_message = "";
$error_message = "";

// 临时开启错误报告，用于调试
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $position = $_POST['position'];
        $experience = $_POST['experience'];
        $cover_letter = $_POST['cover_letter'];
        
        // 修正SQL语句：使用正确的字段名 applied_at（下划线）
        $sql = "INSERT INTO job_applications (full_name, email, phone, position, experience, cover_letter, applied_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        // 准备参数数组
        $params = [$full_name, $email, $phone, $position, $experience, $cover_letter];
        
        // 使用 Database 类的 insert 方法执行插入
        $lastInsertId = $db->insert($sql, $params);
        
        if ($lastInsertId) {
            $success_message = "Application submitted successfully! We'll contact you soon.";
            // 成功后清空 POST 数据，避免重复显示
            $_POST = array();
        } else {
            throw new Exception("Failed to insert application into database.");
        }
        
    } catch (Exception $e) {
        // 记录错误日志
        error_log("Career form error: " . $e->getMessage());
        
        // 在开发环境下显示详细错误信息
        $error_message = "Failed to submit application. Error: " . $e->getMessage();
        
        // 如果您想查看更详细的PDO错误信息，可以尝试以下方法：
        try {
            // 直接测试数据库连接和查询
            $test_db = new Database();
            $test_sql = "SHOW TABLES LIKE 'job_applications'";
            $result = $test_db->fetchOne($test_sql);
            if (!$result) {
                $error_message .= " [Table 'job_applications' not found]";
            }
        } catch (Exception $test_e) {
            $error_message .= " [Database connection test failed: " . $test_e->getMessage() . "]";
        }
    }
}
?>

<div class="careers-section">
    <h1>Join Our Team</h1>
    <p class="subtitle">Help us create amazing gaming experiences</p>
    
    <?php if(!empty($success_message)): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($error_message)): ?>
        <div class="alert alert-error">
            <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <div class="careers-content">
        <div class="job-positions">
            <h2>Open Positions</h2>
            <div class="position-card">
                <h3>Game Developer</h3>
                <p>Full-time • Remote</p>
                <p>Develop innovative games using Unity and Unreal Engine.</p>
            </div>
            <div class="position-card">
                <h3>Web Developer</h3>
                <p>Full-time • On-site</p>
                <p>Build and maintain our gaming platform using PHP and JavaScript.</p>
            </div>
            <div class="position-card">
                <h3>Customer Support</h3>
                <p>Part-time • Remote</p>
                <p>Help our customers with game-related issues and inquiries.</p>
            </div>
        </div>
        
        <div class="application-form">
            <h2>Application Form</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Desired Position *</label>
                        <select id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Game Developer" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Game Developer') ? 'selected' : ''; ?>>Game Developer</option>
                            <option value="Web Developer" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Web Developer') ? 'selected' : ''; ?>>Web Developer</option>
                            <option value="Customer Support" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Customer Support') ? 'selected' : ''; ?>>Customer Support</option>
                            <option value="Other" <?php echo (isset($_POST['position']) && $_POST['position'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="experience">Years of Experience</label>
                    <input type="number" id="experience" name="experience" min="0" max="50"
                           value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="cover_letter">Cover Letter *</label>
                    <textarea id="cover_letter" name="cover_letter" rows="6" required><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>