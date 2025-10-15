document.getElementById('registerForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const birthdate = document.getElementById('birthdate').value;
    const gender = document.getElementById('gender').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (password !== confirmPassword) {
      alert('Las contraseñas no coinciden. Por favor, verifica y vuelve a intentar.');
      return;
    }

    alert(`Usuario registrado: ${username}\nCorreo Electrónico: ${email}\nTeléfono: ${phone}\nFecha de Nacimiento: ${birthdate}\nGénero: ${gender}`);
  });