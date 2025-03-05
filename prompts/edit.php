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

// 检查权限（只有提示词的创建者可以编辑）
if ($prompt['created_by'] != $userId) {
    setFlashMessage('error', '您没有权限编辑此提示词');
    redirect(BASE_URL . '/prompts/index.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = escapeString($_POST['title']);
    $content = escapeString($_POST['content']);
    $category = escapeString($_POST['category']);
    $subject = escapeString($_POST['subject']);
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    
    // 更新提示词记录
    $sql = "UPDATE prompts SET 
            title = '$title', 
            content = '$content', 
            category = '$category', 
            subject = '$subject', 
            is_public = $isPublic, 
            updated_at = NOW() 
            WHERE id = $promptId";
    execute($sql);
    
    setFlashMessage('success', '提示词已成功更新！');
    redirect(BASE_URL . '/prompts/view.php?id=' . $promptId);
}

// 获取提示词分类列表
$sql = "SELECT DISTINCT category FROM prompts ORDER BY category";
$categories = query($sql);

$pageTitle = '编辑提示词';
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
                <div class="card">
                    <div class="card-header">
                        <h2>编辑提示词</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" id="title" name="title" value="<?php echo $prompt['title']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">分类</label>
                                <input type="text" id="category" name="category" list="categories" value="<?php echo $prompt['category']; ?>" required>
                                <datalist id="categories">
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['category']; ?>">
                                    <?php endwhile; ?>
                                </datalist>
                                <small class="form-text">可以选择现有分类或输入新分类</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">主题/学科</label>
                                <input type="text" id="subject" name="subject" value="<?php echo $prompt['subject']; ?>">
                                <small class="form-text">可选，用于进一步分类</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">内容</label>
                                <textarea id="content" name="content" rows="15" required><?php echo $prompt['content']; ?></textarea>
                                <small class="form-text">支持Markdown格式</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_public" value="1" <?php echo $prompt['is_public'] ? 'checked' : ''; ?>>
                                    公开此提示词（所有用户可见）
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <a href="<?php echo BASE_URL; ?>/prompts/view.php?id=<?php echo $promptId; ?>" class="btn-secondary">取消</a>
                                <button type="submit" class="btn-primary">保存更改</button>
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
