document.addEventListener('DOMContentLoaded', () => {
    // Elementos del DOM
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const menuItems = document.querySelectorAll('.menu-item');
    const contentSections = document.querySelectorAll('.content-section');
    const userName = document.getElementById('user-name');
    const userRole = document.getElementById('user-role');
    const userAvatar = document.getElementById('user-avatar');
    const headerSubtitle = document.getElementById('header-subtitle');

    // Cargar datos del usuario al iniciar
    cargarPerfilUsuario();

    // Toggle del menú
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });

// Navegación entre secciones
menuItems.forEach(item => {
    item.addEventListener('click', (e) => {
        if (item.classList.contains('logout')) return;
        
        e.preventDefault();
        
        // Obtener sección actual antes de cambiar
        const seccionActual = document.querySelector('.menu-item.active')?.getAttribute('data-section');
        
        // Remover activo de todos los items
        menuItems.forEach(i => i.classList.remove('active'));
        // Agregar activo al item clickeado
        item.classList.add('active');
        
        // Ocultar todas las secciones
        contentSections.forEach(section => {
            section.classList.remove('active');
            section.style.display = 'none';
        });
        
        // Limpiar sección anterior si es necesario
        if (seccionActual) {
            limpiarSeccionAnterior(seccionActual);
        }
        
        // Mostrar sección correspondiente
        const sectionId = item.getAttribute('data-section');
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            targetSection.style.display = 'block';
            actualizarSubtitulo(sectionId);
            
            // Cargar datos específicos de la sección
            if (sectionId === 'mis-turnos') {
                cargarMisTurnos();
            } else if (sectionId === 'perfil') {
                cargarPerfilCompleto();
            } else if (sectionId === 'calendario') {
                // Inicializar el calendario con un pequeño delay
                setTimeout(() => {
                    inicializarCalendario();
                }, 50);
            }
        }
    });
});
    // FUNCIÓN: Inicializar calendario básico
function inicializarCalendario() {
    console.log('🔄 Inicializando calendario desde dashboard...');
    
    // Verificar si los elementos del calendario existen
    const calendarGrid = document.getElementById('calendar-grid');
    const monthTitle = document.getElementById('current-month');
    
    if (!calendarGrid) {
        console.error('❌ No se encontró el elemento calendar-grid');
        return;
    }
    
    if (!monthTitle) {
        console.error('❌ No se encontró el elemento current-month');
        return;
    }
    
    console.log('✅ Elementos del calendario encontrados');
    
    // Asegurar que toda la sección de calendario sea visible
    const calendarioSection = document.getElementById('calendario');
    if (calendarioSection) {
        calendarioSection.style.display = 'block';
    }
    
    // Asegurar que los elementos del calendario sean visibles
    calendarGrid.style.display = 'grid';
    monthTitle.style.display = 'block';
    if (document.querySelector('.weekdays')) {
        document.querySelector('.weekdays').style.display = 'grid';
    }
    if (document.getElementById('prev-month')) {
        document.getElementById('prev-month').style.display = 'block';
    }
    if (document.getElementById('next-month')) {
        document.getElementById('next-month').style.display = 'block';
    }
    
    // Forzar la visualización inicial del calendario
    const currentDate = new Date();
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();
    
    monthTitle.textContent = `${currentDate.toLocaleString('es-ES', { month: 'long' })} ${currentYear}`;
    
    // Crear una cuadrícula básica del calendario
    calendarGrid.innerHTML = '';
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    // Agregar días vacíos para alinear el primer día
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'date-cell other-month';
        calendarGrid.appendChild(emptyCell);
    }
    
    // Agregar los días del mes
    for (let day = 1; day <= daysInMonth; day++) {
        const dateCell = document.createElement('div');
        dateCell.className = 'date-cell';
        dateCell.setAttribute('data-day', day);
        dateCell.innerHTML = `
            <div class="date-number">${day}</div>
            <div style="font-size: 0.7rem; color: #666; margin-top: 5px;">Click para detalles</div>
        `;
        
        // Agregar evento click
        dateCell.addEventListener('click', function() {
            console.log(`📅 Día seleccionado: ${day}`);
            mostrarNotificacion(`Día ${day} seleccionado. Funcionalidad completa en desarrollo.`, 'info');
        });
        
        calendarGrid.appendChild(dateCell);
    }
    
    console.log('📅 Calendario básico renderizado desde dashboard');
    
    // Asegurar que los filtros también sean visibles
    const filtros = document.querySelector('.filtros');
    if (filtros) {
        filtros.style.display = 'block';
    }
    const calendarContainer = document.querySelector('.calendar-container');
    if (calendarContainer) {
        calendarContainer.style.display = 'block';
    }
}

