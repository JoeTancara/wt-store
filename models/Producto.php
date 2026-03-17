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
                (SELECT ruta_imagen FROM producto_imagenes
                 WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
                FROM productos p
                LEFT JOIN categorias c ON c.id = p.categoria_id"
             . (count($where) ? " WHERE " . implode(" AND ", $where) : "")
             . " ORDER BY p.fecha_creacion DESC";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre,
             (SELECT ruta_imagen FROM producto_imagenes
              WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
             FROM productos p
             LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getImagenes($productoId) {
        $stmt = $this->db->prepare("SELECT * FROM producto_imagenes WHERE producto_id = ? ORDER BY orden ASC");
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countImagenes($productoId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM producto_imagenes WHERE producto_id = ?");
        $stmt->bind_param("i", $productoId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['total'];
    }

    // tipos: i=catId, s=nombre, s=desc, d=precioCompra, d=precioVenta, i=stock, i=estado  => issddii (7)
    public function create($categoriaId, $nombre, $descripcion, $precioCompra, $precioVenta, $stock, $estado) {
        $stmt = $this->db->prepare(
            "INSERT INTO productos (categoria_id, nombre, descripcion, precio_compra, precio_venta, stock, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issddii", $categoriaId, $nombre, $descripcion, $precioCompra, $precioVenta, $stock, $estado);
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    // tipos: i=catId, s=nombre, s=desc, d=precioCompra, d=precioVenta, i=stock, i=estado, i=id  => issddiii (8)
    public function update($id, $categoriaId, $nombre, $descripcion, $precioCompra, $precioVenta, $stock, $estado) {
        $stmt = $this->db->prepare(
            "UPDATE productos
             SET categoria_id=?, nombre=?, descripcion=?, precio_compra=?, precio_venta=?, stock=?, estado=?
             WHERE id=?"
        );
        $stmt->bind_param("issddiii", $categoriaId, $nombre, $descripcion, $precioCompra, $precioVenta, $stock, $estado, $id);
        return $stmt->execute();
    }

    public function updateStock($id, $cantidad) {
        $stmt = $this->db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $cantidad, $id);
        return $stmt->execute();
    }

    public function decrementStock($id, $cantidad) {
        $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt->bind_param("iii", $cantidad, $id, $cantidad);
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

    public function addImagen($productoId, $rutaImagen, $orden) {
        $stmt = $this->db->prepare(
            "INSERT INTO producto_imagenes (producto_id, ruta_imagen, orden) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("isi", $productoId, $rutaImagen, $orden);
        return $stmt->execute();
    }

    public function deleteImagen($imagenId, $productoId) {
        $stmt = $this->db->prepare(
            "SELECT ruta_imagen FROM producto_imagenes WHERE id=? AND producto_id=?"
        );
        $stmt->bind_param("ii", $imagenId, $productoId);
        $stmt->execute();
        $img = $stmt->get_result()->fetch_assoc();
        if ($img) {
            $ruta = UPLOAD_DIR . basename($img['ruta_imagen']);
            if (file_exists($ruta)) @unlink($ruta);
            $del = $this->db->prepare("DELETE FROM producto_imagenes WHERE id=?");
            $del->bind_param("i", $imagenId);
            return $del->execute();
        }
        return false;
    }

    public function search($term) {
        $e = "%" . $this->db->escape($term) . "%";
        $r = $this->db->query(
            "SELECT p.*, c.nombre AS categoria_nombre,
             (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
             FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = 1 AND (p.nombre LIKE '$e' OR p.descripcion LIKE '$e' OR c.nombre LIKE '$e')
             ORDER BY p.nombre ASC"
        );
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
