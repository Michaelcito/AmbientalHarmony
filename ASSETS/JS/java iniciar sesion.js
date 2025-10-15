document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const errorMsg = document.getElementById("login-error");
    const passwordField = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    if (!loginForm) return;

    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    loginForm.addEventListener("submit", function (event) {
        if (errorMsg) {
            errorMsg.textContent = "";
        }

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        if (!email || !password) {
            event.preventDefault();
            if (errorMsg) {
                errorMsg.textContent = "Por favor, complete todos los campos.";
            }
            return;
        }

        const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
        if (!emailPattern.test(email)) {
            event.preventDefault();
            if (errorMsg) {
                errorMsg.textContent = "Introduzca un correo electrónico válido.";
            }
            return;
        }

        if (password.length < 6) {
            event.preventDefault();
            if (errorMsg) {
                errorMsg.textContent = "La contraseña debe tener al menos 6 caracteres.";
            }
            return;
        }

    });

    const bubbleContainer = document.querySelector('.bubble-container');
    if (bubbleContainer) {
        function createBubble() {
            const bubble = document.createElement('div');
            bubble.classList.add('bubble');
            const size = Math.random() * 60 + 20 + 'px';
            bubble.style.width = size;
            bubble.style.height = size;
            bubble.style.left = Math.random() * 100 + 'vw';
            bubble.style.top = Math.random() * 100 + 'vh';
            bubble.style.animationDuration = Math.random() * 5 + 5 + 's';
            bubble.style.animationDelay = Math.random() * 5 + 's';
            bubble.style.opacity = Math.random() * 0.5 + 0.3;
            bubbleContainer.appendChild(bubble);

            bubble.addEventListener('animationend', () => {
                bubble.remove();
            });
        }
        setInterval(createBubble, 500);
    }
});

(function(){
        const form = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        const emailRegex = /^[A-Za-z0-9][A-Za-z0-9._%+\-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i;

        form.addEventListener('submit', function(e){
            // Busca o crea el elemento de error
            let errorEl = document.getElementById('login-error');
            if (!errorEl) {
                errorEl = document.createElement('p');
                errorEl.id = 'login-error';
                errorEl.className = 'error-message';
                errorEl.style.color = 'red';
                // insertar antes del formulario
                form.parentNode.insertBefore(errorEl, form);
            }

            const email = emailInput.value.trim();
            const password = passwordInput.value || '';

            if (!emailRegex.test(email)) {
                e.preventDefault();
                errorEl.textContent = 'Correo inválido: no puede empezar con símbolos y debe tener formato válido.';
                emailInput.focus();
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                errorEl.textContent = 'La contraseña debe tener al menos 6 caracteres.';
                passwordInput.focus();
                return false;
            }

            // si pasa validaciones cliente, se permite el envío y el servidor volverá a validar
            return true;
        });

        // pequeño helper: limpiar mensaje cuando el usuario escribe
        emailInput.addEventListener('input', () => {
            const err = document.getElementById('login-error');
            if (err) err.textContent = '';
        });
        passwordInput.addEventListener('input', () => {
            const err = document.getElementById('login-error');
            if (err) err.textContent = '';
        });
    })();