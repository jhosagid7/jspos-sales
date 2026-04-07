# Changelog
All notable changes to this project will be documented in this file.

## [1.9.58] - 2026-04-07
### Fixed
- **Previsualización Vacía**: Se corrigió el error donde el reporte PDF (Previsualizar) salía "Sin datos" o en cero cuando no se seleccionaba una fecha. Ahora, si no hay filtro de fecha activo, el PDF muestra todo el historial de la pantalla actual.
- **Filtros de Fecha Opcionales**: Se sincronizó la lógica entre la web y el PDF: el "cerrojo" de inmutabilidad solo se activa si el usuario especifica un rango de fechas. Si no hay rango, se muestra el estado completo de las facturas (Totalmente pagas, etc.).

## [1.9.57] - 2026-04-07
### Fixed
- **Error format() on null**: Se corrigió el crash que ocurría al intentar generar un PDF sin seleccionar primero una fecha en los filtros. El sistema ahora usa la fecha actual por defecto.
- **Inmutabilidad de Créditos (F622)**: Se rediseñó el cálculo de deudas en los reportes. Ahora se basa en el **Balance Histórico** (Total - Pagos del Día) en lugar del estado actual de la factura. Esto garantiza que las facturas a crédito aparezcan como tales en reportes viejos, incluso si ya fueron pagadas después.
- **Sello de Inmutabilidad en PDF**: Se replicaron los filtros de relación (Pagos, Vueltos, Devoluciones) en el controlador del PDF (`ReportController`) para que los reportes impresos coincidan exactamente con lo que el usuario ve en pantalla.

### Added
- **Montos en Bs. (VED) en PDF**: Ahora el desglose de pagos en el reporte diario muestra el equivalente en Bolívares `[Bs. X.XX]` basándose en la tasa de cambio registrada en la transacción.

## [1.9.56] - 2026-04-07
### Fixed
- **Inmutabilidad Total de Reportes Financieros**: Se aplicó un "cerrojo" de fecha a todos los movimientos de una venta (Pagos, Vueltos y Devoluciones).
    - **Pagos Retroactivos**: Se corrigió el error donde los abonos a crédito realizados en fechas futuras afectaban los reportes históricos. Los reportes ahora solo muestran el flujo de dinero que ocurrió **exactamente** en el periodo consultado.
    - **Sincronización de Arqueo**: El Arqueo de Caja (`CashCount`) ahora filtra rigurosamente los `SalePaymentDetail` por fecha de creación, garantizando que el "Total a Entregar" sea una foto fiel del día.

## [1.9.55] - 2026-04-07
### Added
- **Optimización de Módulos de Inventario**: Aplicación de patrones de alto rendimiento en los módulos de **Cargos**, **Descargos** y **Compras**.
    - **Caché de Permisos**: Se eliminaron cientos de consultas redundantes a la base de datos mediante el pre-cálculo de autorizaciones en el método `mount()`.
    - **Integración de ConfigurationService**: Acceso optimizado a la configuración del IVA y el sistema, reduciendo el overhead en el renderizado de tablas de productos.
    - **Refactorización de Purchases.php**: Eliminación completa de `Configuration::first()` en el ciclo de `render()`, mejorando drásticamente la fluidez al interactuar con el carrito.

## [1.9.54] - 2026-04-07
### Added
- **Inmutabilidad de Reportes Financieros**: Se implementó una lógica de "Snapshot Temporal" en los reportes de Ventas Diarias, Arqueo de Caja y Reportes de Ventas.
    - **Inmutabilidad Histórica**: Los reportes generados en el pasado ahora son inmutables. El pago de un crédito futuro o una devolución posterior no alterarán los totales de días cerrados anteriormente.
    - **Cálculo de Créditos Estático**: La suma de créditos en el reporte se basa en el estado de la deuda al momento de la creación de la venta, garantizando la consistencia de la auditoría.
    - **Filtrado Temporal de Devoluciones**: Las devoluciones ahora solo afectan los reportes del día en que se procesaron, evitando que reduzcan retrospectivamente las ventas netas de periodos pasados.

### Fixed
- **Consistencia en Arqueo de Caja**: Se unificó la lógica de cálculo entre la vista de Livewire y el PDF del Arqueo para evitar discrepancias en los totales de moneda extranjera y pagos recibidos.

## [1.9.53] - 2026-04-07
### Added
- **Optimización Crítica de POS**: Reducción drástica del tiempo de carga inicial (~8s a <1s) mediante:
    - **Caché de Permisos**: Los permisos y configuraciones de módulos se calculan ahora una sola vez al cargar (`mount`), eliminando cientos de llamadas redundantes durante el renderizado.
    - **Servicio de Configuración Centralizado**: Se implementó una capa de caché para los ajustes del sistema, evitando consultas repetitivas a la tabla `configurations`.
    - **Middleware de Auto-Migración Reforzado**: Se sustituyó el motor de caché por archivos de bandera persistentes (`storage/framework/migrated_*.log`), eliminando la sobrecarga de Artisan en cada petición GET.
    - **Caché de Licencia**: La verificación de validez de la licencia se redujo de cada petición a una frecuencia de 1 hora mediante `Cache::remember`.
- **Estandarización de Componentes**: Preparación de la arquitectura para unificar el rendimiento en los módulos de Inventario y Compras bajo el mismo patrón de alta velocidad.

### Fixed
- **N+1 en Listado de Productos**: Se eliminaron las consultas recurrentes a `Auth::user()->can()` y `config()` dentro de los bucles de Blade, mejorando la respuesta visual del Punto de Venta.
- **Redundancia en AppServiceProvider**: Optimización del arranque global de la aplicación (Boot) para evitar el acceso directo a la base de datos en peticiones concurrentes.

## [1.9.51] - 2026-04-07
### Added
- **Estandarización de Clonación (Shortcuts)**: Se unificó el motor de clonación en todos los módulos (Ventas, Compras, Cargos y Descargos). Ahora el sistema reconoce sinónimos en español como `ENTRADA:`, `SALIDA:`, `COMPRA:`, `AJUSTE:` y `OC:` tanto en el escáner como en el buscador manual, facilitando la carga rápida de mercancía.
- **Leyenda de Comandos**: Se añadió una tabla de referencia en **Configuración -> Móvil** que detalla todos los códigos de clonación disponibles para guía del usuario.
- **Buscador de Clientes (Generador de Precios)**: Se implementó un buscador inteligente (TomSelect) en el Generador de Listas de Precios. Los usuarios ahora pueden buscar clientes por nombre, RIF o dirección en lugar de desplazarse por una lista estática, mejorando drásticamente la usabilidad con bases de datos grandes.
- **Configuración de Bloque de Pagos (PDF)**: Se añadió una opción en la configuración de la Lista de Precios para mostrar u ocultar el bloque informativo de pagos (Vencimiento, Mora y cabecera BCV) en el PDF generado.
- **Filtrado por Vendedor en Búsqueda**: El buscador de clientes ahora se sincroniza automáticamente con el vendedor seleccionado (modo Administrador), mostrando únicamente los clientes pertenecientes a la cartera del asesor elegido.

### Fixed
- **Integridad de Datos en Clonación**: Se resolvió un error técnico (`TypeError: json_decode`) en el módulo de compras que ocurría al intentar clonar documentos con metadatos complejos.

## [1.9.50] - 2026-04-06

## [1.9.49] - 2026-04-06
### Added
- **Justificación Obligatoria de Anulaciones**: Se implementó un sistema de auditoría que requiere obligatoriamente un motivo para cada anulación o solicitud de borrado.
- **Unificación de Flujo de Borrado**: Se integró un único aviso (SweetAlert) para todos los roles (administradores y operadores), eliminando la posibilidad de "borrados silenciosos".
- **Transparencia en Reportes**: El motivo de anulación ahora es visible directamente en la tabla del reporte de ventas y en el modal de detalles, incluyendo quién solicitó y quién aprobó la acción.

## [1.9.48] - 2026-04-06
### Added
- **Garantía Correlativa Sin Saltos**: Se blindó el motor de folios para que use un contador transaccional (`lockForUpdate`). Esto garantiza que **nunca** existan huecos en la numeración (1, 2, 3, 4...), incluso si una transacción falla, manteniendo siempre la concordancia 1:1 con el ID.
- **Auto-Calibración en Actualización**: Nueva migración de base de datos que alinea automáticamente los contadores de configuración con el ID más alto de las tablas, facilitando la transición automática para todos los clientes.

## [1.9.47] - 2026-04-06
### Added
- Feature: Ventas y Órdenes ahora tienen un Folio (invoice_number) armonizado 1:1 con el ID de la base de datos de forma automática.
- Migración de base de datos integrada para sincronizar números de folio en registros históricos sin intervención manual.

### Fixed
- **Armonización de Folio vs ID**: Se eliminó el desfase entre el número de venta ("#731") y el folio ("F00000724"). Ahora ambos coincidirán permanentemente (ej: ID 731 = Folio F00000731).
- **Consistencia en PDF/Reportes**: Se actualizó el motor de reportes y generación de PDFs para que utilicen el folio formateado directamente.
- **Búsqueda Avanzada**: Se optimizó la búsqueda en los reportes de ventas para que localice registros por ID o Folio indistintamente y con mayor precisión.
- **Detalle de Venta**: El título del modal de detalles ahora refleja fielmente el folio de facturación.


### Added
- **Optimización de QR de Clonación**: Rediseño del código QR de clonación (SALE/ORD) con tamaño reducido (2x2) y centrado perfecto en el área de disclaimer. Se eliminó el texto redundante para un acabado más limpio y profesional.

### Fixed
- **Precisión Financiera (Subtotal)**: Se corrigió la discrepancia de redondeo en el ticket térmico; ahora el Subtotal y el IVA mantienen sus decimales y coinciden exactamente con el Total facturado.
- **Protección contra Scanner (Atajos)**: Se reforzó el bloqueo del atajo `Shift+D` durante el escaneo de órdenes (ORD) para evitar que se abran modales de cliente de forma accidental.
- **Diseño de PDFs (Limpieza)**: Se eliminó el campo duplicado de "Total Amount" que aparecía en las cabeceras de los archivos PDF generados.

## [1.9.45] - 2026-04-06
- **Integridad de Clonación (Scanner)**: Se optimizó el motor de detección de códigos de barra para aceptar el prefijo `ORD:` o `SALE:` con mayor precisión, garantizando que el punto de venta cargue los documentos clonados independientemente de la velocidad del escáner.

## [1.9.44] - 2026-04-06
### Corregido
- **Persistencia de Órdenes (Pedidos Fantasma)**: Se eliminó el error que mantenía los pedidos en estado "Pendiente" después de facturarlos. Ahora la orden desaparece automáticamente del modal al concretarse la venta, gracias a una nueva lógica de cierre atómico transaccional.
- **Flujo de Venta Atómica**: Se sincronizó la creación de la factura con el cierre de la orden primaria, eliminando la necesidad de que el cajero borre pedidos manualmente para liberar el stock.

## [1.9.43] - 2026-04-06
### Corregido
- **Integridad de Inventario (Crédito)**: Se resolvió la discrepancia donde los productos variables vendidos a crédito seguían apareciendo como "Reservados". Ahora, toda Factura (contado o crédito) marca los ítems como "Vendidos" inmediatamente, reflejando su salida física del almacén.
- **Restauración de Stock (Cancelaciones)**: Se reparó un error crítico en el motor de anulaciones que impedía devolver las cantidades al stock disponible tras cancelar una venta. El sistema ahora garantiza la bidireccionalidad total de los inventarios (Maestro y Depósitos) en procesos de anulación.

## [1.9.42] - 2026-04-06
### Added
- **Gestión Bancaria (Titulares)**: Se añadió el campo "Titular de la Cuenta" a la estructura de bancos. Ahora se muestra el nombre oficial del dueño de la cuenta en todos los reportes para mayor seguridad en transferencias.
- **Diseño Premium Responsivo**: Rediseño total del selector de bancos en el formulario de vendedores con cards interactivas, estados dinámicos y soporte **Mobile-First** obligatorio.
- **Identidad Local (Pago Móvil)**: Se renombró la etiqueta "Teléfono" a "**Pago Móvil**" en todos los formularios de banco y plantillas PDF para una mejor guía de cobranza.

### Fixed
- **Blindaje de PDFs (Estabilidad)**: Implementado sistema de validación `file_exists` para logos de empresa. El sistema ahora realiza un fallback automático al logo por defecto si el archivo configurado no existe, eliminando errores 500 (pantalla en blanco) al abrir órdenes.
- **Optimización de Datos (Eager Loading)**: Se corrigió la carga de relaciones en el motor de PDF para asegurar que los bancos del vendedor aparezcan siempre en órdenes pendientes y procesadas.

## [1.9.41] - 2026-04-06
### Corregido
- **Creación de Usuarios**: Se corrigió un error crítico de base de datos (SQLSTATE 23000) que impedía la creación de usuarios con roles que no son de venta (como el rol de "Driver"). El sistema ahora inicializa automáticamente los campos de configuración de crédito del vendedor con valores por defecto en lugar de nulos, garantizando la integridad de la base de datos en todas las operaciones de guardado.

## [1.9.40] - 2026-04-01
### Corregido
- **Relación de Pagos:** Se corrigió bug donde pagos aprobados hoy pero subidos ayer aparecían en el reporte de ayer. Ahora al aprobar se mueven a la planilla de hoy.
- **Sincronización Financiera:** Se unificó la lógica de aprobación entre Cuentas por Cobrar y Pagos Parciales.
- **Integridad de Caja:** Se añadió el registro de movimiento en caja para aprobaciones administrativas (faltaba en reportes AR).
- **Planillas de Recaudación:** El total de la planilla (`total_amount`) ahora solo se incrementa al aprobar el pago, garantizando consistencia con los reportes.

