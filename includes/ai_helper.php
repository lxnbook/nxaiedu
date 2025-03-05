<?php
/**
 * AI辅助功能类
 * 用于处理与AI模型的交互
 */
class AIHelper {
    private $pdo;
    private $text_api_settings;
    private $image_api_settings;
    
    /**
     * 构造函数
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadApiSettings();
    }
    
    /**
     * 加载API设置
     */
    private function loadApiSettings() {
        // 加载文本API设置
        $stmt = $this->pdo->query("SELECT * FROM ai_api_settings WHERE api_type = 'text' AND is_active = 1 LIMIT 1");
        $this->text_api_settings = $stmt->fetch();
        
        // 加载图像API设置
        $stmt = $this->pdo->query("SELECT * FROM ai_api_settings WHERE api_type = 'image' AND is_active = 1 LIMIT 1");
        $this->image_api_settings = $stmt->fetch();
    }
    
    /**
     * 检查API是否已配置
     */
    public function isConfigured() {
        return !empty($this->text_api_settings);
    }
    
    /**
     * 检查图像API是否已配置
     */
    public function isImageConfigured() {
        return !empty($this->image_api_settings);
    }
    
    /**
     * 生成报告内容
     */
    public function generateReportContent($prompt) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $messages = [
            ['role' => 'system', 'content' => '你是一个专业的教育项目汇报助手，擅长生成高质量的教育相关报告内容。请根据用户的提示生成内容。'],
            ['role' => 'user', 'content' => $prompt]
        ];
        
        return $this->callTextApi($messages);
    }
    
    /**
     * 优化报告内容
     */
    public function improveReportContent($content, $instruction) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $messages = [
            ['role' => 'system', 'content' => '你是一个专业的教育项目汇报助手，擅长优化和改进报告内容。请根据用户的指示优化提供的内容。'],
            ['role' => 'user', 'content' => "请根据以下指示优化内容：\n\n指示：$instruction\n\n内容：$content"]
        ];
        
        return $this->callTextApi($messages);
    }
    
    /**
     * 生成提示词模板
     */
    public function generatePromptTemplate($title, $category = '') {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $categoryText = $category ? "类别：$category" : "";
        
        $messages = [
            ['role' => 'system', 'content' => '你是一个专业的教育项目汇报助手，擅长创建提示词模板。请根据用户提供的标题和类别生成一个详细的提示词模板。'],
            ['role' => 'user', 'content' => "请为以下标题创建一个详细的提示词模板：\n\n标题：$title\n$categoryText\n\n模板应该包含详细的结构和指导，帮助用户生成高质量的教育相关报告。"]
        ];
        
        return $this->callTextApi($messages);
    }
    
    /**
     * 生成图像
     */
    public function generateImage($prompt, $size = '1024x1024') {
        if (!$this->isImageConfigured()) {
            return ['success' => false, 'message' => '图像生成API未配置'];
        }
        
        $provider = $this->image_api_settings['provider'];
        
        switch ($provider) {
            case 'openai':
                return $this->generateOpenAIImage($prompt, $size);
            case 'stability':
                return $this->generateStabilityImage($prompt, $size);
            default:
                return ['success' => false, 'message' => '不支持的图像生成提供商'];
        }
    }
    
    /**
     * 生成OpenAI图像
     */
    private function generateOpenAIImage($prompt, $size) {
        $api_key = $this->image_api_settings['api_key'];
        $api_url = $this->image_api_settings['api_url'];
        
        $params = [
            'model' => $this->image_api_settings['model_name'],
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'response_format' => 'url'
        ];
        
        // 添加额外参数
        if (!empty($this->image_api_settings['additional_params'])) {
            $additional_params = json_decode($this->image_api_settings['additional_params'], true);
            if (isset($additional_params['image_quality'])) {
                $params['quality'] = $additional_params['image_quality'];
            }
            if (isset($additional_params['image_style'])) {
                $params['style'] = $additional_params['image_style'];
            }
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        
        $response = $this->makeHttpRequest($api_url, json_encode($params), $headers);
        
        if ($response['success']) {
            $result = json_decode($response['content'], true);
            if (isset($result['data'][0]['url'])) {
                return ['success' => true, 'image_url' => $result['data'][0]['url']];
            } else {
                return ['success' => false, 'message' => '无效的响应格式: ' . $response['content']];
            }
        } else {
            return $response;
        }
    }
    
    /**
     * 生成Stability AI图像
     */
    private function generateStabilityImage($prompt, $size) {
        $api_key = $this->image_api_settings['api_key'];
        $api_url = $this->image_api_settings['api_url'];
        
        // 解析尺寸
        list($width, $height) = explode('x', $size);
        
        $params = [
            'text_prompts' => [
                ['text' => $prompt]
            ],
            'cfg_scale' => 7,
            'height' => (int)$height,
            'width' => (int)$width,
            'samples' => 1
        ];
        
        // 添加额外参数
        if (!empty($this->image_api_settings['additional_params'])) {
            $additional_params = json_decode($this->image_api_settings['additional_params'], true);
            if (isset($additional_params['cfg_scale'])) {
                $params['cfg_scale'] = (int)$additional_params['cfg_scale'];
            }
        }
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ];
        
        $response = $this->makeHttpRequest($api_url, json_encode($params), $headers);
        
        if ($response['success']) {
            $result = json_decode($response['content'], true);
            if (isset($result['artifacts'][0]['base64'])) {
                $image_data = $result['artifacts'][0]['base64'];
                
                // 保存图像到服务器
                $upload_dir = '../uploads/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = 'ai_image_' . time() . '.png';
                $filepath = $upload_dir . $filename;
                
                file_put_contents($filepath, base64_decode($image_data));
                
                return [
                    'success' => true, 
                    'image_url' => BASE_URL . '/uploads/images/' . $filename
                ];
            } else {
                return ['success' => false, 'message' => '无效的响应格式: ' . $response['content']];
            }
        } else {
            return $response;
        }
    }
    
    /**
     * 调用文本API
     */
    private function callTextApi($messages) {
        $api_key = $this->text_api_settings['api_key'];
        $api_url = $this->text_api_settings['api_url'];
        $model = $this->text_api_settings['model_name'];
        $max_tokens = $this->text_api_settings['max_tokens'];
        $temperature = $this->text_api_settings['temperature'];
        
        $params = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        
        $response = $this->makeHttpRequest($api_url, json_encode($params), $headers);
        
        if (!$response['success']) {
            return $response;
        }
        
        $result = json_decode($response['content'], true);
        if (isset($result['choices'][0]['message']['content'])) {
            return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
        } else {
            return ['success' => false, 'message' => '无效的响应格式: ' . $response['content']];
        }
    }
    
    /**
     * 发送HTTP请求
     */
    private function makeHttpRequest($url, $data, $headers = []) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'cURL错误: ' . $error];
        }
        
        curl_close($ch);
        
        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true, 'content' => $response];
        } else {
            return ['success' => false, 'message' => 'HTTP错误: ' . $http_code . ' - ' . $response];
        }
    }
}
