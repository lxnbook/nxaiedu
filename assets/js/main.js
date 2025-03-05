// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化警告消息自动关闭
    initAlerts();
});

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
