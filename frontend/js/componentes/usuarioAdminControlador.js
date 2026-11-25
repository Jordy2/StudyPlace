const UsuarioAdminControlador = {
  accionPendiente: null,

  async inicializar() {
    // Solo el administrador puede ver estas páginas (RF4, RF17, RF19)
    if (!window.Layout?.obtenerUsuario()) {
      // Layout puede seguir verificando la sesión
      setTimeout(() => this.arrancar(), 250);
      return;
    }

    this.arrancar();
  },

  async arrancar() {
    if (!window.Layout?.requerirRol('administrador')) return;

    if (document.getElementById('tabla-usuarios-body')) {
      this.configurarEventosUsuarios();
      await this.cargarUsuarios();
    }

    if (document.getElementById('tabla-auditoria-body')) {
      await this.cargarAuditoria();
    }
  },

  configurarEventosUsuarios() {
    document.getElementById('form-filtros')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.cargarUsuarios();
    });

    document.querySelectorAll('.modal-close, .btn-modal-cancelar').forEach((btn) => {
      btn.addEventListener('click', () => {
        window.UsuarioAdminVista.cerrarModal('modal-confirmar-accion');
        this.accionPendiente = null;
      });
    });

    document.getElementById('btn-confirmar-accion')?.addEventListener('click', () => this.confirmarAccion());
  },

  async cargarUsuarios() {
    const filtros = {
      q: document.getElementById('filtro-q')?.value.trim() || '',
      rol: document.getElementById('filtro-rol')?.value || '',
      estado: document.getElementById('filtro-estado')?.value || '',
    };

    try {
      const usuarios = await window.UsuarioAdminModelo.listarUsuarios(filtros);
      const admin = window.Layout.obtenerUsuario();

      window.UsuarioAdminVista.renderizarUsuarios(
        usuarios,
        admin?.id,
        (u) => this.pedirConfirmacion('suspender', u),
        (u) => this.pedirConfirmacion('reactivar', u),
        (u) => this.pedirConfirmacion('cambiar_rol', u)
      );
    } catch (e) {
      window.UsuarioAdminVista.mostrarAlerta(`Error al cargar usuarios: ${e.message}`, true);
    }
  },

  async cargarAuditoria() {
    try {
      const registros = await window.UsuarioAdminModelo.listarAuditoria(100);
      window.UsuarioAdminVista.renderizarAuditoria(registros);
    } catch (e) {
      window.UsuarioAdminVista.mostrarAlerta(`Error al cargar la auditoría: ${e.message}`, true);
    }
  },

  pedirConfirmacion(accion, usuario) {
    this.accionPendiente = { accion, usuario };

    const mensaje = document.getElementById('confirmar-accion-mensaje');
    const grupoRol = document.getElementById('grupo-nuevo-rol');
    const selectRol = document.getElementById('nuevo-rol');
    const boton = document.getElementById('btn-confirmar-accion');
    const nombre = `${usuario.nombre} (${usuario.correo})`;

    grupoRol.style.display = 'none';
    boton.className = 'btn btn-danger';

    if (accion === 'suspender') {
      mensaje.textContent = `¿Seguro que deseas suspender a ${nombre}? No podrá iniciar sesión hasta que lo reactives.`;
      boton.textContent = 'Suspender';
    } else if (accion === 'reactivar') {
      mensaje.textContent = `¿Seguro que deseas reactivar a ${nombre}?`;
      boton.textContent = 'Reactivar';
      boton.className = 'btn btn-primary';
    } else {
      mensaje.textContent = `Selecciona el nuevo rol para ${nombre}. Su sesión se cerrará para aplicar el cambio.`;
      boton.textContent = 'Cambiar rol';
      boton.className = 'btn btn-primary';
      selectRol.value = usuario.rol === 'mentor' ? 'estudiante' : 'mentor';
      grupoRol.style.display = 'block';
    }

    window.UsuarioAdminVista.abrirModal('modal-confirmar-accion');
  },

  async confirmarAccion() {
    if (!this.accionPendiente) return;

    const { accion, usuario } = this.accionPendiente;
    const extra = accion === 'cambiar_rol'
      ? { rol: document.getElementById('nuevo-rol').value }
      : {};

    const boton = document.getElementById('btn-confirmar-accion');
    boton.disabled = true;

    try {
      const res = await window.UsuarioAdminModelo.ejecutarAccion(usuario.id, accion, extra);
      window.UsuarioAdminVista.cerrarModal('modal-confirmar-accion');
      window.UsuarioAdminVista.mostrarAlerta(res?.mensaje || 'Acción realizada correctamente.');
      this.accionPendiente = null;
      await this.cargarUsuarios();
    } catch (e) {
      window.UsuarioAdminVista.cerrarModal('modal-confirmar-accion');
      window.UsuarioAdminVista.mostrarAlerta(e.message, true);
    } finally {
      boton.disabled = false;
    }
  },
};

window.UsuarioAdminControlador = UsuarioAdminControlador;

document.addEventListener('DOMContentLoaded', () => {
  UsuarioAdminControlador.inicializar();
});
