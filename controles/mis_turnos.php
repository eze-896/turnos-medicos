<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

include_once '../modelos/conexion.php';
include_once '../modelos/turno.php';

try {
    $db = (new Conexion())->conectar();
    $turnoModel = new Turno($db);
    
    // Obtener el ID del paciente desde el usuario
    $id_usuario = $_SESSION['id_usuario'];
    $sql_paciente = "SELECT id FROM paciente WHERE id = ?";
    $stmt_paciente = $db->prepare($sql_paciente);
    $stmt_paciente->bind_param("i", $id_usuario);
    $stmt_paciente->execute();
    $result_paciente = $stmt_paciente->get_result();
    
    if ($result_paciente->num_rows === 0) {
        echo json_encode(['error' => 'Usuario no es paciente']);
        exit;
    }
    
    $row_paciente = $result_paciente->fetch_assoc();
    $id_paciente = $row_paciente['id'];

    // Manejar diferentes acciones
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? 'listar';

    switch ($accion) {
        case 'listar':
            $turnos = $turnoModel->misTurnos($id_paciente);
            echo json_encode([
                'success' => true,
                'turnos' => $turnos
            ]);
            break;

        case 'cancelar':
            $id_turno = (int) ($_POST['id_turno'] ?? 0);
            
            if (!$id_turno) {
                echo json_encode(['error' => 'ID de turno requerido']);
                exit;
            }

            // Verificar que el turno pertenece al paciente
            $sql_verificar = "SELECT id FROM turnos WHERE id = ? AND id_paciente = ?";
            $stmt_verificar = $db->prepare($sql_verificar);
            $stmt_verificar->bind_param("ii", $id_turno, $id_paciente);
            $stmt_verificar->execute();
            $result_verificar = $stmt_verificar->get_result();
            
            if ($result_verificar->num_rows === 0) {
                echo json_encode(['error' => 'Turno no encontrado o no pertenece al paciente']);
                exit;
            }

            $resultado = $turnoModel->cancelar($id_turno);
            
            if (isset($resultado['exito']) && $resultado['exito']) {
                echo json_encode([
                    'success' => true,
                    'message' => $resultado['mensaje']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $resultado['error'] ?? 'Error al cancelar el turno'
                ]);
            }
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>