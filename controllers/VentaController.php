<?php
// controllers/VentaController.php
require_once __DIR__ . '/../models/Venta.php';

class VentaController {
    private $model;
    public function __construct() { $this->model = new Venta(); }

    public function getAll($limit=null,$uid=null)     { return $this->model->getAll($limit,$uid); }
    public function findById($id)                      { return ['venta'=>$this->model->findById($id),'detalle'=>$this->model->getDetalle($id)]; }

    public function create($uid,$tipoPago,$items) {
        if (empty($items))                            return ['success'=>false,'message'=>'El carrito está vacío'];
        if (!in_array($tipoPago,['efectivo','qr']))   return ['success'=>false,'message'=>'Tipo de pago inválido'];
        $total = array_reduce($items, fn($s,$i)=>$s+floatval($i['precio'])*intval($i['cantidad']), 0);
        if ($total<=0) return ['success'=>false,'message'=>'Total inválido'];
        try { $id=$this->model->create($uid,$total,$tipoPago,$items); return ['success'=>true,'message'=>'Venta registrada','venta_id'=>$id]; }
        catch (Exception $e) { return ['success'=>false,'message'=>$e->getMessage()]; }
    }

    public function createConDescuento($uid,$tipoPago,$items,$descuento=0) {
        if (empty($items))                           return ['success'=>false,'message'=>'El carrito está vacío'];
        if (!in_array($tipoPago,['efectivo','qr']))  return ['success'=>false,'message'=>'Tipo de pago inválido'];
        $sub = array_reduce($items, fn($s,$i)=>$s+floatval($i['precio'])*intval($i['cantidad']), 0);
        $desc = max(0,min(floatval($descuento),$sub));
        $total = round($sub-$desc,2);
        if ($total<0) return ['success'=>false,'message'=>'Total inválido'];
        try { $id=$this->model->create($uid,$total,$tipoPago,$items); return ['success'=>true,'message'=>'Venta registrada','venta_id'=>$id,'total'=>$total]; }
        catch (Exception $e) { return ['success'=>false,'message'=>$e->getMessage()]; }
    }

    public function anular($id,$motivo='') {
        $motivo=trim($motivo)?:'Anulada por administrador';
        try { $this->model->anular($id,$motivo); return ['success'=>true,'message'=>'Venta #'.$id.' anulada correctamente']; }
        catch (Exception $e) { return ['success'=>false,'message'=>$e->getMessage()]; }
    }

    public function getStats($uid=null) {
        return [
            'total_hoy'        => $this->model->getTotalHoy($uid),
            'total_mes'        => $this->model->getTotalMes($uid),
            'count_hoy'        => $this->model->getCountHoy($uid),
            'ventas_semana'    => $this->model->getVentasPorDia(7,$uid),
            'ingresos_egresos' => $this->model->getIngresosEgresosPorDia(7,$uid),
        ];
    }

    public function getVentasRango($d,$h,$uid=null) { return $this->model->getVentasRango($d,$h,$uid); }
    public function getResumenMensual($m=6)          { return $this->model->getResumenMensual($m); }
    public function getGananciasRango($d, $h)  { return $this->model->getGananciasRango($d, $h); }
    public function getArqueoMensual($m=6)      { return $this->model->getArqueoMensual($m); }

}
