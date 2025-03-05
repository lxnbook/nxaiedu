<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员和经理可以导出）
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    setFlashMessage('error', '您没有权限导出汇报');
    redirect(BASE_URL . '/reports/index.php');
}

// 获取筛选参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// 构建查询条件
$conditions = [];

if (!empty($status)) {
    $conditions[] = "r.status = '$status'";
}

if (!empty($type)) {
    $conditions[] = "r.type = '$type'";
}

if (!empty($department)) {
    $conditions[] = "u.department = '$department'";
}

if (!empty($startDate)) {
    $conditions[] = "r.created_at >= '$startDate 00:00:00'";
}

if (!empty($endDate)) {
    $conditions[] = "r.created_at <= '$endDate 23:59:59'";
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 获取汇报数据
$sql = "SELECT 
        r.id, r.title, r.type, r.content, r.status, 
        r.created_at, r.updated_at, r.submitted_at, r.reviewed_at,
        u.name as author_name, u.department,
        rv.name as reviewer_name,
        r.review_comment
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users rv ON r.reviewed_by = rv.id 
        $whereClause
        ORDER BY r.created_at DESC";
$reports = query($sql);

// 设置文件名
$filename = 'reports_export_' . date('Y-m-d') . '.' . $format;

// 导出为CSV
if ($format == 'csv') {
    // 设置响应头
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // 创建输出流
    $output = fopen('php://output', 'w');
    
    // 添加BOM以支持中文
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // 写入表头
    fputcsv($output, [
        'ID', '标题', '类型', '作者', '部门', '状态', 
        '创建时间', '提交时间', '审核时间', '审核人', '审核意见'
    ]);
    
    // 写入数据
    while ($report = $reports->fetch_assoc()) {
        $status = [
            'draft' => '草稿',
            'submitted' => '已提交',
            'approved' => '已批准',
            'rejected' => '已退回'
        ][$report['status']];
        
        fputcsv($output, [
            $report['id'],
            $report['title'],
            $report['type'],
            $report['author_name'],
            $report['department'],
            $status,
            $report['created_at'],
            $report['submitted_at'],
            $report['reviewed_at'],
            $report['reviewer_name'],
            $report['review_comment']
        ]);
    }
    
    fclose($output);
    exit;
}
// 导出为Excel (简化版，实际应用可能需要使用PHPExcel等库)
elseif ($format == 'excel') {
    // 设置响应头
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>标题</th>
            <th>类型</th>
            <th>作者</th>
            <th>部门</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>提交时间</th>
            <th>审核时间</th>
            <th>审核人</th>
            <th>审核意见</th>
          </tr>";
    
    while ($report = $reports->fetch_assoc()) {
        $status = [
            'draft' => '草稿',
            'submitted' => '已提交',
            'approved' => '已批准',
            'rejected' => '已退回'
        ][$report['status']];
        
        echo "<tr>
                <td>{$report['id']}</td>
                <td>{$report['title']}</td>
                <td>{$report['type']}</td>
                <td>{$report['author_name']}</td>
                <td>{$report['department']}</td>
                <td>{$status}</td>
                <td>{$report['created_at']}</td>
                <td>{$report['submitted_at']}</td>
                <td>{$report['reviewed_at']}</td>
                <td>{$report['reviewer_name']}</td>
                <td>{$report['review_comment']}</td>
              </tr>";
    }
    
    echo "</table>";
    exit;
}
// 导出为PDF (需要安装额外的PDF库，这里只是示例)
elseif ($format == 'pdf') {
    // 在实际应用中，您需要使用TCPDF、FPDF或其他PDF库
    // 这里只是重定向回列表页面并显示一个消息
    setFlashMessage('error', 'PDF导出功能需要安装额外的库，暂未实现');
    redirect(BASE_URL . '/reports/index.php');
}
// 不支持的格式
else {
    setFlashMessage('error', '不支持的导出格式');
    redirect(BASE_URL . '/reports/index.php');
}
