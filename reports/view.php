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
$sql = "SELECT r.*, u.name as author_name, u.department, 
        rv.name as reviewer_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users rv ON r.reviewed_by = rv.id 
        WHERE r.id = $reportId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '汇报不存在');
    redirect(BASE_URL . '/reports/index.php');
}

$report = $result->fetch_assoc();

// 检查权限（只有管理员、经理或汇报的创建者可以查看）
if ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'manager' && $report['user_id'] != $_SESSION['user_id']) {
    setFlashMessage('error', '您没有权限查看此汇报');
    redirect(BASE_URL . '/reports/index.php');
}

// 获取附件列表
$sql = "SELECT * FROM attachments WHERE report_id = $reportId";
$attachments = query($sql);

// 处理审核操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager')) {
    $action = $_POST['action'];
    $reviewComment = escapeString($_POST['review_comment']);
    $reviewerId = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        $status = 'approved';
        $message = '汇报已批准';
    } else {
        $status = 'rejected';
        $message = '汇报已退回';
    }
    
    $sql = "UPDATE reports SET 
            status = '$status', 
            review_comment = '$reviewComment', 
            reviewed_by = $reviewerId, 
            reviewed_at = NOW() 
            WHERE id = $reportId";
    execute($sql);
    
    setFlashMessage('success', $message);
    redirect(BASE_URL . '/reports/view.php?id=' . $reportId);
}

$pageTitle = '查看汇报';
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
                
                <div class="action-bar">
                    <a href="<?php echo BASE_URL; ?>/reports/index.php" class="btn-secondary">
                        <i class="icon-back"></i> 返回列表
                    </a>
                    
                    <?php if ($report['status'] == 'draft' || $report['status'] == 'rejected'): ?>
                        <?php if ($report['user_id'] == $_SESSION['user_id']): ?>
                            <a href="<?php echo BASE_URL; ?>/reports/edit.php?id=<?php echo $reportId; ?>" class="btn-primary">
                                <i class="icon-edit"></i> 编辑汇报
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $report['title']; ?></h2>
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
                    </div>
                    <div class="card-body">
                        <div class="report-meta">
                            <div class="meta-item">
                                <span class="meta-label">汇报类型：</span>
                                <span class="meta-value"><?php echo $report['type']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">提交人：</span>
                                <span class="meta-value"><?php echo $report['author_name']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">部门：</span>
                                <span class="meta-value"><?php echo $report['department']; ?></span>
                            </div>
                            <?php if ($report['status'] == 'submitted'): ?>
                                <div class="meta-item">
                                    <span class="meta-label">提交时间：</span>
                                    <span class="meta-value"><?php echo date('Y-m-d H:i', strtotime($report['submitted_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($report['status'] == 'approved' || $report['status'] == 'rejected'): ?>
                                <div class="meta-item">
                                    <span class="meta-label">审核人：</span>
                                    <span class="meta-value"><?php echo $report['reviewer_name']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">审核时间：</span>
                                    <span class="meta-value"><?php echo date('Y-m-d H:i', strtotime($report['reviewed_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="report-content">
                            <?php echo nl2br($report['content']); ?>
                        </div>
                        
                        <?php if ($attachments->num_rows > 0): ?>
                            <div class="report-attachments">
                                <h3>附件</h3>
                                <ul class="attachment-list">
                                    <?php while ($attachment = $attachments->fetch_assoc()): ?>
                                        <li>
                                            <a href="<?php echo BASE_URL; ?>/uploads/reports/<?php echo $attachment['filename']; ?>" target="_blank">
                                                <?php echo $attachment['original_name']; ?>
                                            </a>
                                            <span class="file-size">(<?php echo formatFileSize($attachment['file_size']); ?>)</span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report['status'] == 'approved' || $report['status'] == 'rejected'): ?>
                            <div class="review-comment">
                                <h3>审核意见</h3>
                                <p><?php echo nl2br($report['review_comment']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report['status'] == 'submitted' && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager')): ?>
                            <div class="review-form">
                                <h3>审核操作</h3>
                                <form method="post" action="">
                                    <div class="form-group">
                                        <label for="review_comment">审核意见</label>
                                        <textarea id="review_comment" name="review_comment" rows="5" required></textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" name="action" value="reject" class="btn-danger">退回修改</button>
                                        <button type="submit" name="action" value="approve" class="btn-success">批准通过</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
