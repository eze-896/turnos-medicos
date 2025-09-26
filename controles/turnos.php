<?php
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header("Content-Type: application/json; charset=utf-8");
// Inicia una sesión para manejar datos del usuario logueado
session_start();

// Incluye los archivos necesarios para conectarse a la base de datos y manejar turnos, médicos y especialidades
include_once "../modelos/conexion.php";
include_once "../modelos/turno.php";
include_once "../modelos/medico.php";
include_once "../modelos/especialidad.php";
include_once "../modelos/usuario.php";

// Crea una nueva conexión con la base de datos y los modelos principales
$db = (new Conexion())->conectar();

// Verifica que la conexión sea exitosa
if (!$db) {
    echo json_encode(["error" => "Error al conectar a la base de datos"]);
    exit;
}

$turnoModel = new Turno($db);
$medicoModel = new Medico($db);
$espModel = new Especialidad($db);

// Toma los datos enviados en formato JSON o por GET
$data = json_decode(file_get_contents("php://input"), true);
$accion = $data["accion"] ?? $_GET["accion"] ?? null;

// Según la acción que la persona solicita, ejecuta la opción correspondiente
switch ($accion) {

    // Listar turnos disponibles según filtros
    case "listar":
        $fecha = $data["fecha"] ?? null;
        $id_medico = !empty($data["id_medico"]) ? (int) $data["id_medico"] : null;
        $id_especialidad = !empty($data["id_especialidad"]) ? (int) $data["id_especialidad"] : null;

        // Devuelve la lista de turnos disponibles según los filtros
        echo json_encode($turnoModel->listarDisponibles($fecha, $id_medico, $id_especialidad));
        break;

    // Listar disponibilidad por mes (para mostrar en el calendario)
    case "listar_mes":
        $year = (int) ($data["year"] ?? date('Y'));
        $month = (int) ($data["month"] ?? date('m'));
        $id_medico = !empty($data["id_medico"]) ? (int) $data["id_medico"] : null;
        $id_especialidad = !empty($data["id_especialidad"]) ? (int) $data["id_especialidad"] : null;

        // Valida que el año y el mes sean correctos
        if ($year < 2020 || $year > 2030 || $month < 1 || $month > 12) {
            echo json_encode(["error" => "Fecha inválida"]);
            break;
        }

        try {
            // Calcula el rango de fechas para el mes solicitado
            $fecha_inicio = sprintf("%04d-%02d-01", $year, $month);
            $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
            
            // Obtiene todos los turnos disponibles del mes
            $turnos = $turnoModel->listarDisponibles($fecha_inicio, $id_medico, $id_especialidad);
            
            // Cuenta cuántos turnos hay por cada día
            $disponibilidad = [];
            foreach ($turnos as $turno) {
                $fecha = date("Y-m-d", strtotime($turno['fecha_hora']));
                if (!isset($disponibilidad[$fecha])) {
                    $disponibilidad[$fecha] = 0;
                }
                $disponibilidad[$fecha]++;
            }
            
            // Devuelve la cantidad de turnos disponibles por día
            echo json_encode($disponibilidad);
        } catch (Exception $e) {
            echo json_encode(["error" => "Error al obtener disponibilidad: " . $e->getMessage()]);
        }
        break;
    
    // Reservar un turno para el paciente logueado
    case "reservar":
        $id_turno = (int) ($data["id_turno"] ?? 0);
        // Toma el id_paciente desde la sesión (debe estar logueado)
        $id_paciente = (int) ($_SESSION["id_usuario"] ?? 0);

        // Verifica que existan los datos necesarios
        if (!$id_turno || !$id_paciente) {
            echo json_encode(["error" => "Faltan datos para reservar. Debe iniciar sesión."]);
            break;
        }
        // Intenta reservar el turno
        echo json_encode($turnoModel->reservar($id_turno, $id_paciente));
        break;

    // Cancelar un turno (por id_turno)
    case "cancelar":
        $id_turno = (int) ($data["id_turno"] ?? 0);
        if (!$id_turno) {
            echo json_encode(["error" => "Falta id_turno"]);
            break;
        }
        // Intenta cancelar el turno
        echo json_encode($turnoModel->cancelar($id_turno));
        break;

    // Listar los turnos del paciente logueado
    case "mis_turnos":
        $id_paciente = (int) ($_SESSION["id_paciente"] ?? 0);
        if (!$id_paciente) {
            echo json_encode(["error" => "No se encontró paciente en sesión"]);
            break;
        }
        // Devuelve la lista de turnos del paciente
        echo json_encode($turnoModel->misTurnos($id_paciente));
        break;

    // Listar todos los médicos
    case "medicos":
        echo json_encode($medicoModel->obtenerTodosConUsuario());
        break;

    // Listar todas las especialidades
    case "especialidades":
        echo json_encode($espModel->obtenerTodos());
        break;

    // Listar todos los turnos de un médico
    case "listar_turnos":
        $id_medico = (int) ($_GET["medico_id"] ?? $data["medico_id"] ?? 0);
        if (!$id_medico) {
            echo json_encode(["error" => "Falta medico_id"]);
            break;
        }
        // Devuelve la lista de turnos del médico
        echo json_encode($turnoModel->listarPorMedico($id_medico));
        break;

    // Guardar un turno (crear o editar)
    case "guardar":
        $id_turno = (int) ($data["id_turno"] ?? 0);
        $id_medico = (int) ($data["medico_id"] ?? 0);
        $fecha = $data["fecha"] ?? null;
        $hora = $data["hora"] ?? null;
        $duracion = (int) ($data["duracion"] ?? 30);

        // Verifica que existan los datos necesarios
        if (!$id_medico || !$fecha || !$hora) {
            echo json_encode(["error" => "Faltan datos para guardar turno"]);
            break;
        }

        if ($id_turno) {
            // Edita un turno existente
            echo json_encode($turnoModel->editar($id_turno, $fecha, $hora, $duracion));
        } else {
            // Crea un nuevo turno
            echo json_encode($turnoModel->crear($id_medico, $fecha, $hora, $duracion));
        }
        break;

    // Eliminar un turno (solo si no está reservado)
    case "eliminar":
        $id_turno = (int) ($data["id_turno"] ?? 0);
        if (!$id_turno) {
            echo json_encode(["error" => "Falta id_turno"]);
            break;
        }
        // Intenta eliminar el turno
        echo json_encode($turnoModel->eliminar($id_turno));
        break;
    
    // Si la acción no es válida, responde con un mensaje de error
    default:
        echo json_encode(["error" => "Acción no válida"]);
}

// Cierra la conexión a la base de datos si está abierta
if (isset($db) && $db instanceof mysqli) {
    $db->close();
}
?>
