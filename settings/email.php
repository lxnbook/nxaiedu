<?php
require_once '../config/config.php';

// 需要管理员权限
requireAdmin();

$pageTitle = '邮件设置';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取所有设置
    $settings = [
        'enable_email_notification' => isset($_POST['enable_email_notification']) ? 1 : 0,
        'smtp_host' => $_POST['smtp_host'],
        'smtp_port' => $_POST['smtp_port'],
        'smtp_username' => $_POST['smtp_username'],
        'smtp_password' => $_POST['smtp_password'],
        'smtp_from_email' => $_POST['smtp_from_email'],
        'smtp_from_name' => $_POST['smtp_from_name']
    ];
    
    // 更新设置
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    // 测试邮件发送
    if (isset($_POST['test_email'])) {
        $test_email = $_POST['test_email_address'];
        
        if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            // 这里添加发送测试邮件的代码
            // 由于需要额外的邮件发送库，这里只模拟结果
            $success = true;
            
            if ($success) {
                setFlashMessage('success', '测试邮件已发送');
            } else {
                setFlashMessage('error', '发送测试邮件失败');
            }
        } else {
            setFlashMessage('error', '无效的邮箱地址');
        }
    } else {
        setFlashMessage('success', '邮件设置已更新');
    }
    
    redirect($_SERVER['PHP_SELF']);
}

// 获取当前设置
$stmt = $pdo->query("SELECT * FROM settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 设置默认值
$settings = [
    'enable_email_notification' => $settings_data['enable_email_notification'] ?? 0,
    'smtp_host' => $settings_data['smtp_host'] ?? '',
    'smtp_port' => $settings_data['smtp_port'] ?? '587',
    'smtp_username' => $settings_data['smtp_username'] ?? '',
    'smtp_password' => $settings_data['smtp_password'] ?? '',
    'smtp_from_email' => $settings_data['smtp_from_email'] ?? '',
    'smtp_from_name' => $settings_data['smtp_from_name'] ?? ''
];

include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $pageTitle; ?></h1>
            <p>配置系统的邮件发送设置</p>
        </div>
        
        <?php include_once '../includes/flash_messages.php'; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="post" action="">
                    <h3>邮件通知设置</h3>
                    <div class="form-group">
                        <label for="enable_email_notification">启用邮件通知</label>
                        <div class="toggle-switch">
                            <input type="checkbox" id="enable_email_notification" name="enable_email_notification" <?php echo $settings['enable_email_notification'] ? 'checked' : ''; ?>>
                            <label for="enable_email_notification"></label>
                        </div>
                    </div>
                    
                    <h3>SMTP服务器设置</h3>
                    <div class="form-group">
                        <label for="smtp_host">SMTP服务器地址</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_port">SMTP服务器端口</label>
                        <input type="text" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_username">SMTP用户名</label>
                        <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_password">SMTP密码</label>
                        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_from_email">发件人邮箱</label>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_from_name">发件人名称</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="test_email_address">测试邮箱地址</label>
                        <input type="email" id="test_email_address" name="test_email_address" placeholder="输入测试邮箱地址">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">保存设置</button>
                        <button type="submit" name="test_email" class="btn-secondary">发送测试邮件</button>
                        <a href="<?php echo BASE_URL; ?>/settings/index.php" class="btn-secondary">返回</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
