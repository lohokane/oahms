<?php
require_once __DIR__ . '/../app/init.php';

if (is_admin_logged_in()) {
    header('Location: /public/dashboard.php');
    exit;
}

header('Location: /public/login.php');
exit;

