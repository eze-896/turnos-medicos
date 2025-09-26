<?php
// Limpia el buffer de salida 
ob_start();
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');
ob_clean();

// Verifica que la persona hace una consulta GET y que envía el ID del médico
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    // Si no es GET o falta el ID, responde diciendo que el ID es requerido
    echo json_encode(['error' => true, 'mensaje' => 'ID requerido']);
    exit;
}

try {
    // Convierte el ID recibido a número entero
    $id = intval($_GET['id']);
    // Incluye los archivos necesarios para conectarse a la base de datos y manejar médicos, especialidades y días
    require_once '../modelos/conexion.php';
    require_once '../modelos/medico.php';
    require_once '../modelos/medico_especialidad.php';
    require_once '../modelos/medico_dia.php';

    // Crea una nueva conexión con la base de datos
    $conexion = new Conexion();
    $db = $conexion->conectar();

    // Verifica que la conexión sea exitosa
    if (!$db) {
        throw new Exception('Error al obtener conexión a la base de datos.');
    }

    // Crea los objetos para manejar médicos, especialidades y días
    $medico = new Medico($db);
    $medicoEspecialidad = new MedicoEspecialidad($db);
    $medicoDia = new MedicoDia($db);

    // Busca los datos del médico por su ID (incluye datos de usuario)
    $medicoData = $medico->buscarPorId($id);
    if (!$medicoData) {
        // Si no encuentra el médico, responde con un mensaje de error
        throw new Exception('Médico no encontrado.');
    }

    // Obtiene los IDs de las especialidades asociadas al médico
    $especialidadesIds = $medicoEspecialidad->obtenerIdsEspecialidadesPorMedico($id);
    // Obtiene los IDs de los días en que el médico atiende
    $diasIds = $medicoDia->obtenerIdsDiasPorMedico($id);

    // Junta toda la información en un solo arreglo para responder
    $resultado = array_merge($medicoData, [
        'especialidades_ids' => $especialidadesIds,
        'dias_ids' => $diasIds
    ]);

    // Limpia la salida y responde con los datos del médico en formato JSON
    ob_clean();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si ocurre un error, limpia la salida, lo registra y responde con el mensaje de error
    ob_clean();
    error_log('Error en obtener_medico: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db)) $db->close();
}
?>