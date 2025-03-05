document.addEventListener('DOMContentLoaded', function() {
    // 侧边栏折叠/展开
    const menuItems = document.querySelectorAll('.menu-item.has-submenu');
    
    menuItems.forEach(item => {
        const link = item.querySelector('.menu-link');
        
        link.addEventListener('click', function(e) {
            e.preventDefault();
            item.classList.toggle('open');
        });
    });
    
    // 移动端侧边栏切换
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // 用户下拉菜单
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userDropdown) {
        const userInfo = userDropdown.querySelector('.user-info');
        
        userInfo.addEventListener('click', function(e) {
            e.preventDefault();
            userDropdown.classList.toggle('open');
        });
        
        // 点击外部关闭下拉菜单
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }
    
    // 提示框自动关闭
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // 文件上传预览
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileList = this.files;
            const previewContainer = this.parentElement.querySelector('.file-preview');
            
            if (previewContainer) {
                previewContainer.innerHTML = '';
                
                for (let i = 0; i < fileList.length; i++) {
                    const file = fileList[i];
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    
                    // 显示文件名和大小
                    fileItem.innerHTML = `
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${formatFileSize(file.size)}</span>
                    `;
                    
                    previewContainer.appendChild(fileItem);
                }
            }
        });
    });
    
    // 格式化文件大小
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // 表格排序
    const sortableHeaders = document.querySelectorAll('th.sortable');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const index = Array.from(this.parentElement.children).indexOf(this);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAsc = this.classList.contains('asc');
            
            // 移除所有排序类
            sortableHeaders.forEach(h => {
                h.classList.remove('asc', 'desc');
            });
            
            // 添加新的排序类
            this.classList.add(isAsc ? 'desc' : 'asc');
            
            // 排序行
            rows.sort((a, b) => {
                const aValue = a.children[index].textContent.trim();
                const bValue = b.children[index].textContent.trim();
                
                // 尝试数字排序
                const aNum = parseFloat(aValue);
                const bNum = parseFloat(bValue);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? bNum - aNum : aNum - bNum;
                }
                
                // 字符串排序
                return isAsc ? 
                    bValue.localeCompare(aValue, 'zh-CN') : 
                    aValue.localeCompare(bValue, 'zh-CN');
            });
            
            // 重新添加排序后的行
            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
        });
    });
    
    // 确认对话框
    const confirmLinks = document.querySelectorAll('a[data-confirm]');
    
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // 标签页切换
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabContainer = this.closest('.tabs-container');
            const tabId = this.getAttribute('data-tab');
            
            // 移除所有活动状态
            tabContainer.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            tabContainer.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // 添加当前活动状态
            this.classList.add('active');
            tabContainer.querySelector(`#${tabId}`).classList.add('active');
        });
    });
});
