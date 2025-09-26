<?php
// Indica que la respuesta será en formato JSON 
header('Content-Type: application/json');

// Incluye el archivo para conectarse a la base de datos
require_once '../modelos/conexion.php';

// Verifica que la persona envía el ID del médico y que sea un número
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si falta el ID o no es válido, responde con un mensaje de error
    echo json_encode(['error' => true, 'mensaje' => 'ID de médico inválido']);
    exit;
}

// Convierte el ID recibido a número entero
$id_medico = intval($_GET['id']);

// Crea una nueva conexión con la base de datos
$conexion = new Conexion();
$db = $conexion->conectar();

// Verifica que la conexión sea exitosa
if (!$db) {
    echo json_encode(['error' => true, 'mensaje' => 'Error en conexión a la base de datos']);
    exit;
}

// Prepara la consulta para buscar los días en que el médico atiende
$dias = [];
$sql = "SELECT d.id, d.nombre FROM medico_dia md 
        JOIN dia d ON md.id_dia = d.id 
        WHERE md.id_medico = ? 
        ORDER BY d.id";
$stmt = $db->prepare($sql);

// Verifica que la consulta se prepara correctamente
if (!$stmt) {
    echo json_encode(['error' => true, 'mensaje' => 'Error preparando query: ' . $db->error]);
    exit;
}

// Asocia el ID del médico a la consulta
$stmt->bind_param("i", $id_medico);
// Ejecuta la consulta y verifica si funciona
if (!$stmt->execute()) {
    echo json_encode(['error' => true, 'mensaje' => 'Error ejecutando query: ' . $stmt->error]);
    exit;
}

// Recorre los resultados y los guarda en un arreglo
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dias[] = $row; // Por ejemplo: ['id' => 1, 'nombre' => 'Lunes']
}

// Devuelve la lista de días en formato JSON 
echo json_encode($dias);

// Cierra la consulta y la conexión a la base de datos
$stmt->close();
if (isset($db) && $db->connect_error === null) {
    $db->close();
}
?>