# 📘 Guía Definitiva de Instalación de JSPOS Sales desde Archivo ZIP

Esta guía detalla paso a paso el procedimiento completo para instalar, configurar, validar la licencia y poner en producción el sistema **JSPOS Sales** en un entorno Windows utilizando un archivo comprimido `.zip` (Método Despliegue Manual / Release).

---

## 📋 1. Requisitos Previos e Instalación de Software Base

Antes de comenzar, asegúrate de tener instaladas las siguientes herramientas en tu servidor o computadora Windows:

1. **Laragon Full (Servidor Web WAMP)**
   - Descarga e instala Laragon (incluye PHP 8.1 / 8.2, MySQL 8.0, Apache/Nginx).
   - Ruta estándar de instalación: `C:\laragon`.
2. **Composer (Gestor de Dependencias de PHP)**
   - Verifica la instalación abriendo una consola (CMD / PowerShell):
     ```cmd
     composer --version
     ```
3. **Node.js y NPM (Entorno para Scripts y WhatsApp API)**
   - Verifica la instalación:
     ```cmd
     node -v
     npm -v
     ```
4. **Google Drive para Escritorio (Opcional para Respaldos en la Nube)**
   - Instala la aplicación oficial de Google Drive y sincroniza una unidad local (ejemplo: `G:\My Drive`).

---

## 📂 2. Descompresión y Ubicación de Archivos

1. Descarga el paquete de lanzamiento `jspos-sales.zip`.
2. Extrae el contenido directamente dentro de la carpeta `www` de Laragon:
   - **Ruta de instalación:** `C:\laragon\www\jspos-sales`
3. Verifica la estructura de directorios resultante:
   ```text
   C:\laragon\www\jspos-sales\
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── nssm/
   ├── public/
   ├── resources/
   ├── routes/
   ├── storage/
   ├── vendor/
   ├── whatsapp-api/
   ├── .env.example
   ├── artisan
   ├── backup.bat
   ├── instalar_servicios.bat
   └── version.txt
   ```

---

## 🛠️ 3. Permisos de Carpetas en Windows

Asegúrate de que el usuario del sistema o del servidor web tenga permisos de escritura en los siguientes directorios:
- `storage/` y todas sus subcarpetas (`storage/app`, `storage/framework`, `storage/logs`).
- `bootstrap/cache/`

---

## 🗄️ 4. Creación de la Base de Datos MySQL

1. Inicia los servicios de Laragon (haciendo clic en **"Start All"** en Laragon).
2. Abre la consola de MySQL o HeidiSQL e ingresa con el usuario `root` (sin contraseña por defecto en Laragon):
3. Ejecuta el comando SQL para crear la base de datos:
   ```sql
   CREATE DATABASE jspos_sales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## ⚙️ 5. Configuración del Archivo `.env`

1. En la raíz del proyecto (`C:\laragon\www\jspos-sales`), haz una copia del archivo `.env.example` y renómbralo a `.env`:
   - En consola CMD:
     ```cmd
     copy .env.example .env
     ```
2. Abre el archivo `.env` con un editor de texto (Notepad++, VS Code, Bloc de Notas) y configura los valores clave:

   ```env
   APP_NAME="JSPOS Sales"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://jspos-sales.test

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=jspos_sales
   DB_USERNAME=root
   DB_PASSWORD=

   # Ruta binaria de MySQL/mysqldump para respaldos automatizados
   DB_DUMP_PATH="C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin"

   FILESYSTEM_DISK=local
   QUEUE_CONNECTION=database
   SESSION_DRIVER=file
   ```

   > 💡 **Nota sobre `DB_DUMP_PATH`:** Ajusta la versión exacta de MySQL según la que tengas instalada dentro de `C:\laragon\bin\mysql\`.

---

## 💻 6. Comandos de Inicialización de Laravel

Abre una terminal CMD o PowerShell **dentro de la carpeta del proyecto** (`C:\laragon\www\jspos-sales`) y ejecuta la siguiente secuencia exacta de comandos:

### Paso 6.1: Instalación/Verificación de Dependencias Composer
Si el archivo `.zip` no incluía la carpeta `vendor`, ejecútalo:
```cmd
composer install --no-dev --optimize-autoloader
```

### Paso 6.2: Generar la Llave Única de la Aplicación
```cmd
php artisan key:generate
```

### Paso 6.3: Crear el Enlace Simbólico de Almacenamiento (Storage Link)
Este comando vincula la carpeta de almacenamiento de imágenes/documentos con la carpeta pública accesible vía web:
```cmd
php artisan storage:link
```

### Paso 6.4: Migraciones y Carga de Datos Maestros
Ejecuta la creación de tablas e inserts de datos base del sistema (Roles, Permisos, Monedas VED/USD/COP, Bancos):
```cmd
php artisan migrate --seed
```

### Paso 6.5: Limpieza e Optimización de Caché
```cmd
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔑 7. Validación y Activación de Licencia

