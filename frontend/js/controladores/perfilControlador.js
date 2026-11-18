const PerfilControlador = {
  perfilActual: null,

  async inicializar() {
    const token = window.API?.obtenerToken();
    if (!token) {
      window.location.href = 'login.html';
      return;
    }

    this.configurarEventos();
    await this.cargarDatos();
  },

  async cargarDatos() {
    try {
      const datos = await window.PerfilModelo.obtenerPerfil();
      if (!datos || !datos.usuario) {
        throw new Error('No se pudo recuperar la información del perfil.');
      }

      this.perfilActual = datos;
      window.PerfilVista.renderizarDatosUsuario(datos.usuario);

      if (datos.usuario.rol === 'mentor') {
        window.PerfilVista.renderizarMaterias(
          datos.catalogo_materias || [],
          datos.materias_ids || []
        );
      }
    } catch (error) {
      window.PerfilVista.mostrarAlerta(
        error.message || 'Error al cargar los datos del perfil.',
        true
      );
    }
  },

  configurarEventos() {
    // 0. Alternar visibilidad de contraseñas
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

        const nuevoEstado = esPassword ? 'Ocultar contraseña' : 'Mostrar contraseña';
        btn.setAttribute('aria-label', nuevoEstado);
        btn.setAttribute('title', nuevoEstado);
      });
    });

    // 1. Envío del formulario de perfil (datos de cuenta y contraseña)
    const formPerfil = document.getElementById('form-perfil');
    if (formPerfil) {
      formPerfil.addEventListener('submit', async (e) => {
        e.preventDefault();
        await this.guardarPerfil();
      });
    }

    // 2. Envío del formulario de materias dominadas (solo mentores)
    const formMaterias = document.getElementById('form-materias');
    if (formMaterias) {
      formMaterias.addEventListener('submit', async (e) => {
        e.preventDefault();
        await this.guardarMaterias();
      });
    }

    // 3. Filtro en vivo para materias (mejora de usabilidad RNF4)
    const inputFiltro = document.getElementById('filtro-materias');
    if (inputFiltro) {
      inputFiltro.addEventListener('input', (e) => {
        const termino = e.target.value.toLowerCase().trim();
        const tarjetas = document.querySelectorAll('#contenedor-materias .materia-card-label');

        tarjetas.forEach((tarjeta) => {
          const texto = tarjeta.textContent.toLowerCase();
          tarjeta.style.display = texto.includes(termino) ? 'flex' : 'none';
        });
      });
    }

    // 4. Botones para selección rápida de materias
    document.getElementById('btn-seleccionar-todas')?.addEventListener('click', () => {
      const checkboxes = document.querySelectorAll('#contenedor-materias input[type="checkbox"]');
      checkboxes.forEach((cb) => {
        cb.checked = true;
        const label = cb.closest('label');
        if (label) {
          label.style.borderColor = 'var(--color-primary)';
          label.style.backgroundColor = 'color-mix(in srgb, var(--color-primary) 6%, var(--color-surface))';
        }
      });
      window.PerfilVista.actualizarContadorMaterias();
    });

    document.getElementById('btn-deseleccionar-todas')?.addEventListener('click', () => {
      const checkboxes = document.querySelectorAll('#contenedor-materias input[type="checkbox"]');
      checkboxes.forEach((cb) => {
        cb.checked = false;
        const label = cb.closest('label');
        if (label) {
          label.style.borderColor = 'var(--color-border)';
          label.style.backgroundColor = 'var(--color-surface)';
        }
      });
      window.PerfilVista.actualizarContadorMaterias();
    });
  },

  async guardarPerfil() {
    const nombre = document.getElementById('nombre')?.value.trim();
    const correo = document.getElementById('correo')?.value.trim();
    const semestre = document.getElementById('semestre')?.value;
    const password = document.getElementById('password')?.value;
    const passwordConfirm = document.getElementById('password_confirm')?.value;

    const rol = this.perfilActual?.usuario?.rol;

    // Validaciones del lado del cliente
    if (!nombre) {
      window.PerfilVista.mostrarAlerta('El nombre completo es obligatorio.', true);
      return;
    }

    if (!correo || !correo.includes('@')) {
      window.PerfilVista.mostrarAlerta('Por favor ingresa un correo electrónico válido.', true);
      return;
    }

    if (rol === 'estudiante') {
      const semestreNum = parseInt(semestre, 10);
      if (isNaN(semestreNum) || semestreNum < 1 || semestreNum > 12) {
        window.PerfilVista.mostrarAlerta('El semestre debe ser un número entero entre 1 y 12.', true);
        return;
      }
    }

    if (password && password.trim() !== '') {
      if (password.length < 8) {
        window.PerfilVista.mostrarAlerta('La nueva contraseña debe tener al menos 8 caracteres.', true);
        return;
      }

      if (password !== passwordConfirm) {
        window.PerfilVista.mostrarAlerta('Las contraseñas ingresadas no coinciden.', true);
        return;
      }
    }

    const payload = {
      nombre,
      correo,
    };

    if (rol === 'estudiante') {
      payload.semestre = parseInt(semestre, 10);
    }

    if (password && password.trim() !== '') {
      payload.password = password;
      payload.password_confirm = passwordConfirm;
    }

    window.PerfilVista.setBotonCargando(true, 'perfil');

    try {
      const res = await window.PerfilModelo.actualizarPerfil(payload);
      const datosActualizados = res?.datos;

      if (datosActualizados && datosActualizados.usuario) {
        this.perfilActual = datosActualizados;

        // Actualizar en localStorage y layout global
        const storageKey = window.API_CONFIG?.USER_KEY || 'studyplace_user';
        localStorage.setItem(storageKey, JSON.stringify(datosActualizados.usuario));

        if (window.Layout) {
          window.Layout.usuarioActual = datosActualizados.usuario;
        }

        if (window.MenuComponente) {
          window.MenuComponente.renderizar('menu-navegacion', datosActualizados.usuario);
        }

        window.PerfilVista.renderizarDatosUsuario(datosActualizados.usuario);
        window.PerfilVista.limpiarCamposPassword();
        window.PerfilVista.mostrarAlerta(
          res.mensaje || 'Datos de la cuenta actualizados exitosamente.',
          false
        );
      }
    } catch (error) {
      window.PerfilVista.mostrarAlerta(
        error.message || 'Error al actualizar el perfil.',
        true
      );
    } finally {
      window.PerfilVista.setBotonCargando(false, 'perfil');
    }
  },

  async guardarMaterias() {
    const materiasSeleccionadas = window.PerfilVista.obtenerMateriasSeleccionadas();
    const payload = {
      materias: materiasSeleccionadas,
    };

    window.PerfilVista.setBotonCargando(true, 'materias');

    try {
      const res = await window.PerfilModelo.actualizarPerfil(payload);
      const datosActualizados = res?.datos;

      if (datosActualizados) {
        this.perfilActual = datosActualizados;

        if (datosActualizados.usuario?.rol === 'mentor') {
          window.PerfilVista.renderizarMaterias(
            datosActualizados.catalogo_materias || [],
            datosActualizados.materias_ids || []
          );
        }

        window.PerfilVista.mostrarAlerta(
          'Materias dominadas actualizadas exitosamente.',
          false
        );
      }
    } catch (error) {
      window.PerfilVista.mostrarAlerta(
        error.message || 'Error al guardar materias dominadas.',
        true
      );
    } finally {
      window.PerfilVista.setBotonCargando(false, 'materias');
    }
  },
};

window.PerfilControlador = PerfilControlador;

document.addEventListener('DOMContentLoaded', () => {
  PerfilControlador.inicializar();
});
