<?php
// models/Configuracion.php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function get($clave, $default = '') {
        $stmt = $this->db->prepare("SELECT valor FROM configuracion_sitio WHERE clave = ?");
        if (!$stmt) return $default;
        $stmt->bind_param("s", $clave);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['valor'] : $default;
    }

    public function getAll() {
        $r = $this->db->query("SELECT * FROM configuracion_sitio ORDER BY clave ASC");
        if (!$r) return [];
        $data = [];
        foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) {
            $data[$row['clave']] = $row;
        }
        return $data;
    }

    public function set($clave, $valor) {
        $stmt = $this->db->prepare("UPDATE configuracion_sitio SET valor=? WHERE clave=?");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $valor, $clave);
        return $stmt->execute();
    }

    public function setMultiple(array $pares) {
        foreach ($pares as $clave => $valor) $this->set($clave, $valor);
        return true;
    }

    /* ---- BANNERS ---- */
    public function getBanners($soloActivos = false) {
        $where = $soloActivos ? "WHERE activo=1" : "";
        $r = $this->db->query("SELECT * FROM banner_carousel $where ORDER BY orden ASC, id ASC");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getBannerById($id) {
        $stmt = $this->db->prepare("SELECT * FROM banner_carousel WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createBanner($titulo, $subtitulo, $imagen, $enlace, $orden, $activo) {
        $stmt = $this->db->prepare(
            "INSERT INTO banner_carousel (titulo,subtitulo,imagen,enlace,orden,activo) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("ssssii", $titulo, $subtitulo, $imagen, $enlace, $orden, $activo);
        return $stmt->execute() ? $this->db->lastInsertId() : false;
    }

    public function updateBanner($id, $titulo, $subtitulo, $imagenNueva, $enlace, $orden, $activo) {
        if ($imagenNueva) {
            $stmt = $this->db->prepare(
                "UPDATE banner_carousel SET titulo=?,subtitulo=?,imagen=?,enlace=?,orden=?,activo=? WHERE id=?"
            );
            $stmt->bind_param("ssssiii", $titulo, $subtitulo, $imagenNueva, $enlace, $orden, $activo, $id);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE banner_carousel SET titulo=?,subtitulo=?,enlace=?,orden=?,activo=? WHERE id=?"
            );
            $stmt->bind_param("sssiii", $titulo, $subtitulo, $enlace, $orden, $activo, $id);
        }
        return $stmt->execute();
    }

    public function deleteBanner($id) {
        $b = $this->getBannerById($id);
        if ($b && $b['imagen']) {
            @unlink(__DIR__ . '/../uploads/banners/' . basename($b['imagen']));
        }
        $stmt = $this->db->prepare("DELETE FROM banner_carousel WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function toggleBanner($id) {
        $stmt = $this->db->prepare("UPDATE banner_carousel SET activo = 1-activo WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
