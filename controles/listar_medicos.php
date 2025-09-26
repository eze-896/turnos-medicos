<?php
// Limpia el buffer de salida
ob_start();
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');
// Limpia cualquier salida previa antes de enviar la respuesta
ob_clean();

try {
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

    // Obtiene la lista de todos los médicos junto con los datos de usuario
    $medicos = $medico->obtenerTodosConUsuario();

    $resultado = [];
    // Recorre cada médico para armar la información completa
    foreach ($medicos as $m) {
        // Obtiene las especialidades del médico y las convierte en una cadena separada por comas
        $esps = $medicoEspecialidad->obtenerEspecialidadesPorMedico($m['id_medico']);
        $nombresEsp = array_column($esps, 'nombre');
        $especialidadesStr = implode(', ', $nombresEsp) ?: 'Ninguna';

        // Obtiene los días en que el médico atiende y los convierte en una cadena separada por comas
        $ds = $medicoDia->obtenerDiasPorMedico($m['id_medico']);
        $nombresDia = array_column($ds, 'nombre');
        $diasStr = implode(', ', $nombresDia) ?: 'Ninguno';

        // Agrega la información del médico al resultado final
        $resultado[] = [
            'id' => $m['id_medico'],
            'dni' => $m['dni'],
            'nombre' => $m['nombre'],
            'apellido' => $m['apellido'],
            'email' => $m['email'],
            'telefono' => $m['telefono'],
            'especialidades' => $especialidadesStr,
            'dias' => $diasStr
        ];
    }

    // Limpia la salida y responde con la lista de médicos en formato JSON
    ob_clean();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si ocurre un error, limpia la salida, lo registra y responde con el mensaje de error
    ob_clean();
    error_log('Error en listar_medicos: ' . $e->getMessage());
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db)) $db->close();
}
?>