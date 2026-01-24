# Contexto y Memoria del Proyecto para Antigravity (IA)

Este archivo sirve para almacenar instrucciones recurrentes, decisiones de diseño y contexto del proyecto `jspos-sales`.
La IA debe leer este archivo para entender cómo trabajar en este proyecto específico.

## Reglas de Trabajo (Workflow)

### Gestión de Versiones y Git
1.  **Ramas por Funcionalidad**: NUNCA trabajar directamente en `develop` o `main`. Siempre crear una rama nueva para cada tarea o corrección.
    -   Formato: `feature/nombre-tarea` o `fix/nombre-bug`.
2.  **Flujo de Aprobación**:
    -   Hacer cambios en la rama temporal.
    -   Verificar funcionalmente.
    -   Si todo está bien, fusionar (merge) con `develop`.
    -   Crear el Release / Tag.
3.  **Orden del Changelog**: Las versiones más recientes siempre deben ir arriba en `CHANGELOG.md`.

### Procedimiento de Release (OBLIGATORIO)

#### Paso 0: Actualizar CHANGELOG.md (CRÍTICO - SIEMPRE PRIMERO)
ANTES de hacer cualquier commit de release, SIEMPRE actualizar `CHANGELOG.md`:
- Agregar la nueva versión al INICIO del archivo
- Formato: `## [1.x.x] - YYYY-MM-DD`
- Secciones: `### Added`, `### Fixed`, `### Changed`, `### Database`
- Si el usuario reporta que no ve las notas, verificar que CHANGELOG.md esté actualizado

#### Paso 1: Formato de Commits
```
feat: [Título breve]

- Punto detallado 1
- Punto detallado 2
```

#### Paso 2: Crear Tag con Notas
```bash
git tag -a v1.x.x -m "Release v1.x.x: [Título]

New Features:
- Feature 1

Improvements:
- Improvement 1

Bug Fixes:
- Fix 1

Database Changes:
- Migration 1"
```

#### Paso 3: Archivo release_notes_v1.x.x.md
Crear archivo markdown con las notas para que el usuario las copie a GitHub Release.

#### Paso 4: Secuencia Completa
```bash
# 0. Actualizar CHANGELOG.md (OBLIGATORIO)
# 1. git add .
# 2. git commit -m "feat: [mensaje]"
# 3. git push origin feature/nombre-rama
# 4. git tag -a v1.x.x -m "[notas]"
# 5. git push origin v1.x.x
# 6. Crear release_notes_v1.x.x.md
```

## Decisiones de Diseño
...

## 1. Reglas Generales de Desarrollo
- **Stack Tecnológico**: Laravel, Blade/Vue (según aplique), Tailwind CSS, MySQL.
- **Estilo de Código**: Seguir estándares de Laravel.
- **Idioma**: Español (según preferencia del usuario).
- **Diseño Responsivo (OBLIGATORIO)**: Todas las nuevas interfaces o modificaciones DEBEN verse y funcionar correctamente en Celulares, Tablets y PC. El sistema es multi-dispositivo.

## 2. Instrucciones Frecuentes
*(Pega aquí las instrucciones que repites en cada sesión)*
- Ejemplo: "Siempre validar los stocks antes de crear una venta."
- Ejemplo: "Usar componentes de Blade para elementos repetitivos."

## 3. Arquitectura y Lógica Clave
- **Base de Datos**: Ver directorio `database/migrations` para estructura.
- **Modelos**: Ubicados en `app/Models` (o `app/` si es Laravel antiguo).
- **Flujos Críticos**: Ventas, Control de Stock, Reportes.

## 4. Gestión de Dispositivos e Impresión
- **Jerarquía de Configuración de Impresora**:
    1. **Dispositivo** (`DeviceAuthorization`): Prioridad MÁXIMA. Se configura por cookie `device_token`.
    2. **Usuario** (`User`): Si el dispositivo no tiene impresora configurada.
    3. **Global** (`Configuration`): Fallback final si ni dispositivo ni usuario tienen configuración.
- **Ancho de Papel**: Soportado 58mm y 80mm. Se define junto con el nombre de la impresora.
- **Drivers**: Se usa `Mike42\Escpos` con `WindowsPrintConnector`. El nombre de la impresora debe coincidir con el recurso compartido en Windows.

## 5. Historial de Decisiones Importantes
- [Fecha]: decisión tomada...

## 6. Flujo de Trabajo y Control de Versiones (CRÍTICO)
- **CHANGELOG Obligatorio**: ANTES de hacer cualquier commit de release, tag, o `git push origin develop`, **SIEMPRE** se debe actualizar el archivo `CHANGELOG.md` con los cambios realizados.
- **Recordatorio Constante**: Si el usuario pide "subir cambios" o "hacer release", el primer paso es verificar y actualizar el Changelog.

## 7. Cambios Arquitectónicos Recientes (Protección y Optimización)
### 7.1. Protección contra Re-instalación Accidental
- **Problema**: Fallos momentáneos en la lectura del `.env` (config `app.installed`) redirigían al asistente de instalación.
- **Solución**: Implementación de un archivo de bloqueo físico en `storage/installed`.
- **Mecanismo**: El middleware `CheckInstalled` verifica primero la existencia de este archivo. Si el config dice "instalado" pero el archivo falta, se auto-crea (self-healing).
- **Despliegue/Migración**: Este archivo está en `.gitignore`. Al migrar a una PC nueva copiando archivos, el usuario DEBE borrar `storage/installed` manualmente para disparar el instalador.

### 7.2. Búsqueda de Productos Optimizada (UX)
- **Problema**: La búsqueda era lenta al navegar con flechas porque usaba `wire:keydown`, enviando requests al servidor por cada tecla.
- **Solución**: Migración a **AlpineJS** para la navegación en el cliente.
- **Detalles**:
    - `items.blade.php` ahora usa `x-data` para manejar `selectedIndex` y `itemCount`.
    - La navegación (Flechas Arriba/Abajo) es instantánea y puramente JS.
    - Se implementó `scrollIntoView` para seguimiento automático de la selección.
    - El Backend (`Sales.php`) solo se encarga de filtrar la query, no de la navegación UI.

### 7.3. Lógica de Productos Variables (Bobinas)
- **Reservas**: Al guardar una venta como "Pendiente" (`storeOrder`), los items variables se marcan como `reserved` en DB.
- **Carga de Ordenes**: Al editar/cargar una orden guardada, se usa un flag `$bypassReservation` para permitir que la orden cargue sus propios items reservados (que normalmente estarían ocultos si la config `check_stock_reservation` está activa).

