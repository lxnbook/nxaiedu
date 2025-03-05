<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取提示词ID
$promptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$promptId) {
    echo json_encode(['success' => false, 'message' => '无效的提示词ID']);
    exit;
}

// 获取提示词详情
$sql = "SELECT * FROM prompts WHERE id = $promptId AND (is_public = 1 OR created_by = {$_SESSION['user_id']})";
$result = query($sql);

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '提示词不存在或您没有权限访问']);
    exit;
}

$prompt = $result->fetch_assoc();

// 返回提示词内容
echo json_encode([
    'success' => true,
    'id' => $prompt['id'],
    'title' => $prompt['title'],
    'content' => $prompt['content'],
    'category' => $prompt['category']
]);
