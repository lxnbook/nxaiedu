<?php
// 数据库配置
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'report_system';

// 创建数据库连接
$conn = new mysqli($db_host, $db_user, $db_pass);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 创建数据库
$sql = "CREATE DATABASE IF NOT EXISTS $db_name DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "数据库创建成功或已存在<br>";
} else {
    echo "创建数据库错误: " . $conn->error . "<br>";
    exit;
}

// 选择数据库
$conn->select_db($db_name);

// 创建用户表
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'teacher', 'staff') NOT NULL DEFAULT 'staff',
    department VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "用户表创建成功<br>";
} else {
    echo "创建用户表错误: " . $conn->error . "<br>";
}

// 创建汇报表
$sql = "CREATE TABLE IF NOT EXISTS reports (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    author_id INT(11) UNSIGNED NOT NULL,
    reviewer_id INT(11) UNSIGNED DEFAULT NULL,
    review_comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at DATETIME DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "汇报表创建成功<br>";
} else {
    echo "创建汇报表错误: " . $conn->error . "<br>";
}

// 创建附件表
$sql = "CREATE TABLE IF NOT EXISTS attachments (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT(11) UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    filesize INT(11) UNSIGNED NOT NULL,
    filetype VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "附件表创建成功<br>";
} else {
    echo "创建附件表错误: " . $conn->error . "<br>";
}

// 创建提示词表
$sql = "CREATE TABLE IF NOT EXISTS prompts (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    author_id INT(11) UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "提示词表创建成功<br>";
} else {
    echo "创建提示词表错误: " . $conn->error . "<br>";
}

// 创建设置表
$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "设置表创建成功<br>";
} else {
    echo "创建设置表错误: " . $conn->error . "<br>";
}

// 创建通知表
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "通知表创建成功<br>";
} else {
    echo "创建通知表错误: " . $conn->error . "<br>";
}

// 创建AI API设置表
$sql = "CREATE TABLE IF NOT EXISTS ai_api_settings (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    api_type ENUM('text', 'image', 'embedding', 'other') NOT NULL DEFAULT 'text',
    api_key VARCHAR(255) NOT NULL,
    api_url VARCHAR(255) NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    max_tokens INT(11) DEFAULT 2000,
    temperature FLOAT DEFAULT 0.7,
    additional_params TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY provider_type (provider, api_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "AI API设置表创建成功<br>";
} else {
    echo "创建AI API设置表错误: " . $conn->error . "<br>";
}

// 插入默认管理员账户
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (name, email, password, role, department, status) 
        VALUES ('管理员', 'admin@example.com', '$admin_password', 'admin', '系统管理部', 'active')";

if ($conn->query($sql) === TRUE) {
    echo "默认管理员账户创建成功<br>";
} else {
    // 如果插入失败，可能是因为邮箱已存在，尝试更新密码
    if ($conn->errno == 1062) { // 1062 是重复键错误
        $sql = "UPDATE users SET password = '$admin_password' WHERE email = 'admin@example.com'";
        if ($conn->query($sql) === TRUE) {
            echo "默认管理员账户密码已更新<br>";
        } else {
            echo "更新管理员账户错误: " . $conn->error . "<br>";
        }
    } else {
        echo "创建管理员账户错误: " . $conn->error . "<br>";
    }
}

// 插入默认设置
$default_settings = [
    ['site_name', '教育局项目汇报系统', '网站名称'],
    ['site_description', '高效管理和提交各类教育项目汇报', '网站描述'],
    ['allow_registration', '1', '是否允许用户注册'],
    ['default_user_role', 'staff', '默认用户角色'],
    ['items_per_page', '10', '每页显示项目数'],
    ['allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar', '允许上传的文件类型'],
    ['max_file_size', '10485760', '最大文件大小（字节）'],
    ['enable_email_notification', '0', '是否启用邮件通知'],
    ['smtp_host', '', 'SMTP服务器地址'],
    ['smtp_port', '587', 'SMTP服务器端口'],
    ['smtp_username', '', 'SMTP用户名'],
    ['smtp_password', '', 'SMTP密码'],
    ['smtp_from_email', '', '发件人邮箱'],
    ['smtp_from_name', '', '发件人名称']
];

foreach ($default_settings as $setting) {
    $key = $setting[0];
    $value = $setting[1];
    $description = $setting[2];
    
    $sql = "INSERT INTO settings (setting_key, setting_value, setting_description) 
            VALUES ('$key', '$value', '$description')
            ON DUPLICATE KEY UPDATE setting_value = '$value'";
    
    if ($conn->query($sql) === TRUE) {
        echo "设置 '$key' 已插入或更新<br>";
    } else {
        echo "插入设置 '$key' 错误: " . $conn->error . "<br>";
    }
}

// 插入示例提示词
$example_prompts = [
    [
        '月度工作汇报模板',
        "# 月度工作汇报\n\n## 本月工作概述\n\n[在此简要概述本月的主要工作内容和成果]\n\n## 具体工作内容\n\n### 1. [工作项目一]\n- 完成情况：\n- 存在问题：\n- 解决方案：\n\n### 2. [工作项目二]\n- 完成情况：\n- 存在问题：\n- 解决方案：\n\n## 下月工作计划\n\n1. [计划项目一]\n2. [计划项目二]\n\n## 工作总结与反思\n\n[在此总结本月工作的经验和教训，以及对未来工作的思考]",
        '工作汇报',
        1
    ],
    [
        '教学活动总结模板',
        "# 教学活动总结\n\n## 活动基本信息\n\n- 活动名称：\n- 活动时间：\n- 参与人员：\n- 活动地点：\n\n## 活动目标\n\n[描述本次教学活动的预期目标]\n\n## 活动过程\n\n[详细描述活动的实施过程，可分为准备阶段、实施阶段和总结阶段]\n\n## 活动成效\n\n[描述活动取得的成果和效果，可以包括学生反馈、教学目标达成情况等]\n\n## 问题与反思\n\n[分析活动中存在的问题，并进行反思]\n\n## 改进建议\n\n[针对问题提出具体的改进建议]",
        '教学活动',
        1
    ],
    [
        '项目进展汇报模板',
        "# 项目进展汇报\n\n## 项目基本信息\n\n- 项目名称：\n- 项目负责人：\n- 汇报时间段：\n\n## 项目进展概述\n\n[简要概述当前项目的整体进展情况]\n\n## 已完成工作\n\n1. [工作项一]\n   - 完成时间：\n   - 完成情况：\n\n2. [工作项二]\n   - 完成时间：\n   - 完成情况：\n\n## 进行中工作\n\n1. [工作项一]\n   - 当前进度：\n   - 预计完成时间：\n   - 存在问题：\n\n## 风险与挑战\n\n[描述项目面临的主要风险和挑战，以及应对措施]\n\n## 下阶段计划\n\n[详细说明下一阶段的工作计划和目标]",
        '项目汇报',
        1
    ]
];

foreach ($example_prompts as $prompt) {
    $title = $prompt[0];
    $content = $prompt[1];
    $category = $prompt[2];
    $is_public = $prompt[3];
    
    $sql = "INSERT INTO prompts (title, content, category, is_public, author_id) 
            VALUES ('$title', '$content', '$category', $is_public, 1)";
    
    if ($conn->query($sql) === TRUE) {
        echo "示例提示词 '$title' 已插入<br>";
    } else {
        echo "插入示例提示词 '$title' 错误: " . $conn->error . "<br>";
    }
}

echo "<br>数据库初始化完成！<br>";
echo "<a href='../index.php'>返回首页</a>";

// 关闭连接
$conn->close();
?>
