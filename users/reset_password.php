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

// 不能重置自己的密码
if ($userId == $_SESSION['user_id']) {
    setFlashMessage('error', '不能通过此方式重置自己的密码，请使用个人资料页面');
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

// 生成新密码
$newPassword = generateRandomPassword();
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// 更新密码
$sql = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
execute($sql);

setFlashMessage('success', "用户 {$user['name']} 的密码已重置为: $newPassword");
redirect(BASE_URL . '/users/index.php');

// 生成随机密码
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}
