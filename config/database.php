<?php
// 数据库连接配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // 默认XAMPP用户名
define('DB_PASS', '');      // 默认XAMPP密码为空，如有修改请更新
define('DB_NAME', 'education_report_db');

// 创建数据库连接
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // 检查连接
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    // 设置字符集
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// 关闭数据库连接
function closeDB($conn) {
    $conn->close();
}

// 执行SQL查询并返回结果
function query($sql) {
    $conn = connectDB();
    $result = $conn->query($sql);
    closeDB($conn);
    return $result;
}

// 执行SQL查询并返回插入ID
function insert($sql) {
    $conn = connectDB();
    $conn->query($sql);
    $id = $conn->insert_id;
    closeDB($conn);
    return $id;
}

// 执行SQL查询并返回影响的行数
function execute($sql) {
    $conn = connectDB();
    $conn->query($sql);
    $affected = $conn->affected_rows;
    closeDB($conn);
    return $affected;
}

// 转义字符串防止SQL注入
function escapeString($string) {
    $conn = connectDB();
    $escaped = $conn->real_escape_string($string);
    closeDB($conn);
    return $escaped;
}
