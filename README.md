# Mentoría Académica
 
Sistema web donde **estudiantes** pueden buscar **mentores** por materia y día, reservar sesiones de tutoría, confirmar su asistencia y dejar reseñas. Incluye puntos, insignias y rankings (gamificación) y un panel para el **administrador**.

## Integrantes del grupo

- Agudo Triana Jordy Manuel (Lider)
- Moreira Moreno Abel Daniel
- Mora Flores Briggitte Lisbeth
- Orellana Lavayen Dherian Mikael
- Herrera Motoche Danny Alexander
- Rodriguez Tierra Cesar Emanuel
- Carrillo Villao Alex Antonio
- Reyes Pozo Emily Lissette
- Ronald Steven Baque Pérez
 
## Arquitectura
 
- **Frontend:** Se publica en GitHub Pages
- **Backend:** PHP orientado a objetos, sin framework. Recibe peticiones, aplica las reglas y guarda en MySQL.
- **Base de datos:** MySQL.

### Roles
 
| Rol | Qué puede hacer | Requisitos asociados |
|---|---|---|
| Estudiante | Buscar mentores, solicitar reservas, confirmar asistencia con PIN, calificar con reseñas, ganar puntos (XP), desbloquear insignias y consultar rankings | RF1, RF2, RF4, RF5, RF9, RF10, RF13, RF14, RF20, RF21, RF22 |
| Mentor | Configurar disponibilidad horaria, gestionar solicitudes (aceptar/rechazar), generar PIN de sesión, acumular horas de vinculación, ganar puntos e insignias | RF1, RF2, RF4, RF5, RF7, RF12, RF13, RF15, RF20, RF21 |
| Administrador | Gestionar usuarios (suspender/reactivar/eliminar/cambio de rol), administrar catálogos (materias y espacios), moderar reservas y reseñas, y consultar auditoría. Se crea solo desde la base de datos | RF3, RF4, RF6, RF8, RF16, RF17, RF18, RF19 |

## Requisitos funcionales

### Usuarios y seguridad
- **RF1.** Registro de usuarios (estudiante o mentor) con datos académicos y semestre actual.
- **RF2.** Inicio y cierre de sesión. Las cuentas suspendidas o eliminadas no pueden ingresar.
- **RF3.** Gestión de roles (estudiante, mentor y administrador).
- **RF4.** Restricción de acceso a las páginas y a la API según el rol.
- **RF5.** Gestión de perfil y selección de materias dominadas.
- **RF16.** Rol de administrador con permisos para administrar todo el sitio. Sus cuentas se crean solo desde la base de datos, nunca por el registro público.
- **RF17.** El administrador puede listar, buscar, editar, cambiar de rol (estudiante/mentor), suspender, reactivar y eliminar usuarios de roles inferiores. No puede modificar ni eliminar a otros administradores ni a sí mismo.
- **RF18.** El administrador puede cancelar cualquier reserva o sesión y eliminar reseñas inapropiadas.
- **RF19.** Registro de auditoría de las acciones del administrador (quién, qué, sobre quién y cuándo).

### Catálogo y horarios
- **RF6.** Catálogo de materias con código único. Solo el administrador crea, edita y elimina; los demás roles consultan.
- **RF7.** Configuración de horarios disponibles por mentor (día, hora de inicio y fin).
- **RF8.** Catálogo de espacios: aulas físicas (con capacidad) y salas virtuales (enlace externo). Solo el administrador crea, edita y elimina; los demás roles consultan.

### Búsqueda
- **RF9.** Búsqueda de mentores filtrando por materia y día, sin recargar la página.

### Reservas
- **RF10.** Reserva de sesiones individuales o grupales con cupo máximo.
- **RF11.** Validación de choques de horario y bloqueo por sobrecupo.
- **RF12.** Aceptación o rechazo de solicitudes por parte del mentor.