## [1.9.39] - 2026-04-01
### Added
- **Automatización de Oficina:** Se implementó un "Override" de automatización para usuarios con rol de oficina. Ahora, al realizar operaciones administrativas, el sistema ignora las restricciones de fletes y comisiones foráneas, permitiendo una gestión centralizada sin bloqueos de permisos.

### Fixed
- **Búsqueda Visual:** Corregido el error de renderizado en el buscador de productos que impedía la selección rápida mediante teclado en dispositivos táctiles.
- **Automatización de Precios:** Corregida la jerarquía de precios en el POS para que los incrementos (comisión, flete y diferencial) se activen automáticamente al seleccionar un cliente.
- **Vendedores Foráneos:** Eliminada la restricción de visibilidad de fletes que afectaba a usuarios con permisos limitados.
- **Persistencia de Precios:** Mejorada la hidratación del componente para mantener los precios inflados tras recargar la página.

## [1.9.38] - 2026-04-01
### Fixed
- **Búsqueda Visual:** Corregido el error de renderizado en el buscador de productos que impedía la selección rápida mediante teclado en dispositivos táctiles.
- **Automatización de Precios:** Corregida la jerarquía de precios en el POS para que los incrementos (comisión, flete y diferencial) se activen automáticamente al seleccionar un cliente.
- **Vendedores Foráneos:** Eliminada la restricción de visibilidad de fletes que afectaba a usuarios con permisos limitados.
- **Persistencia de Precios:** Mejorada la hidratación del componente para mantener los precios inflados tras recargar la página.

## [1.9.36] - 2026-04-01
- **HOTFIX: Visibilidad de Vendedores Foráneos**: Se corrigió el error que impedía visualizar y asignar vendedores con el rol `Vendedor foraneo` al crear o editar clientes. Ahora la lista desplegable unifica a todos los asesores comerciales independientemente de su categoría (Local o Foráneo).

## [1.9.35] - 2026-04-01
- **Optimización de Comisiones (Fecha de Entrega)**: Se implementó una nueva lógica de cálculo para los días transcurridos. Ahora el sistema prioriza la fecha de entrega real (`delivered_at`) registrada por el chofer. En caso de no existir, se otorga automáticamente 1 día de gracia sobre la fecha de factura, protegiendo al vendedor ante retrasos administrativos.
- **Seguridad en Comisiones (Estados de Venta)**: Se blindó el módulo de comisiones y sus reportes PDF para excluir automáticamente facturas con estados no válidos (`returned`, `voided`, `cancelled`, `anulated`), evitando pagos sobre ventas no concretadas.
- **Privacidad y Permisos de Comisiones**: Se implementaron los nuevos permisos `commissions.view_all` y `commissions.view_own`. Ahora los vendedores foráneos solo pueden ver sus propias comisiones en las notificaciones, el dashboard y el módulo principal, mientras que los administradores conservan la visión global.
- **Automatización de Actualización**: Se incluyó una migración de base de datos que registra automáticamente los nuevos permisos durante el proceso de actualización, garantizando que la funcionalidad esté lista sin comandos manuales.
- **Precisión en Pagos de Cuentas por Cobrar**: Se habilitó el campo "Fecha de Pago" como obligatorio para todos los pagos en efectivo (independientemente de la moneda), permitiendo registrar la fecha real del recibo físico para una auditoría financiera exacta.

## [1.9.34] - 2026-03-31
- **Integridad Financiera (Deuda de Clientes)**: Se corrigió un error crítico en el Punto de Venta (POS) donde las facturas marcadas como `returned` (devueltas) o `voided` (anuladas) seguían sumando al saldo total del cliente. Ahora el sistema las excluye automáticamente para garantizar que la deuda mostrada sea 100% real y vigente.
- **Sincronización Web-PDF (Cuentas por Cobrar)**: Se unificaron los filtros de exclusión de estatus en todo el ecosistema de reportes. Las facturas anuladas, devueltas o canceladas ahora desaparecen consistentemente tanto del tablero interactivo como del reporte impreso.
- **Blindaje de Cobros**: Se eliminó la visibilidad del botón "Pagar" para facturas inactivas en el reporte de Cuentas por Cobrar, evitando intentos de cobro sobre documentos ya procesados comercialmente.
- **Precisión en Totales de Reporte**: Se refactorizó el motor de cálculo del reporte de Cuentas por Cobrar para que los totales de cabecera (Costo, Venta, Ganancia, Deuda) reflejen la suma global de TODOS los resultados filtrados, corrigiendo el bug anterior que solo calculaba basándose en los registros de la página actual.

## [1.9.33] - 2026-03-31
- **Mejora de Experiencia de Usuario (Catálogo)**: Se añadió un retraso (debounce) de 500ms en los campos de Costo, Incremento, Margen y Precio de Venta. Esto evita que el sistema borre o sobreescriba lo que el usuario está escribiendo antes de terminar el ingreso de datos, permitiendo una edición de precios más fluida y sin interrupciones.

## [1.9.32] - 2026-03-31
- **Reinicio Automático de Base de Datos**: Se ha forzado esta nueva versión para garantizar que el sistema ejecute obligatoriamente cualquier migración pendiente (ID de bobinas, metadatos) en los clientes que se quedaron en un estado intermedio.
- **Limpieza de Caché Interna**: Se añadió una directiva de limpieza de caché automática durante el proceso de actualización para asegurar que los componentes de Livewire reflejen los cambios inmediatamente.

## [1.9.31] - 2026-03-31
- **Corrección Crítica (Auto-Update SQL)**: Se implementó un middleware de **Auto-Migración** que detecta cambios en el esquema de la base de datos tras una actualización de código. Esto resuelve el error `Unknown column 'metadata'` de forma totalmente transparente para el cliente sin necesidad de comandos manuales.
- **Persistencia de Bobinas**: Se añadieron las columnas `metadata` faltantes en las tablas `sale_details` y `order_details` para garantizar el registro exacto de las bobinas físicas en cada pedido y venta.

## [1.9.30] - 2026-03-31
- **Corrección Crítica (Auto-Update SQL)**: Se implementó un middleware de **Auto-Migración** que detecta cambios en el esquema de la base de datos tras una actualización de código. Esto resuelve el error `Unknown column 'metadata'` de forma totalmente transparente para el cliente sin necesidad de comandos manuales.
- **Persistencia de Bobinas**: Se añadieron las columnas `metadata` faltantes en las tablas `sale_details` y `order_details` para garantizar el registro exacto de las bobinas físicas en cada pedido y venta.

## [1.9.29] - 2026-03-31
- **Individual en Carrito (Ventas/Compras)**: Se implementó una lógica de eliminación basada en índices y UIDs únicos en el resumen de venta, permitiendo que múltiples bobinas o productos iguales coexistan como filas independientes y puedan ser eliminados uno a uno sin afectar al resto de la familia de productos.
- **Trazabilidad de Inventario**: Al eliminar un ítem o cancelar la venta/compra, el estado de los productos variables (`ProductItem`) se restaura automáticamente a "**Disponible**" en la base de datos, garantizando una integridad absoluta del stock físico en todo momento.
- **Estabilidad de Interfaz**: Se resolvieron errores de reactividad de Livewire (`PropertyNotFoundException` y desajustes de índices) en el resumen de venta, facilitando un flujo de trabajo fluido y sin interrupciones técnicas.
- **Unificación de Modales**: Se estandarizaron los identificadores de modales en el módulo de compras para resolver conflictos de DOM al añadir productos por peso/unidad (`variableItemModal`).

## [1.9.28] - 2026-03-30
- **Transparencia en Pagos Zelle**: Se eliminó la etiqueta "Desconocido" en todos los canales de reporte (Arqueo en Vivo, PDF de Venta Diaria, PDF de Arqueo y Ticket Térmico). Ahora el sistema carga forzosamente el nombre del remitente y la referencia bancaria para cada transacción Zelle, garantizando la trazabilidad financiera total solicitada por la gerencia.
- **Segregación de NC Antiguas**: Se implementó una lógica que detecta si una Nota de Crédito pertenece a una factura de días anteriores. Estas NC aparecerán etiquetadas como "**Venta Antigua**" y no afectarán el arqueo de ventas de hoy, resolviendo la discrepancia de los $36.71 detectada en la auditoría técnica.
- **Impacto en Arqueo**: Las NC de días anteriores marcadas como "Reducción de Deuda" o "Billetera" ya no restan de la responsabilidad física del cajero hoy, manteniendo el arqueo perfectamente sincronizado con el dinero real en caja.
- **Etiquetas en PDF**: Se añadió la columna "Afecta Caja" en la tabla de NC del reporte diario para total transparencia para el cliente.

## [1.9.27] - 2026-03-30
- **Transparencia en Facturas**: Se añadió el desglose de "EFECTIVO USD" en la descripción del reporte PDF para facturas mixtas, asegurando que la suma de pagos coincida visualmente con la columna de Dólares.
- **Sincronización de Totales**: Se repararon los acumuladores de pie de página (Neto, Dólares, Créditos, VED, COP) para garantizar que coincidan exactamente con la suma de las transacciones individuales.

## [1.9.26] - 2026-03-30

### Fixed
- **Reporte de Ventas Diarias (Matemática)**: Corregido un bug crítico donde el "Total Contado" del encabezado se sobreescribía con el "Total Bruto", causando reportes de ingresos inflados que no reflejaban la realidad de la caja.
- **Reporte de Ventas Diarias (Layout)**: Renombrada la columna "Contado" a "Dólares" y ajustada su lógica para que solo muestre el componente pagado en USD/Divisas. Ahora, si se paga en Bolívares o Pesos, el monto aparece en sus respectivas columnas y se muestra como 0 en la columna de Dólares (tal como lo solicitó el cliente).
- **Reporte de Ventas Diarias (Redundancia)**: Eliminada la columna "Divisas" del final de la tabla para un diseño más limpio y evitar duplicidad de información.
- **Claridad de Totales**: Se mejoraron las etiquetas del bloque de resumen superior ("Total Cobrado Eq. USD", "Total Neto Facturado", "Total Ingresos Caja") para una mejor interpretación administrativa por parte de los supervisores.

## [1.9.25] - 2026-03-30

### Fixed
- **Reporte de Ventas Diarias (PDF)**: Corregido el error de layout donde facturas con múltiples pagos (como la factura 629) causaban que las columnas se desplazaran horizontalmente. Ahora los detalles de pago se muestran en líneas separadas dentro de la columna de descripción, respetando el ancho de la tabla y facilitando la impresión.

## [1.9.24] - 2026-03-30

### Fixed
- **Conciliación de Arqueo de Caja**: Se unificó la lógica financiera entre el Dashboard (Livewire), el Reporte PDF y el Ticket Térmico. Ahora los tres canales de reporte reflejan un "Total a Entregar" consistente y matemáticamente exacto.
- **Segregación de Billetera Virtual**: Se implementó la visualización clara de los movimientos de billetera (Custodia Hoy y Consumo de Saldo Anterior) en todos los reportes de arqueo.
- **Precisión en Ventas del Día**: Ahora el arqueo reporta el flujo de caja **NETO** (Ventas menos Devoluciones), eliminando la inflación artificial de ingresos cuando se generan Notas de Crédito que se quedan en custodia.
- **Ticket Térmico Profesional**: Se rediseñó el ticket de corte (`PrintTrait`) para detallar los pagos por Banco y Zelle (incluyendo emisor/referencia) y mostrar el desglose de movimientos de billetera.

## [1.9.23] - 2026-03-30

### Added
- **Previsualización de Reporte Cuentas por Cobrar**: Se implementó un nuevo botón "Previsualizar" con icono de ojo que abre un modal con un visualizador PDF (iframe), permitiendo revisar el reporte antes de descargarlo.
- **Rediseño Profesional de Reporte AR**: Se reestructuró totalmente la plantilla PDF (`accounts-receivable-pdf`) siguiendo un diseño de alta gama:
    - Agrupación por cliente con bloques de información (Código, Dirección, Teléfono, etc.).
    - Detalle de transacciones (Operación, Emisión, Vencimiento, Días de Mora, No. Doc, Descripción y Monto).
    - Desglose matemático exacto: La línea de "Factura" muestra el saldo previo y las "N/C" se restan visualmente para que el total por cliente sea intuitivo y cuadre a simple vista.
- **Optimización de Paginación**: Se corrigió un error de DomPDF que generaba grandes espacios en blanco al inicio de página. Ahora el reporte corta y fluye naturalmente entre hojas.
- **Control de Acceso Dinámico**: El reporte PDF ahora respeta estrictamente los permisos del usuario; si el operador no tiene permiso de "Ver todo", el reporte solo incluirá sus propios movimientos.

## [1.9.22] - 2026-03-28

### Fixed
- **Anulación de Pagos (Cuentas por Cobrar)**: Se corrigió un error crítico de base de datos (SQL 1265 - Data truncated) que ocurría al intentar anular un pago, causado por un valor de estado inválido.
- **Sincronización de Caja**: Ahora, al anular o eliminar un pago desde el reporte de Cuentas por Cobrar, el monto se resta automáticamente de la Hoja de Recaudación (Collection Sheet) para mantener la integridad del cuadre de caja.
- **Estado de Factura**: Se optimizó la lógica de liquidación para que las facturas regresen automáticamente al estado "Pendiente" si, tras una anulación de pago, el saldo deja de estar cubierto, permitiendo una trazabilidad exacta de la deuda.

## [1.9.21] - 2026-03-28

### Added
- **Persistencia de Depósito en Compras**: Se añadió el campo `warehouse_id` a la tabla de `purchases` para mantener un registro histórico de en qué almacén entró la mercancía comprada.
- **Trazabilidad en Kardex**: El reporte de Movimientos de Producto (Kardex) ahora muestra el nombre real del depósito para las nuevas compras.

