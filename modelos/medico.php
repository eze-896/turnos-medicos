<?php
// Define una clase para manejar los perfiles de médicos en la base de datos
class Medico {
    private $conn;                // Guarda la conexión a la base de datos
    private $table = "medico";    // Nombre de la tabla de médicos

    public $id;                   // ID del médico (igual al usuario)
    public $id_usuario;           // ID del usuario asociado

    // Cuando crea un objeto Medico, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe un perfil de médico para un usuario dado
    public function existe($id_usuario) {
        $sql = "SELECT id FROM " . $this->table . " WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra el perfil, sino false
        return $stmt->num_rows > 0;
    }

    // Registra un perfil de médico
    public function registrar($id_usuario) {
        // Si ya existe el perfil, no lo crea de nuevo
        if ($this->existe($id_usuario)) {
            return false; // Ya existe
        }
        $sql = "INSERT INTO " . $this->table . " (id, id_usuario) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $id = $id_usuario; // El id del médico es igual al del usuario
        $stmt->bind_param("ii", $id, $id_usuario);
        if ($stmt->execute()) {
            $this->id = $id;
            $this->id_usuario = $id_usuario;
            return true;
        }
        return false;
    }

    // Busca un médico por su ID y devuelve todos los datos clave del usuario asociado
    public function buscarPorId($id) {
        $sql = "SELECT m.*, u.dni, u.nombre, u.apellido, u.email, u.telefono, u.rol 
                FROM " . $this->table . " m 
                JOIN usuario u ON m.id_usuario = u.id 
                WHERE m.id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing buscarPorId en medico.php: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos del médico y usuario como un arreglo asociativo, o false si no lo encuentra
        return $result->fetch_assoc() ?: false;
    }

    // Obtiene todos los médicos 
    public function obtenerTodos() {
        $sql = "SELECT * FROM " . $this->table;
        $result = $this->conn->query($sql);
        // Devuelve un arreglo con todos los médicos, o vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Obtiene todos los médicos con datos completos de usuario (para mostrar en la gestión de médicos)
    public function obtenerTodosConUsuario() {
        $sql = "SELECT m.id as id_medico, u.dni, u.nombre, u.apellido, u.email, u.telefono 
                FROM " . $this->table . " m 
                JOIN usuario u ON m.id_usuario = u.id 
                ORDER BY u.apellido, u.nombre";
        $result = $this->conn->query($sql);
        // Devuelve un arreglo con los médicos y sus datos de usuario, o vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // Actualiza el perfil del médico (por ahora no hay campos editables, pero permite futuras extensiones)
    public function actualizar() {
        // Si se agregan campos a la tabla medico, aquí se actualizan
        // Por ahora, retorna true porque no hay campos editables
        return true;
    }

    // Elimina un médico por su ID
    public function eliminar($id) {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>