<?php
// controllers/CategoriaController.php
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../config/auth.php';

class CategoriaController {
    private $model;

    public function __construct() {
        $this->model = new Categoria();
    }

    public function getAll($soloActivas = false) {
        return $this->model->getAll($soloActivas);
    }

    public function getWithCount() {
        return $this->model->getWithProductCount();
    }

    public function findById($id) {
        return $this->model->findById($id);
    }

    public function create($data) {
        $nombre      = trim(sanitize($data['nombre'] ?? ''));
        $descripcion = trim(sanitize($data['descripcion'] ?? ''));
        $estado      = intval($data['estado'] ?? 1);
        if (!$nombre) return ['success' => false, 'message' => 'El nombre es obligatorio'];
        $ok = $this->model->create($nombre, $descripcion, $estado);
        if (!$ok) return ['success' => false, 'message' => 'Error al crear la categoría'];
        return ['success' => true, 'message' => 'Categoría creada correctamente'];
    }

    public function update($id, $data) {
        $nombre      = trim(sanitize($data['nombre'] ?? ''));
        $descripcion = trim(sanitize($data['descripcion'] ?? ''));
        $estado      = intval($data['estado'] ?? 1);
        if (!$nombre) return ['success' => false, 'message' => 'El nombre es obligatorio'];
        $ok = $this->model->update($id, $nombre, $descripcion, $estado);
        if (!$ok) return ['success' => false, 'message' => 'Error al actualizar la categoría'];
        return ['success' => true, 'message' => 'Categoría actualizada correctamente'];
    }

    public function delete($id) {
        return $this->model->delete($id);
    }
}
