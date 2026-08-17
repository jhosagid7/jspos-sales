# Contexto y Memoria del Proyecto para Antigravity (IA)

> [!WARNING]
> **REGLA CRÍTICA DE COMPILACIÓN FLUTTER**: Antes de compilar cualquier aplicación móvil (`mobile_*_app/`), la IA DEBE leer obligatoriamente la sección **9.1** de este archivo.
> NUNCA se debe compilar usando `flutter build apk --release` plano (genera un archivo de >50MB).
> SIEMPRE usar `flutter build apk --release --split-per-abi` y copiar únicamente el APK de 64 bits (`app-arm64-v8a-release.apk`) a la raíz.

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
4.  **REGLA DE ORO (PUSH)**: NUNCA realizar `git push` al repositorio de GitHub ni subir cambios al remoto sin que el usuario lo pida **EXPLÍCITAMENTE**. Primero se deben revisar los cambios localmente y, una vez aprobados, se procede con el comando de subida.

## Decisiones de Diseño
...

## 1. Reglas Generales de Desarrollo
- **Stack Tecnológico**: Laravel, Blade/Vue (según aplique), Tailwind CSS, MySQL.
- **Estilo de Código**: Seguir estándares de Laravel.
- **Idioma**: Español (OBLIGATORIO para todos los reportes, resúmenes y comunicaciones con el usuario).
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
- **OBLIGATORIO: Revisar Workflow de Release**: Antes de ejecutar cualquier comando para "subir cambios" o hacer un "release" o "despliegue", el asistente DEBE leer el archivo `.agent/workflows/release.md` para seguir estrictamente los pasos definidos. Asumir los pasos ha llevado a omisiones (como faltar el archivo version.txt o el tag).
- **MIGRACIONES AUTOMÁTICAS**: Si el desarrollo incluye modificaciones a la base de datos (nuevas tablas, migraciones, seeders), **NUNCA** se debe asumir que el cliente correrá los comandos manualmente. El desarrollador (IA) debe integrar la ejecución de estas migraciones en el script/controlador de actualización del sistema (ej. `UpdateService` o rutas de update) para que sea transparente para el usuario final.
- **CHANGELOG Obligatorio**: ANTES de hacer cualquier commit de release, tag, o `git push origin develop`, **SIEMPRE** se debe actualizar el archivo `CHANGELOG.md` con los cambios realizados.
- **Recordatorio Constante**: Si el usuario pide "subir cambios" o "hacer release", el primer paso es verificar y actualizar el Changelog. **NO REALIZAR GIT PUSH AL REMOTO SIN ORDEN EXPLÍCITA**.

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

### 7.4. Conciliación Financiera y Segregación de Billetera (Arqueo de Caja)
- **Problema**: Discrepancias en el "Total a Entregar" cuando había devoluciones (Notas de Crédito) que se convertían en saldo de billetera, ya que el sistema no diferenciaba claramente entre el flujo de efectivo físico y el saldo virtual.
- **Solución**: Refactorización del motor de cálculo en `CashCount.php` y `ReportController.php`.
- **Lógica de Reconciliación**:
    1. **Flujo Neto**: Las ventas se reportan netas (Ventas Brutas - Devoluciones).
    2. **Custodia Hoy (+)**: El efectivo que queda en caja por devoluciones convertidas a billetera se suma al arqueo como "Responsabilidad del Cajero" (Custodia).
    3. **Consumo Billetera (-)**: Los pagos realizados con saldo virtual anterior se restan del flujo, ya que no representan entrada de dinero físico hoy.
    4. **Sincronización Total**: Se unificó el cálculo para el Dashboard (Livewire), el PDF (Letter/A4) y el Ticket Térmico (PrintTrait), asegurando que los tres canales arrojen el mismo resultado exacto.

