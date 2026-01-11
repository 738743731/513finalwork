<?php

define('DB_HOST', 'sql307.infinityfree.com');  // 主机名
define('DB_USER', 'if0_39945006');             // 数据库用户名
define('DB_PASS', '52630000Aa');               // 数据库密码
define('DB_NAME', 'if0_39945006_wp911');       // 数据库名

// 测试数据库连接
function testDBConnection() {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return "连接失败: " . $conn->connect_error;
    }
    $conn->close();
    return "连接成功！";
}
?>