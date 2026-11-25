const UsuarioAdminModelo = {
  async listarUsuarios(filtros = {}) {
    const params = new URLSearchParams();
    if (filtros.q) params.append('q', filtros.q);
    if (filtros.rol) params.append('rol', filtros.rol);
    if (filtros.estado) params.append('estado', filtros.estado);

    const query = params.toString() ? `?${params.toString()}` : '';
    const res = await window.API.fetch(`admin/usuarios.php${query}`);
    return res?.datos || [];
  },

  async ejecutarAccion(id, accion, datosExtra = {}) {
    return await window.API.fetch(`admin/usuarios.php?id=${encodeURIComponent(id)}`, {
      method: 'PUT',
      body: { accion, ...datosExtra },
    });
  },

  async listarAuditoria(limite = 100) {
    const res = await window.API.fetch(`admin/auditoria.php?limite=${encodeURIComponent(limite)}`);
    return res?.datos || [];
  },
};

window.UsuarioAdminModelo = UsuarioAdminModelo;
