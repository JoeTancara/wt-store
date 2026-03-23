<?php
// models/Producto.php
require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll($soloActivos = false, $categoriaId = null) {
        $where = [];
        if ($soloActivos) $where[] = "p.estado = 1";
        if ($categoriaId) $where[] = "p.categoria_id = " . intval($categoriaId);
        $sql = "SELECT p.*, c.nombre AS categoria_nombre,
                (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id=p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal,
                (COALESCE(p.stock_docenas,0)*COALESCE(p.unidades_por_docena,12) + COALESCE(p.stock_unidades,0)) AS stock_total
                FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id"
             . (count($where) ? " WHERE ".implode(" AND ",$where) : "")
             . " ORDER BY p.fecha_creacion DESC";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre,
             (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id=p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal,
             (COALESCE(p.stock_docenas,0)*COALESCE(p.unidades_por_docena,12) + COALESCE(p.stock_unidades,0)) AS stock_total
             FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id WHERE p.id=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getImagenes($productoId) {
        $stmt = $this->db->prepare("SELECT * FROM producto_imagenes WHERE producto_id=? ORDER BY orden ASC");
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countImagenes($productoId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS t FROM producto_imagenes WHERE producto_id=?");
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['t'];
    }

    // 11 params: i s s d d d i i i i i
    public function create($catId, $nombre, $desc, $pCompra, $pVenta, $pDocena, $stock, $docenas, $unidades, $upd, $estado) {
        $stmt = $this->db->prepare(
            "INSERT INTO productos
             (categoria_id, nombre, descripcion, precio_compra, precio_venta, precio_docena,
              stock, stock_docenas, stock_unidades, unidades_por_docena, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issdddiiiii",
            $catId, $nombre, $desc,
            $pCompra, $pVenta, $pDocena,
            $stock, $docenas, $unidades, $upd, $estado
        );
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    // 12 params: i s s d d d i i i i i i
    public function update($id, $catId, $nombre, $desc, $pCompra, $pVenta, $pDocena, $docenas, $unidades, $upd, $estado) {
        $stock = ($docenas * $upd) + $unidades;
        $stmt  = $this->db->prepare(
            "UPDATE productos SET
               categoria_id=?, nombre=?, descripcion=?,
               precio_compra=?, precio_venta=?, precio_docena=?,
               stock=?, stock_docenas=?, stock_unidades=?,
               unidades_por_docena=?, estado=?
             WHERE id=?"
        );
        $stmt->bind_param("issdddiiiiii",
            $catId, $nombre, $desc,
            $pCompra, $pVenta, $pDocena,
            $stock, $docenas, $unidades, $upd, $estado, $id
        );
        return $stmt->execute();
    }

    public function updateStockDesdeColores($id) {
        $r = $this->db->query(
            "SELECT COALESCE(SUM(docenas),0) AS d, COALESCE(SUM(unidades),0) AS u
             FROM producto_colores WHERE producto_id=$id AND activo=1"
        );
        if (!$r) return false;
        $row  = $r->fetch_assoc();
        $doc  = (int)$row['d']; $uni = (int)$row['u'];
        $prod = $this->findById($id);
        $upd  = $prod ? max(1,(int)$prod['unidades_por_docena']) : 12;
        $tot  = ($doc * $upd) + $uni;
        $stmt = $this->db->prepare("UPDATE productos SET stock=?, stock_docenas=?, stock_unidades=? WHERE id=?");
        $stmt->bind_param("iiii", $tot, $doc, $uni, $id);
        return $stmt->execute();
    }

    public function updateStockDocenas($id, $docenas, $unidades) {
        $p     = $this->findById($id);
        $upd   = $p ? max(1,(int)$p['unidades_por_docena']) : 12;
        $total = ($docenas * $upd) + $unidades;
        $stmt  = $this->db->prepare("UPDATE productos SET stock=?, stock_docenas=?, stock_unidades=? WHERE id=?");
        $stmt->bind_param("iiii", $total, $docenas, $unidades, $id);
        return $stmt->execute();
    }

    public function updateStock($id, $cantidad) {
        $stmt = $this->db->prepare("UPDATE productos SET stock=stock+?, stock_unidades=GREATEST(0,stock_unidades+?) WHERE id=?");
        $stmt->bind_param("iii", $cantidad, $cantidad, $id);
        return $stmt->execute();
    }

    public function decrementStock($id, $cantidad) {
        $stmt = $this->db->prepare("UPDATE productos SET stock=stock-?, stock_unidades=GREATEST(0,stock_unidades-?) WHERE id=? AND stock>=?");
        $stmt->bind_param("iiii", $cantidad, $cantidad, $id, $cantidad);
        return $stmt->execute();
    }

    public function delete($id) {
        foreach ($this->getImagenes($id) as $img) {
            $ruta = UPLOAD_DIR . basename($img['ruta_imagen']);
            if (file_exists($ruta)) @unlink($ruta);
        }
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function addImagen($productoId, $ruta, $orden) {
        $stmt = $this->db->prepare("INSERT INTO producto_imagenes (producto_id,ruta_imagen,orden) VALUES (?,?,?)");
        $stmt->bind_param("isi", $productoId, $ruta, $orden);
        return $stmt->execute();
    }

    public function deleteImagen($imgId, $productoId) {
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM producto_imagenes WHERE id=? AND producto_id=?");
        $stmt->bind_param("ii", $imgId, $productoId);
        $stmt->execute();
        $img = $stmt->get_result()->fetch_assoc();
        if ($img) {
            $ruta = UPLOAD_DIR . basename($img['ruta_imagen']);
            if (file_exists($ruta)) @unlink($ruta);
            $del = $this->db->prepare("DELETE FROM producto_imagenes WHERE id=?");
            $del->bind_param("i", $imgId);
            return $del->execute();
        }
        return false;
    }

    public function search($term) {
        $e = "%" . $this->db->escape($term) . "%";
        $r = $this->db->query(
            "SELECT p.*, c.nombre AS categoria_nombre,
             (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id=p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal,
             (COALESCE(p.stock_docenas,0)*COALESCE(p.unidades_por_docena,12)+COALESCE(p.stock_unidades,0)) AS stock_total
             FROM productos p LEFT JOIN categorias c ON c.id=p.categoria_id
             WHERE p.estado=1 AND (p.nombre LIKE '$e' OR p.descripcion LIKE '$e' OR c.nombre LIKE '$e')
             ORDER BY p.nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ---- ESTADÍSTICAS PARA CARDS ---- */
    public function getStats() {
        $db = $this->db;
        $total    = (int)($db->query("SELECT COUNT(*) AS t FROM productos")?->fetch_assoc()['t'] ?? 0);
        $activos  = (int)($db->query("SELECT COUNT(*) AS t FROM productos WHERE estado=1")?->fetch_assoc()['t'] ?? 0);
        $sinStock = (int)($db->query("SELECT COUNT(*) AS t FROM productos WHERE estado=1 AND stock=0")?->fetch_assoc()['t'] ?? 0);
        $critico  = (int)($db->query("SELECT COUNT(*) AS t FROM productos WHERE estado=1 AND stock>0 AND stock<=5")?->fetch_assoc()['t'] ?? 0);
        $valComp  = (float)($db->query("SELECT COALESCE(SUM(stock*precio_compra),0) AS t FROM productos WHERE estado=1")?->fetch_assoc()['t'] ?? 0);
        $valVenta = (float)($db->query("SELECT COALESCE(SUM(stock*precio_venta),0) AS t FROM productos WHERE estado=1")?->fetch_assoc()['t'] ?? 0);
        $ventasMes = (float)($db->query("SELECT COALESCE(SUM(vd.subtotal),0) AS t FROM venta_detalle vd JOIN ventas v ON v.id=vd.venta_id WHERE v.estado=1 AND MONTH(v.fecha)=MONTH(CURDATE()) AND YEAR(v.fecha)=YEAR(CURDATE())")?->fetch_assoc()['t'] ?? 0);
        $ganMes   = (float)($db->query("SELECT COALESCE(SUM(vd.subtotal - (p.precio_compra*vd.cantidad)),0) AS t FROM venta_detalle vd JOIN ventas v ON v.id=vd.venta_id JOIN productos p ON p.id=vd.producto_id WHERE v.estado=1 AND MONTH(v.fecha)=MONTH(CURDATE()) AND YEAR(v.fecha)=YEAR(CURDATE())")?->fetch_assoc()['t'] ?? 0);
        return compact('total','activos','sinStock','critico','valComp','valVenta','ventasMes','ganMes');
    }

    /* ---- REPORTES ---- */
    public function getTopVendidos($limit = 10, $desde = null, $hasta = null) {
        $conds = ["v.estado=1"];
        if ($desde) $conds[] = "DATE(v.fecha)>='" . $this->db->escape($desde) . "'";
        if ($hasta) $conds[] = "DATE(v.fecha)<='" . $this->db->escape($hasta) . "'";
        $where = "WHERE " . implode(" AND ", $conds);
        $r = $this->db->query(
            "SELECT p.id, p.nombre, c.nombre AS categoria,
             p.precio_compra, p.precio_venta,
             SUM(vd.cantidad) AS total_unidades,
             SUM(vd.subtotal) AS total_ingresos,
             SUM(vd.subtotal - (p.precio_compra * vd.cantidad)) AS ganancia_total,
             (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id=p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
             FROM venta_detalle vd
             JOIN ventas v      ON v.id  = vd.venta_id
             JOIN productos p   ON p.id  = vd.producto_id
             LEFT JOIN categorias c ON c.id = p.categoria_id
             $where
             GROUP BY p.id, p.nombre, c.nombre, p.precio_compra, p.precio_venta
             ORDER BY total_unidades DESC
             LIMIT " . intval($limit)
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getInventarioValorizado() {
        $r = $this->db->query(
            "SELECT p.*, c.nombre AS categoria_nombre,
             (COALESCE(p.stock_docenas,0)*COALESCE(p.unidades_por_docena,12)+COALESCE(p.stock_unidades,0)) AS stock_total,
             (p.stock*p.precio_compra) AS valor_compra,
             (p.stock*p.precio_venta)  AS valor_venta,
             (p.stock*(p.precio_venta-p.precio_compra)) AS ganancia_potencial,
             (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id=p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
             FROM productos p
             LEFT JOIN categorias c ON c.id=p.categoria_id
             ORDER BY c.nombre ASC, p.nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getGananciasPorProducto($desde = null, $hasta = null) {
        $conds = ["v.estado=1"];
        if ($desde) $conds[] = "DATE(v.fecha)>='" . $this->db->escape($desde) . "'";
        if ($hasta) $conds[] = "DATE(v.fecha)<='" . $this->db->escape($hasta) . "'";
        $where = "WHERE " . implode(" AND ", $conds);
        $r = $this->db->query(
            "SELECT p.id, p.nombre, c.nombre AS categoria,
             p.precio_compra, p.precio_venta,
             SUM(vd.cantidad) AS unidades_vendidas,
             SUM(vd.subtotal) AS ingresos,
             SUM(p.precio_compra * vd.cantidad) AS costo,
             SUM(vd.subtotal - (p.precio_compra * vd.cantidad)) AS ganancia
             FROM venta_detalle vd
             JOIN ventas v    ON v.id  = vd.venta_id
             JOIN productos p ON p.id  = vd.producto_id
             LEFT JOIN categorias c ON c.id = p.categoria_id
             $where
             GROUP BY p.id, p.nombre, c.nombre, p.precio_compra, p.precio_venta
             ORDER BY ganancia DESC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
