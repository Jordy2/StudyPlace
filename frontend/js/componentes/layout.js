const Layout = {
  usuarioActual: null,

  async inicializar() {
    const token = window.API?.obtenerToken();
    const storageKey = window.API_CONFIG?.USER_KEY || 'studyplace_user';

    if (token) {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        try {
          this.usuarioActual = JSON.parse(stored);
        } catch (_) {
          this.usuarioActual = null;
        }
      }

      if (!this.usuarioActual) {
        try {
          const res = await window.API?.fetch('auth/me.php');
          if (res?.datos?.usuario) {
            this.usuarioActual = res.datos.usuario;
            localStorage.setItem(storageKey, JSON.stringify(this.usuarioActual));
          }
        } catch (_) {
          this.usuarioActual = null;
          window.API?.eliminarToken();
          localStorage.removeItem(storageKey);
        }
      }
    }

    if (window.MenuComponente) {
      window.MenuComponente.renderizar('menu-navegacion', this.usuarioActual);
    }

    this.configurarMenuMovil();
  },

  configurarMenuMovil() {
    const btnToggle = document.getElementById('btn-menu-toggle');
    const navLinks = document.getElementById('menu-navegacion');

    if (btnToggle && navLinks) {
      btnToggle.addEventListener('click', () => {
        navLinks.classList.toggle('open');
      });
    }
  },

  obtenerUsuario() {
    return this.usuarioActual;
  },

  requerirRol(rolesPermitidos, redireccion = 'login.html') {
    const roles = Array.isArray(rolesPermitidos) ? rolesPermitidos : [rolesPermitidos];
    if (!this.usuarioActual || !roles.includes(this.usuarioActual.rol)) {
      const esSubpagina = window.location.pathname.includes('/paginas/');
      const rutaLogin = esSubpagina ? redireccion : `paginas/${redireccion}`;
      window.location.href = rutaLogin;
      return false;
    }
    return true;
  },
};

window.Layout = Layout;

document.addEventListener('DOMContentLoaded', () => {
  Layout.inicializar();
});
