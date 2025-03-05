// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化下拉菜单
    initDropdowns();
    
    // 初始化侧边栏折叠
    initSidebar();
    
    // 初始化警告消息自动关闭
    initAlerts();
});

// 初始化下拉菜单
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.user-dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.user-dropdown-toggle');
        
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
        });
    });
    
    // 点击页面其他地方关闭下拉菜单
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
}

// 初始化侧边栏折叠
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }
}

// 初始化警告消息自动关闭
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // 添加关闭按钮
        const closeBtn = document.createElement('button');
        closeBtn.className = 'alert-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function() {
            alert.remove();
        });
        alert.appendChild(closeBtn);
        
        // 5秒后自动关闭
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// 格式化文件大小
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
