<?php

return [
    // Ventas
    'sales_index' => ['name' => 'Ver Ventas', 'description' => 'Permite ver el listado general de ventas realizadas.'],
    'sales_create' => ['name' => 'Crear Venta', 'description' => 'Permite registrar nuevas ventas en el sistema.'],
    'sales_edit' => ['name' => 'Editar Venta', 'description' => 'Permite modificar los detalles de una venta existente.'],
    'sales_delete' => ['name' => 'Eliminar Venta', 'description' => 'Permite eliminar registros de ventas (Soft Delete).'],
    'sales_pdf' => ['name' => 'Generar PDF', 'description' => 'Permite descargar el comprobante de venta en PDF.'],
    'sales_view_all' => ['name' => 'Ver Todas las Ventas', 'description' => 'Permite ver las ventas realizadas por cualquier usuario.'],
    'sales_view_own' => ['name' => 'Ver Ventas Propias', 'description' => 'Limita al usuario a ver solo las ventas que él mismo realizó.'],
    'sales_switch_warehouse' => ['name' => 'Cambiar Sucursal', 'description' => 'Permite vender desde cualquier sucursal/almacén habilitado.'],
    'sales_mix_warehouses' => ['name' => 'Mezclar Almacenes', 'description' => 'Permite agregar productos de diferentes almacenes en una misma venta.'],
    'sales_approve_deletion' => ['name' => 'Aprobar Eliminación', 'description' => 'Permite autorizar la eliminación de una venta solicitada por otro usuario.'],
    'sales_manage_adjustments' => ['name' => 'Ajustes de Precio/Flete', 'description' => 'Permite modificar comisiones, fletes y descuentos especiales en la venta.'],
    'sales_change_invoice_currency' => ['name' => 'Cambiar Divisa Factura', 'description' => 'Permite cambiar la moneda en la que se genera la factura (USD/BS).'],
    'sales_configure_price_list' => ['name' => 'Configurar Lista Precios', 'description' => 'Permite crear y modificar listas de precios.'],
    'sales_generate_price_list' => ['name' => 'Generar Lista Precios', 'description' => 'Permite descargar o visualizar las listas de precios configuradas.'],
    'sales_show_exchange_rate' => ['name' => 'Ver Tasa USD/BCV', 'description' => 'Permite ver la referencia de tasa de cambio USD/BCV en ventas y productos.'],
    'sales_reset_credit_snapshot' => ['name' => 'Actualizar Reglas de Crédito', 'description' => 'Permite actualizar las políticas de crédito (descuentos, plazos) de una venta según la configuración actual del cliente.'],

    // Ordenes
    'orders_view_all' => ['name' => 'Ver Todas las Órdenes', 'description' => 'Permite ver órdenes guardadas por cualquier usuario.'],
    'orders_view_own' => ['name' => 'Ver Órdenes Propias', 'description' => 'Permite ver solo las órdenes guardadas por el usuario.'],
    'orders_add_to_cart' => ['name' => 'Cargar al Carrito', 'description' => 'Permite retomar una orden guardada y enviarla a la caja.'],
    'orders_delete' => ['name' => 'Eliminar Orden', 'description' => 'Permite eliminar órdenes guardadas permanentemente.'],
    'orders_edit' => ['name' => 'Editar Orden', 'description' => 'Permite modificar una orden guardada.'],
    'orders_details' => ['name' => 'Ver Detalles Orden', 'description' => 'Permite ver los productos que contiene una orden guardada.'],
    'orders_pdf' => ['name' => 'PDF Orden', 'description' => 'Permite generar un PDF de la orden guardada.'],

    // Pagos
    'payments_view_all' => ['name' => 'Ver Todos los Pagos', 'description' => 'Permite ver el historial de pagos de todos los clientes/vendedores.'],
    'payments_view_own' => ['name' => 'Ver Pagos Propios', 'description' => 'Permite ver solo los pagos registrados por el usuario.'],
    'payments_pay' => ['name' => 'Registrar Pago', 'description' => 'Permite abonar a una cuenta por cobrar o registrar un pago.'],
    'payments_history' => ['name' => 'Historial de Pagos', 'description' => 'Permite acceder al detalle histórico de pagos de un cliente.'],
    'payments_print_receipt' => ['name' => 'Imprimir Recibo', 'description' => 'Permite imprimir el comprobante de un pago individual.'],
    'payments_view_proof' => ['name' => 'Ver Comprobante', 'description' => 'Permite visualizar la imagen/captura del comprobante de transferencia.'],
    'payments_print_history' => ['name' => 'Imprimir Historial', 'description' => 'Permite imprimir el estado de cuenta completo.'],
    'payments_print_pdf' => ['name' => 'PDF Historial', 'description' => 'Permite descargar el estado de cuenta en PDF.'],
    'payments_upload' => ['name' => 'Subir Pago', 'description' => 'Permite a los vendedores subir comprobantes de pago para revisión.'],
    'payments_approve' => ['name' => 'Aprobar Pagos', 'description' => 'Permite confirmar pagos pendientes y aplicarlos a la cuenta.'],
    'payments_register_direct' => ['name' => 'Pago Directo', 'description' => 'Permite registrar un pago como "Aprobado" directamente, saltando la revisión.'],
    'payments_delete' => ['name' => 'Eliminar Pago', 'description' => 'Permite anular o eliminar un pago registrado.'],
    'payments_create_credit_note' => ['name' => 'Crear Nota de Crédito', 'description' => 'Permite crear ajustes de saldo manuales (Notas de Crédito) al momento de abonar.'],

    // Caja
    'cash_register_open' => ['name' => 'Abrir Caja', 'description' => 'Permite realizar la apertura de caja diaria.'],
    'cash_register_close' => ['name' => 'Cerrar Caja', 'description' => 'Permite realizar el cierre y corte de caja.'],
    'cash_register_access' => ['name' => 'Acceso a Caja', 'description' => 'Permite ver el estado de la caja sin realizar apertura/cierre.'],
    'cash_register_bypass' => ['name' => 'Omitir Caja', 'description' => 'Permite vender sin necesidad de tener una caja abierta (Vendedores foráneos).'],

    // Productos
    'products_index' => ['name' => 'Ver Productos', 'description' => 'Permite acceder al catálogo de productos.'],
    'products_create' => ['name' => 'Crear Producto', 'description' => 'Permite agregar nuevos productos al catálogo.'],
    'products_edit' => ['name' => 'Editar Producto', 'description' => 'Permite modificar precios, costos y detalles de productos.'],
    'products_delete' => ['name' => 'Eliminar Producto', 'description' => 'Permite eliminar productos del sistema.'],
    'products_import' => ['name' => 'Importar Productos', 'description' => 'Permite cargar productos masivamente desde Excel.'],
    'products_labels' => ['name' => 'Etiquetas', 'description' => 'Permite generar códigos de barra y etiquetas.'],

    // Categorias
    'categories_index' => ['name' => 'Ver Categorías', 'description' => 'Permite listar las categorías de productos.'],
    'categories_create' => ['name' => 'Crear Categoría', 'description' => 'Permite registrar nuevas categorías.'],
    'categories_edit' => ['name' => 'Editar Categoría', 'description' => 'Permite modificar nombres de categorías.'],
    'categories_delete' => ['name' => 'Eliminar Categoría', 'description' => 'Permite borrar categorías.'],

    // Clientes
    'customers_index' => ['name' => 'Ver Clientes', 'description' => 'Permite acceder al directorio de clientes.'],
    'customers_create' => ['name' => 'Crear Cliente', 'description' => 'Permite registrar nuevos clientes.'],
    'customers_edit' => ['name' => 'Editar Cliente', 'description' => 'Permite actualizar datos de clientes.'],
    'customers_delete' => ['name' => 'Eliminar Cliente', 'description' => 'Permite eliminar clientes.'],
    'customers_view_all' => ['name' => 'Ver Todos Clientes', 'description' => 'Permite ver la base de datos completa de clientes.'],
    'customers_view_own' => ['name' => 'Ver Clientes Propios', 'description' => 'Limita a ver solo los clientes asignados al vendedor.'],

    // Proveedores
    'suppliers_index' => ['name' => 'Ver Proveedores', 'description' => 'Permite listar proveedores.'],
    'suppliers_create' => ['name' => 'Crear Proveedor', 'description' => 'Permite registrar nuevos proveedores.'],
    'suppliers_edit' => ['name' => 'Editar Proveedor', 'description' => 'Permite modificar datos de proveedores.'],
    'suppliers_delete' => ['name' => 'Eliminar Proveedor', 'description' => 'Permite eliminar proveedores.'],

    // Compras
    'purchases_index' => ['name' => 'Ver Compras', 'description' => 'Permite ver historial de compras/abastecimiento.'],
    'purchases_create' => ['name' => 'Registrar Compra', 'description' => 'Permite registrar ingreso de mercancía de proveedores.'],
    'purchases_edit' => ['name' => 'Editar Compra', 'description' => 'Permite modificar una compra registrada.'],
    'purchases_delete' => ['name' => 'Eliminar Compra', 'description' => 'Permite anular compras.'],

    // Inventario
    'inventory_index' => ['name' => 'Ver Inventario', 'description' => 'Permite ver stock actual por almacén.'],
    'adjustments_create' => ['name' => 'Crear Ajuste', 'description' => 'Permite realizar cargos y descargos manuales de inventario.'],
    'adjustments_approve' => ['name' => 'Aprobar Ajuste', 'description' => 'Permite autorizar un ajuste de inventario pendiente.'],
    'transfers_create' => ['name' => 'Crear Traspaso', 'description' => 'Permite enviar mercancía entre sucursales.'],
    'warehouses_index' => ['name' => 'Ver Almacenes', 'description' => 'Permite listar almacenes/sucursales.'],
    'warehouses_create' => ['name' => 'Crear Almacén', 'description' => 'Permite registrar nuevas sucursales.'],
    'warehouses_edit' => ['name' => 'Editar Almacén', 'description' => 'Permite modificar datos de sucursales.'],
    'warehouses_delete' => ['name' => 'Eliminar Almacén', 'description' => 'Permite eliminar almacenes.'],

    // Usuarios
    'users_index' => ['name' => 'Ver Usuarios', 'description' => 'Permite listar usuarios del sistema.'],
    'users_create' => ['name' => 'Crear Usuario', 'description' => 'Permite registrar nuevos usuarios/empleados.'],
    'users_edit' => ['name' => 'Editar Usuario', 'description' => 'Permite modificar contraseñas y datos de usuarios.'],
    'users_delete' => ['name' => 'Eliminar Usuario', 'description' => 'Permite dar de baja usuarios.'],

    // Roles
    'roles_index' => ['name' => 'Ver Roles', 'description' => 'Permite ver los roles de seguridad configurados.'],
    'roles_create' => ['name' => 'Crear Rol', 'description' => 'Permite definir nuevos roles.'],
    'roles_edit' => ['name' => 'Editar Rol', 'description' => 'Permite cambiar nombre de roles.'],
    'roles_delete' => ['name' => 'Eliminar Rol', 'description' => 'Permite borrar roles.'],
    'permissions_assign' => ['name' => 'Asignar Permisos', 'description' => 'Permite otorgar o quitar permisos a los roles.'],

    // Reportes
    'reports_sales' => ['name' => 'Rep. Ventas', 'description' => 'Acceso a reportes detallados de ventas.'],
    'reports_purchases' => ['name' => 'Rep. Compras', 'description' => 'Acceso a reportes de gastos y compras.'],
    'reports_stock' => ['name' => 'Rep. Stock', 'description' => 'Acceso a reportes de existencias y valoración.'],
    'reports_financial' => ['name' => 'Rep. Financieros', 'description' => 'Acceso a cuentas por cobrar/pagar y balances.'],
    'reports_commissions' => ['name' => 'Rep. Comisiones', 'description' => 'Acceso a cálculos de comisiones por venta.'],

    // Comisiones
    'commissions_access' => ['name' => 'Acceder a Comisiones', 'description' => 'Permite ingresar al módulo de gestión de comisiones.'],
    'commissions_view_all' => ['name' => 'Ver Todas Comisiones', 'description' => 'Permite ver las comisiones de todos los vendedores. Si no se tiene, solo ve las propias.'],
    'commissions_manage' => ['name' => 'Gestionar Comisiones', 'description' => 'Permite procesar pagos, recalcular comisiones y descargar reportes.'],

    // Configuración
    'settings_index' => ['name' => 'Ver Configuración', 'description' => 'Acceso al panel principal de configuración.'],
    'settings_backups' => ['name' => 'Respaldos', 'description' => 'Permite gestionar y descargar copias de seguridad de la BD.'],
    'settings_logs' => ['name' => 'Ver Logs', 'description' => 'Permite ver el registro técnico de errores.'],
    'settings_update' => ['name' => 'Actualizar Sistema', 'description' => 'Permite aplicar actualizaciones de software.'],
    'settings_stock_reservation' => ['name' => 'Reserva Stock', 'description' => 'Configura si el stock se reserva al guardar pedidos.'],

    // Producción
    'production_index' => ['name' => 'Ver Producción', 'description' => 'Permite ver órdenes de producción.'],
    'production_create' => ['name' => 'Crear Producción', 'description' => 'Permite registrar procesos de fabricación.'],
    'production_delete' => ['name' => 'Eliminar Producción', 'description' => 'Permite anular órdenes de producción.'],

    // Distribución
    'distribution_map' => ['name' => 'Mapa Repartidores', 'description' => 'Permite ver ubicación en tiempo real de repartidores.'],

    // Sistema
    'system_is_seller' => ['name' => 'Es Vendedor', 'description' => 'Marca al usuario como "Vendedor" en listados y filtros.'],
    'system_is_foreign_seller' => ['name' => 'Es Vendedor Foráneo', 'description' => 'Indica que es un vendedor externo/calle sin caja fija en sucursal.'],

    // MIGRATED PERMISSIONS
    'adjustments_approve_cargo' => ['name' => 'Aprobar Cargos', 'description' => 'Permite aprobar entradas de inventario pendientes.'],
    'adjustments_approve_descargo' => ['name' => 'Aprobar Descargos', 'description' => 'Permite aprobar salidas de inventario pendientes.'],
    'orders_save' => ['name' => 'Guardar Orden', 'description' => 'Permite guardar una venta como orden pendiente (SHIFT+G).'],
    'payments_methods' => ['name' => 'Ver Métodos Pago', 'description' => 'Permite ver el bloque de botones de pago en el POS.'],
    'payments_method_cash' => ['name' => 'Pago Efectivo/Nequi', 'description' => 'Habilita el botón de pago rápido en Efectivo.'],
    'payments_method_credit' => ['name' => 'Pago Crédito', 'description' => 'Habilita el botón de pago a Crédito.'],
    'payments_method_bank' => ['name' => 'Pago Banco', 'description' => 'Habilita el botón de pago Bancario.'],
    'payments_method_nequi' => ['name' => 'Pago Nequi', 'description' => 'Habilita el botón de pago Nequi (si existe).'],

    // CONSULTATION MODULES
    'zelle_index' => ['name' => 'Ver Zelle', 'description' => 'Permite acceder al módulo de consulta de pagos Zelle.'],
    'zelle_view_details' => ['name' => 'Ver Detalles Zelle', 'description' => 'Permite ver la foto y detalles completos del pago Zelle.'],
    'zelle_print_pdf' => ['name' => 'Imprimir Zelle', 'description' => 'Permite generar el reporte PDF del pago Zelle.'],
    
    'bank_index' => ['name' => 'Ver Bancos', 'description' => 'Permite acceder al módulo de consulta de pagos Bancarios.'],
    'bank_view_details' => ['name' => 'Ver Detalles Banco', 'description' => 'Permite ver la foto y detalles completos del pago Bancario.'],
    'bank_print_pdf' => ['name' => 'Imprimir Banco', 'description' => 'Permite generar el reporte PDF del pago Bancario.'],
    'customer_statement_index' => ['name' => 'Ver Menú Principal', 'description' => 'Permite ver el botón "Estado de Cuenta" en la barra lateral y acceder al módulo.'],
    'customer_statement_view_all' => ['name' => 'Ver Todos los Clientes', 'description' => 'Permite consultar el estado de cuenta de cualquier cliente de la base de datos.'],
    'customer_statement_view_own' => ['name' => 'Ver Clientes Propios', 'description' => 'Limita la consulta solo a los clientes asignados directamente al usuario/vendedor.'],
    'reports.customer_payment_relationship' => ['name' => 'Rep. Cobros por Cliente', 'description' => 'Acceso al reporte detallado de cobros agrupado por cliente con filtros por factura y fechas.'],
];
