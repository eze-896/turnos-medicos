<?php
header('Content-Type: application/json'); // JSON
include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';
include_once '../modelos/paciente.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = (new Conexion())->conectar();
    $usuario = new Usuario($db);
    $paciente = new Paciente($db);

    $usuario->dni = $_POST['dni'];
    $usuario->nombre = $_POST['nombre'];
    $usuario->apellido = $_POST['apellido'];
    $usuario->email = $_POST['email'];
    $usuario->contrasena = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    $usuario->rol = "paciente";
    $usuario->telefono = $_POST['telefono'] ?? null;

    $paciente->obra_social = $_POST['obra_social'];
    $paciente->num_afiliado = $_POST['num_afiliado'];

    if ($usuario->existe($usuario->dni, $usuario->email)) {
        echo json_encode(["success" => false, "message" => "El DNI o correo ya están registrados"]);
        exit;
    }

    if ($usuario->registrar()) {
        $paciente->id = $usuario->id;
        if ($paciente->registrar()) {
            echo json_encode(["success" => true, "message" => "Registro exitoso. Ahora puede iniciar sesión."]);
        } else {
            throw new Exception("Error al registrar paciente.");
        }
    } else {
        throw new Exception("Error al registrar usuario.");
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Ocurrió un error en el registro.", "error" => $e->getMessage()]);
}
