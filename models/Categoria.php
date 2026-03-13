<?php

require_once __DIR__ . '/../config/database.php';

class Categoria {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll($soloActivas = false) {
        $where = $soloActivas ? "WHERE estado = 1" : "";
        $result = $this->db->query("SELECT * FROM categorias $where ORDER BY nombre ASC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($nombre, $descripcion, $estado) {
        $stmt = $this->db->prepare("INSERT INTO categorias (nombre, descripcion, estado) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nombre, $descripcion, $estado);
        return $stmt->execute();
    }

    public function update($id, $nombre, $descripcion, $estado) {
        $stmt = $this->db->prepare("UPDATE categorias SET nombre=?, descripcion=?, estado=? WHERE id=?");
        $stmt->bind_param("ssii", $nombre, $descripcion, $estado, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        // Verificar que no tenga productos
        $check = $this->db->prepare("SELECT COUNT(*) AS total FROM productos WHERE categoria_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $row = $check->get_result()->fetch_assoc();
        if ((int)$row['total'] > 0) {
            return ['success' => false, 'message' => 'No se puede eliminar: la categoría tiene ' . $row['total'] . ' producto(s) asociado(s)'];
        }
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if (!$ok) return ['success' => false, 'message' => 'Error al eliminar la categoría'];
        return ['success' => true, 'message' => 'Categoría eliminada correctamente'];
    }

    public function getWithProductCount() {
        $result = $this->db->query("
            SELECT c.*, COUNT(p.id) AS total_productos
            FROM categorias c
            LEFT JOIN productos p ON p.categoria_id = c.id
            GROUP BY c.id
            ORDER BY c.nombre ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
