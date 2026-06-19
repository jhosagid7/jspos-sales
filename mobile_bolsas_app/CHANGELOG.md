# Changelog - JSPOS Mobile Bolsas
All notable changes to the Bolsas Manufacturing application will be documented in this file.

## [1.0.1] - 2026-06-19
### Added
- **Fecha de Elaboración por Producto**: Agregada propiedad `productionDate` en `ProductionEntry` y selector de fecha individual en el diálogo de añadir bolsa. 
- **Insignias Informativas**: Rediseño de tarjetas de ítems cargados usando doble fila de badges (Cantidad, Peso, Operario, Fecha).
- **Herencia Automática de Fecha**: Los ítems agregados toman por defecto la fecha de producción global seleccionada al principio, previniendo errores de validación.
- **Historial Agrupado**: El listado del historial móvil ahora agrupa visualmente los detalles por fecha real de elaboración con formato en español.

## [1.0.0] - 2026-06-16
### Added
- **Lanzamiento Inicial de App Bolsas**: Flujo directo de levantamiento diario con selector de fecha, lectura QR, carga multi-operador e ingresos de bobinas variables.
