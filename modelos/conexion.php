<?php
class Conexion {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $db = "turnos_medico";
    private $conn;

    public function conectar() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db);

        if ($this->conn->connect_error) {
            // En lugar de die(), lanza excepción para que try-catch la maneje
            throw new Exception("Error de conexión: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8"); // Agrego charset para acentos
        return $this->conn;
    }
}
?>