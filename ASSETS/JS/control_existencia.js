function getFormattedDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

document.addEventListener('DOMContentLoaded', () => {
    // Lógica para Deshabilitar Artículo, Reactivar, y Eliminar Movimiento (Omitida por brevedad)
    
    document.querySelectorAll('.deshabilitar-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.deshabilitar-form');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡El artículo se deshabilitará y no aparecerá en las planillas activas!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, deshabilitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.action = "../PHP/CONTROL_EXISTENCIA/deshabilitar_articulo.php";
                    form.method = "POST";
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.reactivar-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.reactivar-form');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡El artículo se reactivará y volverá a aparecer en las planillas activas!",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, reactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.action = "../PHP/CONTROL_EXISTENCIA/reactivar_articulo.php";
                    form.method = "POST";
                    form.submit();
                }
            });
        });
    });

    document.querySelectorAll('.eliminar-movimiento-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.eliminar-movimiento-form');
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡Este movimiento se eliminará permanentemente!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.action = "../PHP/CONTROL_EXISTENCIA/eliminar_movimiento.php";
                    form.method = "POST";
                    form.submit();
                }
            });
        });
    });

    // Lógica de Planillas y Formularios
    function bindPlanilla(planilla) {
        const btnDia = planilla.querySelector('.agregar-dia');
        const formWrap = planilla.querySelector('.daily-form');
        const btnCancel = planilla.querySelector('.btn-cancel');
        const form = formWrap.querySelector('form');
        const titulo = formWrap.querySelector('.form-title');
        const btnSubmit = form.querySelector('.btn-save');

        const entradasInput = form.querySelector('input[name=entradas_cantidad]');
        const salidasInput = form.querySelector('input[name=salidas_cantidad]');
        const movimientoIdInput = form.querySelector('input[name=movimiento_id]');
        const valorUnitarioInput = form.querySelector('input[name=valor_unitario]');
        const fechaInput = form.querySelector('input[name=fecha]');
        const detalleInput = form.querySelector('input[name=detalle]');

        if (!btnDia || !formWrap || !btnCancel || !form || !titulo || !btnSubmit || 
            !entradasInput || !salidasInput || !movimientoIdInput || 
            !valorUnitarioInput || !fechaInput || !detalleInput) {
            console.warn("Elementos faltantes en una planilla, se omite la inicialización para esta planilla.");
            return;
        }

        // 🔒 Bloquear edición del campo de fecha (HTML ya lo marca readonly, pero reforzamos)
        fechaInput.readOnly = true;
        fechaInput.addEventListener('mousedown', e => e.preventDefault());
        fechaInput.addEventListener('touchstart', e => e.preventDefault());
        fechaInput.addEventListener('keydown', e => e.preventDefault());
        fechaInput.addEventListener('focus', e => e.target.blur());

        const validarCampos = () => {
            const strEntradas = entradasInput.value.trim();
            const strSalidas = salidasInput.value.trim();
            const strValorUnitario = valorUnitarioInput.value.trim();

            const entradas = parseFloat(strEntradas) || 0;
            const salidas = parseFloat(strSalidas) || 0;
            const valorUnitario = parseFloat(strValorUnitario) || 0;
            const detalle = detalleInput.value.trim(); 

            let isValid = true;
            let errorText = '';

            if (strValorUnitario === '-0' || strEntradas === '-0' || strSalidas === '-0') {
                errorText = 'No se permite el valor "-0". El valor debe ser cero o positivo.';
                isValid = false;
            }

            if (isValid && (valorUnitario < 0 || entradas < 0 || salidas < 0)) {
                errorText = 'Ningún valor (Unitario, Entradas o Salidas) puede ser negativo.';
                isValid = false;
            } 
            
            if (isValid && detalle.startsWith('-')) {
                errorText = 'El campo Detalle no debe comenzar con el signo negativo ( - ).';
                isValid = false;
            }

            if (isValid && salidas > entradas) {
                errorText = `Las salidas (${salidas}) no pueden ser mayores que las entradas (${entradas}) en este mismo registro.`;
                isValid = false;
            }

            if (isValid && entradas > 0 && salidas > 0) {
                errorText = 'No es recomendable registrar Entradas y Salidas en el mismo movimiento. Por favor, regístralos por separado.';
                isValid = false;
            }

            if (!isValid && errorText) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Validación',
                    text: errorText,
                    confirmButtonText: 'Entendido'
                });
            }

            btnSubmit.disabled = !isValid;
            return isValid;
        };
        
        detalleInput.addEventListener('input', validarCampos);
        valorUnitarioInput.addEventListener('input', validarCampos);
        entradasInput.addEventListener('input', validarCampos);
        salidasInput.addEventListener('input', validarCampos);
        
        form.addEventListener('submit', function(event) {
            if (!validarCampos()) {
                event.preventDefault();
            }
        });

        // Agregar día nuevo
        btnDia.addEventListener('click', () => {
            form.reset();
            form.action = "../PHP/CONTROL_EXISTENCIA/guardar_movimiento.php";
            movimientoIdInput.value = "";
            titulo.textContent = "Registrar Nuevo Día";
            btnSubmit.textContent = "Guardar";
            valorUnitarioInput.value = 0.01; 
            entradasInput.value = 0;
            salidasInput.value = 0;
            fechaInput.value = getFormattedDate(); // automática
            formWrap.style.display = 'block';
            btnSubmit.disabled = false;
            validarCampos();
        });

        btnCancel.addEventListener('click', () => {
            formWrap.style.display = 'none';
        });

        // Editar movimiento
        planilla.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                formWrap.style.display = 'block';
                titulo.textContent = "Editar Movimiento";
                form.action = "../PHP/CONTROL_EXISTENCIA/editar_movimiento.php";
                btnSubmit.textContent = "Actualizar";

                fechaInput.value = btn.dataset.fecha;
                detalleInput.value = btn.dataset.detalle;
                valorUnitarioInput.value = btn.dataset.unitario;
                entradasInput.value = btn.dataset.entrada;
                salidasInput.value = btn.dataset.salida;
                movimientoIdInput.value = btn.dataset.id;

                validarCampos();
            });
        });

        // Lógica de búsqueda y planilla toggle
        const searchForm = planilla.querySelector('.search-form');
        const searchMonthInput = searchForm.querySelector('input[name="search_month"]');
        const searchDateInput = searchForm.querySelector('input[name="search_date_exact"]');
        const submitSearchBtn = searchForm.querySelector('.submit-search-btn');
        const clearSearchBtn = planilla.querySelector('.clear-search-btn');

        if (searchMonthInput && searchDateInput && submitSearchBtn) {
            searchMonthInput.addEventListener('change', () => {
                if (searchMonthInput.value) searchDateInput.value = '';
            });

            searchDateInput.addEventListener('change', () => {
                if (searchDateInput.value) searchMonthInput.value = '';
            });

            submitSearchBtn.addEventListener('click', (event) => {
                if (!searchMonthInput.value && !searchDateInput.value) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Búsqueda vacía',
                        text: 'Por favor, selecciona un mes o una fecha específica para buscar.',
                        confirmButtonText: 'Entendido'
                    });
                    event.preventDefault();
                }
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                const url = new URL(window.location.href);
                const currentArticuloId = clearSearchBtn.dataset.articuloId;

                url.searchParams.delete('search_month');
                url.searchParams.delete('search_date_exact');
                url.searchParams.set('articulo_id', currentArticuloId);
                window.location.href = url.toString();
            });
        }

        const planillaHeader = planilla.querySelector('.planilla-header');
        const planillaContent = planilla.querySelector('.planilla-content');
        const currentArticuloIdInPlanilla = planilla.querySelector('input[name="articulo_id"]').value;

        if (planillaHeader && planillaContent) {
            planillaHeader.addEventListener('click', () => {
                planillaContent.classList.toggle('active');
                planillaHeader.classList.toggle('expanded');
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        const articuloIdFromUrl = urlParams.get('articulo_id');

        if (articuloIdFromUrl && articuloIdFromUrl == currentArticuloIdInPlanilla) {
            planillaContent.classList.add('active');
            planillaHeader.classList.add('expanded');
            planilla.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    const planillas = document.querySelectorAll('.planilla');
    planillas.forEach(bindPlanilla);
});
