document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("userForm");
    const inputPhoto = document.getElementById("userPhoto");
    const previewPhoto = document.getElementById("previewPhoto");
    const inputSearch = document.getElementById("busquedaUsuario");
    const tbody = document.getElementById("tablaUsuarios");

    if (inputPhoto) {
        inputPhoto.addEventListener("change", () => {
            const file = inputPhoto.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewPhoto.src = e.target.result;
                    previewPhoto.style.display = "block";
                };
                reader.readAsDataURL(file);
            } else {
                const originalSrc = previewPhoto.getAttribute('data-original-src');
                if (originalSrc) {
                     previewPhoto.src = originalSrc;
                     previewPhoto.style.display = "block";
                } else {
                    previewPhoto.style.display = "none";
                    previewPhoto.src = "";
                }
            }
        });
    }
    if (form) {
        form.addEventListener("submit", e => {      
            e.preventDefault();

            form.querySelectorAll('input, select').forEach(input => {
                input.classList.remove("input-error");
            });

            if (validarFormulario(form)) {
                form.submit();
            }
        });
    }

    if (inputSearch && tbody) {
        inputSearch.addEventListener("input", filtrarUsuarios);
    }
});


/**
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validarFormulario(form) {
    const inputName = form.querySelector('input[name="nombre"]');
    const inputEmail = form.querySelector('input[name="email"]');
    const inputPhone = form.querySelector('input[name="telefono"]');
    const inputPass = form.querySelector('input[name="password"]');
    const inputPhoto = document.getElementById("userPhoto");
    
    const nameVal = inputName.value.trim();
    const emailVal = inputEmail.value.trim();
    const phoneVal = inputPhone.value.trim();
    const passVal = inputPass.value.trim();
    const editing = form.action.includes("actualizar_usuario.php");
    
    let errores = [];
    
    // Nuevo patrón: obliga que el primer carácter sea letra o número,
    // permite ., _, %, +, - dentro de la parte local, valida dominio sencillo.
    const emailPattern = /^[A-Za-z0-9][A-Za-z0-9._%+\-]*@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/;
    const namePattern = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.]+$/; 
    const phonePattern = /^[0-9]{7,15}$/;
    const minPassLength = 8;
    let firstInvalidInput = null; 

    if (nameVal.length < 3) {
        errores.push("El nombre es obligatorio y debe tener al menos 3 caracteres.");
        inputName.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputName;
    } else if (!namePattern.test(nameVal)) {
        errores.push("El nombre no debe contener números ni caracteres especiales como: / * @ # etc.");
        inputName.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputName;
    }

    if (!emailPattern.test(emailVal)) {
        // Mensaje más claro si falla el patrón general
        errores.push("Introduce un correo electrónico válido (ej. usuario@dominio.com). No puede empezar por símbolos ni por punto.");
        inputEmail.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputEmail;
    } else {
        // chequeo extra opcional para mensaje específico (no obligatorio porque el patrón ya lo garantiza)
        if (!/^[A-Za-z0-9]/.test(emailVal)) {
            errores.push("El correo electrónico debe comenzar con una letra o un número (no símbolos).");
            inputEmail.classList.add("input-error");
            if (!firstInvalidInput) firstInvalidInput = inputEmail;
        }
    }

    if (phoneVal === "") {
        errores.push("El teléfono es obligatorio.");
        inputPhone.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputPhone;
    } else if (phoneVal.startsWith('-')) {
        errores.push("El teléfono no puede ser un número negativo.");
        inputPhone.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputPhone;
    } else if (!phonePattern.test(phoneVal)) {
        errores.push("El teléfono debe contener solo números (7-15 dígitos).");
        inputPhone.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputPhone;
    } else if (parseInt(phoneVal, 10) === 0) {
        errores.push("El número de teléfono no puede ser cero.");
        inputPhone.classList.add("input-error");
        if (!firstInvalidInput) firstInvalidInput = inputPhone;
    }
    
    if (!editing) {
        if (passVal.length < minPassLength) {
            errores.push(`La contraseña es obligatoria y debe tener al menos ${minPassLength} caracteres.`);
            inputPass.classList.add("input-error");
            if (!firstInvalidInput) firstInvalidInput = inputPass;
        }
    } else {
        if (passVal !== "" && passVal.length < minPassLength) {
            errores.push(`Si cambias la contraseña, debe tener al menos ${minPassLength} caracteres.`);
            inputPass.classList.add("input-error");
            if (!firstInvalidInput) firstInvalidInput = inputPass;
        }
    }

    if (inputPhoto && inputPhoto.files.length > 0) {
        const fileSize = inputPhoto.files[0].size / 1024 / 1024; 
        if (fileSize > 2) { 
            errores.push("El tamaño de la foto no debe exceder los 2MB.");
            inputPhoto.classList.add("input-error");
        }
    }

    if (errores.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Validación',
            html: '<ul style="text-align:left; margin-top: 5px; list-style-type: disc; padding-left: 20px; font-weight: 500;">' + errores.map(e => `<li>${e}</li>`).join('') + '</ul>',
            confirmButtonText: 'Entendido'
        }).then(() => {
            if (firstInvalidInput) {
                firstInvalidInput.focus();
            }
        });
        return false;
    }

    return true;
}


function filtrarUsuarios() {
    const input = document.getElementById('busquedaUsuario');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('tablaUsuarios');
    const tr = table.getElementsByTagName('tr');

    Array.from(tr).forEach(row => {
        const cellName = row.cells[1] ? row.cells[1].textContent : '';
        const cellEmail = row.cells[2] ? row.cells[2].textContent : '';
        const cellPhone = row.cells[3] ? row.cells[3].textContent : '';
        
        const isMatch = cellName.toLowerCase().includes(filter) ||
                        cellEmail.toLowerCase().includes(filter) ||
                        cellPhone.toLowerCase().includes(filter);
        
        row.style.display = isMatch ? "" : "none";
    });
}