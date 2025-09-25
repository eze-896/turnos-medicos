<?php
class Usuario {
    private $conn;
    private $table = "usuario";

    public $id;
    public $dni;
    public $nombre;
    public $apellido;
    public $email;
    public $contrasena;
    public $rol;
    public $telefono;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function existe($dni, $email) {
        $sql = "SELECT id FROM " . $this->table . " WHERE dni = ? OR email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $dni, $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function registrar() {
        $sql = "INSERT INTO " . $this->table . " (dni, nombre, apellido, email, contrasena, rol, telefono) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssssi", $this->dni, $this->nombre, $this->apellido, $this->email, $this->contrasena, $this->rol, $this->telefono);
        if ($stmt->execute()) {
            $this->id = $this->conn->insert_id;
            return true;
        }
        return false;
    }

    public function buscarPorEmail($email) {
    $sql = "SELECT * FROM " . $this->table . " WHERE email = ?";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: false;
    }

}
?>