<?php
class MedicoDia {
    private $conn;
    private $table = "medico_dia";

    public $id_medico;
    public $id_dia;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Check if association exists
    public function existe($id_medico, $id_dia) {
        $sql = "SELECT id_medico FROM " . $this->table . " WHERE id_medico = ? AND id_dia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_dia);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // Add association between medico and dia
    public function asociar($id_medico, $id_dia) {
        if ($this->existe($id_medico, $id_dia)) {
            return false; // Already associated
        }
        $sql = "INSERT INTO " . $this->table . " (id_medico, id_dia) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_dia);
        if ($stmt->execute()) {
            $this->id_medico = $id_medico;
            $this->id_dia = $id_dia;
            return true;
        }
        return false;
    }

    // Remove association
    public function desasociar($id_medico, $id_dia) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_medico = ? AND id_dia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_dia);
        return $stmt->execute();
    }

    // Fetch dias for a medico
    public function obtenerDiasPorMedico($id_medico) {
        $sql = "SELECT d.* FROM " . $this->table . " md 
                JOIN dia d ON md.id_dia = d.id 
                WHERE md.id_medico = ? ORDER BY d.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Fetch medicos for a dia
    public function obtenerMedicosPorDia($id_dia) {
        $sql = "SELECT m.* FROM " . $this->table . " md 
                JOIN medico m ON md.id_medico = m.id 
                WHERE md.id_dia = ? ORDER BY m.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_dia);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>