document.addEventListener('DOMContentLoaded', () => {
  const apiUrl = '../controles/turnos.php';
  
  // DOM - CORREGIDO para dashboard.html
  const calendarGrid = document.getElementById('calendar-grid');
  const monthTitle = document.getElementById('current-month');
  const prevMonthBtn = document.getElementById('prev-month');
  const nextMonthBtn = document.getElementById('next-month');
  const medicoSelect = document.getElementById('medico');
  const especialidadSelect = document.getElementById('especialidad');
  const monthPicker = document.getElementById('month-picker');
  const buscarBtn = document.getElementById('buscar');
  const turnosBox = document.getElementById('turnos-box');
  const turnosDate = document.getElementById('turnos-date');
  const turnosList = document.getElementById('turnos-list');
  const reservarBtn = document.getElementById('reservar-btn');
  const cancelBtn = document.getElementById('cancel-btn');
  const closeTurnosBtn = document.getElementById('close-turnos');
  const msgDiv = document.getElementById('message');

  // Contenedores para mostrar/ocultar
  const filtrosContainer = document.querySelector('.filtros');
  const calendarioContainer = document.querySelector('.calendar-container');

  let current = new Date();
  let currentMonth = current.getMonth();
  let currentYear = current.getFullYear();
  let availabilityMap = {};
  let selectedDate = null;
  let selectedTurnoId = null;
  let filtrosAplicados = false;
  let filtroMedico = null;
  let filtroEspecialidad = null;

  // Inicializar controles y calendario
  init();

  async function init() {
    monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
    await loadEspecialidades(); // Solo cargar especialidades al inicio
    updateMonthTitle();

    // Ocultar todo inicialmente excepto la especialidad
    ocultarTodo();
    setupListeners();
  }

  // Función de inicialización que se llamará desde dashboard.js
function inicializarCalendario() {
    console.log('🎯 Inicializando calendario completo...');
    
    // Verificar que todos los elementos necesarios existan
    const elementosRequeridos = [
        'calendar-grid', 'current-month', 'prev-month', 'next-month',
        'medico', 'especialidad', 'month-picker', 'buscar',
        'reset-filtros', 'turnos-box', 'close-turnos', 'turnos-list',
        'reservar-btn', 'cancel-btn', 'message', 'msg-clear'
    ];
    
    let todosElementosExisten = true;
    
    elementosRequeridos.forEach(id => {
        const elemento = document.getElementById(id);
        if (!elemento) {
            console.error(`❌ Elemento no encontrado: ${id}`);
            todosElementosExisten = false;
        } else {
            console.log(`✅ Elemento encontrado: ${id}`);
        }
    });
    
    if (!todosElementosExisten) {
        console.error('🚨 Faltan elementos críticos para el calendario');
        mostrarMensaje('Error: No se pudo cargar el calendario correctamente', 'error');
        return;
    }
    
    // Inicializar el calendario
    inicializar();
    cargarFiltros();
    
    console.log('✅ Calendario inicializado correctamente');
}

// Función para mostrar mensajes
function mostrarMensaje(mensaje, tipo = 'info') {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.textContent = mensaje;
        messageDiv.style.color = tipo === 'error' ? '#e74c3c' : 
                               tipo === 'success' ? '#27ae60' : '#3498db';
        
        const clearBtn = document.getElementById('msg-clear');
        if (clearBtn) {
            clearBtn.style.display = 'inline-block';
        }
    }
    console.log(`📢 ${tipo.toUpperCase()}: ${mensaje}`);
}

  function ocultarTodo() {
    // Ocultar calendario y controles
    calendarioContainer.style.display = 'none';
    calendarGrid.style.display = 'none';
    document.querySelector('.weekdays').style.display = 'none';
    monthTitle.style.display = 'none';
    prevMonthBtn.style.display = 'none';
    nextMonthBtn.style.display = 'none';
    
    // Ocultar select de médico y mes inicialmente
    medicoSelect.style.display = 'none';
    monthPicker.style.display = 'none';
    buscarBtn.style.display = 'none';
    
    // Ocultar etiquetas relacionadas
    document.querySelectorAll('label').forEach(label => {
      if (label.htmlFor === 'medico' || label.htmlFor === 'month-picker') {
        label.style.display = 'none';
      }
    });
    
    // Ocultar mensajes
    msgDiv.style.display = 'none';
    document.getElementById('msg-clear').style.display = 'none';
    
    console.log('🔧 Interfaz inicial: Solo especialidad visible');
  }

  function mostrarMedicos() {
    // Mostrar select de médico
    medicoSelect.style.display = 'block';
    document.querySelector('label[for="medico"]').style.display = 'block';
    
    console.log('✅ Select de médico ahora visible');
  }

  function mostrarMesYBuscar() {
    // Mostrar mes y botón buscar
    monthPicker.style.display = 'block';
    buscarBtn.style.display = 'block';
    document.querySelector('label[for="month-picker"]').style.display = 'block';
    
    console.log('✅ Select de mes y botón buscar ahora visibles');
  }

  function mostrarCalendario() {
    // Mostrar todo el calendario
    calendarioContainer.style.display = 'block';
    calendarGrid.style.display = 'grid';
    document.querySelector('.weekdays').style.display = 'grid';
    monthTitle.style.display = 'block';
    prevMonthBtn.style.display = 'block';
    nextMonthBtn.style.display = 'block';
    
    console.log('✅ Calendario completo ahora visible');
  }

  function aplicarFiltrosYMostrarCalendarios() {
    // Validar que se haya seleccionado especialidad
    if (!especialidadSelect.value) {
      console.log('❌ Debe seleccionar una especialidad primero');
      return;
    }

    filtroMedico = medicoSelect.value ? parseInt(medicoSelect.value) : null;
    filtroEspecialidad = especialidadSelect.value ? parseInt(especialidadSelect.value) : null;
    filtrosAplicados = true;
    
    availabilityMap = {};
    hideTurnosBox();
    
    console.log('🎯 FILTROS APLICADOS:', {
      especialidad: filtroEspecialidad,
      medico: filtroMedico
    });
    
    // Mostrar calendario completo
    mostrarCalendario();
    loadMonthAvailabilityAndRender();
  }

  function setupListeners() {
    prevMonthBtn.addEventListener('click', async () => {
      if (prevMonthBtn.disabled) return;
      navigateMonth(-1);
    });
    
    nextMonthBtn.addEventListener('click', async () => {
      navigateMonth(1);
    });
    
    buscarBtn.textContent = 'Aplicar Filtros';
    buscarBtn.addEventListener('click', () => {
      const mp = monthPicker.value;
      if (mp) {
        const [y,m] = mp.split('-').map(Number);
        currentYear = y; 
        currentMonth = m - 1;
        updateMonthTitle();
      }
      aplicarFiltrosYMostrarCalendarios();
    });
    
    closeTurnosBtn.addEventListener('click', hideTurnosBox);
    cancelBtn.addEventListener('click', hideTurnosBox);

    // Evento: Cuando cambia la especialidad, cargar médicos de esa especialidad
    especialidadSelect.addEventListener('change', async () => {
      const idEspecialidad = especialidadSelect.value;
      
      if (idEspecialidad) {
        console.log('🔄 Cargando médicos para especialidad:', idEspecialidad);
        await loadMedicosPorEspecialidad(parseInt(idEspecialidad));
        
        // Mostrar select de médico
        mostrarMedicos();
        
        // Resetear filtros aplicados cuando cambia la especialidad
        if (filtrosAplicados) {
          filtrosAplicados = false;
          ocultarTodo();
          mostrarMedicos();
        }
      } else {
        // Si se deselecciona especialidad, volver al estado inicial
        medicoSelect.innerHTML = '<option value="">Seleccione médico</option>';
        medicoSelect.disabled = true;
        medicoSelect.style.display = 'none';
        document.querySelector('label[for="medico"]').style.display = 'none';
        
        // Ocultar mes y buscar
        monthPicker.style.display = 'none';
        buscarBtn.style.display = 'none';
        document.querySelector('label[for="month-picker"]').style.display = 'none';
        
        // Ocultar calendario
        calendarioContainer.style.display = 'none';
        
        if (filtrosAplicados) {
          filtrosAplicados = false;
          ocultarTodo();
        }
      }
    });
    
    // Evento: Cuando cambia el médico, mostrar mes y buscar
    medicoSelect.addEventListener('change', () => {
      if (medicoSelect.value) {
        // Mostrar mes y botón buscar cuando se selecciona un médico
        mostrarMesYBuscar();
      } else {
        // Si se selecciona "Todos los médicos", igual mostrar mes y buscar
        mostrarMesYBuscar();
      }
      
      // Resetear filtros aplicados cuando cambia el médico
      if (filtrosAplicados) {
        filtrosAplicados = false;
        calendarioContainer.style.display = 'none';
      }
    });
    
    reservarBtn.addEventListener('click', async () => { 
      await reservarSeleccion(); 
    });
    
    document.getElementById('reset-filtros').addEventListener('click', resetearFiltros);
  }

  function navigateMonth(delta) {
    currentMonth += delta;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
    updateMonthTitle();
    
    if (filtrosAplicados) {
      loadMonthAvailabilityAndRender();
    } else {
      renderCalendar();
    }
  }

  function updateMonthTitle() {
    const monthNames = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    const today = new Date();
    const currentMonthStart = new Date(currentYear, currentMonth, 1);
    prevMonthBtn.disabled = currentMonthStart <= new Date(today.getFullYear(), today.getMonth(), 1);
  }

  // Nueva función: Cargar solo especialidades al inicio
  async function loadEspecialidades() {
    try {
      const resE = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'especialidades' })
      });
      
      const esp = await resE.json();
      especialidadSelect.innerHTML = `<option value="">Seleccione especialidad</option>`;
      
      if (Array.isArray(esp)) {
        esp.forEach(e => {
          especialidadSelect.innerHTML += `<option value="${e.id}">${escapeHtml(e.nombre)}</option>`;
        });
      }
      
      // Inicializar select de médicos como deshabilitado y oculto
      medicoSelect.innerHTML = '<option value="">Seleccione médico</option>';
      medicoSelect.disabled = true;
      
      console.log('✅ Especialidades cargadas:', esp.length);
    } catch (err) {
      console.error('❌ No se pudieron cargar especialidades:', err);
      especialidadSelect.innerHTML = `<option value="">Error al cargar</option>`;
    }
  }

  // Nueva función: Cargar médicos por especialidad
  async function loadMedicosPorEspecialidad(idEspecialidad) {
    try {
      const resM = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          accion: 'medicos',
          id_especialidad: idEspecialidad
        })
      });
      
      const medicos = await resM.json();
      medicoSelect.innerHTML = `<option value="">Todos los médicos</option>`;
      
      if (Array.isArray(medicos)) {
        medicos.forEach(m => {
          const idMedico = m.id_medico || m.id;
          const nombre = m.nombre || '';
          const apellido = m.apellido || '';
          
          medicoSelect.innerHTML += `<option value="${idMedico}">${escapeHtml(apellido)}, ${escapeHtml(nombre)}</option>`;
        });
        
        medicoSelect.disabled = false;
        console.log('✅ Médicos cargados para especialidad:', medicos.length);
      } else {
        medicoSelect.innerHTML = `<option value="">No hay médicos</option>`;
        medicoSelect.disabled = true;
        console.log('ℹ️ No hay médicos para esta especialidad');
      }
    } catch (err) {
      console.error('❌ No se pudieron cargar médicos:', err);
      medicoSelect.innerHTML = `<option value="">Error al cargar</option>`;
      medicoSelect.disabled = true;
    }
  }

  async function loadMonthAvailabilityAndRender() {
    availabilityMap = {};
    
    if (!filtrosAplicados) {
      renderCalendar();
      return;
    }
    
    console.log('🔄 Cargando disponibilidad...');
    
    const medicoParam = filtroMedico !== null ? parseInt(filtroMedico) : null;
    const especialidadParam = filtroEspecialidad !== null ? parseInt(filtroEspecialidad) : null;
    
    try {
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          accion: 'listar_mes',
          year: currentYear,
          month: currentMonth + 1,
          id_medico: medicoParam,
          id_especialidad: especialidadParam
        })
      });

      if (!res.ok) throw new Error('Respuesta no OK');
      
      const json = await res.json();
      
      if (!Array.isArray(json) && typeof json === 'object') {
        availabilityMap = json;
      } else if (Array.isArray(json)) {
        json.forEach(item => {
          if (item.fecha) availabilityMap[item.fecha] = item.count ?? 0;
        });
      }

      renderCalendar();
      console.log('✅ Calendario actualizado con filtros aplicados');
    } catch (err) {
      console.error('❌ Error al cargar disponibilidad:', err);
      availabilityMap = {};
      renderCalendar();
    }
  }

  function renderCalendar() {
    calendarGrid.innerHTML = '';
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    const daysInMonth = lastDay.getDate();
    const firstDayIndex = firstDay.getDay();
    const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

    // Días del mes anterior
    for (let i = firstDayIndex - 1; i >= 0; i--) {
      const day = prevMonthLastDay - i;
      const date = new Date(currentYear, currentMonth - 1, day);
      const dateId = formatDateId(date);
      const cell = createDateCell(day, dateId, true, true);
      calendarGrid.appendChild(cell);
    }

    const today = new Date();
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(currentYear, currentMonth, d);
      const dateId = formatDateId(date);
      const isToday = (date.toDateString() === today.toDateString());
      const isDisabled = date < new Date(today.getFullYear(), today.getMonth(), today.getDate());
      const cell = createDateCell(d, dateId, false, isDisabled, isToday);

      const count = availabilityMap[dateId];
      if (typeof count !== 'undefined' && Number(count) > 0) {
        addSelectionIndicator(cell, Number(count));
      }
      calendarGrid.appendChild(cell);
    }

    // Rellenar con días del próximo mes
    const totalCells = 42;
    const currentCells = calendarGrid.children.length;
    const remaining = totalCells - currentCells;
    for (let i = 1; i <= remaining; i++) {
      const date = new Date(currentYear, currentMonth + 1, i);
      const dateId = formatDateId(date);
      const cell = createDateCell(i, dateId, true, true);
      calendarGrid.appendChild(cell);
    }
  }

  function createDateCell(day, dateId, otherMonth = false, disabled = false, isToday = false) {
    const div = document.createElement('div');
    div.className = 'date-cell';
    if (otherMonth) div.classList.add('other-month');
    if (disabled) div.classList.add('disabled');
    if (isToday) div.classList.add('today');
    
    const count = availabilityMap[dateId];
    const sinDisponibilidad = filtrosAplicados && 
                             (typeof count === 'undefined' || Number(count) === 0);
    
    if (sinDisponibilidad) {
      div.classList.add('no-availability');
    }
    
    div.id = `cell-${dateId}`;
    div.dataset.date = dateId;

    const number = document.createElement('div');
    number.className = 'date-number';
    number.textContent = day;
    div.appendChild(number);

    if (typeof count !== 'undefined' && Number(count) > 0) {
      addSelectionIndicator(div, Number(count));
    }

    if (!disabled && !otherMonth && !sinDisponibilidad) {
      div.addEventListener('click', () => onDateClick(dateId, div));
    } else if (sinDisponibilidad) {
      div.title = 'Sin turnos disponibles con los filtros actuales';
      div.style.cursor = 'not-allowed';
    }
    
    return div;
  }

  function resetearFiltros() {
    especialidadSelect.value = '';
    medicoSelect.innerHTML = '<option value="">Seleccione médico</option>';
    medicoSelect.disabled = true;
    monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
    filtrosAplicados = false;
    filtroMedico = null;
    filtroEspecialidad = null;
    availabilityMap = {};
    
    // Volver al estado inicial
    ocultarTodo();
    hideTurnosBox();
    renderCalendar();
    
    console.log('🔄 Filtros reseteados - Volviendo al estado inicial');
  }

  async function onDateClick(dateId, cellElement) {
    selectedDate = dateId;
    
    // Remover selección anterior
    document.querySelectorAll('.date-cell.selected').forEach(n => n.classList.remove('selected'));
    cellElement.classList.add('selected');

    showTurnosLoading(true);
    try {
      const id_medico = filtroMedico;
      const id_especialidad = filtroEspecialidad;
      
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          accion: 'listar', 
          fecha: dateId, 
          id_medico: id_medico,
          id_especialidad: id_especialidad
        })
      });
      
      const turnos = await res.json();
      
      renderTurnosList(turnos);
      positionTurnosBox(cellElement);
      showTurnosLoading(false);
    } catch (err) {
      console.error('❌ Error al obtener turnos:', err);
      renderTurnosList([]);
      positionTurnosBox(cellElement);
      showTurnosLoading(false);
    }
  }

  function renderTurnosList(turnos) {
    turnosList.innerHTML = '';
    selectedTurnoId = null;
    reservarBtn.disabled = true;

    if (!Array.isArray(turnos) || turnos.length === 0) {
      let mensaje = 'No hay turnos para esta fecha';
      if (filtroMedico || filtroEspecialidad) {
        mensaje += ' con los filtros aplicados';
      }
      turnosList.innerHTML = `<p>${mensaje}</p>`;
      turnosBox.style.display = 'block';
      turnosBox.setAttribute('aria-hidden', 'false');
      return;
    }

    turnos.forEach(t => {
      const item = document.createElement('div');
      item.className = 'turno-item';
      if (t.estado !== 'disponible') item.classList.add('disabled');

      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'selected_turno';
      radio.value = t.id;
      radio.disabled = (t.estado !== 'disponible');
      radio.id = `t-${t.id}`;
      radio.addEventListener('change', () => {
        selectedTurnoId = parseInt(radio.value, 10);
        reservarBtn.disabled = !selectedTurnoId;
      });

      const hora = document.createElement('div');
      hora.className = 'turno-hora';
      hora.textContent = t.hora_inicio;

      const nombre = document.createElement('div');
      nombre.style.marginLeft = '8px';
      nombre.textContent = `${t.medico_apellido || ''} ${t.medico_nombre || ''}`;
      
      const estado = document.createElement('div');
      estado.style.marginLeft = 'auto';
      estado.style.fontSize = '0.8em';
      estado.style.color = t.estado === 'disponible' ? '#27ae60' : '#e74c3c';
      estado.textContent = t.estado === 'disponible' ? 'Disponible' : 'No disponible';
      
      item.appendChild(radio);
      item.appendChild(hora);
      item.appendChild(nombre);
      item.appendChild(estado);
      turnosList.appendChild(item);
    });

    turnosBox.style.display = 'block';
    turnosBox.setAttribute('aria-hidden', 'false');
  }

  function positionTurnosBox(cellElement) {
    const rect = cellElement.getBoundingClientRect();
    
    // USAR el contenedor correcto del dashboard
    const containerRect = document.querySelector('.calendar-container').getBoundingClientRect();

    let left = rect.right + 10;
    let top = rect.top;

    // Ajustar posición si se sale por la derecha
    if (left + 340 > containerRect.right) {
      left = rect.left - 340;
    }
    
    // Ajustar posición si se sale por abajo
    if (top + turnosBox.offsetHeight > containerRect.bottom) {
      top = containerRect.bottom - turnosBox.offsetHeight - 10;
    }

    turnosBox.style.left = `${left - containerRect.left}px`;
    turnosBox.style.top = `${Math.max(top - containerRect.top, 10)}px`;
    
    // focus for accessibility
    if (turnosBox.focus) {
      turnosBox.focus();
    }
  }

  function verificarSesion() {
    return true; // La sesión se verifica en el backend
  }

  async function reservarSeleccion() {
    if (!verificarSesion()) {
      console.log('❌ Debe iniciar sesión para reservar turnos');
      return;
    }
    
    if (!selectedTurnoId) { 
      console.log('❌ Seleccione un turno disponible primero');
      return; 
    }

    reservarBtn.disabled = true;
    console.log('🔄 Intentando reservar turno...');

    try {
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'reservar', id_turno: selectedTurnoId })
      });
      
      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (parseError) {
        console.error('❌ Respuesta no es JSON válido:', text);
        return;
      }
      
      if (json.success || json.exito) {
        console.log('✅ Turno reservado exitosamente');
        hideTurnosBox();
        await loadMonthAvailabilityAndRender();
      } else {
        console.error('❌ No se pudo reservar:', json.error || json.message);
      }
    } catch (err) {
      console.error('❌ Error al reservar:', err);
    } finally {
      reservarBtn.disabled = false;
    }
  }

  function hideTurnosBox() {
    turnosBox.style.display = 'none';
    turnosBox.setAttribute('aria-hidden', 'true');
    
    if (selectedDate) {
      const el = document.getElementById(`cell-${selectedDate}`);
      if (el) el.classList.remove('selected');
    }
    selectedDate = null;
    selectedTurnoId = null;
    reservarBtn.disabled = true;
  }

  function showTurnosLoading(loading) {
    if (loading) {
      turnosList.innerHTML = '<p>Cargando turnos...</p>';
      turnosBox.style.display = 'block';
    }
  }

  function formatDateId(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
  }

  function addSelectionIndicator(cell, count) {
    let existing = cell.querySelector('.selection-indicator');
    if (existing) {
      existing.textContent = count;
    } else {
      const el = document.createElement('div');
      el.className = 'selection-indicator';
      el.textContent = count;
      cell.appendChild(el);
    }
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
});