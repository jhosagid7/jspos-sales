# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
