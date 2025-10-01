<?php
// Define una clase para manejar los días de la semana en la base de datos
class Dia {
    private $conn;           // Guarda la conexión a la base de datos
    private $table = "dia";  // Nombre de la tabla donde se guardan los días

    public $id;              // ID del día
    public $nombre;          // Nombre del día (por ejemplo, 'Lunes')

    // Cuando crea un objeto Dia, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe un día con ese nombre en la base de datos
    public function existe($nombre) {
        $sql = "SELECT id FROM " . $this->table . " WHERE nombre = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra al menos un resultado, sino false
        return $stmt->num_rows > 0;
    }

    // Crea un nuevo día en la base de datos (por ejemplo, 'Lunes')
    public function crear() {
        // Si el día ya existe, no lo crea de nuevo
        if ($this->existe($this->nombre)) {
            return false; // Ya existe
        }
        $sql = "INSERT INTO " . $this->table . " (nombre) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $this->nombre);
        if ($stmt->execute()) {
            // Si lo crea correctamente, guarda el ID generado
            $this->id = $this->conn->insert_id;
            return true;
        }
        return false;
    }

    // Busca un día por su ID y devuelve sus datos
    public function buscarPorId($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos del día como un arreglo asociativo, o false si no lo encuentra
        return $result->fetch_assoc() ?: false;
    }

    // Obtiene todos los días guardados en la base de datos, ordenados por id
    public function obtenerTodos() {
        $sql = "SELECT * FROM " . $this->table . " ORDER BY id";
        $result = $this->conn->query($sql);
        // Devuelve un arreglo con todos los días, o un arreglo vacío si no hay ninguno
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>