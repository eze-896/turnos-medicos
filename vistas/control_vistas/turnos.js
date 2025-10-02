document.addEventListener('DOMContentLoaded', () => {
  const apiUrl = '../controles/turnos.php';
  // DOM
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

  // ID de paciente fijo para TESTING (cámbialo por $_SESSION['id_paciente'] en producción)
// Asegúrate de que exista un paciente con ID=1 en tu BD (tabla 'paciente')
const TEST_PACIENTE_ID = 1;

  let current = new Date();
  let currentMonth = current.getMonth();
  let currentYear = current.getFullYear();
  let availabilityMap = {}; // { 'YYYY-MM-DD': count }
  let selectedDate = null;
  let selectedTurnoId = null;
  let filtrosAplicados = false;
  let filtroMedico = null;
  let filtroEspecialidad = null;

  // Inicializar controles y calendario
  init();

  async function init() {
    monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
    await loadFilters();
    updateMonthTitle();

    // Mostrar calendario solo después de cargar filtros
    mostrarInterfazFiltros();
    setupListeners();
  }

  function mostrarInterfazFiltros() {
  // Ocultar calendario inicialmente
  calendarGrid.style.display = 'none';
  document.querySelector('.weekdays').style.display = 'none';
  monthTitle.style.display = 'none';
  prevMonthBtn.style.display = 'none';
  nextMonthBtn.style.display = 'none';
  
  // Mostrar mensaje instructivo
  if (!filtrosAplicados) {
    showMessage('Seleccione filtros y haga clic en "Aplicar Filtros" para ver el calendario', false);
  }
}

function aplicarFiltrosYMostrarCalendarios() {
  // CORREGIR: Asegurar que sean null cuando estén vacíos
  filtroMedico = medicoSelect.value ? parseInt(medicoSelect.value) : null;
  filtroEspecialidad = especialidadSelect.value ? parseInt(especialidadSelect.value) : null;
  filtrosAplicados = true;
  
  // LIMPIAR datos anteriores antes de cargar nuevos
  availabilityMap = {};
  hideTurnosBox(); // Ocultar cualquier turno flotante abierto
  
  console.log('🎯 FILTROS CORREGIDOS:', {
    medico: filtroMedico, 
    especialidad: filtroEspecialidad,
    tipoMedico: typeof filtroMedico,
    tipoEspecialidad: typeof filtroEspecialidad
  });
  
  // Mostrar elementos del calendario
  calendarGrid.style.display = 'grid';
  document.querySelector('.weekdays').style.display = 'grid';
  monthTitle.style.display = 'block';
  prevMonthBtn.style.display = 'block';
  nextMonthBtn.style.display = 'block';
  
  // Cargar y renderizar con filtros
  loadMonthAvailabilityAndRender();
  showMessage('Filtros aplicados. Los días sin disponibilidad aparecen en rojo.', false);
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

    medicoSelect.addEventListener('change', () => {
    if (filtrosAplicados) {
      showMessage('Haga clic en "Aplicar Filtros" para actualizar', false);
    }
  });
    especialidadSelect.addEventListener('change', () => {
    if (filtrosAplicados) {
      showMessage('Haga clic en "Aplicar Filtros" para actualizar', false);
    }
  });
    reservarBtn.addEventListener('click', async () => { await reservarSeleccion(); });
    document.getElementById('reset-filtros').addEventListener('click', resetearFiltros);
  }

  function navigateMonth(delta) {
  currentMonth += delta;
  if (currentMonth < 0) { currentMonth = 11; currentYear--; }
  if (currentMonth > 11) { currentMonth = 0; currentYear++; }
  monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
  updateMonthTitle();
  
  // Solo cargar disponibilidad si los filtros están aplicados
  if (filtrosAplicados) {
    loadMonthAvailabilityAndRender();
  } else {
    renderCalendar(); // Renderizar calendario vacío
  }
}

  function updateMonthTitle() {
    const monthNames = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    monthTitle.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    // Prev button disabled for months earlier than current month
    const today = new Date();
    const currentMonthStart = new Date(currentYear, currentMonth, 1);
    prevMonthBtn.disabled = currentMonthStart <= new Date(today.getFullYear(), today.getMonth(), 1);
  }

async function loadFilters() {
  // cargar medicos usando turnos.php
  try {
    const resM = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accion: 'medicos' })
    });
    
    const medicos = await resM.json();
    medicoSelect.innerHTML = `<option value="">Todos</option>`;
    
    if (Array.isArray(medicos)) {
      console.log('👥 MÉDICOS CARGADOS (ESTRUCTURA):', medicos); // ← Ver la estructura real
      
      medicos.forEach(m => {
        // CORREGIR: Usar id_medico en lugar de id
        const idMedico = m.id_medico || m.id; // Usa id_medico si existe, sino id
        const nombre = m.nombre || '';
        const apellido = m.apellido || '';
        
        console.log(`Médico: ID=${idMedico}, Nombre=${nombre}, Apellido=${apellido}`); // ← Debug
        
        medicoSelect.innerHTML += `<option value="${idMedico}">${escapeHtml(apellido)}, ${escapeHtml(nombre)}</option>`;
      });
    }
  } catch (err) {
    console.error('No se pudieron cargar médicos:', err);
    medicoSelect.innerHTML = `<option value="">Todos</option>`;
  }

  // cargar especialidades usando turnos.php
  try {
    const resE = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accion: 'especialidades' })
    });
    
    const esp = await resE.json();
    especialidadSelect.innerHTML = `<option value="">Todas</option>`;
    
    if (Array.isArray(esp)) {
      esp.forEach(e => {
        especialidadSelect.innerHTML += `<option value="${e.id}">${escapeHtml(e.nombre)}</option>`;
      });
    }
  } catch (err) {
    console.error('No se pudieron cargar especialidades:', err);
    especialidadSelect.innerHTML = `<option value="">Todas</option>`;
  }
}
  // Cargar disponibilidad del mes completo desde el backend
  async function loadMonthAvailabilityAndRender() {
  availabilityMap = {};
  
  if (!filtrosAplicados) {
    renderCalendar();
    return;
  }
  
  showMessage('Cargando disponibilidad...', false);
  
  // CORREGIR: Asegurar tipos correctos
  const medicoParam = filtroMedico !== null ? parseInt(filtroMedico) : null;
  const especialidadParam = filtroEspecialidad !== null ? parseInt(filtroEspecialidad) : null;
  
  console.log('🔄 Enviando al backend con tipos correctos:', {
    medico: medicoParam, 
    especialidad: especialidadParam,
    tipoMedico: typeof medicoParam,
    tipoEspecialidad: typeof especialidadParam
  });
  
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
    console.log('📊 Datos recibidos del backend:', json);
    
    if (!json) {
      availabilityMap = {};
      console.log('⚠️ JSON vacío o nulo');
    } else if (!Array.isArray(json) && typeof json === 'object') {
      availabilityMap = json;
      console.log('✅ Mapa de disponibilidad cargado:', availabilityMap);
    } else if (Array.isArray(json)) {
      json.forEach(item => {
        if (item.fecha) availabilityMap[item.fecha] = item.count ?? 0;
      });
      console.log('✅ Array de disponibilidad procesado:', availabilityMap);
    }

    // Contar días con/sin disponibilidad
    const diasConDisponibilidad = Object.values(availabilityMap).filter(count => count > 0).length;
    const diasSinDisponibilidad = Object.values(availabilityMap).filter(count => count === 0).length;
    console.log(`📅 Resumen: ${diasConDisponibilidad} días con disponibilidad, ${diasSinDisponibilidad} días sin disponibilidad`);

    renderCalendar();
    showMessage('Filtros aplicados. Los días sin disponibilidad aparecen en rojo.', false);
  } catch (err) {
    console.error('❌ Error al cargar disponibilidad:', err);
    availabilityMap = {};
    renderCalendar();
    showMessage('Error al cargar disponibilidad. Intente nuevamente.', true);
  }
}

  // Genera la cuadrícula del mes (matriz 6x7)
  function renderCalendar() {
    calendarGrid.innerHTML = '';
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    const daysInMonth = lastDay.getDate();
    const firstDayIndex = firstDay.getDay(); // 0..6
    const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

    // prev month days
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
      const isDisabled = date < new Date(today.getFullYear(), today.getMonth(), today.getDate()); // past
      const cell = createDateCell(d, dateId, false, isDisabled, isToday);

      // indicador de disponibilidad si lo conocemos
      const count = availabilityMap[dateId];
      if (typeof count !== 'undefined' && Number(count) > 0) {
        addSelectionIndicator(cell, Number(count));
      }
      calendarGrid.appendChild(cell);
    }

    // next month filler to fill 6*7 = 42 cells
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
  
  // VERIFICAR DISPONIBILIDAD CON FILTROS APLICADOS - CORREGIDO
  const count = availabilityMap[dateId];
  
  // CORREGIR: Marcar en rojo cuando los filtros están aplicados Y:
  // 1. La fecha NO está en el mapa (count === undefined) O
  // 2. La fecha está en el mapa pero con count === 0
  const sinDisponibilidad = filtrosAplicados && 
                           (typeof count === 'undefined' || Number(count) === 0);
  
  console.log(`📅 Fecha: ${dateId}, Count: ${count}, Filtros: ${filtrosAplicados}, SinDisponibilidad: ${sinDisponibilidad}`);
  
  if (sinDisponibilidad) {
    console.log(`🔴 Marcando ${dateId} como SIN DISPONIBILIDAD`);
    div.classList.add('no-availability');
  }
  
  div.id = `cell-${dateId}`;
  div.dataset.date = dateId;

  const number = document.createElement('div');
  number.className = 'date-number';
  number.textContent = day;
  div.appendChild(number);

  // Agregar indicador de disponibilidad solo si hay disponibilidad
  if (typeof count !== 'undefined' && Number(count) > 0) {
    console.log(`🟢 ${dateId} tiene ${count} turnos disponibles`);
    addSelectionIndicator(div, Number(count));
  }

  if (!disabled && !otherMonth && !sinDisponibilidad) {
    div.addEventListener('click', () => onDateClick(dateId, div));
  } else if (sinDisponibilidad) {
    // Agregar tooltip para explicar por qué está bloqueado
    div.title = 'Sin turnos disponibles con los filtros actuales';
    div.style.cursor = 'not-allowed';
  }
  
  return div;
}

