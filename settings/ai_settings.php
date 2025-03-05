<?php
require_once '../config/config.php';

// 需要管理员权限
requireAdmin();

$pageTitle = 'AI模型设置';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 保存API设置
    if (isset($_POST['save_api'])) {
        $provider = $_POST['provider'];
        $api_key = $_POST['api_key'];
        $api_url = $_POST['api_url'];
        $model_name = $_POST['model_name'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $max_tokens = (int)$_POST['max_tokens'];
        $temperature = (float)$_POST['temperature'];
        
        // 检查是否已存在该提供商的配置
        $stmt = $pdo->prepare("SELECT id FROM ai_api_settings WHERE provider = ?");
        $stmt->execute([$provider]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 更新现有配置
            $stmt = $pdo->prepare("UPDATE ai_api_settings SET 
                api_key = ?, 
                api_url = ?, 
                model_name = ?, 
                is_active = ?,
                max_tokens = ?,
                temperature = ?
                WHERE provider = ?");
            $stmt->execute([$api_key, $api_url, $model_name, $is_active, $max_tokens, $temperature, $provider]);
            setFlashMessage('success', 'AI API设置已更新');
        } else {
            // 创建新配置
            $stmt = $pdo->prepare("INSERT INTO ai_api_settings 
                (provider, api_key, api_url, model_name, is_active, max_tokens, temperature) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$provider, $api_key, $api_url, $model_name, $is_active, $max_tokens, $temperature]);
            setFlashMessage('success', 'AI API设置已添加');
        }
        
        // 如果启用了当前提供商，禁用其他提供商
        if ($is_active) {
            $stmt = $pdo->prepare("UPDATE ai_api_settings SET is_active = 0 WHERE provider != ?");
            $stmt->execute([$provider]);
        }
        
        redirect(BASE_URL . '/settings/ai_settings.php');
    }
    
    // 测试API连接
    if (isset($_POST['test_api'])) {
        $provider = $_POST['provider'];
        
        // 获取API设置
        $stmt = $pdo->prepare("SELECT * FROM ai_api_settings WHERE provider = ?");
        $stmt->execute([$provider]);
        $api_settings = $stmt->fetch();
        
        if ($api_settings) {
            // 测试API连接
            $result = testApiConnection($api_settings);
            
            if ($result['success']) {
                setFlashMessage('success', 'API连接测试成功: ' . $result['message']);
            } else {
                setFlashMessage('error', 'API连接测试失败: ' . $result['message']);
            }
        } else {
            setFlashMessage('error', '找不到该提供商的API设置');
        }
        
        redirect(BASE_URL . '/settings/ai_settings.php');
    }
}

// 获取所有API设置
$stmt = $pdo->query("SELECT * FROM ai_api_settings ORDER BY provider");
$api_settings = $stmt->fetchAll();

// 测试API连接函数
function testApiConnection($api_settings) {
    $provider = $api_settings['provider'];
    $api_key = $api_settings['api_key'];
    $api_url = $api_settings['api_url'];
    $model_name = $api_settings['model_name'];
    
    // 根据不同的提供商使用不同的测试方法
    if ($provider === 'deepseek') {
        // DeepSeek API测试
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        
        $data = [
            'model' => $model_name,
            'messages' => [
                ['role' => 'user', 'content' => '你好，这是一个API测试']
            ],
            'max_tokens' => 10
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'message' => '连接错误: ' . $error];
        }
        
        if ($http_code !== 200) {
            return ['success' => false, 'message' => 'HTTP错误: ' . $http_code . ' - ' . $response];
        }
        
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return ['success' => true, 'message' => '响应: ' . $result['choices'][0]['message']['content']];
        } else {
            return ['success' => false, 'message' => '无效的响应格式: ' . $response];
        }
    } else {
        // 其他API提供商的测试方法
        return ['success' => false, 'message' => '不支持的API提供商: ' . $provider];
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- 侧边导航 -->
        <?php include_once '../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <!-- 页面头部 -->
            <?php include_once '../includes/header.php'; ?>
            
            <div class="page-content">
                <!-- 显示提示消息 -->
                <?php include_once '../includes/flash_messages.php'; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>AI模型API设置</h2>
                    </div>
                    <div class="card-body">
                        <div class="tabs-container">
                            <div class="tab-nav">
                                <button class="tab-btn active" data-tab="tab-deepseek">DeepSeek</button>
                                <button class="tab-btn" data-tab="tab-other">其他模型</button>
                            </div>
                            
                            <!-- DeepSeek设置 -->
                            <div id="tab-deepseek" class="tab-pane active">
                                <form method="post" action="">
                                    <input type="hidden" name="provider" value="deepseek">
                                    
                                    <?php
                                    // 获取DeepSeek设置
                                    $deepseek = null;
                                    foreach ($api_settings as $setting) {
                                        if ($setting['provider'] === 'deepseek') {
                                            $deepseek = $setting;
                                            break;
                                        }
                                    }
                                    ?>
                                    
                                    <div class="form-group">
                                        <label for="is_active">启用DeepSeek API</label>
                                        <input type="checkbox" id="is_active" name="is_active" <?php echo ($deepseek && $deepseek['is_active']) ? 'checked' : ''; ?>>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="api_key">API密钥</label>
                                        <input type="text" id="api_key" name="api_key" value="<?php echo $deepseek ? $deepseek['api_key'] : ''; ?>" required>
                                        <small>您可以从DeepSeek开发者平台获取API密钥</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="api_url">API端点URL</label>
                                        <input type="text" id="api_url" name="api_url" value="<?php echo $deepseek ? $deepseek['api_url'] : 'https://api.deepseek.com/v1/chat/completions'; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="model_name">模型名称</label>
                                        <input type="text" id="model_name" name="model_name" value="<?php echo $deepseek ? $deepseek['model_name'] : 'deepseek-chat'; ?>" required>
                                        <small>例如: deepseek-chat, deepseek-coder等</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max_tokens">最大生成Token数</label>
                                        <input type="number" id="max_tokens" name="max_tokens" value="<?php echo $deepseek ? $deepseek['max_tokens'] : 2000; ?>" required min="1" max="8000">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="temperature">温度 (0.0-1.0)</label>
                                        <input type="number" id="temperature" name="temperature" value="<?php echo $deepseek ? $deepseek['temperature'] : 0.7; ?>" required min="0" max="1" step="0.1">
                                        <small>较低的值使输出更确定，较高的值使输出更随机</small>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="save_api" class="btn-primary">保存设置</button>
                                        <button type="submit" name="test_api" class="btn-secondary">测试连接</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 其他模型设置 -->
                            <div id="tab-other" class="tab-pane">
                                <p>此处可以添加其他AI模型的配置，如OpenAI、百度文心一言等。</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>AI功能使用说明</h2>
                    </div>
                    <div class="card-body">
                        <h3>DeepSeek API集成</h3>
                        <p>DeepSeek是一个强大的AI大语言模型，可以帮助用户生成高质量的文本内容。在本系统中，DeepSeek API主要用于以下功能：</p>
                        <ul>
                            <li>汇报内容生成与优化</li>
                            <li>提示词库内容生成</li>
                            <li>文本摘要与关键信息提取</li>
                        </ul>
                        
                        <h3>使用注意事项</h3>
                        <ul>
                            <li>API调用会消耗您的DeepSeek账户额度</li>
                            <li>请妥善保管您的API密钥，不要泄露给他人</li>
                            <li>系统会缓存部分AI响应以提高性能并减少API调用</li>
                            <li>请确保您的使用符合DeepSeek的服务条款</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
