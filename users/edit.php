<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员可以访问）
if ($_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 获取用户ID
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    setFlashMessage('error', '无效的用户ID');
    redirect(BASE_URL . '/users/index.php');
}

// 获取用户详情
$sql = "SELECT * FROM users WHERE id = $userId";
$result = query($sql);

if ($result->num_rows === 0) {
    setFlashMessage('error', '用户不存在');
    redirect(BASE_URL . '/users/index.php');
}

$user = $result->fetch_assoc();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = escapeString($_POST['name']);
    $email = escapeString($_POST['email']);
    $department = escapeString($_POST['department']);
    $role = escapeString($_POST['role']);
    
    // 更新用户记录
    $sql = "UPDATE users SET 
            name = '$name', 
            email = '$email', 
            department = '$department', 
            role = '$role' 
            WHERE id = $userId";
    execute($sql);
    
    setFlashMessage('success', '用户信息已成功更新！');
    redirect(BASE_URL . '/users/index.php');
}

// 获取部门列表
$sql = "SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department";
$departments = query($sql);

$pageTitle = '编辑用户';
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
                <div class="card">
                    <div class="card-header">
                        <h2>编辑用户</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="username">用户名</label>
                                <input type="text" id="username" value="<?php echo $user['username']; ?>" disabled>
                                <small class="form-text">用户名不可修改</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">姓名</label>
                                <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">邮箱</label>
                                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="department">部门</label>
                                <input type="text" id="department" name="department" list="departments" value="<?php echo $user['department']; ?>" required>
                                <datalist id="departments">
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department']; ?>">
                                    <?php endwhile; ?>
                                </datalist>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">角色</label>
                                <select id="role" name="role" required>
                                    <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>工作人员</option>
                                    <option value="teacher" <?php echo $user['role'] == 'teacher' ? 'selected' : ''; ?>>教师</option>
                                    <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>部门经理</option>
                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>系统管理员</option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <a href="<?php echo BASE_URL; ?>/users/index.php" class="btn-secondary">取消</a>
                                <button type="submit" class="btn-primary">保存更改</button>
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
