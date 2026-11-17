const CatalogoVista = {
  mostrarAlerta(mensaje, esError = false) {
    const alerta = document.getElementById('alerta-global');
    if (!alerta) return;

    alerta.textContent = mensaje;
    alerta.className = `alert visible ${esError ? 'alert-error' : 'alert-success'}`;
    setTimeout(() => {
      alerta.className = 'alert';
      alerta.textContent = '';
    }, 4500);
  },

  renderizarMaterias(materias, onEditar, onEliminar) {
    const tbody = document.getElementById('tabla-materias-body');
    if (!tbody) return;

    if (!materias || materias.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--color-text-muted);">No hay materias registradas.</td></tr>';
      return;
    }

    tbody.innerHTML = '';
    materias.forEach((m) => {
      const tr = document.createElement('tr');
      const badgeClass = m.estado === 'activo' ? 'badge-activo' : 'badge-inactivo';

      tr.innerHTML = `
        <td><strong>${this.escaparHtml(m.codigo)}</strong></td>
        <td>${this.escaparHtml(m.nombre)}</td>
        <td style="color: var(--color-text-muted);">${this.escaparHtml(m.descripcion || '-')}</td>
        <td><span class="badge ${badgeClass}">${m.estado}</span></td>
        <td>
          <div class="table-actions">
            <button class="btn btn-secondary btn-sm btn-editar-materia" data-id="${m.id}">Editar</button>
            <button class="btn btn-danger btn-sm btn-eliminar-materia" data-id="${m.id}">Eliminar</button>
          </div>
        </td>
      `;

      tr.querySelector('.btn-editar-materia').addEventListener('click', () => onEditar(m));
      tr.querySelector('.btn-eliminar-materia').addEventListener('click', () => onEliminar(m));
      tbody.appendChild(tr);
    });
  },

  renderizarEspacios(espacios, onEditar, onEliminar) {
    const tbody = document.getElementById('tabla-espacios-body');
    if (!tbody) return;

    if (!espacios || espacios.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--color-text-muted);">No hay espacios registrados.</td></tr>';
      return;
    }

    tbody.innerHTML = '';
    espacios.forEach((e) => {
      const badgeTipo = e.tipo === 'fisico' ? 'badge-fisico' : 'badge-virtual';
      const badgeEstado = e.estado === 'activo' ? 'badge-activo' : 'badge-inactivo';

      let detalleHtml = '-';
      if (e.tipo === 'fisico') {
        detalleHtml = `Capacidad: <strong>${e.capacidad ?? '-'} personas</strong>`;
      } else if (e.enlace_virtual) {
        detalleHtml = `<a href="${this.escaparHtml(e.enlace_virtual)}" target="_blank" rel="noopener">Abrir Enlace Virtual &nearr;</a>`;
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>${this.escaparHtml(e.nombre)}</strong></td>
        <td><span class="badge ${badgeTipo}">${e.tipo}</span></td>
        <td>${detalleHtml}</td>
        <td style="color: var(--color-text-muted);">${this.escaparHtml(e.ubicacion || '-')}</td>
        <td><span class="badge ${badgeEstado}">${e.estado}</span></td>
        <td>
          <div class="table-actions">
            <button class="btn btn-secondary btn-sm btn-editar-espacio" data-id="${e.id}">Editar</button>
            <button class="btn btn-danger btn-sm btn-eliminar-espacio" data-id="${e.id}">Eliminar</button>
          </div>
        </td>
      `;

      tr.querySelector('.btn-editar-espacio').addEventListener('click', () => onEditar(e));
      tr.querySelector('.btn-eliminar-espacio').addEventListener('click', () => onEliminar(e));
      tbody.appendChild(tr);
    });
  },

  abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
  },

  cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
  },

  escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  },
};

window.CatalogoVista = CatalogoVista;
