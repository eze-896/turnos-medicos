$(document).ready(function() {
    // Cargar listas al iniciar
    cargarEspecialidades();
    cargarDias();

    // Validar y enviar formulario (maneja create/update)
    $('#form-registro-medico').on('submit', function(e) {
        e.preventDefault();

        if (!validarFormulario()) {
            return;
        }

        const id_medico = $('#id_medico').val();
        const esEdit = id_medico !== '';
        const datos = {
            id_medico: id_medico, // Para modo edit
            dni: $('#dni').val().trim(),
            nombre: $('#nombre').val().trim(),
            apellido: $('#apellido').val().trim(),
            email: $('#email').val().trim(),
            contrasena: $('#contrasena').val(), // Opcional en edit
            telefono: $('#telefono').val().trim(),
            especialidades: [],
            dias: []
        };

        // Obtener checkboxes
        $('input[name="especialidades[]"]:checked').each(function() {
            datos.especialidades.push($(this).val());
        });
        $('input[name="dias[]"]:checked').each(function() {
            datos.dias.push($(this).val());
        });

        // Enviar via AJAX
        $.ajax({
            url: '../controles/registro_medico.php',
            type: 'POST',
            data: datos,
            dataType: 'json',
            success: function(respuesta) {
                const msg = $('#mensaje-respuesta');
                if (respuesta.exito) {
                    msg.html('<p class="exito">' + (esEdit ? 'Médico actualizado' : 'Médico registrado') + ' exitosamente. ID: ' + respuesta.id + '</p>');
                    resetForm(); // Reset siempre
                    cargarEspecialidades();
                    cargarDias();
                    if (esEdit) {
                        // Si estaba editando, recargar tabla
                        if ($('#seccion-gestionar-medicos').is(':visible')) {
                            cargarTablaMedicos();
                        }
                    }
                } else {
                    msg.html('<p class="error">Error: ' + (respuesta.mensaje || 'Error desconocido') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en submit:', error, xhr.responseText); // Debug
                $('#mensaje-respuesta').html('<p class="error">Error de conexión: ' + error + '. Ver consola para detalles.</p>');
            }
        });
    });
});

// Función para cargar especialidades (con callback para editar)
function cargarEspecialidades(callback) {
    $.ajax({
        url: '../controles/listar_especialidades.php',
        type: 'GET',
        success: function(respuesta) {
            let especialidades;
            if (typeof respuesta === 'string') {
                try {
                    especialidades = JSON.parse(respuesta);
                } catch (e) {
                    console.error('Respuesta no es JSON válido:', respuesta);
                    $('#lista-especialidades').html('<p class="error">Error al cargar especialidades.</p>');
                    return;
                }
            } else {
                especialidades = respuesta;
            }

            if (especialidades.error) {
                console.error('Error del servidor:', especialidades.mensaje);
                $('#lista-especialidades').html('<p class="error">Error del servidor: ' + especialidades.mensaje + '</p>');
                return;
            }

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
            if (callback) callback();
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX especialidades:', status, error, xhr.responseText);
            $('#lista-especialidades').html('<p class="error">Error al cargar especialidades: ' + error + '. Ver consola.</p>');
        }
    });
}

// Nueva función: Cargar días dinámicamente (similar a especialidades, con callback)
function cargarDias(callback) {
    $.ajax({
        url: '../controles/listar_dias.php',
        type: 'GET',
        success: function(respuesta) {
            let dias;
            if (typeof respuesta === 'string') {
                try {
                    dias = JSON.parse(respuesta);
                } catch (e) {
                    console.error('Respuesta no es JSON válido para días:', respuesta);
                    $('#lista-dias').html('<p class="error">Error al cargar días.</p>');
                    return;
                }
            } else {
                dias = respuesta;
            }

            if (dias.error) {
                console.error('Error del servidor en días:', dias.mensaje);
                $('#lista-dias').html('<p class="error">Error del servidor: ' + dias.mensaje + '</p>');
                return;
            }

            const contenedor = $('#lista-dias');
            contenedor.empty();
            if (dias.length === 0) {
                contenedor.html('<p>No hay días disponibles. Se crearán por defecto al registrar.</p>');
            } else {
                dias.forEach(function(dia) {
                    contenedor.append(
                        '<label><input type="checkbox" name="dias[]" value="' + dia.id + '"> ' + dia.nombre + '</label><br>'
                    );
                });
            }
            if (callback) callback();
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX días:', status, error, xhr.responseText);
            $('#lista-dias').html('<p class="error">Error al cargar días: ' + error + '. Ver consola.</p>');
        }
    });
}

// Validación avanzada (actualizada: contraseña opcional en edit)
function validarFormulario() {
    const id_medico = $('#id_medico').val();
    const esEdit = id_medico !== '';
    const campos = [
        { id: 'dni', regex: /^\d{8}$/, msg: 'DNI debe ser numérico de 8 dígitos.' },
        { id: 'nombre', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Nombre inválido (solo letras, 2-50 chars).' },
        { id: 'apellido', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Apellido inválido (solo letras, 2-50 chars).' },
        { id: 'email', regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Email inválido.' },
        { id: 'telefono', regex: /^\d{10}$/, msg: 'Teléfono debe ser numérico de 10 dígitos.' }
    ];

    let valido = true;
    campos.forEach(function(campo) {
        const valor = $('#' + campo.id).val().trim();
        if (!valor || !campo.regex.test(valor)) {
            alert('Error en ' + campo.id + ': ' + campo.msg);
            valido = false;
            return false;
        }
    });

    // Contraseña: Requerida solo en create
    if (!esEdit) {
        const contrasena = $('#contrasena').val();
        if (!contrasena || contrasena.length < 8) {
            alert('Contraseña debe tener al menos 8 caracteres (requerida para nuevo médico).');
            valido = false;
        }
    } else if ($('#contrasena').val() && $('#contrasena').val().length < 8) {
        alert('Nueva contraseña debe tener al menos 8 caracteres si la cambias.');
        valido = false;
    }

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

// Función reset (llamada desde HTML/JS externo)
function resetForm() {
    $('#form-registro-medico')[0].reset();
    $('#id_medico').val('');
    $('#contrasena').attr('placeholder', 'Contraseña');
    $('#form-titulo').text('Agregar Nuevo Médico');
    $('#btn-submit').text('Registrar Médico');
    $('#volver-lista').hide();
    $('#mensaje-respuesta').empty();
}