### Fixed
- **Filtro de Depósito en Kardex**: Se corrigió el filtrado por almacén que no se estaba aplicando correctamente en las consultas SQL del reporte.
- **Filtro de Devoluciones**: Se integró el filtrado por depósito en los movimientos de Notas de Crédito/Devoluciones dentro del Kardex.
- **Interfaz de PDF**: Se reparó el botón "Cerrar" del modal de previsualización de PDF que no respondía a la interacción del usuario.

## [1.9.20] - 2026-03-28

### Fixed
- **Automatización de Notificaciones (Ventas y Abonos)**: Se corrigió la lógica de envío automático para que los Abonos (Recibos de Pago) se despachen instantáneamente por correo electrónico, al igual que las ventas.
- **Generación de PDF de Pagos**: Se implementó una nueva plantilla de PDF específica para los recibos de pago (`payment-history-pdf`), asegurando que las notificaciones de abono adjunten el documento correcto y no la factura de venta original.
- **Sincronización de Preferencias del Cliente**: Se optimizó el motor de notificaciones para que respete estrictamente los interruptores de "Notificar Ventas" y "Notificar Abonos" de cada cliente. Ahora, si se desactiva una opción, el sistema deja el mensaje "En Cola" en lugar de enviarlo automáticamente, manteniendo la sincronización entre lo que se ve en la configuración y el comportamiento del servidor.
- **Estabilidad del Worker**: Se implementó la recarga forzada del modelo `Customer` desde la base de datos en cada tarea de notificación, eliminando problemas de datos obsoletos o nulos en el servidor de segundo plano.

## [1.9.19] - 2026-03-27

### Added
- **Edición de Cargos**: Añadida la opción para modificar los Cargos "Pendientes". Los usuarios pueden actualizar las cantidades, eliminar productos, cambiar el motivo y ajustar el detalle antes de aplicar el impacto definitivamente al inventario.

### Changed
- **Procesos en Segundo Plano Eficientes (Colas)**: Para evitar cuelgues al crear Cargos o procesos largos, se implementó que la generación de recibos PDF, envíos de correo del comprobante y notificaciones vía WhatsApp API ahora corran 100% en cola de procesos (background Database Jobs) usando el Worker del sistema configurado mediante NSSM. Se migró el entorno y el sistema es ahora considerablemente más fluido.


## [1.9.18] - 2026-03-26

### Changed
- **Requisición (Déficit)**: Simplificación de la columna "Déficit (A Comprar)". Se eliminó el texto redundante y ahora sólo se muestran los íconos de colores junto con los números precisos para mejorar el minimalismo visual y evitar distractores en pantalla.


## [1.9.17] - 2026-03-26

### Changed
- **Mejoras Visuales en Requisición**: Se rediseñó la columna "Déficit (A Comprar)" en la tabla de Sugerencias de Compras. Ahora muestra textos explícitos con código de colores en lugar de números matemáticos: Verde ("Óptimo") para stock en cero déficit, Rojo ("Faltan X") cuando la mercancía realmente falta, y Azul ("Sobran X") cuando el inventario cruza holgadamente el máximo sugerido. Ninguna cantidad negativa causará confusión nuevamente.


## [1.9.16] - 2026-03-26

### Added
- **Métricas Financieras en Catálogo**: Se agregó la columna de "Costo" ($) y la de "Inc. / Margen" (%) a la tabla principal de productos. Esto permite a los usuarios previsualizar el costo real, el incremento sobre el costo y el margen de ganancia de venta sin necesidad de entrar al modo de edición.


## [1.9.15] - 2026-03-26

### Added
- **Actualización Masiva de Precios**: Nueva sección en la Configuración del Sistema que permite ajustar (aumentar o descontar) el Costo de Compra o el Precio de Venta mediante un porcentaje. Cuenta con filtros obligatorios por Categoría y/o Proveedor y panel de confirmación irreversible.
- **Sincronización Bidireccional de Precios**: Se agregó un nuevo campo en el catálogo de productos para el "Porcentaje de Incremento sobre Costo". Ahora el sistema sincroniza automáticamente en tres vías: Margen de Ganancia, Porcentaje de Incremento y Precio de Venta.

## [1.9.14] - 2026-03-26

### Fixed
- **Historial de Pagos**: Reescritura del archivo `historypays.blade.php` para restaurar los botones y funcionalidades perdidas (Aprobar, Rechazar, Anular, Imprimir Recibo) manteniendo la corrección de la estructura "Multiple Root Elements" de Livewire.

## [1.9.13] - 2026-03-26

### Fixed
- **Estatus Cuentas por Cobrar**: Corregida lógica central donde las facturas no cambiaban a "Pagado" al saldar la deuda restante usando devoluciones (Notas de Crédito).
- **Corrección Retroactiva Automática**: Incluida migración que escanea facturas históricas a crédito en estado "Pendiente" y las marca como pagadas si su saldo fue cubierto por devoluciones, generando sus respectivas comisiones.

## [1.9.12] - 2026-03-26

### Added
- **Módulo de Reporte de Inventario (Stock)**: Nueva interfaz profesional inspirada en el reporte de despacho para la gestión de existencias.
- **Configuración de Columnas Dinámicas**: Capacidad de activar/desactivar campos como SKU, Nombre, Categoría, Proveedor, Costo, Precio y Valuaciones.
- **Campo de Conteo Físico**: Opción para incluir una columna vacía en el PDF diseñada para inventarios manuales con lápiz/bolígrafo.
- **Columna de Utilidad (UT. %)**: Visualización del margen de ganancia por producto basado en el costo y precio de venta actual.
- **Firmas Personalizables**: Selección dinámica de hasta 4 líneas de firma (Elaborado, Autorizado, Gerencia, Auditoría/Almacén) al pie del reporte.
- **Acceso Directo en Sidebar**: Integración del nuevo reporte en la sección de "REPORTES" del menú principal.

### Changed
- **Plantilla de Orden de Compra**: Optimización del diseño del PDF de compras, incluyendo la columna "Nuevo Costo" y mejora en la disposición de la información del proveedor.

## [1.9.11] - 2026-03-26
### Added
- Feature for Purchase Order PDF generation with an empty "Nuevo Costo" column for manual entry.
- `PurchaseController` for handling purchase report requests.
- Custom PDF template `invoice-purchase-order.blade.php` for professional purchase orders.
- "Print" action button in the "Procesar Ordenes de Compra" modal.

## [1.9.10] - 2026-03-26

### Fixed
- **Solución Definitiva a 'Multiple Root Elements'**: Reescrita la estructura de `historypays.blade.php` para garantizar un balance perfecto de etiquetas div, permitiendo que el componente `purchase-partial-payment` se monte correctamente en Livewire sin excepciones estructurales.

## [1.9.9] - 2026-03-26

### Fixed
- **Excepción de Raíz Livewire**: Corregido el error de "Multiple Root Elements" en `historypays.blade.php` al envolver los estilos y el contenido en un único div. Esto permite que el modal de Abonos funcione sin errores.

## [1.9.8] - 2026-03-26

### Fixed
- **Balance de Etiquetas HTML**: Corregida la falta de cierre de divs en `historypays.blade.php` que causaba la rotura del diseño responsivo en Ventas y Compras, obligando a los componentes a apilarse verticalmente.

## [1.9.7] - 2026-03-26

### Fixed
- **Estructura de Rejilla - Compras**: Implementación de `container-fluid` y corrección de etiquetas Livewire para garantizar que el sidebar se mantenga a la derecha y no se desplace debajo de la tabla.

## [1.9.6] - 2026-03-26

### Fixed
- **Layout Responsivo - Compras**: Cambio de breakpoints (lg a md) para evitar que el resumen de compra se baje en pantallas medianas o portátiles.

## [1.9.5] - 2026-03-26

### Added
- **Rediseño Premium - Compras**: Interfaz totalmente renovada con sidebar de resumen fijo, diseño de tarjetas redondeadas, gradientes modernos y mejora en la legibilidad de la tabla de items.

## [1.9.4] - 2026-03-26

### Fixed
- **Estructura Blade de Pagos**: Corregida la presencia de etiquetas de cierre div redundantes en `historypays.blade.php` que causaban errores de "Multiple root elements" en el módulo de compras y abonos.

## [1.9.3] - 2026-03-26

### Fixed
- **Estabilidad del Módulo de Compras (Crucial)**: Corregido un error estructural de "Multiple root elements" en la vista Blade que impedía el acceso al módulo de compras.
- **Null-Safety en Inicialización**: Añadida protección nula al cargar la configuración por defecto del almacén en el módulo de compras, previniendo cuelgues al iniciar.
- **Ambigüedad en Consultas de Reportes**: Resueltos errores de "Column 'created_at' is ambiguous" en los reportes mediante el uso de prefijos de tabla explícitos.
- **Gestión de Proveedores en Órdenes**: Corregida la visualización y filtrado de órdenes de compra que no tienen un proveedor asociado (común en órdenes automáticas desde Requisición).

### Changed
- **Reporte de Billetera (Ingresos vs Uso)**: El reporte de Arqueo de Caja ahora desglosa correctamente el uso de la billetera virtual, diferenciando entre ingresos del día (devoluciones) y pagos realizados con saldos anteriores.

## [1.9.2] - 2026-03-25

### Added
- **Precisión Financiera de 4 Decimales (Soporte Global)**: Implementación de arquitectura completa para soportar 4 decimales en toda la cadena de suministro y ventas. 
    - **Base de Datos**: Migración masiva de columnas de costo y precio de `decimal(15, 2)` a `decimal(15, 4)` en productos, órdenes, ventas, compras e inventario.
    - **Configuración Dinámica**: El sistema ahora lee `getDecimalPlaces()` de forma centralizada para aplicar el redondeo configurado en todos los cálculos y traits.
    - **Visualización en PDF**: Actualización de plantillas de reportes de Ventas Diarias y Cuadre de Caja para mostrar montos con 4 decimales.

### Changed
- **Motor de Interfaz (UX)**: Mejorada la visualización de precios en el POS para mostrar ceros a la derecha (ej. `12.4300`) mediante `number_format`, garantizando claridad en productos con precios de alta precisión.
- **Validación de Entradas**: Las funciones globales de JavaScript (`justNumber`, `validarInputNumber`) ahora permiten hasta 4 decimales durante el tipeo manual.
- **Módulos POS y Compras**: Se añadió el atributo `step="0.0001"` a todos los campos de entrada de costos y precios para permitir ajustes granulares sin restricciones del navegador.

### Fixed
- **Redondeo en Inventario**: Corregida la visualización de valorización de stock que estaba forzada a 2 decimales en la vista de inventario.

## [1.9.1] - 2026-03-24

### Added
- **Flujo de Aprobación de Descargos**: Migración completa del motor de flujo de trabajo para salidas de inventario (Descargos / Ajustes de Reducción). Ahora las salidas se registran como `Pendientes` y requieren autorización para descontar definitivamente el stock.
- **Notificaciones de WhatsApp para Descargos**: Integración con el motor de WhatsApp para enviar alertas automáticas de nuevas salidas a los supervisores, incluyendo el PDF del ajuste adjunto.
- **Configuración Independiente de Plantillas**: Nueva sección en los Ajustes de WhatsApp para personalizar los mensajes de Descargos de forma separada a los Cargos, con soporte para variables como `[DESCARGO_ID]`. (Permite habilitar/deshabilitar por separado).
- **Estados de Carga (UX)**: Se implementaron indicadores visuales de "PROCESANDO..." y bloqueo de botones al guardar Cargos y Descargos para evitar duplicidad de registros y mejorar la respuesta visual (especialmente durante la generación de PDF).

### Fixed
- **Integridad de Stock en Anulaciones**: Corregido error crítico donde la anulación (void) de un ajuste ya aprobado no revertía el inventario. Ahora, anular un Cargo DECRECE el stock y anular un Descargo lo INCREMENTA, incluyendo la recreación/eliminación de ítems variables (bobinas) para mantener la trazabilidad exacta.
- **Consistencia de Base de Datos**: Actualización de la columna `status` en la tabla de Cargos de ENUM a STRING para evitar el error "Data truncated" al usar los nuevos estados de flujo de trabajo (`rejected`, `voided`).
- **Visualización de Auditoría**: Mejora integral de las vistas de detalle para mostrar quién aprobó, rechazó o anuló cada ajuste, junto con la fecha y el motivo obligatorio de la acción.

## [1.9.0] - 2026-03-24

### Added
- **Adjuntos de PDF en Notificaciones**: Implementación de generación dinámica de documentos PDF para cada Cargo (Ajuste). Los archivos se adjuntan automáticamente tanto al **Correo Electrónico** como al mensaje de **WhatsApp** enviado a los aprobadores.
- **Reporte Detallado de Cargo**: Nueva plantilla profesional de comprobante interno de ajuste de inventario, incluyendo motivo, almacén, items cuantificados y costos valorados.
- **Botón de Descarga Manual**: Se añadió el ícono de descarga de PDF directamente en el listado de cargos para un acceso rápido a los documentos históricos.

## [1.8.99] - 2026-03-24

### Added
- **Flujo de Aprobación de Cargos**: Nueva arquitectura de flujo de trabajo para ajustes manuales de inventario (Cargos). Los ajustes ahora se registran como `Pendientes` y no modifican el stock hasta ser formalmente aprobados por un usuario autorizado.
- **Notificaciones Multi-canal (Avisos de Ajuste)**: Integración automática de notificaciones por **Email y WhatsApp** que se envían a todos los supervisores/administradores al momento de registrar un nuevo ajuste.
- **Gestión de Plantillas**: Nueva sección en la configuración del sistema para activar/desactivar y personalizar los mensajes de notificación de ajustes, incluyendo soporte para variables dinámicas.
- **Justificación Obligatoria**: Se implementó el requerimiento de motivo (textarea) para todas las acciones de **rechazo** y **anulación** de cargos, reforzando la trazabilidad de la auditoría.
- **Permisos de Flujo de Trabajo**: Nuevos permisos granulares `adjustments.approve_cargo`, `adjustments.reject_cargo` y `adjustments.delete_cargo` para un control total del ciclo de vida del inventario.

