<?php
// Indica que la respuesta será JSON
header('Content-Type: application/json');

// Evita que warnings rompan el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluye clases necesarias
include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';

// Configura MySQLi para lanzar excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = (new Conexion())->conectar();
    if ($db->connect_error) {
        throw new Exception("Error de conexión: " . $db->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["success" => false, "message" => "Método no permitido."]);
        exit;
    }

    // DEBUG: Ver qué campos están llegando
    error_log("Campos POST recibidos para secretaria: " . print_r($_POST, true));

    // Establecer rol como 'secretaria'
    $rol = 'secretaria';

    $usuario = new Usuario($db);
    $usuario->dni = $_POST['dni'] ?? '';
    $usuario->nombre = $_POST['nombre'] ?? '';
    $usuario->apellido = $_POST['apellido'] ?? '';
    $usuario->email = $_POST['email'] ?? '';
    
    $contrasena = $_POST['contrasena'] ?? '';
    $usuario->contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    
    $usuario->rol = $rol;
    $usuario->telefono = $_POST['telefono'] ?? '';

    // Validaciones básicas
    if (empty($usuario->dni) || empty($usuario->nombre) || empty($usuario->apellido) || empty($usuario->email) || empty($contrasena)) {
        echo json_encode(["success" => false, "message" => "Todos los campos obligatorios deben ser completados."]);
        exit;
    }

    // Si ya existe usuario con DNI o email
    if ($usuario->existe($usuario->dni, $usuario->email)) {
        echo json_encode(["success" => false, "message" => "El DNI o correo ya están registrados"]);
        exit;
    }

    // Registrar usuario (solo en tabla usuario, no en paciente)
    if (!$usuario->registrar()) {
        throw new Exception("Error al registrar secretaria.");
    }

    echo json_encode([
        "success" => true,
        "message" => "Secretaria registrada exitosamente."
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Ocurrió un error en el registro de la secretaria.",
        "error" => $e->getMessage()
    ]);
}

// Cierra la conexión
if (isset($db) && $db->ping()) {
    $db->close();
}
?>