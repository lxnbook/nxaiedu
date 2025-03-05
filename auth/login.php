<?php
require_once '../config/config.php';

// 如果已经登录，重定向到首页
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escapeString($_POST['username']);
    $password = $_POST['password'];
    
    // 验证用户名和密码
    $sql = "SELECT id, username, password, name, role FROM users WHERE username = '$username' AND status = 'active'";
    $result = query($sql);
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 验证密码 (使用password_verify函数验证哈希密码)
        if (password_verify($password, $user['password'])) {
            // 登录成功，设置会话
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // 更新最后登录时间
            $userId = $user['id'];
            execute("UPDATE users SET last_login = NOW() WHERE id = $userId");
            
            // 重定向到首页
            setFlashMessage('success', '登录成功，欢迎回来！');
            redirect(BASE_URL . '/index.php');
        } else {
            $error = '密码不正确';
        }
    } else {
        $error = '用户名不存在或账号已被禁用';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 教育局项目汇报系统</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="教育局项目汇报系统" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-logo.png'">
                <h1>教育局项目汇报系统</h1>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="请输入用户名" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" placeholder="请输入密码" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">登录</button>
                    <a href="#" class="forgot-password">忘记密码?</a>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
