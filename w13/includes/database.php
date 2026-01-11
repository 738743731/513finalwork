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

// Database类用于更便捷的数据库操作
class Database {
    private $conn;
    private $stmt;

    public function __construct() {
        try {
            // 使用PDO进行数据库连接
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            // 记录错误但不直接显示给用户
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error. Please try again later.");
        }
    }

    // 执行SQL查询（带参数绑定）
    public function query($sql, $params = []) {
        try {
            $this->stmt = $this->conn->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt;
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            throw new Exception("Database query error.");
        }
    }

    // 获取所有结果
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // 获取单行结果
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // 获取单个值
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    // 插入数据并返回最后插入的ID
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->conn->lastInsertId();
    }

    // 更新数据并返回受影响的行数
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    // 删除数据并返回受影响的行数
    public function delete($sql, $params = []) {
        return $this->update($sql, $params);
    }

    // 开启事务
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // 提交事务
    public function commit() {
        return $this->conn->commit();
    }

    // 回滚事务
    public function rollBack() {
        return $this->conn->rollBack();
    }

    // 关闭数据库连接
    public function close() {
        $this->conn = null;
        $this->stmt = null;
    }

    // 获取数据库错误信息
    public function getError() {
        return $this->conn->errorInfo();
    }
}

// 可选：创建一个全局的数据库连接实例
function getDatabaseInstance() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

?>