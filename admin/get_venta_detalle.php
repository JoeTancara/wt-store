<?php
// admin/get_venta_detalle.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error'=>'ID inválido']); exit; }

$db    = Database::getInstance();
$vstmt = $db->prepare("SELECT v.*,u.nombre AS vendedor_nombre FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.id=?");
$vstmt->bind_param("i",$id); $vstmt->execute();
$venta = $vstmt->get_result()->fetch_assoc();
if (!$venta) { echo json_encode(['error'=>'Venta no encontrada']); exit; }

$dstmt = $db->prepare(
    "SELECT vd.*,p.nombre AS producto_nombre,COALESCE(vd.tipo_unidad,'unidad') AS tipo_unidad
     FROM venta_detalle vd LEFT JOIN productos p ON p.id=vd.producto_id WHERE vd.venta_id=?"
);
$dstmt->bind_param("i",$id); $dstmt->execute();
$detalle = $dstmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['venta'=>$venta,'detalle'=>$detalle]);
