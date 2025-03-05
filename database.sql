-- 创建数据库
CREATE DATABASE IF NOT EXISTS `education_report_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `education_report_db`;

-- 创建用户表
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','manager','teacher','staff') NOT NULL DEFAULT 'staff',
  `department` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建汇报表
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `deadline` date DEFAULT NULL,
  `review_comment` text,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `submitted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建附件表
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建提示词表
CREATE TABLE `prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `prompts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 创建系统设置表
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认管理员账户
INSERT INTO `users` (`username`, `password`, `name`, `email`, `role`, `department`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 'admin@example.com', 'admin', '教育局');

-- 插入默认设置
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('system_name', '教育局项目汇报系统'),
('system_logo', 'logo.png'),
('system_announcement', '欢迎使用教育局项目汇报系统。本系统用于各科室和学校提交工作汇报，请按时完成相关汇报任务。');

-- 插入示例提示词
INSERT INTO `prompts` (`title`, `content`, `category`, `subject`, `created_by`, `is_public`) VALUES
('月度教研工作汇报模板', '# 月度教研工作汇报\r\n\r\n## 一、本月教研活动开展情况\r\n1. 开展了XX次教研活动，主题包括...\r\n2. 组织了XX次集体备课，涉及年级和内容...\r\n3. 参加了XX培训/研讨会，主要内容...\r\n\r\n## 二、教研成果与亮点\r\n1. 教学方法创新：...\r\n2. 学生学习效果提升：...\r\n3. 教师专业成长：...\r\n\r\n## 三、存在问题与改进措施\r\n1. 问题一：...\r\n   改进措施：...\r\n2. 问题二：...\r\n   改进措施：...\r\n\r\n## 四、下月工作计划\r\n1. 计划开展的教研活动：...\r\n2. 重点关注的教学问题：...\r\n3. 教师培训计划：...\r\n\r\n## 五、其他说明\r\n...', '月度汇报', '通用', 1, 1),
('教师培训反馈模板', '# 教师培训反馈报告\r\n\r\n## 一、培训基本情况\r\n- 培训名称：...\r\n- 培训时间：...\r\n- 培训地点：...\r\n- 培训对象：...\r\n- 培训主讲人：...\r\n\r\n## 二、培训内容摘要\r\n1. 主题一：...\r\n2. 主题二：...\r\n3. 主题三：...\r\n\r\n## 三、培训收获与反思\r\n1. 主要收获：...\r\n2. 对教学工作的启示：...\r\n3. 个人反思：...\r\n\r\n## 四、实践应用计划\r\n1. 短期应用计划：...\r\n2. 长期应用计划：...\r\n3. 预期效果：...\r\n\r\n## 五、建议与反馈\r\n...', '培训反馈', '通用', 1, 1);
