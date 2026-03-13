<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
$ctrl = new AuthController();
$ctrl->logout();
redirect(BASE_URL . '/login.php');
