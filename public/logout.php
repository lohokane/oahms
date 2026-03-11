<?php
require_once __DIR__ . '/../app/init.php';

logout_admin();

header('Location: /public/login.php');
exit;

