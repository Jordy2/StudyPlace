const CatalogoControlador = {
  materiaEnEdicion: null,
  espacioEnEdicion: null,
  entidadAEliminar: null,

  async inicializar() {
    // 1. Verificar acceso: Solo administrador (RF6, RF8)
    const usuario = window.Layout?.obtenerUsuario();
    if (!usuario) {
      // Esperar brevemente si Layout aún está autenticando
      setTimeout(() => {
        if (!window.Layout?.requerirRol('administrador')) return;
        this.configurarEventos();
        this.cargarDatos();
      }, 250);
      return;
    }

    if (!window.Layout.requerirRol('administrador')) return;

    this.configurarEventos();
    await this.cargarDatos();
  },

  configurarEventos() {
    // Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        tabBtns.forEach((b) => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach((c) => c.classList.remove('active'));

        btn.classList.add('active');
        const targetId = btn.getAttribute('data-target');
        const targetContent = document.getElementById(targetId);
        if (targetContent) targetContent.classList.add('active');
      });
    });

    // Botones Abrir Modal Creación
    document.getElementById('btn-nueva-materia')?.addEventListener('click', () => {
      this.materiaEnEdicion = null;
      document.getElementById('form-materia').reset();
      document.getElementById('modal-materia-id').value = '';
      document.getElementById('modal-materia-titulo').textContent = 'Nueva Materia';
      window.CatalogoVista.abrirModal('modal-materia');
    });

    document.getElementById('btn-nuevo-espacio')?.addEventListener('click', () => {
      this.espacioEnEdicion = null;
      document.getElementById('form-espacio').reset();
      document.getElementById('modal-espacio-id').value = '';
      document.getElementById('modal-espacio-titulo').textContent = 'Nuevo Espacio';
      this.actualizarCamposTipoEspacio('fisico');
      window.CatalogoVista.abrirModal('modal-espacio');
    });

    // Cerrar Modales
    document.querySelectorAll('.modal-close, .btn-modal-cancelar').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        const modal = e.target.closest('.modal-overlay');
        if (modal) modal.classList.remove('active');
      });
    });

    // Cambio de tipo en Espacio (físico / virtual)
    const selectTipo = document.getElementById('espacio-tipo');
    selectTipo?.addEventListener('change', (e) => {
      this.actualizarCamposTipoEspacio(e.target.value);
    });

    // Submit Formularios
    document.getElementById('form-materia')?.addEventListener('submit', (e) => this.guardarMateria(e));
    document.getElementById('form-espacio')?.addEventListener('submit', (e) => this.guardarEspacio(e));

    // Confirmación de Borrado
    document.getElementById('btn-confirmar-eliminar')?.addEventListener('click', () => this.confirmarEliminacion());
  },

  actualizarCamposTipoEspacio(tipo) {
    const campoFisico = document.getElementById('grupo-espacio-fisico');
    const campoVirtual = document.getElementById('grupo-espacio-virtual');
    const inputCapacidad = document.getElementById('espacio-capacidad');
    const inputEnlace = document.getElementById('espacio-enlace');

    if (tipo === 'fisico') {
      if (campoFisico) campoFisico.style.display = 'block';
      if (campoVirtual) campoVirtual.style.display = 'none';
      if (inputCapacidad) inputCapacidad.required = true;
      if (inputEnlace) inputEnlace.required = false;
    } else {
      if (campoFisico) campoFisico.style.display = 'none';
      if (campoVirtual) campoVirtual.style.display = 'block';
      if (inputCapacidad) inputCapacidad.required = false;
      if (inputEnlace) inputEnlace.required = true;
    }
  },

  async cargarDatos() {
    await Promise.all([this.cargarMaterias(), this.cargarEspacios()]);
  },

  async cargarMaterias() {
    try {
      const materias = await window.CatalogoModelo.obtenerMaterias(true);
      window.CatalogoVista.renderizarMaterias(
        materias,
        (m) => this.abrirEditarMateria(m),
        (m) => this.solicitarEliminar('materia', m)
      );
    } catch (e) {
      window.CatalogoVista.mostrarAlerta(`Error al cargar materias: ${e.message}`, true);
    }
  },

  async cargarEspacios() {
    try {
      const espacios = await window.CatalogoModelo.obtenerEspacios(null, true);
      window.CatalogoVista.renderizarEspacios(
        espacios,
        (esp) => this.abrirEditarEspacio(esp),
        (esp) => this.solicitarEliminar('espacio', esp)
      );
    } catch (e) {
      window.CatalogoVista.mostrarAlerta(`Error al cargar espacios: ${e.message}`, true);
    }
  },

  abrirEditarMateria(m) {
    this.materiaEnEdicion = m;
    document.getElementById('modal-materia-id').value = m.id;
    document.getElementById('materia-codigo').value = m.codigo;
    document.getElementById('materia-nombre').value = m.nombre;
    document.getElementById('materia-descripcion').value = m.descripcion || '';
    document.getElementById('materia-estado').value = m.estado;
    document.getElementById('modal-materia-titulo').textContent = 'Editar Materia';
    window.CatalogoVista.abrirModal('modal-materia');
  },

  abrirEditarEspacio(e) {
    this.espacioEnEdicion = e;
    document.getElementById('modal-espacio-id').value = e.id;
    document.getElementById('espacio-nombre').value = e.nombre;
    document.getElementById('espacio-tipo').value = e.tipo;
    document.getElementById('espacio-capacidad').value = e.capacidad || '';
    document.getElementById('espacio-ubicacion').value = e.ubicacion || '';
    document.getElementById('espacio-enlace').value = e.enlace_virtual || '';
    document.getElementById('espacio-estado').value = e.estado;
    document.getElementById('modal-espacio-titulo').textContent = 'Editar Espacio';
    this.actualizarCamposTipoEspacio(e.tipo);
    window.CatalogoVista.abrirModal('modal-espacio');
  },

  solicitarEliminar(tipo, item) {
    this.entidadAEliminar = { tipo, id: item.id, nombre: item.nombre || item.codigo };
    const desc = document.getElementById('confirmar-eliminar-mensaje');
    if (desc) {
      desc.textContent = `¿Estás seguro de que deseas dar de baja o eliminar ${tipo === 'materia' ? 'la materia' : 'el espacio'} "${this.entidadAEliminar.nombre}"?`;
    }
    window.CatalogoVista.abrirModal('modal-confirmar-eliminar');
  },

  async confirmarEliminacion() {
    if (!this.entidadAEliminar) return;

    try {
      if (this.entidadAEliminar.tipo === 'materia') {
        await window.CatalogoModelo.eliminarMateria(this.entidadAEliminar.id);
        window.CatalogoVista.mostrarAlerta('Materia dada de baja exitosamente.');
        await this.cargarMaterias();
      } else {
        await window.CatalogoModelo.eliminarEspacio(this.entidadAEliminar.id);
        window.CatalogoVista.mostrarAlerta('Espacio dado de baja exitosamente.');
        await this.cargarEspacios();
      }
    } catch (e) {
      window.CatalogoVista.mostrarAlerta(e.message, true);
    } finally {
      this.entidadAEliminar = null;
      window.CatalogoVista.cerrarModal('modal-confirmar-eliminar');
    }
  },

  async guardarMateria(e) {
    e.preventDefault();
    const id = document.getElementById('modal-materia-id').value;
    const datos = {
      codigo: document.getElementById('materia-codigo').value,
      nombre: document.getElementById('materia-nombre').value,
      descripcion: document.getElementById('materia-descripcion').value,
      estado: document.getElementById('materia-estado').value,
    };

    try {
      if (id) {
        await window.CatalogoModelo.actualizarMateria(id, datos);
        window.CatalogoVista.mostrarAlerta('Materia actualizada correctamente.');
      } else {
        await window.CatalogoModelo.crearMateria(datos);
        window.CatalogoVista.mostrarAlerta('Materia creada correctamente.');
      }
      window.CatalogoVista.cerrarModal('modal-materia');
      await this.cargarMaterias();
    } catch (err) {
      window.CatalogoVista.mostrarAlerta(err.message, true);
    }
  },

  async guardarEspacio(e) {
    e.preventDefault();
    const id = document.getElementById('modal-espacio-id').value;
    const tipo = document.getElementById('espacio-tipo').value;
    const datos = {
      nombre: document.getElementById('espacio-nombre').value,
      tipo: tipo,
      capacidad: tipo === 'fisico' ? document.getElementById('espacio-capacidad').value : null,
      ubicacion: tipo === 'fisico' ? document.getElementById('espacio-ubicacion').value : null,
      enlace_virtual: tipo === 'virtual' ? document.getElementById('espacio-enlace').value : null,
      estado: document.getElementById('espacio-estado').value,
    };

    try {
      if (id) {
        await window.CatalogoModelo.actualizarEspacio(id, datos);
        window.CatalogoVista.mostrarAlerta('Espacio actualizado correctamente.');
      } else {
        await window.CatalogoModelo.crearEspacio(datos);
        window.CatalogoVista.mostrarAlerta('Espacio creado correctamente.');
      }
      window.CatalogoVista.cerrarModal('modal-espacio');
      await this.cargarEspacios();
    } catch (err) {
      window.CatalogoVista.mostrarAlerta(err.message, true);
    }
  },
};

window.CatalogoControlador = CatalogoControlador;

document.addEventListener('DOMContentLoaded', () => {
  CatalogoControlador.inicializar();
});
