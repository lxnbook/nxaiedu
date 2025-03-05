<header class="header">
    <div class="header-left">
        <h1 class="page-title"><?php echo isset($pageTitle) ? $pageTitle : '教育局项目汇报系统'; ?></h1>
    </div>
    <div class="header-right">
        <div class="user-dropdown">
            <div class="user-dropdown-toggle">
                <img src="<?php echo BASE_URL; ?>/assets/images/avatar.png" alt="用户头像" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
                <span><?php echo $_SESSION['user_name']; ?></span>
                <i class="icon-dropdown"></i>
            </div>
            <div class="user-dropdown-menu">
                <a href="<?php echo BASE_URL; ?>/users/profile.php">
                    <i class="icon-profile"></i> 个人资料
                </a>
                <a href="<?php echo BASE_URL; ?>/auth/logout.php">
                    <i class="icon-logout"></i> 退出登录
                </a>
            </div>
        </div>
    </div>
</header>
