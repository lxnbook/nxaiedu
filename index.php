<?php
require_once 'config/config.php';

// 需要登录才能访问
requireLogin();

// 重定向到仪表盘
redirect(BASE_URL . '/dashboard.php');
