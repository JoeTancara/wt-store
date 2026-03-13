<?php

require_once __DIR__ . '/../config/database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll() {
        $result = $this->db->query("SELECT id, nombre, email, rol, estado, fecha_creacion FROM usuarios ORDER BY fecha_creacion DESC");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create($nombre, $email, $passwordHash, $rol, $estado) {
        $stmt = $this->db->prepare("INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nombre, $email, $passwordHash, $rol, $estado);
        return $stmt->execute();
    }

    public function update($id, $nombre, $email, $rol, $estado) {
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, estado=? WHERE id=?");
        $stmt->bind_param("sssii", $nombre, $email, $rol, $estado, $id);
        return $stmt->execute();
    }

    public function updatePassword($id, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE usuarios SET password=? WHERE id=?");
        $stmt->bind_param("si", $passwordHash, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email=? AND id != ? LIMIT 1");
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
        }
        $stmt->execute();
        return (bool)$stmt->get_result()->fetch_assoc();
    }
}
