<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员可以访问）
if ($_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escapeString($_POST['username']);
    $name = escapeString($_POST['name']);
    $email = escapeString($_POST['email']);
    $department = escapeString($_POST['department']);
    $role = escapeString($_POST['role']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // 验证密码
    if ($password !== $confirmPassword) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名是否已存在
        $sql = "SELECT id FROM users WHERE username = '$username'";
        $result = query($sql);
        
        if ($result->num_rows > 0) {
            $error = '用户名已存在，请选择其他用户名';
        } else {
            // 哈希密码
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // 插入用户记录
            $sql = "INSERT INTO users (username, password, name, email, department, role, status, created_at) 
                    VALUES ('$username', '$hashedPassword', '$name', '$email', '$department', '$role', 'active', NOW())";
            $userId = insert($sql);
            
            if ($userId) {
                setFlashMessage('success', '用户已成功创建！');
                redirect(BASE_URL . '/users/index.php');
            } else {
                $error = '创建用户失败，请重试';
            }
        }
    }
}

// 获取部门列表
$sql = "SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department";
$departments = query($sql);

$pageTitle = '新建用户';
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
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h2>新建用户</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="username">用户名</label>
                                <input type="text" id="username" name="username" required>
                                <small class="form-text">用户登录时使用的名称，创建后不可修改</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">姓名</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">邮箱</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="department">部门</label>
                                <input type="text" id="department" name="department" list="departments" required>
                                <datalist id="departments">
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department']; ?>">
                                    <?php endwhile; ?>
                                </datalist>
                                <small class="form-text">可以选择现有部门或输入新部门</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">角色</label>
                                <select id="role" name="role" required>
                                    <option value="staff">工作人员</option>
                                    <option value="teacher">教师</option>
                                    <option value="manager">部门经理</option>
                                    <option value="admin">系统管理员</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">密码</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">确认密码</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-actions">
                                <a href="<?php echo BASE_URL; ?>/users/index.php" class="btn-secondary">取消</a>
                                <button type="submit" class="btn-primary">创建用户</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