### Changed
- **Optimización de Productos Variables**: Los detalles de bobinas y items de báscula ahora se almacenan de forma temporal en formato JSON durante la fase pendiente, creándose los registros definitivos de `ProductItem` únicamente tras la aprobación final.

## [1.8.98] - 2026-03-24

### Added
- **Módulo Inicial de Cargos (Ajustes)**: Implementación de la fase 1 del sistema de ajustes de inventario manual con soporte para productos variables (bobinas) y búsqueda optimizada.

### Fixed
- **Restauración de Stock (Integridad)**: Corregido error crítico donde la eliminación de una venta no devolvía el stock al depósito de origen. Ahora se restaura correctamente tanto el stock global como el específico por almacén, incluyendo componentes de productos dinámicos.
- **Redondeo en Ticket (POS)**: Se ajustó el motor de cálculo en el recibo de venta para mostrar el subtotal con 2 decimales exactos, eliminando la discrepancia visual con el total cobrado.

## [1.8.92] - 2026-03-24
 
## [1.8.90] - 2026-03-24

### Added
- **Anulación de Pagos (Cuentas por Cobrar)**: Nueva funcionalidad para anular pagos aprobados sin eliminarlos del historial, permitiendo mantener una auditoría completa.
- **Motivo de Anulación**: Se integró un campo obligatorio para registrar el motivo de la anulación del pago, visible tanto en el historial del sistema como en los reportes PDF.
- **Permisos Granulares**: Se implementaron dos nuevos niveles de seguridad: `payments.void_today` (para anular pagos del mismo día) y `payments.void_anytime` (para anular cualquier fecha).
- **Reversión Automática**: El sistema ahora restaura automáticamente los saldos en los registros de Zelle y Banco vinculados al anular un pago, y devuelve la factura al estado "Crédito" si estaba totalmente pagada.

### Fixed
- **Integridad de Base de Datos**: Se actualizó la columna `status` en la tabla de pagos para soportar el nuevo estado `voided`, evitando errores de truncado de datos.
- **Traducciones**: Se agregaron etiquetas amigables en español para los nuevos permisos en el módulo de asignación.

## [1.8.89] - 2026-03-24

### Fixed
- **Módulo de Productos**: Se corrigió el error de persistencia de datos donde al editar múltiples productos consecutivamente, la información del producto anterior permanecía en los campos. Ahora el sistema realiza una limpieza total del estado al cancelar o cambiar de producto.

## [1.8.88] - 2026-03-24

### Added
- **Corte de Caja (Reporte Detallado)**: Rediseño completo del ticket térmico para ser más explícito. Ahora separa claramente las ventas del día de los abonos de créditos recibidos, ambos desglosados por método de pago y moneda.
- **Exportación a PDF (Corte de Caja)**: Nueva funcionalidad para generar un informe oficial en formato A4 (PDF) con tablas detalladas de arqueo de caja y espacios para firmas de supervisión.
- **Vista Previa de Reportes**: Implementación de un modal de previsualización que permite revisar el PDF sin salir del módulo de Corte de Caja.
- **Filtros Inteligentes de Fecha**: El reporte ahora toma por defecto la fecha actual ("Hoy") si no se especifica un rango, facilitando los cortes diarios rápidos.

## [1.8.87] - 2026-03-24

### Fixed
- **Catálogo de Clientes**: Se relajaron las validaciones para que los campos **CC/Nit** y **Billetera** dejen de ser obligatorios, evitando errores de integridad en la base de datos al dejar campos vacíos.
- **Códigos de Descuento (PP/PD)**: Se implementaron valores por defecto automáticos para evitar fallos cuando el usuario no define un código manual.

## [1.8.86] - 2026-03-24

### Fixed
- **Sincronización de Filtros en Reportes**: Se alinearon los criterios de búsqueda (vendedor y fecha) de la "Hoja de Liquidación" con la "Relación de Despacho", garantizando que el PDF muestre exactamente lo mismo que se visualiza en pantalla.

## [1.8.85] - 2026-03-23

### Added
- **Hoja de Liquidación de Ruta (PDF)**: Nuevo reporte para conciliación administrativa al finalizar recorridos. Incluye desglose por factura, cobranza declarada y novedades.
- **Selector de Choferes (Modo Supervisión)**: Los administradores ahora pueden alternar entre rutas de diferentes choferes desde el dashboard centralizado.
- **Permiso `driver_monitoring`**: Control de acceso granular para la visualización de rutas de terceros.
- **Inicio Masivo de Rutas**: Botón "Iniciar Todas las Rutas" para que el chofer active todos sus pedidos pendientes con un solo clic.

### Fixed
- **Error Intermitente al Guardar Novedades**: Se corrigió el fallo de `Integrity constraint violation (sale_id cannot be null)` mediante validación robusta y estados de carga (`wire:loading`).
- **Prevención de Doble Envío**: Bloqueo del botón guardar para evitar duplicidad de registros y pérdida de estado en modales de cobranza.
- **Validación GPS**: Mejora en la captura de coordenadas al iniciar rutas masivas.

## [1.8.80] - 2026-03-22
### Added
- **Monitoreo Administrativo de Drivers**: Ahora el administrador puede ver el dashboard específico de cualquier chofer haciendo clic en su etiqueta en el mapa o en el reporte de despacho.
- **Vínculos de Seguimiento en Mapa**: Se añadieron enlaces directos en los popups del "Mapa en Vivo" para ver la "Hoja de Ruta" del chofer y el seguimiento individual de cada pedido.
- **Acceso Logístico en Sidebar**: El enlace "MI RUTA" ahora es visible para Administradores y Supervisores bajo el nombre "LOGÍSTICA / RUTAS", facilitando el acceso al monitoreo.

### Changed
- **Arquitectura de Vistas (Livewire 3)**: Se optimizó el renderizado del Mapa y el Dashboard para cumplir estrictamente con el requisito de "Root Element" único de Livewire 3, moviendo los scripts a stacks específicos.
- **Navegación Dinámica**: El dashboard del chofer ahora detecta si está siendo visto por un administrador, mostrando un banner informativo y previniendo la actualización accidental de la ubicación del administrador como si fuera la del chofer.

### Fixed
- **Error Multiple Root Elements**: Se corrigió el fallo crítico de Livewire en el Mapa de Choferes causado por etiquetas HTML mal cerradas y scripts mal posicionados.
- **Data Binding en Dashboard**: Se restauraron propiedades públicas perdidas (tab, sales, historySales) que causaban errores de variable indefinida.



## [1.8.79] - 2026-03-20
### Added
- **Reporte de Despacho (Relación de Despacho)**: Se integró un nuevo reporte detallado bajo el módulo de entregas.
- **Acceso por Módulo**: Se implementó una lógica de visibilidad dinámica en el sidebar, condicionada a la licencia `module_delivery`.

### Changed
- **Diseño de Reportes**: Se unificó la estética del Reporte de Despacho con el diseño premium de "Ventas Diarias", incluyendo agrupaciones por vendedor y secciones de firma (Despacho, Chofer y Recibido).
- **Persistencia de Choferes**: Se optimizó el proceso de carga de usuarios con roles de driver/chofer/repartidor, haciéndolo robusto ante variaciones en los nombres de roles en la base de datos.
- **Ciclo de Vida de Ventas**: Se ajustó la lógica de limpieza pos-venta para preservar la lista de choferes cargada, permitiendo múltiples operaciones consecutivas sin recarga manual.

### Fixed
- **Asignación de Chofer en Venta**: Se corrigió el bug que impedía guardar el `driver_id` en la tabla de ventas al finalizar una factura.
- **Error Spatie RoleDoesNotExist**: Se eliminó la excepción fatal que ocurría cuando un rol de chofer esperado no existía en el sistema.

## [1.8.78] - 2026-03-19
### Added
- **Permiso Forzar Descuento**: Se creó e inyectó un nuevo permiso llamado `payments.force_discounts` que le confiere poderes al usuario para eludir el sistema de control de descuentos por pronto pago o divisa.

### Changed
- **Lógica de Descuentos**: Se habilitó la capacidad a "superusuarios" con el nuevo permiso para activar de forma forzada los switches de descuento en divisas y pronto pago en los módulos de Cuentas por Cobrar y Abonos.
- **Inmutabilidad de la Factura**: Se corrigió el botón de "Actualizar Reglas de Crédito" para que su comportamiento emane una inmutabilidad en la factura: en vez de destruir la configuración y atarla en vivo, ahora crea un nuevo "Snapshot", respetando su valor a futuro si el cliente cambia.

## [1.8.77] - 2026-03-18
### Changed
- **Estética de Reportes**: Se unificaron los criterios de los contadores en el resumen del informe diario. Ahora se leen como "Total Facturas Procesadas" en vez de "Total Transacciones", y el "Total Facturas Eliminadas" ahora indica la cantidad de facturas anuladas y no la sumatoria de sus montos, para una comprensión más limpia.
## [1.8.76] - 2026-03-18
### Changed
- **Reporte de Ventas Diarias**: Se mejoró la visualización de los pagos en divisas (Zelle y Bancos), mostrando el equivalente en dólares de una forma más clara (ej. `(Dólar: $5.04)`). Además, la columna de Bolívares ahora refleja el monto exacto cobrado en moneda nacional en lugar de su conversión a dólares, mejorando la conciliación de caja.
## [1.8.75] - 2026-03-17
### Changed
- **Unificación Estética**: Se aplicaron los nuevos estilos de la "Billetera Virtual" y la disposición de botones (col-4) a todos los módulos de pago, incluyendo Ventas/Abono y Cuentas por Cobrar, para mantener una estética coherente en todo el sistema.

## [1.8.74] - 2026-03-17
### Fixed
- **Estilos en POS**: Se corrigió un error en el que el código CSS de la billetera se mostraba como texto en la parte superior del modal debido a una incompatibilidad con la etiqueta @style.

## [1.8.73] - 2026-03-17
### Changed
- **Interfaz de Pago POS**: Se removió el botón de "Crédito" del modal de pago rápido y se reubicó el botón de "Billetera Virtual" en su lugar.
- **Estética de Billetera**: Se actualizó el color del botón de Billetera a un naranja vibrante y moderno para mejorar la experiencia visual.

## [1.8.72] - 2026-03-17
### Fixed
- **Billetera en Cuentas por Cobrar**: Se habilitó la opción de pago con billetera virtual en el módulo de reportes de cuentas por cobrar, sincronizando el saldo del cliente correctamente.

## [1.8.71] - 2026-03-17
### Fixed
- **Retroactividad de NC**: Se mejoró la lógica de descubrimiento de Notas de Crédito antiguas para que aparezcan en los reportes de días anteriores basándose en su fecha de creación o asociación con facturas pagadas, incluso si no tenían un ID de planilla asignado originalmente.

## [1.8.70] - 2026-03-17
### Added
- **Integración de Notas de Crédito**: Se centralizó la lógica de creación de "Planillas de Cobro" mediante un Trait para asegurar que las Notas de Crédito (NC), manuales o por devolución, se asocien correctamente al reporte diario.
- **Relación de Cobros**: Se mejoró la búsqueda y visualización de Notas de Crédito en el reporte, permitiendo ver el historial completo de transacciones (pagos y NC) por cliente en el PDF.

## [1.8.69] - 2026-03-17
### Fixed
- **Modal de Pago**: Corregido error crítico de visualización en el que los formularios de Zelle y Banco no cargaban correctamente debido a un error de estructura HTML.

## [1.8.68] - 2026-03-17
### Fixed
- **Billetera Virtual en Abonos**: Corregido error de base de datos (SQL 1265) al intentar pagar con la billetera virtual en el módulo de abonos parciales.
- **Billetera Virtual en Abonos**: Se habilitó la visibilidad del botón de "Billetera" en el modal de abonos y se sincronizó el saldo del cliente correctamente.
- **Billetera Virtual**: Se corrigió la lógica de deducción de saldo para asegurar que se reste el monto equivalente en moneda principal, manteniendo consistencia con el punto de venta.

## [1.8.67] - 2026-03-14
### Added
- **Identificación del Operador**: Se añadió el nombre del operador que genera el reporte en el encabezado, debajo del periodo y la moneda de referencia.

## [1.8.66] - 2026-03-14
### Fixed
- **Estética del Reporte**: Se reforzaron las líneas divisorias en los totales de los cuadros de resumen (ahora en color negro sólido).
- **Firmas**: Se reorganizó la sección de firmas para que aparezcan una al lado de la otra, optimizando el espacio al final del reporte.

## [1.8.65] - 2026-03-14
### Added
- **Trazabilidad en Devoluciones**: Se añadieron las columnas "Solicitante", "Aprobador" y "Motivo" a la tabla de Notas de Crédito, garantizando el mismo nivel de control que en las facturas eliminadas.
- **Resumen de Eliminaciones**: Se reemplazó el campo "Total Exento" por el "Total de Facturas Eliminadas" en el resumen general del reporte para una mejor visibilidad de las anulaciones.
- **Base de Datos Atualizada**: Nueva migración para registrar quién solicita y quién aprueba cada devolución de mercancía.

## [1.8.64] - 2026-03-14
### Fixed
- **Detalle de Pagos en PDF**: Corregido error que impedía visualizar el banco, referencia y tasa en la descripción de las ventas.
- **Optimización de Espacio**: Se ajustaron los anchos de las columnas para otorgar más espacio a la descripción del cliente y evitar recortes innecesarios.

