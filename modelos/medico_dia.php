<?php
// Define una clase para manejar la relación entre médicos y días en la base de datos
class MedicoDia {
    private $conn;                // Guarda la conexión a la base de datos
    private $table = "medico_dia"; // Nombre de la tabla de relación

    public $id_medico;            // ID del médico
    public $id_dia;               // ID del día

    // Cuando crea un objeto MedicoDia, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe la asociación entre un médico y un día
    public function existe($id_medico, $id_dia) {
        $sql = "SELECT id_medico FROM " . $this->table . " WHERE id_medico = ? AND id_dia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_dia);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra la asociación, sino false
        return $stmt->num_rows > 0;
    }

    // Crea una nueva asociación entre un médico y un día
    public function asociar($id_medico, $id_dia) {
        // Si ya existe la asociación, no la crea de nuevo
        if ($this->existe($id_medico, $id_dia)) {
            return false; // Ya está asociado
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

    // Elimina la asociación entre un médico y un día
    public function desasociar($id_medico, $id_dia) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_medico = ? AND id_dia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_dia);
        return $stmt->execute();
    }

    // Obtiene todos los días en que atiende un médico
    public function obtenerDiasPorMedico($id_medico) {
        $sql = "SELECT d.* FROM " . $this->table . " md 
                JOIN dia d ON md.id_dia = d.id 
                WHERE md.id_medico = ? ORDER BY d.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve un arreglo con los días, o vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene todos los médicos que atienden en un día específico
    public function obtenerMedicosPorDia($id_dia) {
        $sql = "SELECT m.* FROM " . $this->table . " md 
                JOIN medico m ON md.id_medico = m.id 
                WHERE md.id_dia = ? ORDER BY m.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_dia);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve un arreglo con los médicos, o vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene solo los IDs de los días en que atiende un médico
    public function obtenerIdsDiasPorMedico($id_medico) {
        $sql = "SELECT id_dia FROM " . $this->table . " WHERE id_medico = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = intval($row['id_dia']);
        }
        // Devuelve un arreglo con los IDs de los días
        return $ids;
    }

    // Elimina todas las asociaciones de un médico (por ejemplo, al actualizar o eliminar)
    public function eliminarPorMedico($id_medico) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_medico = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        return $stmt->execute();
    }
}
?>