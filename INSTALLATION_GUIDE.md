# Guía de Instalación Profesional - JSPOS Sales

Esta guía detalla los pasos para desplegar el sistema JSPOS utilizando el nuevo instalador híbrido v1.10.52.

## 1. Métodos de Instalación

El sistema detectará automáticamente el método utilizado para ajustar la lógica de despliegue.

### A. Método Git Clone (Recomendado para Desarrolladores)
Ideal si planeas recibir actualizaciones constantes y mantener un control de versiones profesional.

1.  Clona el repositorio: `git clone https://github.com/jhosagid7/jspos-sales.git`
2.  Entra al directorio: `cd jspos-sales`
3.  Instala dependencias: `composer install --no-dev`
4.  Apunta tu servidor web (Apache/Nginx) a la carpeta `/public`.
5.  Accede vía navegador para iniciar el instalador visual.

### B. Método de Copia Manual (Standard)
Ideal para despliegues rápidos en hosting compartidos o servidores sin Git.

1.  Sube todos los archivos del proyecto al servidor.
2.  Asegúrate de que la carpeta `vendor` esté presente (si descargas un release empaquetado).
3.  Verifica que las carpetas `storage` y `bootstrap/cache` tengan permisos de escritura (775 o 777).
4.  Accede vía navegador para iniciar el instalador visual.

---

## 2. Pasos del Instalador Visual

Una vez que accedas a la URL de tu sistema, el instalador te guiará por 5 pasos críticos:

### Paso 1: Requisitos y Entorno
El sistema verificará las extensiones de PHP y detectará si estás en modo **Git** o **Manual**. También validará que las dependencias estén instaladas.

### Paso 2: Base de Datos
Ingresa las credenciales de tu servidor MySQL. El instalador intentará crear la base de datos automáticamente si no existe.

### Paso 3: Estructura y Datos Maestros
Al hacer clic en **"Iniciar Despliegue Profesional"**, el sistema:
- Generará la `APP_KEY` automáticamente.
- Ejecutará las migraciones para crear la arquitectura de tablas.
- Cargará los **Datos Maestros** (Roles, Permisos, Monedas, Bancos) necesarios para operar sin datos basura.

### Paso 4: Activación de Licencia
Ingresa tu llave de licencia para vincular la instalación con tu servidor.

### Paso 5: Cuenta Administrador
Crea la cuenta del dueño del negocio. Esta cuenta tendrá el rol de **Admin** con acceso total.

---

## 3. Post-Instalación

Al finalizar, el sistema creará un archivo de bloqueo en `storage/installed`. Si necesitas reinstalar, borra ese archivo y limpia la base de datos.

**Soporte Técnico:** [jhonnypirela.dev](https://jhonnypirela.dev)
