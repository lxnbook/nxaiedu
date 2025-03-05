<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员可以访问）
if ($_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 获取用户ID
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    setFlashMessage('error', '无效的用户ID');
    redirect(BASE_URL . '/users/index.php');
}

// 不能修改自己的状态
if ($userId == $_SESSION['user_id']) {
    setFlashMessage('error', '不能修改自己的账户状态');
    redirect(BASE_URL . '/users/index.php');
}

// 获取用户详情
$sql = "SELECT * FROM users WHERE id = $userId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '用户不存在');
    redirect(BASE_URL . '/users/index.php');
}

$user = $result->fetch_assoc();

// 切换状态
$newStatus = $user['status'] == 'active' ? 'inactive' : 'active';
$sql = "UPDATE users SET status = '$newStatus' WHERE id = $userId";
execute($sql);

$message = $newStatus == 'active' ? '用户已成功激活' : '用户已成功停用';
setFlashMessage('success', $message);
redirect(BASE_URL . '/users/index.php');
