const API = {
  getTokenKey() {
    return window.API_CONFIG?.TOKEN_KEY || 'studyplace_token';
  },

  obtenerToken() {
    return localStorage.getItem(this.getTokenKey());
  },

  guardarToken(token) {
    localStorage.setItem(this.getTokenKey(), token);
  },

  eliminarToken() {
    localStorage.removeItem(this.getTokenKey());
  },

  async fetch(endpoint, options = {}) {
    const baseUrl = window.API_CONFIG?.BASE_URL || '';
    const route = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const token = this.obtenerToken();

    const isFormData = options.body instanceof FormData;
    const hasBody = options.body !== undefined && options.body !== null;

    const headers = {
      Accept: 'application/json',
      ...(!isFormData && hasBody && { 'Content-Type': 'application/json' }),
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    };

    const body = hasBody && !isFormData && typeof options.body === 'object'
      ? JSON.stringify(options.body)
      : options.body;

    const response = await fetch(`${baseUrl}${route}`, {
      ...options,
      headers,
      body,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      if (response.status === 401) {
        this.eliminarToken();
      }

      const error = new Error(
        data?.error || data?.mensaje || `Error HTTP ${response.status}`
      );

      error.status = response.status;
      error.detalles = data;

      throw error;
    }

    return data;
  },
};

window.API = API;
