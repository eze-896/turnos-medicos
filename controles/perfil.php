<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

include_once '../modelos/conexion.php';
include_once '../modelos/usuario.php';
include_once '../modelos/paciente.php';

try {
    $db = (new Conexion())->conectar();
    $usuario = new Usuario($db);
    $paciente = new Paciente($db);
    
    $id_usuario = $_SESSION['id_usuario'];
    $datosUsuario = $usuario->buscarPorId($id_usuario);
    
    if (!$datosUsuario) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    // Obtener datos del paciente si el rol es 'paciente'
    $datosPaciente = null;
    if ($datosUsuario['rol'] === 'paciente') {
        $datosPaciente = $paciente->buscarPorId($id_usuario);
    }
    
    echo json_encode([
        'success' => true,
        'datos' => [
            'nombre' => $datosUsuario['nombre'],
            'apellido' => $datosUsuario['apellido'],
            'email' => $datosUsuario['email'],
            'dni' => $datosUsuario['dni'],
            'telefono' => $datosUsuario['telefono'],
            'rol' => $datosUsuario['rol'],
            'obra_social' => $datosPaciente['obra_social'] ?? null,
            'num_afiliado' => $datosPaciente['num_afiliado'] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>