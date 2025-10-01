document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("login-form");
    const emailInput = document.getElementById("login-email");
    const passwordInput = document.getElementById("login-password");

    // Crear botón para mostrar/ocultar contraseña
    const toggleBtn = document.createElement("button");
    toggleBtn.type = "button";
    toggleBtn.textContent = "Mostrar";
    toggleBtn.style.marginLeft = "10px";
    toggleBtn.style.cursor = "pointer";
    toggleBtn.style.padding = "5px 10px";
    toggleBtn.style.fontSize = "0.9rem";

    // Insertar el botón después del input de contraseña
    passwordInput.parentNode.appendChild(toggleBtn);

    toggleBtn.addEventListener("click", () => {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleBtn.textContent = "Ocultar";
        } else {
            passwordInput.type = "password";
            toggleBtn.textContent = "Mostrar";
        }
    });

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const correo = emailInput.value.trim();
        const password = passwordInput.value;

        // Validaciones

        // Correo obligatorio y formato básico válido
        if (!correo) {
            alert("El correo electrónico es obligatorio.");
            console.error("Correo vacío");
            emailInput.focus();
            return;
        }
        const emailRegex = /^\S+@\S+\.\S+$/;
        if (!emailRegex.test(correo)) {
            alert("Ingrese un correo electrónico válido.");
            console.error("Correo inválido:", correo);
            emailInput.focus();
            return;
        }

        // Contraseña obligatoria y mínimo 8 caracteres
        if (!password) {
            alert("La contraseña es obligatoria.");
            console.error("Contraseña vacía");
            passwordInput.focus();
            return;
        }
        if (password.length < 8) {
            alert("La contraseña debe tener al menos 8 caracteres.");
            console.error("Contraseña demasiado corta");
            passwordInput.focus();
            return;
        }

        try {
            const formData = new FormData(form);
            const response = await fetch("../controles/login.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                if (result.rol === 'secretaria') window.location.href = "../vistas/administracion.html";
                else if (result.rol === 'medico') window.location.href = "../vistas/medico.html";
                else window.location.href = "../vistas/index.html";
            } else {
                alert(result.message);
                if (result.error) console.error("Error detallado:", result.error);
            }

        } catch (error) {
            console.error("Error en el login:", error);
            alert("Ocurrió un error. Revise la consola.");
        }
    });
});