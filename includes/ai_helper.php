<?php
/**
 * AI辅助功能类
 * 用于处理与AI模型的交互
 */
class AIHelper {
    private $pdo;
    private $api_settings;
    
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
        $stmt = $this->pdo->query("SELECT * FROM ai_api_settings WHERE is_active = 1 LIMIT 1");
        $this->api_settings = $stmt->fetch();
    }
    
    /**
     * 检查API是否已配置
     */
    public function isConfigured() {
        return !empty($this->api_settings);
    }
    
    /**
     * 获取当前使用的AI提供商
     */
    public function getProvider() {
        return $this->api_settings ? $this->api_settings['provider'] : null;
    }
    
    /**
     * 生成汇报内容
     * 
     * @param string $prompt 提示词
     * @param array $params 额外参数
     * @return array 包含成功状态和内容的数组
     */
    public function generateReportContent($prompt, $params = []) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $system_message = "你是一个专业的教育项目汇报助手，擅长编写清晰、专业、详细的教育项目汇报。请根据用户的要求，生成符合教育行业规范的汇报内容。";
        
        return $this->callApi($system_message, $prompt, $params);
    }
    
    /**
     * 优化汇报内容
     * 
     * @param string $content 原始内容
     * @param string $instruction 优化指令
     * @return array 包含成功状态和内容的数组
     */
    public function improveReportContent($content, $instruction) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $system_message = "你是一个专业的教育项目汇报编辑，擅长优化和改进汇报内容。请根据用户的指令，优化下面的汇报内容，使其更加专业、清晰和有说服力。";
        
        $prompt = "请按照以下指令优化这份汇报内容：\n\n指令：{$instruction}\n\n原始内容：\n{$content}";
        
        return $this->callApi($system_message, $prompt);
    }
    
    /**
     * 生成提示词模板
     * 
     * @param string $title 提示词标题
     * @param string $category 提示词类别
     * @return array 包含成功状态和内容的数组
     */
    public function generatePromptTemplate($title, $category) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'AI API未配置'];
        }
        
        $system_message = "你是一个专业的教育项目汇报模板设计师，擅长创建结构清晰、内容全面的汇报模板。";
        
        $prompt = "请为以下教育汇报创建一个详细的模板：\n标题：{$title}\n类别：{$category}\n\n模板应包含适当的标题、小标题和提示性文字，帮助用户填写完整的汇报内容。";
        
        return $this->callApi($system_message, $prompt);
    }
    
    /**
     * 调用AI API
     * 
     * @param string $system_message 系统消息
     * @param string $user_message 用户消息
     * @param array $params 额外参数
     * @return array 包含成功状态和内容的数组
     */
    private function callApi($system_message, $user_message, $params = []) {
        $provider = $this->api_settings['provider'];
        $api_key = $this->api_settings['api_key'];
        $api_url = $this->api_settings['api_url'];
        $model_name = $this->api_settings['model_name'];
        $max_tokens = isset($params['max_tokens']) ? $params['max_tokens'] : $this->api_settings['max_tokens'];
        $temperature = isset($params['temperature']) ? $params['temperature'] : $this->api_settings['temperature'];
        
        // 根据不同的提供商使用不同的API调用方法
        if ($provider === 'deepseek') {
            return $this->callDeepseekApi($api_key, $api_url, $model_name, $system_message, $user_message, $max_tokens, $temperature);
        } else {
            return ['success' => false, 'message' => '不支持的API提供商: ' . $provider];
        }
    }
    
    /**
     * 调用DeepSeek API
     */
    private function callDeepseekApi($api_key, $api_url, $model_name, $system_message, $user_message, $max_tokens, $temperature) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        
        $data = [
            'model' => $model_name,
            'messages' => [
                ['role' => 'system', 'content' => $system_message],
                ['role' => 'user', 'content' => $user_message]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
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
            return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
        } else {
            return ['success' => false, 'message' => '无效的响应格式: ' . $response];
        }
    }
}