// FUNCIÓN: Limpiar sección anterior al cambiar
function limpiarSeccionAnterior(seccionAnterior) {
    if (seccionAnterior === 'calendario') {
        // Limpiar cualquier estado del calendario si es necesario
        console.log('🧹 Limpiando sección de calendario');
    } else if (seccionAnterior === 'mis-turnos') {
        // Limpiar contenedor de turnos
        const container = document.getElementById('turnos-reservados-container');
        if (container) {
            container.innerHTML = '';
        }
    } else if (seccionAnterior === 'perfil') {
        // Limpiar contenedor de perfil
        const container = document.getElementById('perfil-container');
        if (container) {
            container.innerHTML = '';
        }
    }
}

    // FUNCIÓN: Cargar perfil del usuario para el sidebar
    async function cargarPerfilUsuario() {
        try {
            console.log('🔄 Cargando perfil del usuario...');
            const response = await fetch('../controles/perfil.php');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📊 Datos del perfil recibidos:', data);
            
            if (data.success && data.datos) {
                const usuario = data.datos;
                userName.textContent = `${usuario.nombre} ${usuario.apellido}`;
                userRole.textContent = usuario.rol.charAt(0).toUpperCase() + usuario.rol.slice(1);
                userAvatar.textContent = (usuario.nombre.charAt(0) + usuario.apellido.charAt(0)).toUpperCase();
                actualizarSubtitulo('calendario');
                console.log('✅ Perfil cargado correctamente');
            } else {
                console.error('❌ Error en la respuesta:', data.error);
                mostrarError('Error al cargar perfil: ' + (data.error || 'Desconocido'));
            }
        } catch (error) {
            console.error('❌ Error al cargar perfil:', error);
            mostrarError('Error de conexión: ' + error.message);
        }
    }

    // FUNCIÓN: Cargar perfil completo para la sección perfil
    async function cargarPerfilCompleto() {
        const container = document.getElementById('perfil-container');
        container.innerHTML = '<div class="loading">Cargando información del perfil...</div>';
        
        try {
            const response = await fetch('../controles/perfil.php');
            const data = await response.json();
            
            if (data.success && data.datos) {
                const usuario = data.datos;
                
                let obraSocialHTML = '';
                if (usuario.rol === 'paciente') {
                    obraSocialHTML = `
                        <div class="info-group">
                            <div class="info-label">Obra Social</div>
                            <div class="info-value">${escapeHtml(usuario.obra_social || 'No especificada')}</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Número de Afiliado</div>
                            <div class="info-value">${escapeHtml(usuario.num_afiliado || 'No especificado')}</div>
                        </div>
                    `;
                }
                
                container.innerHTML = `
                    <div class="profile-container">
                        <div class="profile-info">
                            <div class="info-group">
                                <div class="info-label">Nombre</div>
                                <div class="info-value">${escapeHtml(usuario.nombre)}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Apellido</div>
                                <div class="info-value">${escapeHtml(usuario.apellido)}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Email</div>
                                <div class="info-value">${escapeHtml(usuario.email)}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">DNI</div>
                                <div class="info-value">${escapeHtml(usuario.dni)}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value">${escapeHtml(usuario.telefono || 'No especificado')}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Rol</div>
                                <div class="info-value">${escapeHtml(usuario.rol.charAt(0).toUpperCase() + usuario.rol.slice(1))}</div>
                            </div>
                            ${obraSocialHTML}
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<div class="error">Error al cargar el perfil: ' + (data.error || 'Desconocido') + '</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            container.innerHTML = '<div class="error">Error de conexión: ' + error.message + '</div>';
        }
    }

    // FUNCIÓN: Cargar turnos reservados
    async function cargarMisTurnos() {
        const container = document.getElementById('turnos-reservados-container');
        container.innerHTML = '<div class="loading">Cargando tus turnos...</div>';
        
        try {
            const response = await fetch('../controles/mis_turnos.php');
            const data = await response.json();
            
            if (data.success) {
                const turnos = data.turnos;
                
                if (turnos.length === 0) {
                    container.innerHTML = `
                        <div class="turnos-list-container">
                            <div style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                No tienes turnos reservados
                            </div>
                        </div>
                    `;
                    return;
                }
                
                let html = '<div class="turnos-list-container">';
                turnos.forEach(turno => {
                    const fecha = new Date(turno.fecha + 'T' + turno.hora_inicio);
                    const fechaFormateada = fecha.toLocaleDateString('es-ES');
                    const horaFormateada = fecha.toLocaleTimeString('es-ES', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    // Verificar si el turno se puede cancelar (solo turnos reservados futuros)
                    const ahora = new Date();
                    const esFuturo = fecha > ahora;
                    const puedeCancelar = turno.estado === 'reservado' && esFuturo;
                    
                    html += `
                        <div class="turno-card">
                            <div class="turno-info">
                                <h4>Dr. ${escapeHtml(turno.medico_apellido || '')}, ${escapeHtml(turno.medico_nombre || '')}</h4>
                                <div class="turno-fecha">${fechaFormateada} - ${horaFormateada}</div>
                                <div class="turno-estado estado-${turno.estado}">
                                    ${turno.estado.charAt(0).toUpperCase() + turno.estado.slice(1)}
                                </div>
                            </div>
                            <div class="turno-acciones">
                                ${puedeCancelar ? 
                                    `<button class="btn-cancelar" data-id="${turno.id}">Cancelar Turno</button>` : 
                                    '<span class="text-muted">No cancelable</span>'
                                }
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
                
                // Agregar event listeners a los botones de cancelar
                agregarEventListenersCancelar();
            } else {
                container.innerHTML = '<div class="error">Error al cargar los turnos: ' + (data.error || 'Desconocido') + '</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            container.innerHTML = '<div class="error">Error de conexión: ' + error.message + '</div>';
        }
    }

    // FUNCIÓN: Agregar event listeners para cancelar turnos
    function agregarEventListenersCancelar() {
        const botonesCancelar = document.querySelectorAll('.btn-cancelar');
        
        botonesCancelar.forEach(boton => {
            boton.addEventListener('click', async (e) => {
                const idTurno = e.target.getAttribute('data-id');
                
                if (confirm('¿Estás seguro de que quieres cancelar este turno?')) {
                    try {
                        // Mostrar loading
                        e.target.textContent = 'Cancelando...';
                        e.target.disabled = true;
                        
                        const formData = new FormData();
                        formData.append('accion', 'cancelar');
                        formData.append('id_turno', idTurno);
                        
                        const response = await fetch('../controles/mis_turnos.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const resultado = await response.json();
                        
                        if (resultado.success) {
                            mostrarNotificacion(resultado.message || 'Turno cancelado exitosamente', 'success');
                            await cargarMisTurnos(); // Recargar la lista
                        } else {
                            mostrarNotificacion(resultado.error || 'Error al cancelar el turno', 'error');
                            e.target.textContent = 'Cancelar Turno';
                            e.target.disabled = false;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        mostrarNotificacion('Error de conexión', 'error');
                        e.target.textContent = 'Cancelar Turno';
                        e.target.disabled = false;
                    }
                }
            });
        });
    }

    // FUNCIÓN: Actualizar subtítulo según sección
    function actualizarSubtitulo(seccion) {
        const subtitulos = {
            'calendario': 'Sistema de Gestión de Turnos Médicos',
            'mis-turnos': 'Gestiona tus turnos médicos programados',
            'perfil': 'Información personal de tu cuenta'
        };
        headerSubtitle.textContent = subtitulos[seccion] || 'Sistema de Gestión de Turnos Médicos';
    }

    // FUNCIÓN: Mostrar notificaciones
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion notificacion-${tipo}`;
        notificacion.textContent = mensaje;
        
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        if (tipo === 'success') {
            notificacion.style.background = '#27ae60';
        } else if (tipo === 'error') {
            notificacion.style.background = '#e74c3c';
        } else {
            notificacion.style.background = '#3498db';
        }
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }

    // FUNCIÓN: Mostrar errores
    function mostrarError(mensaje) {
        console.error(mensaje);
        // También podrías mostrar una notificación
        mostrarNotificacion(mensaje, 'error');
    }

    // FUNCIÓN: Escapar HTML para seguridad
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // INICIALIZAR CALENDARIO AL CARGAR LA PÁGINA
    console.log('🚀 Iniciando dashboard - forzando inicialización del calendario...');
    
    // Esperar a que la página se cargue completamente
    setTimeout(() => {
        console.log('🎯 Ejecutando inicialización del calendario...');
        if (document.getElementById('calendario').classList.contains('active')) {
            inicializarCalendario();
        }
    }, 800);
}); // FIN DEL DOMContentLoaded