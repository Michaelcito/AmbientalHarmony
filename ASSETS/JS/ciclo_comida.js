function abrirModal() {
  document.getElementById("logoutModal").style.display = "flex";
}

function cerrarModal() {
  document.getElementById("logoutModal").style.display = "none";
}

function confirmarEliminacion() {
  document.getElementById("formEliminar").submit();
}
