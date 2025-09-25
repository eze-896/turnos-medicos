<?php
class Medico {
    private $conn;
    private $table = "medico";

    public $id;
    public $id_usuario;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if a medico profile exists for a given user ID
    public function existe($id_usuario) {
        $sql = "SELECT id FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // Register a medico profile (assumes user is already created; id will match usuario.id)
    public function registrar($id_usuario) {
        if ($this->existe($id_usuario)) {
            return false; // Already exists
        }
        $sql = "INSERT INTO " . $this->table . " (id, id_usuario) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $id = $id_usuario; // id matches usuario.id
        $stmt->bind_param("ii", $id, $id_usuario);
        if ($stmt->execute()) {
            $this->id = $id;
            $this->id_usuario = $id_usuario;
            return true;
        }
        return false;
    }

    // Fetch medico by ID (includes linked user data if needed)
    public function buscarPorId($id) {
        $sql = "SELECT m.*, u.nombre, u.apellido, u.email FROM " . $this->table . " m 
                JOIN usuario u ON m.id_usuario = u.id 
                WHERE m.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: false;
    }

    // Fetch all medicos (basic list)
    public function obtenerTodos() {
        $sql = "SELECT * FROM " . $this->table;
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>