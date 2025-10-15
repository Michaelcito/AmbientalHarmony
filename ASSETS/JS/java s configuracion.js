document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const passwordInput = document.getElementById("password");
    const togglePassword = document.createElement("i");
    togglePassword.classList.add("fas", "fa-eye", "password-toggle");
    
    passwordInput.parentNode.appendChild(togglePassword);

    togglePassword.addEventListener("click", function () {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            togglePassword.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordInput.type = "password";
            togglePassword.classList.replace("fa-eye-slash", "fa-eye");
        }
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!hayCambios(form)) {
            alert("No has realizado cambios.");
            return;
        }

        if (confirm("¿Estás seguro de guardar los cambios?")) {
            form.submit();
        }
    });


    function hayCambios(formulario) {
        const inputs = formulario.querySelectorAll("input, select");
        for (let input of inputs) {
            if (input.value !== input.defaultValue) {
                return true;
            }
        }
        return false; 
    }
});
