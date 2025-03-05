<?php
require_once '../config/config.php';

// 清除所有会话数据
session_unset();
session_destroy();

// 重定向到登录页面
redirect(BASE_URL . '/auth/login.php');
