<?php
// subscribers.php - 修改后版本，与产品页样式一致
session_start();

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Please login to view this page';
    $_SESSION['message_type'] = 'error';
    header('Location: login.php');
    exit();
}

// 数据库配置 - 请根据您的实际情况修改
$db_host = 'sql307.infinityfree.com';
$db_user = 'if0_39945006'; // 替换为您的数据库用户名
$db_pass = '52630000Aa'; // 替换为您的数据库密码
$db_name = 'if0_39945006_wp911'; // 替换为您的数据库名

// 连接到数据库
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// 检查数据库连接
if (!$conn) {
    $error_message = "Database connection failed. Please check your database configuration.";
    // 不要退出，继续显示页面但有错误信息
}

// 获取订阅者数据
function getSubscribers($connection) {
    if (!$connection) return [];
    
    $subscribers = [];
    $query = "SELECT * FROM wp9k_fc_subscribers ORDER BY id DESC LIMIT 100";
    $result = mysqli_query($connection, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $subscribers[] = $row;
        }
    }
    
    return $subscribers;
}

// 获取表字段
function getTableColumns($connection) {
    if (!$connection) return [];
    
    $columns = [];
    $result = mysqli_query($connection, "SHOW COLUMNS FROM wp9k_fc_subscribers");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $columns[] = $row['Field'];
        }
    }
    
    return $columns;
}

// 获取数据
$subscribers = $conn ? getSubscribers($conn) : [];
$columns = $conn ? getTableColumns($conn) : [];

// 如果表不存在，尝试获取示例数据
if (empty($columns) && $conn) {
    $columns = ['id', 'email', 'first_name', 'last_name', 'status', 'created_at'];
    // 添加示例数据用于测试
    $subscribers = [
        ['id' => 1, 'email' => 'test1@example.com', 'first_name' => 'John', 'last_name' => 'Doe', 'status' => 'active', 'created_at' => '2024-01-01'],
        ['id' => 2, 'email' => 'test2@example.com', 'first_name' => 'Jane', 'last_name' => 'Smith', 'status' => 'inactive', 'created_at' => '2024-01-02'],
    ];
}

// 关闭数据库连接
if ($conn) {
    mysqli_close($conn);
}

$page_title = "Subscribers - GameHub";
require_once 'includes/header.php';
?>

<div class="content">
    <?php if(isset($error_message)): ?>
        <div class="alert alert-error">
            <strong>Database Error:</strong> <?php echo $error_message; ?>
            <p>Please update the database configuration in subscribers.php file.</p>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Subscribers Management</h1>
        <div class="subscribers-count">Total: <?php echo count($subscribers); ?> subscribers</div>
    </div>
    
    <div class="subscribers-actions">
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Search subscribers..." onkeyup="searchSubscribers()">
            <i class="fas fa-search"></i>
        </div>
        <button class="btn btn-primary" onclick="exportToCSV()">
            <i class="fas fa-download"></i> Export CSV
        </button>
    </div>
    
    <?php if (empty($subscribers)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>No Subscribers Data</h3>
            <p>No subscriber records found in the database</p>
            <p><small>This could be because the database table doesn't exist or is empty.</small></p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table id="subscribers-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <?php 
                            // 格式化列名
                            $formatted_column = str_replace('_', ' ', $column);
                            $formatted_column = ucwords($formatted_column);
                            ?>
                            <th><?php echo $formatted_column; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td>
                                    <?php 
                                    $value = $subscriber[$column] ?? '';
                                    
                                    // 格式化显示
                                    if (empty($value) || $value === 'NULL') {
                                        echo '<span style="color:#999; font-style:italic;">—</span>';
                                    } elseif ($column === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                        echo '<a href="mailto:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                                    } elseif ($column === 'status') {
                                        $status_class = ($value == 'active' || $value == '1') ? 'status-active' : 'status-inactive';
                                        $status_text = ($value == 'active' || $value == '1') ? 'Active' : 'Inactive';
                                        echo '<span class="status-badge ' . $status_class . '">' . $status_text . '</span>';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; padding: 20px; color: #666;">
            Showing <?php echo count($subscribers); ?> records
        </div>
    <?php endif; ?>
</div>

<style>
/* 订阅者管理页面特定样式 */

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eaeaea;
}

.page-header h1 {
    font-size: 28px;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header h1 i {
    color: #4CAF50;
}

.subscribers-count {
    background: #f0f0f0;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 14px;
    color: #666;
}

/* 表格样式 */
.table-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    overflow-x: auto;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #eaeaea;
    position: sticky;
    top: 0;
}

td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

tbody tr:hover {
    background: #f8f9fa;
}

/* 状态标签 */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

/* 警告框 */
.alert {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 4px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* 操作按钮 */
.subscribers-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 15px;
    flex-wrap: wrap;
}

.search-container {
    position: relative;
    flex-grow: 1;
    max-width: 400px;
}

.search-container input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.search-container input:focus {
    border-color: #4CAF50;
    outline: none;
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-state i {
    font-size: 60px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

/* 响应式 */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .subscribers-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-container {
        max-width: 100%;
    }
    
    table {
        min-width: 600px;
    }
}
</style>

<script>
// 搜索功能
function searchSubscribers() {
    const input = document.getElementById('search-input');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('subscribers-table');
    const rows = table.getElementsByTagName('tr');
    
    // 跳过表头
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const text = cell.textContent || cell.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

// 导出为CSV
function exportToCSV() {
    const table = document.getElementById('subscribers-table');
    const rows = table.querySelectorAll('tr');
    let csvContent = '';
    
    // 表头
    const headerRow = rows[0];
    const headerCells = headerRow.querySelectorAll('th');
    const headers = Array.from(headerCells).map(cell => {
        let text = cell.textContent || cell.innerText;
        return `"${text}"`;
    });
    csvContent += headers.join(',') + '\n';
    
    // 数据行
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        if (row.style.display === 'none') continue;
        
        const cells = row.querySelectorAll('td');
        const rowData = Array.from(cells).map(cell => {
            let cellText = cell.textContent || cell.innerText || '';
            // 移除状态标签的HTML
            cellText = cellText.replace(/Active|Inactive/g, '').trim();
            cellText = cellText.replace(/"/g, '""');
            return `"${cellText}"`;
        });
        csvContent += rowData.join(',') + '\n';
    }
    
    // 创建下载链接
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `subscribers_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // 显示成功消息
    showNotification('Subscribers data exported successfully!');
}

// 显示通知
function showNotification(message) {
    // 检查是否已存在通知元素
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    notification.textContent = message;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}
</script>

<?php 
require_once 'includes/footer.php';
?>