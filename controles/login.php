<?php
session_start();
header('Content-Type: application/json'); // salida siempre en JSON

// Evitar que warnings rompan el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';

try {
    $db = (new Conexion())->conectar();

    // Obtener datos del POST
    $correo = $_POST['login_email'] ?? '';
    $contrasena = $_POST['login_password'] ?? '';

    if (empty($correo) || empty($contrasena)) {
        echo json_encode([
            "success" => false,
            "message" => "Debe ingresar correo y contraseña."
        ]);
        exit;
    }

    // Buscar usuario
    $usuario = new Usuario($db);
    $fila = $usuario->buscarPorEmail($correo);

    if (!$fila) {
        echo json_encode([
            "success" => false,
            "message" => "Correo no registrado"
        ]);
        exit;
    }

    if (!empty($fila['contrasena']) && password_verify($contrasena, $fila['contrasena'])) {
        // Guardar datos en sesión
        $_SESSION['nombre'] = $fila['nombre'];
        $_SESSION['rol'] = $fila['rol'];
        $_SESSION['dni_usuario'] = $fila['dni'];

        echo json_encode([
            "success" => true,
            "message" => "Login exitoso",
            "rol" => $fila['rol']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Contraseña incorrecta"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Ocurrió un error en el login",
        "error" => $e->getMessage()
    ]);
}
?>
