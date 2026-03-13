<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
require_once __DIR__ . '/../models/Producto.php';

$productoId = intval($_GET['producto_id'] ?? 0);
if (!$productoId) {
    echo json_encode([]);
    exit;
}
$model    = new Producto();
$imagenes = $model->getImagenes($productoId);
echo json_encode($imagenes);
