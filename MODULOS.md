# Control de Progreso por Módulos

Este documento sirve como registro de avance para el desarrollo del proyecto. Permite conocer qué módulos están completados, cuáles están en desarrollo y cuáles son los siguientes según las dependencias técnicas.

## Instrucciones para los estudiantes

1. **Cómo marcar un módulo completado:**
   Cambia los corchetes vacíos `- [ ]` por una equis mayúscula o minúscula `- [x]`.
2. **Criterios antes de marcar como terminado:**
   - La base de datos asociada está actualizada en `database/script.sql`.
   - El backend cumple con MVC y SOLID (Contratos, Repositorio, Servicio y Controlador sin SQL quemado en controladores).
   - El frontend consume los endpoints usando `js/api.js`.
   - Se ha probado el flujo manualmente de extremo a extremo sin errores en consola.
   - Los cambios están confirmados con su respectivo mensaje de commit.
3. **Respetar el orden:** No iniciar un módulo si los que están listados en su columna *Depende de* no están marcados con `[x]`.


## Fase 0: Preparación Técnica

- [x] **Infraestructura Base**
  - Base de datos inicial con tablas `usuarios`, `tokens_sesion` y administrador semilla.
  - Conexión PDO con manejo estricto de excepciones y charset UTF-8.
  - Autoloader PSR-4 (`backend/lib/autoload.php`) y utilidades de respuesta JSON / CORS.
  - Contenedor de inyección de dependencias (`backend/src/Contenedor.php`).
  - Cliente base de API en frontend (`frontend/js/api.js` y `frontend/js/config.js`).
  - Portada inicial (`frontend/index.html` y `frontend/css/base.css`).


## Primer Parcial

| Estado | Orden | Módulo | Requisitos | Descripción clave | Depende de | Encargado |
|:---:|:---:|---|---|---|---|---|
| [ ] | 1 | **M1. Autenticación, roles y base común** | RF1, RF2, RF3, RF4, RF16, RNF1, RNF9, RNF10 | Registro, login, logout, sesión con tokens Bearer, middleware de rol y pantallas `login.html` y `registro.html`. | Fase 0 | Moreira Abel |
| [ ] | 2 | **M3. Catálogos y diseño base** | RF6, RF8, RNF4 | CRUD de materias y espacios (solo admin). Estructura de plantilla y menú dinámico por rol. | M1 | - |
| [ ] | 3 | **M10 (base). Función de puntos** | RF20 (base), RNF8 | `GamificacionServicio::otorgarPuntos()` y tablas base de reglas e historial de XP. | M1 | - |
| [ ] | 4 | **M4. Disponibilidad del mentor** | RF7 | Configuración de horarios por mentor (día, hora de inicio y fin). | M1 | Ronald Baque |
| [ ] | 5 | **M2. Perfil y materias dominadas** | RF5 | Ver y editar perfil personal, selección de materias dominadas por el mentor. | M1, M3 | Ronald Baque |
| [ ] | 6 | **M5. Búsqueda de mentores** | RF9 | Filtro asíncrono de mentores por materia y día sin recargar página. | M2, M3, M4 | - |
| [ ] | 7 | **M11 (base). Gestión de usuarios y auditoría** | RF16, RF17, RF19 (base), RNF7 | Listar, suspender, reactivar y cambiar de rol a usuarios. Registro de auditoría básico. | M1, M3 | - |
| [ ] | 8 | **M6. Reservas y validaciones** | RF10, RF11, RNF3 | Reservas individuales o grupales, validación estricta de cupos y choque de horarios. | M3, M4, M5 | - |

## Segundo Parcial

| Estado | Orden | Módulo | Requisitos | Descripción clave | Depende de | Encargado |
|:---:|:---:|---|---|---|---|---|
| [ ] | 9 | **M7. Gestión de solicitudes** | RF12 | El mentor acepta o rechaza solicitudes de tutoría pendientes. | M6 | - |
| [ ] | 10 | **M10 (completo). Insignias y ranking** | RF21, RF22 | Insignias automáticas por eventos, ranking de mentores y estudiantes, pantalla "Mis logros". | M6, eventos de M8/M9 | - |
| [ ] | 11 | **M8. Asistencia con PIN y horas** | RF13, RF15 | Generación de PIN por el mentor, confirmación del estudiante y acumulación de horas. | M6, M7 | - |
| [ ] | 12 | **M9. Reseñas** | RF14 | Calificación con estrellas (1-5) y comentario, habilitada solo tras confirmar PIN. | M8 | - |
| [ ] | 13 | **M11 (moderación). Gestión avanzada** | RF18, RF19 (completo) | Moderación del administrador: cancelación forzada de sesiones y eliminación de reseñas. | M6, M9 | - |
