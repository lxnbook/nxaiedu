<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取筛选参数
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// 构建查询条件
$conditions = [];
$userId = $_SESSION['user_id'];

// 普通用户只能看到自己的汇报
if ($_SESSION['user_role'] == 'staff' || $_SESSION['user_role'] == 'teacher') {
    $conditions[] = "r.user_id = $userId";
}

if (!empty($status)) {
    $conditions[] = "r.status = '$status'";
}

if (!empty($type)) {
    $conditions[] = "r.type = '$type'";
}

if (!empty($search)) {
    $conditions[] = "(r.title LIKE '%$search%' OR r.content LIKE '%$search%')";
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 获取汇报列表
$sql = "SELECT r.*, u.name as author_name, u.department 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        $whereClause 
        ORDER BY r.updated_at DESC";
$reports = query($sql);

// 获取汇报类型列表
$reportTypes = getReportTypes();

$pageTitle = '项目汇报';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 教育局项目汇报系统</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- 侧边导航 -->
        <?php include_once '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- 页面头部 -->
            <?php include_once '../includes/header.php'; ?>
            
            <!-- 页面内容 -->
            <div class="page-content">
                <?php if ($flashMessage = getFlashMessage()): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                        <?php echo $flashMessage['message']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 操作栏 -->
                <div class="action-bar">
                    <a href="<?php echo BASE_URL; ?>/reports/create.php" class="btn-primary">
                        <i class="icon-plus"></i> 新建汇报
                    </a>
                    
                    <!-- 筛选表单 -->
                    <form class="filter-form" method="get" action="">
                        <div class="form-group">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">所有状态</option>
                                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="submitted" <?php echo $status == 'submitted' ? 'selected' : ''; ?>>已提交</option>
                                <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>已批准</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>已退回</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="type" onchange="this.form.submit()">
                                <option value="">所有类型</option>
                                <?php foreach ($reportTypes as $reportType): ?>
                                    <option value="<?php echo $reportType; ?>" <?php echo $type == $reportType ? 'selected' : ''; ?>>
                                        <?php echo $reportType; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group search-group">
                            <input type="text" name="search" placeholder="搜索标题或内容" value="<?php echo $search; ?>">
                            <button type="submit" class="btn-icon"><i class="icon-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <!-- 汇报列表 -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($reports->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>标题</th>
                                        <th>类型</th>
                                        <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager'): ?>
                                            <th>提交人</th>
                                            <th>部门</th>
                                        <?php endif; ?>
                                        <th>更新日期</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($report = $reports->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $report['title']; ?></td>
                                            <td><?php echo $report['type']; ?></td>
                                            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager'): ?>
                                                <td><?php echo $report['author_name']; ?></td>
                                                <td><?php echo $report['department']; ?></td>
                                            <?php endif; ?>
                                            <td><?php echo date('Y-m-d', strtotime($report['updated_at'])); ?></td>
                                            <td>
                                                <span class="status <?php echo $report['status']; ?>">
                                                    <?php 
                                                    $statusLabels = [
                                                        'draft' => '草稿',
                                                        'submitted' => '已提交',
                                                        'approved' => '已批准',
                                                        'rejected' => '已退回'
                                                    ];
                                                    echo $statusLabels[$report['status']];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo BASE_URL; ?>/reports/view.php?id=<?php echo $report['id']; ?>" class="btn-icon" title="查看"><i class="icon-view"></i></a>
                                                    <?php if ($report['status'] == 'draft' || $report['status'] == 'rejected'): ?>
                                                        <a href="<?php echo BASE_URL; ?>/reports/edit.php?id=<?php echo $report['id']; ?>" class="btn-icon" title="编辑"><i class="icon-edit"></i></a>
                                                    <?php endif; ?>
                                                    <?php if ($report['status'] == 'draft'): ?>
                                                        <a href="<?php echo BASE_URL; ?>/reports/delete.php?id=<?php echo $report['id']; ?>" class="btn-icon" title="删除" onclick="return confirm('确定要删除此汇报吗？')"><i class="icon-delete"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">暂无汇报数据</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
