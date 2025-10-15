document.getElementById('historyForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const selectedPeriod = document.getElementById('selectedPeriod').value;

    if (!selectedPeriod) {
        alert('Por favor, selecciona un período válido.');
        return;
    }

});