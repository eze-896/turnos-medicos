<?php
// Indica que la respuesta será en formato JSON 
header('Content-Type: application/json');

require_once '../modelos/conexion.php';
require_once '../modelos/turno.php';

// Comprueba si se enviaron los datos usando el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Toma el id del médico que llega y lo convierte en número
$id_medico = intval($_POST['id_medico'] ?? 0);

// Toma la fecha BASE que la secretaría selecciona para determinar el mes
$fecha_base = $_POST['fecha'] ?? '';

// Toma la lista de horas que la secretaria selecciona para los turnos
$horas = $_POST['horas'] ?? [];

// Verifica que la secretaria haya enviado todos los datos necesarios
if (!$id_medico || !$fecha_base || empty($horas) || !is_array($horas)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos: médico, fecha y horas son requeridos']);
    exit;
}

// Comprueba que la fecha base sea futura
if (strtotime($fecha_base) < strtotime(date('Y-m-d'))) {
    echo json_encode(['exito' => false, 'mensaje' => 'La fecha debe ser actual o futura']);
    exit;
}

// Crea una nueva conexión con la base de datos
$conexion = new Conexion();
$db = $conexion->conectar();

if (!$db) {
    echo json_encode(['exito' => false, 'mensaje' => 'Error en conexión a la base de datos']);
    exit;
}

// Crea un nuevo objeto Turno
$turno = new Turno($db);

// Obtener días laborables del médico
$sql_dias = "SELECT id_dia FROM medico_dia WHERE id_medico = ?";
$stmt_dias = $db->prepare($sql_dias);
$stmt_dias->bind_param("i", $id_medico);
$stmt_dias->execute();
$result_dias = $stmt_dias->get_result();

$dias_laborables = [];
while ($row = $result_dias->fetch_assoc()) {
    $dias_laborables[] = $row['id_dia'];
}

if (empty($dias_laborables)) {
    echo json_encode(['exito' => false, 'mensaje' => 'El médico no tiene días laborables configurados']);
    exit;
}

// Calcular rango del mes
$year = date('Y', strtotime($fecha_base));
$month = date('m', strtotime($fecha_base));
$fecha_inicio = "$year-$month-01";
$fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

$exitos = 0;
$errores = [];
$total_turnos_creados = 0;
$dias_procesados = 0;

// Recorrer todos los días del mes
$fecha_actual = $fecha_inicio;
while (strtotime($fecha_actual) <= strtotime($fecha_fin)) {
    
    // Verificar si es día laborable (1=Lunes, 7=Domingo)
    $dia_semana = date('N', strtotime($fecha_actual));
    
    if (in_array($dia_semana, $dias_laborables) && strtotime($fecha_actual) >= strtotime(date('Y-m-d'))) {
        $dias_procesados++;
        
        // Para cada día laborable, crear turnos en las horas seleccionadas
        foreach ($horas as $hora) {
            // Verificar que la hora tenga el formato correcto
            if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
                $errores[] = "Hora inválida: $hora en $fecha_actual";
                continue;
            }
            
            // Crear el turno para este día y hora
            $resultado = $turno->crear($id_medico, $fecha_actual, $hora);
            
            if (isset($resultado['exito']) && $resultado['exito']) {
                $exitos++;
                $total_turnos_creados++;
            } else {
                // Solo registrar errores que no sean por duplicados
                if (strpos($resultado['error'] ?? '', 'Ya existe') === false) {
                    $errores[] = "$fecha_actual $hora: " . ($resultado['error'] ?? 'Error desconocido');
                }
            }
        }
    }
    
    // Siguiente día
    $fecha_actual = date('Y-m-d', strtotime($fecha_actual . ' +1 day'));
}

// Preparar mensaje de resultado
$mensaje = "Proceso completado. ";
if ($total_turnos_creados > 0) {
    $mensaje .= "✅ Se crearon $total_turnos_creados turnos exitosamente. ";
}
$mensaje .= "Días laborables procesados: $dias_procesados. ";
$mensaje .= "Horarios por día: " . count($horas) . ". ";

if (!empty($errores)) {
    $mensaje .= "⚠️ Se encontraron " . count($errores) . " errores: " . implode('; ', array_slice($errores, 0, 3));
    if (count($errores) > 3) {
        $mensaje .= "... (y " . (count($errores) - 3) . " más)";
    }
} else {
    $mensaje .= "✅ Todos los turnos se crearon correctamente.";
}

// Enviar respuesta
if ($total_turnos_creados > 0) {
    echo json_encode([
        'exito' => true, 
        'mensaje' => $mensaje, 
        'total_turnos' => $total_turnos_creados,
        'dias_procesados' => $dias_procesados
    ]);
} else {
    echo json_encode([
        'exito' => false, 
        'mensaje' => $mensaje,
        'dias_procesados' => $dias_procesados
    ]);
}

// Cerrar conexión
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>