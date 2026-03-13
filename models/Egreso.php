<?php

require_once __DIR__ . '/../config/database.php';

class Egreso {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        $result = $this->db->query("
            SELECT e.*, u.nombre AS usuario_nombre
            FROM egresos e
            LEFT JOIN usuarios u ON u.id = e.usuario_id
            ORDER BY e.fecha DESC, e.id DESC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM egresos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($usuarioId, $concepto, $monto, $fecha) {
        $stmt = $this->db->prepare("INSERT INTO egresos (usuario_id, concepto, monto, fecha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $usuarioId, $concepto, $monto, $fecha);
        return $stmt->execute();
    }

    public function update($id, $concepto, $monto, $fecha) {
        $stmt = $this->db->prepare("UPDATE egresos SET concepto=?, monto=?, fecha=? WHERE id=?");
        $stmt->bind_param("sdsi", $concepto, $monto, $fecha, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM egresos WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getTotalHoy() {
        $result = $this->db->query("SELECT COALESCE(SUM(monto),0) AS total FROM egresos WHERE fecha = CURDATE()");
        return $result ? floatval($result->fetch_assoc()['total']) : 0;
    }

    public function getTotalMes() {
        $result = $this->db->query("SELECT COALESCE(SUM(monto),0) AS total FROM egresos WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())");
        return $result ? floatval($result->fetch_assoc()['total']) : 0;
    }
}
