<?php
// Define una clase para manejar las especialidades médicas en la base de datos
class Especialidad {
    private $conn;               // Guarda la conexión a la base de datos
    private $table = "especialidad"; // Nombre de la tabla donde se guardan las especialidades

    public $id;                  // ID de la especialidad
    public $nombre;              // Nombre de la especialidad (por ejemplo, 'Cardiología')

    // Cuando crea un objeto Especialidad, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe una especialidad con ese nombre en la base de datos
    public function existe($nombre) {
        $sql = "SELECT id FROM " . $this->table . " WHERE nombre = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra al menos un resultado, sino false
        return $stmt->num_rows > 0;
    }

    // Crea una nueva especialidad en la base de datos
    public function crear() {
        // Si la especialidad ya existe, no la crea de nuevo
        if ($this->existe($this->nombre)) {
            return false; // Ya existe
        }
        $sql = "INSERT INTO " . $this->table . " (nombre) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->nombre);
        if ($stmt->execute()) {
            // Si la crea correctamente, guarda el ID generado
            $this->id = $this->conn->insert_id;
            return true;
        }
        return false;
    }

    // Busca una especialidad por su ID y devuelve sus datos
    public function buscarPorId($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos de la especialidad como un arreglo asociativo, o false si no la encuentra
        return $result->fetch_assoc() ?: false;
    }

    // Obtiene todas las especialidades guardadas en la base de datos, ordenadas por nombre
    public function obtenerTodos() {
        $sql = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $result = $this->conn->query($sql);
        // Devuelve un arreglo con todas las especialidades, o un arreglo vacío si no hay ninguna
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>