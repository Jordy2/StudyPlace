const PerfilModelo = {
  async obtenerPerfil() {
    const res = await window.API.fetch('perfil/perfil.php');
    return res?.datos || null;
  },

  async actualizarPerfil(datos) {
    return await window.API.fetch('perfil/perfil.php', {
      method: 'POST',
      body: datos,
    });
  },
};

window.PerfilModelo = PerfilModelo;
