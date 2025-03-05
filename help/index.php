<?php
require_once '../config/config.php';

// 需要登录才能访问
requireLogin();

$pageTitle = '帮助中心';
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
                <div class="help-container">
                    <div class="help-sidebar">
                        <div class="help-search">
                            <input type="text" id="helpSearch" placeholder="搜索帮助内容...">
                        </div>
                        <ul class="help-nav">
                            <li class="active"><a href="#getting-started">系统介绍</a></li>
                            <li><a href="#reports">汇报管理</a></li>
                            <li><a href="#prompts">提示词库</a></li>
                            <li><a href="#users">用户管理</a></li>
                            <li><a href="#analytics">统计分析</a></li>
                            <li><a href="#settings">系统设置</a></li>
                            <li><a href="#faq">常见问题</a></li>
                        </ul>
                    </div>
                    
                    <div class="help-content">
                        <section id="getting-started" class="help-section active">
                            <h2>系统介绍</h2>
                            <p>欢迎使用教育局项目汇报系统！本系统旨在帮助教育局各部门和学校更高效地管理和提交各类项目汇报。</p>
                            
                            <h3>系统功能概述</h3>
                            <ul>
                                <li><strong>汇报管理</strong>：创建、编辑、提交和审核各类汇报</li>
                                <li><strong>提示词库</strong>：管理和使用汇报模板和提示词</li>
                                <li><strong>用户管理</strong>：管理系统用户和权限</li>
                                <li><strong>统计分析</strong>：查看汇报数据统计和趋势分析</li>
                                <li><strong>系统设置</strong>：配置系统参数和选项</li>
                            </ul>
                            
                            <h3>用户角色和权限</h3>
                            <ul>
                                <li><strong>系统管理员</strong>：拥有所有功能的完全访问权限</li>
                                <li><strong>部门经理</strong>：可以查看和审核部门内的汇报，访问统计分析</li>
                                <li><strong>教师</strong>：可以创建、编辑和提交汇报</li>
                                <li><strong>工作人员</strong>：可以创建、编辑和提交汇报</li>
                            </ul>
                        </section>
                        
                        <section id="reports" class="help-section">
                            <h2>汇报管理</h2>
                            
                            <h3>创建汇报</h3>
                            <p>要创建新的汇报，请按照以下步骤操作：</p>
                            <ol>
                                <li>点击侧边栏中的"汇报管理"</li>
                                <li>点击"新建汇报"按钮</li>
                                <li>填写汇报标题、选择汇报类型</li>
                                <li>编写汇报内容（可以使用提示词模板）</li>
                                <li>上传相关附件（如需要）</li>
                                <li>点击"保存草稿"或"提交汇报"</li>
                            </ol>
                            
                            <h3>编辑汇报</h3>
                            <p>只有处于"草稿"或"已退回"状态的汇报可以编辑。要编辑汇报，请点击汇报列表中对应汇报的"编辑"按钮。</p>
                            
                            <h3>提交汇报</h3>
                            <p>汇报提交后将进入审核流程，等待部门经理或管理员审核。提交后的汇报不能再编辑，除非被退回修改。</p>
                            
                            <h3>审核汇报</h3>
                            <p>部门经理和管理员可以审核已提交的汇报。审核时可以选择"批准通过"或"退回修改"，并提供审核意见。</p>
                        </section>
                        
                        <section id="prompts" class="help-section">
                            <h2>提示词库</h2>
                            
                            <h3>使用提示词</h3>
                            <p>提示词是预设的汇报模板或内容框架，可以帮助您更快地创建规范的汇报。在创建或编辑汇报时，您可以从下拉菜单中选择提示词，然后点击"插入提示词"按钮。</p>
                            
                            <h3>创建提示词</h3>
                            <p>要创建新的提示词，请按照以下步骤操作：</p>
                            <ol>
                                <li>点击侧边栏中的"提示词库"</li>
                                <li>点击"新建提示词"按钮</li>
                                <li>填写提示词标题、分类和内容</li>
                                <li>选择是否公开此提示词</li>
                                <li>点击"保存提示词"按钮</li>
                            </ol>
                            
                            <h3>管理提示词</h3>
                            <p>您可以查看、编辑和删除自己创建的提示词。公开的提示词对所有用户可见，私有提示词只对自己可见。</p>
                        </section>
                        
                        <section id="users" class="help-section">
                            <h2>用户管理</h2>
                            
                            <h3>用户角色</h3>
                            <p>系统中有四种用户角色：</p>
                            <ul>
                                <li><strong>系统管理员</strong>：管理整个系统，包括用户、设置和所有数据</li>
                                <li><strong>部门经理</strong>：管理部门内的汇报和用户，审核汇报</li>
                                <li><strong>教师</strong>：创建和提交汇报</li>
                                <li><strong>工作人员</strong>：创建和提交汇报</li>
                            </ul>
                            
                            <h3>创建用户</h3>
                            <p>只有系统管理员可以创建新用户。要创建用户，请按照以下步骤操作：</p>
                            <ol>
                                <li>点击侧边栏中的"用户管理"</li>
                                <li>点击"新建用户"按钮</li>
                                <li>填写用户信息，包括用户名、姓名、邮箱、部门和角色</li>
                                <li>设置初始密码</li>
                                <li>点击"创建用户"按钮</li>
                            </ol>
                            
                            <h3>管理用户</h3>
                            <p>系统管理员可以编辑用户信息、重置密码和停用/激活用户账户。</p>
                            
                            <h3>个人资料</h3>
                            <p>所有用户都可以通过点击右上角的用户名，然后选择"个人资料"来更新自己的个人信息和修改密码。</p>
                        </section>
                        
                        <section id="analytics" class="help-section">
                            <h2>统计分析</h2>
                            
                            <h3>查看统计数据</h3>
                            <p>系统管理员和部门经理可以访问统计分析功能，查看汇报提交情况、部门统计、汇报类型分布等数据。</p>
                            
                            <h3>筛选数据</h3>
                            <p>您可以通过选择时间段、部门等条件来筛选统计数据，以便更精确地分析特定范围内的汇报情况。</p>
                            
                            <h3>导出数据</h3>
                            <p>统计数据可以导出为CSV或Excel格式，方便进一步分析或报告使用。</p>
                        </section>
                        
                        <section id="settings" class="help-section">
                            <h2>系统设置</h2>
                            
                            <h3>基本设置</h3>
                            <p>系统管理员可以配置系统的基本参数，如系统名称、描述、Logo等。</p>
                            
                            <h3>汇报设置</h3>
                            <p>配置汇报相关的参数，如汇报类型、默认状态、审核流程等。</p>
                            
                            <h3>通知设置</h3>
                            <p>配置系统通知相关的参数，如是否启用邮件通知、通知触发条件等。</p>
                            
                            <h3>高级设置</h3>
                            <p>配置系统的高级选项，如维护模式、日志级别等。</p>
                        </section>
                        
                        <section id="faq" class="help-section">
                            <h2>常见问题</h2>
                            
                            <h3>如何重置密码？</h3>
                            <p>如果您忘记了密码，请联系系统管理员重置密码。如果您知道当前密码，可以在个人资料页面修改密码。</p>
                            
                            <h3>为什么我无法编辑已提交的汇报？</h3>
                            <p>已提交的汇报进入了审核流程，不能再编辑。如果需要修改，请等待审核人员将其退回，或联系管理员。</p>
                            
                            <h3>如何使用提示词模板？</h3>
                            <p>在创建或编辑汇报时，您可以从下拉菜单中选择提示词，然后点击"插入提示词"按钮，提示词内容将自动填充到汇报内容框中。</p>
                            
                            <h3>如何查看我的汇报状态？</h3>
                            <p>在汇报列表页面，您可以看到所有汇报的状态。状态包括：草稿、已提交、已批准和已退回。</p>
                            
                            <h3>谁可以查看我提交的汇报？</h3>
                            <p>您的部门经理和系统管理员可以查看您提交的汇报。其他用户无法查看您的汇报。</p>
                            
                            <h3>如何导出汇报数据？</h3>
                            <p>系统管理员和部门经理可以在汇报列表页面使用导出功能，将汇报数据导出为CSV或Excel格式。</p>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 帮助导航切换
            const navLinks = document.querySelectorAll('.help-nav a');
            const sections = document.querySelectorAll('.help-section');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 移除所有活动状态
                    navLinks.forEach(l => l.parentElement.classList.remove('active'));
                    sections.forEach(s => s.classList.remove('active'));
                    
                    // 添加当前活动状态
                    this.parentElement.classList.add('active');
                    const targetId = this.getAttribute('href').substring(1);
                    document.getElementById(targetId).classList.add('active');
                });
            });
            
            // 帮助搜索功能
            const searchInput = document.getElementById('helpSearch');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                if (searchTerm.length < 2) {
                    // 如果搜索词太短，显示所有内容
                    sections.forEach(section => {
                        section.style.display = '';
                        Array.from(section.querySelectorAll('h3, p, li')).forEach(el => {
                            el.innerHTML = el.textContent;
                        });
                    });
                    return;
                }
                
                // 搜索内容并高亮匹配项
                sections.forEach(section => {
                    const sectionText = section.textContent.toLowerCase();
                    const hasMatch = sectionText.includes(searchTerm);
                    
                    section.style.display = hasMatch ? '' : 'none';
                    
                    if (hasMatch) {
                        // 高亮匹配的文本
                        Array.from(section.querySelectorAll('h3, p, li')).forEach(el => {
                            const text = el.textContent;
                            const regex = new RegExp(searchTerm, 'gi');
                            el.innerHTML = text.replace(regex, match => `<mark>${match}</mark>`);
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
