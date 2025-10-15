let editMode = false;

let hasRecords = false; 

document.addEventListener('DOMContentLoaded', () => {
    const btnEditarConfig = document.getElementById('btn-editar-config');
    if (btnEditarConfig && btnEditarConfig.disabled) {
        deshabilitarBotonEditarConfig();
        hasRecords = true; 
    } else if (btnEditarConfig) {
        hasRecords = false; 
    }

    const formConfig = document.getElementById('form-config');
    if (formConfig) {
        formConfig.querySelectorAll('input:not([type="hidden"])').forEach(input => {
            if (input.dataset.originalValue === undefined) {
                input.dataset.originalValue = input.value;
            }
        });
    }

    const mesInput = document.getElementById('periodo');
    if (mesInput && !mesInput.value) {
        const hoy = new Date();
        mesInput.value = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}`;
    }

    if (btnEditarConfig) btnEditarConfig.addEventListener('click', handleEditarConfig);
    const btnRegistrar = document.getElementById('btn-registrar');
    if (btnRegistrar) btnRegistrar.addEventListener('click', handleRegistrarAsistencia);
    const btnGuardarPlanilla = document.getElementById('btn-guardar-planilla-completa');
    if (btnGuardarPlanilla) btnGuardarPlanilla.addEventListener('click', handleGuardarPlanilla);

    const formDia = document.getElementById('form-dia');
    if (formDia) {
        formDia.querySelectorAll('input[type="number"]').forEach(inp => {
            inp.addEventListener('input', () => {
                // keep only digits
                const cleaned = inp.value.replace(/[^0-9]/g, '');
                if (inp.value !== cleaned) inp.value = cleaned;
            });
            inp.addEventListener('keydown', (e) => {
                if (e.key === '-' || e.key === '+' || e.key === 'e') e.preventDefault();
            });
        });

        formDia.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!validarFormularioDiario()) return;

            Swal.fire({
                title: '¿Confirmar el registro?',
                text: editMode
                    ? 'Se actualizará el registro diario.'
                    : 'Se guardará el nuevo registro diario.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'No, cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    hasRecords = true; 
                    deshabilitarBotonEditarConfig();
                    formDia.submit();
                }
            });
        });
    }

    ['institucion','localidad','tipo','operador'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', () => {
            const cleaned = el.value.replace(/[0-9]/g, '');
            if (el.value !== cleaned) el.value = cleaned;
        });
    });

    const rectorEl = document.getElementById('rector');
    if (rectorEl) {
        rectorEl.addEventListener('input', () => {
            const cleaned = rectorEl.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.\(\)]/g, '');
            if (rectorEl.value !== cleaned) rectorEl.value = cleaned;
        });
    }
});


function validarConfigFields() {
    const errores = [];
    const camposRequeridos = [
        {id: 'institucion', label: 'Institución Educativa', type: 'textNoDigits'},
        {id: 'direccion', label: 'Dirección sede', type: 'direccion'},
        {id: 'localidad', label: 'Localidad / Barrio', type: 'textNoDigits'},
        {id: 'tipo', label: 'Tipo de entrega', type: 'textNoDigits'},
        {id: 'anio', label: 'Año', type: 'anio'},
        {id: 'convenio', label: 'N° Convenio', type: 'convenio'},
        {id: 'operador', label: 'Operador / Asociado', type: 'textNoDigits'},
        {id: 'rector', label: 'Rector (Nombre y Teléfono)', type: 'rector'}
    ];

    const regex = {
        textNoDigits: /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.\(\)]+$/,
        direccion: /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.\(\)\,#]+$/,
        convenio: /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.\(\)]+$/, 
        rector: /^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s\-\.\(\)]+$/
    };

    camposRequeridos.forEach(c => {
        const el = document.getElementById(c.id);
        if (!el) {
            return;
        }
        const val = (el.value || '').trim();
        if (val === '') {
            errores.push(`${c.label} no puede quedar vacío.`);
            return;
        }
        if (c.type === 'anio') {
            if (!/^[0-9]+$/.test(val)) {
                errores.push(`${c.label} debe ser un número entero (sin signos).`);
                return;
            }
            const n = Number(val);
            if (!Number.isInteger(n) || n < 0) {
                errores.push(`${c.label} debe ser un número entero no negativo.`);
                return;
            }
        } else {
            const r = regex[c.type];
            if (r && !r.test(val)) {
                if (c.type === 'textNoDigits') {
                    errores.push(`${c.label} no debe contener números ni caracteres inválidos.`);
                } else if (c.type === 'direccion') {
                    errores.push(`${c.label} contiene caracteres no permitidos.`);
                } else if (c.type === 'convenio') {
                    errores.push(`${c.label} contiene caracteres no permitidos.`);
                } else if (c.type === 'rector') {
                    errores.push(`${c.label} contiene caracteres no permitidos. (Ej: Juan Pérez 3123456789)`);
                } else {
                    errores.push(`${c.label} no cumple el formato requerido.`);
                }
            }
        }
    });

    return errores;
}


function validarFormularioDiario() {
    const fechaVal = document.getElementById('d_fecha')?.value;
    if (!fechaVal) {
        Swal.fire({ icon: 'error', title: 'Error de Validación', text: 'Debes seleccionar una fecha.', confirmButtonText: 'Entendido' });
        return false;
    }
    
    const tipos = ['d_tipoA', 'd_tipoB', 'd_tipoC', 'd_tipoD'];
    let errorTipos = false;
    const mensajesErrorTipos = [];

    tipos.forEach(id => {
        const el = document.getElementById(id);
        if (!el) {
            mensajesErrorTipos.push(`Campo ${id} no encontrado.`);
            errorTipos = true;
            return;
        }
        const raw = el.value;
        if (raw === undefined || raw === null || raw.toString().trim() === '') {
            errorTipos = true;
            mensajesErrorTipos.push(`El campo ${id.replace('d_tipo', 'Tipo ')} no puede quedar vacío.`);
            return;
        }
        if (!/^[0-9]+$/.test(raw.trim())) {
            errorTipos = true;
            mensajesErrorTipos.push(`El campo ${id.replace('d_tipo', 'Tipo ')} debe ser un número entero no negativo.`);
            return;
        }
        const valor = Number(raw);
        if (!Number.isInteger(valor) || valor < 0) {
            errorTipos = true;
            mensajesErrorTipos.push(`El campo ${id.replace('d_tipo', 'Tipo ')} debe ser un número entero no negativo.`);
        }
    });

    if (errorTipos) {
        Swal.fire({ 
            icon: 'error', 
            title: 'Error de Validación', 
            html: '<p style="text-align:left; font-weight: bold;">Los campos Tipo A/B/C/D deben ser números enteros no negativos y no quedar vacíos:</p><ul style="text-align:left; margin-top: 5px; list-style-type: disc; padding-left: 20px;">' + mensajesErrorTipos.map(e => `<li>${e}</li>`).join('') + '</ul>', 
            confirmButtonText: 'Entendido' 
        });
        return false;
    }

    const respIE = document.getElementById('d_respIE')?.value.trim() ?? '';
    const respAsoc = document.getElementById('d_respAsoc')?.value.trim() ?? '';
    const respInt = document.getElementById('d_respInt')?.value.trim() ?? '';
    const textoValido = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.\(\)]+$/;
    let errores = [];
    if (!respIE || !textoValido.test(respIE)) errores.push('Responsable IE inválido (no dejar vacío ni usar números).');
    if (!respAsoc || !textoValido.test(respAsoc)) errores.push('Responsable Asociado inválido (no dejar vacío ni usar números).');
    if (!respInt || !textoValido.test(respInt)) errores.push('Responsable Interno inválido (no dejar vacío ni usar números).');
    if (document.getElementById('d_cont')?.checked && (!respIE && !respAsoc && !respInt)) {
        errores.push('Si hay contingencia, debe haber al menos un responsable.');
    }
    if (errores.length) {
        Swal.fire({ icon: 'error', title: 'Errores en el formulario', html: '<ul style="text-align:left">' + errores.map(e => `<li>${e}</li>`).join('') + '</ul>' });
        return false;
    }
    return true;
}


function hayCambiosEnConfiguracion() {
    const formConfig = document.getElementById('form-config');
    if (!formConfig) return false;
    let cambios = false;
    formConfig.querySelectorAll('input:not([type="hidden"])').forEach(input => {
        if (input.dataset.originalValue !== undefined && input.value !== input.dataset.originalValue) cambios = true;
    });
    return cambios;
}

function deshabilitarBotonEditarConfig() {
    const btn = document.getElementById('btn-editar-config');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.style.cursor = 'not-allowed';
        btn.textContent = 'Configuración Bloqueada';
        btn.classList.remove('btn-secondary', 'btn-success');
        btn.classList.add('btn-disabled-custom');
    }
}

function habilitarBotonEditarConfig() {
    const btn = document.getElementById('btn-editar-config');
    if (btn && !hasRecords) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btn.textContent = '✏️ Editar Configuración';
        btn.classList.remove('btn-disabled-custom', 'btn-success');
        btn.classList.add('btn-secondary');
    }
}

function handleRegistrarAsistencia() {
    if (hasRecords || localStorage.getItem('importante_seen') === '1') {
        toggleForm(false, hasRecords);
        return;
    }

    Swal.fire({
        title: '¡Importante!',
        html: 'Al registrar el primer día, la configuración quedará bloqueada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, registrar asistencia',
        cancelButtonText: 'No, cancelar',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) {
            toggleForm(false, hasRecords);
        }
    });
}


function handleEditarConfig() {
    const btn = document.getElementById('btn-editar-config');
    const formConfig = document.getElementById('form-config');
    if (!formConfig || btn.disabled) return;
    const configInputs = Array.from(formConfig.querySelectorAll('input:not([name=\"consecutivo\"])'));

    if (btn.textContent.includes('Editar')) {
        configInputs.forEach(i => i.readOnly = false);
        btn.textContent = '💾 Guardar Cambios Configuración';
        btn.classList.replace('btn-secondary', 'btn-success');

        configInputs.forEach(i => i.setAttribute('required', 'required'));
    } else {
        const errores = validarConfigFields();
        if (errores.length) {
            Swal.fire({
                icon: 'error',
                title: 'Errores en Configuración',
                html: '<ul style=\"text-align:left\">' + errores.map(e => `<li>${e}</li>`).join('') + '</ul>',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        if (hayCambiosEnConfiguracion()) {
            Swal.fire({
                title: '¿Guardar cambios de configuración?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar cambios',
                cancelButtonText: 'No, cancelar'
            }).then(r => {
                if (r.isConfirmed) {
                    formConfig.submit();
                } else {
                    configInputs.forEach(i => {
                        i.value = i.dataset.originalValue;
                        i.readOnly = true;
                        i.removeAttribute('required');
                    });
                    btn.textContent = '✏️ Editar Configuración';
                    btn.classList.replace('btn-success', 'btn-secondary');
                }
            });
        } else {
            Swal.fire({ icon: 'info', title: 'Sin Cambios', text: 'No se detectaron cambios.', confirmButtonText: 'Entendido' });
            configInputs.forEach(i => {
                i.readOnly = true;
                i.removeAttribute('required');
            });
            btn.textContent = '✏️ Editar Configuración';
            btn.classList.replace('btn-success', 'btn-secondary');
        }
    }
}

function toggleForm(hide = false, recordsExist = false) {
    const sec = document.getElementById('daily-form');
    const btnRegistrar = document.getElementById('btn-registrar');
    const d_fecha = document.getElementById('d_fecha');
    const idRegistroEditarInput = document.getElementById('id_registro_editar');
    
    if (hide) {
        sec && (sec.style.display = 'none');
        editMode = false;
        const formDia = document.getElementById('form-dia');
        formDia && formDia.reset();
        if (idRegistroEditarInput) idRegistroEditarInput.value = '';
        btnRegistrar && (btnRegistrar.style.display = 'inline-block');
        d_fecha && (d_fecha.readOnly = true);
        
        if (!recordsExist) {
             habilitarBotonEditarConfig(); 
        } else {
             deshabilitarBotonEditarConfig(); 
        }

    } else {
        sec && (sec.style.display = 'block');
        btnRegistrar && (btnRegistrar.style.display = 'none');
        const hoy = new Date();
        const fechaFormatoInput = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`;
        if (d_fecha) {
            d_fecha.value = fechaFormatoInput;
            d_fecha.readOnly = true;
        }
        deshabilitarBotonEditarConfig()
    }
}

