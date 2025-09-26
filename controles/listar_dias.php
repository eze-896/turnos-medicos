<?php
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');
ob_clean();

try {
    // Verifica que existan los archivos necesarios para conectarse a la base de datos y manejar los días
    if (!file_exists('../modelos/conexion.php') || !file_exists('../modelos/dia.php')) {
        throw new Exception('Archivos de modelo no encontrados.');
    }

    // Incluye los archivos para conectarse a la base de datos y manejar los días
    require_once '../modelos/conexion.php';
    require_once '../modelos/dia.php';

    // Crea una nueva conexión con la base de datos
    $conexion = new Conexion();
    $db = $conexion->conectar();
    // Verifica que la conexión sea exitosa
    if (!$db) {
        throw new Exception('Error en conexión.');
    }

    // Crea un objeto para manejar los días
    $dia = new Dia($db);
    // Obtiene la lista de todos los días guardados en la base de datos
    $lista = $dia->obtenerTodos();

    // Si no hay días guardados, crea los días de la semana por defecto
    if (empty($lista)) {
        $diasPorDefecto = [
            'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'
        ];

        // Recorre cada nombre de día y lo guarda en la base de datos
        foreach ($diasPorDefecto as $nombre) {
            $dia->nombre = $nombre;
            // Si logra crear el día, lo agrega a la lista
            if ($dia->crear()) {
                $lista[] = ['id' => $dia->id, 'nombre' => $nombre];
            } else {
                // Si no puede crear el día, lo registra en el log de errores
                error_log("No se pudo crear día por defecto: $nombre");
            }
        }
        // Ordena la lista de días por nombre para que siempre aparezcan igual
        usort($lista, function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
    }

    // Limpia la salida y responde con la lista de días en formato JSON
    ob_clean();
    echo json_encode($lista, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si ocurre un error, limpia la salida, lo registra y responde con el mensaje de error
    ob_clean();
    error_log('Error en listar_dias: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // Si ocurre un error grave, limpia la salida, lo registra y responde con un mensaje genérico
    ob_clean();
    error_log('Error fatal en listar_dias: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>