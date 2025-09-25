<?php
class Paciente {
    private $conn;
    private $table = "paciente";

    public $id;
    public $obra_social;
    public $num_afiliado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function registrar() {
        $sql = "INSERT INTO " . $this->table . " (id, obra_social, num_afiliado) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $this->id, $this->obra_social, $this->num_afiliado);
        return $stmt->execute();
    }
}
?>