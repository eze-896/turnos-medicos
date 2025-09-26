<?php
// Define una clase para manejar los pacientes en la base de datos
class Paciente {
    private $conn;                // Guarda la conexión a la base de datos
    private $table = "paciente";  // Nombre de la tabla de pacientes

    public $id;                   // ID del paciente (igual al usuario)
    public $obra_social;          // Nombre de la obra social del paciente
    public $num_afiliado;         // Número de afiliado a la obra social

    // Cuando crea un objeto Paciente, recibe la conexión a la base de datos
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

    // Busca un paciente por su DNI y devuelve sus datos
    public function buscarPorDNI($dni) {
        // Nota: aquí hay un error, la tabla correcta debería ser 'paciente' y no 'pacientes'
        $sql = "SELECT * FROM paciente WHERE dni = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos del paciente como un arreglo asociativo, o false si no lo encuentra
        return $result->fetch_assoc() ?: false;
    }
}
?>