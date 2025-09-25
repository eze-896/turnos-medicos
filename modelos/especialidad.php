<?php
class Especialidad {
    private $conn;
    private $table = "especialidad";

    public $id;
    public $nombre;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if an especialidad exists by name
    public function existe($nombre) {
        $sql = "SELECT id FROM " . $this->table . " WHERE nombre = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // Create a new especialidad
    public function crear() {
        if ($this->existe($this->nombre)) {
            return false; // Already exists
        }
        $sql = "INSERT INTO " . $this->table . " (nombre) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->nombre);
        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            return true;
        }
        return false;
    }

    // Fetch especialidad by ID
    public function buscarPorId($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: false;
    }

    // Fetch all especialidades
    public function obtenerTodos() {
        $sql = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>