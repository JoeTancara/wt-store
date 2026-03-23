<?php
// models/Egreso.php
require_once __DIR__ . '/../config/database.php';

class Egreso {
    private $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function getAll() {
        $r=$this->db->query("SELECT e.*,u.nombre AS usuario_nombre FROM egresos e LEFT JOIN usuarios u ON u.id=e.usuario_id ORDER BY e.fecha DESC,e.id DESC");
        return $r?$r->fetch_all(MYSQLI_ASSOC):[];
    }

    public function getAllFiltrado($limit=null,$desde=null,$hasta=null) {
        $where=[];
        if ($desde) $where[]="e.fecha>='".$this->db->escape($desde)."'";
        if ($hasta) $where[]="e.fecha<='".$this->db->escape($hasta)."'";
        $w = $where ? "WHERE ".implode(" AND ",$where) : "";
        $l = $limit ? "LIMIT ".intval($limit) : "";
        $r=$this->db->query("SELECT e.*,u.nombre AS usuario_nombre FROM egresos e LEFT JOIN usuarios u ON u.id=e.usuario_id $w ORDER BY e.fecha DESC,e.id DESC $l");
        return $r?$r->fetch_all(MYSQLI_ASSOC):[];
    }

    public function findById($id) {
        $stmt=$this->db->prepare("SELECT * FROM egresos WHERE id=?");
        $stmt->bind_param("i",$id); $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    public function create($uid,$concepto,$monto,$fecha) {
        $stmt=$this->db->prepare("INSERT INTO egresos (usuario_id,concepto,monto,fecha) VALUES (?,?,?,?)");
        $stmt->bind_param("isds",$uid,$concepto,$monto,$fecha);
        return $stmt->execute();
    }
    public function update($id,$concepto,$monto,$fecha) {
        $stmt=$this->db->prepare("UPDATE egresos SET concepto=?,monto=?,fecha=? WHERE id=?");
        $stmt->bind_param("sdsi",$concepto,$monto,$fecha,$id);
        return $stmt->execute();
    }
    public function delete($id) {
        $stmt=$this->db->prepare("DELETE FROM egresos WHERE id=?");
        $stmt->bind_param("i",$id); return $stmt->execute();
    }
    public function getTotalHoy() {
        $r=$this->db->query("SELECT COALESCE(SUM(monto),0) AS t FROM egresos WHERE fecha=CURDATE()");
        return $r?floatval($r->fetch_assoc()['t']):0;
    }
    public function getTotalMes() {
        $r=$this->db->query("SELECT COALESCE(SUM(monto),0) AS t FROM egresos WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())");
        return $r?floatval($r->fetch_assoc()['t']):0;
    }
}
