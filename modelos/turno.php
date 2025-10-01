<?php
// Define una clase para manejar los turnos médicos en la base de datos
class Turno {
    private $conn;                  // Guarda la conexión a la base de datos
    private $table = 'turnos';      // Nombre de la tabla de turnos

    // Cuando crea un objeto Turno, recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    // Lista los turnos disponibles según filtros de fecha, médico y especialidad
    public function listarDisponibles($fecha = null, $id_medico = null, $id_especialidad = null) {
        // Actualiza los estados de turnos pasados para que no aparezcan como disponibles
        $this->actualizarEstadosPasados();

        $sql = "SELECT t.id, t.fecha, t.hora_inicio, t.estado, u.nombre as medico_nombre, u.apellido as medico_apellido 
                FROM $this->table t 
                JOIN medico m ON t.id_medico = m.id 
                JOIN usuario u ON m.id_usuario = u.id 
                WHERE t.estado = 'disponible'";
        $params = [];
        $types = '';
        if ($fecha) {
            $sql .= " AND t.fecha = ?";
            $params[] = $fecha;
            $types .= 's';
        }
        if ($id_medico) {
            $sql .= " AND t.id_medico = ?";
            $params[] = $id_medico;
            $types .= 'i';
        }
        if ($id_especialidad) {
            // Filtra solo médicos con esa especialidad
            $sql .= " AND t.id_medico IN (SELECT me.id_medico FROM medico_especialidad me WHERE me.id_especialidad = ?)";
            $params[] = $id_especialidad;
            $types .= 'i';
        }
        $sql .= " ORDER BY t.hora_inicio";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Registra en el log el tiempo que tarda la consulta (opcional)
        $tiempo = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        error_log("listarDisponibles tardó: " . $tiempo . "s");

        return $result;
    }

