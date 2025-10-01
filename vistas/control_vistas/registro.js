document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("register-form");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const nombre = formData.get("nombre").trim();
        const apellido = formData.get("apellido").trim();
        const dni = formData.get("dni").trim();
        const contrasena = formData.get("contraseña");
        const email = formData.get("email").trim();
        const telefono = formData.get("telefono").trim();
        const obraSocial = formData.get("obra_social").trim();
        const numAfiliado = formData.get("num_afiliado").trim();

        // Validaciones

        // Nombre y Apellido: obligatorios, solo letras y espacios, mínimo 2 caracteres
        const nombreApellidoRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,}$/;
        if (!nombre) {
            console.error("Nombre vacío");
            alert("El nombre es obligatorio.");
            return;
        }
        if (!nombreApellidoRegex.test(nombre)) {
            console.error("Nombre inválido:", nombre);
            alert("El nombre debe contener solo letras y tener al menos 2 caracteres.");
            return;
        }
        if (!apellido) {
            console.error("Apellido vacío");
            alert("El apellido es obligatorio.");
            return;
        }
        if (!nombreApellidoRegex.test(apellido)) {
            console.error("Apellido inválido:", apellido);
            alert("El apellido debe contener solo letras y tener al menos 2 caracteres.");
            return;
        }

        // DNI: obligatorio, solo números, 7 u 8 dígitos
        if (!dni) {
            console.error("DNI vacío");
            alert("El DNI es obligatorio.");
            return;
        }
        if (!/^\d{7,15}$/.test(dni)) {
            console.error("DNI inválido:", dni);
            alert("El DNI debe tener entre 7 y 15 números.");
            return;
        }

        // Contraseña: obligatoria, mínimo 8 caracteres, al menos una letra y un número
        if (!contrasena) {
            console.error("Contraseña vacía");
            alert("La contraseña es obligatoria.");
            return;
        }
        if (contrasena.length < 8) {
            console.error("Contraseña demasiado corta");
            alert("La contraseña debe tener al menos 8 caracteres.");
            return;
        }
        if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(contrasena)) {
            console.warn("Contraseña no contiene letra y número");
            alert("La contraseña debe contener al menos una letra y un número.");
            return;
        }

        // Email: obligatorio, formato válido
        const emailRegex = /^\S+@\S+\.\S+$/;
        if (!email) {
            console.error("Email vacío");
            alert("El correo electrónico es obligatorio.");
            return;
        }
        if (!emailRegex.test(email)) {
            console.error("Email inválido:", email);
            alert("Ingrese un correo electrónico válido.");
            return;
        }

        // Teléfono: obligatorio, solo números, entre 7 y 15 dígitos
        if (!telefono) {
            console.error("Teléfono vacío");
            alert("El teléfono es obligatorio.");
            return;
        }
        if (!/^\d{7,15}$/.test(telefono)) {
            console.error("Teléfono inválido:", telefono);
            alert("El teléfono debe contener solo números y tener entre 7 y 15 dígitos.");
            return;
        }

        // Obra social: obligatorio, mínimo 2 caracteres
        if (!obraSocial) {
            console.error("Obra social vacía");
            alert("La obra social es obligatoria.");
            return;
        }
        if (obraSocial.length < 2) {
            console.error("Obra social inválida:", obraSocial);
            alert("La obra social debe tener al menos 2 caracteres.");
            return;
        }

        // Número de afiliado: obligatorio, alfanumérico, 3 a 20 caracteres
        if (!numAfiliado) {
            console.error("Número de afiliado vacío");
            alert("El número de afiliado es obligatorio.");
            return;
        }
        if (!/^[a-zA-Z0-9]{3,20}$/.test(numAfiliado)) {
            console.error("Número de afiliado inválido:", numAfiliado);
            alert("El número de afiliado debe ser alfanumérico y tener entre 3 y 20 caracteres.");
            return;
        }

        // Evitar espacios en campos críticos
        if (/\s/.test(dni)) {
            console.error("DNI contiene espacios");
            alert("El DNI no debe contener espacios.");
            return;
        }
        if (/\s/.test(email)) {
            console.error("Email contiene espacios");
            alert("El correo electrónico no debe contener espacios.");
            return;
        }
        if (/\s/.test(numAfiliado)) {
            console.error("Número de afiliado contiene espacios");
            alert("El número de afiliado no debe contener espacios.");
            return;
        }

        // Si pasa todas las validaciones, enviar formulario
        try {
            const response = await fetch("../controles/registro.php", {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message || "Registro exitoso.");
                window.location.href = "login.html"; // relativa a vistas/
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
