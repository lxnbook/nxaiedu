<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取提示词ID
$promptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$promptId) {
    setFlashMessage('error', '无效的提示词ID');
    redirect(BASE_URL . '/prompts/index.php');
}

// 获取提示词详情
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM prompts WHERE id = $promptId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '提示词不存在');
    redirect(BASE_URL . '/prompts/index.php');
}

$prompt = $result->fetch_assoc();

// 检查权限（只有提示词的创建者可以删除）
if ($prompt['created_by'] != $userId) {
    setFlashMessage('error', '您没有权限删除此提示词');
    redirect(BASE_URL . '/prompts/index.php');
}

// 删除提示词记录
$sql = "DELETE FROM prompts WHERE id = $promptId";
execute($sql);

setFlashMessage('success', '提示词已成功删除');
redirect(BASE_URL . '/prompts/index.php');
