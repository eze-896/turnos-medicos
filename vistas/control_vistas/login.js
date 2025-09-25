document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("login-form");

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const correo = document.getElementById("login-email").value.trim();
        const password = document.getElementById("login-password").value.trim();

        if (!correo || !password) {
            alert("Ingrese correo y contraseña");
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
