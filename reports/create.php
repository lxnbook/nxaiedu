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
    $status = isset($_POST['submit_type']) && $_POST['submit_type'] === 'submit' ? 'submitted' : 'draft';
    $submittedAt = $status === 'submitted' ? 'NOW()' : 'NULL';
    
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
    if ($status === 'submitted') {
        setFlashMessage('success', '汇报已成功提交！');
    } else {
        setFlashMessage('success', '汇报已保存为草稿！');
    }
    redirect(BASE_URL . '/reports/index.php');
}

// 获取汇报类型列表
$reportTypes = getReportTypes();

// 获取提示词列表
$sql = "SELECT * FROM prompts WHERE is_public = 1 OR created_by = {$_SESSION['user_id']} ORDER BY title";
$prompts = query($sql);

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
                                <div class="editor-toolbar">
                                    <button type="button" class="btn-secondary btn-sm" id="insertPrompt">插入提示词</button>
                                </div>
                                <textarea id="content" name="content" rows="15" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachments">附件</label>
                                <input type="file" id="attachments" name="attachments[]" multiple>
                                <small class="form-text">支持多个文件，单个文件大小不超过10MB</small>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="submit_type" value="draft" class="btn-secondary">保存草稿</button>
                                <button type="submit" name="submit_type" value="submit" class="btn-primary">提交汇报</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- 提示词选择弹窗 -->
    <div id="promptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>选择提示词</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group search-group">
                    <input type="text" id="promptSearch" placeholder="搜索提示词">
                </div>
                <div class="prompt-list">
                    <?php if ($prompts->num_rows > 0): ?>
                        <?php while ($prompt = $prompts->fetch_assoc()): ?>
                            <div class="prompt-item" data-id="<?php echo $prompt['id']; ?>">
                                <h4><?php echo $prompt['title']; ?></h4>
                                <p><?php echo substr(strip_tags($prompt['content']), 0, 100) . '...'; ?></p>
                                <div class="prompt-meta">
                                    <span class="prompt-category"><?php echo $prompt['category']; ?></span>
                                    <?php if (!empty($prompt['subject'])): ?>
                                        <span class="prompt-subject"><?php echo $prompt['subject']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-data">暂无提示词</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">取消</button>
                <button type="button" class="btn-primary" id="insertSelectedPrompt">插入</button>
            </div>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        // 插入提示词功能
        document.addEventListener('DOMContentLoaded', function() {
            const insertPromptBtn = document.getElementById('insertPrompt');
            const promptModal = document.getElementById('promptModal');
            const modalClose = document.querySelectorAll('.modal-close');
            const insertSelectedPromptBtn = document.getElementById('insertSelectedPrompt');
            const promptItems = document.querySelectorAll('.prompt-item');
            const contentTextarea = document.getElementById('content');
            const promptSearch = document.getElementById('promptSearch');
            
            // 打开提示词弹窗
            insertPromptBtn.addEventListener('click', function() {
                promptModal.style.display = 'block';
            });
            
            // 关闭提示词弹窗
            modalClose.forEach(btn => {
                btn.addEventListener('click', function() {
                    promptModal.style.display = 'none';
                });
            });
            
            // 点击窗口外部关闭弹窗
            window.addEventListener('click', function(event) {
                if (event.target === promptModal) {
                    promptModal.style.display = 'none';
                }
            });
            
            // 选择提示词
            let selectedPromptId = null;
            promptItems.forEach(item => {
                item.addEventListener('click', function() {
                    promptItems.forEach(i => i.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedPromptId = this.dataset.id;
                });
            });
            
            // 插入选中的提示词
            insertSelectedPromptBtn.addEventListener('click', function() {
                if (selectedPromptId) {
                    // 通过AJAX获取提示词内容
                    fetch(`<?php echo BASE_URL; ?>/prompts/get_prompt.php?id=${selectedPromptId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // 插入提示词内容到编辑器
                                contentTextarea.value += data.content;
                                promptModal.style.display = 'none';
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
            
            // 搜索提示词
            promptSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                promptItems.forEach(item => {
                    const title = item.querySelector('h4').textContent.toLowerCase();
                    const content = item.querySelector('p').textContent.toLowerCase();
                    if (title.includes(searchTerm) || content.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
