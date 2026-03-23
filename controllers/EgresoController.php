<?php
// controllers/EgresoController.php
require_once __DIR__ . '/../models/Egreso.php';
require_once __DIR__ . '/../config/auth.php';

class EgresoController {
    private $model;
    public function __construct() { $this->model = new Egreso(); }

    public function getAll($limit=null,$desde=null,$hasta=null) {
        return $this->model->getAllFiltrado($limit,$desde,$hasta);
    }

    public function findById($id)   { return $this->model->findById($id); }

    public function create($uid,$data) {
        $concepto = trim(sanitize($data['concepto'] ?? ''));
        $monto    = floatval($data['monto'] ?? 0);
        $fecha    = $data['fecha'] ?? date('Y-m-d');
        if (!$concepto) return ['success'=>false,'message'=>'El concepto es obligatorio'];
        if ($monto<=0)  return ['success'=>false,'message'=>'El monto debe ser mayor a 0'];
        return $this->model->create($uid,$concepto,$monto,$fecha)
            ? ['success'=>true,'message'=>'Egreso registrado correctamente']
            : ['success'=>false,'message'=>'Error al crear el egreso'];
    }

    public function update($id,$data) {
        $concepto = trim(sanitize($data['concepto'] ?? ''));
        $monto    = floatval($data['monto'] ?? 0);
        $fecha    = $data['fecha'] ?? date('Y-m-d');
        if (!$concepto) return ['success'=>false,'message'=>'El concepto es obligatorio'];
        if ($monto<=0)  return ['success'=>false,'message'=>'El monto debe ser mayor a 0'];
        return $this->model->update($id,$concepto,$monto,$fecha)
            ? ['success'=>true,'message'=>'Egreso actualizado correctamente']
            : ['success'=>false,'message'=>'Error al actualizar el egreso'];
    }

    public function delete($id) {
        return $this->model->delete($id)
            ? ['success'=>true,'message'=>'Egreso eliminado correctamente']
            : ['success'=>false,'message'=>'Error al eliminar el egreso'];
    }

    public function getStats() {
        return ['total_hoy'=>$this->model->getTotalHoy(),'total_mes'=>$this->model->getTotalMes()];
    }
}
