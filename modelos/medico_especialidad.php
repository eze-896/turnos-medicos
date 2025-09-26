<?php
// Define una clase para manejar la relación entre médicos y especialidades en la base de datos
class MedicoEspecialidad {
    private $conn;                    // Guarda la conexión a la base de datos
    private $table = "medico_especialidad"; // Nombre de la tabla de relación

    public $id_medico;                // ID del médico
    public $id_especialidad;          // ID de la especialidad

    // Cuando crea un objeto MedicoEspecialidad, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe la asociación entre un médico y una especialidad
    public function existe($id_medico, $id_especialidad) {
        $sql = "SELECT id_medico FROM " . $this->table . " WHERE id_medico = ? AND id_especialidad = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_especialidad);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra la asociación, sino false
        return $stmt->num_rows > 0;
    }

    // Crea una nueva asociación entre un médico y una especialidad
    public function asociar($id_medico, $id_especialidad) {
        // Si ya existe la asociación, no la crea de nuevo
        if ($this->existe($id_medico, $id_especialidad)) {
            return false; // Ya está asociado
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

    // Elimina la asociación entre un médico y una especialidad
    public function desasociar($id_medico, $id_especialidad) {
        $sql = "DELETE FROM " . $this->table . " WHERE id_medico = ? AND id_especialidad = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $id_especialidad);
        return $stmt->execute();
    }

    // Obtiene todas las especialidades asociadas a un médico
    public function obtenerEspecialidadesPorMedico($id_medico) {
        $sql = "SELECT e.* FROM " . $this->table . " me 
                JOIN especialidad e ON me.id_especialidad = e.id 
                WHERE me.id_medico = ? ORDER BY e.nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve un arreglo con las especialidades, o vacío si no hay ninguna
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene todos los médicos asociados a una especialidad
    public function obtenerMedicosPorEspecialidad($id_especialidad) {
        $sql = "SELECT m.* FROM " . $this->table . " me 
                JOIN medico m ON me.id_medico = m.id 
                WHERE me.id_especialidad = ? ORDER BY m.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_especialidad);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve un arreglo con los médicos, o vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene solo los IDs de especialidades asociadas a un médico (útil para checkboxes en edición)
    public function obtenerIdsEspecialidadesPorMedico($id_medico) {
        $sql = "SELECT id_especialidad FROM " . $this->table . " WHERE id_medico = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = intval($row['id_especialidad']);
        }
        // Devuelve un arreglo con los IDs de las especialidades
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