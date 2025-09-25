<?php
// Limpiar output buffer para evitar basura (espacios, warnings)
ob_start();
error_reporting(E_ALL); // Para depuración; quítalo en producción
ini_set('display_errors', 0); // No mostrar errores en output

header('Content-Type: application/json; charset=utf-8');

// Limpiar cualquier output previo
ob_clean();

try {
    // Verificar si los archivos de modelo existen
    if (!file_exists('../modelos/conexion.php') || !file_exists('../modelos/especialidad.php')) {
        throw new Exception('Archivos de modelo no encontrados. Verifica la estructura de carpetas.');
    }

    require_once '../modelos/conexion.php';
    require_once '../modelos/especialidad.php';

    // Usar tu clase Conexion
    $conexion = new Conexion();
    $db = $conexion->conectar();
    if (!$db) {
        throw new Exception('Error al obtener conexión a la base de datos.');
    }

    $especialidad = new Especialidad($db);
    $lista = $especialidad->obtenerTodos();

    // Si no hay especialidades, crear algunas por defecto
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

        foreach ($especialidadesPorDefecto as $nombre) {
            $especialidad->nombre = $nombre;
            if ($especialidad->crear()) {
                $lista[] = ['id' => $especialidad->id, 'nombre' => $nombre];
            } else {
                // Si falla creación, log y continúa
                error_log("No se pudo crear especialidad por defecto: $nombre");
            }
        }
        // Ordenar por nombre
        usort($lista, function($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
    }

    // Limpiar buffer y enviar JSON
    ob_clean();
    echo json_encode($lista, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    error_log('Error en listar_especialidades: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { // Captura errores fatales también
    ob_clean();
    error_log('Error fatal en listar_especialidades: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
} finally {
    // Cerrar conexión si existe
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>