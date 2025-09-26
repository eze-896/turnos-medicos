<?php
// Limpia el buffer de salida 
ob_start();
// Configura el reporte de errores para que solo se registren y no se muestren al usuario
error_reporting(E_ALL); // Solo para depuración
ini_set('display_errors', 0); // No muestra errores en la salida

// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');

// Limpia cualquier salida previa antes de enviar la respuesta
ob_clean();

try {
    // Verifica si existen los archivos necesarios para conectarse a la base de datos y manejar especialidades
    if (!file_exists('../modelos/conexion.php') || !file_exists('../modelos/especialidad.php')) {
        throw new Exception('Archivos de modelo no encontrados. Verifica la estructura de carpetas.');
    }

    // Incluye los archivos para conectarse a la base de datos y manejar especialidades
    require_once '../modelos/conexion.php';
    require_once '../modelos/especialidad.php';

    // Crea una nueva conexión con la base de datos
    $conexion = new Conexion();
    $db = $conexion->conectar();
    // Verifica que la conexión sea exitosa
    if (!$db) {
        throw new Exception('Error al obtener conexión a la base de datos.');
    }

    // Crea un objeto para manejar las especialidades
    $especialidad = new Especialidad($db);
    // Obtiene la lista de todas las especialidades guardadas en la base de datos
    $lista = $especialidad->obtenerTodos();

    // Si no hay especialidades guardadas, crea algunas por defecto
    if (empty($lista)) {
        $especialidadesPorDefecto = [
            'Cardiología',
            'Pediatría',
            'Medicina General',
            'Dermatología',
            'Ginecología',
            'Neurología',
            'Ortopedia'
        ];

        // Recorre cada nombre de especialidad y la guarda en la base de datos
        foreach ($especialidadesPorDefecto as $nombre) {
            $especialidad->nombre = $nombre;
            // Si logra crear la especialidad, la agrega a la lista
            if ($especialidad->crear()) {
                $lista[] = ['id' => $especialidad->id, 'nombre' => $nombre];
            } else {
                // Si no puede crear la especialidad, lo registra en el log de errores
                error_log("No se pudo crear especialidad por defecto: $nombre");
            }
        }
        // Ordena la lista de especialidades por nombre para que siempre aparezcan igual
        usort($lista, function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
    }

    // Limpia la salida y responde con la lista de especialidades en formato JSON
    ob_clean();
    echo json_encode($lista, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si ocurre un error, limpia la salida, lo registra y responde con el mensaje de error
    ob_clean();
    error_log('Error en listar_especialidades: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { // Captura errores fatales también
    // Si ocurre un error grave, limpia la salida, lo registra y responde con un mensaje genérico
    ob_clean();
    error_log('Error fatal en listar_especialidades: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>