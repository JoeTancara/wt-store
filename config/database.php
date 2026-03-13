<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'joewt');
define('DB_PASS', 'technological.world.dev@gmail.com+*+*+*+*+*WT7');
define('DB_NAME', 'wtstore');
//define('DB_NAME', 'catalogo_db');
define('BASE_URL', 'https://store.technologicalworld.website');
//define('BASE_URL', 'http://localhost/store');
define('UPLOAD_DIR', __DIR__ . '/../uploads/productos/');
define('UPLOAD_URL', BASE_URL . '/uploads/productos/');
define('MAX_IMAGES', 3);

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            die(json_encode(['error' => 'Error de conexión: ' . $this->connection->connect_error]));
        }
        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function lastInsertId() {
        return $this->connection->insert_id;
    }
}
