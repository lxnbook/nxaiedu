<?php
// 应用程序配置

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 网站根目录URL
define('BASE_URL', 'http://localhost/nxaiedu');

// 上传文件目录
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// 启动会话
session_start();

// 包含数据库配置
require_once 'database.php';

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 需要登录才能访问的页面
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', '请先登录');
        redirect(BASE_URL . '/auth/login.php');
        exit;
    }
}

// 需要管理员权限才能访问的页面
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] != 'admin') {
        setFlashMessage('error', '您没有权限访问此页面');
        redirect(BASE_URL . '/dashboard.php');
        exit;
    }
}

// 需要管理员或经理权限才能访问的页面
function requireManagerOrAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
        setFlashMessage('error', '您没有权限访问此页面');
        redirect(BASE_URL . '/dashboard.php');
        exit;
    }
}

// 页面重定向
function redirect($url) {
    header("Location: $url");
    exit;
}

// 设置闪存消息
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// 获取闪存消息
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// 格式化文件大小
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// 获取汇报类型列表
function getReportTypes() {
    return [
        '月度汇报',
        '季度汇报',
        '年度汇报',
        '专项汇报',
        '工作总结',
        '培训反馈',
        '调研报告',
        '其他'
    ];
}
