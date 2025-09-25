$(document).ready(function() {
    // Cargar especialidades al iniciar
    cargarEspecialidades();

    // Validar y enviar formulario
    $('#form-registro-medico').on('submit', function(e) {
        e.preventDefault();

        // Validaciones básicas y avanzadas
        if (!validarFormulario()) {
            return;
        }

        // Recopilar datos
        const datos = {
            dni: $('#dni').val().trim(),
            nombre: $('#nombre').val().trim(),
            apellido: $('#apellido').val().trim(),
            email: $('#email').val().trim(),
            contrasena: $('#contrasena').val(),
            telefono: $('#telefono').val().trim(),
            especialidades: [], // IDs seleccionados
            dias: []
        };

        // Obtener especialidades seleccionadas
        $('input[name="especialidades[]"]:checked').each(function() {
            datos.especialidades.push($(this).val());
        });

        // Obtener días seleccionados (asumiendo IDs 1-7 para Lunes-Domingo)
        $('input[name="dias[]"]:checked').each(function() {
            datos.dias.push($(this).val());
        });

        // Enviar via AJAX
        $.ajax({
            url: '../controles/registro_medico.php',
            type: 'POST',
            data: datos,
            success: function(respuesta) {
                const msg = $('#mensaje-respuesta');
                if (respuesta.exito) {
                    msg.html('<p class="exito">Médico registrado exitosamente. ID: ' + respuesta.id + '</p>');
                    $('#form-registro-medico')[0].reset();
                    // Recargar especialidades si es necesario
                    cargarEspecialidades();
                } else {
                    msg.html('<p class="error">Error: ' + (respuesta.mensaje || 'Error desconocido') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#mensaje-respuesta').html('<p class="error">Error de conexión: ' + error + '. Intenta de nuevo.</p>');
            }
        });
    });
});

// Función para cargar especialidades via AJAX
function cargarEspecialidades() {
    $.ajax({
        url: '../controles/listar_especialidades.php',
        type: 'GET',
        dataType: 'json',
        success: function(especialidades) {
            const contenedor = $('#lista-especialidades');
            contenedor.empty();
            if (especialidades.length === 0) {
                contenedor.html('<p>No hay especialidades disponibles. Se crearán por defecto al registrar.</p>');
            } else {
                especialidades.forEach(function(esp) {
                    contenedor.append(
                        '<label><input type="checkbox" name="especialidades[]" value="' + esp.id + '"> ' + esp.nombre + '</label><br>'
                    );
                });
            }
        },
        error: function(xhr, status, error) {
            $('#lista-especialidades').html('<p class="error">Error al cargar especialidades: ' + error + '</p>');
        }
    });
}

// Validación avanzada del formulario
function validarFormulario() {
    const campos = [
        { id: 'dni', regex: /^\d{8}$/, msg: 'DNI debe ser numérico de 8 dígitos.' },
        { id: 'nombre', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Nombre inválido (solo letras, 2-50 chars).' },
        { id: 'apellido', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Apellido inválido (solo letras, 2-50 chars).' },
        { id: 'email', regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Email inválido.' },
        { id: 'contrasena', regex: /^.{8,}$/, msg: 'Contraseña debe tener al menos 8 caracteres.' },
        { id: 'telefono', regex: /^\d{10}$/, msg: 'Teléfono debe ser numérico de 10 dígitos.' }
    ];

    let valido = true;
    campos.forEach(function(campo) {
        const valor = $('#' + campo.id).val().trim();
        if (!valor || !campo.regex.test(valor)) {
            alert('Error en ' + campo.id + ': ' + campo.msg);
            valido = false;
            return false; // Salir del forEach si hay error
        }
    });

    if ($('input[name="especialidades[]"]:checked').length === 0) {
        alert('Selecciona al menos una especialidad.');
        valido = false;
    }
    if ($('input[name="dias[]"]:checked').length === 0) {
        alert('Selecciona al menos un día laborable.');
        valido = false;
    }
    return valido;
}