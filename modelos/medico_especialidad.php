<?php
class MedicoEspecialidad {
    private $conn;
    private $table = "medico_especialidad";

    public $id_medico;
    public $id_especialidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if association exists
    public function existe($id_medico, $id_especialidad) {
        $sql = "SELECT id_medico FROM " . $this->table . " WHERE id_medico = ? AND id_especialidad = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_especialidad);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // Add association between medico and especialidad
    public function asociar($id_medico, $id_especialidad) {
        if ($this->existe($id_medico, $id_especialidad)) {
            return false; // Already associated
        }
        $sql = "INSERT INTO " . $this->table . " (id_medico, id_especialidad) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_especialidad);
        if ($stmt->execute()) {
            $this->id_medico = $id_medico;
            $this->id_especialidad = $id_especialidad;
            return true;
        }
        return false;
    }

    // Remove association
    public function desasociar($id_medico, $id_especialidad) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_medico = ? AND id_especialidad = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_especialidad);
        return $stmt->execute();
    }

    // Fetch especialidades for a medico
    public function obtenerEspecialidadesPorMedico($id_medico) {
        $sql = "SELECT e.* FROM " . $this->table . " me 
                JOIN especialidad e ON me.id_especialidad = e.id 
                WHERE me.id_medico = ? ORDER BY e.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Fetch medicos for an especialidad
    public function obtenerMedicosPorEspecialidad($id_especialidad) {
        $sql = "SELECT m.* FROM " . $this->table . " me 
                JOIN medico m ON me.id_medico = m.id 
                WHERE me.id_especialidad = ? ORDER BY m.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_especialidad);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>