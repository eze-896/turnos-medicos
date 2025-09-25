<?php
header('Content-Type: application/json; charset=utf-8');
ob_start(); // Limpiar output
ini_set('display_errors', 0);

ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    // Verificar archivos
    if (!file_exists('../modelos/conexion.php') || !file_exists('../modelos/usuario.php') || 
        !file_exists('../modelos/medico.php') || !file_exists('../modelos/especialidad.php') ||
        !file_exists('../modelos/medico_especialidad.php') || !file_exists('../modelos/dia.php') ||
        !file_exists('../modelos/medico_dia.php')) {
        throw new Exception('Archivos de modelo no encontrados. Verifica la estructura de carpetas.');
    }

    require_once '../modelos/conexion.php';
    require_once '../modelos/usuario.php';
    require_once '../modelos/medico.php';
    require_once '../modelos/especialidad.php';
    require_once '../modelos/medico_especialidad.php';
    require_once '../modelos/dia.php';
    require_once '../modelos/medico_dia.php';

    // Usar tu clase Conexion
    $conexion = new Conexion();
    $db = $conexion->conectar();
    if (!$db) {
        throw new Exception('Error al obtener conexión a la base de datos.');
    }

    // Iniciar transacción para atomicidad
    $db->autocommit(false);

    $usuario = new Usuario($db);
    $medico = new Medico($db);
    $especialidadModel = new Especialidad($db); // Para crear si no existe
    $medicoEspecialidad = new MedicoEspecialidad($db);
    $diaModel = new Dia($db); // Para verificar/crear si no existe
    $medicoDia = new MedicoDia($db);

    // Recopilar y validar datos básicos
    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = 'medico';
    $especialidades = array_map('intval', $_POST['especialidades'] ?? []); // Convertir a int
    $dias = array_map('intval', $_POST['dias'] ?? []); // Convertir a int

    // Validaciones exhaustivas
    $errores = [];
    if (empty($dni) || !ctype_digit($dni) || strlen($dni) !== 8) {
        $errores[] = 'DNI inválido (8 dígitos numéricos).';
    }
    if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 50 || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
        $errores[] = 'Nombre inválido (2-50 letras).';
    }
    if (empty($apellido) || strlen($apellido) < 2 || strlen($apellido) > 50 || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
        $errores[] = 'Apellido inválido (2-50 letras).';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Email inválido.';
    }
    if (empty($contrasena) || strlen($contrasena) < 8) {
        $errores[] = 'Contraseña debe tener al menos 8 caracteres.';
    }
    if (empty($telefono) || !ctype_digit($telefono) || strlen($telefono) !== 10) {
        $errores[] = 'Teléfono inválido (10 dígitos numéricos).';
    }
    if (empty($especialidades)) {
        $errores[] = 'Selecciona al menos una especialidad.';
    }
    if (empty($dias)) {
        $errores[] = 'Selecciona al menos un día laborable.';
    }

    if (!empty($errores)) {
        throw new Exception(implode(' ', $errores));
    }

    if ($usuario->existe($dni, $email)) {
        throw new Exception('DNI o email ya existe en el sistema.');
    }

    // Hashear contraseña
    $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Crear Usuario
    $usuario->dni = $dni;
    $usuario->nombre = $nombre;
    $usuario->apellido = $apellido;
    $usuario->email = $email;
    $usuario->contrasena = $contrasenaHash;
    $usuario->rol = $rol;
    $usuario->telefono = $telefono;

    if (!$usuario->registrar()) {
        throw new Exception('Error al registrar usuario en la base de datos.');
    }

    $id_usuario = $usuario->id;

    // Crear Medico
    if (!$medico->registrar($id_usuario)) {
        throw new Exception('Error al crear perfil de médico.');
    }

    // Procesar Especialidades: Verificar/crear si no existe y asociar
    $especialidadesCreadas = [];
    foreach ($especialidades as $id_esp) {
        $espExistente = $especialidadModel->buscarPorId($id_esp);
        if (!$espExistente) {
            // Crear especialidad si no existe (salvaguarda)
            $especialidadModel->nombre = "Especialidad Desconocida ID: $id_esp";
            if ($especialidadModel->crear()) {
                $id_esp = $especialidadModel->id; // Usar el nuevo ID
                $especialidadesCreadas[] = $id_esp;
            } else {
                // Saltar si falla creación
                continue;
            }
        }
        if (!$medicoEspecialidad->asociar($id_usuario, $id_esp)) {
            throw new Exception("Error al asociar especialidad ID: $id_esp.");
        }
    }

    // Procesar Días: Verificar si existe y asociar (no crear, ya que son estáticos; saltar si no)
    $diasInvalidos = [];
    foreach ($dias as $id_dia) {
        $diaExistente = $diaModel->buscarPorId($id_dia);
        if ($diaExistente) {
            if (!$medicoDia->asociar($id_usuario, $id_dia)) {
                throw new Exception("Error al asociar día ID: $id_dia.");
            }
        } else {
            $diasInvalidos[] = $id_dia;
        }
    }
    if (!empty($diasInvalidos)) {
        // No falla el registro, pero informa
        error_log("Días inválidos ignorados: " . implode(', ', $diasInvalidos));
    }

    // Commit transacción
    $db->commit();
    $db->autocommit(true);

    $mensajeExtra = !empty($especialidadesCreadas) ? ' (Creadas nuevas especialidades: ' . implode(', ', $especialidadesCreadas) . ')' : '';
    echo json_encode([
        'exito' => true, 
        'id' => $id_usuario, 
        'mensaje' => 'Médico registrado exitosamente.' . $mensajeExtra
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Rollback si hay error
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    error_log('Error en registro_medico: ' . $e->getMessage()); // Log para depuración
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    error_log('Error SQL en registro_medico: ' . $e->getMessage());
    echo json_encode(['exito' => false, 'mensaje' => 'Error en la base de datos: ' . $e->getCode()], JSON_UNESCAPED_UNICODE);
} finally {
    // Cerrar conexión si existe
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>