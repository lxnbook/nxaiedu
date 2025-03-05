<?php
require_once '../config/config.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

// 获取报告ID
$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$report_id) {
    setFlashMessage('error', '未指定报告ID');
    redirect(BASE_URL . '/reports/index.php');
}

// 获取报告信息
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    setFlashMessage('error', '找不到指定的报告');
    redirect(BASE_URL . '/reports/index.php');
}

// 检查权限 - 只有作者或管理员可以编辑
if ($report['author_id'] != $_SESSION['user_id'] && $_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限编辑此报告');
    redirect(BASE_URL . '/reports/view.php?id=' . $report_id);
}

// 检查报告状态 - 只有草稿或被拒绝的报告可以编辑
if ($report['status'] != 'draft' && $report['status'] != 'rejected') {
    setFlashMessage('error', '只有草稿或被拒绝的报告可以编辑');
    redirect(BASE_URL . '/reports/view.php?id=' . $report_id);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $type = trim($_POST['type']);
    
    // 验证输入
    $errors = [];
    
    if (empty($title)) {
        $errors[] = '标题不能为空';
    }
    
    if (empty($content)) {
        $errors[] = '内容不能为空';
    }
    
    if (empty($type)) {
        $errors[] = '请选择报告类型';
    }
    
    // 如果没有错误，更新报告
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE reports SET title = ?, content = ?, type = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$title, $content, $type, $report_id]);
        
        if ($result) {
            // 处理附件上传
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = '../uploads/reports/' . $report_id . '/';
                
                // 确保上传目录存在
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // 获取允许的文件类型
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allowed_file_types'");
                $stmt->execute();
                $allowed_types_setting = $stmt->fetch();
                $allowed_types = explode(',', $allowed_types_setting['setting_value']);
                
                // 获取最大文件大小
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'max_file_size'");
                $stmt->execute();
                $max_size_setting = $stmt->fetch();
                $max_size = (int)$max_size_setting['setting_value'];
                
                $file_count = count($_FILES['attachments']['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                        $name = $_FILES['attachments']['name'][$i];
                        $size = $_FILES['attachments']['size'][$i];
                        $type = $_FILES['attachments']['type'][$i];
                        
                        // 检查文件类型
                        $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (!in_array($file_ext, $allowed_types)) {
                            $errors[] = "文件 '$name' 类型不允许上传";
                            continue;
                        }
                        
                        // 检查文件大小
                        if ($size > $max_size) {
                            $errors[] = "文件 '$name' 超过最大允许大小";
                            continue;
                        }
                        
                        // 生成唯一文件名
                        $new_name = uniqid() . '.' . $file_ext;
                        $destination = $upload_dir . $new_name;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            // 保存附件信息到数据库
                            $stmt = $pdo->prepare("INSERT INTO attachments (report_id, filename, filepath, filesize, filetype) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$report_id, $name, 'uploads/reports/' . $report_id . '/' . $new_name, $size, $type]);
                        } else {
                            $errors[] = "上传文件 '$name' 失败";
                        }
                    } elseif ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "上传文件时发生错误: " . $_FILES['attachments']['error'][$i];
                    }
                }
            }
            
            if (empty($errors)) {
                setFlashMessage('success', '报告已成功更新');
                redirect(BASE_URL . '/reports/view.php?id=' . $report_id);
            }
        } else {
            $errors[] = '更新报告时发生错误';
        }
    }
}

// 获取报告类型列表
$report_types = [
    '工作汇报' => '工作汇报',
    '项目进展' => '项目进展',
    '教学活动' => '教学活动',
    '会议纪要' => '会议纪要',
    '调研报告' => '调研报告',
    '其他' => '其他'
];

// 获取已上传的附件
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE report_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$report_id]);
$attachments = $stmt->fetchAll();