### Asistencia y evaluación
- **RF13.** Confirmación de asistencia mediante PIN de sesión.
- **RF14.** Reseñas de estudiantes a mentores (1 a 5 estrellas y comentario), habilitadas solo tras confirmar la asistencia.
- **RF15.** Acumulación de horas de vinculación para el mentor.
- **RF20.** Gamificación: puntos de experiencia (XP) por acciones (completar perfil, configurar disponibilidad, solicitar sesión, asistir, dictar sesión, dejar o recibir reseñas), niveles según el XP con barra de progreso y una página "Mis logros". Las reglas de puntos se guardan en una tabla.
- **RF21.** Gamificación: insignias que se otorgan automáticamente al cumplir condiciones (ej. "Primera sesión", "Mentor 5 estrellas", "10 sesiones").
- **RF22.** Gamificación: ranking de mentores (horas y reseñas) y de estudiantes (sesiones asistidas).

## Requisitos no funcionales

- **RNF1. Seguridad:** las contraseñas se almacenan con hash y el acceso a las páginas y a la API se controla según el rol. Cada endpoint verifica el rol en el servidor mediante un middleware; los de administración solo responden al administrador.
- **RNF2. Validación:** las entradas se validan en el back-end antes de procesarse, aunque el front también valide.
- **RNF3. Integridad:** las reservas no superan el cupo ni generan choques de horario, incluso con solicitudes simultáneas. Al suspender o eliminar un usuario no quedan reservas, reseñas ni horarios huérfanos (se recomienda baja lógica).
- **RNF4. Usabilidad:** interfaz sencilla y coherente, legible en computador y celular. El menú se adapta al rol.
- **RNF5. Alcance:** el sistema no incluye pagos ni servidores de video propios; las aulas virtuales usan enlaces externos.
- **RNF6. Arquitectura desacoplada:** el front-end (HTML/CSS/JS) no contiene código del servidor y se comunica con el back-end solo mediante una API JSON, de modo que se publique como sitio estático en GitHub Pages. El back-end (PHP y MySQL) se aloja aparte (XAMPP en desarrollo y demostración local, o un hosting con PHP y MySQL para acceso en línea). La API habilita CORS para el dominio de GitHub Pages.
- **RNF7. Trazabilidad:** las acciones del administrador quedan registradas en auditoría y las acciones destructivas piden confirmación.
- **RNF8. Gamificación justa:** los puntos los otorga solo el servidor y cada acción da puntos una sola vez, aunque se repita la petición.
- **RNF9. Diseño (MVC + SOLID):** el back-end es PHP orientado a objetos sin framework y sigue el patrón MVC adaptado a una API JSON, con capas de servicio y repositorio:
  - **Modelo:** entidades (`Modelos/`), servicios de negocio (`Servicios/`) y repositorios con el SQL (`Repositorios/`).
  - **Vista:** la representación JSON de la respuesta (`Vistas/`) y el front-end estático, que pinta la interfaz.
  - **Controlador:** clases (`Controladores/`) que reciben la petición, llaman al servicio y devuelven la respuesta.
  - Los endpoints de `api/` solo instancian el controlador y lo ejecutan. Los servicios dependen de interfaces (`Contratos/`), no de clases concretas.
- **RNF10. Autenticación entre dominios:** como el front (GitHub Pages) y la API están en dominios distintos, la sesión se maneja con un **token** que el front guarda y envía en el encabezado `Authorization`, en lugar de depender de cookies de `$_SESSION`. *(Decisión a confirmar en el módulo 1.)*

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
├── api/             ← endpoints organizados por recurso/módulo
│   ├── admin/           ← gestión de usuarios, moderación y auditoría
│   ├── asistencia/      ← generación de PIN y registro de asistencia
│   ├── auth/            ← registro, login, logout, sesión
│   ├── busqueda/        ← búsqueda de mentores con filtros
│   ├── catalogos/       ← materias y espacios
│   ├── disponibilidad/  ← horarios disponibles del mentor
│   ├── gamificacion/    ← puntos XP, insignias, niveles y ranking
│   ├── perfil/          ← perfil y materias dominadas
│   ├── reservas/        ← creación y consulta de reservas
│   ├── resenas/         ← calificaciones y comentarios
│   └── solicitudes/     ← aceptación/rechazo de tutorías
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
├── index.html       ← página de inicio / bienvenida
├── paginas/         ← una página HTML por pantalla (login, perfil, buscar, reservas, admin, logros...)
├── css/base.css     ← estilos globales y componentes visuales
└── js/
    ├── api.js           ← único lugar que habla con el backend
    ├── config.js        ← dirección del backend
    ├── componentes/     ← menú y plantilla comunes dinámicos por rol
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

  
## Orden de desarrollo
 
