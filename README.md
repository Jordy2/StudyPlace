# Mentoría Académica
 
Sistema web donde **estudiantes** pueden buscar **mentores** por materia y día, reservar sesiones de tutoría, confirmar su asistencia y dejar reseñas. Incluye puntos, insignias y rankings (gamificación) y un panel para el **administrador**.

## Integrantes del grupo

- Moreira Moreno Abel
 
## Arquitectura
 
- **Frontend:** Se publica en GitHub Pages
- **Backend:** PHP orientado a objetos, sin framework. Recibe peticiones, aplica las reglas y guarda en MySQL.
- **Base de datos:** MySQL.

### Roles
 
| Rol | Qué puede hacer |
|---|---|
| Estudiante | Buscar mentores, reservar, asistir, dejar reseñas |
| Mentor | Configurar horarios, aceptar o rechazar solicitudes, dictar sesiones |
| Administrador | Gestionar usuarios, materias, espacios y moderar. Se crea solo desde la base de datos |
  
## Estructura de carpetas
 
```
root/
├── frontend/        ← la página web (se publica en GitHub Pages)
├── backend/         ← la API en PHP (se copia a xampp/htdocs)
├── database/        ← script.sql y datos_prueba.sql
├── docs/            ← sitio de documentación (Hugo)
└── documentacion/   ← entregables del profesor (.docx y bitácoras)
```
 
### backend
 
```
backend/
├── api/             ← un archivo por endpoint. Son MUY cortos
├── src/
│   ├── Controladores/   ← reciben la petición y responden
│   ├── Servicios/       ← aquí va la lógica ("las reglas")
│   ├── Repositorios/    ← aquí va TODO el SQL
│   ├── Modelos/         ← clases simples: Usuario, Reserva...
│   ├── Contratos/       ← interfaces
│   ├── Middleware/      ← revisa sesión y rol
│   ├── Validadores/     ← reglas de reserva (cupo, choque de horario)
│   ├── Insignias/       ← una clase por insignia
│   ├── Vistas/          ← formato del JSON de salida
│   └── Contenedor.php   ← arma y conecta todas las clases
├── lib/             ← utilidades: autoload, cors, respuesta
└── config/          ← conexión a la base de datos
```
 
### frontend
 
```
frontend/
├── paginas/         ← una página HTML por pantalla
├── css/base.css
└── js/
    ├── api.js           ← único lugar que habla con el backend
    ├── config.js        ← dirección del backend
    ├── modelos/         ← los datos
    ├── vistas/          ← funciones que dibujan en pantalla
    └── controladores/   ← capturan clics y conectan todo
```
 
 
## Comunicación entre capas
 
Cuando un usuario hace una acción, la petición recorre siempre:
 
```
Página → api.js → endpoint (api/) → Controlador → Servicio → Repositorio → MySQL
```
 
| Capa | Hace | NO hace |
|---|---|---|
| Endpoint (`api/`) | Crea el controlador y lo ejecuta | Lógica ni SQL |
| Controlador | Lee la petición y devuelve la respuesta | Lógica ni SQL |
| Servicio | Aplica las reglas del negocio | SQL ni leer `$_POST` |
| Repositorio | Escribe el SQL | Reglas del negocio |
 
Otras reglas del equipo:
 
- Los servicios reciben sus repositorios **por el constructor** (no crean la conexión ellos mismos).
- Para agregar una regla de reserva o una insignia nueva, se **crea una clase nueva**, no se edita las existentes.
- Todo dato se valida tanto en el frontend como en el backend.
- Las contraseñas se guardan con hash. Nunca en texto plano.
- Los puntos de gamificación los da solo el servidor, una sola vez por acción.
- Las acciones destructivas piden confirmación.

  
## 5. Orden de desarrollo

| Orden | Módulo | Qué se hace | Necesita haber terminado |
|:--:|---|---|---|
| 1 | **M1. Autenticación, roles y base común** | Registro, login, logout, sesión. El "esqueleto" que todos usan: conexión, respuestas JSON, CORS, middleware de roles, autoload, contenedor y `api.js` | Nada |
| 2 | **M3. Catálogos y diseño base** | Materias y espacios (solo el administrador escribe). `base.css`, plantilla y menú por rol | M1 |
| 3 | **M10 (parte base). Función de puntos** | `GamificacionServicio::otorgarPuntos()` y las tablas de puntos. Se hace pronto para que nadie se bloquee | M1 |
| 4 | **M4. Disponibilidad del mentor** | El mentor define sus horarios (día, hora inicio y fin) | M1 |
| 5 | **M2. Perfil y materias dominadas** | Ver y editar perfil, elegir materias que domina | M1, M3 |
| 6 | **M5. Búsqueda de mentores** | Filtrar por materia y día, sin recargar la página | M2, M3, M4 |
| 7 | **M11 (parte base). Gestión de usuarios y auditoría** | Listar, editar, suspender y eliminar usuarios. Registro de acciones del administrador | M1, M3 |
| 8 | **M6. Reservas y validaciones** | Reservar sesiones individuales o grupales, validar cupo y choques de horario. **El más difícil: asígnenlo a quien tenga más experiencia** | M3, M4, M5 |
| 9 | **M7. Gestión de solicitudes** | El mentor acepta o rechaza solicitudes (trabaja junto con M6) | M6 |
| 10 | **M10 (parte completa). Insignias y ranking** | Insignias automáticas, ranking, "Mis logros" | M6 y los módulos que envían eventos |
| 11 | **M8. Asistencia con PIN y horas** | Generar PIN, confirmar asistencia, acumular horas del mentor | M6, M7 |
| 12 | **M9. Reseñas** | Estrellas y comentario, solo después de confirmar asistencia | M8 |
| 13 | **M11 (moderación)** | El administrador cancela reservas y elimina reseñas | M6, M9 |
 
### Alcence primer parcial
  
- módulos **1 al 6**, más versiones mínimas de **gamificación** (puntos, niveles y "Mis logros") y **administración** (gestión de usuarios y auditoría), porque el profesor las exige.

### Alcence segundo parcial

- módulos 7, 8 y 9, y el resto de la moderación. 
 
## Qué NO incluye el proyecto
 
- Pagos.
- Servidor de video propio. Las aulas virtuales usan enlaces externos (Meet, Zoom, etc.).
 