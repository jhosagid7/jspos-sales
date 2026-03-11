# Changelog

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

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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
