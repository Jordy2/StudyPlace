const PerfilVista = {
  mostrarAlerta(mensaje, esError = false) {
    const alerta = document.getElementById('alerta-global');
    if (!alerta) return;

    alerta.textContent = mensaje;
    alerta.className = `alert visible ${esError ? 'alert-error' : 'alert-success'}`;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    setTimeout(() => {
      alerta.className = 'alert';
      alerta.textContent = '';
    }, 4500);
  },

  renderizarDatosUsuario(usuario) {
    if (!usuario) return;

    const nombreInput = document.getElementById('nombre');
    const correoInput = document.getElementById('correo');
    const rolInput = document.getElementById('rol');
    const semestreInput = document.getElementById('semestre');

    if (nombreInput) nombreInput.value = usuario.nombre || '';
    if (correoInput) correoInput.value = usuario.correo || '';
    if (rolInput) rolInput.value = usuario.rol ? usuario.rol.toUpperCase() : '';
    if (semestreInput && usuario.semestre) semestreInput.value = usuario.semestre;

    // Resumen en cabecera de perfil
    const elNombre = document.getElementById('resumen-nombre');
    const elCorreo = document.getElementById('resumen-correo');
    const elRol = document.getElementById('resumen-rol');
    const elAvatar = document.getElementById('resumen-avatar');

    if (elNombre) elNombre.textContent = usuario.nombre;
    if (elCorreo) elCorreo.textContent = usuario.correo;
    if (elRol) {
      elRol.textContent = usuario.rol;
      elRol.className = `badge badge-rol`;
    }
    if (elAvatar) {
      const iniciales = (usuario.nombre || 'U')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0].toUpperCase())
        .join('');
      elAvatar.textContent = iniciales;
    }

    this.configurarCamposPorRol(usuario.rol);
  },

  configurarCamposPorRol(rol) {
    const grupoSemestre = document.getElementById('grupo-semestre');
    const seccionMaterias = document.getElementById('seccion-materias');

    if (rol === 'estudiante') {
      if (grupoSemestre) grupoSemestre.style.display = 'flex';
      if (seccionMaterias) seccionMaterias.style.display = 'none';
    } else if (rol === 'mentor') {
      if (grupoSemestre) grupoSemestre.style.display = 'none';
      if (seccionMaterias) seccionMaterias.style.display = 'block';
    } else {
      if (grupoSemestre) grupoSemestre.style.display = 'none';
      if (seccionMaterias) seccionMaterias.style.display = 'none';
    }
  },

  renderizarMaterias(catalogo, materiasDominadasIds = []) {
    const contenedor = document.getElementById('contenedor-materias');
    if (!contenedor) return;

    if (!catalogo || catalogo.length === 0) {
      contenedor.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 1rem; text-align: center; color: var(--color-text-muted);">
          No hay materias activas en el catálogo general.
        </div>
      `;
      this.actualizarContadorMaterias(0);
      return;
    }

    contenedor.innerHTML = '';
    const idsSet = new Set(materiasDominadasIds.map((id) => Number(id)));

    catalogo.forEach((materia) => {
      const label = document.createElement('label');
      label.className = 'materia-card-label';
      label.style.display = 'flex';
      label.style.alignItems = 'flex-start';
      label.style.gap = '0.75rem';
      label.style.padding = '0.75rem 1rem';
      label.style.background = 'var(--color-surface)';
      label.style.border = '1px solid var(--color-border)';
      label.style.borderRadius = 'var(--radius)';
      label.style.cursor = 'pointer';
      label.style.transition = 'all 0.15s ease';

      const isChecked = idsSet.has(Number(materia.id));

      label.innerHTML = `
        <input
          type="checkbox"
          name="materias[]"
          value="${materia.id}"
          ${isChecked ? 'checked' : ''}
          style="margin-top: 0.25rem; cursor: pointer; accent-color: var(--color-primary);"
        >
        <div style="flex: 1;">
          <div style="font-weight: 600; font-size: 0.875rem; color: var(--color-text);">
            ${this.escaparHtml(materia.nombre)}
          </div>
          <div style="font-size: 0.75rem; color: var(--color-text-muted);">
            Código: <strong>${this.escaparHtml(materia.codigo)}</strong>
          </div>
          ${materia.descripcion ? `<div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;">${this.escaparHtml(materia.descripcion)}</div>` : ''}
        </div>
      `;

      const checkbox = label.querySelector('input[type="checkbox"]');
      checkbox.addEventListener('change', () => {
        if (checkbox.checked) {
          label.style.borderColor = 'var(--color-primary)';
          label.style.backgroundColor = 'color-mix(in srgb, var(--color-primary) 6%, var(--color-surface))';
        } else {
          label.style.borderColor = 'var(--color-border)';
          label.style.backgroundColor = 'var(--color-surface)';
        }
        this.actualizarContadorMaterias();
      });

      if (isChecked) {
        label.style.borderColor = 'var(--color-primary)';
        label.style.backgroundColor = 'color-mix(in srgb, var(--color-primary) 6%, var(--color-surface))';
      }

      contenedor.appendChild(label);
    });

    this.actualizarContadorMaterias();
  },

  actualizarContadorMaterias(cantidad = null) {
    const elContador = document.getElementById('contador-materias');
    if (!elContador) return;

    const total = cantidad !== null
      ? cantidad
      : document.querySelectorAll('#contenedor-materias input[type="checkbox"]:checked').length;

    elContador.textContent = `${total} ${total === 1 ? 'materia seleccionada' : 'materias seleccionadas'}`;
  },

  obtenerMateriasSeleccionadas() {
    const checkboxes = document.querySelectorAll('#contenedor-materias input[name="materias[]"]:checked');
    return Array.from(checkboxes).map((cb) => parseInt(cb.value, 10));
  },

  limpiarCamposPassword() {
    const pass = document.getElementById('password');
    const passConfirm = document.getElementById('password_confirm');
    if (pass) pass.value = '';
    if (passConfirm) passConfirm.value = '';
  },

  setBotonCargando(cargando, tipo = 'perfil') {
    const btnId = tipo === 'materias' ? 'btn-submit-materias' : 'btn-submit-perfil';
    const btn = document.getElementById(btnId);
    if (!btn) return;

    btn.disabled = cargando;
    if (tipo === 'materias') {
      btn.textContent = cargando ? 'Guardando materias...' : 'Guardar Materias Dominadas';
    } else {
      btn.textContent = cargando ? 'Guardando cambios...' : 'Guardar Cambios';
    }
  },

  escaparHtml(texto) {
    if (!texto) return '';
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  },
};

window.PerfilVista = PerfilVista;
