document.getElementById('formulario').addEventListener('submit', function(event) {
    event.preventDefault();
    calcularImpacto();
});

let impactoChart; 

function calcularImpacto() {
    let residuosOrganicos = parseFloat(document.getElementById('residuos-organicos').value);
    let residuosReciclables = parseFloat(document.getElementById('residuos-reciclables').value);
    let residuosNoReciclables = parseFloat(document.getElementById('residuos-no-reciclables').value);

    if (isNaN(residuosOrganicos) || isNaN(residuosReciclables) || isNaN(residuosNoReciclables)) {
        alert('Por favor, ingresa valores numéricos válidos en todos los campos.');
        return;
    }

    if (residuosOrganicos < 0 || residuosReciclables < 0 || residuosNoReciclables < 0) {
        alert('Los valores no pueden ser negativos.');
        return;
    }

    let impactoOrganicos = residuosOrganicos * 0.5;
    let impactoReciclables = residuosReciclables * 0.3;
    let impactoNoReciclables = residuosNoReciclables * 0.7;
    let impactoTotal = impactoOrganicos + impactoReciclables + impactoNoReciclables;

    document.getElementById('impacto-organicos').textContent = impactoOrganicos.toFixed(2);
    document.getElementById('impacto-reciclables').textContent = impactoReciclables.toFixed(2);
    document.getElementById('impacto-no-reciclables').textContent = impactoNoReciclables.toFixed(2);
    document.getElementById('impacto-total').textContent = impactoTotal.toFixed(2);

    document.getElementById('resultado').classList.add('active');

    generarGrafico(residuosOrganicos, residuosReciclables, residuosNoReciclables);
}

function generarGrafico(org, rec, noRec) {
    const ctx = document.getElementById('impactoChart').getContext('2d');

    if (impactoChart) {
        impactoChart.destroy();
    }

    impactoChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Residuos Orgánicos', 'Residuos Reciclables', 'Residuos No Reciclables'],
            datasets: [{
                label: 'Impacto Ecológico (unidades)',
                data: [org * 0.5, rec * 0.3, noRec * 0.7],
                backgroundColor: [
                    'rgba(76, 175, 80, 0.6)', 
                    'rgba(33, 150, 243, 0.6)', 
                    'rgba(244, 67, 54, 0.6)'  
                ],
                borderColor: [
                    'rgba(76, 175, 80, 1)',
                    'rgba(33, 150, 243, 1)',
                    'rgba(244, 67, 54, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}