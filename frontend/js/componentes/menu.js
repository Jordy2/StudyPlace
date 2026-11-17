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
          { texto: 'Mis Reservas', url: rutas.reservas },
          { texto: 'Mis Logros', url: rutas.logros }
        );
        break;

      case 'mentor':
        items.push(
          { texto: 'Mi Disponibilidad', url: rutas.disponibilidad },
          { texto: 'Solicitudes', url: rutas.solicitudes },
          { texto: 'Mis Logros', url: rutas.logros }
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

    items.push({ texto: 'Perfil', url: rutas.perfil });

    return items;
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
      const liDivider = document.createElement('li');
      liDivider.innerHTML = '<div class="nav-divider"></div>';
      contenedor.appendChild(liDivider);

      const liInfo = document.createElement('li');
      liInfo.innerHTML = `
        <div class="nav-user">
          <span class="nav-user-nombre">${this.escaparHtml(usuario.nombre.split(' ')[0])}</span>
          <span class="badge badge-rol">${this.escaparHtml(usuario.rol)}</span>
        </div>
      `;
      contenedor.appendChild(liInfo);

      const liLogout = document.createElement('li');
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
    }
  },

  escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  },
};

window.MenuComponente = MenuComponente;
