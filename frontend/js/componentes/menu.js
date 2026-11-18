const MenuComponente = {
  obtenerRutas() {
    const esSubpagina = window.location.pathname.includes('/paginas/');
    const prefijoPaginas = esSubpagina ? './' : 'paginas/';
    const prefijoRaiz = esSubpagina ? '../' : './';

    return {
      inicio: `${prefijoRaiz}index.html`,
      login: `${prefijoPaginas}login.html`,
      registro: `${prefijoPaginas}registro.html`,
      catalogos: `${prefijoPaginas}catalogos.html`,
      perfil: `${prefijoPaginas}perfil.html`,
      buscar: `${prefijoPaginas}buscar.html`,
      reservas: `${prefijoPaginas}reservas.html`,
      logros: `${prefijoPaginas}logros.html`,
      disponibilidad: `${prefijoPaginas}disponibilidad.html`,
      solicitudes: `${prefijoPaginas}solicitudes.html`,
      usuarios: `${prefijoPaginas}usuarios.html`,
      auditoria: `${prefijoPaginas}auditoria.html`,
    };
  },

  obtenerElementosPorRol(usuario, rutas) {
    if (!usuario) {
      return [
        { texto: 'Inicio', url: rutas.inicio },
        { texto: 'Iniciar Sesión', url: rutas.login, clase: 'btn btn-secondary' },
        { texto: 'Registrarse', url: rutas.registro, clase: 'btn btn-primary' },
      ];
    }

    const items = [{ texto: 'Inicio', url: rutas.inicio }];

    switch (usuario.rol) {
      case 'estudiante':
        items.push(
          { texto: 'Buscar Mentores', url: rutas.buscar },
          { texto: 'Mis Reservas', url: rutas.reservas }
        );
        break;

      case 'mentor':
        items.push(
          { texto: 'Mi Disponibilidad', url: rutas.disponibilidad },
          { texto: 'Solicitudes', url: rutas.solicitudes }
        );
        break;

      case 'administrador':
        items.push(
          { texto: 'Catálogos', url: rutas.catalogos },
          { texto: 'Usuarios', url: rutas.usuarios },
          { texto: 'Auditoría', url: rutas.auditoria }
        );
        break;
    }

    return items;
  },

  obtenerIniciales(nombre) {
    if (!nombre) return 'U';
    return nombre
      .trim()
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0].toUpperCase())
      .join('');
  },

  renderizar(contenedorId = 'menu-navegacion', usuario = null) {
    const contenedor = document.getElementById(contenedorId);
    if (!contenedor) return;

    const rutas = this.obtenerRutas();
    const items = this.obtenerElementosPorRol(usuario, rutas);
    const rutaActual = window.location.pathname;

    contenedor.innerHTML = '';

    items.forEach((item) => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = item.url;
      a.textContent = item.texto;

      if (item.clase) {
        a.className = item.clase;
      } else {
        a.className = 'nav-link';
        const rutaLimpia = item.url.replace('./', '').replace('../', '');
        const esActivo = rutaActual.endsWith(rutaLimpia) ||
          (item.url.includes('index.html') && (rutaActual.endsWith('/') || rutaActual.endsWith('index.html')));

        if (esActivo) {
          a.classList.add('active');
        }
      }

      li.appendChild(a);
      contenedor.appendChild(li);
    });

    if (usuario) {
      const iniciales = this.obtenerIniciales(usuario.nombre);
      const primerNombre = (usuario.nombre || 'Usuario').trim().split(' ')[0];
      const rutaPerfil = rutas.perfil;
      const rutaLogros = rutas.logros;
      const esPerfilActivo = rutaActual.endsWith('perfil.html');
      const esLogrosActivo = rutaActual.endsWith('logros.html');

      // Botón circular con dropdown para agrupar Mi Perfil y Mis Logros
      const liProfile = document.createElement('li');
      liProfile.className = 'nav-item-profile dropdown-container';
      liProfile.innerHTML = `
        <button type="button" class="nav-avatar-btn ${esPerfilActivo || esLogrosActivo ? 'active' : ''}" id="btn-user-dropdown" aria-haspopup="true" aria-expanded="false" title="Mi Cuenta: ${this.escaparHtml(usuario.nombre)} (${this.escaparHtml(usuario.rol)})" aria-label="Menú de cuenta: perfil y logros">
          <span class="nav-avatar">${this.escaparHtml(iniciales)}</span>
        </button>
        <div class="user-dropdown-menu" id="user-dropdown-menu" role="menu" aria-labelledby="btn-user-dropdown">
          <div class="user-dropdown-header">
            <span class="dropdown-user-name">${this.escaparHtml(primerNombre)}</span>
            <span class="badge badge-rol">${this.escaparHtml(usuario.rol)}</span>
          </div>
          <div class="dropdown-divider"></div>
          <a href="${rutaPerfil}" class="dropdown-item ${esPerfilActivo ? 'active' : ''}" role="menuitem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Mi Perfil</span>
          </a>
          <a href="${rutaLogros}" class="dropdown-item ${esLogrosActivo ? 'active' : ''}" role="menuitem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="8" r="7"></circle>
              <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
            </svg>
            <span>Mis Logros</span>
          </a>
        </div>
      `;
      contenedor.appendChild(liProfile);

      // Línea divisoria colocada ANTES del botón salir (principio de proximidad)
      const liDivider = document.createElement('li');
      liDivider.className = 'nav-item-divider';
      liDivider.innerHTML = '<div class="nav-divider"></div>';
      contenedor.appendChild(liDivider);

      // Botón Salir con separación intencional
      const liLogout = document.createElement('li');
      liLogout.className = 'nav-item-logout';
      const btnLogout = document.createElement('button');
      btnLogout.type = 'button';
      btnLogout.className = 'btn btn-secondary nav-btn-logout';
      btnLogout.textContent = 'Salir';
      btnLogout.addEventListener('click', async () => {
        try {
          await window.API?.fetch('auth/logout.php', { method: 'POST' });
        } catch (_) {
        } finally {
          window.API?.eliminarToken();
          localStorage.removeItem(window.API_CONFIG?.USER_KEY || 'studyplace_user');
          window.location.href = rutas.login;
        }
      });
      liLogout.appendChild(btnLogout);
      contenedor.appendChild(liLogout);

      this.configurarDropdown();
    }
  },

  configurarDropdown() {
    const btn = document.getElementById('btn-user-dropdown');
    const menu = document.getElementById('user-dropdown-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const estaAbierto = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', estaAbierto ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && menu.classList.contains('open')) {
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
      }
    });
  },

  escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  },
};

window.MenuComponente = MenuComponente;
