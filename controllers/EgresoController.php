<?php
// controllers/EgresoController.php
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../config/auth.php';

class EgresoController {
    private $model;

    public function __construct() {
        $this->model = new Egreso();
    }

    public function getAll() {
        return $this->model->getAll();
    }

    public function findById($id) {
        return $this->model->findById($id);
    }

    public function create($usuarioId, $data) {
        $concepto = trim(sanitize($data['concepto'] ?? ''));
        $monto    = floatval($data['monto'] ?? 0);
        $fecha    = $data['fecha'] ?? date('Y-m-d');
        if (!$concepto) return ['success' => false, 'message' => 'El concepto es obligatorio'];
        if ($monto <= 0) return ['success' => false, 'message' => 'El monto debe ser mayor a 0'];
        $ok = $this->model->create($usuarioId, $concepto, $monto, $fecha);
        if (!$ok) return ['success' => false, 'message' => 'Error al crear el egreso'];
        return ['success' => true, 'message' => 'Egreso registrado correctamente'];
    }

    public function update($id, $data) {
        $concepto = trim(sanitize($data['concepto'] ?? ''));
        $monto    = floatval($data['monto'] ?? 0);
        $fecha    = $data['fecha'] ?? date('Y-m-d');
        if (!$concepto) return ['success' => false, 'message' => 'El concepto es obligatorio'];
        if ($monto <= 0) return ['success' => false, 'message' => 'El monto debe ser mayor a 0'];
        $ok = $this->model->update($id, $concepto, $monto, $fecha);
        if (!$ok) return ['success' => false, 'message' => 'Error al actualizar el egreso'];
        return ['success' => true, 'message' => 'Egreso actualizado correctamente'];
    }

    public function delete($id) {
        $ok = $this->model->delete($id);
        if (!$ok) return ['success' => false, 'message' => 'Error al eliminar el egreso'];
        return ['success' => true, 'message' => 'Egreso eliminado correctamente'];
    }

    public function getStats() {
        return [
            'total_hoy' => $this->model->getTotalHoy(),
            'total_mes' => $this->model->getTotalMes(),
        ];
    }
}
