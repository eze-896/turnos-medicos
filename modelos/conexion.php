<?php
// Define una clase para manejar la conexión a la base de datos
class Conexion {
    // Guarda los datos necesarios para conectarse a la base de datos
    private $host = "localhost";      // Dirección del servidor de base de datos
    private $user = "root";           // Usuario para conectarse
    private $password = "";           // Contraseña del usuario
    private $db = "turnos_medico";    // Nombre de la base de datos
    private $conn;                    // Aquí se guarda la conexión

    // Crea y devuelve una conexión a la base de datos
    public function conectar() {
        // Intenta conectarse usando los datos guardados
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db);

        // Si ocurre un error al conectar, lanza una excepción para que otro código la maneje
        if ($this->conn->connect_error) {
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }

        // Configura la conexión para que acepte caracteres especiales como acentos
        $this->conn->set_charset("utf8");
        // Devuelve el objeto de conexión para que otros lo usen
        return $this->conn;
    }
}
?>