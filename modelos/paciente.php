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

    // Registra un paciente en la base de datos
    public function registrar() {
        $sql = "INSERT INTO " . $this->table . " (id, obra_social, num_afiliado) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $this->id, $this->obra_social, $this->num_afiliado);
        return $stmt->execute();
    }

    // MÉTODO NUEVO: Busca un paciente por su ID
    public function buscarPorId($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: false;
    }
}
?>
