<?php
require_once '../config/config.php';
require_once '../includes/ai_helper.php';

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

// 需要登录
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未授权']);
    exit;
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求数据']);
    exit;
}

// 初始化AI助手
$aiHelper = new AIHelper($pdo);

// 检查AI API是否已配置
if (!$aiHelper->isConfigured()) {
    echo json_encode(['success' => false, 'message' => 'AI API未配置，请联系管理员']);
    exit;
}

// 根据操作类型处理请求
switch ($data['action']) {
    case 'generate':
        // 生成内容
        if (empty($data['prompt'])) {
            echo json_encode(['success' => false, 'message' => '提示词不能为空']);
            exit;
        }
        
        $result = $aiHelper->generateReportContent($data['prompt']);
        echo json_encode($result);
        break;
        
    case 'improve':
        // 优化内容
        if (empty($data['content'])) {
            echo json_encode(['success' => false, 'message' => '内容不能为空']);
            exit;
        }
        
        if (empty($data['instruction'])) {
            echo json_encode(['success' => false, 'message' => '优化指令不能为空']);
            exit;
        }
        
        $result = $aiHelper->improveReportContent($data['content'], $data['instruction']);
        echo json_encode($result);
        break;
        
    case 'generate_prompt':
        // 生成提示词模板
        if (empty($data['title'])) {
            echo json_encode(['success' => false, 'message' => '标题不能为空']);
            exit;
        }
        
        $category = $data['category'] ?? '';
        $result = $aiHelper->generatePromptTemplate($data['title'], $category);
        echo json_encode($result);
        break;
        
    case 'generate_image':
        // 生成图像
        if (empty($data['prompt'])) {
            echo json_encode(['success' => false, 'message' => '图像描述不能为空']);
            exit;
        }
        
        $size = $data['size'] ?? '1024x1024';
        $result = $aiHelper->generateImage($data['prompt'], $size);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}
