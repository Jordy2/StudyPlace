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

  // --- Funcionalidad común: Alternar Visibilidad de Contraseñas ---
  function inicializarTogglePasswords() {
    document.querySelectorAll('.btn-toggle-password').forEach((btn) => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const esPassword = input.type === 'password';
        input.type = esPassword ? 'text' : 'password';

        const eyeOpen = btn.querySelector('.eye-open');
        const eyeClosed = btn.querySelector('.eye-closed');
        if (eyeOpen && eyeClosed) {
          eyeOpen.style.display = esPassword ? 'none' : 'block';
          eyeClosed.style.display = esPassword ? 'block' : 'none';
        }

        const estado = esPassword ? 'Ocultar contraseña' : 'Mostrar contraseña';
        btn.setAttribute('aria-label', estado);
        btn.setAttribute('title', estado);
      });
    });
  }

  inicializarTogglePasswords();

  // --- Formulario de Login ---
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

  // --- Formulario de Registro con Validaciones en Tiempo Real ---
  const formRegistro = document.getElementById('form-registro');
  if (formRegistro) {
    const btnSubmit = document.getElementById('btn-submit');
    const rolSelect = document.getElementById('rol');
    const grupoSemestre = document.getElementById('grupo-semestre');

    const inputNombre = document.getElementById('nombre');
    const inputCorreo = document.getElementById('correo');
    const inputSemestre = document.getElementById('semestre');
    const inputPassword = document.getElementById('password');
    const inputPasswordConfirm = document.getElementById('password_confirm');

    function setFeedback(campoId, mensaje, tipo = 'error') {
      const input = document.getElementById(campoId);
      const feedback = document.getElementById(`feedback-${campoId}`);

      if (input) {
        input.classList.remove('form-error', 'form-success');
        if (tipo === 'error') input.classList.add('form-error');
        else if (tipo === 'success') input.classList.add('form-success');
      }

      if (feedback) {
        feedback.textContent = mensaje || '';
        feedback.className = `form-feedback ${tipo}`;
      }
    }

    function limpiarFeedback(campoId) {
      const input = document.getElementById(campoId);
      const feedback = document.getElementById(`feedback-${campoId}`);
      if (input) input.classList.remove('form-error', 'form-success');
      if (feedback) {
        feedback.textContent = '';
        feedback.className = 'form-feedback';
      }
    }

    function validarEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validarCoincidenciaContraseñas() {
      const pass = inputPassword?.value || '';
      const passConfirm = inputPasswordConfirm?.value || '';

      if (pass.length === 0) {
        limpiarFeedback('password');
      } else if (pass.length < 8) {
        setFeedback('password', `La contraseña debe tener al menos 8 caracteres (llevas ${pass.length})`, 'error');
      } else {
        setFeedback('password', '✓ Longitud de contraseña correcta', 'success');
      }

      if (passConfirm.length === 0) {
        limpiarFeedback('password_confirm');
      } else if (pass !== passConfirm) {
        setFeedback('password_confirm', 'Las contraseñas ingresadas no coinciden', 'error');
      } else if (pass.length < 8) {
        setFeedback('password_confirm', 'Las contraseñas coinciden pero deben tener al menos 8 caracteres', 'error');
      } else {
        setFeedback('password_confirm', '✓ Las contraseñas coinciden', 'success');
      }
    }

    // Escuchadores en tiempo real
    inputNombre?.addEventListener('blur', () => {
      const val = inputNombre.value.trim();
      if (!val) {
        setFeedback('nombre', 'El nombre completo es obligatorio', 'error');
      } else {
        limpiarFeedback('nombre');
      }
    });

    inputCorreo?.addEventListener('input', () => {
      const val = inputCorreo.value.trim();
      if (!val) {
        limpiarFeedback('correo');
      } else if (!validarEmail(val)) {
        setFeedback('correo', 'Ingresa un formato de correo válido (ej. usuario@universidad.edu.ec)', 'error');
      } else {
        setFeedback('correo', '✓ Formato de correo válido', 'success');
      }
    });

    inputSemestre?.addEventListener('input', () => {
      if (rolSelect.value !== 'estudiante') return;
      const num = parseInt(inputSemestre.value, 10);
      if (isNaN(num) || num < 1 || num > 12) {
        setFeedback('semestre', 'El semestre debe ser un número entero entre 1 y 12', 'error');
      } else {
        limpiarFeedback('semestre');
      }
    });

    inputPassword?.addEventListener('input', validarCoincidenciaContraseñas);
    inputPasswordConfirm?.addEventListener('input', validarCoincidenciaContraseñas);

    if (rolSelect && grupoSemestre) {
      rolSelect.addEventListener('change', () => {
        const esEstudiante = rolSelect.value === 'estudiante';
        grupoSemestre.style.display = esEstudiante ? 'flex' : 'none';
        if (!esEstudiante) {
          limpiarFeedback('semestre');
        }
      });
    }

    // Validación al enviar el formulario
    formRegistro.addEventListener('submit', async (e) => {
      e.preventDefault();
      limpiarAlerta();

      const nombre = inputNombre?.value.trim();
      const correo = inputCorreo?.value.trim();
      const rol = rolSelect?.value;
      const semestreInput = inputSemestre?.value;
      const password = inputPassword?.value;
      const passwordConfirm = inputPasswordConfirm?.value;

      let tieneErrores = false;
      let primerCampoConError = null;

      // 1. Validar nombre
      if (!nombre) {
        setFeedback('nombre', 'El nombre completo es obligatorio', 'error');
        tieneErrores = true;
        if (!primerCampoConError) primerCampoConError = inputNombre;
      }

      // 2. Validar correo
      if (!correo || !validarEmail(correo)) {
        setFeedback('correo', 'Ingresa un correo electrónico válido', 'error');
        tieneErrores = true;
        if (!primerCampoConError) primerCampoConError = inputCorreo;
      }

      // 3. Validar semestre
      if (rol === 'estudiante') {
        const semestreNum = parseInt(semestreInput, 10);
        if (isNaN(semestreNum) || semestreNum < 1 || semestreNum > 12) {
          setFeedback('semestre', 'El semestre debe ser un número entre 1 y 12', 'error');
          tieneErrores = true;
          if (!primerCampoConError) primerCampoConError = inputSemestre;
        }
      }

      // 4. Validar contraseña
      if (!password || password.length < 8) {
        setFeedback('password', 'La contraseña debe tener al menos 8 caracteres', 'error');
        tieneErrores = true;
        if (!primerCampoConError) primerCampoConError = inputPassword;
      }

      // 5. Validar confirmación de contraseña
      if (!passwordConfirm || password !== passwordConfirm) {
        setFeedback('password_confirm', 'Las contraseñas ingresadas no coinciden', 'error');
        tieneErrores = true;
        if (!primerCampoConError) primerCampoConError = inputPasswordConfirm;
      }

      if (tieneErrores) {
        mostrarAlerta('Por favor corrige los campos señalados antes de continuar.', 'error');
        if (primerCampoConError) primerCampoConError.focus();
        return;
      }

      const cuerpo = {
        nombre,
        correo,
        rol,
        password,
      };

      if (rol === 'estudiante') {
        cuerpo.semestre = parseInt(semestreInput, 10);
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
