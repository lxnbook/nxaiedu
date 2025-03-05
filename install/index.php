<?php
// 检查是否已安装
if (file_exists('../config/config.php')) {
    $config = include '../config/config.php';
    if (isset($config['DB_HOST'])) {
        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装教育局项目汇报系统</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #1890ff;
        }
        .step {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .step h2 {
            margin-top: 0;
            color: #1890ff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background-color: #1890ff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #40a9ff;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #f6ffed;
            border: 1px solid #b7eb8f;
            color: #52c41a;
        }
        .alert-danger {
            background-color: #fff1f0;
            border: 1px solid #ffa39e;
            color: #f5222d;
        }
        .requirements {
            margin-bottom: 20px;
        }
        .requirement {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .requirement:last-child {
            border-bottom: none;
        }
        .status {
            font-weight: bold;
        }
        .status.pass {
            color: #52c41a;
        }
        .status.fail {
            color: #f5222d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>安装教育局项目汇报系统</h1>
        
        <?php
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db_host = $_POST['db_host'];
            $db_name = $_POST['db_name'];
            $db_user = $_POST['db_user'];
            $db_pass = $_POST['db_pass'];
            $admin_email = $_POST['admin_email'];
            $admin_password = $_POST['admin_password'];
            $site_name = $_POST['site_name'];
            
            // 验证输入
            $errors = [];
            
            if (empty($db_host)) $errors[] = "数据库主机不能为空";
            if (empty($db_name)) $errors[] = "数据库名称不能为空";
            if (empty($db_user)) $errors[] = "数据库用户名不能为空";
            if (empty($admin_email)) $errors[] = "管理员邮箱不能为空";
            if (empty($admin_password)) $errors[] = "管理员密码不能为空";
            if (empty($site_name)) $errors[] = "网站名称不能为空";
            
            // 如果没有错误，尝试连接数据库
            if (empty($errors)) {
                try {
                    $conn = new mysqli($db_host, $db_user, $db_pass);
                    
                    // 检查连接
                    if ($conn->connect_error) {
                        $errors[] = "数据库连接失败: " . $conn->connect_error;
                    } else {
                        // 创建配置文件
                        $config_content = "<?php
return [
    'DB_HOST' => '$db_host',
    'DB_NAME' => '$db_name',
    'DB_USER' => '$db_user',
    'DB_PASS' => '$db_pass',
    'SITE_NAME' => '$site_name',
    'ADMIN_EMAIL' => '$admin_email'
];
";
                        
                        // 确保config目录存在
                        if (!is_dir('../config')) {
                            mkdir('../config', 0755, true);
                        }
                        
                        // 写入配置文件
                        if (file_put_contents('../config/config.php', $config_content)) {
                            // 重定向到安装脚本
                            header('Location: install.php');
                            exit;
                        } else {
                            $errors[] = "无法写入配置文件，请确保有足够的权限";
                        }
                    }
                    
                    $conn->close();
                } catch (Exception $e) {
                    $errors[] = "发生错误: " . $e->getMessage();
                }
            }
            
            // 显示错误信息
            if (!empty($errors)) {
                echo '<div class="alert alert-danger">';
                foreach ($errors as $error) {
                    echo $error . '<br>';
                }
                echo '</div>';
            }
        }
        
        // 检查系统要求
        $requirements = [
            'PHP版本 >= 7.0' => version_compare(PHP_VERSION, '7.0.0', '>='),
            'MySQLi扩展' => extension_loaded('mysqli'),
            'PDO扩展' => extension_loaded('pdo'),
            'GD扩展' => extension_loaded('gd'),
            'config目录可写' => is_writable('../config') || is_writable('..'),
            'uploads目录可写' => is_writable('../uploads') || is_writable('..')
        ];
        
        $all_requirements_met = true;
        foreach ($requirements as $requirement => $status) {
            if (!$status) {
                $all_requirements_met = false;
                break;
            }
        }
        ?>
        
        <div class="step">
            <h2>1. 系统要求检查</h2>
            <div class="requirements">
                <?php foreach ($requirements as $requirement => $status): ?>
                <div class="requirement">
                    <span><?php echo $requirement; ?></span>
                    <span class="status <?php echo $status ? 'pass' : 'fail'; ?>">
                        <?php echo $status ? '通过' : '未通过'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$all_requirements_met): ?>
            <div class="alert alert-danger">
                您的系统不满足所有安装要求，请解决上述问题后再继续。
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($all_requirements_met): ?>
        <div class="step">
            <h2>2. 数据库和管理员设置</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_name">数据库名称</label>
                    <input type="text" id="db_name" name="db_name" value="report_system" required>
                </div>
                
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" id="db_pass" name="db_pass">
                </div>
                
                <div class="form-group">
                    <label for="site_name">网站名称</label>
                    <input type="text" id="site_name" name="site_name" value="教育局项目汇报系统" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">管理员邮箱</label>
                    <input type="text" id="admin_email" name="admin_email" value="admin@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_password">管理员密码</label>
                    <input type="password" id="admin_password" name="admin_password" value="admin123" required>
                </div>
                
                <button type="submit" class="btn">开始安装</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
