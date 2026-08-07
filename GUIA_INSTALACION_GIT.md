# 🐙 Guía Definitiva de Instalación de JSPOS Sales mediante Git Clone

Esta guía detalla el procedimiento profesional paso a paso para clonar, instalar, configurar, validar la licencia y desplegar el sistema **JSPOS Sales** en un entorno Windows utilizando el repositorio oficial de **Git**. Este método es el recomendado para mantener el sistema actualizado de forma constante mediante control de versiones.

---

## 📋 1. Requisitos Previos e Instalación de Software Base

Asegúrate de instalar los siguientes componentes de software en tu servidor Windows antes de iniciar:

1. **Git para Windows**
   - Descarga e instala Git para Windows desde [git-scm.com](https://git-scm.com).
   - Verifica la instalación abriendo una consola (CMD / PowerShell / Git Bash):
     ```cmd
     git --version
     ```
2. **Laragon Full (Servidor Web WAMP)**
   - Descarga e instala Laragon (PHP 8.1 / 8.2, MySQL 8.0, Apache/Nginx).
   - Ruta recomendada: `C:\laragon`.
3. **Composer (Gestor de Paquetes PHP)**
   - Verifica la instalación:
     ```cmd
     composer --version
     ```
4. **Node.js y NPM (Entorno JS y WhatsApp API)**
   - Verifica la instalación:
     ```cmd
     node -v
     npm -v
     ```
5. **Google Drive para Escritorio (Opcional para Respaldos)**
   - Configura la sincronización con una carpeta local (ej: `G:\My Drive`).

---

## 🐙 2. Clonación del Repositorio Oficial

1. Abre la terminal de comandos (CMD, PowerShell o Git Bash).
2. Dirígete al directorio web de Laragon (`C:\laragon\www`):
   ```cmd
   cd C:\laragon\www
   ```
3. Ejecuta el comando de clonación:
   ```cmd
   git clone https://github.com/jhosagid7/jspos-sales.git
   ```
4. Entra a la carpeta del proyecto clonado:
   ```cmd
   cd jspos-sales
   ```

---

## 📦 3. Instalación de Dependencias (Composer y NPM)

Dado que un repositorio Git no incluye las carpetas compiladas `vendor/` ni `node_modules/`, debemos instalarlas:

### Paso 3.1: Instalar Dependencias de PHP con Composer
```cmd
composer install --no-dev --optimize-autoloader
```

### Paso 3.2: Instalar y Compilar Assets de Frontend con NPM
```cmd
npm install
npm run build
```

### Paso 3.3: Instalar Dependencias del Motor de WhatsApp
```cmd
cd whatsapp-api
npm install
cd ..
```

---

## 🗄️ 4. Creación de la Base de Datos MySQL

1. Inicia Laragon haciendo clic en **"Start All"**.
2. Abre la consola de MySQL o HeidiSQL e ingresa con el usuario `root`:
3. Ejecuta la sentencia SQL para crear la base de datos:
   ```sql
   CREATE DATABASE jspos_sales CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## ⚙️ 5. Configuración del Archivo de Entorno `.env`

1. Crea el archivo `.env` duplicando la plantilla `.env.example`:
   ```cmd
   copy .env.example .env
   ```
2. Edita el archivo `.env` ajustando los valores requeridos:

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

   > 💡 **Nota sobre `DB_DUMP_PATH`:** Revisa la versión exacta de tu ejecutable `mysqldump.exe` en `C:\laragon\bin\mysql\`.

---

## 💻 6. Comandos de Inicialización de Laravel

Ejecuta la siguiente secuencia obligatoria en la terminal dentro de `C:\laragon\www\jspos-sales`:

### Paso 6.1: Generar la Clave de Encriptación (`APP_KEY`)
```cmd
php artisan key:generate
```

### Paso 6.2: Crear el Enlace Simbólico de Almacenamiento (Storage Link)
Este comando vincula `storage/app/public` con `public/storage`:
```cmd
php artisan storage:link
```

### Paso 6.3: Ejecutar Migraciones y Cargar Datos Maestros Base
Crea la estructura de tablas e inserta los catálogos requeridos (Roles, Permisos, Monedas, Bancos):
```cmd
php artisan migrate --seed
```

### Paso 6.4: Optimización y Limpieza de Cachés
```cmd
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔑 7. Validación y Activación de Licencia

El sistema requiere una licencia activa para habilitar sus módulos.

### Opción A: Activación mediante la Interfaz Web
1. Ingresa a `http://jspos-sales.test` desde tu navegador web.
2. Si aparece la pantalla de activación de licencia, pega la clave en Base64 suministrada.
3. El sistema verificará la fecha de vencimiento (`expires_at`), máquina y módulos activos.

### Opción B: Generación Manual vía Comando Artisan (Desarrolladores / Administradores)
Si deseas generar una licencia desde la consola:
```cmd
php artisan license:generate --client="Nombre Cliente" --days=365 --type="full"
```

---

## ☁️ 8. Configuración de Respaldos de Base de Datos y Google Drive

JSPOS Sales incluye integración nativa con Spatie Backup y scripts de sincronización `.bat`.

### Paso 8.1: Probar respaldo de base de datos
```cmd
php artisan backup:run --only-db
```
Los archivos `.zip` comprimidos con el dump `.sql` se guardan en `storage/app/backups/`.

### Paso 8.2: Configuración del Script de Sincronización `backup.bat`
Abre el archivo `backup.bat` en la raíz del proyecto y ajusta las rutas:
```bat
set "PROJECT_PATH=C:\laragon\www\jspos-sales"
set "SOURCE_PATH=%PROJECT_PATH%\storage\app\backups"
set "DEST_PATH=G:\My Drive\Backups_JSPOS"
```
- Asegúrate de que `DEST_PATH` coincida con la unidad y carpeta de tu Google Drive para Escritorio.

---

## ⚙️ 9. Instalación de Servicios en Segundo Plano (NSSM)

Para garantizar la ejecución continua e invisible del bot de **WhatsApp Web**, la **cola de tareas** y el **planificador de respaldos**:

1. Haz clic derecho en el archivo `instalar_servicios.bat` en la raíz del proyecto.
2. Selecciona **"Ejecutar como Administrador"**.
3. El script configurará automáticamente 3 servicios del sistema Windows mediante NSSM:
   - **`JSPOS_WhatsApp_API`**: Motor Node.js de WhatsApp.
   - **`JSPOS_Queue_Worker`**: Procesador de colas en segundo plano.
   - **`JSPOS_Scheduler`**: Cron de respaldos y tareas programadas.

4. Abre la consola de servicios (`Win + R` -> `services.msc`) y verifica que los 3 servicios figuren con el estado **En Ejecución (Running)**.

---

## 🔄 10. Procedimiento para Futuras Actualizaciones con Git

Al estar instalado mediante Git Clone, actualizar el sistema en el cliente a una nueva versión es sumamente sencillo:

```cmd
cd C:\laragon\www\jspos-sales
git pull origin develop
composer install --no-dev
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

---

## ✅ 11. Pasos de Validación Final

Ejecuta este chequeo para confirmar que todo quedó 100% operativo:

- [ ] **Navegación Web:** El sistema abre en `http://jspos-sales.test`.
- [ ] **Storage Link:** Las imágenes y logotipos cargan correctamente desde la carpeta `/storage`.
- [ ] **Módulo POS / Ventas:** El selector de monedas y el asignador de vendedores funcionan adecuadamente.
- [ ] **Servicios NSSM:** Los 3 servicios en `services.msc` están iniciados.
- [ ] **Logs de Sistema:** Sin errores críticos en `storage/logs/laravel.log`.

---

¡**Enhorabuena!** El sistema **JSPOS Sales** se ha instalado y desplegado profesionalmente mediante clonación de Git. 🚀
