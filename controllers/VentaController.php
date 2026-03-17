<?php
// controllers/VentaController.php
require_once __DIR__ . '/../models/Venta.php';

class VentaController {
    private $model;
    public function __construct() { $this->model = new Venta(); }

    public function getAll($limit = null, $usuarioId = null) {
        return $this->model->getAll($limit, $usuarioId);
    }

    public function findById($id) {
        return ['venta' => $this->model->findById($id), 'detalle' => $this->model->getDetalle($id)];
    }

    public function create($usuarioId, $tipoPago, $items) {
        if (empty($items))                            return ['success'=>false,'message'=>'El carrito esta vacio'];
        if (!in_array($tipoPago, ['efectivo','qr']))  return ['success'=>false,'message'=>'Tipo de pago invalido'];
        $total = array_reduce($items, fn($s,$i) => $s + floatval($i['precio']) * intval($i['cantidad']), 0);
        if ($total <= 0)                              return ['success'=>false,'message'=>'Total invalido'];
        try {
            $id = $this->model->create($usuarioId, $total, $tipoPago, $items);
            return ['success'=>true,'message'=>'Venta registrada','venta_id'=>$id];
        } catch (Exception $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    public function anular($id, $motivo = '') {
        $motivo = trim($motivo) ?: 'Anulada por administrador';
        try {
            $this->model->anular($id, $motivo);
            return ['success'=>true,'message'=>'Venta #'.$id.' anulada correctamente'];
        } catch (Exception $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    public function getStats($usuarioId = null) {
        return [
            'total_hoy'        => $this->model->getTotalHoy($usuarioId),
            'total_mes'        => $this->model->getTotalMes($usuarioId),
            'count_hoy'        => $this->model->getCountHoy($usuarioId),
            'ventas_semana'    => $this->model->getVentasPorDia(7, $usuarioId),
            'ingresos_egresos' => $this->model->getIngresosEgresosPorDia(7, $usuarioId),
        ];
    }

    public function createConDescuento($usuarioId, $tipoPago, $items, $descuento = 0) {
        if (empty($items))                           return ['success'=>false,'message'=>'El carrito esta vacio'];
        if (!in_array($tipoPago, ['efectivo','qr'])) return ['success'=>false,'message'=>'Tipo de pago invalido'];

        $subtotal  = array_reduce($items, fn($s,$i) => $s + floatval($i['precio']) * intval($i['cantidad']), 0);
        $descuento = max(0, min(floatval($descuento), $subtotal));
        $total     = round($subtotal - $descuento, 2);
        if ($total < 0) return ['success'=>false,'message'=>'Total invalido'];

        try {
            $id = $this->model->create($usuarioId, $total, $tipoPago, $items);
            return ['success'=>true,'message'=>'Venta registrada','venta_id'=>$id,'total'=>$total];
        } catch (Exception $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

}