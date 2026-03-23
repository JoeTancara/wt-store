<?php
// models/Venta.php
require_once __DIR__ . '/../config/database.php';

class Venta {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($limit = null, $uid = null) {
        $where    = $uid ? "WHERE v.usuario_id=" . intval($uid) : "";
        $limitSQL = $limit ? "LIMIT " . intval($limit) : "";
        $r = $this->db->query(
            "SELECT v.*, u.nombre AS vendedor_nombre
             FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id
             $where ORDER BY v.fecha DESC $limitSQL"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT v.*, u.nombre AS vendedor_nombre
             FROM ventas v LEFT JOIN usuarios u ON u.id=v.usuario_id WHERE v.id=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getDetalle($ventaId) {
        $stmt = $this->db->prepare(
            "SELECT vd.*, p.nombre AS producto_nombre,
             COALESCE(vd.tipo_unidad,'unidad') AS tipo_unidad,
             vd.color_nombre
             FROM venta_detalle vd
             LEFT JOIN productos p ON p.id=vd.producto_id
             WHERE vd.venta_id=?"
        );
        $stmt->bind_param("i", $ventaId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($uid, $total, $tipoPago, $items) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO ventas (usuario_id,total,tipo_pago,estado) VALUES (?,?,?,1)");
            $stmt->bind_param("ids", $uid, $total, $tipoPago);
            $stmt->execute();
            $ventaId = $conn->insert_id;

            foreach ($items as $item) {
                $pid       = intval($item['producto_id']);
                $cant      = intval($item['cantidad']);
                $precio    = floatval($item['precio']);
                $subtotal  = $precio * $cant;
                $tipoUni   = in_array($item['tipo_unidad'] ?? 'unidad', ['unidad','docena'])
                             ? ($item['tipo_unidad'] ?? 'unidad') : 'unidad';
                $colorId   = intval($item['color_id']   ?? 0) ?: null;
                $colorNom  = trim($item['color_nombre'] ?? '');

                // ---- verificar stock general ----
                $chk = $conn->prepare(
                    "SELECT stock, stock_docenas, stock_unidades, unidades_por_docena
                     FROM productos WHERE id=? FOR UPDATE"
                );
                $chk->bind_param("i", $pid);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                if (!$row) throw new Exception("Producto ID $pid no encontrado");

                if ($tipoUni === 'docena') {
                    if ((int)$row['stock_docenas'] < $cant)
                        throw new Exception("Docenas insuficientes para '$colorNom' (disp: {$row['stock_docenas']})");
                    $u = max(1, (int)$row['unidades_por_docena']);
                    $conn->query("UPDATE productos SET stock_docenas=stock_docenas-$cant, stock=stock-" . ($cant*$u) . " WHERE id=$pid");

                    // Si hay color, descontar de producto_colores
                    if ($colorId) {
                        $cc = $conn->prepare("SELECT docenas FROM producto_colores WHERE id=? FOR UPDATE");
                        $cc->bind_param("i", $colorId); $cc->execute();
                        $crow = $cc->get_result()->fetch_assoc();
                        if (!$crow || (int)$crow['docenas'] < $cant)
                            throw new Exception("Sin suficientes docenas del color '$colorNom'");
                        $conn->query("UPDATE producto_colores SET docenas=docenas-$cant WHERE id=$colorId");
                    }
                } else {
                    if ((int)$row['stock'] < $cant)
                        throw new Exception("Stock insuficiente para ID $pid");
                    $conn->query("UPDATE productos SET stock=stock-$cant, stock_unidades=GREATEST(0,stock_unidades-$cant) WHERE id=$pid");

                    // descontar unidades del color
                    if ($colorId) {
                        $cc = $conn->prepare("SELECT unidades FROM producto_colores WHERE id=? FOR UPDATE");
                        $cc->bind_param("i", $colorId); $cc->execute();
                        $crow = $cc->get_result()->fetch_assoc();
                        if (!$crow || (int)$crow['unidades'] < $cant)
                            throw new Exception("Sin suficientes unidades del color '$colorNom'");
                        $conn->query("UPDATE producto_colores SET unidades=unidades-$cant WHERE id=$colorId");
                    }
                }

                // Insertar detalle con color
                $ins = $conn->prepare(
                    "INSERT INTO venta_detalle (venta_id,producto_id,cantidad,tipo_unidad,color_id,color_nombre,precio,subtotal)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                if ($ins) {
                    $ins->bind_param("iiisisdd", $ventaId, $pid, $cant, $tipoUni, $colorId, $colorNom, $precio, $subtotal);
                    if (!$ins->execute()) {
                        // fallback sin color_id/color_nombre si columnas no existen
                        $ins2 = $conn->prepare(
                            "INSERT INTO venta_detalle (venta_id,producto_id,cantidad,tipo_unidad,precio,subtotal)
                             VALUES (?,?,?,?,?,?)"
                        );
                        $ins2->bind_param("iiisdd", $ventaId, $pid, $cant, $tipoUni, $precio, $subtotal);
                        $ins2->execute();
                    }
                }
            }
            $conn->commit();
            return $ventaId;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function anular($id, $motivo) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $chk = $conn->prepare("SELECT id,estado FROM ventas WHERE id=?");
            $chk->bind_param("i", $id); $chk->execute();
            $v = $chk->get_result()->fetch_assoc();
            if (!$v)          throw new Exception("Venta no encontrada");
            if (!$v['estado']) throw new Exception("La venta ya está anulada");

            $upd = $conn->prepare("UPDATE ventas SET estado=0,motivo_anulacion=? WHERE id=?");
            $upd->bind_param("si", $motivo, $id); $upd->execute();

            $det = $conn->prepare(
                "SELECT producto_id, cantidad,
                 COALESCE(tipo_unidad,'unidad') AS tipo_unidad,
                 color_id, color_nombre
                 FROM venta_detalle WHERE venta_id=?"
            );
            $det->bind_param("i", $id); $det->execute();
            foreach ($det->get_result()->fetch_all(MYSQLI_ASSOC) as $it) {
                $pid  = (int)$it['producto_id'];
                $cant = (int)$it['cantidad'];
                $cid  = (int)($it['color_id'] ?? 0);

                if ($it['tipo_unidad'] === 'docena') {
                    $r2 = $conn->prepare("SELECT unidades_por_docena FROM productos WHERE id=?");
                    $r2->bind_param("i", $pid); $r2->execute();
                    $pr = $r2->get_result()->fetch_assoc();
                    $u  = max(1, (int)($pr['unidades_por_docena'] ?? 12));
                    $conn->query("UPDATE productos SET stock=stock+".($cant*$u).",stock_docenas=stock_docenas+$cant WHERE id=$pid");
                    if ($cid) $conn->query("UPDATE producto_colores SET docenas=docenas+$cant WHERE id=$cid");
                } else {
                    $conn->query("UPDATE productos SET stock=stock+$cant,stock_unidades=stock_unidades+$cant WHERE id=$pid");
                    if ($cid) $conn->query("UPDATE producto_colores SET unidades=unidades+$cant WHERE id=$cid");
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getTotalHoy($uid = null) {
        $w = "WHERE DATE(fecha)=CURDATE() AND estado=1" . ($uid ? " AND usuario_id=".intval($uid) : "");
        $r = $this->db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas $w");
        return $r ? floatval($r->fetch_assoc()['t']) : 0;
    }
    public function getTotalMes($uid = null) {
        $w = "WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE()) AND estado=1" . ($uid ? " AND usuario_id=".intval($uid) : "");
        $r = $this->db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas $w");
        return $r ? floatval($r->fetch_assoc()['t']) : 0;
    }
    public function getCountHoy($uid = null) {
        $w = "WHERE DATE(fecha)=CURDATE() AND estado=1" . ($uid ? " AND usuario_id=".intval($uid) : "");
        $r = $this->db->query("SELECT COUNT(*) AS t FROM ventas $w");
        return $r ? intval($r->fetch_assoc()['t']) : 0;
    }
    public function getVentasPorDia($dias = 7, $uid = null) {
        $dias = intval($dias); $e = $uid ? " AND usuario_id=".intval($uid) : "";
        $r = $this->db->query("SELECT DATE(fecha) AS dia, SUM(total) AS total, COUNT(*) AS cantidad
            FROM ventas WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL $dias DAY) AND estado=1$e
            GROUP BY DATE(fecha) ORDER BY dia ASC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getIngresosEgresosPorDia($dias = 7, $uid = null) {
        $dias = intval($dias); $e = $uid ? " AND usuario_id=".intval($uid) : "";
        $r = $this->db->query(
            "SELECT d.dia, COALESCE(v.total,0) AS ingresos, COALESCE(eg.total,0) AS egresos
             FROM (SELECT DATE_SUB(CURDATE(),INTERVAL n DAY) AS dia FROM
                   (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) nums
                   WHERE n<$dias ORDER BY dia ASC) d
             LEFT JOIN (SELECT DATE(fecha) AS dia, SUM(total) AS total FROM ventas
                        WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL $dias DAY) AND estado=1$e
                        GROUP BY DATE(fecha)) v ON v.dia=d.dia
             LEFT JOIN (SELECT fecha AS dia, SUM(monto) AS total FROM egresos
                        WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL $dias DAY)
                        GROUP BY fecha) eg ON eg.dia=d.dia
             ORDER BY d.dia ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getVentasRango($desde, $hasta, $uid = null) {
        $d = $this->db->escape($desde); $h = $this->db->escape($hasta);
        $w = "WHERE DATE(v.fecha) BETWEEN '$d' AND '$h'";
        if ($uid) $w .= " AND v.usuario_id=" . intval($uid);
        $r = $this->db->query(
            "SELECT v.*, u.nombre AS vendedor_nombre FROM ventas v
             LEFT JOIN usuarios u ON u.id=v.usuario_id $w ORDER BY v.fecha DESC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getResumenMensual($meses = 6) {
        $r = $this->db->query(
            "SELECT DATE_FORMAT(fecha,'%Y-%m') AS mes,
             SUM(CASE WHEN estado=1 THEN total ELSE 0 END) AS ingresos,
             COUNT(CASE WHEN estado=1 THEN 1 END) AS cantidad,
             COUNT(CASE WHEN estado=0 THEN 1 END) AS anuladas
             FROM ventas WHERE fecha>=DATE_SUB(CURDATE(),INTERVAL $meses MONTH)
             GROUP BY DATE_FORMAT(fecha,'%Y-%m') ORDER BY mes ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ---- REPORTE GANANCIAS ---- */
    public function getGananciasRango($desde, $hasta) {
        $d = $this->db->escape($desde); $h = $this->db->escape($hasta);
        $r = $this->db->query(
            "SELECT
               SUM(vd.subtotal) AS ingresos,
               SUM(p.precio_compra * vd.cantidad) AS costos,
               SUM(vd.subtotal - (p.precio_compra * vd.cantidad)) AS ganancia
             FROM venta_detalle vd
             JOIN ventas v    ON v.id=vd.venta_id
             JOIN productos p ON p.id=vd.producto_id
             WHERE v.estado=1 AND DATE(v.fecha) BETWEEN '$d' AND '$h'"
        );
        return $r ? $r->fetch_assoc() : ['ingresos'=>0,'costos'=>0,'ganancia'=>0];
    }
    public function getArqueoMensual($meses = 6) {
        $r = $this->db->query(
            "SELECT
               DATE_FORMAT(v.fecha,'%Y-%m') AS mes,
               SUM(vd.subtotal) AS ingresos,
               SUM(p.precio_compra * vd.cantidad) AS costos,
               SUM(vd.subtotal - (p.precio_compra * vd.cantidad)) AS ganancia,
               COUNT(DISTINCT v.id) AS num_ventas
             FROM venta_detalle vd
             JOIN ventas v    ON v.id=vd.venta_id AND v.estado=1
             JOIN productos p ON p.id=vd.producto_id
             WHERE v.fecha>=DATE_SUB(CURDATE(),INTERVAL $meses MONTH)
             GROUP BY DATE_FORMAT(v.fecha,'%Y-%m')
             ORDER BY mes ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
