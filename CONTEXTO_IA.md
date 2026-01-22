# Contexto y Memoria del Proyecto para Antigravity (IA)

Este archivo sirve para almacenar instrucciones recurrentes, decisiones de diseño y contexto del proyecto `jspos-sales`.
La IA debe leer este archivo para entender cómo trabajar en este proyecto específico.

## 1. Reglas Generales de Desarrollo
- **Stack Tecnológico**: Laravel, Blade/Vue (según aplique), Tailwind CSS, MySQL.
- **Estilo de Código**: Seguir estándares de Laravel.
- **Idioma**: Español (según preferencia del usuario).
- **Diseño Responsivo (OBLIGATORIO)**: Todas las nuevas interfaces o modificaciones DEBEN verse y funcionar correctamente en Celulares, Tablets y PC. El sistema es multi-dispositivo.

## 2. Instrucciones Frecuentes
*(Pega aquí las instrucciones que repites en cada sesión)*
- Ejemplo: "Siempre validar los stocks antes de crear una venta."
- Ejemplo: "Usar componentes de Blade para elementos repetitivos."

## 3. Arquitectura y Lógica Clave
- **Base de Datos**: Ver directorio `database/migrations` para estructura.
- **Modelos**: Ubicados en `app/Models` (o `app/` si es Laravel antiguo).
- **Flujos Críticos**: Ventas, Control de Stock, Reportes.

## 4. Gestión de Dispositivos e Impresión
- **Jerarquía de Configuración de Impresora**:
    1. **Dispositivo** (`DeviceAuthorization`): Prioridad MÁXIMA. Se configura por cookie `device_token`.
    2. **Usuario** (`User`): Si el dispositivo no tiene impresora configurada.
    3. **Global** (`Configuration`): Fallback final si ni dispositivo ni usuario tienen configuración.
- **Ancho de Papel**: Soportado 58mm y 80mm. Se define junto con el nombre de la impresora.
- **Drivers**: Se usa `Mike42\Escpos` con `WindowsPrintConnector`. El nombre de la impresora debe coincidir con el recurso compartido en Windows.

## 5. Historial de Decisiones Importantes
- [Fecha]: decisión tomada...

## 6. Flujo de Trabajo y Control de Versiones (CRÍTICO)
- **CHANGELOG Obligatorio**: ANTES de hacer cualquier commit de release, tag, o `git push origin develop`, **SIEMPRE** se debe actualizar el archivo `CHANGELOG.md` con los cambios realizados.
- **Recordatorio Constante**: Si el usuario pide "subir cambios" o "hacer release", el primer paso es verificar y actualizar el Changelog.
