# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-01-08

### Added
- **Collection Sheet Reports**:
  - Implemented `CollectionSheet` model and migration.
  - Added "Relación de Cobro" (Payment Relationship) reports with detailed and basic views.
  - Added PDF export functionality for Collection Sheets (Basic and Detailed).
  - Added "Hojas de Cobranza" listing and management.

### Changed
- **Payment Relationship**:
  - Enhanced `PaymentRelationshipReport` to include dynamic filtering and better data presentation.
  - Refined PDF layouts for better readability and data accuracy.
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