El sistema JSPOS Sales incluye un mecanismo estricto de protección de licencias basado en Base64 y firma de máquina.

### Método A: Activación por Interfaz Web (Recomendado)
1. Abre tu navegador e ingresa a `http://jspos-sales.test` (o la IP local/dominio).
2. Si el sistema te redirige al asistente de instalación o a la vista de Licencia, ingresa tu clave de licencia provista.
3. El sistema validará la expiración (`expires_at`), módulos permitidos y guardará el registro en la base de datos.

### Método B: Activación Manual por Comando Artisan (SuperAdmin / Dev)
Si necesitas generar o activar una licencia manualmente desde consola:
```cmd
php artisan license:generate --client="Nombre Cliente" --days=365 --type="full"
```

---

## ☁️ 8. Configuración de Respaldos de Base de Datos y Google Drive

JSPOS Sales incluye scripts `.bat` integrados para generar respaldos automáticos de la base de datos y copiarlos localmente o en Google Drive.

### Paso 8.1: Verificación de comando de respaldo manual
Prueba que el paquete de respaldo de Laravel funcione correctamente:
```cmd
php artisan backup:run --only-db
```
Los respaldos generados se almacenarán en `storage/app/backups/`.

### Paso 8.2: Configuración del script `backup.bat`
Abre el archivo `backup.bat` ubicado en la raíz del proyecto y verifica las rutas:
```bat
set "PROJECT_PATH=C:\laragon\www\jspos-sales"
set "SOURCE_PATH=%PROJECT_PATH%\storage\app\backups"
set "DEST_PATH=G:\My Drive\Backups_JSPOS"
```
- Modifica `DEST_PATH` hacia la ruta de tu Google Drive sincronizado o disco externo.
- Puedes probar su funcionamiento ejecutando el archivo `backup.bat` con doble clic.

---

## ⚙️ 9. Instalación de Servicios en Segundo Plano (NSSM)

Para que el envío de mensajes por **WhatsApp Web**, la **cola de tareas pesadas** y el **planificador de respaldos** funcionen automáticamente sin necesidad de mantener ventanas de consola abiertas:

1. Haz clic derecho sobre el archivo `instalar_servicios.bat` ubicado en la raíz del proyecto.
2. Selecciona **"Ejecutar como Administrador"**.
3. El script instalará e iniciará automáticamente los 3 servicios de Windows usando NSSM:
   - **`JSPOS_WhatsApp_API`**: Servicio Node.js en `whatsapp-api/`.
   - **`JSPOS_Queue_Worker`**: `php artisan queue:work`.
   - **`JSPOS_Scheduler`**: `php artisan schedule:work`.

4. Para verificar que los servicios están activos:
   - Presiona `Win + R`, escribe `services.msc` y presiona Enter.
   - Busca los servicios que comiencen por **JSPOS_** y verifica que estén en estado **En Ejecución (Running)**.

---

## ✅ 10. Pasos de Validación y Lista de Chequeo Final

Antes de entregar el sistema en producción, ejecuta la siguiente lista de verificación:

- [ ] **Acceso Web:** Navega a `http://jspos-sales.test` y comprueba que cargue la pantalla de Login o Asistente.
- [ ] **Enlace de Imágenes:** Sube una imagen de producto o logo y verifica que se visualice correctamente (validación de `php artisan storage:link`).
- [ ] **Módulo de Ventas / POS:** Verifica que se pueda seleccionar la moneda y asignar un vendedor en caja.
- [ ] **Servicios NSSM:** Comprueba que en `services.msc` estén corriendo los 3 servicios de JSPOS.
- [ ] **Revisión de Logs:** Inspecciona que no existan errores fatales en `storage/logs/laravel.log`.

---

¡**Felicidades!** El sistema **JSPOS Sales** ha sido instalado y configurado correctamente mediante el método ZIP. 🚀