## [1.8.63] - 2026-03-14
### Changed
- **Optimización de Reporte de Ventas Diarias**: Se rediseñó el layout del PDF para hacerlo más compacto, permitiendo ahorrar papel sin perder legibilidad.
- **Detalles de Pago Transparente**: Ahora las descripciones de las ventas incluyen el banco y el número de referencia para pagos por Zelle o transferencia bancaria.
### Added
- **Control de Facturas Eliminadas**: Se incorporó una nueva sección que lista las facturas anuladas del día, detallando quién solicitó la eliminación, quién la aprobó y el motivo.
- **Sección de Devoluciones**: Añadida la tabla de Notas de Crédito (Devoluciones) directamente en el reporte diario.
- **Previsualización en Vivo**: Implementado un botón de "Previsualizar" que abre el reporte en una ventana modal antes de descargarlo.
 
## [1.8.62] - 2026-03-14
### Added
- **Visor de PDF Integrado (Modal)**: Se implementó un previsualizador de PDF que se abre en una ventana modal dentro del sistema, permitiendo revisar la Relación de Cobros antes de imprimir o descargar.

## [1.8.61] - 2026-03-14
### Changed
- **Ajustes de Legibilidad en Reporte**: Se aumentó el tamaño de fuente de los registros a 7pt y se incrementó el espacio entre las firmas de "Entregado" y "Recibido" para mayor claridad.

## [1.8.60] - 2026-03-14
### Fixed
- **Estética de Reporte PDF**: Se reforzó la línea divisoria del "Total Ingreso" en el resumen por categoría para asegurar su visibilidad en la generación del PDF.

## [1.8.59] - 2026-03-14
### Changed
- **Rediseño de Pie de Reporte**: Se reorganizó el pie de página de la Relación de Cobros en 3 columnas (Resumen por Categoría, Detalle por Moneda y Firmas) para evitar solapamientos y ahorrar espacio.
- **Doble Firma**: Se añadieron los bloques de firma para "Entregado por (Operador)" y "Recibido por".
- **Optimización de Papel**: Se redujo el tamaño de fuente en los registros de pago de 7pt a 6pt para compactar la información.

## [1.8.58] - 2026-03-14
### Added
- **Tag Personalizable para Pago Divisa**: Ahora puedes configurar el código (Tag) para el descuento por pago en USD (ej. "PD") directamente en la configuración global, por cliente o por vendedor.

## [1.8.57] - 2026-03-14
### Added
- **Configuración de Tags de Descuento**: Se añadió un nuevo campo "Código" (Tag) en la configuración de reglas de descuento (Global, por Cliente y por Vendedor).
- **Identificación de Descuentos en Reportes**: El reporte de Relación de Cobros ahora muestra el código específico del descuento aplicado (ej: "Desc. PP" para Pronto Pago, "Desc. PD" para Pago Divisa) en lugar de un genérico "Desc.".

### Technical
- **Base de Datos**: Nueva columna `tag` en `credit_discount_rules` y `discount_tag` en `payments`.
- **Servicios**: Actualización de `CreditConfigService` para calcular y propagar el tag del descuento aplicado.

## [1.8.56] - 2026-03-14
### Fixed
- **Corrección de Encabezado de Fechas**: Se ajustó el formato de las etiquetas de fecha (Desde/Hasta) y la información de cabecera (Fecha/Hora/Pág) para que coincidan exactamente con la estética del diseño de referencia (formato de dos puntos con espacio y sin negritas).
- **Lógica de Fechas en PDF**: Se implementó una lógica de respaldo para que, en caso de no haber un filtro activo, el reporte muestre automáticamente la fecha de apertura de la planilla de cobro.

## [1.8.55] - 2026-03-13
### Changed
- **Optimización de Reporte PDF**:
    - **Ahorro de Papel**: Se redujo el tamaño de la fuente en las tablas de registros y se ajustaron los márgenes para permitir mayor contenido por página.
    - **Encabezado Detallado**: Añadida la sección de filtros aplicados (Activo, Monedas, Fecha Desde y Fecha Hasta) para coincidir con los estándares de auditoría.
    - **Contador de Movimientos**: Ahora se muestra el "Total Transacciones" al final de cada cliente, facilitando el conteo rápido de operaciones procesadas.

## [1.8.54] - 2026-03-13
### Changed
- **Refinamiento de Relación de Cobros**:
    - **Número de Documento**: Ahora se muestra el Número de Factura (o Nota de Crédito) directamente en la columna principal, facilitando la identificación inmediata de qué se está cobrando.
    - **Cálculo de Días Vencidos**: La columna de días ha sido reprogramada para mostrar los días de mora. (Ejemplo: +10 si está vencida, -2 si se pagó antes de tiempo, 0 si se pagó el día exacto).
    - **Simplificación de Interfaz**: Se eliminaron las columnas de "Adelantos" y "Retenciones" para dar más espacio al contenido relevante, ya que el sistema no utiliza estos métodos.

### Fixed
- **Trazabilidad**: Removida la duplicidad del número de factura en la descripción para una lectura más limpia del reporte.

## [1.8.53] - 2026-03-13
### Added
- **Nueva Relación de Cobros (REPORTE PREMIUM)**: Implementado un nuevo diseño de reporte detallado para planillas de cobro que agrupa los pagos por cliente, facilitando la auditoría y conciliación de saldos recaudados.
- **Detalle por Cliente**: Cada sección del reporte incluye el RIF/CI del cliente, subtotal financiero y un desglose de facturas canceladas con sus respectivos tipos de pago.
- **Integración de Notas de Crédito**: Las Notas de Crédito (ajustes manuales) ahora se guardan vinculadas a la planilla de cobro del día, permitiendo su visualización en el reporte como parte de la gestión de deudas del operador.
- **Resumen Multimoneda y Bancos**: El reporte incluye un nuevo cuadro de resumen al final que desglosa los totales por cada moneda recolectada (USD, VED, COP) y por cada banco configurado (Bancolombia, Banesco, Zelle, etc.) para coincidir perfectamente con la cabecera del documento.

### Changed
- **Lógica de Auditoría**: Añadida una columna de "DÍAS" que calcula automáticamente el tiempo transcurrido desde la emisión de la factura hasta el pago realizado, permitiendo detectar de forma visual los pagos en mora.
- **Identificación de Banco**: Se mejoraron las descripciones automáticas en el reporte para incluir el nombre del banco y el número de referencia en transferencias, y el nombre del pagador en reportes de Zelle.

### Fixed
- **Trazabilidad**: Corregida la falta de vinculación de las Notas de Crédito manuales a las planillas de cobro, asegurando que todos los movimientos contables aparezcan en los cierres de caja correspondientes.

## [1.8.52] - 2026-03-13
### Added
- **Notas de Crédito Manuales**: Implementada la funcionalidad de "Ajustes de Saldo" que permite realizar descuentos de deuda manuales directamente desde el modal de abonos sin necesidad de devolver productos físicos.
- **Multimoneda en Ajustes**: Selector de moneda integrado en la nota de crédito manual con cálculo automático de equivalencia hacia la moneda de la deuda.
- **Seguridad**: Nuevo permiso `payments.create_credit_note` para restringir quién puede aplicar ajustes manuales a las facturas.

### Changed
- **Interfaz de Pagos**: Rediseñado el resumen de descuentos para mayor claridad. Ahora solo se muestran las filas de descuento que están activamente seleccionadas (Pronto Pago o Divisa), eliminando el texto tachado y mejorando el enfoque visual.
- **Iconografía**: Actualizado el sistema de iconos en reportes y listados. Los ajustes manuales ahora se identifican con un icono de factura naranja para diferenciarlos visualmente de las devoluciones de mercancía (amarillo).
- **PDF de Notas**: Adaptado el generador de PDF para imprimir descripciones personalizadas de "AJUSTE DE SALDO" cuando se detecta un retorno de tipo manual.

### Fixed
- **Base de Datos**: Corregido error de truncado en la tabla `sale_returns` al permitir el tipo de retorno 'manual' mediante una nueva migración.

## [1.8.51] - 2026-03-13
### Added
- **Abonos Parciales**: Implementada paginación en el listado de ventas pendientes por abonar para mejorar el rendimiento.
- **Vendedores**: Agregada columna "Vendedor" con distintivo de color identificador en el modal de abonos.
- **Búsqueda Avanzada**: Habilitada la búsqueda por nombre de vendedor en el modal de abonos.
- **Notas de Crédito**: Agregada visualización de iconos de Notas de Crédito (devoluciones) con acceso directo al PDF en el modal de abonos y reportes de ventas/cuentas por cobrar.

### Fixed
- **Estabilidad**: Corregido error de variable indefinida `$sales` al filtrar ventas en el componente de pagos parciales.

## [1.8.50] - 2026-03-12
### Fixed
- **Fechas de Pago**: Corregida la lógica de registro de fechas en pagos y abonos. Ahora el sistema prioriza y utiliza la fecha seleccionada en el formulario (fecha del voucher) en lugar de la fecha actual del sistema.
- **Historial de Pagos**: Actualizada la visualización del historial para mostrar la fecha real del depósito/transferencia, facilitando la conciliación bancaria.
- **Lógica de Descuento**: La validación de "pago a tiempo" para descuentos ahora utiliza la fecha del pago seleccionada por el usuario, evitando penalizar a clientes por registros tardíos del personal administrativo.
- **UI de Pagos**: Se añadió el campo de "Fecha de Pago" a la sección de bancos estándar para permitir registros históricos precisos.
- **Permisos**: Corregida la visibilidad y traducción del permiso "Actualizar Reglas de Crédito" en el módulo de asignación de permisos.
- **Edición de Pagos**: Asegurada la consistencia de fechas al editar pagos pendientes, manteniendo la fecha del comprobante original.

## [1.8.49] - 2026-03-12
### Fixed
- **Descuentos**: Corregida la visibilidad de las alertas de descuento en el módulo de abonos. Ahora las opciones de "Pronto Pago" y "Pago en Divisa" aparecen de inmediato al abrir el modal, permitiendo su selección manual antes de registrar el primer pago.
- **Descuentos**: Implementada exclusividad mutua entre descuentos. Activar uno desactiva automáticamente el otro para evitar errores de cálculo.
- **Ventas Foráneas**: Optimizada la detección de ventas foráneas. Ahora se marcan correctamente aunque no se seleccione aplicar comisiones al momento de la venta, siempre que haya un vendedor asignado.
- **Compatibilidad**: Expandida la disponibilidad del descuento por divisa para ventas existentes basadas en la configuración del cliente, incluso si no fueron marcadas originalmente como foráneas.

## [1.8.48] - 2026-03-12
### Fixed
- **Cuentas por Cobrar**: Corregido un error de servidor (Hotfix) al intentar abrir el modal de pago. El error era causado por variables no definidas en la última actualización de cálculo de devoluciones.

## [1.8.47] - 2026-03-12
### Fixed
- **Descuentos y Devoluciones**: Corregido el cálculo de descuentos (Pronto Pago y Pago en Divisa) cuando existen devoluciones parciales. Ahora el sistema calcula el beneficio sobre el monto neto real de la factura (Total original menos productos devueltos) en lugar de usar siempre el monto original de venta. Esto aplica tanto en el módulo de Abonos como en el de Cuentas por Cobrar.

## [1.8.46] - 2026-03-12
### Fixed
- **Pagos**: Mejora integral en la eliminación de abonos. Ahora el sistema elimina completamente los registros de **Zelle** y **Transferencias Bancarias** vinculados a un pago si este era el único que los utilizaba. Esto evita que queden referencias "fantasmas" marcadas como usadas que bloquean nuevos reportes del mismo pago.
- **Validaciones**: Se ajustó la precisión decimal al restaurar saldos para garantizar que el estatus vuelva a "Unused" (No usado) correctamente.

## [1.8.45] - 2026-03-12
### Fixed
- **Pagos**: Corregido el error de integridad (SQL 1451) al eliminar abonos que comparten una misma transferencia o depósito bancario. Ahora el sistema reintegra el saldo al registro bancario original y solo lo elimina si no existen otros pagos vinculados a él.
- **Pagos**: Se aseguró el orden correcto de eliminación (Pago primero, Registro Bancario después) en todos los módulos de gestión.

## [1.8.44] - 2026-03-12
### Fixed
- **Filtros de Ordenes**: Corregido el filtro de "Vendedor" para que sea omnicanal. Ahora, al seleccionar un usuario en el desplegable, se filtran las órdenes donde este sea el **Vendedor Responsable** del cliente O el **Operador** que creó la orden.
- **Búsqueda**: Reforzada la búsqueda por texto para incluir simultáneamente el nombre del Operador, Vendedor y Cliente.

## [1.8.43] - 2026-03-12
### Added
- **Ordenes de Venta**: Se separaron las figuras de "Vendedor" y "Operador" en la lista de órdenes procesables. 
    - **Vendedor**: Ahora muestra el vendedor asignado al cliente de la orden, incluyendo su color identificador.
    - **Operador**: Nueva columna que indica el usuario del sistema que creó la orden.
- **Filtros**: El filtro de búsqueda por vendedor ahora actúa sobre el vendedor asignado al cliente, facilitando el seguimiento de carteras de clientes por vendedor.

## [1.8.42] - 2026-03-12
### Changed
- **Navegación**: Se agregaron etiquetas descriptivas (tooltips) a los iconos de notificaciones en la barra superior ("Cuentas por Pagar", "Créditos Vencidos / Cuentas por Cobrar" y "Comisiones Pendientes") para hacerlos más intuitivos para el usuario.

