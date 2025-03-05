<?php
require_once 'config/config.php';

// 需要登录才能访问
requireLogin();

// 获取用户信息
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $userId";
$result = query($sql);
$user = $result->fetch_assoc();

// 获取汇报统计
$sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM reports 
        WHERE user_id = $userId";
$result = query($sql);
$stats = $result->fetch_assoc();

// 获取最近的汇报
$sql = "SELECT * FROM reports WHERE user_id = $userId ORDER BY updated_at DESC LIMIT 5";
$recentReports = query($sql);

// 获取待审核的汇报（仅管理员和经理可见）
$pendingReports = null;
if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager') {
    $sql = "SELECT r.*, u.name as author_name, u.department 
            FROM reports r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.status = 'submitted' 
            ORDER BY r.submitted_at ASC 
            LIMIT 5";
    $pendingReports = query($sql);
}

// 获取系统公告
$sql = "SELECT setting_value FROM settings WHERE setting_key = 'system_announcement'";
$result = query($sql);
$announcement = $result->fetch_assoc()['setting_value'];

$pageTitle = '仪表盘';
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
        <?php include_once 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- 页面头部 -->
            <?php include_once 'includes/header.php'; ?>
            
            <!-- 页面内容 -->
            <div class="page-content">
                <?php if ($flashMessage = getFlashMessage()): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                        <?php echo $flashMessage['message']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- 系统公告 -->
                <?php if (!empty($announcement)): ?>
                <div class="announcement">
                    <h3><i class="icon-announcement"></i> 系统公告</h3>
                    <p><?php echo $announcement; ?></p>
                </div>
                <?php endif; ?>
                
                <!-- 统计卡片 -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">汇报总数</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['draft']; ?></div>
                        <div class="stat-label">草稿</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['submitted']; ?></div>
                        <div class="stat-label">已提交</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">已批准</div>
                    </div>
                </div>
                
                <!-- 最近的汇报 -->
                <section class="card">
                    <div class="card-header">
                        <h2>最近的汇报</h2>
                        <a href="<?php echo BASE_URL; ?>/reports/index.php" class="btn-link">查看全部</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recentReports->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>标题</th>
                                        <th>类型</th>
                                        <th>更新日期</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($report = $recentReports->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $report['title']; ?></td>
                                            <td><?php echo $report['type']; ?></td>
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
                </section>
                
                <!-- 待审核的汇报（仅管理员和经理可见） -->
                <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager'): ?>
                <section class="card">
                    <div class="card-header">
                        <h2>待审核的汇报</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($pendingReports && $pendingReports->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>标题</th>
                                        <th>提交人</th>
                                        <th>部门</th>
                                        <th>提交日期</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($report = $pendingReports->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $report['title']; ?></td>
                                            <td><?php echo $report['author_name']; ?></td>
                                            <td><?php echo $report['department']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($report['submitted_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo BASE_URL; ?>/reports/view.php?id=<?php echo $report['id']; ?>" class="btn-icon" title="查看"><i class="icon-view"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">暂无待审核的汇报</p>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
