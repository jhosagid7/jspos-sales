# REPORTE TÉCNICO Y ARQUITECTURA DEL SISTEMA — JSBOLSAS PRO

**Proyecto:** Sistema Integral de Producción, Costeo, Auditoría en Báscula y Control de Fábrica de Bolsas y Bobinas  
**Cliente / Empresa:** Plásticos M&F, C.A.  
**Fecha de Emisión:** Septiembre 2026  
**Ambiente de Producción:** [https://bolsas.plasticosmyf.com](https://bolsas.plasticosmyf.com)  

---

## 1. STACK TECNOLÓGICO Y ARQUITECTURA GENERAL

El sistema está diseñado bajo una arquitectura cliente-servidor desacoplada que combina un panel administrativo web con una flota de terminales móviles Android de captura en planta bajo el paradigma **Offline-First**.

```
┌─────────────────────────────────────────────────────────────┐
│                      FLOTA MÓVIL (APK)                      │
│   Flutter 3.x / Dart 3.x • SQLite Local (Offline-First)     │
│       Escaneo QR Nativo (Mobile Scanner) • Build 230        │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTPS / JSON
                               │ Bearer Token (Sanctum)
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 NÚCLEO BACKEND / API REST                   │
│         PHP 8.2 • Laravel 10.x • Laravel Sanctum            │
│  Eloquent ORM (Single Source of Truth de Lógica Financiera) │
└──────────────┬──────────────────────────────┬───────────────┘
               │                              │
               ▼                              ▼
┌──────────────────────────────┐┌─────────────────────────────┐
│    BASE DE DATOS (MYSQL)     ││    PANEL WEB ADMINISTRATIVO │
│          MySQL 8.0           ││ Laravel Blade + Bootstrap 5 │
│ InnoDB / Relaciones Foráneas ││ Dark Slate Industrial UI    │
└──────────────────────────────┘└─────────────────────────────┘
```

### Detalle del Stack:
* **Backend (Servidor & API REST):** PHP 8.2 sobre **Laravel 10.x**. Autenticación vía **Laravel Sanctum** con tokens Bearer para dispositivos móviles y sesiones HTTP seguras para administradores web.
* **Frontend Web (Panel Administrativo):** Laravel Blade Templates, **Bootstrap 5.3** (*Dark Slate Industrial UI*), iconografía con Bootstrap Icons y Lucide Icons, Popovers y Tooltips nativos de Bootstrap interactivos (hover en escritorio y tap en móviles).
* **Aplicación Móvil (APK Android):** Desarrollada en **Flutter 3.x / Dart 3.x**. Utiliza **SQLite (`sqflite 2.4.1`)** localmente para operar 100% offline dentro del galpón de producción. Cuenta con sincronización idempotente vía UUID y lectura de códigos QR con `mobile_scanner 6.0.4`. Versión actual de compilación: `v2.3.0` (Build 230).
* **Infraestructura y Alojamiento:**
  * Servidor Cloud VPS Ubuntu 22.04 LTS (IP: `209.126.81.6`).
  * Servidor Web Nginx como Reverse Proxy hacia PHP-FPM 8.2.
  * Base de datos relacional MySQL 8.0 (`jsbolsas_db`).
  * Certificados de seguridad SSL/TLS emitidos por Let's Encrypt con renovación automática.

---

## 2. ESQUEMA DE BASE DE DATOS ACTUAL

A continuación se presenta el diccionario de datos de las tablas especializadas del módulo de producción y costeo:

### A. `bag_products` (Catálogo de Productos y Fichas Técnicas)
Representa las especificaciones técnicas, dimensiones, precios y metas de cada tipo de bolsa o bobina fabricada.

| Campo | Tipo de Dato | Clave / Restricción | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK / Auto-inc | Identificador único del producto. |
| `name` | VARCHAR(191) | NOT NULL | Nombre comercial (ej. `BOLSA 40X60`, `BOBINA ZANAHORIA 1KG`). |
| `category` | VARCHAR(100) | NOT NULL | Categoría funcional (`Bolsas`, `Bobinas`, etc.). |
| `production_formula_id` | BIGINT UNSIGNED | FK (`production_formulas.id`) | Enlace opcional a la receta de extrusión. NULL = Resina Genérica. |
| `sale_unit` | VARCHAR(50) | NOT NULL | Unidad de comercialización (`BULTO`, `MILLAR`, `BOBINA`, `KG`). |
| `sku` | VARCHAR(100) | UNIQUE | Código único de referencia interna. |
| `millar_per_bulto` | DECIMAL(10,4) | Default: 0 | Cantidad de millares contenidos por bulto. |
| `width_inch` | DECIMAL(10,2) | Default: 0 | Ancho en pulgadas. |
| `length_inch` | DECIMAL(10,2) | Default: 0 | Largo en pulgadas. |
| `gauge_caliber` | DECIMAL(10,4) | Default: 0 | Calibre o espesor del plástico. |
| `unit_weight_kg` | DECIMAL(10,4) | Default: 0 | Peso unitario calculado ($A \times L \times C$). |
| `real_total_weight_kg` | DECIMAL(10,4) | Default: 0 | Peso real por unidad de venta (permite override manual). |
| `margin_percentage` | DECIMAL(8,2) | Default: 45.00 | Margen de utilidad bruta deseado (%). |
| `cost` | DECIMAL(10,4) | Default: 0 | Costo de materia prima por unidad de venta. |
| `price` | DECIMAL(10,4) | Default: 0 | Precio base de salida de fábrica. |
| `price_tier_1` | DECIMAL(10,4) | Nullable | Precio sugerido Nivel 1 (+10% sobre fábrica). |
| `price_tier_2` | DECIMAL(10,4) | Nullable | Precio sugerido Nivel 2 (+17% sobre fábrica). |
| `price_tier_3` | DECIMAL(10,4) | Nullable | Precio sugerido Nivel 3 (+21% sobre fábrica). |
| `target_units_per_shift` | INT | Default: 0 | Meta de producción exigida por turno de trabajo. |
| `target_daily_profit` | DECIMAL(10,4) | Default: 0 | Meta económica de ganancia diaria asociada. |
| `is_variable_quantity` | BOOLEAN | Default: 0 | `1` para Bobinas (peso variable en Kg), `0` para Bultos. |
| `is_active` | BOOLEAN | Default: 1 | Estado operativo del producto en catálogo. |

---

### B. `raw_materials` (Catálogo de Materias Primas)
Almacena los 17 insumos base (polímeros vírgenes, recuperados, pigmentos y aditivos).

| Campo | Tipo de Dato | Clave | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK / Auto-inc | Identificador del insumo. |
| `code` | VARCHAR(100) | UNIQUE | Código de inventario (ej. `MP-POL-REC-NEG-01`). |
| `name` | VARCHAR(191) | NOT NULL | Nombre descriptivo del material. |
| `category` | VARCHAR(100) | NOT NULL | Clasificación del polímero o aditivo. |
| `unit` | VARCHAR(20) | Default: 'KG' | Unidad de compra/consumo. |
| `base_cost` | DECIMAL(10,4) | NOT NULL | Costo base en proveedor ($/KG). |
| `logistics_cost_percent` | DECIMAL(5,2) | Default: 10.00 | Recargo por fletes y nacionalización (%). |
| `current_cost` | DECIMAL(10,4) | NOT NULL | Costo real puesto en planta ($/KG). |
| `is_active` | BOOLEAN | Default: 1 | Estado activo. |

---

### C. `production_formulas`, `formula_versions` y `formula_version_items`
Estructura jerárquica para la formulación de mezclas de extrusión.

* `production_formulas`: `id` (PK), `name`, `code`, `description`, `is_active`.
* `formula_versions`: `id` (PK), `production_formula_id` (FK), `version_number` (INT), `is_active` (BOOLEAN), `total_percentage` (DECIMAL 5,2), `weighted_cost_per_kg` (DECIMAL 10,4), `notes` (TEXT).
* `formula_version_items`: `id` (PK), `formula_version_id` (FK), `raw_material_id` (FK), `percentage` (DECIMAL 5,2), `unit_cost_snapshot` (DECIMAL 10,4), `weighted_cost` (DECIMAL 10,4).

---

### D. `bag_shifts` (Jornadas y Turnos Operativos)
Controla el inicio, cierre y balance financiero de cada turno de máquina.

| Campo | Tipo de Dato | Clave | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK / Auto-inc | Identificador del turno. |
| `shift_code` | VARCHAR(100) | UNIQUE | Código legible (ej. `TURNO-20260903-12`). |
| `user_id` | BIGINT UNSIGNED | FK (`users.id`) | Operario titular del turno. |
| `start_time` | DATETIME | NOT NULL | Fecha y hora de apertura. |
| `end_time` | DATETIME | Nullable | Fecha y hora de cierre. |
| `status` | ENUM('open','closed') | NOT NULL | Estado actual de la jornada. |
| `total_packages` | DECIMAL(10,2) | Default: 0 | Total de bultos o rollos fabricados. |
| `total_weight_kg` | DECIMAL(10,2) | Default: 0 | Total de kilogramos pesados. |
| `total_income` | DECIMAL(10,4) | Default: 0 | Valor monetario de la producción del turno ($). |
| `total_raw_material_cost` | DECIMAL(10,4) | Default: 0 | Costo del plástico consumido ($). |
| `fixed_operational_cost` | DECIMAL(10,4) | Default: 0 | Costo fijo asignado al turno (sueldo + energía). |
| `total_production_cost` | DECIMAL(10,4) | Default: 0 | Suma de materia prima + costo fijo. |
| `net_profit` | DECIMAL(10,4) | Default: 0 | Ganancia neta real generada por el turno ($). |

---

### E. `bag_productions` (Pesajes Físicos y Control de Calidad)
Registra cada bulto o evento de pesaje de bobinas.

| Campo | Tipo de Dato | Clave | Descripción |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK / Auto-inc | Identificador del pesaje. |
| `bag_shift_id` | BIGINT UNSIGNED | FK (`bag_shifts.id`) | Turno al que pertenece la producción. |
| `user_id` | BIGINT UNSIGNED | FK (`users.id`) | Operario fabricante. |
| `product_id` | BIGINT UNSIGNED | FK (`bag_products.id`)| Producto elaborado. |
| `quantity` | DECIMAL(10,2) | NOT NULL | Cantidad de bultos o unidades de bobina. |
| `weight` | DECIMAL(10,2) | NOT NULL | Peso total acumulado en báscula (Kg). |
| `qr_code` | VARCHAR(100) | UNIQUE, Nullable | Código QR único generado (`PKG-XXXXXX`). |
| `status` | ENUM | NOT NULL | `pending_review`, `approved`, `rejected`, `lifted`. |
| `reviewer_id` | BIGINT UNSIGNED | FK (`users.id`), Null | Supervisor o auditor de báscula. |
| `reviewed_at` | TIMESTAMP | Nullable | Fecha y hora de auditoría. |
| `rejection_reason` | TEXT | Nullable | Motivo en caso de rechazo por calidad. |
| `lifter_id` | BIGINT UNSIGNED | FK (`users.id`), Null | Personal de almacén que ejecutó el levantamiento. |
| `lifted_at` | TIMESTAMP | Nullable | Fecha y hora de ingreso a inventario general. |
| `sync_uuid` | VARCHAR(100) | UNIQUE, Nullable | UUID emitido por la app móvil (garantía anti-duplicados). |
| `metadata` | JSON | Nullable | **Desglose individual de bobinas** (array con IDs y pesos de cada rollo). |
| `recorded_at` | TIMESTAMP | NOT NULL | Momento exacto del pesaje en planta. |

---

### F. `bag_cost_settings` (Parámetros Financieros Globales)
* `resin_cost_per_kg`: Costo fallback de resina virgen genérica ($/KG, ej. `$1.4000`).
* `shift_fixed_cost`: Costo fijo operativo por turno (luz + nómina, ej. `$25.00`).
* `daily_profit_target`: Meta económica de ganancia diaria de la fábrica (ej. `$105.00 USD/Día`).
* `default_margin_percentage`: Margen porcentual por defecto (ej. `45.00%`).

---

## 3. APIS Y SINCRONIZACIÓN (CONEXIÓN WEB ⇄ APK)

Todos los endpoints móviles responden en formato JSON bajo el prefijo `/api/bag-factory/` y requieren autenticación mediante cabecera HTTP:  
`Authorization: Bearer <SANCTUM_TOKEN>`.

### Catálogo Completo de Endpoints:

```
[Móvil] POST /api/login                    --> Autenticación y obtención de Token
[Móvil] GET  /api/bag-factory/products     --> Descarga catálogo, fórmulas y metas
[Móvil] POST /api/bag-factory/shifts/open  --> Apertura de turno de operario
[Móvil] POST /api/bag-factory/shifts/close --> Cierre de turno
[Móvil] GET  /api/bag-factory/shifts/active--> Consulta de turno actual
[Móvil] POST /api/bag-factory/productions/sync --> Sincronización encolada de pesajes
[Báscula] GET  /api/bag-factory/supervisor/feed       --> Cola en vivo de pendientes
[Báscula] PUT  /api/bag-factory/supervisor/productions/{id} --> Ajuste/edición de pesos
[Báscula] POST /api/bag-factory/supervisor/productions/{id}/approve --> Aprobación y QR
[Báscula] POST /api/bag-factory/supervisor/productions/{id}/reject  --> Rechazo por calidad
[Almacén] GET  /api/bag-factory/lifting/pending --> Lista de stock listo para levantar
[Almacén] POST /api/bag-factory/lifting/receive --> Confirmación de levantamiento
```

### Flujo de Sincronización Detallado:

1. **Captura Desconectada en SQLite:**  
   Cuando un operario pesa bobinas en planta, la app móvil inserta el registro localmente con su desglose de rollos en JSON y le asigna un `sync_uuid` (UUID v4), marcándolo como pendiente de sincronización (`is_synced = 0`).

2. **Carga en Lote hacia el Servidor:**  
   El `SyncService` de Flutter detecta red y envía la petición a `POST /api/bag-factory/productions/sync`:
   ```json
   {
     "productions": [
       {
         "sync_uuid": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
         "bag_shift_id": 18,
         "product_id": 31,
         "quantity": 2,
         "weight": 51.00,
         "recorded_at": "2026-09-03 15:20:00",
         "metadata": {
           "individual_coils": [
             {"coil_id": "BOBINA-01", "weight": 30.50, "recorded_at": "2026-09-03 15:18:12"},
             {"coil_id": "BOBINA-02", "weight": 20.50, "recorded_at": "2026-09-03 15:19:40"}
           ]
         }
       }
     ]
   }
   ```

3. **Validación e Idempotencia en Laravel:**  
   El backend verifica si el `sync_uuid` ya existe. Si ya fue procesado, actualiza la información sin duplicar registros. Si es nuevo, inserta en `bag_productions` y ejecuta el método `$shift->recalculateFinancials()`.

4. **Confirmación y Limpieza Local:**  
   El servidor retorna HTTP 200 con la lista de UUIDs procesados satisfactoriamente, y Flutter marca los registros locales como `is_synced = 1`.

---

## 4. LÓGICA DE CÁLCULO IMPLEMENTADA

La arquitectura garantiza que la **lógica matemática y financiera se procese íntegramente en el Backend (Modelos Eloquent)** como *Single Source of Truth*. Las interfaces consumen datos ya calculados o ejecutan simuladores de interfaz puramente visuales.

### Fórmulas Matemáticas Centrales:

1. **Peso Físico Unitario:**
   $$\text{PESO} = \text{Ancho (pulg)} \times \text{Largo (pulg)} \times \text{Calibre}$$
   *(Para Bobinas de peso libre se fija en $1.0000\text{ Kg}$).*

2. **Peso Real por Unidad de Venta ($\text{PESO\_R}$):**
   * Si Unidad = `MILLAR`: $\text{PESO\_R} = \text{PESO}$
   * Si Unidad = `BULTO`: $\text{PESO\_R} = \text{PESO} \times \text{Millar/Bulto}$
   * Si Unidad = `BOBINA` o `KG`: $\text{PESO\_R} = \text{unit\_weight\_kg}$ *(o $1.0000$)*
   * *Permite sobreescritura manual en ficha técnica si el peso físico difiere del nominal.*

3. **Costo de Materia Prima ($\text{COSTO}$):**
   * **Con Fórmula Asignada:**  
     $$\text{COSTO} = \text{PESO\_R} \times \text{Costo Ponderado de la Fórmula (\$/KG)}$$
   * **Con Resina Genérica (Fallback):**  
     $$\text{COSTO} = \text{PESO\_R} \times \text{Costo Global de Resina (\$/KG)}$$

4. **Precio Base de Fábrica ($\text{FABRICA}$):**
   $$\text{PRECIO\_FABRICA} = \text{COSTO} \times \left(1 + \frac{\text{Margen \%}}{100}\right)$$

5. **Simulador Inverso (Precio basado en Meta Diaria):**
   $$\text{Precio Sugerido} = \text{Costo Unitario} + \left(\frac{\text{Meta Diaria (\$/Día)}}{\text{Cuota del Turno} \times \text{Turnos por Día}}\right)$$

6. **Tiers de Precios Escalonados:**
   * **Tier 1 (+10%):** $\text{FABRICA} \times 1.10$
   * **Tier 2 (+17%):** $\text{FABRICA} \times 1.17$
   * **Tier 3 (+21%):** $\text{FABRICA} \times 1.21$

7. **Balance Financiero del Turno y Períodos:**
   * **Ingreso Bruto:** $\sum (\text{Bultos o Kilos} \times \text{Precio de Fábrica})$
   * **Costo Total:** $\text{Costo de Plástico} + \text{Costo Fijo Operativo por Turno}$
   * **Utilidad Neta Real:** $\text{Ingreso Bruto} - \text{Costo Total}$
   * **Margen Real \%:** $(\text{Utilidad Neta} / \text{Ingreso Bruto}) \times 100$

---

## 5. ESTRUCTURA DE ARCHIVOS Y ESTADO DEL PROYECTO

### Estructura de Directorios Clave:

```
jspos-sales/
├── app/
│   ├── Http/Controllers/
│   │   ├── BagFactoryWebController.php      <-- Controlador Web (Dashboard, Báscula, Costos, Reportes)
│   │   └── Api/
│   │       └── BagFactoryApiController.php  <-- Controlador API REST para la APK Móvil
│   └── Models/
│       ├── BagProduct.php                   <-- Modelo de Fichas Técnicas, Pesos y Precios
│       ├── BagProduction.php                <-- Modelo de Pesajes, Desglose de Bobinas y QR
│       ├── BagShift.php                     <-- Modelo de Turnos y Recálculo Financiero
│       ├── BagCostSetting.php               <-- Configuración Global de Costos
│       ├── ProductionFormula.php            <-- Fórmulas Maestras de Extrusión
│       ├── FormulaVersion.php               <-- Versiones y Recetas Activas
│       ├── FormulaVersionItem.php           <-- Insumos y Ponderación de Costos
│       └── RawMaterial.php                  <-- 17 Materias Primas con Logística
├── resources/views/
│   ├── dashboard.blade.php                  <-- Monitor en vivo con filtro de periodo (Hoy/Semana/Mes)
│   ├── costs/index.blade.php                <-- Fichas técnicas y simulador inverso
│   ├── formulas/index.blade.php             <-- Recetas y matriz de ponderación
│   ├── raw_materials/index.blade.php        <-- Gestión de materias primas
│   ├── scale/index.blade.php                <-- Báscula de auditoría y desglose de bobinas
│   ├── reports/
│   │   ├── index.blade.php                  <-- Reporte histórico con balance financiero
│   │   └── pdf.blade.php                    <-- Plantilla de impresión formal PDF
│   ├── ticket.blade.php                     <-- Ticket térmico con QR para bulto/bobina
│   └── layouts/app.blade.php                <-- Layout base con popovers oscuros globales
├── mobile_bolsas_app/                       <-- Código fuente de la APK Flutter
│   ├── lib/
│   │   ├── screens/
│   │   │   ├── operator_dashboard.dart      <-- Registro de pesajes y bobinas
│   │   │   ├── supervisor_dashboard.dart    <-- Báscula y auditoría móvil
│   │   │   ├── warehouse_lifting_screen.dart<-- Escaneo y recepción en almacén
│   │   │   └── camera_scanner_screen.dart   <-- Lector QR nativo
│   │   └── services/
│   │       ├── local_db.dart                <-- SQLite local Offline-First
│   │       └── sync_service.dart            <-- Sincronización HTTP con servidor
│   └── pubspec.yaml                         <-- Dependencias y versionado (v2.3.0)
└── tests/Feature/
    ├── BobinaKiloVariableProductTest.php
    ├── PromptV2FormulasAndRawMaterialsTest.php
    └── DashboardAndReportPeriodFilterTest.php
```

---

### Estado Actual de Funcionalidades:

| Módulo / Funcionalidad | Estado | Comentarios Técnicos |
|---|:---:|---|
| **Catálogo & Fichas Técnicas** | 🟢 100% | Fórmulas, pesos calculados y nominales, precios base y Tiers. |
| **Fórmulas & Materias Primas** | 🟢 100% | 17 insumos con costo logístico, versionado y ponderación matemática exacta. |
| **APK Móvil de Captura (Flutter)** | 🟢 100% | Soporte para bobinas con desglose individual de rollos, SQLite offline y `v2.3.0`. |
| **Báscula Web & Control de Calidad** | 🟢 100% | Edición individual de bobinas, aprobación, rechazo con motivo y generación de QR. |
| **Monitor Principal (Dashboard)** | 🟢 100% | Selector dinámico de períodos (Hoy por defecto, Semana, Mes, Rango) y KPIs. |
| **Reportes Históricos & PDF** | 🟢 100% | Filtros avanzados, cuadro de balance financiero y formato para impresión. |
| **Tickets Térmicos con QR** | 🟢 100% | Generación de tickets con QR para trazabilidad física de bultos y rollos. |
| **Almacén / Pre-Levantamiento** | 🟢 100% | Escaneo QR y recepción física de mercancía aprobada. |

---

## 6. RECOMENDACIONES PARA EL ARQUITECTO DE SOFTWARE

Para la siguiente fase evolutiva del sistema y optimización de la infraestructura, se recomiendan las siguientes mejoras arquitectónicas:

1. **Integración Automática con el Inventario General del POS (`sales` / `products` de JSPOS):**
   * *Situación actual:* El flujo de producción culmina en el estado `lifted` dentro de `bag_productions`.
   * *Recomendación:* Crear un Listener / Evento en Laravel (`ProductionLiftedEvent`) que incremente automáticamente el stock disponible en la tabla de productos comerciales del sistema de facturación/ventas de JSPOS (`products.stock`), cerrando el ciclo completo desde la extrusión del plástico hasta la venta final al cliente.

2. **Comunicación Bidireccional en Tiempo Real con WebSockets (Laravel Reverb / Pusher):**
   * *Situación actual:* La pantalla de báscula y el dashboard requieren refresco o recarga de datos para mostrar los nuevos pesajes que llegan desde los teléfonos móviles.
   * *Recomendación:* Implementar WebSockets para que el pesaje de cada bobina o bulto aparezca en la pantalla del auditor en la báscula de forma instantánea sin necesidad de recargar la página.

3. **Módulo de Mermas, Retales y Balance de Masa:**
   * *Recomendación:* Incorporar una tabla `bag_scrap_records` vinculada a `bag_shifts` para que el operario registre el peso de los desperdicios o purgas de máquina al inicio/cierre de turno. Esto permitirá calcular el **rendimiento porcentual de transformación de resina vs. merma**.

4. **Estrategia de Caché para Parámetros Globales (Redis):**
   * *Recomendación:* Almacenar en Redis las configuraciones de `bag_cost_settings` y las recetas de `production_formulas` con invalidación por tags, reduciendo lecturas repetitivas a MySQL durante picos de sincronización en cambios de turno.