## [1.8.40] - 2026-03-11
### Fixed
- **Configuración de Crédito**: Solucionado un error ("MethodNotFoundException") que ocurría si intentabas dar click al botón de "Actualizar Reglas de Crédito" desde la página de Cuentas por Cobrar en lugar de dentro de una venta específica.

## [1.8.39] - 2026-03-11
### Changed
- **Catálogos**: Se incrementó el límite de caracteres en el nombre de los Clientes, Usuarios (Vendedores) y Proveedores. Anteriormente permitían máximo 45, 85 y 50 caracteres respectivamente. Ahora todos permiten registrar nombres de hasta 200 caracteres de longitud.

## [1.8.38] - 2026-03-11
### Fixed
- **Abonos**: Corregido el error de eliminación ("Integrity Constraint Violation 1451") que ocurría al intentar borrar un depósito bancario o transferencia pendiente (BankRecord) eliminando primero el recibo de pago y luego el registro del banco.

## [1.8.37] - 2026-03-11
### Fixed
- **Configuración de Crédito**: Corregido un error técnico (TypeError) que ocurría al actualizar las reglas de crédito desde el Historial de Pagos, permitiendo que la tabla de historial se refresque correctamente.

## [1.8.36] - 2026-03-11
### Added
- **Configuración de Crédito**: Añadida la opción "Actualizar Reglas de Crédito" en el Historial de Pagos. Permite a los administradores forzar que una factura pendiente herede la configuración de crédito más reciente del cliente, resolviendo casos donde los descuentos por "Pronto Pago" no aplicaban debido a reglas antiguas guardadas en la factura.
- **Permisos**: Nuevo permiso `sales.reset_credit_snapshot` para controlar quién puede actualizar las reglas de crédito de una factura.

## [1.8.35] - 2026-03-11
### Fixed
- **Pagos**: Corregido bug donde la opción de "Pago en Divisa" aparecía incorrectamente al editar un pago aunque la venta tuviera abonos previos en Bolívares.
- **Pronto Pago**: Actualizado el cálculo de días para basarse en la fecha real del depósito bancario (`payment_date`) en lugar de la fecha en que se registró en el sistema, asegurando que el cliente no pierda su descuento por demoras administrativas.
- **Reporte de Ventas Diarias**: Corregido el cálculo del total neto y la generación del PDF para usar de forma precisa los montos equivalentes en dólares.

## [1.8.34] - 2026-03-10
### Fixed
- **Comisiones**: Filtradas y ocultadas todas las ventas que tengan una comisión aplicada explícita de `0%`. Ya no aparecerán ni en el Módulo de Comisiones ni en el Reporte de Comisiones, incluso si el vendedor tiene un porcentaje predeterminado en su perfil.

## [1.8.33] - 2026-03-10
### Added
- **Búsqueda Global**: Implementada la búsqueda por Número de Factura en los módulos Abonos a Cuenta, Reporte de Ventas, Cuentas por Cobrar, Relación de Pagos y Comisiones. 
- **Búsqueda Global**: Activada la tecla `Enter` como disparador principal para ejecutar búsquedas sin necesidad de click en los reportes mencionados.

### Fixed
- **Filtro de Vendedores**: Modificadas las consultas de la barra superior en todos los reportes (Ventas Diarias, Cuentas por Cobrar, Comisiones, Relación de Pagos y Reporte de Ventas) para incluir correctamente al rol "Vendedor foraneo" junto con el "Vendedor" regular.
- **Estatus Visuales**: Corregido un fallo en el componente de *Cuentas por Cobrar* donde las ventas "pendientes" compartían el característico color rojo de las "retornadas". Ahora las retornos conservan el color rojo (`badge-danger`), mientras que las pendientes usan un amarillo informativo (`badge-warning`).

## [1.8.32] - 2026-03-10
### Added
- **Auditoría de Pagos**: Añadido campo "Comentario de Modificación" opcional al editar un pago pendiente. El comentario se guarda en base de datos y se muestra en azul en el historial general para justificar correcciones.
- **Gestión de Descuentos**: Los administradores ahora pueden visualizar y alternar en tiempo real los descuentos por "Pronto Pago" y "Pago en Divisa" dentro del modal de edición de pagos.
- **Contexto de Deuda**: Incorporado un nuevo panel superior en el modal de Edición de Pagos que resume "Monto Venta", "Abonado" y "Deuda Actual".
- **Calculador Predictivo**: Añadido indicador dinámico "Saldo Restante Posterior a este Abono" que evalúa en vivo el equivalente en dólares del pago editado más los descuentos activados.

### Changed
- **Edición de Pagos**: La "Tasa de Cambio" ahora es editable por administradores, recalculando instantáneamente el equivalente en dólares antes de aprobar o denegar.
- **Vendedores Foráneos**: Optimizada la vista de Abonos a Cuenta para mostrar dinámicamente el badge de "Pago por aprobar" y garantizar que el botón "Ver Historial" esté siempre visible si hay depósitos en proceso.

## [1.8.31] - 2026-03-05
### Fixed
- **Abonos:** Excluidos los pagos con estado 'PENDIENTE' o 'RECHAZADO' del cálculo de la "Deuda Actual" en las tablas principales de Cuentas por Cobrar y Abonos.
- **Abonos:** Actualizado el modal "Historial de Pagos" para desglosar el "Total Aprobado" y "Total Pendiente".



## [1.8.30] - 2026-03-04
### Fixed
- **Cart Stability**: Fixed cart reordering when updating quantities. Cart items now stay in their appropriate position.
- **Price Groups**: Fixed price group synchronization when modifying item quantities. Sibling items in a price group accurately update their discounted prices simultaneously without requiring a page reload.
- **PDF Templates**: Fixed `invoice-credit-short.blade.php` and `invoice-paid-short.blade.php` PDF templates so the `Vendedor:` label isn't incorrectly grouped line-wrapped with the due date.

## [1.8.29] - 2026-02-25
### Added
- **Price Groups**: Implemented a new feature that allows grouping multiple products for volume pricing. When products in the same group are added to the cart, their quantities are summed to determine which volume discount tier applies to all members of the group.
- **Price Groups UI**: Added a new "Grupos de Precio" management screen under Catalogos and a dropdown in the Product Edit form (Price Rules tab) to assign products to groups.

### Fixed
- **Price Tiers Persistence**: Resolved a bug where price tiers added to a product would disappear after clicking "Update Product". The system now persists tiers directly to the database and reloads them correctly during Livewire re-hydration.
- **Auto-Recalculate Group Prices**: Fixed an issue where changing the quantity of one product in a group wouldn't immediately update the prices of other group members in the cart. The system now automatically recalculates and updates the entire group whenever any member's quantity changes.
- **Cart Order Stability**: Fixed a bug where updating a product's quantity would cause it to jump to the last position in the cart list. Items now maintain their original order during updates.

## [1.8.28] - 2026-02-25
### Fixed
- **Order PDF**: Fixed an issue where the Customer's specific Credit Configuration (Base discounts, Early Payment Rules, Credit Days) was completely missing from the PDF for Pending and Processed Orders due to a trait naming collision.
- **Order PDF**: Fixed missing currency decimals on Pending and Processed Orders to dynamically read from the system's global `getDecimalPlaces` setting instead of rounding to `0`.
- **Advanced Payments (Zelle/Bank)**: Resolved an "Access Denied / Módulo de pagos avanzados no activo" bug occurring for users with Premium licenses. The payment module incorrectly validated `session('tenant.modules')` instead of the globally updated `config('tenant.modules')`.

## [1.8.27] - 2026-02-25
### Added
- **Global Customer Search**: Added support for searching customers by their Taxpayer ID (RIF/Cedula) across all main modules including Point of Sale (POS), Sales Report, Daily Sales Report, and Accounts Receivable. The Taxpayer ID is now also displayed in the search result dropdown alongside the customer name.

## [1.8.26] - 2026-02-25
### Fixed
- **Sales Module**:
  - **Zero Price Bug**: Fixed a bug where `sale_price` and `regular_price` were saving as `0.00` in the database. The system now correctly maps the `base_price` from the cart.
  - **Freight Calculation**: Fixed an issue where the seller's generic freight percentage was incorrectly overriding the customer's specific prioritized freight percentage during sale finalization.
- **Products Module**:
  - **Checkbox State Preservation**: Fixed an issue in the Product Edit Form where the checkboxes "Venta por Peso/Separado" (`is_variable_quantity`) and "Permite Cantidades Decimales" (`allow_decimal`) would automatically uncheck themselves upon saving if the advanced products module was not active.

## [1.8.25] - 2026-02-23
### Fixed
- **Auto-Updater**:
  - **NSSM Locked File Error**: Fixed `copy(...nssm.exe) failed to open stream` error during system updates. The `nssm/` binary directory, `whatsapp-api/` Node service, and `instalar_servicios.bat` are now excluded from GitHub release ZIPs via `.gitattributes export-ignore`. These files are not needed during an app update and were causing Windows file lock errors because NSSM services were running.

## [1.8.24] - 2026-02-23
### Fixed
- **Users Module**:
  - **Livewire Binding Error**: Fixed `CannotBindToModelDataWithoutValidationRuleException` for `user.phone`, `user.taxpayer_id`, and `user.address`. The fields were defined in the `Store()` method's local `$rules` variable but were missing from the class-level `$rules` property, which Livewire requires to allow `wire:model` binding on Eloquent model fields.

## [1.8.23] - 2026-02-23
### Fixed
- **Service Installer**:
  - **Queue Worker Stuck**: Fixed `instalar_servicios.bat` where the `JSPOS_Queue_Worker` Windows service would fail silently because `php` was referenced as a generic command. NSSM-managed services don't inherit Laragon's PATH, so `php.exe` was never found. The script now auto-detects the full path to `php.exe` using `where php` at install-time and passes the absolute path to NSSM.
  - **Clear Error Message**: Added a user-friendly error message if PHP is not found in the PATH, with a suggestion to run the script from the Laragon Terminal.

## [1.8.22] - 2026-02-23
### Added
- **User Management**:
  - **Contact Information**: Added `phone`, `taxpayer_id` (RIF/CI), and `address` fields to the User profile to improve data completeness and support staff.
  - **Database Migration**: Added a new migration to safely implement these fields without data loss.

### Changed
- **Roles & Permissions**:
  - **SaaS Consistency**: The "Comisiones" and "Config. Crédito" tabs in the user profile are now strictly hidden if the active license plan does not include the `module_commissions` or `module_credits` modules, resulting in a cleaner interface for Basic plans.
  - **Seller Role Availability**: Fixed a bug where the "Vendedor" and "Vendedor Foraneo" roles were incorrectly disabled when advanced modules were off. These roles are now always available for basic cashier/sales operations.
- **Customers Module**:
  - **Clean UI**: Removed the deprecated "Tipo" (Type) field from the customer creation and edit forms, as it was no longer used by the system.

### Fixed
- **WhatsApp Integration**:
  - **Fallback Logic Verification**: Ensured the system correctly falls back to using the Seller's phone number for notifications when the Customer does not have a registered phone number.

## [1.8.21] - 2026-02-20
- **Products Module**:
  - **Search Relevance**: Re-wrote the search algorithm to prioritize exact SKU matches and Name matches before considering Category matches, ensuring accurate results (e.g., searching "Tenedor" no longer shows "Contenedores" first).
  - **Pagination Bug**: Fixed a persistent bug where clicking on page 2 or beyond while a search filter was active would instantly bounce the user back to page 1. Pagination now behaves correctly during filtered searches.
- **System Internals**: Addressed a risk where clients might forget to manually run database commands after updating by enforcing auto-migrations in the internal release documentation.

## [1.8.20] - 2026-02-20
### Added
- **Payments**:
  - **Consultation Modules**: Implemented full support for viewing "Cash Sales" (Ventas de Contado) within the Zelle and Bank consultation screens.
  - **Database Links**: Added `salePaymentDetails` relationship to `ZelleRecord` and `BankRecord` models.
  - **PDF Reports**: Updated Zelle and Bank exported PDF reports to include cash sale usages alongside regular credit payments.
  - **UI/UX**: Cash sales are now clearly distinguished with a green "(Contado)" label in the UI, separating them from standard "(Abono)" credits.



## [1.8.19] - 2026-02-19
### Fixed
- **Permissions**:
  - **Super Admin Paradox**: Resolved an issue where Super Admins were blocked from certain UI actions (like changing invoice currency) because they implicitly held conflicting permissions. Logic updated to rely on positive permissions only.
  - **Legacy Cleanup**: Migrated old permissions (`aprobar_cargos`, `metodos de pago`) to new, standardized keys (`adjustments.approve_cargo`, `payments.methods`).
  - **Orphans**: Removed unused legacy permissions (`compras`, `clientes`, etc.) to clean up the assignment UI.
  - **Auto-Migration**: Added a DB migration to legally execute the permission cleanup and reassignment on client update.

## [1.8.17] - 2026-02-17
### Fixed
- **Permissions**:
  - **Missing Permissions**: Resolved an issue where some permissions (e.g., `system.is_foreign_seller`, `sales.switch_warehouse`, `cash_register.bypass`) were missing in some environments because they were defined in separate seeders.
  - **Consolidation**: Consolidated all 125 system permissions into the main `CreatePermissionsSeeder` to ensure consistency.
  - **Auto-Repair**: Included a migration that automatically runs the permission seeder during the update process to restore any missing permissions on existing installations.

## [1.8.17] - 2026-02-17
### Fixed
- **Permissions**: 
    - Fixed translation issue preventing Spanish names and icons from appearing (replaced dot notation with underscores).
    - Updated `CreatePermissionsSeeder` to include the full list of 125 permissions, ensuring synchronization with client environments.

## [1.8.16] - 2026-02-17
### Fixed
- **Customer Import**:
  - **Data Truncated Error**: Resolved an issue where importing customers with type "Minorista" failed due to database enum restrictions. Added "Minorista" to `customers` table and manual creation form.
