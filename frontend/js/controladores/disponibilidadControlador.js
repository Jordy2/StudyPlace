const DisponibilidadControlador = {
  materias: [],
  disponibilidad: [],

  async inicializar() {
    setTimeout(async () => {
      if (!window.Layout?.requerirRol('mentor')) return;
      this.configurarEventos();
      await this.cargarMaterias();
      await this.cargarDisponibilidad();
    }, 250);
  },

  configurarEventos() {
    document.getElementById('btn-nueva-disponibilidad')?.addEventListener('click', () => this.abrirNuevo());
    document.querySelectorAll('#modal-disponibilidad .modal-close, #modal-disponibilidad .btn-modal-cancelar')
      .forEach((b) => b.addEventListener('click', () => document.getElementById('modal-disponibilidad').classList.remove('active')));
    document.getElementById('form-disponibilidad')?.addEventListener('submit', (e) => this.guardar(e));
  },

  mostrarAlerta(texto, error = false) {
    const alerta = document.getElementById('alerta-global');
    alerta.textContent = texto;
    alerta.className = `alert visible ${error ? 'alert-error' : 'alert-success'}`;
  },

  async cargarMaterias() {
    const respuesta = await window.API.fetch('catalogos/materias.php');
    this.materias = respuesta.datos || [];
    const select = document.getElementById('disponibilidad-materia');
    select.innerHTML = '<option value="">Selecciona una materia</option>';
    this.materias.forEach((m) => select.insertAdjacentHTML('beforeend', `<option value="${m.id}">${this.escape(m.codigo)} - ${this.escape(m.nombre)}</option>`));
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
      body.innerHTML = '<tr><td colspan="5" style="text-align:center">No tienes horarios configurados.</td></tr>';
      return;
    }
    body.innerHTML = this.disponibilidad.map((d) => `<tr>
      <td>${this.escape(d.materia_codigo)} - ${this.escape(d.materia_nombre)}</td>
      <td>${this.escape(d.dia_semana)}</td><td>${d.hora_inicio}</td><td>${d.hora_fin}</td>
      <td><div class="table-actions">
        <button class="btn btn-secondary btn-editar" data-id="${d.id}" type="button">Editar</button>
        <button class="btn btn-danger btn-eliminar" data-id="${d.id}" type="button">Eliminar</button>
      </div></td></tr>`).join('');
    body.querySelectorAll('.btn-editar').forEach((b) => b.addEventListener('click', () => this.abrirEditar(Number(b.dataset.id))));
    body.querySelectorAll('.btn-eliminar').forEach((b) => b.addEventListener('click', () => this.eliminar(Number(b.dataset.id))));
  },

  abrirNuevo() {
    document.getElementById('form-disponibilidad').reset();
    document.getElementById('disponibilidad-id').value = '';
    document.getElementById('modal-disponibilidad-titulo').textContent = 'Nuevo horario';
    document.getElementById('modal-disponibilidad').classList.add('active');
  },

  abrirEditar(id) {
    const d = this.disponibilidad.find((item) => item.id === id);
    if (!d) return;
    document.getElementById('disponibilidad-id').value = d.id;
    document.getElementById('disponibilidad-materia').value = d.materia_id;
    document.getElementById('disponibilidad-dia').value = d.dia_semana;
    document.getElementById('disponibilidad-inicio').value = d.hora_inicio;
    document.getElementById('disponibilidad-fin').value = d.hora_fin;
    document.getElementById('modal-disponibilidad-titulo').textContent = 'Editar horario';
    document.getElementById('modal-disponibilidad').classList.add('active');
  },

  async guardar(e) {
    e.preventEvent?.();
    e.preventDefault();
    const id = document.getElementById('disponibilidad-id').value;
    const datos = {
      materia_id: Number(document.getElementById('disponibilidad-materia').value),
      dia_semana: document.getElementById('disponibilidad-dia').value,
      hora_inicio: document.getElementById('disponibilidad-inicio').value,
      hora_fin: document.getElementById('disponibilidad-fin').value,
    };
    try {
      await window.API.fetch(`disponibilidad/disponibilidad.php${id ? `?id=${id}` : ''}`, {
        method: id ? 'PUT' : 'POST',
        body: datos,
      });
      document.getElementById('modal-disponibilidad').classList.remove('active');
      this.mostrarAlerta(id ? 'Horario actualizado.' : 'Horario creado.');
      await this.cargarDisponibilidad();
    } catch (e) {
      this.mostrarAlerta(e.message, true);
    }
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
};

window.DisponibilidadControlador = DisponibilidadControlador;
document.addEventListener('DOMContentLoaded', () => DisponibilidadControlador.inicializar());
