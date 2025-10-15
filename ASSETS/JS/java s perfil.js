document.addEventListener('DOMContentLoaded', () => {
    fetch('/PHP/USUARIOS/obtener_mis_datos.php')
    .then(response => {
        if (!response.ok) throw new Error('Error al obtener datos');
        return response.json();
    })
    .then(user => {
        if(user.error){
            document.getElementById('user-data').textContent = user.error;
            return;
        }

        document.getElementById('user-data').innerHTML = `
            <p><strong>Nombre:</strong> ${user.nombre}</p>
            <p><strong>Email:</strong> ${user.email}</p>
            <p><strong>Teléfono:</strong> ${user.telefono || 'No proporcionado'}</p>
            <p><strong>Rol:</strong> ${user.rol}</p>
            <p><strong>Estado:</strong> ${user.estado}</p>
        `;
    })
    .catch(err => {
        document.getElementById('user-data').textContent = 'No se pudieron cargar los datos.';
        console.error(err);
    });
});