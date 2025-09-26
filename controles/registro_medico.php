<?php
// Indica que la respuesta será en formato JSON y con codificación UTF-8
header('Content-Type: application/json; charset=utf-8');
// Limpia el buffer de salida 
ob_start();
// Oculta los errores para que no se muestren al usuario
ini_set('display_errors', 0);
ob_clean();

// Verifica que la persona envía los datos usando el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

try {
    // Verifica que existan todos los archivos necesarios para conectarse a la base de datos y manejar médicos, especialidades y días
    $archivosRequeridos = [
        '../modelos/conexion.php', '../modelos/usuario.php', '../modelos/medico.php',
        '../modelos/especialidad.php', '../modelos/medico_especialidad.php',
        '../modelos/dia.php', '../modelos/medico_dia.php'
    ];
    foreach ($archivosRequeridos as $archivo) {
        if (!file_exists($archivo)) {
            throw new Exception('Archivo de modelo no encontrado: ' . basename($archivo));
        }
    }

    // Incluye los archivos necesarios para conectarse a la base de datos y manejar médicos, especialidades y días
    require_once '../modelos/conexion.php';
    require_once '../modelos/usuario.php';
    require_once '../modelos/medico.php';
    require_once '../modelos/especialidad.php';
    require_once '../modelos/medico_especialidad.php';
    require_once '../modelos/dia.php';
    require_once '../modelos/medico_dia.php';

    // Crea una nueva conexión con la base de datos
    $conexion = new Conexion();
    $db = $conexion->conectar();

    // Verifica que la conexión sea exitosa
    if (!$db) {
        throw new Exception('Error al obtener conexión.');
    }

    $db->autocommit(false);

    // Crea los objetos para manejar usuarios, médicos, especialidades y días
    $usuario = new Usuario($db);
    $medico = new Medico($db);
    $especialidadModel = new Especialidad($db);
    $medicoEspecialidad = new MedicoEspecialidad($db);
    $diaModel = new Dia($db);
    $medicoDia = new MedicoDia($db);

    // Toma los datos enviados por la persona desde el formulario
    $id_medico = intval($_POST['id_medico'] ?? 0);
    $esEdit = $id_medico > 0;
    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = 'medico';
    $especialidades = array_map('intval', $_POST['especialidades'] ?? []);
    $dias = array_map('intval', $_POST['dias'] ?? []);

    // Realiza validaciones para asegurarse de que los datos sean correctos
    $errores = [];
    if (empty($dni) || !ctype_digit($dni) || strlen($dni) !== 8) $errores[] = 'DNI inválido.';
    if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 50 || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) $errores[] = 'Nombre inválido.';
    if (empty($apellido) || strlen($apellido) < 2 || strlen($apellido) > 50 || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) $errores[] = 'Apellido inválido.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = 'Email inválido.';
    if (!$esEdit && (empty($contrasena) || strlen($contrasena) < 8)) $errores[] = 'Contraseña requerida (mín. 8 chars).';
    if ($esEdit && !empty($contrasena) && strlen($contrasena) < 8) $errores[] = 'Nueva contraseña inválida.';
    if (empty($telefono) || !ctype_digit($telefono) || strlen($telefono) !== 10) $errores[] = 'Teléfono inválido.';
    if (empty($especialidades)) $errores[] = 'Selecciona al menos una especialidad.';
    if (empty($dias)) $errores[] = 'Selecciona al menos un día.';

    // Si hay errores, los muestra y detiene el proceso
    if (!empty($errores)) {
        throw new Exception(implode(' ', $errores));
    }

    if ($esEdit) {
        // Si la persona está editando un médico existente, primero busca el médico
        $medicoData = $medico->buscarPorId($id_medico);
        if (!$medicoData) {
            throw new Exception('Médico no encontrado.');
        }
        $id_usuario = $medicoData['id_usuario'];

        // Verifica que el DNI y el email no estén repetidos (excepto para el usuario actual)
        if ($usuario->existe($dni, $email, $id_usuario)) {
            throw new Exception('DNI o email ya existe (excluyendo el actual).');
        }

        // Actualiza los datos del usuario
        $usuario->id = $id_usuario;
        $usuario->dni = $dni;
        $usuario->nombre = $nombre;
        $usuario->apellido = $apellido;
        $usuario->email = $email;
        $usuario->telefono = $telefono;
        if (!empty($contrasena)) {
            $usuario->contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        }
        if (!$usuario->actualizar()) {
            throw new Exception('Error al actualizar usuario.');
        }

        // Actualiza los datos del médico
        $medico->id = $id_medico;
        if (!$medico->actualizar()) {
            throw new Exception('Error al actualizar perfil de médico.');
        }

        // Elimina las especialidades y días anteriores para volver a asociar los nuevos
        $medicoEspecialidad->eliminarPorMedico($id_medico);
        $medicoDia->eliminarPorMedico($id_medico);

    } else {
        // Si la persona está registrando un médico nuevo, verifica que el DNI y el email no existan
        if ($usuario->existe($dni, $email)) {
            throw new Exception('DNI o email ya existe.');
        }
        $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

        // Carga los datos del usuario
        $usuario->dni = $dni;
        $usuario->nombre = $nombre;
        $usuario->apellido = $apellido;
        $usuario->email = $email;
        $usuario->contrasena = $contrasenaHash;
        $usuario->rol = $rol;
        $usuario->telefono = $telefono;

        // Registra el usuario y luego el perfil de médico
        if (!$usuario->registrar()) {
            throw new Exception('Error al registrar usuario.');
        }
        $id_usuario = $usuario->id;

        if (!$medico->registrar($id_usuario)) {
            throw new Exception('Error al crear perfil de médico.');
        }
        $id_medico = $medico->id;
    }

    // Asocia las especialidades seleccionadas al médico
    $especialidadesCreadas = [];
    foreach ($especialidades as $id_esp) {
        $espExistente = $especialidadModel->buscarPorId($id_esp);
        if (!$espExistente) {
            $especialidadModel->nombre = "Especialidad Desconocida ID: $id_esp";
            if ($especialidadModel->crear()) {
                $id_esp = $especialidadModel->id;
                $especialidadesCreadas[] = $id_esp;
            } else {
                continue;
            }
        }
        if (!$medicoEspecialidad->asociar($id_medico, $id_esp)) {
            throw new Exception("Error al asociar especialidad ID: $id_esp.");
        }
    }

    // Asocia los días seleccionados al médico (y crea los días si no existen)
    $diasCreados = [];
    $diasPorDefecto = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    foreach ($diasPorDefecto as $id_dia => $nombre_dia) {
        if (!$diaModel->buscarPorId($id_dia)) {
            $sql = "INSERT IGNORE INTO dia (id, nombre) VALUES (?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("is", $id_dia, $nombre_dia);
            if ($stmt->execute()) {
                $diasCreados[] = $id_dia;
            }
        }
    }

    $diasInvalidos = [];
    foreach ($dias as $id_dia) {
        if ($diaModel->buscarPorId($id_dia)) {
            if (!$medicoDia->asociar($id_medico, $id_dia)) {
                throw new Exception("Error al asociar día ID: $id_dia.");
            }
        } else {
            $diasInvalidos[] = $id_dia;
        }
    }
    if (!empty($diasInvalidos)) error_log("Días inválidos: " . implode(', ', $diasInvalidos));

    // Confirma todos los cambios en la base de datos
    $db->commit();
    $db->autocommit(true);

    // Prepara un mensaje extra si se crean especialidades o días nuevos
    $mensajeExtra = (!empty($especialidadesCreadas) ? ' (Especialidades creadas: ' . implode(', ', $especialidadesCreadas) . ')' : '') .
                    (!empty($diasCreados) ? ' (Días creados: ' . implode(', ', $diasCreados) . ')' : '');
    echo json_encode([
        'exito' => true,
        'id' => $id_medico,
        'mensaje' => ($esEdit ? 'Médico actualizado' : 'Médico registrado') . ' exitosamente.' . $mensajeExtra
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si ocurre un error, revierte todos los cambios hechos en la base de datos
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    error_log('Error en registro_medico: ' . $e->getMessage());
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    // Si ocurre un error de base de datos, también revierte los cambios
    if (isset($db) && $db->autocommit(false)) {
        $db->rollback();
        $db->autocommit(true);
    }
    error_log('Error SQL en registro_medico: ' . $e->getMessage());
    echo json_encode(['exito' => false, 'mensaje' => 'Error en BD: ' . $e->getCode()], JSON_UNESCAPED_UNICODE);
} finally {
    // Cierra la conexión a la base de datos si está abierta
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>