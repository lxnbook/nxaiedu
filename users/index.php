<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

// 检查权限（只有管理员可以访问）
if ($_SESSION['user_role'] != 'admin') {
    setFlashMessage('error', '您没有权限访问此页面');
    redirect(BASE_URL . '/dashboard.php');
}

// 获取筛选参数
$role = isset($_GET['role']) ? $_GET['role'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? escapeString($_GET['search']) : '';

// 构建查询条件
$conditions = [];

if (!empty($role)) {
    $conditions[] = "role = '$role'";
}

if (!empty($department)) {
    $conditions[] = "department = '$department'";
}

if (!empty($status)) {
    $conditions[] = "status = '$status'";
}

if (!empty($search)) {
    $conditions[] = "(username LIKE '%$search%' OR name LIKE '%$search%' OR email LIKE '%$search%')";
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 获取用户列表
$sql = "SELECT * FROM users $whereClause ORDER BY name";
$users = query($sql);

// 获取部门列表
$sql = "SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department";
$departments = query($sql);

$pageTitle = '用户管理';
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
                
                <!-- 操作栏 -->
                <div class="action-bar">
                    <a href="<?php echo BASE_URL; ?>/users/create.php" class="btn-primary">
                        <i class="icon-plus"></i> 新建用户
                    </a>
                    
                    <!-- 筛选表单 -->
                    <form class="filter-form" method="get" action="">
                        <div class="form-group">
                            <select name="role" onchange="this.form.submit()">
                                <option value="">所有角色</option>
                                <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>管理员</option>
                                <option value="manager" <?php echo $role == 'manager' ? 'selected' : ''; ?>>部门经理</option>
                                <option value="teacher" <?php echo $role == 'teacher' ? 'selected' : ''; ?>>教师</option>
                                <option value="staff" <?php echo $role == 'staff' ? 'selected' : ''; ?>>工作人员</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="department" onchange="this.form.submit()">
                                <option value="">所有部门</option>
                                <?php while ($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['department']; ?>" <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo $dept['department']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="status" onchange="this.form.submit()">
                                <option value="">所有状态</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>活跃</option>
                                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>停用</option>
                            </select>
                        </div>
                        
                        <div class="form-group search-group">
                            <input type="text" name="search" placeholder="搜索用户名、姓名或邮箱" value="<?php echo $search; ?>">
                            <button type="submit" class="btn-icon"><i class="icon-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <!-- 用户列表 -->
                <div class="card">
                    <div class="card-body">
                        <?php if ($users->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>姓名</th>
                                        <th>用户名</th>
                                        <th>部门</th>
                                        <th>角色</th>
                                        <th>邮箱</th>
                                        <th>状态</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $user['name']; ?></td>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['department']; ?></td>
                                            <td>
                                                <?php 
                                                $roleLabels = [
                                                    'admin' => '系统管理员',
                                                    'manager' => '部门经理',
                                                    'teacher' => '教师',
                                                    'staff' => '工作人员'
                                                ];
                                                echo $roleLabels[$user['role']]; 
                                                ?>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <span class="status <?php echo $user['status']; ?>">
                                                    <?php echo $user['status'] == 'active' ? '活跃' : '停用'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo BASE_URL; ?>/users/edit.php?id=<?php echo $user['id']; ?>" class="btn-icon" title="编辑"><i class="icon-edit"></i></a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="<?php echo BASE_URL; ?>/users/toggle_status.php?id=<?php echo $user['id']; ?>" class="btn-icon" title="<?php echo $user['status'] == 'active' ? '停用' : '激活'; ?>">
                                                            <i class="icon-<?php echo $user['status'] == 'active' ? 'disable' : 'enable'; ?>"></i>
                                                        </a>
                                                        <a href="<?php echo BASE_URL; ?>/users/reset_password.php?id=<?php echo $user['id']; ?>" class="btn-icon" title="重置密码" onclick="return confirm('确定要重置此用户的密码吗？')"><i class="icon-reset"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">暂无用户数据</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
