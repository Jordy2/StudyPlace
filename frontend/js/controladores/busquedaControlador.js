const BusquedaControlador = {
  temporizador: null,

  async inicializar() {
    setTimeout(async () => {
      if (!window.Layout?.requerirRol(['estudiante','mentor','administrador'])) return;
      await this.cargarMaterias();
      document.getElementById('buscar-materia')?.addEventListener('change', () => this.buscar());
      document.getElementById('buscar-dia')?.addEventListener('change', () => this.buscar());
      await this.buscar();
    }, 250);
  },

  async cargarMaterias() {
    const respuesta = await window.API.fetch('catalogos/materias.php');
    const select = document.getElementById('buscar-materia');
    (respuesta.datos || []).forEach((m) => {
      select.insertAdjacentHTML('beforeend', `<option value="${m.id}">${this.escape(m.codigo)} - ${this.escape(m.nombre)}</option>`);
    });
  },

  async buscar() {
    clearTimeout(this.temporizador);
    this.temporizador = setTimeout(async () => {
      const materia = document.getElementById('buscar-materia').value;
      const dia = document.getElementById('buscar-dia').value;
      const params = new URLSearchParams();
      if (materia) params.set('materia_id', materia);
      if (dia) params.set('dia', dia);
      try {
        const respuesta = await window.API.fetch(`busqueda/mentores.php${params.size ? `?${params}` : ''}`);
        this.renderizar(respuesta.datos || []);
      } catch (e) {
        this.mostrarError(e.message);
      }
    }, 180);
  },

  renderizar(mentores) {
    const contenedor = document.getElementById('resultados-busqueda');
    if (!mentores.length) {
      contenedor.innerHTML = '<p class="search-message">No se encontraron mentores con esos filtros.</p>';
      return;
    }
    contenedor.innerHTML = mentores.map((m) => {
      const materias = (m.materias || []).map((materia) =>
        `<span class="mentor-tag mentor-tag-materia">${this.escape(materia.codigo)} - ${this.escape(materia.nombre)}</span>`
      ).join('');
      const horarios = (m.horarios || []).map((h) =>
        `<span class="mentor-tag mentor-tag-horario">${this.capitalize(this.escape(h.dia_semana))}: ${this.escape(h.hora_inicio)} - ${this.escape(h.hora_fin)}</span>`
      ).join('');
      return `
        <article class="mentor-card">
          <h3>${this.escape(m.nombre)}</h3>
          <p class="mentor-description">${m.semestre ? `Semestre ${this.escape(String(m.semestre))}` : 'Mentor académico'}</p>
          <h4>Materias que domina (M2):</h4>
          <div class="mentor-tags">${materias}</div>
          <h4>Disponibilidad (M4):</h4>
          <div class="mentor-tags">${horarios}</div>
        </article>`;
    }).join('');
  },

  mostrarError(mensaje) {
    const alerta = document.getElementById('alerta-busqueda');
    alerta.textContent = mensaje;
    alerta.className = 'alert visible alert-error';
  },

  capitalize(texto) {
    return texto ? texto.charAt(0).toUpperCase() + texto.slice(1) : '';
  },

  escape(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
  },
};

window.BusquedaControlador = BusquedaControlador;
document.addEventListener('DOMContentLoaded', () => BusquedaControlador.inicializar());
