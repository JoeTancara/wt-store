<?php
// controllers/ProductoController.php
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/auth.php';

class ProductoController {
    private $model;
    public function __construct() { $this->model = new Producto(); }

    public function getAll($soloActivos=false) { return $this->model->getAll($soloActivos); }

    public function findById($id) {
        $p = $this->model->findById($id);
        if ($p) $p['imagenes'] = $this->model->getImagenes($id);
        return $p;
    }

    public function create($data,$files=[]) {
        $nombre   = trim(sanitize($data['nombre']      ?? ''));
        $desc     = trim(sanitize($data['descripcion'] ?? ''));
        $catId    = intval($data['categoria_id']       ?? 0);
        $pCompra  = floatval($data['precio_compra']    ?? 0);
        $pVenta   = floatval($data['precio_venta']     ?? 0);
        $pDocena  = floatval($data['precio_docena']    ?? 0);
        $docenas  = intval($data['stock_docenas']      ?? 0);
        $unidades = intval($data['stock_unidades']     ?? 0);
        $upd      = max(1, intval($data['unidades_por_docena'] ?? 12));
        $estado   = intval($data['estado']             ?? 1);
        $stock    = ($docenas * $upd) + $unidades;

        if (!$nombre)       return ['success'=>false,'message'=>'El nombre es obligatorio'];
        if (!$catId)        return ['success'=>false,'message'=>'La categoría es obligatoria'];
        if ($pVenta <= 0)   return ['success'=>false,'message'=>'El precio de venta debe ser mayor a 0'];

        $id = $this->model->create($catId,$nombre,$desc,$pCompra,$pVenta,$pDocena,$stock,$docenas,$unidades,$upd,$estado);
        if (!$id) return ['success'=>false,'message'=>'Error al crear el producto'];
        if (!empty($files['imagenes']['name'][0])) $this->uploadImages($id,$files['imagenes']);
        return ['success'=>true,'message'=>'Producto creado correctamente','id'=>$id];
    }

    public function update($id,$data,$files=[]) {
        $nombre   = trim(sanitize($data['nombre']      ?? ''));
        $desc     = trim(sanitize($data['descripcion'] ?? ''));
        $catId    = intval($data['categoria_id']       ?? 0);
        $pCompra  = floatval($data['precio_compra']    ?? 0);
        $pVenta   = floatval($data['precio_venta']     ?? 0);
        $pDocena  = floatval($data['precio_docena']    ?? 0);
        $docenas  = intval($data['stock_docenas']      ?? 0);
        $unidades = intval($data['stock_unidades']     ?? 0);
        $upd      = max(1, intval($data['unidades_por_docena'] ?? 12));
        $estado   = intval($data['estado']             ?? 1);

        if (!$nombre)       return ['success'=>false,'message'=>'El nombre es obligatorio'];
        if (!$catId)        return ['success'=>false,'message'=>'La categoría es obligatoria'];
        if ($pVenta <= 0)   return ['success'=>false,'message'=>'El precio de venta debe ser mayor a 0'];

        $ok = $this->model->update($id,$catId,$nombre,$desc,$pCompra,$pVenta,$pDocena,$docenas,$unidades,$upd,$estado);
        if (!$ok) return ['success'=>false,'message'=>'Error al actualizar el producto'];
        if (!empty($files['imagenes']['name'][0])) $this->uploadImages($id,$files['imagenes']);
        return ['success'=>true,'message'=>'Producto actualizado correctamente'];
    }

    private function uploadImages($productoId,$filesArray) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $orden   = $this->model->countImagenes($productoId);
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR,0755,true);
        for ($i=0; $i<count($filesArray['name']); $i++) {
            if ($this->model->countImagenes($productoId) >= MAX_IMAGES) break;
            if ($filesArray['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (!in_array($filesArray['type'][$i],$allowed)) continue;
            if ($filesArray['size'][$i] > 5*1024*1024) continue;
            $ext  = strtolower(pathinfo($filesArray['name'][$i],PATHINFO_EXTENSION));
            $file = 'prod_'.$productoId.'_'.uniqid().'.'.$ext;
            if (move_uploaded_file($filesArray['tmp_name'][$i],UPLOAD_DIR.$file))
                $this->model->addImagen($productoId,$file,$orden++);
        }
    }

    public function deleteImagen($imgId,$productoId) { return $this->model->deleteImagen($imgId,$productoId); }
    public function delete($id) {
        return $this->model->delete($id)
            ? ['success'=>true,'message'=>'Producto eliminado correctamente']
            : ['success'=>false,'message'=>'Error al eliminar el producto'];
    }
    public function updateStock($id,$cantidad) {
        return $this->model->updateStock($id,$cantidad)
            ? ['success'=>true,'message'=>'Stock actualizado']
            : ['success'=>false,'message'=>'Error al actualizar stock'];
    }
    public function updateStockDocenas($id,$docenas,$unidades) {
        return $this->model->updateStockDocenas($id,$docenas,$unidades)
            ? ['success'=>true,'message'=>'Stock actualizado']
            : ['success'=>false,'message'=>'Error al actualizar stock'];
    }
    public function search($term)              { return $this->model->search($term); }
    public function getTopVendidos($l=10,$d=null,$h=null) { return $this->model->getTopVendidos($l,$d,$h); }
    public function getInventarioValorizado()  { return $this->model->getInventarioValorizado(); }
    public function getStats()                        { return $this->model->getStats(); }
    public function getGananciasPorProducto($d=null,$h=null) { return $this->model->getGananciasPorProducto($d,$h); }

}
