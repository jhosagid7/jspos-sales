---
name: pos-multicurrency-rules
description: >
  Domain business logic and calculation rules for JSPOS Sales Point-of-Sale.
  Enforces multi-currency (USD/VES), BCV exchange rate synchronization,
  mixed payment methods, credit authorization limits, and cash drawer reconciliation.
  Trigger: When modifying POS checkout, currency conversions, payments, credit sales, or cashier shifts.
license: Apache-2.0
metadata:
  author: jspos-sales-core
  version: "1.0"
---

## When to Use

- When touching checkout, sales settlement, or mixed currency calculations (USD cash, VES Pago Móvil, Point of Sale debit, Zelle).
- When modifying customer credit limits, approval gates, or accounts receivable (cobranzas).
- When calculating exchange differentials, cashier drawer openings, closures, and shift audits.

---

## 1. Multi-Currency (USD / VES) Core Business Rules

1. **Base Currency & Exchange Rate**:
   - Primary reference price is stored in USD (`precio_usd` / `total_usd`).
   - Conversion to VES (`total_ves`) is calculated using the active BCV (Banco Central de Venezuela) exchange rate (`tasa_cambio`).
   - Formula: `Total_VES = Total_USD * Tasa_BCV`.
2. **Rounding Rules**:
   - Currency in USD: Round to 2 decimal places (`round($amount, 2)`).
   - Currency in VES: Round to 2 decimal places (`round($amount, 2)`).
3. **Mixed Payments (Pagos Mixtos)**:
   - A single invoice can be settled with multiple payment methods (e.g., $10 USD cash + 350.00 VES Pago Móvil + $5.00 Credit).
   - The sum of all payments converted to USD MUST equal or exceed the invoice total (`total_usd`).
   - If payments exceed the total, change/vuelto is calculated in the chosen return currency (USD or VES).

---

## 2. Credit Sales & Authorization Workflow

1. **Credit Limits**:
   - If `monto_credito > cliente.limite_credito` or `cliente.saldo_pendiente > 0`, the POS triggers an approval prompt.
2. **Approval Flow**:
   - Cashiers (`cajero`) cannot approve credit overrides or discounts exceeding authorized thresholds.
   - Administrators or authorized Supervisors can approve via PIN / password or approval token.
3. **Audit Trail**:
   - Every credit invoice records: `user_id` (cashier), `authorized_by` (supervisor/admin ID), `fecha_vencimiento`, and `saldo_pendiente`.

---

## 3. Shift Management (Apertura y Cierre de Caja)

1. **Cash Drawer Reconciliation (Arqueo)**:
   - Cashiers start shifts with initial float (`monto_inicial_usd`, `monto_inicial_ves`).
   - Cash drawer closing computes expected totals:
     `Esperado = Monto Inicial + Ventas Efectivo + Ingresos Manuales - Egresos/Retiros`.
   - Any difference (`diferencia = Real - Esperado`) is logged as surplus (`sobrante`) or deficit (`faltante`).
