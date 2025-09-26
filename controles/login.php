<?php
// Inicia la sesión para manejar datos del usuario
session_start();
// Indica que la respuesta será en formato JSON
header('Content-Type: application/json');

// Configura el reporte de errores para que solo se registren y no se muestren al usuario
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Incluye los archivos necesarios para conectarse a la base de datos y manejar usuarios
include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';

try {
    // Crea una nueva conexión con la base de datos
    $db = (new Conexion())->conectar();

    // Veriica la conexión
    if ($db->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $db->connect_error);
    }

    // Verifica que la solicitud sea un POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            "success" => false,
            "message" => "Método no permitido"
        ]);
        exit;
    }

    // Obtiene los datos enviados por la persona desde el formulario de login
    $correo = $_POST['login_email'] ?? '';
    $contrasena = $_POST['login_password'] ?? '';

    // Verifica que la persona haya escrito su correo y su contraseña
    if (empty($correo) || empty($contrasena)) {
        echo json_encode([
            "success" => false,
            "message" => "Debe ingresar correo y contraseña."
        ]);
        exit;
    }

    // Busca al usuario en la base de datos usando el correo que la persona escribió
    $usuario = new Usuario($db);
    $fila = $usuario->buscarPorEmail($correo);

    // Si no encuentra el usuario, responde que el correo no está registrado
    if (!$fila) {
        echo json_encode([
            "success" => false,
            "message" => "Correo no registrado"
        ]);
        exit;
    }

    // Verifica que la contraseña escrita sea igual a la guardada en la base de datos
    if (!empty($fila['contrasena']) && password_verify($contrasena, $fila['contrasena'])) {
        // Si la contraseña es correcta, guarda los datos principales en la sesión
        $_SESSION['nombre'] = $fila['nombre'];
        $_SESSION['rol'] = $fila['rol'];
        $_SESSION['dni_usuario'] = $fila['dni'];
        $_SESSION['id_usuario'] = $fila['id'];

        // Responde que el login es exitoso y envía el rol del usuario
        echo json_encode([
            "success" => true,
            "message" => "Login exitoso",
            "rol" => $fila['rol']
        ]);
    } else {
        // Si la contraseña es incorrecta, responde con un mensaje de error
        echo json_encode([
            "success" => false,
            "message" => "Contraseña incorrecta"
        ]);
    }

} catch (Exception $e) {
    // Si ocurre un error inesperado, responde con un mensaje de error y lo registra
    echo json_encode([
        "success" => false,
        "message" => "Ocurrió un error en el login",
        "error" => $e->getMessage()
    ]);
}

// Cierra la conexión a la base de datos
if (isset($db) && $db->connect_error === null) {
    $db->close();
}
?>