### 7.5. Optimización de Layout en Reportes PDF (Multilínea)
- **Problema**: En el Reporte de Ventas Diarias, las facturas con múltiples métodos de pago concatenados causaban un desbordamiento horizontal de las columnas (ej. Factura 629), rompiendo el formato de impresión.
- **Solución**: Se eliminó la restricción `white-space: nowrap` de las celdas de la tabla y se refactorizaron los detalles de pago para que se listen verticalmente.
- **Detalle Técnico**:
    - Uso de `display: block` para cada entrada de pago dentro de la celda de descripción.
    - Cambio de `vertical-align: middle` a `top` para mejorar la legibilidad en registros multilínea.
    - Eliminación de `height: 14px` fijo para permitir que la fila crezca dinámicamente según el contenido.

## 8. Roadmap y Tareas Futuras Adjudicadas
### 8.1. Sistema de Rollback para Actualizaciones (Planificado)
- **Objetivo**: Permitir a los clientes y administradores revertir una actualización fácilmente si algo falla en producción.
- **Estrategia Acordada (Método de Backup Local)**:
  1. Al iniciar una actualización vía `UpdateService`, el sistema debe generar un ZIP completo de las carpetas críticas (`app`, `public`, `resources`, etc.) y un `.sql` completo de la BD.
  2. Guardar estos respaldos en `storage/backups/antes_de_vX.X.X`.
  3. Ejecutar la descarga, reemplazo de archivos y migraciones (`php artisan migrate`).
  4. Proveer un botón de "Rollback" en el panel de SuperAdmin que restaure el ZIP y el SQL anterior de forma atómica.


## 9. Aplicación Móvil (Flutter)
### 9.1. Entorno de Compilación (CRÍTICO)
- **Ruta SDK Flutter**: `C:\src\flutter` (Ejecutable: `C:\src\flutter\bin\flutter.bat`).
- **Directorio de los Proyectos Móviles en la Raíz**:
  - `mobile_app/` -> Aplicación Móvil para Vendedores y Cajeros (POS Vendedor).
  - `mobile_vip_app/` -> Aplicación Móvil para Clientes VIP (Autoservicio y Pagos Cliente).
  - `mobile_bolsas_app/` -> Aplicación Móvil de Manufactura (Planta Bolsas).
  - `mobile_soplados_app/` -> Aplicación Móvil de Manufactura (Planta Soplados).
- **Protocolo de Compilación y Release**:
    1.  **Versión**: Incrementar el `version` en `pubspec.yaml` (tanto el nombre `x.x.x` como el build number `+N`).
    2.  **Limpieza/Dependencias**: Ejecutar `flutter pub get`.
    3.  **Build**: NUNCA usar `flutter build apk --release` a secas (esto genera un FAT APK de >50MB). **SIEMPRE** ejecutar: `flutter build apk --release --split-per-abi`.
    4.  **Distribución**: De los APKs generados, copiar ÚNICAMENTE el archivo de procesadores modernos (`build/app/outputs/flutter-apk/app-arm64-v8a-release.apk`) a la **RAÍZ** del proyecto. Este archivo 64-bits (aprox 18MB) es el estándar desde hace 5-6 años y corre en la inmensa mayoría de dispositivos.
    5.  **Nomenclatura**: Renombrar el APK copiado siguiendo el patrón:
        - App Normal: `JSPOS_Mobile_vX.X.X_BreveDescripcion_SuWeb.apk`
        - App VIP/Clientes: `JSPOS_Mobile_VIP_vX.X.X_AppClientes_SuWeb.apk`
        - App Soplados/Planta: `JSPOS_Mobile_Soplados_vX.X.X_AppManufactura_SuWeb.apk`

### 9.2. Estándares de Diseño y Estética "Su Web" (OBLIGATORIO)
Cualquier modificación en la interfaz móvil DEBE seguir estos lineamientos para mantener la categoría Premium:
- **Tipografía**: Usar `GoogleFonts.outfit()`.
- **Colores**: Evitar colores planos básicos. Usar gradientes armónicos (ej. `Color(0xFF1A237E)` a `Color(0xFF0D47A1)`).
- **Componentes**: 
    - Bordes redondeados pronunciados (mínimo `borderRadius: BorderRadius.circular(20)`).
    - Sombras suaves (`BoxShadow` with opacidad baja).
    - Iconografía moderna y consistente.
- **Interactividad**: Implementar micro-animaciones o feedbacks visuales (SnackBars informativos, loaders personalizados).
