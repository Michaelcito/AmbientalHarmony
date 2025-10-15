document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('articuloForm');
            const minimoInput = document.getElementById('minimo');
            const maximoInput = document.getElementById('maximo');

            form.addEventListener('submit', function(event) {
                const minimo = parseFloat(minimoInput.value);
                const maximo = parseFloat(maximoInput.value);

                if (minimo > maximo) {
                    event.preventDefault(); 
                    alert('Error de validación: La cantidad Mínima no puede ser mayor que la cantidad Máxima.');
                    minimoInput.focus(); 
                }
            });

            [minimoInput, maximoInput].forEach(input => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'e' || e.key === 'E') {
                        e.preventDefault();
                    }
                });
            });
        });