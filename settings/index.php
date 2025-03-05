<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员可以访问）
if ($_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 获取当前系统设置
$sql = "SELECT * FROM settings";
$result = query($sql);
$settings = [];

while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 更新系统设置
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // 移除 'setting_' 前缀
            $settingValue = escapeString($value);
            
            // 检查设置是否存在
            $sql = "SELECT id FROM settings WHERE setting_key = '$settingKey'";
            $result = query($sql);
            
            if ($result->num_rows > 0) {
                // 更新现有设置
                $sql = "UPDATE settings SET setting_value = '$settingValue' WHERE setting_key = '$settingKey'";
            } else {
                // 插入新设置
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('$settingKey', '$settingValue')";
            }
            
            execute($sql);
        }
    }
    
    setFlashMessage('success', '系统设置已成功更新！');
    redirect(BASE_URL . '/settings/index.php');
}

$pageTitle = '系统设置';
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
                
                <div class="card">
                    <div class="card-header">
                        <h2>系统设置</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="settings-tabs">
                                <div class="tabs-nav">
                                    <button type="button" class="tab-btn active" data-tab="general">基本设置</button>
                                    <button type="button" class="tab-btn" data-tab="reports">汇报设置</button>
                                    <button type="button" class="tab-btn" data-tab="notifications">通知设置</button>
                                    <button type="button" class="tab-btn" data-tab="ai">AI模型设置</button>
                                    <button type="button" class="tab-btn" data-tab="advanced">高级设置</button>
                                </div>
                                
                                <div class="tabs-content">
                                    <!-- 基本设置 -->
                                    <div class="tab-pane active" id="general">
                                        <div class="form-group">
                                            <label for="setting_site_name">系统名称</label>
                                            <input type="text" id="setting_site_name" name="setting_site_name" value="<?php echo $settings['site_name'] ?? '教育局项目汇报系统'; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_site_description">系统描述</label>
                                            <textarea id="setting_site_description" name="setting_site_description" rows="3"><?php echo $settings['site_description'] ?? '教育局项目汇报管理系统，用于管理和提交各类教育项目汇报。'; ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_admin_email">管理员邮箱</label>
                                            <input type="email" id="setting_admin_email" name="setting_admin_email" value="<?php echo $settings['admin_email'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_items_per_page">每页显示条目数</label>
                                            <input type="number" id="setting_items_per_page" name="setting_items_per_page" value="<?php echo $settings['items_per_page'] ?? '10'; ?>" min="5" max="100">
                                        </div>
                                    </div>
                                    
                                    <!-- 汇报设置 -->
                                    <div class="tab-pane" id="reports">
                                        <div class="form-group">
                                            <label for="setting_report_types">汇报类型</label>
                                            <textarea id="setting_report_types" name="setting_report_types" rows="5" placeholder="每行一个类型"><?php echo $settings['report_types'] ?? "月度汇报\n季度汇报\n年度汇报\n项目汇报\n专题汇报"; ?></textarea>
                                            <small class="form-text">每行输入一个汇报类型</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_max_file_size">最大附件大小 (MB)</label>
                                            <input type="number" id="setting_max_file_size" name="setting_max_file_size" value="<?php echo $settings['max_file_size'] ?? '10'; ?>" min="1" max="50">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_allowed_file_types">允许的附件类型</label>
                                            <input type="text" id="setting_allowed_file_types" name="setting_allowed_file_types" value="<?php echo $settings['allowed_file_types'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip'; ?>">
                                            <small class="form-text">用逗号分隔的文件扩展名列表</small>
                                        </div>
                                    </div>
                                    
                                    <!-- 通知设置 -->
                                    <div class="tab-pane" id="notifications">
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="setting_email_notifications" value="1" <?php echo ($settings['email_notifications'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                                启用邮件通知
                                            </label>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="setting_notify_on_submission" value="1" <?php echo ($settings['notify_on_submission'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                                汇报提交时通知管理员
                                            </label>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="setting_notify_on_review" value="1" <?php echo ($settings['notify_on_review'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                                汇报审核时通知提交者
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- AI模型设置 -->
                                    <div class="tab-pane" id="ai">
                                        <div class="ai-settings-intro">
                                            <h3>AI模型配置</h3>
                                            <p>在这里配置AI模型API，启用智能辅助功能。</p>
                                            
                                            <div class="ai-settings-actions">
                                                <a href="<?php echo BASE_URL; ?>/settings/ai_settings.php" class="btn-primary">配置AI模型</a>
                                            </div>
                                            
                                            <div class="ai-settings-info">
                                                <h4>AI功能概述</h4>
                                                <ul>
                                                    <li>自动生成汇报内容</li>
                                                    <li>优化和改进现有内容</li>
                                                    <li>生成提示词模板</li>
                                                    <li>生成相关图像</li>
                                                </ul>
                                                
                                                <p>配置AI模型API后，系统将在汇报编辑页面提供AI辅助功能，帮助用户更高效地创建高质量汇报。</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- 高级设置 -->
                                    <div class="tab-pane" id="advanced">
                                        <div class="form-group">
                                            <label for="setting_maintenance_mode">维护模式</label>
                                            <select id="setting_maintenance_mode" name="setting_maintenance_mode">
                                                <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>关闭</option>
                                                <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>开启</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_maintenance_message">维护信息</label>
                                            <textarea id="setting_maintenance_message" name="setting_maintenance_message" rows="3"><?php echo $settings['maintenance_message'] ?? '系统正在维护中，请稍后再试。'; ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="setting_log_level">日志级别</label>
                                            <select id="setting_log_level" name="setting_log_level">
                                                <option value="error" <?php echo ($settings['log_level'] ?? 'error') == 'error' ? 'selected' : ''; ?>>仅错误</option>
                                                <option value="warning" <?php echo ($settings['log_level'] ?? 'error') == 'warning' ? 'selected' : ''; ?>>警告和错误</option>
                                                <option value="info" <?php echo ($settings['log_level'] ?? 'error') == 'info' ? 'selected' : ''; ?>>信息、警告和错误</option>
                                                <option value="debug" <?php echo ($settings['log_level'] ?? 'error') == 'debug' ? 'selected' : ''; ?>>所有（调试模式）</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">保存设置</button>
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
            // 设置选项卡切换
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
                });
            });
        });
    </script>
    
    <style>
    .ai-settings-intro {
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .ai-settings-actions {
        margin: 20px 0;
    }
    
    .ai-settings-info {
        background-color: #fff;
        border-left: 4px solid #4a6cf7;
        padding: 15px;
        margin-top: 20px;
    }
    
    .ai-settings-info h4 {
        margin-top: 0;
        color: #333;
    }
    
    .ai-settings-info ul {
        padding-left: 20px;
    }
    
    .ai-settings-info li {
        margin-bottom: 8px;
    }
    </style>
</body>
</html>
