<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="教育局项目汇报系统" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-logo.png'">
            <h2>教育局汇报系统</h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <span></span>
        </button>
    </div>
    
    <div class="user-profile">
        <img src="<?php echo BASE_URL; ?>/assets/images/avatar.png" alt="用户头像" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
        <div class="user-info">
            <h3><?php echo $_SESSION['user_name']; ?></h3>
            <p><?php 
                $roleLabels = [
                    'admin' => '系统管理员',
                    'manager' => '部门经理',
                    'teacher' => '教师',
                    'staff' => '工作人员'
                ];
                echo $roleLabels[$_SESSION['user_role']]; 
            ?></p>
        </div>
    </div>
    
    <nav class="main-nav">
        <ul>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/dashboard.php">
                    <i class="icon-dashboard"></i> 
                    <span>仪表盘</span>
                </a>
            </li>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/reports/index.php">
                    <i class="icon-reports"></i> 
                    <span>项目汇报</span>
                </a>
            </li>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/prompts/') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/prompts/index.php">
                    <i class="icon-prompts"></i> 
                    <span>提示词库</span>
                </a>
            </li>
            <?php if ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'manager'): ?>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/statistics/') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/statistics/index.php">
                    <i class="icon-stats"></i> 
                    <span>数据统计</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/users/index.php">
                    <i class="icon-users"></i> 
                    <span>用户管理</span>
                </a>
            </li>
            <li class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/settings/index.php" class="menu-link">
                    <i class="icon-settings"></i>
                    <span>系统设置</span>
                </a>
                <ul class="submenu">
                    <li><a href="<?php echo BASE_URL; ?>/settings/general.php">基本设置</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/settings/ai_settings.php">AI模型设置</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/settings/email.php">邮件设置</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
