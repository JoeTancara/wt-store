<?php
// models/Venta.php
require_once __DIR__ . '/../config/database.php';

class Venta {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($limit = null, $usuarioId = null) {
        $where    = $usuarioId ? "WHERE v.usuario_id = " . intval($usuarioId) : "";
        $limitSQL = $limit ? "LIMIT " . intval($limit) : "";
        $r = $this->db->query("SELECT v.*, u.nombre AS vendedor_nombre
            FROM ventas v LEFT JOIN usuarios u ON u.id = v.usuario_id
            $where ORDER BY v.fecha DESC $limitSQL");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT v.*, u.nombre AS vendedor_nombre
            FROM ventas v LEFT JOIN usuarios u ON u.id = v.usuario_id WHERE v.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getDetalle($ventaId) {
        $stmt = $this->db->prepare("SELECT vd.*, p.nombre AS producto_nombre
            FROM venta_detalle vd LEFT JOIN productos p ON p.id = vd.producto_id
            WHERE vd.venta_id = ?");
        $stmt->bind_param("i", $ventaId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($usuarioId, $total, $tipoPago, $items) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO ventas (usuario_id, total, tipo_pago, estado) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ids", $usuarioId, $total, $tipoPago);
            $stmt->execute();
            $ventaId = $conn->insert_id;

            foreach ($items as $item) {
                $pid      = intval($item['producto_id']);
                $cant     = intval($item['cantidad']);
                $precio   = floatval($item['precio']);
                $subtotal = $precio * $cant;

                $chk = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
                $chk->bind_param("i", $pid);
                $chk->execute();
                $row = $chk->get_result()->fetch_assoc();
                if (!$row || $row['stock'] < $cant)
                    throw new Exception("Stock insuficiente para producto ID: $pid");

                $ins = $conn->prepare("INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("iiidd", $ventaId, $pid, $cant, $precio, $subtotal);
                $ins->execute();

                $upd = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $upd->bind_param("ii", $cant, $pid);
                $upd->execute();
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
            $chk = $conn->prepare("SELECT id, estado FROM ventas WHERE id = ?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $venta = $chk->get_result()->fetch_assoc();
            if (!$venta) throw new Exception("Venta no encontrada");
            if (!$venta['estado']) throw new Exception("La venta ya esta anulada");

            $upd = $conn->prepare("UPDATE ventas SET estado = 0, motivo_anulacion = ? WHERE id = ?");
            $upd->bind_param("si", $motivo, $id);
            $upd->execute();

            $det = $conn->prepare("SELECT producto_id, cantidad FROM venta_detalle WHERE venta_id = ?");
            $det->bind_param("i", $id);
            $det->execute();
            foreach ($det->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
                $rst = $conn->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
                $rst->bind_param("ii", $item['cantidad'], $item['producto_id']);
                $rst->execute();
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getTotalHoy($usuarioId = null) {
        $where = "WHERE DATE(fecha) = CURDATE() AND estado = 1" . ($usuarioId ? " AND usuario_id = ".intval($usuarioId) : "");
        $r = $this->db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas $where");
        return $r ? floatval($r->fetch_assoc()['t']) : 0;
    }

    public function getTotalMes($usuarioId = null) {
        $where = "WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE()) AND estado = 1" . ($usuarioId ? " AND usuario_id = ".intval($usuarioId) : "");
        $r = $this->db->query("SELECT COALESCE(SUM(total),0) AS t FROM ventas $where");
        return $r ? floatval($r->fetch_assoc()['t']) : 0;
    }

    public function getCountHoy($usuarioId = null) {
        $where = "WHERE DATE(fecha) = CURDATE() AND estado = 1" . ($usuarioId ? " AND usuario_id = ".intval($usuarioId) : "");
        $r = $this->db->query("SELECT COUNT(*) AS t FROM ventas $where");
        return $r ? intval($r->fetch_assoc()['t']) : 0;
    }

    public function getVentasPorDia($dias = 7, $usuarioId = null) {
        $dias  = intval($dias);
        $extra = $usuarioId ? " AND usuario_id = ".intval($usuarioId) : "";
        $r = $this->db->query("SELECT DATE(fecha) AS dia, SUM(total) AS total, COUNT(*) AS cantidad
            FROM ventas WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY) AND estado = 1 $extra
            GROUP BY DATE(fecha) ORDER BY dia ASC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getIngresosEgresosPorDia($dias = 7, $usuarioId = null) {
        $dias  = intval($dias);
        $extra = $usuarioId ? " AND usuario_id = ".intval($usuarioId) : "";
        $r = $this->db->query("SELECT d.dia,
            COALESCE(v.total,0) AS ingresos,
            COALESCE(e.total,0) AS egresos
            FROM (SELECT DATE_SUB(CURDATE(), INTERVAL n DAY) AS dia FROM
                (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) nums
                WHERE n < $dias ORDER BY dia ASC) d
            LEFT JOIN (SELECT DATE(fecha) AS dia, SUM(total) AS total FROM ventas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY) AND estado = 1 $extra
                GROUP BY DATE(fecha)) v ON v.dia = d.dia
            LEFT JOIN (SELECT fecha AS dia, SUM(monto) AS total FROM egresos
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
                GROUP BY fecha) e ON e.dia = d.dia
            ORDER BY d.dia ASC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
