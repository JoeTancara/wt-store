<?php

require_once __DIR__ . '/../config/database.php';

class Producto {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($soloActivos = false, $categoriaId = null) {
        $where = [];
        if ($soloActivos)  $where[] = "p.estado = 1";
        if ($categoriaId)  $where[] = "p.categoria_id = " . intval($categoriaId);
        $whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        $result = $this->db->query("
            SELECT p.*, c.nombre AS categoria_nombre,
                   (SELECT ruta_imagen FROM producto_imagenes
                    WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            $whereSQL
            ORDER BY p.fecha_creacion DESC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE p.id = ?
        ");
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
        $row = $stmt->get_result()->fetch_assoc();
        return (int)$row['total'];
    }

    // Tipos: i=categoriaId, s=nombre, s=descripcion, d=precio, i=stock, i=estado
    public function create($categoriaId, $nombre, $descripcion, $precio, $stock, $estado) {
        $stmt = $this->db->prepare("INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdii", $categoriaId, $nombre, $descripcion, $precio, $stock, $estado);
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Tipos: i=categoriaId, s=nombre, s=descripcion, d=precio, i=stock, i=estado, i=id
    public function update($id, $categoriaId, $nombre, $descripcion, $precio, $stock, $estado) {
        $stmt = $this->db->prepare("UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio=?, stock=?, estado=? WHERE id=?");
        $stmt->bind_param("issdiii", $categoriaId, $nombre, $descripcion, $precio, $stock, $estado, $id);
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
        // Eliminar imágenes físicas
        $imagenes = $this->getImagenes($id);
        foreach ($imagenes as $img) {
            $ruta = UPLOAD_DIR . basename($img['ruta_imagen']);
            if (file_exists($ruta)) @unlink($ruta);
        }
        $stmt = $this->db->prepare("DELETE FROM productos WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function addImagen($productoId, $rutaImagen, $orden) {
        $stmt = $this->db->prepare("INSERT INTO producto_imagenes (producto_id, ruta_imagen, orden) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $productoId, $rutaImagen, $orden);
        return $stmt->execute();
    }

    public function deleteImagen($imagenId, $productoId) {
        $stmt = $this->db->prepare("SELECT ruta_imagen FROM producto_imagenes WHERE id=? AND producto_id=?");
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
        $escaped = "%" . $this->db->escape($term) . "%";
        $result = $this->db->query("
            SELECT p.*, c.nombre AS categoria_nombre,
                   (SELECT ruta_imagen FROM producto_imagenes WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) AS imagen_principal
            FROM productos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE p.estado = 1
              AND (p.nombre LIKE '$escaped' OR p.descripcion LIKE '$escaped' OR c.nombre LIKE '$escaped')
            ORDER BY p.nombre ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
