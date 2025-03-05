<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员和经理可以访问）
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 获取筛选参数
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// 获取部门列表
$sql = "SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department";
$departments = query($sql);

// 根据时间段获取日期范围
if ($period == 'week') {
    $startDate = date('Y-m-d', strtotime('monday this week'));
    $endDate = date('Y-m-d', strtotime('sunday this week'));
} elseif ($period == 'month') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($period == 'quarter') {
    $quarter = ceil(date('n') / 3);
    $startDate = date('Y-' . (($quarter - 1) * 3 + 1) . '-01');
    $endDate = date('Y-m-t', strtotime($startDate . ' +2 month'));
} elseif ($period == 'year') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
}

// 构建查询条件
$conditions = [];
$conditions[] = "r.created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";

if (!empty($department)) {
    $conditions[] = "u.department = '$department'";
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 获取汇报统计数据
$sql = "SELECT 
        COUNT(*) as total_reports,
        SUM(CASE WHEN r.status = 'draft' THEN 1 ELSE 0 END) as draft_reports,
        SUM(CASE WHEN r.status = 'submitted' THEN 1 ELSE 0 END) as submitted_reports,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reports,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_reports
        FROM reports r
        JOIN users u ON r.user_id = u.id
        $whereClause";
$reportStats = query($sql)->fetch_assoc();

// 获取部门汇报统计
$sql = "SELECT 
        u.department,
        COUNT(*) as total_reports,
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_reports
        FROM reports r
        JOIN users u ON r.user_id = u.id
        $whereClause
        GROUP BY u.department
        ORDER BY total_reports DESC";
$departmentStats = query($sql);

// 获取汇报类型统计
$sql = "SELECT 
        r.type,
        COUNT(*) as total_reports
        FROM reports r
        JOIN users u ON r.user_id = u.id
        $whereClause
        GROUP BY r.type
        ORDER BY total_reports DESC";
$typeStats = query($sql);

// 获取活跃用户统计
$sql = "SELECT 
        u.name,
        u.department,
        COUNT(*) as total_reports
        FROM reports r
        JOIN users u ON r.user_id = u.id
        $whereClause
        GROUP BY r.user_id
        ORDER BY total_reports DESC
        LIMIT 10";
$userStats = query($sql);

// 获取每日汇报提交趋势
$sql = "SELECT 
        DATE(r.created_at) as report_date,
        COUNT(*) as total_reports
        FROM reports r
        JOIN users u ON r.user_id = u.id
        $whereClause
        GROUP BY DATE(r.created_at)
        ORDER BY report_date";
$dailyStats = query($sql);

// 准备图表数据
$chartLabels = [];
$chartData = [];

while ($daily = $dailyStats->fetch_assoc()) {
    $chartLabels[] = date('m-d', strtotime($daily['report_date']));
    $chartData[] = $daily['total_reports'];
}

$pageTitle = '统计分析';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 教育局项目汇报系统</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <!-- 筛选表单 -->
                <div class="filter-bar">
                    <form method="get" action="" class="filter-form">
                        <div class="form-group">
                            <select name="period" onchange="this.form.submit()">
                                <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>本周</option>
                                <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>本月</option>
                                <option value="quarter" <?php echo $period == 'quarter' ? 'selected' : ''; ?>>本季度</option>
                                <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>本年</option>
                                <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>自定义</option>
                            </select>
                        </div>
                        
                        <?php if ($period == 'custom'): ?>
                            <div class="form-group">
                                <input type="date" name="start_date" value="<?php echo $startDate; ?>" onchange="this.form.submit()">
                            </div>
                            <div class="form-group">
                                <input type="date" name="end_date" value="<?php echo $endDate; ?>" onchange="this.form.submit()">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <select name="department" onchange="this.form.submit()">
                                <option value="">所有部门</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['department']; ?>" <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo $dept['department']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <!-- 统计卡片 -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $reportStats['total_reports']; ?></div>
                        <div class="stat-card-label">汇报总数</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $reportStats['submitted_reports']; ?></div>
                        <div class="stat-card-label">已提交</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $reportStats['approved_reports']; ?></div>
                        <div class="stat-card-label">已批准</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $reportStats['rejected_reports']; ?></div>
                        <div class="stat-card-label">已退回</div>
                    </div>
                </div>
                
                <!-- 图表和统计表格 -->
                <div class="analytics-grid">
                    <!-- 趋势图表 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>汇报提交趋势</h2>
                        </div>
                        <div class="card-body">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- 部门统计 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>部门汇报统计</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($departmentStats->num_rows > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>部门</th>
                                            <th>汇报总数</th>
                                            <th>已批准</th>
                                            <th>批准率</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($dept = $departmentStats->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $dept['department']; ?></td>
                                                <td><?php echo $dept['total_reports']; ?></td>
                                                <td><?php echo $dept['approved_reports']; ?></td>
                                                <td>
                                                    <?php 
                                                    $approvalRate = $dept['total_reports'] > 0 ? 
                                                        round(($dept['approved_reports'] / $dept['total_reports']) * 100, 1) : 0;
                                                    echo $approvalRate . '%'; 
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-data">暂无数据</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 汇报类型统计 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>汇报类型统计</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($typeStats->num_rows > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>汇报类型</th>
                                            <th>数量</th>
                                            <th>占比</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($type = $typeStats->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $type['type']; ?></td>
                                                <td><?php echo $type['total_reports']; ?></td>
                                                <td>
                                                    <?php 
                                                    $percentage = $reportStats['total_reports'] > 0 ? 
                                                        round(($type['total_reports'] / $reportStats['total_reports']) * 100, 1) : 0;
                                                    echo $percentage . '%'; 
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-data">暂无数据</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 活跃用户统计 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>活跃用户排行</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($userStats->num_rows > 0): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>用户</th>
                                            <th>部门</th>
                                            <th>汇报数量</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $userStats->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $user['name']; ?></td>
                                                <td><?php echo $user['department']; ?></td>
                                                <td><?php echo $user['total_reports']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-data">暂无数据</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 绘制趋势图表
            const ctx = document.getElementById('trendChart').getContext('2d');
            const trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: '汇报数量',
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
