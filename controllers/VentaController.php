<?php

require_once __DIR__ . '/../models/Venta.php';

class VentaController {
    private $model;

    public function __construct() {
        $this->model = new Venta();
    }

    public function getAll($limit = null) {
        return $this->model->getAll($limit);
    }

    public function findById($id) {
        $venta   = $this->model->findById($id);
        $detalle = $this->model->getDetalle($id);
        return ['venta' => $venta, 'detalle' => $detalle];
    }

    public function create($usuarioId, $tipoPago, $items) {
        if (empty($items)) {
            return ['success' => false, 'message' => 'El carrito está vacío'];
        }
        if (!in_array($tipoPago, ['efectivo', 'qr'])) {
            return ['success' => false, 'message' => 'Tipo de pago inválido'];
        }

        $total = 0;
        foreach ($items as $item) {
            $total += floatval($item['precio']) * intval($item['cantidad']);
        }
        if ($total <= 0) {
            return ['success' => false, 'message' => 'Total inválido'];
        }

        try {
            $ventaId = $this->model->create($usuarioId, $total, $tipoPago, $items);
            return ['success' => true, 'message' => 'Venta registrada correctamente', 'venta_id' => $ventaId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function anular($id, $motivo = '') {
        $motivo = trim($motivo) ?: 'Anulada por administrador';
        try {
            $this->model->anular($id, $motivo);
            return ['success' => true, 'message' => 'Venta #' . $id . ' anulada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getStats() {
        return [
            'total_hoy'         => $this->model->getTotalHoy(),
            'total_mes'         => $this->model->getTotalMes(),
            'count_hoy'         => $this->model->getCountHoy(),
            'ventas_semana'     => $this->model->getVentasPorDia(7),
            'ingresos_egresos'  => $this->model->getIngresosEgresosPorDia(7),
        ];
    }
}