function resetearFiltros() {
  medicoSelect.value = '';
  especialidadSelect.value = '';
  monthPicker.value = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}`;
  filtrosAplicados = false;
  filtroMedico = null;
  filtroEspecialidad = null;
  availabilityMap = {}; // LIMPIAR el mapa de disponibilidad
  
  // Ocultar calendario y mostrar interfaz de filtros
  mostrarInterfazFiltros();
  
  // Limpiar cualquier selección existente
  hideTurnosBox();
  
  // FORZAR re-render del calendario para quitar colores
  renderCalendar();
  
  showMessage('Filtros reseteados. Aplique nuevos filtros para ver el calendario.', false);
}

async function onDateClick(dateId, cellElement) {
  selectedDate = dateId;
  // marcar seleccionado visualmente
  [...document.querySelectorAll('.date-cell.selected')].forEach(n => n.classList.remove('selected'));
  cellElement.classList.add('selected');

  // solicitar listados de turnos para esa fecha - USAR FILTROS APLICADOS
  showTurnosLoading(true);
  try {
    // CORREGIR: Usar los filtros ya aplicados, no los valores actuales del select
    const id_medico = filtroMedico;
    const id_especialidad = filtroEspecialidad;
    
    console.log('🔍 Buscando turnos con filtros:', {
      fecha: dateId,
      medico: id_medico,
      especialidad: id_especialidad
    });
    
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
    console.log('📋 Turnos encontrados:', turnos);
    
    renderTurnosList(turnos);
    positionTurnosBox(cellElement);
    showTurnosLoading(false);
  } catch (err) {
    console.error('Error al obtener turnos:', err);
    renderTurnosList([]);
    positionTurnosBox(cellElement);
    showTurnosLoading(false);
    showMessage('Error al cargar turnos de la fecha seleccionada.', true);
  }
}

function renderTurnosList(turnos) {
  turnosList.innerHTML = '';
  selectedTurnoId = null;
  reservarBtn.disabled = true;

  if (!Array.isArray(turnos) || turnos.length === 0) {
    // MEJORAR mensaje para incluir información de filtros
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
    
    // Agregar información de estado
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
    const containerRect = document.querySelector('.container').getBoundingClientRect();

    let left = rect.right + 10;
    let top = rect.top;

    if (left + 340 > containerRect.right) left = rect.left - 340;
    if (top + turnosBox.offsetHeight > containerRect.bottom) top = containerRect.bottom - turnosBox.offsetHeight - 10;

    turnosBox.style.left = `${left - containerRect.left}px`;
    turnosBox.style.top = `${Math.max(top - containerRect.top, 10)}px`;
    // focus for accessibility
    turnosBox.focus?.();
  }

  async function reservarSeleccion() {
    if (!selectedTurnoId) { showMessage('Seleccione un turno disponible primero.', true); return; }

    reservarBtn.disabled = true;
    showMessage('Intentando reservar...', false);

    try {
      const res = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'reservar', id_turno: selectedTurnoId })
      });
      const json = await res.json();
      if (json.exito || json.success) {
        showMessage(json.mensaje || json.message || 'Turno reservado.', false);
        hideTurnosBox();
        await loadMonthAvailabilityAndRender(); // refrescar calendario
      } else {
        showMessage(json.error || json.message || 'No se pudo reservar.', true);
      }
    } catch (err) {
      console.error('Error al reservar:', err);
      showMessage('Error al reservar. Revise la consola.', true);
    } finally {
      reservarBtn.disabled = false;
    }
  }

  function hideTurnosBox() {
    turnosBox.style.display = 'none';
    turnosBox.setAttribute('aria-hidden', 'true');
    // quitar selección visual
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

  function showMessage(text, isError = false) {
    msgDiv.textContent = text || '';
    msgDiv.style.color = isError ? '#c0392b' : '#2c3e50';
    document.getElementById('msg-clear').style.display = text ? 'inline-block' : 'none';
  }

  // helper: formatea Date a YYYY-MM-DD
  function formatDateId(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
  }

  // Agrega esta función temporal para testear
async function testBackend() {
  console.log('🧪 TESTEANDO BACKEND...');
  
  try {
    const testData = {
      accion: 'listar_mes',
      year: currentYear,
      month: currentMonth + 1,
      id_medico: filtroMedico,
      id_especialidad: filtroEspecialidad
    };
    
    console.log('📤 Enviando al backend:', testData);
    
    const res = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(testData)
    });
    
    console.log('📥 Respuesta HTTP:', res.status, res.statusText);
    const data = await res.json();
    console.log('📊 Datos recibidos:', data);
    
    return data;
  } catch (error) {
    console.error('❌ Error en test:', error);
  }
}

  // indicador pequeño
  function addSelectionIndicator(cell, count) {
    let existing = cell.querySelector('.selection-indicator');
    if (existing) existing.textContent = count;
    else {
      const el = document.createElement('div');
      el.className = 'selection-indicator';
      el.textContent = count;
      cell.appendChild(el);
    }
  }

  // escape básico para insertar texto en DOM
  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }
});
