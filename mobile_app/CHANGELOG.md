# Changelog - JSPOS Mobile
All notable changes to the mobile application will be documented in this file.

## [1.1.15] - 2026-04-14
### Fixed
- **Sincronización de Deuda**: Unificada la lógica de cálculo entre el listado general y el detalle de factura para evitar discrepancias en clientes con transacciones multimoneda.

## [1.1.14] - 2026-04-14
### Fixed
- **Vencimientos**: Corregido error que mostraba vencimiento 'null' y ajustada la polaridad de los d\u00edas (+ para mora, - para d\u00edas restantes) para coincidir con est\u00e1ndares del sistema.

## [1.1.13] - 2026-04-14
### Added
- **Trazabilidad de Vencimientos**: Las facturas pendientes ahora muestran fecha de emisión, fecha de vencimiento y un semáforo visual de días de mora o días restantes.

## [1.1.12] - 2026-04-14
### Improved
- **Filtrado Remoto**: Las pestañas "CON DEUDA" y "VENCIDOS" ahora solicitan datos filtrados directamente al servidor, garantizando visibilidad total de la cartera global para administradores.

## [1.1.11] - 2026-04-14
### Added
- **Financial Audit Trail**: Added mandatory `issuer_name` and voucher date for Zelle and Bank payments.
- **Improved UI**: Refactored payment history for better visibility using `ExpansionTile` with detailed financial metadata (Rate, Issuer, Bank).
- **Multi-Currency Support**: Added support for VED/USD/COP currencies in the payment form with dynamic rate calculation.
- **Security**: Hardened payment upload validation to ensure voucher consistency.
- **Deployment**: Automatic release generation and optimized build (17-19 MB).

## [1.1.10] - 2026-04-13
### Added
- **Financial Sync**: Initial implementation of debt synchronization between Web and Mobile.
- **Payment History**: List of recent payments made by the seller.
