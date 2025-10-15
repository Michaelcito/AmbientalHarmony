function loadHomePage() {
  const ts = Date.now();
  const frame = document.querySelector('iframe[name="contentFrame"]');
  if (frame) frame.src = 'modulos_inicio.php?ts=' + ts;
}

function openProfile() {
  const frame = document.querySelector('iframe[name="contentFrame"]');
  if (frame) frame.src = 'perfil.php';
}

function openUserManagement() {
  const frame = document.querySelector('iframe[name="contentFrame"]');
  if (frame) frame.src = 'gestionar usuarios.php';
}

function openConfig(event) {
  if (event) event.preventDefault();
  const frame = document.querySelector('iframe[name="contentFrame"]');
  if (frame) frame.src = 'ayuda_soporte.php';
}

function confirmarCierreSesion(event) {
  if (event) event.preventDefault();
  document.getElementById('logoutModal').style.display = 'flex';
}

function cerrarModal() {
  document.getElementById('logoutModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.dropdown-details').forEach(container => {
    const btn = container.querySelector('.summary-btn');
    const submenu = container.querySelector('.submenu');
    const chevron = btn.querySelector('.chevron');

    btn.addEventListener('click', () => {
      if (container.classList.contains('open')) {
        container.classList.remove('open');
        submenu.style.maxHeight = null;
        chevron.classList.remove('rotate');
      } else {
        document.querySelectorAll('.dropdown-details.open').forEach(openContainer => {
          openContainer.classList.remove('open');
          openContainer.querySelector('.submenu').style.maxHeight = null;
          openContainer.querySelector('.chevron').classList.remove('rotate');
        });
        container.classList.add('open');
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        chevron.classList.add('rotate');
      }
    });
  });
});

const loadingOverlay = document.getElementById('loading-overlay');
const iframe = document.querySelector('iframe[name="contentFrame"]');

function hideOverlay() {
  if (!loadingOverlay) return;
  loadingOverlay.style.opacity = '0';
  setTimeout(() => {
    loadingOverlay.style.display = 'none';
  }, 500);
}

document.querySelectorAll('.menu a').forEach(link => {
  link.addEventListener('click', () => {
    if (!loadingOverlay) return;
    loadingOverlay.style.display = 'flex';
    loadingOverlay.style.opacity = '1';
    const timeoutId = setTimeout(() => {
      hideOverlay();
    }, 5000);
    if (iframe) {
      iframe.onload = () => {
        clearTimeout(timeoutId);
        hideOverlay();
        iframe.onload = null;
      };
    }
  });
});

window.addEventListener('load', () => {
  hideOverlay();
});