    // Permite a un paciente reservar un turno disponible
    public function reservar($id_turno, $id_paciente) {
        $sql = "UPDATE $this->table SET id_paciente = ?, estado = 'reservado' WHERE id = ? AND estado = 'disponible'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_paciente, $id_turno);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['exito' => true, 'mensaje' => 'Turno reservado exitosamente'];
        }
        return ['error' => 'Turno no disponible o error al reservar'];
    }

    // Devuelve la lista de turnos reservados o cancelados de un paciente
    public function misTurnos($id_paciente) {
        // Actualiza los estados de turnos pasados
        $this->actualizarEstadosPasados();

        $sql = "SELECT t.id, t.fecha, t.hora_inicio, t.estado, u.nombre as medico_nombre, u.apellido as medico_apellido 
                FROM $this->table t 
                JOIN medico m ON t.id_medico = m.id 
                JOIN usuario u ON m.id_usuario = u.id 
                WHERE t.id_paciente = ? AND t.estado IN ('reservado', 'cancelado')
                ORDER BY t.fecha ASC, t.hora_inicio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_paciente);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Permite cancelar un turno reservado
    public function cancelar($id_turno) {
        $sql = "UPDATE $this->table SET estado = 'cancelado', id_paciente = NULL WHERE id = ? AND estado = 'reservado'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_turno);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['exito' => true, 'mensaje' => 'Turno cancelado exitosamente'];
        }
        return ['error' => 'Error al cancelar (solo turnos reservados)'];
    }

    // Actualiza los estados de los turnos pasados para que no aparezcan como disponibles
    private function actualizarEstadosPasados() {
        $sql = "UPDATE $this->table SET estado = 'cancelado' WHERE fecha < CURDATE() AND estado = 'disponible'";
        $this->conn->query($sql);
    }

    // Verifica si el médico trabaja el día de la fecha indicada
    public function esDiaLaborable($id_medico, $fecha) {
        $dia_semana = date('N', strtotime($fecha));  // 1=Lun a 7=Dom
        $sql = "SELECT 1 FROM medico_dia WHERE id_medico = ? AND id_dia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_medico, $dia_semana);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // Crea un nuevo turno disponible para un médico en una fecha y hora
    public function crear($id_medico, $fecha, $hora_inicio) {
        // Verifica que el médico trabaje ese día
        if (!$this->esDiaLaborable($id_medico, $fecha)) {
            return ['error' => 'El médico no trabaja ese día'];
        }

        $estado = "disponible";
        $sql = "INSERT INTO $this->table (id_medico, fecha, hora_inicio, estado) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['error' => 'Error preparando INSERT: ' . $this->conn->error];
        }
        $stmt->bind_param("isss", $id_medico, $fecha, $hora_inicio, $estado);
        if ($stmt->execute()) {
            return ['exito' => true, 'mensaje' => 'Turno creado exitosamente'];
        }
        return ['error' => 'Error al crear turno: ' . $stmt->error];
    }

    // Permite editar un turno disponible (cambiar fecha y hora)
    public function editar($id_turno, $fecha, $hora_inicio) {
        $sql = "UPDATE $this->table SET fecha = ?, hora_inicio = ? WHERE id = ? AND estado = 'disponible'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['error' => 'Error preparando UPDATE: ' . $this->conn->error];
        }
        $stmt->bind_param("ssi", $fecha, $hora_inicio, $id_turno);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['exito' => true, 'mensaje' => 'Turno actualizado exitosamente'];
        }
        return ['error' => 'Solo se pueden modificar turnos disponibles'];
    }

    // Permite eliminar un turno disponible
    public function eliminar($id_turno) {
        $sql = "DELETE FROM $this->table WHERE id = ? AND estado = 'disponible'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_turno);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ['exito' => true, 'mensaje' => 'Turno eliminado exitosamente'];
        }
        return ['error' => 'Solo se pueden eliminar turnos disponibles'];
    }

    // Devuelve la lista de todos los turnos de un médico (para la secretaría)
    public function listarPorMedico($id_medico) {
        $sql = "SELECT t.id, t.fecha, t.hora_inicio, t.estado,
                       u.nombre AS medico_nombre, u.apellido AS medico_apellido
                FROM $this->table t
                JOIN medico m ON t.id_medico = m.id
                JOIN usuario u ON m.id_usuario = u.id
                WHERE t.id_medico = ?
                ORDER BY t.fecha ASC, t.hora_inicio ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_medico);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Lista la cantidad de turnos disponibles por día en un mes, con filtros opcionales
    public function listarDisponiblesPorMes($year, $month, $id_medico = null, $id_especialidad = null) {
        $fecha_inicio = sprintf("%04d-%02d-01", $year, $month);
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
        
        $sql = "SELECT DATE(fecha_hora) as fecha, COUNT(*) as count 
                FROM turnos 
                WHERE estado = 'disponible' 
                AND fecha_hora BETWEEN ? AND LAST_DAY(?)";
        
        $params = [$fecha_inicio, $fecha_inicio];
        
        if ($id_medico) {
            $sql .= " AND id_medico = ?";
            $params[] = $id_medico;
        }
        
        if ($id_especialidad) {
            $sql .= " AND id_medico IN (SELECT id FROM medicos WHERE id_especialidad = ?)";
            $params[] = $id_especialidad;
        }
        
        $sql .= " GROUP BY DATE(fecha_hora)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['fecha']] = (int)$row['count'];
        }
        
        return $result;
    }

/**
 * Depura la tabla de turnos eliminando los turnos disponibles
 * que estén antes de la fecha actual o más de 3 meses en el futuro.
 * Retorna un array con la cantidad de turnos eliminados y posibles errores.
 */
public function depurarTurnosDisponibles() {
    $resultado = ['eliminados' => 0, 'error' => null];

    // Fecha actual y fecha límite (3 meses a futuro)
    $fecha_actual = date('Y-m-d');
    $fecha_limite = date('Y-m-d', strtotime('+3 months'));

    // Eliminar turnos disponibles fuera del rango permitido
    $sql = "DELETE FROM $this->table 
            WHERE estado = 'disponible' 
            AND (fecha < ? OR fecha > ?)";
    $stmt = $this->conn->prepare($sql);
    if (!$stmt) {
        $resultado['error'] = "Error preparando la consulta: " . $this->conn->error;
        return $resultado;
    }
    $stmt->bind_param("ss", $fecha_actual, $fecha_limite);
    if ($stmt->execute()) {
        $resultado['eliminados'] = $stmt->affected_rows;
    } else {
        $resultado['error'] = "Error ejecutando la consulta: " . $stmt->error;
    }
    $stmt->close();

    return $resultado;
}
}
?>