# Plan de Implementación: Módulos SaaS y Permisos Granulares

## Objetivo
Convertir JSPOS en un producto SaaS monetizable mediante la restricción de módulos y características según el plan adquirido por el cliente, de manera completamente independiente a los permisos de los empleados (Spatie).

## Arquitectura Propuesta: Autorización de Dos Capas

### Capa 1: Feature Flags (Nivel de Licencia/Tienda)
Controla qué módulos existen y están accesibles en el sistema para *toda* la tienda, basándose en la licencia pagada.
- **Implementación**: Leer la configuración de la licencia (encriptada en la llave de activación).
- **Ejemplos de Flags**: `has_credits`, `has_commissions`, `advanced_products`, `max_devices` (límite numérico).
- **Seguridad**: Si el flag está en `false`, el módulo desaparece. Nadie puede acceder a él, ni el Super Admin. Las rutas estarán protegidas por un Middleware (`CheckModule`).

### Capa 2: Roles de Empleados (Nivel de Usuario - Spatie)
Controla qué empleados pueden acceder a los módulos *que ya están habilitados* por la Capa 1.
- **Implementación**: Uso estándar del paquete `spatie/laravel-permission` (ej. `@can('credits.create')`).

## Cambios a Nivel de Código (Futuros)

### 1. Directiva Blade Personalizada `@module`
Crear una directiva `@module('nombre_modulo')` para ocultar menús e interfaces enteras.
```blade
@module('credits')
    @can('credits.index')
        <li><a href="/creditos">Menú de Créditos</a></li>
    @endcan
@endmodule
```

### 2. Middleware de Protección de Rutas (`CheckModule`)
Evitar accesos directos por URL a módulos no pagados.
```php
Route::get('commissions', Commissions::class)
    ->middleware(['module:commissions', 'can:reports.commissions']);
```

### 3. Vistas Híbridas (Ej: Creación de Productos)
Para módulos intrínsecos como Productos, la vista principal siempre será accesible, pero las características avanzadas estarán envueltas en la directiva `@module`.
```blade
@module('advanced_products')
    <!-- Sección de variaciones, bobinas, múltiples listas de precio -->
@endmodule
```

### 4. Generación y Validación de Licencias
Asegurar que la llave temporal o de activación que el administrador/dueño del SaaS (Jhosagid) le entrega al cliente final contenga estos permisos encriptados, para que el cliente no pueda habilitarlos manualmente modificando su propia base de datos local.

## Próximos Pasos (Hoja de Ruta)
1. **Auditoría de Módulos Actuales**: Revisar todo el sistema JSPOS para identificar y hacer un listado de todos los módulos y características "Premium" vs "Básicas" y definir los Planes (Ej: Plan Básico, Plan Pro).
2. **Crear Rama de Desarrollo**: Crear la rama `feature/saas-modules` en Git para comenzar a programar esto sin afectar el sistema principal.
3. **Programar Core SaaS**: Crear el Middleware, la directiva Blade y adaptar el sistema de validación de licencias actual.
4. **Implementar en Vistas y Rutas**: Aplicar `@module` y el middleware a lo largo de todo el código.