| Orden | Módulo | Requisitos asociados | Qué se hace | Necesita haber terminado |
|:--:|---|---|---|---|
| 1 | **M1. Autenticación, roles y base común** | RF1, RF2, RF3, RF4, RF16, RNF1, RNF9, RNF10 | Registro, login, logout, sesión. El "esqueleto" que todos usan: conexión, respuestas JSON, CORS, middleware de roles, autoload, contenedor y `api.js` | Nada |
| 2 | **M3. Catálogos y diseño base** | RF6, RF8, RNF4 | Materias y espacios (solo el administrador escribe). `base.css`, plantilla y menú por rol | M1 |
| 3 | **M10 (parte base). Función de puntos** | RF20 (base), RNF8 | `GamificacionServicio::otorgarPuntos()` y las tablas de puntos. Se hace pronto para que nadie se bloquee | M1 |
| 4 | **M4. Disponibilidad del mentor** | RF7 | El mentor define sus horarios (día, hora inicio y fin) | M1 |
| 5 | **M2. Perfil y materias dominadas** | RF5 | Ver y editar perfil, elegir materias que domina | M1, M3 |
| 6 | **M5. Búsqueda de mentores** | RF9 | Filtrar por materia y día, sin recargar la página | M2, M3, M4 |
| 7 | **M11 (parte base). Gestión de usuarios y auditoría** | RF16, RF17, RF19 (base), RNF7 | Listar, editar, suspender y eliminar usuarios. Registro de acciones del administrador | M1, M3 |
| 8 | **M6. Reservas y validaciones** | RF10, RF11, RNF3 | Reservar sesiones individuales o grupales, validar cupo y choques de horario. **El más difícil: asígnenlo a quien tenga más experiencia** | M3, M4, M5 |
| 9 | **M7. Gestión de solicitudes** | RF12 | El mentor acepta o rechaza solicitudes (trabaja junto con M6) | M6 |
| 10 | **M10 (parte completa). Insignias y ranking** | RF21, RF22 | Insignias automáticas, ranking, "Mis logros" | M6 y los módulos que envían eventos |
| 11 | **M8. Asistencia con PIN y horas** | RF13, RF15 | Generar PIN, confirmar asistencia, acumular horas del mentor | M6, M7 |
| 12 | **M9. Reseñas** | RF14 | Estrellas y comentario, solo después de confirmar asistencia | M8 |
| 13 | **M11 (moderación)** | RF18, RF19 (completo) | El administrador cancela reservas y elimina reseñas | M6, M9 |
 
### Alcance primer parcial
  
- **Módulos incluidos:** Módulos 1 al 6, junto con las versiones base de **M10** (puntos, niveles y "Mis logros") y **M11** (gestión de usuarios y auditoría inicial).
- **Requisitos funcionales cubiertos:** RF1, RF2, RF3, RF4, RF5, RF6, RF7, RF8, RF9, RF10, RF11, RF16, RF17 (CRUD y estado de usuarios) y RF20 (base de puntos XP y niveles).

### Alcance segundo parcial

- **Módulos incluidos:** Módulos 7, 8, 9, completitud de **M10** (insignias automáticas y rankings) y moderación avanzada en **M11** (cancelación de sesiones y eliminación de reseñas).
- **Requisitos funcionales cubiertos:** RF12, RF13, RF14, RF15, RF18, RF19 (auditoría completa de moderación), RF21 y RF22. 
 
## Qué NO incluye el proyecto
 
- Pagos.
- Servidor de video propio. Las aulas virtuales usan enlaces externos (Meet, Zoom, etc.).
 