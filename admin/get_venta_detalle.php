<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../controllers/VentaController.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['venta' => null, 'detalle' => []]);
    exit;
}
$ctrl   = new VentaController();
$result = $ctrl->findById($id);
echo json_encode($result);
