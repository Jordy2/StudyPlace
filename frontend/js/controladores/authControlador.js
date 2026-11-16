document.addEventListener('DOMContentLoaded', () => {
  const alerta = document.getElementById('alerta');

  function mostrarAlerta(mensaje, tipo = 'error') {
    if (!alerta) return;
    alerta.textContent = mensaje;
    alerta.className = `alert alert-${tipo} visible`;
  }

  function limpiarAlerta() {
    if (!alerta) return;
    alerta.textContent = '';
    alerta.className = 'alert';
  }

  function setBotonCargando(boton, cargando, textoOriginal) {
    if (!boton) return;
    boton.disabled = cargando;
    boton.textContent = cargando ? 'Procesando...' : textoOriginal;
  }

  const formLogin = document.getElementById('form-login');
  if (formLogin) {
    const btnSubmit = document.getElementById('btn-submit');

    formLogin.addEventListener('submit', async (e) => {
      e.preventDefault();
      limpiarAlerta();

      const correo = document.getElementById('correo')?.value.trim();
      const password = document.getElementById('password')?.value;

      if (!correo || !password) {
        mostrarAlerta('Por favor completa todos los campos requeridos.', 'error');
        return;
      }

      setBotonCargando(btnSubmit, true, 'Iniciar Sesión');

      try {
        const respuesta = await API.fetch('auth/login.php', {
          method: 'POST',
          body: { correo, password },
        });

        if (respuesta && respuesta.datos?.token) {
          API.guardarToken(respuesta.datos.token);

          const userKey = window.API_CONFIG?.USER_KEY || 'studyplace_user';
          if (respuesta.datos.usuario) {
            localStorage.setItem(userKey, JSON.stringify(respuesta.datos.usuario));
          }

          mostrarAlerta('Inicio de sesión exitoso. Redirigiendo...', 'success');
          setTimeout(() => {
            window.location.href = '../index.html';
          }, 800);
        } else {
          throw new Error('Respuesta de sesión inválida.');
        }
      } catch (error) {
        mostrarAlerta(error.message || 'Error al iniciar sesión.', 'error');
        setBotonCargando(btnSubmit, false, 'Iniciar Sesión');
      }
    });
  }

  const formRegistro = document.getElementById('form-registro');
  if (formRegistro) {
    const btnSubmit = document.getElementById('btn-submit');
    const rolSelect = document.getElementById('rol');
    const grupoSemestre = document.getElementById('grupo-semestre');

    if (rolSelect && grupoSemestre) {
      rolSelect.addEventListener('change', () => {
        grupoSemestre.style.display = rolSelect.value === 'estudiante' ? 'flex' : 'none';
      });
    }

    formRegistro.addEventListener('submit', async (e) => {
      e.preventDefault();
      limpiarAlerta();

      const nombre = document.getElementById('nombre')?.value.trim();
      const correo = document.getElementById('correo')?.value.trim();
      const rol = document.getElementById('rol')?.value;
      const semestreInput = document.getElementById('semestre')?.value;
      const password = document.getElementById('password')?.value;
      const passwordConfirm = document.getElementById('password_confirm')?.value;

      if (!nombre || !correo || !rol || !password) {
        mostrarAlerta('Por favor completa todos los campos requeridos.', 'error');
        return;
      }

      if (password !== passwordConfirm) {
        mostrarAlerta('Las contraseñas ingresadas no coinciden.', 'error');
        return;
      }

      if (password.length < 8) {
        mostrarAlerta('La contraseña debe tener al menos 8 caracteres.', 'error');
        return;
      }

      const cuerpo = {
        nombre,
        correo,
        rol,
        password,
      };

      if (rol === 'estudiante') {
        const semestreNum = parseInt(semestreInput, 10);
        if (isNaN(semestreNum) || semestreNum < 1 || semestreNum > 12) {
          mostrarAlerta('El semestre debe ser un número entero entre 1 y 12.', 'error');
          return;
        }
        cuerpo.semestre = semestreNum;
      }

      setBotonCargando(btnSubmit, true, 'Registrarme');

      try {
        const respuesta = await API.fetch('auth/registro.php', {
          method: 'POST',
          body: cuerpo,
        });

        mostrarAlerta(
          respuesta.mensaje || 'Cuenta registrada exitosamente. Redirigiendo...',
          'success'
        );

        setTimeout(() => {
          window.location.href = 'login.html';
        }, 1200);
      } catch (error) {
        mostrarAlerta(error.message || 'Error al registrar la cuenta.', 'error');
        setBotonCargando(btnSubmit, false, 'Registrarme');
      }
    });
  }
});
