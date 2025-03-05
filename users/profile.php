<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 获取当前用户信息
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = $userId";
$result = query($sql);
$user = $result->fetch_assoc();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理个人信息更新
    if (isset($_POST['update_profile'])) {
        $name = escapeString($_POST['name']);
        $email = escapeString($_POST['email']);
        
        $sql = "UPDATE users SET name = '$name', email = '$email' WHERE id = $userId";
        execute($sql);
        
        // 更新会话中的用户名
        $_SESSION['user_name'] = $name;
        
        setFlashMessage('success', '个人信息已成功更新！');
        redirect(BASE_URL . '/users/profile.php');
    }
    
    // 处理密码更新
    if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // 验证当前密码
        if (!password_verify($currentPassword, $user['password'])) {
            $passwordError = '当前密码不正确';
        } 
        // 验证新密码
        else if ($newPassword !== $confirmPassword) {
            $passwordError = '两次输入的新密码不一致';
        } 
        // 更新密码
        else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
            execute($sql);
            
            setFlashMessage('success', '密码已成功更新！');
            redirect(BASE_URL . '/users/profile.php');
        }
    }
}

$pageTitle = '个人资料';
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
                <?php if ($flashMessage = getFlashMessage()): ?>
                    <div class="alert alert-<?php echo $flashMessage['type']; ?>">
                        <?php echo $flashMessage['message']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <!-- 个人信息卡片 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>个人信息</h2>
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
                                    <input type="text" id="department" value="<?php echo $user['department']; ?>" disabled>
                                    <small class="form-text">部门信息需联系管理员修改</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">角色</label>
                                    <input type="text" id="role" value="<?php 
                                        $roleLabels = [
                                            'admin' => '系统管理员',
                                            'manager' => '部门经理',
                                            'teacher' => '教师',
                                            'staff' => '工作人员'
                                        ];
                                        echo $roleLabels[$user['role']]; 
                                    ?>" disabled>
                                    <small class="form-text">角色信息需联系管理员修改</small>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn-primary">更新个人信息</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 修改密码卡片 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>修改密码</h2>
                        </div>
                        <div class="card-body">
                            <?php if (isset($passwordError)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $passwordError; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="current_password">当前密码</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">新密码</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">确认新密码</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_password" class="btn-primary">更新密码</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 账户统计卡片 -->
                    <div class="card">
                        <div class="card-header">
                            <h2>账户统计</h2>
                        </div>
                        <div class="card-body">
                            <?php
                            // 获取用户汇报统计
                            $sql = "SELECT 
                                    COUNT(*) as total,
                                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                                FROM reports 
                                WHERE user_id = $userId";
                            $reportStats = query($sql)->fetch_assoc();
                            
                            // 获取用户提示词统计
                            $sql = "SELECT 
                                    COUNT(*) as total,
                                    SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) as public
                                FROM prompts 
                                WHERE created_by = $userId";
                            $promptStats = query($sql)->fetch_assoc();
                            ?>
                            
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-label">汇报总数</div>
                                    <div class="stat-value"><?php echo $reportStats['total']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">草稿</div>
                                    <div class="stat-value"><?php echo $reportStats['draft']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">已提交</div>
                                    <div class="stat-value"><?php echo $reportStats['submitted']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">已批准</div>
                                    <div class="stat-value"><?php echo $reportStats['approved']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">已退回</div>
                                    <div class="stat-value"><?php echo $reportStats['rejected']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">提示词总数</div>
                                    <div class="stat-value"><?php echo $promptStats['total']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">公开提示词</div>
                                    <div class="stat-value"><?php echo $promptStats['public']; ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">注册时间</div>
                                    <div class="stat-value"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
