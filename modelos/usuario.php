<?php
// Define una clase para manejar los usuarios en la base de datos
class Usuario {
    private $conn;                // Guarda la conexión a la base de datos
    private $table = "usuario";   // Nombre de la tabla de usuarios

    public $id;                   // ID del usuario
    public $dni;                  // DNI del usuario
    public $nombre;               // Nombre del usuario
    public $apellido;             // Apellido del usuario
    public $email;                // Correo electrónico del usuario
    public $contrasena;           // Contraseña (encriptada)
    public $rol;                  // Rol del usuario (por ejemplo, 'paciente', 'medico', 'secretaria')
    public $telefono;             // Teléfono del usuario

    // Cuando crea un objeto Usuario, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifica si ya existe un usuario con ese DNI o email (puede excluir un ID para edición)
    public function existe($dni, $email, $excluirId = null) {
        $sql = "SELECT id FROM " . $this->table . " WHERE (dni = ? OR email = ?) ";
        $params = [$dni, $email];
        $types = "ss"; // DNI y email como strings
        if ($excluirId !== null) {
            $sql .= "AND id != ?";
            $params[] = $excluirId;
            $types .= "i";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing existe(): " . $this->conn->error);
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        // Devuelve true si encuentra el usuario, sino false
        return $stmt->num_rows > 0;
    }

    // Registra un nuevo usuario en la base de datos
    public function registrar() {
    $sql = "INSERT INTO " . $this->table . " (dni, nombre, apellido, email, contrasena, rol, telefono) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error en preparar consulta: " . $this->conn->error);
        return false;
    }
    
    // CORREGIR: Cambiar "sssssis" por "sssssss" - todos son strings
    $stmt->bind_param("sssssss", $this->dni, $this->nombre, $this->apellido, $this->email, $this->contrasena, $this->rol, $this->telefono);
    
    if ($stmt->execute()) {
        $this->id = $this->conn->insert_id;
        return true;
    } else {
        error_log("Error al ejecutar consulta: " . $stmt->error);
        return false;
    }
}

    // Busca un usuario por su correo electrónico y devuelve sus datos
    public function buscarPorEmail($email) {
        $sql = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos del usuario como un arreglo asociativo, o false si no lo encuentra
        return $result->fetch_assoc() ?: false;
    }

    // Busca un usuario por su ID (útil para editar o eliminar)
    public function buscarPorId($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing buscarPorId(): " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        // Devuelve los datos del usuario como un arreglo asociativo, o false si no lo encuentra
        return $result->fetch_assoc() ?: false;
    }

    // Actualiza los datos del usuario (la contraseña solo si se proporciona)
    public function actualizar() {
        // Primero, actualiza los campos básicos
        $sql = "UPDATE " . $this->table . " SET dni = ?, nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing actualizar(): " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("sssssi", $this->dni, $this->nombre, $this->apellido, $this->email, $this->telefono, $this->id);
        if (!$stmt->execute()) {
            return false;
        }

        // Si se proporciona nueva contraseña, la actualiza (debe estar hasheada ya)
        if (!empty($this->contrasena)) {
            $sqlPass = "UPDATE " . $this->table . " SET contrasena = ? WHERE id = ?";
            $stmtPass = $this->conn->prepare($sqlPass);
            if (!$stmtPass) {
                error_log("Error preparing actualizar contraseña: " . $this->conn->error);
                return false;
            }
            $stmtPass->bind_param("si", $this->contrasena, $this->id);
            return $stmtPass->execute();
        }
        return true;
    }

    // Elimina un usuario por su ID
    public function eliminar($id) {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing eliminar(): " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("i", $id);
        // Devuelve true si logra eliminar el usuario, sino false
        return $stmt->execute();
    }
}
?>