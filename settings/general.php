<?php
require_once '../config/config.php';

// 需要管理员权限
requireAdmin();

$pageTitle = '基本设置';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取所有设置
    $settings = [
        'site_name' => $_POST['site_name'],
        'site_description' => $_POST['site_description'],
        'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
        'default_user_role' => $_POST['default_user_role'],
        'items_per_page' => (int)$_POST['items_per_page'],
        'allowed_file_types' => $_POST['allowed_file_types'],
        'max_file_size' => (int)$_POST['max_file_size']
    ];
    
    // 更新设置
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    // 处理网站Logo上传
    if (!empty($_FILES['site_logo']['name'])) {
        $upload_dir = '../assets/images/';
        $file_ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = 'logo.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_path)) {
                // 更新logo设置
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
                $stmt->execute([$new_filename]);
            }
        }
    }
    
    setFlashMessage('success', '设置已成功更新');
    redirect($_SERVER['PHP_SELF']);
}

// 获取当前设置
$stmt = $pdo->query("SELECT * FROM settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 设置默认值
$settings = [
    'site_name' => $settings_data['site_name'] ?? '教育局项目汇报系统',
    'site_description' => $settings_data['site_description'] ?? '高效管理和提交各类教育项目汇报',
    'allow_registration' => $settings_data['allow_registration'] ?? 1,
    'default_user_role' => $settings_data['default_user_role'] ?? 'staff',
    'items_per_page' => $settings_data['items_per_page'] ?? 10,
    'allowed_file_types' => $settings_data['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar',
    'max_file_size' => $settings_data['max_file_size'] ?? 10485760,
    'site_logo' => $settings_data['site_logo'] ?? 'logo.png'
];

include_once '../includes/header.php';
include_once '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $pageTitle; ?></h1>
            <p>配置系统的基本设置</p>
        </div>
        
        <?php include_once '../includes/flash_messages.php'; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="post" action="" enctype="multipart/form-data">
                    <h3>网站信息</h3>
                    <div class="form-group">
                        <label for="site_name">网站名称</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">网站描述</label>
                        <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_logo">网站Logo</label>
                        <div class="logo-preview">
                            <img src="<?php echo BASE_URL; ?>/assets/images/<?php echo $settings['site_logo']; ?>" alt="网站Logo" style="max-width: 200px; max-height: 100px;">
                        </div>
                        <input type="file" id="site_logo" name="site_logo" accept="image/*">
                        <small>推荐尺寸: 200x50 像素</small>
                    </div>
                    
                    <h3>用户设置</h3>
                    <div class="form-group">
                        <label for="allow_registration">允许用户注册</label>
                        <div class="toggle-switch">
                            <input type="checkbox" id="allow_registration" name="allow_registration" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>>
                            <label for="allow_registration"></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="default_user_role">默认用户角色</label>
                        <select id="default_user_role" name="default_user_role">
                            <option value="staff" <?php echo $settings['default_user_role'] == 'staff' ? 'selected' : ''; ?>>工作人员</option>
                            <option value="teacher" <?php echo $settings['default_user_role'] == 'teacher' ? 'selected' : ''; ?>>教师</option>
                        </select>
                    </div>
                    
                    <h3>内容设置</h3>
                    <div class="form-group">
                        <label for="items_per_page">每页显示项目数</label>
                        <input type="number" id="items_per_page" name="items_per_page" value="<?php echo $settings['items_per_page']; ?>" min="5" max="100" required>
                    </div>
                    
                    <h3>文件上传设置</h3>
                    <div class="form-group">
                        <label for="allowed_file_types">允许上传的文件类型</label>
                        <input type="text" id="allowed_file_types" name="allowed_file_types" value="<?php echo htmlspecialchars($settings['allowed_file_types']); ?>" required>
                        <small>用逗号分隔的文件扩展名列表</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_file_size">最大文件大小 (字节)</label>
                        <input type="number" id="max_file_size" name="max_file_size" value="<?php echo $settings['max_file_size']; ?>" min="1048576" required>
                        <small>1MB = 1048576字节</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">保存设置</button>
                        <a href="<?php echo BASE_URL; ?>/settings/index.php" class="btn-secondary">返回</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
