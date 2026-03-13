<?php

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../config/auth.php';

class AuthController {
    private $model;

    public function __construct() {
        $this->model = new Usuario();
    }

    public function login($email, $password) {
        $email = trim(sanitize($email));
        if (!$email || !$password) {
            return ['success' => false, 'message' => 'Ingresa tu email y contraseña'];
        }
        $user = $this->model->findByEmail($email);
        if (!$user || !$user['estado']) {
            return ['success' => false, 'message' => 'Credenciales inválidas o cuenta inactiva'];
        }
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['user'] = [
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'email'  => $user['email'],
            'rol'    => $user['rol'],
        ];
        return ['success' => true, 'message' => 'Bienvenido, ' . $user['nombre']];
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
    }
}
