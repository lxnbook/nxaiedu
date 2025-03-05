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
        $api_type = $_POST['api_type'];
        $api_key = $_POST['api_key'];
        $api_url = $_POST['api_url'];
        $model_name = $_POST['model_name'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // 根据API类型处理不同的参数
        $max_tokens = null;
        $temperature = null;
        $additional_params = null;
        
        if ($api_type == 'text') {
            $max_tokens = (int)$_POST['max_tokens'];
            $temperature = (float)$_POST['temperature'];
        } elseif ($api_type == 'image') {
            $additional_params = json_encode([
                'image_size' => $_POST['image_size'] ?? '1024x1024',
                'image_quality' => $_POST['image_quality'] ?? 'standard',
                'image_style' => $_POST['image_style'] ?? 'natural'
            ]);
        }
        
        // 检查是否已存在该提供商和类型的配置
        $stmt = $pdo->prepare("SELECT id FROM ai_api_settings WHERE provider = ? AND api_type = ?");
        $stmt->execute([$provider, $api_type]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 更新现有配置
            $stmt = $pdo->prepare("UPDATE ai_api_settings SET 
                api_key = ?, 
                api_url = ?, 
                model_name = ?, 
                is_active = ?,
                max_tokens = ?,
                temperature = ?,
                additional_params = ?
                WHERE provider = ? AND api_type = ?");
            $stmt->execute([$api_key, $api_url, $model_name, $is_active, $max_tokens, $temperature, $additional_params, $provider, $api_type]);
            setFlashMessage('success', 'AI API设置已更新');
        } else {
            // 创建新配置
            $stmt = $pdo->prepare("INSERT INTO ai_api_settings 
                (provider, api_type, api_key, api_url, model_name, is_active, max_tokens, temperature, additional_params) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$provider, $api_type, $api_key, $api_url, $model_name, $is_active, $max_tokens, $temperature, $additional_params]);
            setFlashMessage('success', 'AI API设置已添加');
        }
    }
    
    // 测试API连接
    if (isset($_POST['test_api'])) {
        $provider = $_POST['provider'];
        $api_type = $_POST['api_type'];
        $api_key = $_POST['api_key'];
        $api_url = $_POST['api_url'];
        
        // 根据API类型和提供商构建测试请求
        $success = false;
        $message = '';
        
        if ($api_type == 'text') {
            // 测试文本API
            $model = $_POST['model_name'];
            
            $params = [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => '你是一个有用的助手。'],
                    ['role' => 'user', 'content' => '你好，这是一个API测试。请回复"API连接成功"。']
                ],
                'max_tokens' => 50,
                'temperature' => 0.7
            ];
            
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ];
            
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $message = 'cURL错误: ' . curl_error($ch);
            } elseif ($http_code >= 200 && $http_code < 300) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    $success = true;
                    $message = '连接成功: ' . $result['choices'][0]['message']['content'];
                } else {
                    $message = '无效的响应格式: ' . $response;
                }
            } else {
                $message = 'HTTP错误: ' . $http_code . ' - ' . $response;
            }
            
            curl_close($ch);
        } elseif ($api_type == 'image') {
            // 测试图像API
            if ($provider == 'openai') {
                $params = [
                    'model' => $_POST['model_name'],
                    'prompt' => '一个简单的测试图像，蓝色背景上的白色圆形',
                    'n' => 1,
                    'size' => '256x256',
                    'response_format' => 'url'
                ];
                
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key
                ];
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (curl_errno($ch)) {
                    $message = 'cURL错误: ' . curl_error($ch);
                } elseif ($http_code >= 200 && $http_code < 300) {
                    $result = json_decode($response, true);
                    if (isset($result['data'][0]['url'])) {
                        $success = true;
                        $message = '连接成功: 图像URL已生成';
                    } else {
                        $message = '无效的响应格式: ' . $response;
                    }
                } else {
                    $message = 'HTTP错误: ' . $http_code . ' - ' . $response;
                }
                
                curl_close($ch);
            } elseif ($provider == 'stability') {
                $params = [
                    'text_prompts' => [
                        ['text' => '一个简单的测试图像，蓝色背景上的白色圆形']
                    ],
                    'cfg_scale' => 7,
                    'height' => 256,
                    'width' => 256,
                    'samples' => 1
                ];
                
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key,
                    'Accept: application/json'
                ];
                
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (curl_errno($ch)) {
                    $message = 'cURL错误: ' . curl_error($ch);
                } elseif ($http_code >= 200 && $http_code < 300) {
                    $result = json_decode($response, true);
                    if (isset($result['artifacts'][0]['base64'])) {
                        $success = true;
                        $message = '连接成功: 图像已生成';
                    } else {
                        $message = '无效的响应格式: ' . $response;
                    }
                } else {
                    $message = 'HTTP错误: ' . $http_code . ' - ' . $response;
                }
                
                curl_close($ch);
            }
        }
        
        if ($success) {
            setFlashMessage('success', 'API测试成功: ' . $message);
        } else {
            setFlashMessage('error', 'API测试失败: ' . $message);
        }
    }
}

