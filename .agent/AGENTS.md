# Reglas del Proyecto (JSPOS Sales)

## Aislamiento Estricto de Proyectos (REGLA CRÍTICA)
- **Identidad Exclusiva:** Este directorio (`c:\laragon\www\jspos-sales`) pertenece ÚNICA Y EXCLUSIVAMENTE a **JSPOS Sales** (Sistema Comercial de Punto de Venta, facturación, compras, inventario de tiendas, clientes y cobranzas).
- **Prohibición de Mezcla:** NUNCA se debe aplicar código, vistas, modelos, rutas, migraciones ni lógicas de **JSBolsas Pro** (Fábrica de Bolsas) en este proyecto.
- **Control de Contexto:** El agente NUNCA debe cambiar de proyecto por iniciativa propia. Solo el usuario indicará explícitamente cuándo cambiar de proyecto.

## Pruebas Obligatorias (TDD y Verificación)
- **Ejecución y Creación de Pruebas Obligatoria:** En cada modificación de código, corrección de errores o nueva funcionalidad, el agente **DEBE** escribir y ejecutar pruebas unitarias, de integración o de regresión en Laravel (`php artisan test`) para validar empíricamente que los cambios funcionan correctamente y no rompen ninguna regla del negocio.
- **Validación Final:** Ninguna tarea se considerará finalizada sin haber ejecutado las pruebas correspondientes y mostrado los resultados limpios al usuario.