- **Backup System**:
  - **Mysqldump Path**: Made `mysqldump` binary path configurable via `.env` (`DB_DUMP_PATH`) to support different development environments and prevent "Path not found" errors on client machines.

## [1.8.15] - 2026-02-17
### Added
- **Customers**:
  - **Bulk Import**: Added `CustomerImport` module allowing bulk upload of customers via Excel/CSV.
  - **Intelligent Mapping**: System automatically detects columns like Name, Phone, Email, TaxID, etc.
  - **Seller Assignment**: Can assign sellers by name from the Excel file; creates new seller users if they don't exist.
- **UX/UI**:
  - **Error 419**: Created a custom "Session Expired" page that automatically redirects to Login after 3 seconds, improving user experience.

## [1.8.14] - 2026-02-17
### Changed
- **Reports**:
  - **Sale Detail**: Improved detailed view of sales, specifically handling of multi-currency payments and pending approvals.
  - **Payment Logic**: Adjustments to `PaymentComponent` and `PartialPayment` for better consistency.
- **Sales**:
  - **Refinements**: General improvements and fixes in the Sales module and product management.
  - **Data Handling**: Updates to `DataController` and `PermissionSeeder`.
### Added
- **PDF**:
  - **Customer Debt**: Added new template `customer-debt.blade.php`.

## [1.8.13] - 2026-02-16
### Added
- **Sales**:
  - **Currency Persistence**:
    - **Session & Orders**: The selected "Invoice Currency" is now remembered across page reloads and correctly saved/restored when parking (pending) and retrieving orders.
    - **Database**: Updated `Order` model to support `invoice_currency_id`.
  - **Total Display**:
    - **Contextual Total**: The main "Total" amount now dynamically displays in the *selected currency* (e.g., Bolívares), matching the user's preference.
    - **Reference Values**: Added a summary section below the total showing equivalents in other currencies (USD, COP).
    - **USD/BCV**: Explicitly added "USD/BCV" reference calculation (Total VED / BCV Rate) in red for easier verification during payment.
  - **Visuals**:
    - **Product Grid/List**: When Bolívares is the active currency, prices in USD are now highlighted as "USD/BCV" in red to indicate they are calculated at the configured rate.

## [1.8.12] - 2026-02-14
### Changed
- **Invoices**:
  - **Visual Unification**: Aligned "Payment Conditions" and "Disclaimer" blocks in `invoice-credit-short`, `invoice-paid-short`, and `invoice-order-pending` templates to match the width and style of the "Amount in Words" block.
  - **Formatting**: Removed extra spacing and standardized borders for a cleaner, more professional look.


## [1.8.11] - 2026-02-12
### Added
- **Foreign Seller Payments**:
  - **Shared Cash Register**: Implemented logic to strictly exclude Foreign Sellers from the "Shared Cash Register" pool. Their sales now correctly remain "Pending" until approved.
  - **Payment Approval**: Updated `PartialPayment` to require an open cash register (Personal or Shared) when approving a payment, ensuring strict financial reconciliation.
  - **Date Refinement**:
    - **Commissions**: "On Time" calculation now uses the *actual transaction date* (Bank/Zelle date) provided by the seller, not the system approval date.
    - **Reporting**: Approved payments are now assigned to the *approver's* daily Collection Sheet, ensuring they appear in the day's Cash Report (Cierre de Caja).
  - **Reports**: Updated "Relación de Cobro General" to strictly filter out Pending/Rejected payments.

## [1.8.10] - 2026-02-12
### Added
- **Configurable Sales View**:
  - Implemented a toggle between **Grid View** (Large Images) and **List View** (Compact) for product search results.
  - Added global default configuration in *Settings > Sales*.
  - Added individual user override in *Users > Edit Profile*.
- **Freight Logic**:
  - Decoupled "Apply Freight" and "Breakdown Freight" toggles from Seller Commissions. Now they can be used even if the customer has no specific seller assigned (reads from Product settings).
  - Kept "Apply Commissions" locked to Seller Config for security.

## [1.8.9] - 2026-02-10
### Fixed
- **Freight Calculation**:
  - **Breakdown Logic**: Fixed incorrect compounding of freight when "Breakdown Freight" (Desglosar Flete) is enabled.
  - **Commission Calculation**: Resolved issues where commissions were being calculated on inflated prices.
  - **UX/UI**: Added security lock to Commission/Freight switches when no customer is selected to prevent user error.

## [1.8.8] - 2026-02-06
### Added
- **Global Exchange Rates System:**
    - **Configuration:** Added ability to set global reference rates for BCV and Binance in System Settings.
    - **History Tracking:** Implemented automated history logging for every rate change.
    - **Reactive Lookup (Smart Rates):**
        - **Cash Payments (VED):** Added "Payment Date" field. Selecting a date automatically fetches the historical rate valid for that specific day.
        - **Bank Payments (VED):** Bank payments in Bolívares now also support historical rate lookup based on the transaction date.
    - **Custom Rate Override:** Users can manually override the suggested historical rate if necessary.

## [1.8.7] - 2026-01-30
### Added
- **Sale Deletion Workflow:**
    - **Approval Process:** Implemented a secure Request-Approval workflow for deleting sales. Operators request deletion with a reason; Supervisors (Admin/Owner) approve or reject.
    - **Financial Integrity:** Pending deletions remain valid in financial reports until explicitly approved. Approval triggers strict cleanup of payments, Zelle/Bank records, and inventory.
    - **Notifications:** Automated email notifications to supervisors when a deletion is requested.
    - **UI:** Added visual indicators (yellow row, "Solicitud Borrado" badge) for sales pending deletion.
- **Login Page Customization:**
    - **Dynamic Branding:** Displays the configured "Business Name" and System Logo (Shopping Cart) on the login screen.
    - **Dynamic Version:** Shows the actual system version (from `version.txt`) instead of a hardcoded value.

## v1.8.6 - 2026-01-29
### Added
- **License Renewal System:**
    - Interactive modal in header to check expiration and renew license.
    - Permanent "License" option in the User Profile menu.
    - Automated email notifications for upcoming expiration (requires configuration in Settings).
    - Email request feature for license renewal directly from the application.
- `license:check-expiration` console command for automated monitoring.

## [1.8.5] - 2026-01-29

### Added
- **Reports**:
  - **Payment Relationship**: Added "Commissions to Pay" table in the Detailed View, mirroring the PDF layout for easier verification of commission amounts.

### Fixed
- **PDF Report**:
  - **Payment Details**: Resolved an issue where the "Payment Details" row appeared empty for Cash payments. It now conditionally renders only for Zelle, Bank Transfer, or Deposit payments.

## [1.8.4] - 2026-01-29

### Fixed
- **Reports**:
  - **Payment Relationship**: Resolved an issue where "Abonos" (Partial Payments) were not appearing in the Collection Sheet report.
- **Payments**:
  - **Collection Sheet**: Fixed logic in `PartialPayment` to automatically assign or create a daily Collection Sheet when registering a partial payment, ensuring proper tracking and reporting.

## [1.8.3] - 2026-01-29

### Fixed
- **Discounts**:
  - **Mutual Exclusivity**: Enforced strict exclusivity between "USD Discount" and "Early Payment Discount".
  - **Mixed Payments**: Fixed logic so that ANY payment in Bolívares (VED/VES) automatically invalidates the USD Discount and falls back to Early Payment rules.
  - **UI Persistence**: Fixed bugs where checkboxes would re-enable themselves automatically or disappear incorrectly.
  - **Visuals**:
    - **Inactive State**: Unchecked discounts now remain visible but appear **greyed out and strikethrough**, ensuring user awareness of eligibility.
    - **Styling**: Standardized the display of discount amounts (using red text) for consistent visual identity across both discount types.

## [1.8.2] - 2026-01-28

### Changed
- UI: Payment Modal now displays "GESTIÓN DE CRÉDITO" and "REGISTRAR CRÉDITO" dynamically when credit payment is selected.
- Logic: Reverted direct credit processing to use the confirmation modal workflow with improved UI context.

### Fixed
- Logs: Fixed UTF-16 LE encoding issue for Laravel logs on Windows environments.
- Credit: Fixed `validateCreditLimit` to correctly calculate current debt by including partial payments.
- System: Updated `reset_system.php` to truncate `credit_discount_rules` table.

## [1.8.1] - 2026-01-27

### Fixed
- **Sales**:
  - **Registration Hang**: Fixed a critical issue where the "Registrar Venta" process would hang indefinitely when validation failed (e.g., missing customer), due to debug output corrupting the Livewire response.
- **Payments**:
  - **Bank History**: Fixed incomplete data display for Bank/Transfer payments in the history modal. Implemented proper `BankRecord` creation and linking in both "Abonos" (Partial Payments) and "Accounts Receivable" modules.
  - **Double Counting**: Resolved a calculation error where new payments were being double-counted (Database + Memory) during the "Paid" status check, causing some invoices to be marked as paid prematurely.
- **Data Integrity**:
  - **Repairs**: Included scripts to retroactively fix missing database links for recent bank payments.
- **Maintenance**:
  - **Clean**: Removed temporary debug scripts and updated `.gitignore`.

## [1.7.3] - 2026-01-22

### Added
- **Production Module**:
  - **Edit Functionality**: Enabled editing for productions in "Pending" status. Users can now modify dates, notes, and product details before sending to inventory.
  - **UI/UX**: Added an "Edit" button (pencil icon) in the production list, strictly controlled by status logic.

## [1.7.2] - 2026-01-22

### Fixed
- **Invoices**:
  - **PDF Generation**: Fixed "Blank Page" issue for Pending Sales by restoring missing logic and robustifying error handling.
  - **Logo**: Added fallback logic to prevent PDF generation failure when the company logo file is missing or the path is invalid.
- **Settings**:
  - **System Logo**: Fixed broken logo display in General Settings by repairing the `public/storage` symlink.
  - **Backup Email**: Added validation to prevent saving invalid email addresses and corrected database typos.
- **Backup System**:
  - **Database Dump**: Configured `mysqldump` binary path explicitly to fix "mysqldump not recognized" error.
  - **Transaction Mode**: Enabled `useSingleTransaction` to ensure consistent backups without locking tables.

## [1.7.1] - 2026-01-22

### Fixed
- **Update System**: Increased download timeout limit to 5 minutes to prevent cURL error 28 on slow connections.

## [1.7.0] - 2026-01-22

### Added
- **Network Printer Authentication**:
  - **Global Settings**: Added fields in "Configuraciones > General" to define a default network printer with authentication (IP, Share Name, User, Password).
  - **User Profile**: Added overrides in "Usuarios > Editar" to assign specific network printers and credentials per user.
  - **Database**: Added `is_network`, `printer_user`, and `printer_password` columns to `configurations` and `users` tables.
  - **Printing Logic**: Updated system to prioritize printer configuration in the following order: Device > User > Global.
  - **SMB Protocol**: Implemented secure SMB connection URI construction (`smb://user:pass@host/share`) for printing to password-protected shared printers.

## [1.6.0] - 2026-01-18

### Added
- **Label Generator Module**:
  - **New Module**: Added a dedicated module for generating product labels (accessible via Sidebar > Etiquetas).
  - **Product Selection**: Search by Name, SKU, Category, or Tag.
  - **PDF Generation**: Generates a printable PDF with 28 labels per page (4 columns x 7 rows) on Letter size paper.
  - **Label Design**: Includes Product Name (Large), Operator, Date, and Barcode (Code 128).

## [1.5.4] - 2026-01-18

### Fixed
- **Access Control**:
  - **Permissions**: Fixed "Access Denied" error in "Assign Permissions" module for the Super Admin account when the "Admin" role is missing. Added explicit bypass for the owner's email.

## [1.5.3] - 2026-01-18

### Fixed
- **Installation**:
  - **Middleware**: Fixed a critical crash on fresh installations where `CheckDeviceAuthorization` and `CheckLicense` middleware would attempt to connect to the database before it was configured. Added checks to skip these middlewares if the application is not installed.

## [1.5.2] - 2026-01-18

### Fixed
- **Database**:
  - **Migrations**: Fixed "Column already exists" error by making the delivery fields migration idempotent. This ensures smooth updates even if previous migrations partially ran.

## [1.5.1] - 2026-01-18

### Fixed
- **System Update**:
  - **UI**: Fixed an issue where a dark overlay (backdrop) would block the screen after an update.
  - **Error Handling**: Added robust error handling for reading release notes.
- **Database**:
  - **Migrations**: Fixed execution order for delivery tracking migrations to prevent "Column not found" errors.
  - **Roles**: Ensure "Driver" role is correctly created by the seeder.
- **Access Control**:
  - **Super Admin**: Added failsafe mechanism to restore Admin access for the system owner.

## [1.5.0] - 2026-01-18

### Added
- **Delivery Tracking System**:
  - **Driver Dashboard**: New dedicated dashboard for drivers to view assigned orders, update status, and report collections.
  - **Live Tracking**: Real-time driver location tracking for administrators.
  - **Collection Reporting**: Drivers can now report payments (multi-currency) and notes directly from their dashboard.
  - **Admin Visibility**: Added "Reportes de Chofer / Cobranza" section to the Sale Detail modal in Admin Sales Report.
- **Mobile Experience**:
  - **Barcode Scanner**: Integrated camera-based barcode scanner for mobile POS.
  - **Optimizations**: Improved touch targets and layout for mobile devices.
- **Performance**:
  - **Database Indexes**: Added missing indexes to `sales`, `products`, and `customers` tables for faster queries.
  - **Query Optimization**: Fixed N+1 query issues in Sales and Reports.

## [1.4.11] - 2026-01-18

