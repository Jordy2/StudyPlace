const CatalogoModelo = {
  async obtenerMaterias(todas = true) {
    const query = todas ? '?todas=1' : '';
    const res = await window.API.fetch(`catalogos/materias.php${query}`);
    return res?.datos || [];
  },

  async crearMateria(datos) {
    return await window.API.fetch('catalogos/materias.php', {
      method: 'POST',
      body: datos,
    });
  },

  async actualizarMateria(id, datos) {
    return await window.API.fetch(`catalogos/materias.php?id=${id}`, {
      method: 'PUT',
      body: datos,
    });
  },

  async eliminarMateria(id) {
    return await window.API.fetch(`catalogos/materias.php?id=${id}`, {
      method: 'DELETE',
    });
  },

  async obtenerEspacios(tipo = null, todos = true) {
    const params = new URLSearchParams();
    if (tipo) params.append('tipo', tipo);
    if (todos) params.append('todos', '1');
    const query = params.toString() ? `?${params.toString()}` : '';

    const res = await window.API.fetch(`catalogos/espacios.php${query}`);
    return res?.datos || [];
  },

  async crearEspacio(datos) {
    return await window.API.fetch('catalogos/espacios.php', {
      method: 'POST',
      body: datos,
    });
  },

  async actualizarEspacio(id, datos) {
    return await window.API.fetch(`catalogos/espacios.php?id=${id}`, {
      method: 'PUT',
      body: datos,
    });
  },

  async eliminarEspacio(id) {
    return await window.API.fetch(`catalogos/espacios.php?id=${id}`, {
      method: 'DELETE',
    });
  },
};

window.CatalogoModelo = CatalogoModelo;
