document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("register-form");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const dni = formData.get("dni");
        const contrasena = formData.get("contraseña");
        const email = formData.get("email");

        if (!/^\d{7,8}$/.test(dni)) {
            alert("El DNI debe tener entre 7 y 8 números.");
            return;
        }
        if (contrasena.length < 8) {
            alert("La contraseña debe tener al menos 8 caracteres.");
            return;
        }
        if (!/\S+@\S+\.\S+/.test(email)) {
            alert("Ingrese un correo electrónico válido.");
            return;
        }

        try {
            const response = await fetch("../controles/registro.php", { method: "POST", body: formData });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                window.location.href = "login.html"; // relativa a vistas/
            } else {
                alert(result.message);
                if (result.error) console.error("Error detallado:", result.error);
            }
        } catch (error) {
            console.error("Error en el registro:", error);
            alert("Ocurrió un error al registrarse. Intente nuevamente.");
        }
    });
});
