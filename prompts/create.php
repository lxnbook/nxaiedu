<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = escapeString($_POST['title']);
    $content = escapeString($_POST['content']);
    $category = escapeString($_POST['category']);
    $subject = escapeString($_POST['subject']);
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $userId = $_SESSION['user_id'];
    
    // 插入提示词记录
    $sql = "INSERT INTO prompts (title, content, category, subject, created_by, is_public, created_at, updated_at) 
            VALUES ('$title', '$content', '$category', '$subject', $userId, $isPublic, NOW(), NOW())";
    $promptId = insert($sql);
    
    if ($promptId) {
        setFlashMessage('success', '提示词已成功创建！');
        redirect(BASE_URL . '/prompts/index.php');
    } else {
        $error = '创建提示词失败，请重试';
    }
}

// 获取提示词分类列表
$sql = "SELECT DISTINCT category FROM prompts ORDER BY category";
$categories = query($sql);

$pageTitle = '新建提示词';
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
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>新建提示词</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">分类</label>
                                <input type="text" id="category" name="category" list="categories" required>
                                <datalist id="categories">
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['category']; ?>">
                                    <?php endwhile; ?>
                                </datalist>
                                <small class="form-text">可以选择现有分类或输入新分类</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">主题/学科</label>
                                <input type="text" id="subject" name="subject">
                                <small class="form-text">可选，用于进一步分类</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">内容</label>
                                <textarea id="content" name="content" rows="15" required></textarea>
                                <small class="form-text">支持Markdown格式</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_public" value="1">
                                    公开此提示词（所有用户可见）
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <a href="<?php echo BASE_URL; ?>/prompts/index.php" class="btn-secondary">取消</a>
                                <button type="submit" class="btn-primary">保存提示词</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
