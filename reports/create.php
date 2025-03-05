<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = escapeString($_POST['title']);
    $type = escapeString($_POST['type']);
    $content = escapeString($_POST['content']);
    $userId = $_SESSION['user_id'];
    $status = isset($_POST['submit']) ? 'submitted' : 'draft';
    $submittedAt = $status == 'submitted' ? 'NOW()' : 'NULL';
    
    // 插入汇报记录
    $sql = "INSERT INTO reports (user_id, title, type, content, status, submitted_at, created_at, updated_at) 
            VALUES ($userId, '$title', '$type', '$content', '$status', $submittedAt, NOW(), NOW())";
    $reportId = insert($sql);
    
    // 处理附件上传
    if ($reportId && !empty($_FILES['attachments']['name'][0])) {
        $uploadDir = UPLOAD_DIR . 'reports/';
        
        // 确保上传目录存在
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileCount = count($_FILES['attachments']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['attachments']['tmp_name'][$i];
                $originalName = $_FILES['attachments']['name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $fileType = $_FILES['attachments']['type'][$i];
                
                // 生成唯一文件名
                $filename = uniqid() . '_' . $originalName;
                $destination = $uploadDir . $filename;
                
                if (move_uploaded_file($tmpName, $destination)) {
                    // 插入附件记录
                    $originalName = escapeString($originalName);
                    $filename = escapeString($filename);
                    $sql = "INSERT INTO attachments (report_id, filename, original_name, file_size, file_type, uploaded_at) 
                            VALUES ($reportId, '$filename', '$originalName', $fileSize, '$fileType', NOW())";
                    insert($sql);
                }
            }
        }
    }
    
    // 设置成功消息并重定向
    if ($status == 'submitted') {
        setFlashMessage('success', '汇报已成功提交！');
    } else {
        setFlashMessage('success', '汇报已保存为草稿！');
    }
    redirect(BASE_URL . '/reports/index.php');
}

// 获取汇报类型列表
$reportTypes = getReportTypes();

$pageTitle = '新建汇报';
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
                        <h2>新建汇报</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">类型</label>
                                <select id="type" name="type" required>
                                    <option value="">请选择汇报类型</option>
                                    <?php foreach ($reportTypes as $reportType): ?>
                                        <option value="<?php echo $reportType; ?>"><?php echo $reportType; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">内容</label>
                                <textarea id="content" name="content" rows="15" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachments">附件</label>
                                <input type="file" id="attachments" name="attachments[]" multiple>
                                <small class="form-text">支持多个文件，单个文件大小不超过10MB</small>
                            </div>
                            
                            <div class="form-group">
                                <label>提示词库</label>
                                <div class="prompt-selector">
                                    <select id="promptSelect">
                                        <option value="">选择提示词模板</option>
                                        <?php
                                        $sql = "SELECT id, title, category FROM prompts WHERE is_public = 1 OR created_by = {$_SESSION['user_id']} ORDER BY category, title";
                                        $prompts = query($sql);
                                        while ($prompt = $prompts->fetch_assoc()) {
                                            echo "<option value=\"{$prompt['id']}\">{$prompt['title']} ({$prompt['category']})</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="button" id="insertPrompt" class="btn-secondary">插入模板</button>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="draft" class="btn-secondary">保存草稿</button>
                                <button type="submit" name="submit" class="btn-primary">提交汇报</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 插入提示词模板
            const promptSelect = document.getElementById('promptSelect');
            const insertPromptBtn = document.getElementById('insertPrompt');
            const contentTextarea = document.getElementById('content');
            
            insertPromptBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const promptId = promptSelect.value;
                
                if (promptId) {
                    fetch(`<?php echo BASE_URL; ?>/prompts/get_prompt.php?id=${promptId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                contentTextarea.value = data.content;
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    </script>
</body>
</html>
