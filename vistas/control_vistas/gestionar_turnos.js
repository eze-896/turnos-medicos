$(document).ready(function () {
    // Cargar médicos en el select
    function cargarMedicos() {
        $.getJSON("turnos.php", { accion: "listar_medicos" }, function (data) {
            $("#select-medico").empty();
            data.forEach(m => {
                $("#select-medico").append(`<option value="${m.id}">${m.nombre} ${m.apellido}</option>`);
            });
            cargarCalendario();
        });
    }

    // Cargar calendario del médico seleccionado
    function cargarCalendario() {
        const medico_id = $("#select-medico").val();
        $.getJSON("turnos.php", { accion: "listar_turnos", medico_id }, function (data) {
            let html = "";

            // Cabecera días
            html += `<div class="header">Hora</div>`;
            ["Lun","Mar","Mié","Jue","Vie","Sáb","Dom"].forEach(d => {
                html += `<div class="header">${d}</div>`;
            });

            // Horas fijas (ejemplo 8 a 18)
            for (let h = 8; h <= 18; h++) {
                html += `<div class="header">${h}:00</div>`;
                for (let d = 1; d <= 7; d++) {
                    let turno = data.find(t => t.dia_semana == d && t.hora == h);
                    if (turno) {
                        let clase = turno.estado === "reservado" ? "turno-reservado" : "turno-disponible";
                        html += `<div class="${clase}" onclick="editarTurno(${turno.id}, '${turno.estado}')">${turno.estado}</div>`;
                    } else {
                        html += `<div onclick="nuevoTurno(${d}, ${h})">+</div>`;
                    }
                }
            }

            $("#calendario-turnos").html(html);
        });
    }

    // Abrir modal nuevo turno
    window.nuevoTurno = function(dia, hora) {
        $("#modal-titulo").text("Nuevo Turno");
        $("#id_turno").val("");
        $("#fecha").val(""); // Podrías calcular fecha exacta con `dia`
        $("#hora").val(hora+":00");
        $("#duracion").val(30);
        $("#modal-turno").show();
    };

    // Editar turno
    window.editarTurno = function(id, estado) {
        if (estado === "reservado") {
            if (confirm("¿Cancelar este turno reservado?")) {
                $.post("turnos.php", { accion: "cancelar", id_turno: id }, function () {
                    cargarCalendario();
                });
            }
        } else {
            if (confirm("¿Eliminar turno disponible?")) {
                $.post("turnos.php", { accion: "eliminar", id_turno: id }, function () {
                    cargarCalendario();
                });
            }
        }
    };

    // Guardar turno
    $("#form-turno").submit(function (e) {
        e.preventDefault();
        const datos = $(this).serialize() + "&accion=guardar&medico_id=" + $("#select-medico").val();
        $.post("turnos.php", datos, function () {
            cerrarModalTurno();
            cargarCalendario();
        });
    });

    window.cerrarModalTurno = function() {
        $("#modal-turno").hide();
    };

    // Eventos
    $("#select-medico").change(cargarCalendario);
    $("#btn-cargar-turno").click(() => nuevoTurno(1,8));

    // Inicial
    cargarMedicos();
});
