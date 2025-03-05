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
$sql = "SELECT * FROM reports WHERE id = $reportId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '汇报不存在');
    redirect(BASE_URL . '/reports/index.php');
}

$report = $result->fetch_assoc();

// 检查权限（只有汇报的创建者可以编辑，且只能编辑草稿或被退回的汇报）
if ($report['user_id'] != $_SESSION['user_id']) {
    setFlashMessage('error', '您没有权限编辑此汇报');
    redirect(BASE_URL . '/reports/index.php');
}

if ($report['status'] != 'draft' && $report['status'] != 'rejected') {
    setFlashMessage('error', '只能编辑草稿或被退回的汇报');
    redirect(BASE_URL . '/reports/view.php?id=' . $reportId);
}

// 获取附件列表
$sql = "SELECT * FROM attachments WHERE report_id = $reportId";
$attachments = query($sql);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = escapeString($_POST['title']);
    $type = escapeString($_POST['type']);
    $content = escapeString($_POST['content']);
    $status = isset($_POST['submit']) ? 'submitted' : 'draft';
    $submittedAt = $status == 'submitted' ? 'NOW()' : 'NULL';
    
    // 更新汇报记录
    $sql = "UPDATE reports SET 
            title = '$title', 
            type = '$type', 
            content = '$content', 
            status = '$status', 
            submitted_at = $submittedAt, 
            updated_at = NOW() 
            WHERE id = $reportId";
    execute($sql);
    
    // 处理附件上传
    if (!empty($_FILES['attachments']['name'][0])) {
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
    
    // 处理附件删除
    if (isset($_POST['delete_attachments']) && is_array($_POST['delete_attachments'])) {
        foreach ($_POST['delete_attachments'] as $attachmentId) {
            $attachmentId = (int)$attachmentId;
            
            // 获取附件信息
            $sql = "SELECT filename FROM attachments WHERE id = $attachmentId AND report_id = $reportId";
            $result = query($sql);
            
            if ($result->num_rows > 0) {
                $attachment = $result->fetch_assoc();
                $filePath = UPLOAD_DIR . 'reports/' . $attachment['filename'];
                
                // 删除文件
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // 删除数据库记录
                $sql = "DELETE FROM attachments WHERE id = $attachmentId";
                execute($sql);
            }
        }
    }
    
    // 设置成功消息并重定向
    if ($status == 'submitted') {
        setFlashMessage('success', '汇报已成功提交！');
    } else {
        setFlashMessage('success', '汇报已保存为草稿！');
    }
    redirect(BASE_URL . '/reports/view.php?id=' . $reportId);
}

// 获取汇报类型列表
$reportTypes = getReportTypes();

$pageTitle = '编辑汇报';
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
                <div class="action-bar">
                    <a href="<?php echo BASE_URL; ?>/reports/view.php?id=<?php echo $reportId; ?>" class="btn-secondary">
                        <i class="icon-back"></i> 返回查看
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>编辑汇报</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" id="title" name="title" value="<?php echo $report['title']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">类型</label>
                                <select id="type" name="type" required>
                                    <option value="">请选择汇报类型</option>
                                    <?php foreach ($reportTypes as $reportType): ?>
                                        <option value="<?php echo $reportType; ?>" <?php echo $report['type'] == $reportType ? 'selected' : ''; ?>>
                                            <?php echo $reportType; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">内容</label>
                                <textarea id="content" name="content" rows="15" required><?php echo $report['content']; ?></textarea>
                            </div>
                            
                            <?php if ($attachments->num_rows > 0): ?>
                                <div class="form-group">
                                    <label>现有附件</label>
                                    <ul class="attachment-list">
                                        <?php while ($attachment = $attachments->fetch_assoc()): ?>
                                            <li>
                                                <label>
                                                    <input type="checkbox" name="delete_attachments[]" value="<?php echo $attachment['id']; ?>">
                                                    删除
                                                </label>
                                                <a href="<?php echo BASE_URL; ?>/uploads/reports/<?php echo $attachment['filename']; ?>" target="_blank">
                                                    <?php echo $attachment['original_name']; ?>
                                                </a>
                                                <span class="file-size">(<?php echo formatFileSize($attachment['file_size']); ?>)</span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                    <small class="form-text">勾选要删除的附件</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="attachments">添加新附件</label>
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
                            
                            <?php if ($report['status'] == 'rejected' && !empty($report['review_comment'])): ?>
                                <div class="review-comment">
                                    <h3>审核意见</h3>
                                    <p><?php echo nl2br($report['review_comment']); ?></p>
                                </div>
                            <?php endif; ?>
                            
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
