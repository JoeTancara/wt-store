<?php

require_once __DIR__ . '/../config/database.php';

class Venta {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($limit = null) {
        $limitSQL = $limit ? "LIMIT " . intval($limit) : "";
        $result = $this->db->query("
            SELECT v.*, u.nombre AS vendedor_nombre
            FROM ventas v
            LEFT JOIN usuarios u ON u.id = v.usuario_id
            ORDER BY v.fecha DESC
            $limitSQL
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, u.nombre AS vendedor_nombre
            FROM ventas v
            LEFT JOIN usuarios u ON u.id = v.usuario_id
            WHERE v.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getDetalle($ventaId) {
        $stmt = $this->db->prepare("
            SELECT vd.*, p.nombre AS producto_nombre
            FROM venta_detalle vd
            LEFT JOIN productos p ON p.id = vd.producto_id
            WHERE vd.venta_id = ?
        ");
        $stmt->bind_param("i", $ventaId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Crea una venta con transacción.
     * bind_param: i=usuario_id, d=total, s=tipo_pago
     */
    public function create($usuarioId, $total, $tipoPago, $items) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO ventas (usuario_id, total, tipo_pago, estado) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("ids", $usuarioId, $total, $tipoPago);
            $stmt->execute();
            $ventaId = $conn->insert_id;

            foreach ($items as $item) {
                $productoId = intval($item['producto_id']);
                $cantidad   = intval($item['cantidad']);
                $precio     = floatval($item['precio']);
                $subtotal   = $precio * $cantidad;

                $checkStmt = $conn->prepare("SELECT stock FROM productos WHERE id = ? FOR UPDATE");
                $checkStmt->bind_param("i", $productoId);
                $checkStmt->execute();
                $row = $checkStmt->get_result()->fetch_assoc();

                if (!$row || (int)$row['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto ID: $productoId");
                }

                $detStmt = $conn->prepare("INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)");
                $detStmt->bind_param("iiidd", $ventaId, $productoId, $cantidad, $precio, $subtotal);
                $detStmt->execute();

                $stockStmt = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stockStmt->bind_param("ii", $cantidad, $productoId);
                $stockStmt->execute();
            }

            $conn->commit();
            return $ventaId;

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Anula una venta: marca estado=0 y devuelve el stock.
     * Solo admins. Solo ventas activas.
     */
    public function anular($id, $motivo) {
        $conn = $this->db->getConnection();
        $conn->begin_transaction();
        try {
            // Verificar que exista y esté activa
            $chk = $conn->prepare("SELECT id, estado FROM ventas WHERE id = ?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $venta = $chk->get_result()->fetch_assoc();

            if (!$venta) throw new Exception("Venta no encontrada");
            if ((int)$venta['estado'] === 0) throw new Exception("La venta ya está anulada");

            // Marcar como anulada
            $upd = $conn->prepare("UPDATE ventas SET estado = 0, motivo_anulacion = ? WHERE id = ?");
            $upd->bind_param("si", $motivo, $id);
            $upd->execute();

            // Revertir stock de cada producto
            $det = $conn->prepare("SELECT producto_id, cantidad FROM venta_detalle WHERE venta_id = ?");
            $det->bind_param("i", $id);
            $det->execute();
            $items = $det->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($items as $item) {
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

    // ---- Estadísticas (solo ventas activas estado=1) ----

    public function getTotalHoy() {
        $result = $this->db->query("SELECT COALESCE(SUM(total),0) AS total FROM ventas WHERE DATE(fecha) = CURDATE() AND estado = 1");
        return $result ? floatval($result->fetch_assoc()['total']) : 0;
    }

    public function getTotalMes() {
        $result = $this->db->query("SELECT COALESCE(SUM(total),0) AS total FROM ventas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE()) AND estado = 1");
        return $result ? floatval($result->fetch_assoc()['total']) : 0;
    }

    public function getCountHoy() {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM ventas WHERE DATE(fecha) = CURDATE() AND estado = 1");
        return $result ? intval($result->fetch_assoc()['total']) : 0;
    }

    public function getVentasPorDia($dias = 7) {
        $dias   = intval($dias);
        $result = $this->db->query("
            SELECT DATE(fecha) AS dia, SUM(total) AS total, COUNT(*) AS cantidad
            FROM ventas
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
              AND estado = 1
            GROUP BY DATE(fecha)
            ORDER BY dia ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Retorna ingresos y egresos por día para el gráfico combinado.
     * Devuelve array con claves 'dia', 'ingresos', 'egresos'.
     */
    public function getIngresosEgresosPorDia($dias = 7) {
        $dias   = intval($dias);
        $result = $this->db->query("
            SELECT
                d.dia,
                COALESCE(v.total, 0) AS ingresos,
                COALESCE(e.total, 0) AS egresos
            FROM (
                SELECT DATE_SUB(CURDATE(), INTERVAL n DAY) AS dia
                FROM (
                    SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                ) nums
                WHERE n < $dias
                ORDER BY dia ASC
            ) d
            LEFT JOIN (
                SELECT DATE(fecha) AS dia, SUM(total) AS total
                FROM ventas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY) AND estado = 1
                GROUP BY DATE(fecha)
            ) v ON v.dia = d.dia
            LEFT JOIN (
                SELECT fecha AS dia, SUM(monto) AS total
                FROM egresos
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)
                GROUP BY fecha
            ) e ON e.dia = d.dia
            ORDER BY d.dia ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
