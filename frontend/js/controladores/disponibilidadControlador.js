const DisponibilidadControlador = {
  disponibilidad: [],
  elementoConFoco: null,

  async inicializar() {
    setTimeout(async () => {
      if (!window.Layout?.requerirRol('mentor')) return;
      this.configurarEventos();
      await this.cargarDisponibilidad();
    }, 250);
  },

  configurarEventos() {
    document.getElementById('form-disponibilidad')?.addEventListener('submit', (e) => this.guardar(e));
    document.getElementById('form-editar-disponibilidad')?.addEventListener('submit', (e) => this.actualizar(e));
    document.querySelectorAll('#modal-editar-disponibilidad .modal-close, #modal-editar-disponibilidad .btn-cancelar-edicion')
      .forEach((boton) => boton.addEventListener('click', () => this.cerrarModalEdicion()));
    document.getElementById('modal-editar-disponibilidad')?.addEventListener('click', (e) => {
      if (e.target.id === 'modal-editar-disponibilidad') this.cerrarModalEdicion();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && document.getElementById('modal-editar-disponibilidad').classList.contains('active')) {
        this.cerrarModalEdicion();
      }
    });
    document.getElementById('tabla-disponibilidad')?.addEventListener('click', (e) => {
      const boton = e.target.closest('button[data-accion]');
      if (!boton) return;
      const id = Number(boton.dataset.id);
      if (boton.dataset.accion === 'editar') this.abrirEditar(id);
      if (boton.dataset.accion === 'eliminar') this.eliminar(id);
    });
  },

  mostrarAlerta(texto, error = false) {
    const alerta = document.getElementById('alerta-global');
    alerta.textContent = texto;
    alerta.className = `alert visible ${error ? 'alert-error' : 'alert-success'}`;
  },

  async cargarDisponibilidad() {
    try {
      const respuesta = await window.API.fetch('disponibilidad/disponibilidad.php');
      this.disponibilidad = respuesta.datos || [];
      this.renderizar();
    } catch (e) {
      this.mostrarAlerta(e.message, true);
    }
  },

  renderizar() {
    const body = document.getElementById('tabla-disponibilidad');
    if (!this.disponibilidad.length) {
      body.innerHTML = '<p class="availability-empty">Aún no has agregado franjas horarias.</p>';
      return;
    }
    body.innerHTML = this.disponibilidad.map((d) => `<article class="availability-item">
      <p><strong>${this.escape(this.capitalize(d.dia_semana))}:</strong> ${this.escape(d.hora_inicio)} - ${this.escape(d.hora_fin)}</p>
      <div class="availability-actions">
        <button class="btn btn-secondary btn-sm" data-accion="editar" data-id="${d.id}" type="button">Editar</button>
        <button class="btn btn-danger btn-sm" data-accion="eliminar" data-id="${d.id}" type="button">Eliminar</button>
      </div>
    </article>`).join('');
  },

  abrirEditar(id) {
    const d = this.disponibilidad.find((item) => Number(item.id) === id);
    if (!d) return;
    document.getElementById('editar-disponibilidad-id').value = d.id;
    document.getElementById('editar-disponibilidad-dia').value = d.dia_semana;
    document.getElementById('editar-disponibilidad-inicio').value = d.hora_inicio;
    document.getElementById('editar-disponibilidad-fin').value = d.hora_fin;
    document.getElementById('alerta-editar-disponibilidad').className = 'alert';
    this.elementoConFoco = document.activeElement;
    const modal = document.getElementById('modal-editar-disponibilidad');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.getElementById('editar-disponibilidad-dia').focus();
  },

  async guardar(e) {
    e.preventDefault();
    const datos = {
      dia_semana: document.getElementById('disponibilidad-dia').value,
      hora_inicio: document.getElementById('disponibilidad-inicio').value,
      hora_fin: document.getElementById('disponibilidad-fin').value,
    };
    try {
      await window.API.fetch('disponibilidad/disponibilidad.php', { method: 'POST', body: datos });
      document.getElementById('form-disponibilidad').reset();
      this.mostrarAlerta('Franja agregada.');
      await this.cargarDisponibilidad();
    } catch (e) {
      this.mostrarAlerta(e.message, true);
    }
  },

  async actualizar(e) {
    e.preventDefault();
    const id = document.getElementById('editar-disponibilidad-id').value;
    const datos = {
      dia_semana: document.getElementById('editar-disponibilidad-dia').value,
      hora_inicio: document.getElementById('editar-disponibilidad-inicio').value,
      hora_fin: document.getElementById('editar-disponibilidad-fin').value,
    };
    try {
      await window.API.fetch(`disponibilidad/disponibilidad.php?id=${id}`, { method: 'PUT', body: datos });
      this.cerrarModalEdicion(false);
      this.mostrarAlerta('Franja actualizada.');
      await this.cargarDisponibilidad();
      document.getElementById('btn-guardar-disponibilidad').focus();
    } catch (e) {
      const alerta = document.getElementById('alerta-editar-disponibilidad');
      alerta.textContent = e.message;
      alerta.className = 'alert visible alert-error';
    }
  },

  cerrarModalEdicion(restaurarFoco = true) {
    const modal = document.getElementById('modal-editar-disponibilidad');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('form-editar-disponibilidad').reset();
    if (restaurarFoco && this.elementoConFoco?.isConnected) this.elementoConFoco.focus();
    this.elementoConFoco = null;
  },

  async eliminar(id) {
    if (!confirm('¿Deseas eliminar este horario?')) return;
    try {
      await window.API.fetch(`disponibilidad/disponibilidad.php?id=${id}`, { method: 'DELETE' });
      this.mostrarAlerta('Horario eliminado.');
      await this.cargarDisponibilidad();
    } catch (e) {
      this.mostrarAlerta(e.message, true);
    }
  },

  escape(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
  },

  capitalize(texto) {
    return texto ? texto.charAt(0).toUpperCase() + texto.slice(1) : '';
  },
};

window.DisponibilidadControlador = DisponibilidadControlador;
document.addEventListener('DOMContentLoaded', () => DisponibilidadControlador.inicializar());
