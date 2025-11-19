<?php
// MEJOR MANEJO DE ERRORES - Evita que se muestre HTML
header("Content-Type: application/json; charset=utf-8");
ob_start(); // Capturar cualquier output no deseado

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

// Incluye los archivos necesarios
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
    
    // DEBUG DETALLADO
    error_log("=== listar_mes INICIO ===");
    error_log("Datos recibidos: " . json_encode($data));
    
    $id_medico = isset($data["id_medico"]) && $data["id_medico"] !== null && $data["id_medico"] !== 'undefined' && $data["id_medico"] !== '' 
        ? (int) $data["id_medico"] 
        : null;
    $id_especialidad = isset($data["id_especialidad"]) && $data["id_especialidad"] !== null && $data["id_especialidad"] !== 'undefined' && $data["id_especialidad"] !== '' 
        ? (int) $data["id_especialidad"] 
        : null;

    error_log("Filtros procesados - Médico: " . ($id_medico ?? 'NULL') . ", Especialidad: " . ($id_especialidad ?? 'NULL'));

    try {
        $fecha_inicio = sprintf("%04d-%02d-01", $year, $month);
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
        
        // CONSULTA CORREGIDA CON DEBUG
        $sql = "SELECT t.fecha, COUNT(*) as count 
                FROM turnos t 
                JOIN medico m ON t.id_medico = m.id 
                WHERE t.estado = 'disponible' 
                AND t.fecha BETWEEN ? AND ?";
        
        $params = [$fecha_inicio, $fecha_fin];
        $types = "ss";
        
        error_log("Consulta base: " . $sql);
        
        if ($id_medico) {
            $sql .= " AND t.id_medico = ?";
            $params[] = $id_medico;
            $types .= "i";
            error_log("➕ Agregando filtro médico: " . $id_medico);
        }
        
        if ($id_especialidad) {
            $sql .= " AND t.id_medico IN (SELECT me.id_medico FROM medico_especialidad me WHERE me.id_especialidad = ?)";
            $params[] = $id_especialidad;
            $types .= "i";
            error_log("➕ Agregando filtro especialidad: " . $id_especialidad);
        }
        
        $sql .= " GROUP BY t.fecha ORDER BY t.fecha";
        
        error_log("Consulta final: " . $sql);
        error_log("Parámetros: " . json_encode($params));
        error_log("Tipos: " . $types);
        
        $stmt = $db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $disponibilidad = [];
        while ($row = $result->fetch_assoc()) {
            $disponibilidad[$row['fecha']] = (int)$row['count'];
        }
        
        error_log("Disponibilidad calculada: " . json_encode($disponibilidad));
        error_log("=== listar_mes FIN ===");
        
        echo json_encode($disponibilidad);
    } catch (Exception $e) {
        error_log("❌ Error en listar_mes: " . $e->getMessage());
        echo json_encode(["error" => "Error al obtener disponibilidad: " . $e->getMessage()]);
    }
    break;
    
    // En la acción "reservar" del switch case:
case "reservar":
    $id_turno = (int) ($data["id_turno"] ?? 0);
    
    // VERIFICAR SESIÓN PRIMERO
    if (!isset($_SESSION["id_usuario"])) {
        echo json_encode(["error" => "Debe iniciar sesión para reservar turnos"]);
        break;
    }
    
    // Obtener el ID del paciente desde la sesión del usuario
    $id_usuario = (int) $_SESSION["id_usuario"];
    
    // Necesitamos convertir id_usuario a id_paciente
    // Buscar si el usuario es un paciente
    $sql_paciente = "SELECT id FROM paciente WHERE id = ?";
    $stmt_paciente = $db->prepare($sql_paciente);
    $stmt_paciente->bind_param("i", $id_usuario);
    $stmt_paciente->execute();
    $result_paciente = $stmt_paciente->get_result();
    
    if ($result_paciente->num_rows === 0) {
        echo json_encode(["error" => "El usuario no es un paciente registrado"]);
        break;
    }
    
    $row_paciente = $result_paciente->fetch_assoc();
    $id_paciente = (int) $row_paciente['id'];
    
    // Verifica que existan los datos necesarios
    if (!$id_turno || !$id_paciente) {
        echo json_encode(["error" => "Faltan datos para reservar"]);
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

    case "medicos":
    $id_especialidad = !empty($data["id_especialidad"]) ? (int) $data["id_especialidad"] : null;
    
    if ($id_especialidad) {
        // Filtrar médicos por especialidad
        echo json_encode($medicoModel->obtenerPorEspecialidad($id_especialidad));
    } else {
        // Todos los médicos
        echo json_encode($medicoModel->obtenerTodosConUsuario());
    }
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
