<?php
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');

// Limpia cualquier salida previa para evitar errores en la respuesta
ob_start();
ob_clean();

// Comprueba si la persona envía los datos usando el método POST y si incluye el ID del médico
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    // Si no es POST o falta el ID, responde diciendo que el ID es requerido
    echo json_encode(['exito' => false, 'mensaje' => 'ID requerido']);
    exit;
}

try {
    // Toma el ID del médico y lo convierte en número
    $id_medico = intval($_POST['id']);
    // Verifica que el ID sea válido (mayor que cero)
    if ($id_medico <= 0) {
        throw new Exception('ID inválido.');
    }

    // Incluye los archivos necesarios para conectarse a la base de datos y manejar médicos 
    require_once '../modelos/conexion.php';
    require_once '../modelos/usuario.php';
    require_once '../modelos/medico.php';
    require_once '../modelos/medico_especialidad.php';
    require_once '../modelos/medico_dia.php';

    // Crea una nueva conexión con la base de datos
    $conexion = new Conexion();
    $db = $conexion->conectar();
    // Verifica que la conexión sea exitosa
    if (!$db) {
        throw new Exception('Error en conexión.');
    }

    $db->autocommit(false);

    // Crea los objetos para manejar usuarios, médicos, especialidades y días
    $usuario = new Usuario($db);
    $medico = new Medico($db);
    $medicoEspecialidad = new MedicoEspecialidad($db);
    $medicoDia = new MedicoDia($db);

    // Busca los datos del médico por su ID para asegurarse de que existe
    $medicoData = $medico->buscarPorId($id_medico);
    if (!$medicoData) {
        throw new Exception('Médico no encontrado.');
    }
    // Toma el ID del usuario asociado al médico
    $id_usuario = $medicoData['id_usuario'];

    // Elimina primero las especialidades asociadas al médico
    if (!$medicoEspecialidad->eliminarPorMedico($id_medico)) {
        throw new Exception('Error al eliminar especialidades asociadas.');
    }
    // Elimina los días asociados al médico
    if (!$medicoDia->eliminarPorMedico($id_medico)) {
        throw new Exception('Error al eliminar días asociados.');
    }

    // Elimina el perfil del médico
    if (!$medico->eliminar($id_medico)) {
        throw new Exception('Error al eliminar perfil de médico.');
    }

    // Elimina el usuario asociado al médico
    if (!$usuario->eliminar($id_usuario)) {
        throw new Exception('Error al eliminar usuario.');
    }

    // Si todo sale bien, confirma los cambios en la base de datos
    $db->commit();
    $db->autocommit(true);

    // Limpia la salida y responde que el médico se elimina exitosamente
    ob_clean();
    echo json_encode(['exito' => true, 'mensaje' => 'Médico eliminado exitosamente.']);

} catch (Exception $e) {
    // Si ocurre un error, revierte todos los cambios hechos en la base de datos
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    // Registra el error para los administradores y responde con el mensaje de error
    error_log('Error en eliminar_medico: ' . $e->getMessage());
    ob_clean();
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    // Si ocurre un error de base de datos, también revierte los cambios
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    // Registra el error SQL y responde con un mensaje de error
    error_log('Error SQL en eliminar_medico: ' . $e->getMessage());
    ob_clean();
    echo json_encode(['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getCode()], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>