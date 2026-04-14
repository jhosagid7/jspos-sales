# Changelog - JSPOS Mobile
All notable changes to the mobile application will be documented in this file.

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
