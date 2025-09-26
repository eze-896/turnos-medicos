<?php
// Indica que la respuesta será en formato JSON
header('Content-Type: application/json');

// Incluye los archivos necesarios para conectarse a la base de datos y manejar usuarios y pacientes
include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';
include_once '../modelos/paciente.php';

// Configura MySQLi para que lance excepciones en caso de error
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Crea una nueva conexión con la base de datos
    $db = (new Conexion())->conectar();

    // Verifica que la conexión fue exitosa
    if ($db->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $db->connect_error);
    }

    // Verifica que la solicitud sea un POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["success" => false, "message" => "Método no permitido."]);
        exit;
    }

    // Crea los objetos para manejar usuarios y pacientes
    $usuario = new Usuario($db);
    $paciente = new Paciente($db);

    // Toma los datos enviados por la persona desde el formulario de registro
    $usuario->dni = $_POST['dni'];
    $usuario->nombre = $_POST['nombre'];
    $usuario->apellido = $_POST['apellido'];
    $usuario->email = $_POST['email'];
    $usuario->contrasena = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    $usuario->rol = "paciente";
    $usuario->telefono = $_POST['telefono'] ?? null;

    $paciente->obra_social = $_POST['obra_social'];
    $paciente->num_afiliado = $_POST['num_afiliado'];

    // Verifica si ya existe un usuario con ese DNI o correo
    if ($usuario->existe($usuario->dni, $usuario->email)) {
        echo json_encode(["success" => false, "message" => "El DNI o correo ya están registrados"]);
        exit;
    }

    // Intenta registrar el usuario y luego el paciente
    if ($usuario->registrar()) {
        $paciente->id = $usuario->id;
        if ($paciente->registrar()) {
            // Si todo sale bien, responde que el registro fue exitoso
            echo json_encode(["success" => true, "message" => "Registro exitoso. Ahora puede iniciar sesión."]);
        } else {
            // Si ocurre un error al registrar el paciente, lanza una excepción
            throw new Exception("Error al registrar paciente.");
        }
    } else {
        // Si ocurre un error al registrar el usuario, lanza una excepción
        throw new Exception("Error al registrar usuario.");
    }

} catch (Exception $e) {
    // Si ocurre un error, responde con un mensaje de error y lo registra
    echo json_encode(["success" => false, "message" => "Ocurrió un error en el registro.", "error" => $e->getMessage()]);
}

// Cierra la conexión con la base de datos
if (isset($db) && $db->ping()) {
    $db->close();
}
?>