### Fixed
- **Reports**:
  - **Rotation Report**: Fixed "Malformed UTF-8 characters" error during PDF generation by implementing robust data sanitization and switching to `streamDownload`.
  - **Styling**: Applied professional design to the Rotation Report PDF, matching the "Accounts Receivable" report style (Logo, Header, Styled Table).
- **Security**:
  - **Device Authorization**: Enhanced middleware robustness with aggressive input sanitization and error handling to prevent crashes from malformed User Agent strings.

## [1.4.10] - 2026-01-17

### Fixed
- **POS**:
  - **Partial Payment Modal**: Fixed a bug where the "Abonos" modal would close automatically (leaving a gray backdrop) due to a component re-render issue caused by a dynamic key.

## [1.4.9] - 2026-01-17

### Fixed
- **System Update**:
  - **Progress Bar Visibility**: Changed the update progress bar color to yellow (`bg-warning`) with a white background track to ensure it is clearly visible against the blue alert background.

## [1.4.8] - 2026-01-17

### Fixed
- **UI**:
  - **Scrollbar**: Further improved scrollbar visibility with high-contrast colors (Dark Grey thumb on Light Grey track) and increased width for better accessibility.

## [1.4.6] - 2026-01-17

### Fixed
- **System Update**:
  - **Friendly Error Page**: Implemented a user-friendly "Update Required" page when database migrations are pending, replacing the raw Laravel error screen.
  - **Auto-Fix Button**: Added a "Run Update" button to the error page that automatically executes pending migrations.
- **UI**:
  - **Scrollbar**: Improved scrollbar visibility (darker contrast) in the POS sales view.

## [1.4.5] - 2026-01-17

### Added
- **Printing**:
  - **Device-Specific Printers**: Added ability to assign a specific printer and paper width to each device (PC/Mobile) via "Device Manager".
  - **Priority Logic**: Printing now prioritizes: Device Configuration > User Configuration > Global Configuration.
- **Device Manager**:
  - **Inline Editing**: Restored ability to edit device names directly in the list.
  - **Configuration Modal**: Added modal to configure printer name/path and width per device.
  - **Help Guide**: Added comprehensive guide for device and printer configuration.

## [1.4.4] - 2026-01-17

### Fixed
- **Update System**:
  - **Changelog Visibility**: Fixed an issue where `CHANGELOG.md` was excluded from release zips (via `.gitattributes`), causing clients to not see release notes after updating.

## [1.4.3] - 2026-01-16

### Changed
- **UI**:
  - **Footer**: Updated copyright year to 2026.

## [1.4.2] - 2026-01-16

### Fixed
- **Update System**:
  - **Cache Clearing**: Implemented automatic clearing of the "Update Available" cache key (`system_update_available`) after a successful update to ensure the header notification disappears immediately.

## [1.4.1] - 2026-01-16

### Fixed
- **Update System**:
  - **Version Persistence**: Fixed an issue where `version.txt` was not being updated after a system update.
  - **Update Logic**: Modified `UpdateService` to explicitly write the new version number to `version.txt` upon successful installation.

## [1.4.0] - 2026-01-14

### Added
- **Composite Products (Kits/Bundles)**:
  - **Modes**: Implemented "Pre-assembled" (Physical Stock) and "On-Demand" (Dynamic Stock) modes.
  - **Stock Management**:
    - **Pre-assembled**: Creating/Increasing stock deducts components. Selling deducts the kit. Purchasing increments the kit.
    - **On-Demand**: Selling deducts components directly. Purchasing increments components.
  - **UI**: Added "Pre-assembled" switch and "Additional Cost" field to Product Form.
- **Inventory Visibility**:
  - **Stock Distribution**: Added a table in Product Form (Inventory tab) showing stock quantity per warehouse.
- **Product Form Enhancements**:
  - **Persistent Edit**: Form now stays open after saving/updating to allow continuous editing.
  - **Navigation**: Renamed "Cancel" button to "Volver a Productos" for clarity.

### Changed
- **Sales**:
  - **Validation**: Updated stock validation to allow selling "On-Demand" products even if parent stock is 0 (checks components instead).
- **Purchases**:
  - **Stock Logic**: Updated purchase logic to handle both composite modes correctly.

## [1.3.3] - 2026-01-15

### Added
- **Reports**:
  - **Rotation Report**: Added a new report to analyze product rotation and movement.
- **Configuration**:
  - **Purchasing Settings**: Added configuration for purchasing calculation mode and coverage days.
- **Products**:
  - **Pre-assembled Products**: Added support for pre-assembled products and additional costs.

## [1.3.2] - 2026-01-14

### Changed
- **POS**:
  - **Compact Search Results**: Redesigned the product search dropdown to be more compact, showing more results (limit increased to 25).
  - **Stock Display**: Fixed discrepancy in "Total Stock" display by dynamically summing warehouse stocks.
  - **Revert**: Reverted "Product Presentations" and "Advanced Pricing" features to restore previous stability and functionality.

## [1.3.1] - 2026-01-12

### Added
- **Backup System**:
  - **Google Drive Integration**: Added support for automated backups to Google Drive.
  - **Windows Automation**: Included `backup.bat` script for Windows Task Scheduler integration.
  - **Email Attachments**: Configured system to send database backups via email (optional).
- **Auto-Updater**:
  - Implemented `UpdateService` to fetch releases from GitHub.
  - Added "Update System" UI in Settings to check for and apply updates.

## [1.3.0] - 2026-01-11

### Added
- **Licensing System**:
  - Implemented secure offline licensing using RSA cryptography.
  - Added "System Locked" mode for expired licenses.
  - Added "License Generator" tool for administrators.
- **Installation System**:
  - Created a web-based Installation Wizard (Steps: Requirements, Database, Migrations, License, Admin).
  - Added `InstallController` and routes to handle the setup process.
  - Added `CheckInstalled` middleware to redirect to installer if not configured.
- **Role Management**:
  - Implemented **Level-based Hierarchy** (Admin=100, Dueño=50, etc.).
  - Users can only assign roles with a lower level than their own.
  - Added `level` column to `roles` table.
  - Protected Super Admin account from modification.
- **Desktop Integration**:
  - Added "Create Shortcut" feature to the installer.
  - Generates a `.bat` script that creates a Chrome App Mode shortcut (`--app`) and auto-launches the system.
- **Data Initialization**:
  - Added `WarehouseSeeder` to create a default "Tienda Principal" warehouse.
  - Updated `ConfigurationSeeder` to set the default warehouse automatically.

## [1.2.9] - 2026-01-11

### Added
- **Sales**:
  - **Zelle Integration**: Fully integrated Zelle payments into the Sales module.
    - Added `zelle_records` and `sale_payment_details` tables.
    - Implemented real-time validation for Zelle payments.
    - Made Zelle image upload mandatory for verification.
    - Added "Ver Comprobante" link in Sale Details modal.
- **Printing**:
  - **Dynamic Ticket Format**: Implemented intelligent detection for **58mm** and **80mm** printers.
    - Tickets automatically adjust width and separators based on configuration.
    - Centered business header with optimized font size.
    - Added "Condición de Venta" (Crédito/Contado) to the ticket header.
    - Validated compatibility across all ticket types (Sales, Orders, Payments, Cash Count).

## [1.2.8] - 2026-01-10

### Added
- **Warehouse Management**:
  - **System Default Warehouse**: Added configuration to set a system-wide default warehouse for users without a specific assignment.
  - **Permissions**: Implemented granular permissions for warehouse management:
    - `warehouses.create`, `warehouses.edit`, `warehouses.delete` (Internal).
    - `sales.switch_warehouse`, `sales.mix_warehouses` (Internal).
  - **Permission Assignment UI**: Redesigned the permission assignment view with a professional Bootstrap grid layout and Spanish translations (e.g., "Ventas: Cambiar Depósito").

### Changed
- **Sales**:
  - **Warehouse Selection**: Automatically selects the system default warehouse if the user has no principal warehouse assigned.
  - **Permission Enforcement**: Restricted warehouse switching and mixing based on user permissions.

## [1.2.8] - 2026-01-10

### Added
- **Reports**:
  - **Best Sellers Report**: Added a new report module to view top-selling products with filters for date range, category, and status. Includes Bar and Pie charts.
- **Dashboard**:
  - **Top Sellers Chart**: Added a new chart to visualize the top 5 sellers by profit for the current month.
  - **Chart Type Toggle**: Added functionality to switch the "Top Sellers" chart between Column, Bar, Pie, and Donut views dynamically.
  - **Role Filtering**: Configured "Top Sellers" chart to only display users with the "Vendedor" role, correctly attributing sales to the account manager (Customer's Seller).

### Changed
- **Dashboard**:
  - **Charting Library**: Migrated all dashboard charts from Chart.js to **Highcharts** for better performance and consistency.
  - **Optimizations**: Optimized database queries for "Top Products" and "Low Stock" widgets to improve dashboard load time.
  - **Image Handling**: Improved product image loading logic to prevent broken images.

## [1.2.4] - 2026-01-09

### Added
- **Profile**:
  - **Browser Sessions**: Added functionality to view and manage active browser sessions (Desktop/Mobile, IP, Last Activity).
  - **Logout Other Devices**: Added ability to log out from all other devices securely.
  - **AdminLTE Integration**: Redesigned the entire Profile page to match the system's AdminLTE theme.
    - Used Bootstrap Grid and Cards.
    - Replaced Tailwind CSS forms with Bootstrap forms.
    - Replaced Alpine.js modals with Bootstrap modals.

### Fixed
- **UI/UX**:
  - **Sidebar Logo**: Fixed the sidebar to dynamically display the company logo and name from settings.
  - **Profile Page**: Fixed broken layout and navigation links on the profile page by switching to the correct AdminLTE layout component.
  - **Vite Manifest**: Resolved `ViteManifestNotFoundException` by regenerating build assets.

## [1.2.3] - 2026-01-09

### Added
- **Settings**:
  - Added "Company Logo" upload functionality in General Settings.
  - Added `logo` field to `configurations` table.

### Changed
- **PDF Reports & Invoices**:
  - **Standardized Header Design**: Applied a consistent, professional header design across ALL system PDFs (Invoices, Orders, Reports).
    - Layout: Logo (Left), Company Name (Center), Document Title/Number (Right).
    - Added rounded "Info Box" for client/report details.
    - Updated color scheme to use consistent Blue (`#0380b2`) for titles and backgrounds.
  - **Updated Templates**:
    - `invoice-paid` (Sales Invoice)
    - `invoice-order-processed` (Processed Order)
    - `invoice-order-pending` (Pending Order)
    - `accounts-receivable-pdf` (Cuentas por Cobrar)
    - `payment-relationship-pdf` (Relación de Pagos)
    - `daily-sales-report-pdf` (Ventas Diarias)
    - `payment-history-pdf` (Historial de Pagos)
    - `collection-sheets-list-pdf` (Planillas General)
    - `collection-sheet-detail-pdf` (Planilla Básica)
    - `collection-sheet-detail-full-pdf` (Planilla Detallada)

## [1.2.2] - 2026-01-09

### Fixed
- **Purchases**:
  - Fixed layout issue where the "Resumen" card was not properly aligned in the grid (wrapped in `col-md-3`).

## [1.2.0] - 2026-01-09

### Added
- **Dashboard**:
  - Implemented a comprehensive Dashboard at `/welcome`.
  - Added KPI Cards for Sales, Purchases, and Receivables.
  - Added "Recent Sales" table and "Top Products" list.
  - Added "Low Stock Alerts" widget.
  - Added "Pending Commissions" widget (moved to top row).
  - Added "Sales vs Profit" Chart (Last 7 Days).
  - Added "Top Suppliers" widget.
- **UI Enhancements**:
  - Added scrollbar (`max-height: 300px`) to all header notification dropdowns.

### Fixed
- **Dashboard**:
  - Resolved `MultipleRootElementsDetectedException` in Livewire component.
  - Fixed Commission Widget value to match Header Notification logic (Paid sales, Foreign sales, Permissions).
  - Fixed Low Stock Alert contrast issue.
- **Navigation**:
  - Added "DASHBOARD" link to the sidebar.

## [1.1.0] - 2026-01-08

### Added
- **Collection Sheet Reports**:
  - Implemented `CollectionSheet` model and migration.
  - Added "Relación de Cobro" (Payment Relationship) reports with detailed and basic views.
  - Added PDF export functionality for Collection Sheets (Basic, Detailed, and General).
  - Added "Hojas de Cobranza" listing and management.
  - **Enhanced PDF Summaries**: Added a detailed summary table to all PDF reports showing "Original Amount" (per currency) and "USD Equivalent".
  - **PDF Styling**: Aligned "Detailed" PDF style with "Basic" PDF, including payment details row.

### Changed
- **Payment Relationship**:
  - Enhanced `PaymentRelationshipReport` to include dynamic filtering and better data presentation.
  - Refined PDF layouts: Moved summary table to the top of the report (below filters) for better visibility.
  - Updated `Payment` model to support new reporting relationships.

## [1.0.0] - 2026-01-08

### Added
- **Zelle Payment Integration**:
  - Added `zelle_records` table to store Zelle transaction details.
  - Added `zelle_record_id` to `payments` table for direct linking.
  - Integrated Zelle into the "Bank" payment method in `PaymentComponent`.
  - Real-time validation for Zelle payments (duplicate detection, balance tracking).
  - Automatic status updates for Zelle records ('partial', 'used').
  - Display of Zelle details (Sender, Date) in payment history.
  - Support for Zelle payments in `AccountsReceivableReport`.

### Changed
- Updated `pay_way` ENUM in `payments` table to include 'zelle'.
- Modified `historypays.blade.php` to show Zelle specific information.

### Fixed
- Fixed issue where Zelle records were not being created when paying via Accounts Receivable Report.
