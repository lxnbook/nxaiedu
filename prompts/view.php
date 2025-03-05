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
$sql = "SELECT p.*, u.name as creator_name 
        FROM prompts p 
        LEFT JOIN users u ON p.created_by = u.id 
        WHERE p.id = $promptId AND (p.is_public = 1 OR p.created_by = $userId)";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '提示词不存在或您没有权限查看');
    redirect(BASE_URL . '/prompts/index.php');
}

$prompt = $result->fetch_assoc();

$pageTitle = '查看提示词';
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
                    <a href="<?php echo BASE_URL; ?>/prompts/index.php" class="btn-secondary">
                        <i class="icon-back"></i> 返回列表
                    </a>
                    
                    <?php if ($prompt['created_by'] == $userId): ?>
                        <a href="<?php echo BASE_URL; ?>/prompts/edit.php?id=<?php echo $prompt['id']; ?>" class="btn-primary">
                            <i class="icon-edit"></i> 编辑提示词
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $prompt['title']; ?></h2>
                        <div class="prompt-meta-header">
                            <span class="prompt-category"><?php echo $prompt['category']; ?></span>
                            <?php if (!empty($prompt['subject'])): ?>
                                <span class="prompt-subject"><?php echo $prompt['subject']; ?></span>
                            <?php endif; ?>
                            <span class="prompt-visibility"><?php echo $prompt['is_public'] ? '公开' : '私有'; ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="prompt-meta">
                            <div class="meta-item">
                                <span class="meta-label">创建者：</span>
                                <span class="meta-value"><?php echo $prompt['creator_name'] ?: '系统'; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">创建时间：</span>
                                <span class="meta-value"><?php echo date('Y-m-d H:i', strtotime($prompt['created_at'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">更新时间：</span>
                                <span class="meta-value"><?php echo date('Y-m-d H:i', strtotime($prompt['updated_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="prompt-content">
                            <?php echo nl2br($prompt['content']); ?>
                        </div>
                        
                        <div class="prompt-actions">
                            <button class="btn-primary" id="copyPrompt">
                                <i class="icon-copy"></i> 复制内容
                            </button>
                            <a href="<?php echo BASE_URL; ?>/reports/create.php?prompt_id=<?php echo $prompt['id']; ?>" class="btn-success">
                                <i class="icon-report"></i> 使用此模板创建汇报
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 复制提示词内容
            const copyBtn = document.getElementById('copyPrompt');
            const promptContent = document.querySelector('.prompt-content').innerText;
            
            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(promptContent)
                    .then(() => {
                        // 显示复制成功提示
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.textContent = '内容已复制到剪贴板';
                        document.querySelector('.page-content').prepend(alert);
                        
                        // 5秒后自动关闭
                        setTimeout(() => {
                            alert.style.opacity = '0';
                            setTimeout(() => {
                                alert.remove();
                            }, 300);
                        }, 3000);
                    })
                    .catch(err => {
                        console.error('复制失败:', err);
                    });
            });
        });
    </script>
</body>
</html>
