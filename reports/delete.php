<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取汇报ID
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reportId) {
    setFlashMessage('error', '无效的汇报ID');
    redirect(BASE_URL . '/reports/index.php');
}

// 获取汇报详情
$sql = "SELECT * FROM reports WHERE id = $reportId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '汇报不存在');
    redirect(BASE_URL . '/reports/index.php');
}

$report = $result->fetch_assoc();

// 检查权限（只有汇报的创建者可以删除，且只能删除草稿）
if ($report['user_id'] != $_SESSION['user_id']) {
    setFlashMessage('error', '您没有权限删除此汇报');
    redirect(BASE_URL . '/reports/index.php');
}

if ($report['status'] != 'draft') {
    setFlashMessage('error', '只能删除草稿状态的汇报');
    redirect(BASE_URL . '/reports/index.php');
}

// 获取附件列表
$sql = "SELECT * FROM attachments WHERE report_id = $reportId";
$attachments = query($sql);

// 删除附件文件
while ($attachment = $attachments->fetch_assoc()) {
    $filePath = UPLOAD_DIR . 'reports/' . $attachment['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// 删除附件记录
$sql = "DELETE FROM attachments WHERE report_id = $reportId";
execute($sql);

// 删除汇报记录
$sql = "DELETE FROM reports WHERE id = $reportId";
execute($sql);

setFlashMessage('success', '汇报已成功删除');
redirect(BASE_URL . '/reports/index.php');
