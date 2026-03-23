<?php
// models/ProductoColor.php
require_once __DIR__ . '/../config/database.php';

class ProductoColor {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getByProducto($productoId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM producto_colores WHERE producto_id=? ORDER BY color ASC"
        );
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getActivosByProducto($productoId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM producto_colores WHERE producto_id=? AND activo=1 ORDER BY color ASC"
        );
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM producto_colores WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function upsert($productoId, $color, $hexCode, $docenas, $unidades) {
        $color   = trim($color);
        $hexCode = trim($hexCode) ?: '#6b7280';
        if (!$color) return false;

        // Verificar si ya existe
        $chk = $this->db->prepare(
            "SELECT id FROM producto_colores WHERE producto_id=? AND color=?"
        );
        $chk->bind_param("is", $productoId, $color);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE producto_colores SET hex_code=?, docenas=?, unidades=?, activo=1 WHERE id=?"
            );
            $stmt->bind_param("siii", $hexCode, $docenas, $unidades, $existing['id']);
            return $stmt->execute() ? $existing['id'] : false;
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO producto_colores (producto_id,color,hex_code,docenas,unidades) VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param("issii", $productoId, $color, $hexCode, $docenas, $unidades);
            return $stmt->execute() ? $this->db->lastInsertId() : false;
        }
    }

    public function update($id, $color, $hexCode, $docenas, $unidades) {
        $stmt = $this->db->prepare(
            "UPDATE producto_colores SET color=?, hex_code=?, docenas=?, unidades=? WHERE id=?"
        );
        $stmt->bind_param("ssiii", $color, $hexCode, $docenas, $unidades, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM producto_colores WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function deleteByProducto($productoId) {
        $stmt = $this->db->prepare("DELETE FROM producto_colores WHERE producto_id=?");
        $stmt->bind_param("i", $productoId);
        return $stmt->execute();
    }

    /** Recalcula stock_docenas y stock_unidades del producto sumando todos sus colores */
    public function syncStockProducto($productoId) {
        $colores = $this->getByProducto($productoId);
        $totalDoc = 0; $totalUni = 0;
        foreach ($colores as $c) {
            $totalDoc += (int)$c['docenas'];
            $totalUni += (int)$c['unidades'];
        }
        $prod = (new \Producto())->findById($productoId);
        $upd  = $prod ? max(1,(int)$prod['unidades_por_docena']) : 12;
        $total = ($totalDoc * $upd) + $totalUni;

        $stmt = $this->db->prepare(
            "UPDATE productos SET stock=?, stock_docenas=?, stock_unidades=? WHERE id=?"
        );
        $stmt->bind_param("iiii", $total, $totalDoc, $totalUni, $productoId);
        return $stmt->execute();
    }

    /** Total docenas de un producto sumando colores */
    public function getTotalDocenas($productoId) {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(docenas),0) AS d, COALESCE(SUM(unidades),0) AS u
             FROM producto_colores WHERE producto_id=? AND activo=1"
        );
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Decrementar stock de un color específico */
    public function decrementar($colorId, $docenas, $unidades) {
        $stmt = $this->db->prepare(
            "UPDATE producto_colores
             SET docenas  = GREATEST(0, docenas  - ?),
                 unidades = GREATEST(0, unidades - ?)
             WHERE id=?"
        );
        $stmt->bind_param("iii", $docenas, $unidades, $colorId);
        return $stmt->execute();
    }

    /** Incrementar stock de un color (al anular venta) */
    public function incrementar($colorId, $docenas, $unidades) {
        $stmt = $this->db->prepare(
            "UPDATE producto_colores
             SET docenas  = docenas  + ?,
                 unidades = unidades + ?
             WHERE id=?"
        );
        $stmt->bind_param("iii", $docenas, $unidades, $colorId);
        return $stmt->execute();
    }
}
