<?php
require_once __DIR__ . '/../app/init.php';

if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

redirect('login.php');