// 获取现有API设置
$stmt = $pdo->query("SELECT * FROM ai_api_settings");
$api_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 整理设置为易于访问的格式
$deepseek_text = null;
$openai_text = null;
$openai_image = null;
$stability_image = null;

foreach ($api_settings as $setting) {
    if ($setting['provider'] == 'deepseek' && $setting['api_type'] == 'text') {
        $deepseek_text = $setting;
    } elseif ($setting['provider'] == 'openai' && $setting['api_type'] == 'text') {
        $openai_text = $setting;
    } elseif ($setting['provider'] == 'openai' && $setting['api_type'] == 'image') {
        $openai_image = $setting;
        $openai_image_params = json_decode($setting['additional_params'], true) ?? [];
    } elseif ($setting['provider'] == 'stability' && $setting['api_type'] == 'image') {
        $stability_image = $setting;
        $stability_image_params = json_decode($setting['additional_params'], true) ?? [];
    }
}

// 包含页面头部
include_once '../includes/header.php';
?>

<div class="container">
    <?php include_once '../includes/sidebar.php'; ?>
    
    <div class="content">
        <main>
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <p>配置和管理AI模型API设置</p>
            </div>
            
            <?php include_once '../includes/flash_messages.php'; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="tabs-container">
                        <div class="tab-nav">
                            <button class="tab-btn active" data-tab="tab-text-models">文本生成模型</button>
                            <button class="tab-btn" data-tab="tab-image-models">图像生成模型</button>
                            <button class="tab-btn" data-tab="tab-help">使用帮助</button>
                        </div>
                        
                        <!-- 文本生成模型设置 -->
                        <div id="tab-text-models" class="tab-pane active">
                            <div class="sub-tabs">
                                <div class="tab-nav">
                                    <button class="tab-btn active" data-tab="tab-deepseek">DeepSeek</button>
                                    <button class="tab-btn" data-tab="tab-openai-text">OpenAI</button>
                                </div>
                                
                                <!-- DeepSeek设置 -->
                                <div id="tab-deepseek" class="tab-pane active">
                                    <h3>DeepSeek API设置</h3>
                                    <p>配置DeepSeek API以启用AI文本生成功能</p>
                                    
                                    <form method="post" action="">
                                        <input type="hidden" name="provider" value="deepseek">
                                        <input type="hidden" name="api_type" value="text">
                                        
                                        <div class="form-group">
                                            <label for="deepseek_is_active">启用DeepSeek API</label>
                                            <div class="toggle-switch">
                                                <input type="checkbox" id="deepseek_is_active" name="is_active" <?php echo $deepseek_text && $deepseek_text['is_active'] ? 'checked' : ''; ?>>
                                                <label for="deepseek_is_active"></label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="deepseek_api_key">API密钥</label>
                                            <input type="password" id="deepseek_api_key" name="api_key" value="<?php echo $deepseek_text ? $deepseek_text['api_key'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="deepseek_api_url">API端点URL</label>
                                            <input type="text" id="deepseek_api_url" name="api_url" value="<?php echo $deepseek_text ? $deepseek_text['api_url'] : 'https://api.deepseek.com/v1/chat/completions'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="deepseek_model_name">模型名称</label>
                                            <input type="text" id="deepseek_model_name" name="model_name" value="<?php echo $deepseek_text ? $deepseek_text['model_name'] : 'deepseek-chat'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="deepseek_max_tokens">最大生成令牌数</label>
                                            <input type="number" id="deepseek_max_tokens" name="max_tokens" value="<?php echo $deepseek_text ? $deepseek_text['max_tokens'] : 2000; ?>" min="1" max="8000" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="deepseek_temperature">温度 (0.0-1.0)</label>
                                            <input type="number" id="deepseek_temperature" name="temperature" value="<?php echo $deepseek_text ? $deepseek_text['temperature'] : 0.7; ?>" min="0" max="1" step="0.1" required>
                                            <small>较低的值使输出更确定，较高的值使输出更随机</small>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" name="save_api" class="btn-primary">保存设置</button>
                                            <button type="submit" name="test_api" class="btn-secondary">测试连接</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- OpenAI文本设置 -->
                                <div id="tab-openai-text" class="tab-pane">
                                    <h3>OpenAI文本API设置</h3>
                                    <p>配置OpenAI API以启用AI文本生成功能</p>
                                    
                                    <form method="post" action="">
                                        <input type="hidden" name="provider" value="openai">
                                        <input type="hidden" name="api_type" value="text">
                                        
                                        <div class="form-group">
                                            <label for="openai_text_is_active">启用OpenAI文本API</label>
                                            <div class="toggle-switch">
                                                <input type="checkbox" id="openai_text_is_active" name="is_active" <?php echo $openai_text && $openai_text['is_active'] ? 'checked' : ''; ?>>
                                                <label for="openai_text_is_active"></label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_text_api_key">API密钥</label>
                                            <input type="password" id="openai_text_api_key" name="api_key" value="<?php echo $openai_text ? $openai_text['api_key'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_text_api_url">API端点URL</label>
                                            <input type="text" id="openai_text_api_url" name="api_url" value="<?php echo $openai_text ? $openai_text['api_url'] : 'https://api.openai.com/v1/chat/completions'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_text_model_name">模型名称</label>
                                            <input type="text" id="openai_text_model_name" name="model_name" value="<?php echo $openai_text ? $openai_text['model_name'] : 'gpt-3.5-turbo'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_text_max_tokens">最大生成令牌数</label>
                                            <input type="number" id="openai_text_max_tokens" name="max_tokens" value="<?php echo $openai_text ? $openai_text['max_tokens'] : 2000; ?>" min="1" max="8000" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_text_temperature">温度 (0.0-1.0)</label>
                                            <input type="number" id="openai_text_temperature" name="temperature" value="<?php echo $openai_text ? $openai_text['temperature'] : 0.7; ?>" min="0" max="1" step="0.1" required>
                                            <small>较低的值使输出更确定，较高的值使输出更随机</small>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" name="save_api" class="btn-primary">保存设置</button>
                                            <button type="submit" name="test_api" class="btn-secondary">测试连接</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 图像生成模型设置 -->
                        <div id="tab-image-models" class="tab-pane">
                            <div class="sub-tabs">
                                <div class="tab-nav">
                                    <button class="tab-btn active" data-tab="tab-openai-image">OpenAI DALL-E</button>
                                    <button class="tab-btn" data-tab="tab-stability">Stability AI</button>
                                </div>
                                
                                <!-- OpenAI图像设置 -->
                                <div id="tab-openai-image" class="tab-pane active">
                                    <h3>OpenAI DALL-E API设置</h3>
                                    <p>配置OpenAI DALL-E API以启用AI图像生成功能</p>
                                    
                                    <form method="post" action="">
                                        <input type="hidden" name="provider" value="openai">
                                        <input type="hidden" name="api_type" value="image">
                                        
                                        <div class="form-group">
                                            <label for="openai_image_is_active">启用OpenAI图像API</label>
                                            <div class="toggle-switch">
                                                <input type="checkbox" id="openai_image_is_active" name="is_active" <?php echo $openai_image && $openai_image['is_active'] ? 'checked' : ''; ?>>
                                                <label for="openai_image_is_active"></label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_api_key">API密钥</label>
                                            <input type="password" id="openai_image_api_key" name="api_key" value="<?php echo $openai_image ? $openai_image['api_key'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_api_url">API端点URL</label>
                                            <input type="text" id="openai_image_api_url" name="api_url" value="<?php echo $openai_image ? $openai_image['api_url'] : 'https://api.openai.com/v1/images/generations'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_model_name">模型名称</label>
                                            <input type="text" id="openai_image_model_name" name="model_name" value="<?php echo $openai_image ? $openai_image['model_name'] : 'dall-e-3'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_size">图像尺寸</label>
                                            <select id="openai_image_size" name="image_size">
                                                <option value="1024x1024" <?php echo isset($openai_image_params['image_size']) && $openai_image_params['image_size'] == '1024x1024' ? 'selected' : ''; ?>>1024x1024</option>
                                                <option value="1024x1792" <?php echo isset($openai_image_params['image_size']) && $openai_image_params['image_size'] == '1024x1792' ? 'selected' : ''; ?>>1024x1792</option>
                                                <option value="1792x1024" <?php echo isset($openai_image_params['image_size']) && $openai_image_params['image_size'] == '1792x1024' ? 'selected' : ''; ?>>1792x1024</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_quality">图像质量</label>
                                            <select id="openai_image_quality" name="image_quality">
                                                <option value="standard" <?php echo isset($openai_image_params['image_quality']) && $openai_image_params['image_quality'] == 'standard' ? 'selected' : ''; ?>>标准</option>
                                                <option value="hd" <?php echo isset($openai_image_params['image_quality']) && $openai_image_params['image_quality'] == 'hd' ? 'selected' : ''; ?>>高清</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="openai_image_style">图像风格</label>
                                            <select id="openai_image_style" name="image_style">
                                                <option value="vivid" <?php echo isset($openai_image_params['image_style']) && $openai_image_params['image_style'] == 'vivid' ? 'selected' : ''; ?>>生动</option>
                                                <option value="natural" <?php echo isset($openai_image_params['image_style']) && $openai_image_params['image_style'] == 'natural' ? 'selected' : ''; ?>>自然</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" name="save_api" class="btn-primary">保存设置</button>
                                            <button type="submit" name="test_api" class="btn-secondary">测试连接</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Stability AI设置 -->
                                <div id="tab-stability" class="tab-pane">
                                    <h3>Stability AI API设置</h3>
                                    <p>配置Stability AI API以启用AI图像生成功能</p>
                                    
                                    <form method="post" action="">
                                        <input type="hidden" name="provider" value="stability">
                                        <input type="hidden" name="api_type" value="image">
                                        
                                        <div class="form-group">
                                            <label for="stability_is_active">启用Stability AI API</label>
                                            <div class="toggle-switch">
                                                <input type="checkbox" id="stability_is_active" name="is_active" <?php echo $stability_image && $stability_image['is_active'] ? 'checked' : ''; ?>>
                                                <label for="stability_is_active"></label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="stability_api_key">API密钥</label>
                                            <input type="password" id="stability_api_key" name="api_key" value="<?php echo $stability_image ? $stability_image['api_key'] : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="stability_api_url">API端点URL</label>
                                            <input type="text" id="stability_api_url" name="api_url" value="<?php echo $stability_image ? $stability_image['api_url'] : 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="stability_model_name">模型名称</label>
                                            <input type="text" id="stability_model_name" name="model_name" value="<?php echo $stability_image ? $stability_image['model_name'] : 'stable-diffusion-xl-1024-v1-0'; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="stability_img_size">图像尺寸</label>
                                            <select id="stability_img_size" name="image_size">
                                                <option value="1024x1024" <?php echo isset($stability_image_params['image_size']) && $stability_image_params['image_size'] == '1024x1024' ? 'selected' : ''; ?>>1024x1024</option>
                                                <option value="896x1152" <?php echo isset($stability_image_params['image_size']) && $stability_image_params['image_size'] == '896x1152' ? 'selected' : ''; ?>>896x1152</option>
                                                <option value="1152x896" <?php echo isset($stability_image_params['image_size']) && $stability_image_params['image_size'] == '1152x896' ? 'selected' : ''; ?>>1152x896</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="stability_cfg_scale">CFG Scale (1-35)</label>
                                            <input type="number" id="stability_cfg_scale" name="cfg_scale" value="<?php echo isset($stability_image_params['cfg_scale']) ? $stability_image_params['cfg_scale'] : 7; ?>" min="1" max="35" step="1">
                                            <small>控制提示词对图像的影响程度</small>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="submit" name="save_api" class="btn-primary">保存设置</button>
                                            <button type="submit" name="test_api" class="btn-secondary">测试连接</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 使用帮助 -->
                        <div id="tab-help" class="tab-pane">
                            <h3>AI模型设置帮助</h3>
                            
                            <div class="help-section">
                                <h4>DeepSeek API</h4>
                                <p>DeepSeek提供强大的中文AI模型，适用于生成高质量的文本内容。</p>
                                <ol>
                                    <li>访问 <a href="https://www.deepseek.com" target="_blank">DeepSeek官网</a> 注册账号</li>
                                    <li>在个人设置中创建API密钥</li>
                                    <li>将API密钥复制到上面的设置中</li>
                                    <li>选择适合您需求的模型</li>
                                    <li>调整参数以获得最佳效果</li>
                                </ol>
                            </div>
                            
                            <div class="help-section">
                                <h4>OpenAI API</h4>
                                <p>OpenAI提供多种AI模型，包括文本生成和图像生成。</p>
                                <ol>
                                    <li>访问 <a href="https://platform.openai.com" target="_blank">OpenAI平台</a> 注册账号</li>
                                    <li>在API设置中创建API密钥</li>
                                    <li>将API密钥复制到上面的设置中</li>
                                    <li>根据需要配置文本或图像API</li>
                                </ol>
                            </div>
                            
                            <div class="help-section">
                                <h4>Stability AI</h4>
                                <p>Stability AI提供高质量的图像生成模型。</p>
                                <ol>
                                    <li>访问 <a href="https://stability.ai" target="_blank">Stability AI官网</a> 注册账号</li>
                                    <li>在开发者设置中创建API密钥</li>
                                    <li>将API密钥复制到上面的设置中</li>
                                </ol>
                            </div>
                            
                            <div class="help-section">
                                <h4>参数说明</h4>
                                <ul>
                                    <li><strong>API密钥</strong>：用于身份验证的密钥</li>
                                    <li><strong>API端点URL</strong>：API服务器地址</li>
                                    <li><strong>模型名称</strong>：要使用的AI模型</li>
                                    <li><strong>最大生成令牌数</strong>：控制生成文本的最大长度</li>
                                    <li><strong>温度</strong>：控制生成文本的随机性，值越低越确定</li>
                                    <li><strong>图像尺寸</strong>：生成图像的分辨率</li>
                                    <li><strong>图像质量</strong>：生成图像的质量级别</li>
                                    <li><strong>图像风格</strong>：生成图像的风格倾向</li>
                                </ul>
                            </div>
                            
                            <div class="help-section">
                                <h4>常见问题</h4>
                                <div class="faq-item">
                                    <h5>为什么测试连接失败？</h5>
                                    <p>可能的原因：</p>
                                    <ul>
                                        <li>API密钥不正确或已过期</li>
                                        <li>API端点URL错误</li>
                                        <li>网络连接问题</li>
                                        <li>账户余额不足</li>
                                    </ul>
                                </div>
                                
                                <div class="faq-item">
                                    <h5>如何选择合适的模型？</h5>
                                    <p>不同模型有不同的特点：</p>
                                    <ul>
                                        <li>DeepSeek-Coder：适合代码生成和技术文档</li>
                                        <li>DeepSeek-Chat：适合一般对话和内容生成</li>
                                        <li>GPT-3.5-Turbo：平衡性能和成本</li>
                                        <li>GPT-4：最高质量但成本较高</li>
                                        <li>DALL-E 3：高质量图像生成</li>
                                    </ul>
                                </div>
                                
                                <div class="faq-item">
                                    <h5>如何优化AI生成效果？</h5>
                                    <p>调整以下参数：</p>
                                    <ul>
                                        <li>降低温度值可获得更确定、更一致的输出</li>
                                        <li>增加最大令牌数可获得更长的回复</li>
                                        <li>为图像生成提供详细、具体的描述</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 其他AI模型设置 -->
                        <div id="tab-other-models" class="tab-pane">
                            <p>此处可以添加其他类型的AI模型配置，如语音识别、语音合成等。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 主标签页切换
    const mainTabBtns = document.querySelectorAll('.main-tab-btn');
    const mainTabPanes = document.querySelectorAll('.main-tab-pane');
    
    mainTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // 移除所有活动状态
            mainTabBtns.forEach(b => b.classList.remove('active'));
            mainTabPanes.forEach(p => p.classList.remove('active'));
            
            // 添加当前活动状态
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // 子标签页切换
    const subTabsContainers = document.querySelectorAll('.sub-tabs');
    
    subTabsContainers.forEach(container => {
        const subTabBtns = container.querySelectorAll('.tab-nav > .tab-btn');
        const subTabPanes = container.querySelectorAll('.tab-pane');
        
        subTabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // 移除所有活动状态
                subTabBtns.forEach(b => b.classList.remove('active'));
                subTabPanes.forEach(p => p.classList.remove('active'));
                
                // 添加当前活动状态
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
