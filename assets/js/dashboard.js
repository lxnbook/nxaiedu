// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化下拉菜单
    initDropdowns();
    
    // 初始化侧边栏折叠
    initSidebar();
    
    // 初始化警告消息自动关闭
    initAlerts();
    
    // 初始化仪表盘图表
    initDashboardCharts();
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

// 初始化仪表盘图表
function initDashboardCharts() {
    // 仪表盘汇报趋势图表
    if (document.getElementById('reportsChart')) {
        const ctx = document.getElementById('reportsChart').getContext('2d');
        
        // 获取图表数据
        const labels = JSON.parse(document.getElementById('chartLabels').textContent);
        const data = JSON.parse(document.getElementById('chartData').textContent);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '汇报数量',
                    data: data,
                    backgroundColor: 'rgba(24, 144, 255, 0.2)',
                    borderColor: 'rgba(24, 144, 255, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // 部门汇报统计图表
    if (document.getElementById('departmentChart')) {
        const ctx = document.getElementById('departmentChart').getContext('2d');
        
        // 获取图表数据
        const labels = JSON.parse(document.getElementById('deptLabels').textContent);
        const data = JSON.parse(document.getElementById('deptData').textContent);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '汇报数量',
                    data: data,
                    backgroundColor: 'rgba(24, 144, 255, 0.6)',
                    borderColor: 'rgba(24, 144, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // 汇报状态分布图表
    if (document.getElementById('statusChart')) {
        const ctx = document.getElementById('statusChart').getContext('2d');
        
        // 获取图表数据
        const labels = JSON.parse(document.getElementById('statusLabels').textContent);
        const data = JSON.parse(document.getElementById('statusData').textContent);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(82, 196, 26, 0.6)',  // 已批准
                        'rgba(24, 144, 255, 0.6)', // 已提交
                        'rgba(250, 173, 20, 0.6)', // 草稿
                        'rgba(245, 34, 45, 0.6)'   // 已退回
                    ],
                    borderColor: [
                        'rgba(82, 196, 26, 1)',
                        'rgba(24, 144, 255, 1)',
                        'rgba(250, 173, 20, 1)',
                        'rgba(245, 34, 45, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 汇报类型分布图表
    if (document.getElementById('typeChart')) {
        const ctx = document.getElementById('typeChart').getContext('2d');
        
        // 获取图表数据
        const labels = JSON.parse(document.getElementById('typeLabels').textContent);
        const data = JSON.parse(document.getElementById('typeData').textContent);
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(24, 144, 255, 0.6)',
                        'rgba(82, 196, 26, 0.6)',
                        'rgba(250, 173, 20, 0.6)',
                        'rgba(245, 34, 45, 0.6)',
                        'rgba(114, 46, 209, 0.6)'
                    ],
                    borderColor: [
                        'rgba(24, 144, 255, 1)',
                        'rgba(82, 196, 26, 1)',
                        'rgba(250, 173, 20, 1)',
                        'rgba(245, 34, 45, 1)',
                        'rgba(114, 46, 209, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // 用户活跃度图表
    if (document.getElementById('userActivityChart')) {
        const ctx = document.getElementById('userActivityChart').getContext('2d');
        
        // 获取图表数据
        const labels = JSON.parse(document.getElementById('userLabels').textContent);
        const data = JSON.parse(document.getElementById('userData').textContent);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '汇报数量',
                    data: data,
                    backgroundColor: 'rgba(24, 144, 255, 0.6)',
                    borderColor: 'rgba(24, 144, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
}

// 初始化表格排序功能
function initTableSort() {
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
}

// 初始化表单验证
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // 添加错误提示
                    let errorMsg = field.getAttribute('data-error') || '此字段不能为空';
                    let errorEl = field.parentElement.querySelector('.error-message');
                    
                    if (!errorEl) {
                        errorEl = document.createElement('div');
                        errorEl.className = 'error-message';
                        field.parentElement.appendChild(errorEl);
                    }
                    
                    errorEl.textContent = errorMsg;
                } else {
                    field.classList.remove('is-invalid');
                    const errorEl = field.parentElement.querySelector('.error-message');
                    if (errorEl) errorEl.remove();
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // 实时验证
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                if (field.hasAttribute('required') && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    
                    // 添加错误提示
                    let errorMsg = field.getAttribute('data-error') || '此字段不能为空';
                    let errorEl = field.parentElement.querySelector('.error-message');
                    
                    if (!errorEl) {
                        errorEl = document.createElement('div');
                        errorEl.className = 'error-message';
                        field.parentElement.appendChild(errorEl);
                    }
                    
                    errorEl.textContent = errorMsg;
                } else {
                    field.classList.remove('is-invalid');
                    const errorEl = field.parentElement.querySelector('.error-message');
                    if (errorEl) errorEl.remove();
                }
            });
        });
    });
}

// 调用初始化函数
document.addEventListener('DOMContentLoaded', function() {
    initTableSort();
    initFormValidation();
});
