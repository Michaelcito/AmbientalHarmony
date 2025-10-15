document.addEventListener('DOMContentLoaded', function() {
  const inputFecha = document.getElementById('input-fecha');
  const spanFecha = document.getElementById('fecha-hoy');

  let fechaObj;
  if (inputFecha && inputFecha.value) {
    const parts = inputFecha.value.split('-');
    if (parts.length === 3) {
      const y = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10) - 1;
      const d = parseInt(parts[2], 10);
      fechaObj = new Date(y, m, d);
    } else {
      fechaObj = new Date();
      if (inputFecha) inputFecha.value = formatForInput(fechaObj);
    }
  } else {
    fechaObj = new Date();
    if (inputFecha) inputFecha.value = formatForInput(fechaObj);
  }

  spanFecha.textContent = fechaObj.toLocaleDateString('es-CO', {
    day: '2-digit', month: '2-digit', year: 'numeric'
  });

  const formAsis = document.getElementById('form-asistencia');
  const nombre = document.getElementById('nombre_completo');
  const cedula = document.getElementById('cedula');
  const horaEntrada = document.getElementById('hora_entrada');
  const horaSalida = document.getElementById('hora_salida');
  const observacion = document.getElementById('observacion');

  if (formAsis) {
    formAsis.addEventListener('submit', function(e) {
      if (nombre && !nombre.checkValidity()) {
        e.preventDefault();
        Swal.fire({ title: 'Nombre invalido', text: nombre.title || 'Revise el nombre', icon: 'warning' });
        return;
      }

      if (cedula && !cedula.checkValidity()) {
        e.preventDefault();
        Swal.fire({ title: 'Cédula invalida', text: cedula.title || 'Revise la cédula', icon: 'warning' });
        return;
      }

      if (!horaEntrada.value || !horaSalida.value) {
        e.preventDefault();
        Swal.fire({ title: 'Horas incompletas', text: 'Ingrese hora de entrada y salida.', icon: 'warning' });
        return;
      }

      if (horaEntrada.value >= horaSalida.value) {
        e.preventDefault();
        Swal.fire({ title: 'Horas invalidas', text: 'La hora de entrada debe ser anterior a la de salida.', icon: 'error' });
        return;
      }

      if (observacion && observacion.value.length > 255) {
        e.preventDefault();
        Swal.fire({ title: 'Observacion muy larga', text: 'Maximo 255 caracteres.', icon: 'warning' });
        return;
      }

      e.preventDefault();
      Swal.fire({
        title: 'Guardar asistencia',
        text: `¿Deseas registrar la asistencia para el ${spanFecha.textContent}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) formAsis.submit();
      });
    });
  }

  const formFin = document.getElementById('form-finalizar');
  if (formFin) {
    formFin.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Finalizar día',
        text: 'Esta acción moverá todos los registros al historial. ¿Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'No, cancelar'
      }).then((result) => {
        if (result.isConfirmed) formFin.submit();
      });
    });
  }

  if (nombre) {
    nombre.addEventListener('input', function() {
      this.value = this.value.replace(/[^A-Za-z\u00C0-\u017F\s]/g, '');
    });
  }

  if (cedula) {
    cedula.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '');
    });

    cedula.addEventListener('paste', function(e) {
      const pasted = (e.clipboardData || window.clipboardData).getData('text');
      if (/\D/.test(pasted)) {
        e.preventDefault();
        Swal.fire({ title: 'Formato invalido', text: 'Solo se permiten numeros en la cédula.', icon: 'warning' });
      }
    });
  }

  function formatForInput(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }
});
