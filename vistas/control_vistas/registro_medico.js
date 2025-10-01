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
                    console.error('Error en respuesta:', respuesta.mensaje || 'Error desconocido');
                    msg.html('<p class="error">Error: ' + (respuesta.mensaje || 'Error desconocido') + '</p>');
                    alert('Error: ' + (respuesta.mensaje || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en submit:', error, xhr.responseText); // Debug
                $('#mensaje-respuesta').html('<p class="error">Error de conexión: ' + error + '. Ver consola para detalles.</p>');
                alert('Error de conexión: ' + error + '. Revisa la consola para más detalles.');
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
                    alert('Error al cargar especialidades. Revisa la consola.');
                    return;
                }
            } else {
                especialidades = respuesta;
            }

            if (especialidades.error) {
                console.error('Error del servidor:', especialidades.mensaje);
                $('#lista-especialidades').html('<p class="error">Error del servidor: ' + especialidades.mensaje + '</p>');
                alert('Error del servidor al cargar especialidades.');
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
            alert('Error al cargar especialidades. Revisa la consola.');
        }
    });
}

// Nueva función: Cargar días dinámicamente (ordenados Lunes a Domingo)
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
                    alert('Error al cargar días. Revisa la consola.');
                    return;
                }
            } else {
                dias = respuesta;
            }

            if (dias.error) {
                console.error('Error del servidor en días:', dias.mensaje);
                $('#lista-dias').html('<p class="error">Error del servidor: ' + dias.mensaje + '</p>');
                alert('Error del servidor al cargar días.');
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
            alert('Error al cargar días. Revisa la consola.');
        }
    });
}

// Validación avanzada (actualizada: contraseña opcional en edit)
function validarFormulario() {
    const id_medico = $('#id_medico').val();
    const esEdit = id_medico !== '';
    const campos = [
        { id: 'dni', regex: /^\d{7,8}$/, msg: 'DNI debe ser numérico de 7 u 8 dígitos.' },
        { id: 'nombre', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Nombre inválido (solo letras, 2-50 chars).' },
        { id: 'apellido', regex: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/, msg: 'Apellido inválido (solo letras, 2-50 chars).' },
        { id: 'email', regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Email inválido.' },
        { id: 'telefono', regex: /^\d{7,15}$/, msg: 'Teléfono debe ser numérico de 7 a 15 dígitos.' }
    ];

    for (let campo of campos) {
        const valor = $('#' + campo.id).val().trim();
        if (!valor) {
            alert('El campo ' + campo.id + ' es obligatorio.');
            console.error('Campo vacío:', campo.id);
            $('#' + campo.id).focus();
            return false;
        }
        if (!campo.regex.test(valor)) {
            alert('Error en ' + campo.id + ': ' + campo.msg);
            console.error('Error en campo ' + campo.id + ': valor inválido', valor);
            $('#' + campo.id).focus();
            return false;
        }
    }

    // Contraseña: Requerida solo en create
    const contrasena = $('#contrasena').val();
    if (!esEdit) {
        if (!contrasena) {
            alert('La contraseña es obligatoria para nuevo médico.');
            console.error('Contraseña vacía en creación');
            $('#contrasena').focus();
            return false;
        }
        if (contrasena.length < 8) {
            alert('Contraseña debe tener al menos 8 caracteres.');
            console.error('Contraseña demasiado corta en creación');
            $('#contrasena').focus();
            return false;
        }
    } else if (contrasena && contrasena.length < 8) {
        alert('Nueva contraseña debe tener al menos 8 caracteres si la cambias.');
        console.error('Contraseña demasiado corta en edición');
        $('#contrasena').focus();
        return false;
    }

    if ($('input[name="especialidades[]"]:checked').length === 0) {
        alert('Selecciona al menos una especialidad.');
        console.error('No se seleccionó especialidad');
        return false;
    }
    if ($('input[name="dias[]"]:checked').length === 0) {
        alert('Selecciona al menos un día laborable.');
        console.error('No se seleccionó día laborable');
        return false;
    }
    return true;
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