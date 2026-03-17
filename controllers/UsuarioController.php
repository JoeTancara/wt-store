<?php
// controllers/UsuarioController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../config/auth.php';

class UsuarioController {
    private $model;

    public function __construct() {
        $this->model = new Usuario();
    }

    public function getAll() {
        return $this->model->getAll();
    }

    public function findById($id) {
        return $this->model->findById($id);
    }

    public function create($data) {
        $nombre   = trim(sanitize($data['nombre'] ?? ''));
        $email    = trim(sanitize($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $rol      = in_array($data['rol'] ?? '', ['admin','vendedor']) ? $data['rol'] : 'vendedor';
        $estado   = intval($data['estado'] ?? 1);

        if (!$nombre)   return ['success' => false, 'message' => 'El nombre es obligatorio'];
        if (!$email)    return ['success' => false, 'message' => 'El email es obligatorio'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Email inválido'];
        if (!$password) return ['success' => false, 'message' => 'La contraseña es obligatoria'];
        if (strlen($password) < 6) return ['success' => false, 'message' => 'La contraseña debe tener mínimo 6 caracteres'];
        if ($this->model->emailExists($email)) return ['success' => false, 'message' => 'El email ya está registrado'];

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ok   = $this->model->create($nombre, $email, $hash, $rol, $estado);
        if (!$ok) return ['success' => false, 'message' => 'Error al crear el usuario'];
        return ['success' => true, 'message' => 'Usuario creado correctamente'];
    }

    public function update($id, $data) {
        $nombre = trim(sanitize($data['nombre'] ?? ''));
        $email  = trim(sanitize($data['email'] ?? ''));
        $rol    = in_array($data['rol'] ?? '', ['admin','vendedor']) ? $data['rol'] : 'vendedor';
        $estado = intval($data['estado'] ?? 1);

        if (!$nombre) return ['success' => false, 'message' => 'El nombre es obligatorio'];
        if (!$email)  return ['success' => false, 'message' => 'El email es obligatorio'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'message' => 'Email inválido'];

        // Verificar email duplicado
        $existing = $this->model->findByEmail($email);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'message' => 'El email ya está en uso'];
        }

        $ok = $this->model->update($id, $nombre, $email, $rol, $estado);
        if (!$ok) return ['success' => false, 'message' => 'Error al actualizar el usuario'];

        // Actualizar contraseña si se proporcionó
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener mínimo 6 caracteres'];
            }
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $this->model->updatePassword($id, $hash);
        }

        // Si el usuario editó su propia cuenta, actualizar la sesión
        $me = currentUser();
        if ($me && $me['id'] == $id) {
            $_SESSION['user']['nombre'] = $nombre;
            $_SESSION['user']['email']  = $email;
            $_SESSION['user']['rol']    = $rol;
        }

        return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
    }

    public function delete($id) {
        $me = currentUser();
        if ($me && $me['id'] == $id) {
            return ['success' => false, 'message' => 'No puedes eliminar tu propia cuenta'];
        }
        $ok = $this->model->delete($id);
        if (!$ok) return ['success' => false, 'message' => 'Error al eliminar el usuario'];
        return ['success' => true, 'message' => 'Usuario eliminado correctamente'];
    }
}
