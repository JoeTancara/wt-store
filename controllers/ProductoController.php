<?php

require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../config/auth.php';

class ProductoController {
    private $model;

    public function __construct() {
        $this->model = new Producto();
    }

    public function getAll() {
        return $this->model->getAll();
    }

    public function findById($id) {
        $prod = $this->model->findById($id);
        if ($prod) {
            $prod['imagenes'] = $this->model->getImagenes($id);
        }
        return $prod;
    }

    public function create($data, $files = []) {
        $nombre      = trim(sanitize($data['nombre'] ?? ''));
        $descripcion = trim(sanitize($data['descripcion'] ?? ''));
        $categoriaId = intval($data['categoria_id'] ?? 0);
        $precio      = floatval($data['precio'] ?? 0);
        $stock       = intval($data['stock'] ?? 0);
        $estado      = intval($data['estado'] ?? 1);

        if (!$nombre)      return ['success' => false, 'message' => 'El nombre es obligatorio'];
        if (!$categoriaId) return ['success' => false, 'message' => 'La categoría es obligatoria'];
        if ($precio <= 0)  return ['success' => false, 'message' => 'El precio debe ser mayor a 0'];

        $id = $this->model->create($categoriaId, $nombre, $descripcion, $precio, $stock, $estado);
        if (!$id) return ['success' => false, 'message' => 'Error al crear el producto'];

        // Subir imágenes
        if (!empty($files['imagenes']['name'][0])) {
            $this->uploadImages($id, $files['imagenes']);
        }

        return ['success' => true, 'message' => 'Producto creado correctamente', 'id' => $id];
    }

    public function update($id, $data, $files = []) {
        $nombre      = trim(sanitize($data['nombre'] ?? ''));
        $descripcion = trim(sanitize($data['descripcion'] ?? ''));
        $categoriaId = intval($data['categoria_id'] ?? 0);
        $precio      = floatval($data['precio'] ?? 0);
        $stock       = intval($data['stock'] ?? 0);
        $estado      = intval($data['estado'] ?? 1);

        if (!$nombre)      return ['success' => false, 'message' => 'El nombre es obligatorio'];
        if (!$categoriaId) return ['success' => false, 'message' => 'La categoría es obligatoria'];
        if ($precio <= 0)  return ['success' => false, 'message' => 'El precio debe ser mayor a 0'];

        $ok = $this->model->update($id, $categoriaId, $nombre, $descripcion, $precio, $stock, $estado);
        if (!$ok) return ['success' => false, 'message' => 'Error al actualizar el producto'];

        // Subir nuevas imágenes (respetando el límite)
        if (!empty($files['imagenes']['name'][0])) {
            $this->uploadImages($id, $files['imagenes']);
        }

        return ['success' => true, 'message' => 'Producto actualizado correctamente'];
    }

    private function uploadImages($productoId, $filesArray) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $existing = $this->model->countImagenes($productoId);
        $orden    = $existing;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        for ($i = 0; $i < count($filesArray['name']); $i++) {
            if ($this->model->countImagenes($productoId) >= MAX_IMAGES) break;
            if ($filesArray['error'][$i] !== UPLOAD_ERR_OK) continue;
            if (!in_array($filesArray['type'][$i], $allowed))  continue;
            if ($filesArray['size'][$i] > 5 * 1024 * 1024)    continue; // 5 MB max

            $ext      = strtolower(pathinfo($filesArray['name'][$i], PATHINFO_EXTENSION));
            $filename = 'prod_' . $productoId . '_' . uniqid() . '.' . $ext;
            $dest     = UPLOAD_DIR . $filename;

            if (move_uploaded_file($filesArray['tmp_name'][$i], $dest)) {
                $this->model->addImagen($productoId, $filename, $orden++);
            }
        }
    }

    public function deleteImagen($imgId, $productoId) {
        return $this->model->deleteImagen($imgId, $productoId);
    }

    public function delete($id) {
        $ok = $this->model->delete($id);
        if (!$ok) return ['success' => false, 'message' => 'Error al eliminar el producto'];
        return ['success' => true, 'message' => 'Producto eliminado correctamente'];
    }

    public function updateStock($id, $cantidad) {
        $ok = $this->model->updateStock($id, $cantidad);
        if (!$ok) return ['success' => false, 'message' => 'Error al actualizar el stock'];
        return ['success' => true, 'message' => 'Stock actualizado correctamente'];
    }

    public function search($term) {
        return $this->model->search($term);
    }
}