function editarDia(paramsArray) {
    const [id, fecha, a, b, c, d, cont, ie, asoc, inter] = paramsArray;
    editMode = true;
    toggleForm(false, true); 

    document.getElementById('id_registro_editar').value = id;
    document.getElementById('d_fecha').value = fecha;
    document.getElementById('d_tipoA').value = a;
    document.getElementById('d_tipoB').value = b;
    document.getElementById('d_tipoC').value = c;
    document.getElementById('d_tipoD').value = d;
    document.getElementById('d_cont').checked = (cont === true || cont === 'true');
    document.getElementById('d_respIE').value = ie;
    document.getElementById('d_respAsoc').value = asoc;
    document.getElementById('d_respInt').value = inter;
}

function handleGuardarPlanilla() {
    const erroresConfig = validarConfigFields();
    if (erroresConfig.length) {
        Swal.fire({
            icon: 'error',
            title: 'No se puede guardar la planilla',
            html: '<ul style="text-align:left">' + erroresConfig.map(e => `<li>${e}</li>`).join('') + '</ul>'
        });
        return;
    }

    Swal.fire({
        title: '¿Guardar planilla mensual?',
        text: 'Esta acción es irreversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar y finalizar',
        cancelButtonText: 'No, cancelar',
        reverseButtons: true
    }).then(r => {
        if (r.isConfirmed) {
            const form = document.getElementById('form-guardar-planilla');
            if (!form) {
                console.error('No se encontró #form-guardar-planilla en el DOM.');
                return;
            }

            while (form.firstChild) form.removeChild(form.firstChild);

            const configFormId = document.querySelector('#form-config input[name="config_id"]');
            if (configFormId) {
                const v = document.createElement('input');
                v.type = 'hidden'; v.name = 'config_id'; v.value = configFormId.value;
                form.appendChild(v);
            }

            ['consecutivo','periodo','institucion','direccion','localidad','tipo','anio','convenio','operador','rector']
            .forEach(name => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = name;
                inp.value = document.getElementById(name)?.value ?? '';
                form.appendChild(inp);
            });

            document.querySelectorAll('#tabla-dias tbody tr').forEach((tr,i) => {
                const tds = tr.querySelectorAll('td');
                ['fecha','tipoA','tipoB','tipoC','tipoD','cont','respIE','respAsoc','respInt']
                .forEach((campo,j) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = `reg[${i}][${campo}]`;
                    let val = tds[j].innerText.trim();
                    if (campo === 'cont') val = (val === 'Sí' ? '1' : '0');
                    inp.value = val;
                    form.appendChild(inp);
                });
            });

            form.submit();
        }
    });
}
