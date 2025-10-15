    document.addEventListener('DOMContentLoaded', function() {
        const fechaInput      = document.getElementById('fecha');
        const resFechaInput = document.getElementById('res-fecha');
        const today = new Date();
        const yyyy    = today.getFullYear();
        const mm      = String(today.getMonth() + 1).padStart(2, '0');
        const dd      = String(today.getDate()).padStart(2, '0');
        const formatted = `${yyyy}-${mm}-${dd}`;
        if (fechaInput) fechaInput.value = formatted;
        if (resFechaInput) resFechaInput.value = formatted;

        const conceptoSel = document.getElementById('concepto');
        const registroInp = document.getElementById('registro');

        const updateRegistroNumber = async () => {
            const tipo  = conceptoSel.value;
            const fecha = fechaInput.value;

            if (tipo && fecha) {
                const url   = `../PHP/NOVEDADES/obtener_numero_registro.php?fecha=${fecha}&tipo=${tipo}`;
                try {
                    const resp = await fetch(url);
                    const data = await resp.json();
                    registroInp.value = data.numero || 1;
                } catch (error) {
                    console.error('Error fetching record number:', error);
                    registroInp.value = 1;
                }
            } else {
                registroInp.value = '';
            }
        };

        conceptoSel.addEventListener('change', updateRegistroNumber);
        fechaInput.addEventListener('change', updateRegistroNumber);
        if (conceptoSel.value) {
            updateRegistroNumber();
        }

        const formHist = document.getElementById('form-historial');
        if (formHist) {
            formHist.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check for records before submitting
                fetch('../PHP/NOVEDADES/verificar_registros_existentes.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.hay_registros) {
                            Swal.fire({
                                title: '¿Estás seguro?',
                                text: "¡Esta acción enviará todos los registros actuales al historial y los eliminará de esta vista!",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Sí, enviar al historial',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    formHist.submit();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Atención',
                                text: "No hay registros para guardar en el historial.",
                                icon: 'info',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Ocurrió un error al verificar los registros.',
                            icon: 'error',
                            confirmButtonText: 'Cerrar'
                        });
                    });
            });
        }
    });