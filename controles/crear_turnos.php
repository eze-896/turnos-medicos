<?php
// Indica que la respuesta será en formato JSON 
header('Content-Type: application/json');

require_once '../modelos/conexion.php';
require_once '../modelos/turno.php';

// Comprueba si se enviaron los datos usando el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si no es POST, responde diciendo que el método no está permitido
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Toma el id del médico que llega y lo convierte en número
$id_medico = intval($_POST['id_medico'] ?? 0);

// Toma la fecha que la secretaría selecciona para los turnos
$fecha = $_POST['fecha'] ?? '';

// Toma la lista de horas que la secretaria selecciona para los turnos
$horas = $_POST['horas'] ?? []; // Por ejemplo: ['08:00', '09:00']

// Verifica que la secretaria haya enviado todos los datos necesarios y que sean correctos
if (!$id_medico || !$fecha || empty($horas) || !is_array($horas)) {
    // Si falta algún dato, responde diciendo que los datos son inválidos
    echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

// Comprueba que la fecha elegida sea futura
if (strtotime($fecha) <= time()) {
    // Si la fecha no es válida, responde diciendo que debe ser futura
    echo json_encode(['exito' => false, 'mensaje' => 'La fecha debe ser futura']);
    exit;
}

// Crea una nueva conexión con la base de datos
$conexion = new Conexion();

$db = $conexion->conectar();

// Verifica que la conexión sea exitosa
if (!$db) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error en conexión.']);
    exit;
}

// Crea un nuevo objeto Turno
$turno = new Turno($db);

// Inicializa contadores para llevar la cuenta de éxitos y errores
$exitos = 0;
$errores = [];

// Recorre cada hora que la secretaria selecciona para crear los turnos uno por uno
foreach ($horas as $hora) {
    // Verifica que la hora tenga el formato correcto (por ejemplo, 08:00)
    if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
        // Si la hora no es válida, la agrega a la lista de errores
        $errores[] = "Hora inválida: $hora";
        continue;
    }
    
    // Intenta crear el turno para el médico, la fecha y la hora indicadas
    $resultado = $turno->crear($id_medico, $fecha, $hora);
    
    // Si el turno se crea correctamente, suma uno al contador de éxitos
    if (isset($resultado['exito']) && $resultado['exito']) {
        $exitos++;
    } else {
        // Si ocurre un error, lo agrega a la lista de errores
        $errores[] = "Hora $hora: " . ($resultado['error'] ?? 'Error desconocido');
    }
}

// Prepara un mensaje para informar cuántos turnos se crean y si hay errores
$mensaje = "Creados $exitos turnos de " . count($horas) . ". " . (empty($errores) ? 'Todos exitosos.' : 'Errores: ' . implode('; ', $errores));

// Envía la respuesta final en formato JSON, indicando si hay éxito o no y mostrando el mensaje
if ($exitos > 0) {
    echo json_encode(['exito' => true, 'mensaje' => $mensaje]);
} else {
    echo json_encode(['exito' => false, 'mensaje' => $mensaje]);
}

// Cierra la conexión a la base de datos si está abierta
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>