// 页面标题
$pageTitle = '编辑报告';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <!-- 引入编辑器样式 -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/editor.css">
</head>
<body>
    <div class="wrapper">
        <?php include_once '../includes/sidebar.php'; ?>
        
        <main class="content">
            <?php include_once '../includes/header.php'; ?>
            
            <div class="container">
                <div class="page-header">
                    <h1><?php echo $pageTitle; ?></h1>
                    <div class="breadcrumb">
                        <a href="<?php echo BASE_URL; ?>/dashboard.php">首页</a> / 
                        <a href="<?php echo BASE_URL; ?>/reports/index.php">报告列表</a> / 
                        <span>编辑报告</span>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>编辑报告信息</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="title">报告标题</label>
                                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($report['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="type">报告类型</label>
                                <select id="type" name="type" required>
                                    <option value="">请选择报告类型</option>
                                    <?php foreach ($report_types as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($report['type'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content">报告内容</label>
                                <div class="editor-toolbar">
                                    <button type="button" id="aiAssistBtn" class="btn-secondary">
                                        <i class="icon-ai"></i> AI辅助
                                    </button>
                                </div>
                                <textarea id="content" name="content" class="editor" required><?php echo htmlspecialchars($report['content']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="attachments">上传附件</label>
                                <input type="file" id="attachments" name="attachments[]" multiple>
                                <small>允许上传的文件类型: <?php echo $allowed_types_setting['setting_value']; ?></small>
                                <small>最大文件大小: <?php echo formatFileSize($max_size_setting['setting_value']); ?></small>
                            </div>
                            
                            <?php if (!empty($attachments)): ?>
                            <div class="form-group">
                                <label>已上传附件</label>
                                <div class="attachment-list">
                                    <?php foreach ($attachments as $attachment): ?>
                                    <div class="attachment-item">
                                        <span class="attachment-name"><?php echo htmlspecialchars($attachment['filename']); ?></span>
                                        <span class="attachment-size"><?php echo formatFileSize($attachment['filesize']); ?></span>
                                        <div class="attachment-actions">
                                            <a href="<?php echo BASE_URL . '/' . $attachment['filepath']; ?>" target="_blank" class="btn-sm btn-primary">查看</a>
                                            <a href="<?php echo BASE_URL; ?>/reports/delete_attachment.php?id=<?php echo $attachment['id']; ?>&report_id=<?php echo $report_id; ?>" class="btn-sm btn-danger" onclick="return confirm('确定要删除此附件吗？')">删除</a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">保存更改</button>
                                <a href="<?php echo BASE_URL; ?>/reports/view.php?id=<?php echo $report_id; ?>" class="btn-secondary">取消</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- AI辅助模态框 -->
    <div id="aiAssistModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>AI辅助</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="tabs-container">
                    <div class="tab-nav">
                        <button class="tab-btn active" data-tab="tab-generate">生成内容</button>
                        <button class="tab-btn" data-tab="tab-improve">优化内容</button>
                    </div>
                    
                    <!-- 生成内容 -->
                    <div id="tab-generate" class="tab-pane active">
                        <div class="form-group">
                            <label for="aiPrompt">描述您需要的汇报内容</label>
                            <textarea id="aiPrompt" rows="5" placeholder="例如：请生成一份关于学校信息化建设项目的月度进展汇报，包括硬件设施更新、教师培训和学生使用情况"></textarea>
                        </div>
                        <button type="button" id="generateBtn" class="btn-primary">生成内容</button>
                    </div>
                    
                    <!-- 优化内容 -->
                    <div id="tab-improve" class="tab-pane">
                        <div class="form-group">
                            <label for="aiInstruction">优化指令</label>
                            <textarea id="aiInstruction" rows="3" placeholder="例如：优化语言表达，使内容更加专业；或者：添加更多关于教学成果的细节"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="contentToImprove">需要优化的内容</label>
                            <textarea id="contentToImprove" rows="5" placeholder="将需要优化的内容粘贴在这里"></textarea>
                        </div>
                        <button type="button" id="improveBtn" class="btn-primary">优化内容</button>
                    </div>
                </div>
                
                <div id="aiLoading" class="ai-loading" style="display: none;">
                    <div class="spinner"></div>
                    <p>AI正在处理，请稍候...</p>
                </div>
                
                <div id="aiResult" class="ai-result" style="display: none;">
                    <h4>生成结果</h4>
                    <div id="aiResultContent" class="ai-result-content"></div>
                    <div class="form-actions">
                        <button type="button" id="useResultBtn" class="btn-primary">使用此内容</button>
                        <button type="button" id="cancelResultBtn" class="btn-secondary">取消</button>
                    </div>
                </div>
                
                <div id="aiError" class="ai-error" style="display: none;">
                    <p>出错了！<span id="aiErrorMessage"></span></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 引入编辑器脚本 -->
    <script src="<?php echo BASE_URL; ?>/assets/js/editor.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化编辑器
        const contentEditor = document.getElementById('content');
        
        // 获取AI辅助模态框元素
        const aiAssistBtn = document.getElementById('aiAssistBtn');
        const aiAssistModal = document.getElementById('aiAssistModal');
        const closeBtn = aiAssistModal.querySelector('.close');
        const generateBtn = document.getElementById('generateBtn');
        const improveBtn = document.getElementById('improveBtn');
        const useResultBtn = document.getElementById('useResultBtn');
        const cancelResultBtn = document.getElementById('cancelResultBtn');
        const aiLoading = document.getElementById('aiLoading');
        const aiResult = document.getElementById('aiResult');
        const aiResultContent = document.getElementById('aiResultContent');
        const aiError = document.getElementById('aiError');
        const aiErrorMessage = document.getElementById('aiErrorMessage');
        
        // 打开模态框
        aiAssistBtn.addEventListener('click', function() {
            aiAssistModal.style.display = 'block';
            document.getElementById('contentToImprove').value = contentEditor.value;
        });
        
        // 关闭模态框
        closeBtn.addEventListener('click', function() {
            aiAssistModal.style.display = 'none';
            resetAiInterface();
        });
        
        // 点击模态框外部关闭
        window.addEventListener('click', function(event) {
            if (event.target == aiAssistModal) {
                aiAssistModal.style.display = 'none';
                resetAiInterface();
            }
        });
        
        // 生成内容
        generateBtn.addEventListener('click', function() {
            const prompt = document.getElementById('aiPrompt').value.trim();
            
            if (!prompt) {
                showError('请输入提示词');
                return;
            }
            
            showLoading();
            
            // 调用API生成内容
            fetch('<?php echo BASE_URL; ?>/api/ai_generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'generate',
                    prompt: prompt
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showResult(data.content);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                hideLoading();
                showError('请求失败: ' + error.message);
            });
        });
        
        // 优化内容
        improveBtn.addEventListener('click', function() {
            const instruction = document.getElementById('aiInstruction').value.trim();
            const content = document.getElementById('contentToImprove').value.trim();
            
            if (!instruction) {
                showError('请输入优化指令');
                return;
            }
            
            if (!content) {
                showError('当前没有内容可优化，请先在编辑器中输入内容');
                return;
            }
            
            showLoading();
            
            // 调用API优化内容
            fetch('<?php echo BASE_URL; ?>/api/ai_generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'improve',
                    content: content,
                    instruction: instruction
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showResult(data.content);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                hideLoading();
                showError('请求失败: ' + error.message);
            });
        });
        
        // 使用AI生成的结果
        useResultBtn.addEventListener('click', function() {
            contentEditor.value = aiResultContent.textContent;
            aiAssistModal.style.display = 'none';
            resetAiInterface();
        });
        
        // 取消使用AI结果
        cancelResultBtn.addEventListener('click', function() {
            resetAiInterface();
        });
        
        // 显示加载状态
        function showLoading() {
            aiResult.style.display = 'none';
            aiError.style.display = 'none';
            aiLoading.style.display = 'block';
        }
        
        // 隐藏加载状态
        function hideLoading() {
            aiLoading.style.display = 'none';
        }
        
        // 显示结果
        function showResult(content) {
            aiResultContent.textContent = content;
            aiResult.style.display = 'block';
        }
        
        // 显示错误
        function showError(message) {
            aiErrorMessage.textContent = message;
            aiError.style.display = 'block';
        }
        
        // 重置AI界面
        function resetAiInterface() {
            aiResult.style.display = 'none';
            aiError.style.display = 'none';
            aiLoading.style.display = 'none';
            document.getElementById('aiPrompt').value = '';
            document.getElementById('aiInstruction').value = '';
        }
        
        // 标签页切换
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // 移除所有活动状态
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // 添加当前活动状态
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
                
                // 重置AI界面
                resetAiInterface();
            });
        });
    });
    </script>
</body>
</html>
