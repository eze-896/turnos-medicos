document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("form-registro-secretaria");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const nombre = formData.get("nombre").trim();
        const apellido = formData.get("apellido").trim();
        const dni = formData.get("dni").trim();
        const contrasena = formData.get("contrasena");
        const email = formData.get("email").trim();
        const telefono = formData.get("telefono").trim();

        // Validaciones
        const nombreApellidoRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,}$/;

        if (!nombre || !nombreApellidoRegex.test(nombre)) {
            alert("Nombre inválido. Solo letras, mínimo 2 caracteres.");
            return;
        }
        if (!apellido || !nombreApellidoRegex.test(apellido)) {
            alert("Apellido inválido. Solo letras, mínimo 2 caracteres.");
            return;
        }
        if (!/^\d{7,15}$/.test(dni)) {
            alert("DNI inválido. 7 a 15 números.");
            return;
        }
        if (!contrasena || contrasena.length < 8 || !/(?=.*[a-zA-Z])(?=.*\d)/.test(contrasena)) {
            alert("Contraseña inválida. Mínimo 8 caracteres, al menos una letra y un número.");
            return;
        }
        const emailRegex = /^\S+@\S+\.\S+$/;
        if (!email || !emailRegex.test(email)) {
            alert("Email inválido.");
            return;
        }
        if (!/^\d{7,15}$/.test(telefono)) {
            alert("Teléfono inválido. 7 a 15 números.");
            return;
        }

        // Agregamos el rol al FormData
        formData.append("rol", "secretaria");

        // Enviar datos al NUEVO controlador
        try {
            const response = await fetch("../controles/registro_secretaria.php", { // CAMBIADO
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message || "Registro exitoso.");
                form.reset();
            } else {
                alert(result.message || "Error en el registro.");
                if (result.error) console.error("Error detallado:", result.error);
            }
        } catch (error) {
            console.error("Error en el registro:", error);
            alert("Ocurrió un error al registrarse. Intente nuevamente.");
        }
    });
});