const UsuarioAdminVista = {
  ETIQUETAS_ACCION: {
    suspender_usuario: 'Suspender usuario',
    reactivar_usuario: 'Reactivar usuario',
    cambiar_rol: 'Cambiar rol',
  },

  escaparHtml(texto) {
    if (texto === null || texto === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(texto);
    return div.innerHTML;
  },

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

  abrirModal(modalId) {
    document.getElementById(modalId)?.classList.add('active');
  },

  cerrarModal(modalId) {
    document.getElementById(modalId)?.classList.remove('active');
  },

  renderizarUsuarios(usuarios, adminId, onSuspender, onReactivar, onCambiarRol) {
    const tbody = document.getElementById('tabla-usuarios-body');
    if (!tbody) return;

    if (!usuarios || usuarios.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--color-text-muted);">No se encontraron usuarios.</td></tr>';
      return;
    }

    tbody.innerHTML = '';
    usuarios.forEach((u) => {
      const id = Number(u.id);
      const badgeEstado = u.estado === 'activo' ? 'badge-activo' : 'badge-inactivo';
      const protegido = u.rol === 'administrador' || id === Number(adminId);
      const eliminado = u.estado === 'eliminado';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>${this.escaparHtml(u.nombre)}</strong></td>
        <td style="color: var(--color-text-muted);">${this.escaparHtml(u.correo)}</td>
        <td><span class="badge badge-rol">${this.escaparHtml(u.rol)}</span></td>
        <td>${this.escaparHtml(u.semestre ?? '-')}</td>
        <td><span class="badge ${badgeEstado}">${this.escaparHtml(u.estado)}</span></td>
        <td><div class="table-actions"></div></td>
      `;

      const acciones = tr.querySelector('.table-actions');

      if (protegido || eliminado) {
        acciones.innerHTML = '<span style="color: var(--color-text-muted);">-</span>';
      } else {
        const btnEstado = document.createElement('button');
        btnEstado.type = 'button';

        if (u.estado === 'suspendido') {
          btnEstado.className = 'btn btn-primary btn-sm';
          btnEstado.textContent = 'Reactivar';
          btnEstado.addEventListener('click', () => onReactivar(u));
        } else {
          btnEstado.className = 'btn btn-danger btn-sm';
          btnEstado.textContent = 'Suspender';
          btnEstado.addEventListener('click', () => onSuspender(u));
        }

        const btnRol = document.createElement('button');
        btnRol.type = 'button';
        btnRol.className = 'btn btn-secondary btn-sm';
        btnRol.textContent = 'Cambiar rol';
        btnRol.addEventListener('click', () => onCambiarRol(u));

        acciones.appendChild(btnEstado);
        acciones.appendChild(btnRol);
      }

      tbody.appendChild(tr);
    });
  },

  renderizarAuditoria(registros) {
    const tbody = document.getElementById('tabla-auditoria-body');
    if (!tbody) return;

    if (!registros || registros.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--color-text-muted);">Aún no hay acciones registradas.</td></tr>';
      return;
    }

    tbody.innerHTML = '';
    registros.forEach((r) => {
      const accion = this.ETIQUETAS_ACCION[r.accion] || r.accion;
      const afectado = r.afectado_nombre
        ? this.escaparHtml(r.afectado_nombre)
        : '<span style="color: var(--color-text-muted);">-</span>';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="white-space: nowrap;">${this.escaparHtml(r.created_at)}</td>
        <td>${this.escaparHtml(r.admin_nombre)}</td>
        <td><span class="badge badge-rol">${this.escaparHtml(accion)}</span></td>
        <td>${afectado}</td>
        <td style="color: var(--color-text-muted);">${this.escaparHtml(r.detalle || '-')}</td>
      `;
      tbody.appendChild(tr);
    });
  },
};

window.UsuarioAdminVista = UsuarioAdminVista;
