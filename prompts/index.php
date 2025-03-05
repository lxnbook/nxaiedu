<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取筛选参数
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// 构建查询条件
$conditions = [];
$userId = $_SESSION['user_id'];

// 用户只能看到公开的提示词和自己创建的提示词
$conditions[] = "(is_public = 1 OR created_by = $userId)";

if (!empty($category)) {
    $conditions[] = "category = '$category'";
}

if (!empty($search)) {
    $conditions[] = "(title LIKE '%$search%' OR content LIKE '%$search%')";
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 获取提示词列表
$sql = "SELECT p.*, u.name as creator_name 
        FROM prompts p 
        LEFT JOIN users u ON p.created_by = u.id 
        $whereClause 
        ORDER BY p.category, p.title";
$prompts = query($sql);

// 获取提示词分类列表
$sql = "SELECT DISTINCT category FROM prompts WHERE is_public = 1 OR created_by = $userId ORDER BY category";
$categories = query($sql);

$pageTitle = '提示词库';
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
                    <a href="<?php echo BASE_URL; ?>/prompts/create.php" class="btn-primary">
                        <i class="icon-plus"></i> 新建提示词
                    </a>
                    
                    <!-- 筛选表单 -->
                    <form class="filter-form" method="get" action="">
                        <div class="form-group">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">所有分类</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo $cat['category']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group search-group">
                            <input type="text" name="search" placeholder="搜索标题或内容" value="<?php echo $search; ?>">
                            <button type="submit" class="btn-icon"><i class="icon-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <!-- 提示词列表 -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($prompts->num_rows > 0): ?>
                            <div class="prompt-grid">
                                <?php while ($prompt = $prompts->fetch_assoc()): ?>
                                    <div class="prompt-card">
                                        <div class="prompt-header">
                                            <h3><?php echo $prompt['title']; ?></h3>
                                            <span class="prompt-category"><?php echo $prompt['category']; ?></span>
                                        </div>
                                        <div class="prompt-preview">
                                            <?php 
                                            $preview = substr(strip_tags($prompt['content']), 0, 150);
                                            echo $preview . (strlen($prompt['content']) > 150 ? '...' : '');
                                            ?>
                                        </div>
                                        <div class="prompt-footer">
                                            <div class="prompt-meta">
                                                <span class="prompt-creator">
                                                    <?php echo $prompt['is_public'] ? '公开' : '私有'; ?> | 
                                                    <?php echo $prompt['creator_name'] ?: '系统'; ?>
                                                </span>
                                                <span class="prompt-date">
                                                    <?php echo date('Y-m-d', strtotime($prompt['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="prompt-actions">
                                                <a href="<?php echo BASE_URL; ?>/prompts/view.php?id=<?php echo $prompt['id']; ?>" class="btn-icon" title="查看"><i class="icon-view"></i></a>
                                                <?php if ($prompt['created_by'] == $userId): ?>
                                                    <a href="<?php echo BASE_URL; ?>/prompts/edit.php?id=<?php echo $prompt['id']; ?>" class="btn-icon" title="编辑"><i class="icon-edit"></i></a>
                                                    <a href="<?php echo BASE_URL; ?>/prompts/delete.php?id=<?php echo $prompt['id']; ?>" class="btn-icon" title="删除" onclick="return confirm('确定要删除此提示词吗？')"><i class="icon-delete"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">暂无提示词数据</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
