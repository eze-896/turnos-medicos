// Flag global para evitar recargas múltiples de checkboxes (una vez por sesión)
let checkboxesCargados = false;

// Función para cargar la tabla de médicos via AJAX (sin cambios)
function cargarTablaMedicos() {
    $.ajax({
        url: '../controles/listar_medicos.php',
        type: 'GET',
        dataType: 'json',
        success: function(medicos) {
            console.log('Tabla cargada exitosamente:', medicos.length, 'médicos');
            const tbody = $('#tabla-medicos tbody');
            tbody.empty();
            if (medicos.length === 0) {
                tbody.append('<tr><td colspan="8">No hay médicos registrados.</td></tr>');
                return;
            }
            medicos.forEach(function(med) {
                const nombreCompleto = med.nombre + ' ' + med.apellido;
                tbody.append(`
                    <tr>
                        <td>${med.id}</td>
                        <td>${med.nombre}</td>
                        <td>${med.apellido}</td>
                        <td>${med.email}</td>
                        <td>${med.telefono}</td>
                        <td>${med.especialidades || 'Ninguna'}</td>
                        <td>${med.dias || 'Ninguno'}</td>
                        <td>
                            <button onclick="editarMedico(${med.id})" class="btn-editar">Editar</button>
                            <button onclick="agendarTurnos(${med.id}, '${nombreCompleto}', '${med.especialidades}')" class="btn-agendar">Agendar</button>
                            <button onclick="eliminarMedico(${med.id}, '${med.nombre} ${med.apellido}')" class="btn-eliminar">Eliminar</button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar tabla:', error, xhr.responseText);
            $('#mensaje-tabla').html('<p class="error">Error al cargar médicos: ' + error + '. Ver consola.</p>');
        }
    });
}

// Función para editar médico: Carga datos y switch a modo edit (MODIFICADA: Checkboxes post-mostrarSeccion con flag)
function editarMedico(id) {
    console.log('=== INICIANDO EDITAR MÉDICO ID:', id, '===');
    $.ajax({
        url: '../controles/obtener_medico.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(medico) {
            console.log('Success: Datos recibidos COMPLETOS:', medico);
            if (medico.error) {
                console.error('Error en respuesta:', medico.mensaje);
                alert('Error: ' + medico.mensaje);
                return;
            }
            
            // Validación flexible (logs reducidos)
            const datosFaltantes = [];
            if (!medico.nombre) datosFaltantes.push('nombre');
            if (!medico.apellido) datosFaltantes.push('apellido');
            if (!medico.email) datosFaltantes.push('email');
            if (datosFaltantes.length > 0) {
                console.warn('Datos críticos faltantes:', datosFaltantes);
                alert('Datos críticos faltantes (' + datosFaltantes.join(', ') + '). Ver consola.');
            }
            console.log('DNI recibido:', medico.dni || 'VACÍO');
            console.log('Teléfono recibido:', medico.telefono || 'VACÍO');

            // DEBUG: Verificar elementos del form (logs reducidos)
            console.log('=== VERIFICANDO ELEMENTOS DEL FORM ===');
            const elementos = {
                id_medico: $('#id_medico'),
                dni: $('#dni'),
                nombre: $('#nombre'),
                apellido: $('#apellido'),
                email: $('#email'),
                telefono: $('#telefono'),
                contrasena: $('#contrasena'),
                form_titulo: $('#form-titulo'),
                btn_submit: $('#btn-submit'),
                volver_lista: $('#volver-lista')
            };
            let todosExisten = true;
            Object.keys(elementos).forEach(function(key) {
                const elem = elementos[key];
                if (elem.length === 0) {
                    console.error('¡ERROR! Elemento #' + key + ' NO EXISTE en el HTML.');
                    todosExisten = false;
                } else {
                    console.log('OK: #' + key + ' encontrado');
                }
            });
            if (!todosExisten) {
                alert('Error: Algunos elementos del form no existen. Revisa HTML.');
                return;
            }

            elementos.id_medico.val(medico.id || '');
            elementos.dni.val(medico.dni || '');
            elementos.nombre.val(medico.nombre || '');
            elementos.apellido.val(medico.apellido || '');
            elementos.email.val(medico.email || '');
            elementos.telefono.val(medico.telefono || '');
            elementos.contrasena.attr('placeholder', 'Nueva contraseña (opcional)');
            elementos.form_titulo.text('Editar Médico ID: ' + (medico.id || 'Desconocido'));
            elementos.btn_submit.text('Actualizar Médico');
            elementos.volver_lista.show();

            console.log('=== POBLAMIENTO DE CAMPOS BÁSICOS COMPLETADO ===');

            // Switch a sección form PRIMERO
            console.log('Llamando mostrarSeccion("agregar-medico")...');
            if (typeof mostrarSeccion === 'function') {
                mostrarSeccion('agregar-medico');
                console.log('mostrarSeccion ejecutada');
                // Fallback CORREGIDO: ID real del HTML
                $('#seccion-agregar-medico, #form-container, #form-registro-medico').css('display', 'block');
                console.log('Sección #seccion-agregar-medico forzada visible');
            } else {
                console.error('¡ERROR! Función mostrarSeccion() NO EXISTE.');
                alert('Error: mostrarSeccion() no definida.');
                $('#seccion-agregar-medico, #form-registro-medico').css('display', 'block');
            }

            // NUEVO: Cargar/Marcar checkboxes DESPUÉS de mostrarSeccion (con flag para optimizar)
            console.log('=== MANEJANDO CHECKBOXES POST-VISIBILIDAD ===');
            if (!checkboxesCargados) {
                // Primera vez: Carga completa y marca
                console.log('Primera carga: Cargando checkboxes via AJAX...');
                cargarEspecialidades(function() {
                    marcarCheckboxes(medico.especialidades_ids, 'especialidades[]');
                });
                cargarDias(function() {
                    marcarCheckboxes(medico.dias_ids, 'dias[]');
                });
                checkboxesCargados = true;
                console.log('Flag checkboxesCargados seteada a true');
            } else {
                // Ya cargados: Solo marca (sin AJAX, rápido)
                console.log('Checkboxes ya cargados: Solo marcando...');
                marcarCheckboxes(medico.especialidades_ids, 'especialidades[]');
                marcarCheckboxes(medico.dias_ids, 'dias[]');
            }

            console.log('=== EDITAR MÉDICO COMPLETADO ===');
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX en editarMedico:', status, error, 'Response:', xhr.responseText);
            alert('Error al cargar datos: ' + error + '\nVer consola (F12).');
        }
    });
}

// Función para eliminar médico (sin cambios)
function eliminarMedico(id, nombreCompleto) {
    if (!confirm('¿Eliminar al médico "' + nombreCompleto + '"? Esto eliminará su perfil y asociaciones.')) {
        return;
    }
    $.ajax({
        url: '../controles/eliminar_medico.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(respuesta) {
            if (respuesta.exito) {
                alert('Médico eliminado exitosamente.');
                cargarTablaMedicos(); // Recargar tabla
            } else {
                alert('Error: ' + respuesta.mensaje);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al eliminar:', error, xhr.responseText);
            alert('Error al eliminar médico.');
        }
    });
}

// Función para cargar especialidades (sin cambios, logs reducidos)
function cargarEspecialidades(callback) {
    console.log('Cargando especialidades (AJAX)...');
    $.ajax({
        url: '../controles/listar_especialidades.php',
        type: 'GET',
        success: function(respuesta) {
            let especialidades;
            if (typeof respuesta === 'string') {
                try { especialidades = JSON.parse(respuesta); } catch(e) { console.error('Parse error en especialidades:', e); return; }
            } else { especialidades = respuesta; }
            if (especialidades.error) {
                console.error('Error en especialidades:', especialidades);
                return;
            }
            const contenedor = $('#lista-especialidades');
            contenedor.empty();
            especialidades.forEach(function(esp) {
                contenedor.append('<label><input type="checkbox" name="especialidades[]" value="' + esp.id + '"> ' + esp.nombre + '</label><br>');
            });
            console.log('Especialidades cargadas:', especialidades.length, 'items');
            if (callback) callback();
        },
        error: function(xhr, status, error) {
            console.error('Error cargando especialidades:', status, error, xhr.responseText);
        }
    });
}

// Función para cargar días (similar, logs reducidos)
function cargarDias(callback) {
    console.log('Cargando días (AJAX)...');
    $.ajax({
        url: '../controles/listar_dias.php',
        type: 'GET',
        success: function(respuesta) {
            let dias;
            if (typeof respuesta === 'string') {
                try { dias = JSON.parse(respuesta); } catch(e) { console.error('Parse error en días:', e); return; }
            } else { dias = respuesta; }
            if (dias.error) {
                console.error('Error en días:', dias);
                return;
            }
            const contenedor = $('#lista-dias');
            contenedor.empty();
            dias.forEach(function(dia) {
                contenedor.append('<label><input type="checkbox" name="dias[]" value="' + dia.id + '"> ' + dia.nombre + '</label><br>');
            });
            console.log('Días cargados:', dias.length, 'items');
            if (callback) callback();
        },
        error: function(xhr, status, error) {
            console.error('Error cargando días:', status, error, xhr.responseText);
        }
    });
}

// Helper: Marcar checkboxes sin recargar (reutilizable, con logs para debug)
function marcarCheckboxes(ids, nameSelector) {
    console.log('Marcando checkboxes para ' + nameSelector + ':', ids || []);
    const checkboxes = $('input[name="' + nameSelector + '"]');
    console.log('Checkboxes encontrados:', checkboxes.length);
    if (checkboxes.length === 0) {
        console.warn('No hay checkboxes para ' + nameSelector + ' - Posible error de carga.');
        return;
    }
    checkboxes.prop('checked', false); // Desmarca todos primero
    if (ids && Array.isArray(ids) && ids.length > 0) {
        let marcados = 0;
        ids.forEach(function(id) {
            const cb = $('input[name="' + nameSelector + '"][value="' + id + '"]');
            if (cb.length > 0) {
                cb.prop('checked', true);
                marcados++;
                console.log('Marcado checkbox ID ' + id + ' para ' + nameSelector);
            } else {
                console.warn('Checkbox para ID ' + id + ' no encontrado en ' + nameSelector);
            }
        });
        console.log('Total marcados para ' + nameSelector + ': ' + marcados + '/' + ids.length);
    } else {
        console.log('No hay IDs para marcar en ' + nameSelector + ' (vacío)');
    }
}

// Función para agendar turnos (MODIFICADA: Carga días via AJAX y guarda como JSON)
function agendarTurnos(id, nombreCompleto, especialidades) {
    console.log('Iniciando agendamiento para médico ID:', id);
    
    // Guardar datos básicos en sessionStorage (nombreCompleto como string único)
    sessionStorage.setItem('medico_id', id);
    sessionStorage.setItem('medico_nombre', nombreCompleto);
    sessionStorage.setItem('medico_especialidades', especialidades || 'Ninguna');
    
    // NUEVO: Cargar días laborables del médico via AJAX (array de {id, nombre})
    $.ajax({
        url: '../controles/obtener_dias_medico.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(dias) {
            if (dias.error) {
                console.error('Error cargando días:', dias.mensaje);
                alert('Error al cargar días laborables: ' + dias.mensaje);
                return;
            }
            // Guardar como JSON stringificado (formato válido: [{"id":1,"nombre":"Lunes"}, ...])
            sessionStorage.setItem('medico_dias', JSON.stringify(dias));
            console.log('Días laborables cargados y guardados como JSON:', dias);
            
            // Redirigir a página de agendamiento
            window.location.href = 'agendar_turnos.html';
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX en agendarTurnos:', error, xhr.responseText);
            alert('Error al preparar agendamiento: ' + error);
            // Fallback: Continuar sin días (deshabilitar validación)
            sessionStorage.setItem('medico_dias', JSON.stringify([]));
            window.location.href = 'agendar_turnos.html';
        }
    });
}


// Reset form para modo create (MODIFICADO: Desmarca checkboxes)
function resetForm() {
    $('#form-registro-medico')[0].reset();
    $('#id_medico').val('');
    $('#contrasena').attr('placeholder', 'Contraseña');
    $('#form-titulo').text('Agregar Nuevo Médico');
    $('#btn-submit').text('Registrar Médico');
    $('#volver-lista').hide();
    $('#mensaje-respuesta').empty();
    $('input[name="especialidades[]"], input[name="dias[]"]').prop('checked', false);
    console.log('resetForm: Checkboxes desmarcados');
}
