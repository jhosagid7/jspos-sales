# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).





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
