# 🖥️ Guía de Uso del Instalador Web Visual de JSPOS Sales

Esta guía detalla paso a paso cómo utilizar el **Asistente de Instalación Web Interactivo** de **JSPOS Sales** (`/install`). Este asistente permite desplegar la base de datos, estructurar el sistema, activar la licencia y crear la cuenta del administrador directamente desde la interfaz del navegador sin escribir comandos manuales.

---

## 🚀 1. ¿Cómo Iniciar el Asistente de Instalación Web?

El sistema JSPOS Sales incluye un middleware inteligente (`CheckInstalled.php`). 

1. **Asegúrate de que Laragon esté iniciado** (Servicio Apache/Nginx y MySQL activos).
2. **Abre tu navegador web** e ingresa a la dirección del proyecto:
   - `http://jspos-sales.test` (o `http://localhost/jspos-sales/public`)
3. Si el sistema no ha sido instalado previamente (es decir, no existe el archivo `storage/installed`), la aplicación **te redirigirá automáticamente** a la URL del asistente:
   - `http://jspos-sales.test/install`

> 💡 **Tip de Reinstalación:** Si necesitas volver a correr el instalador web desde cero en el futuro, simplemente elimina el archivo de bloqueo `storage/installed` y limpia la base de datos en MySQL.

---

## 📋 2. Pasos del Asistente Visual de Instalación

El instalador visual consta de **5 pasos guiados e intuitivos**:

```text
[Paso 1: Requisitos] ➔ [Paso 2: Base de Datos] ➔ [Paso 3: Migraciones] ➔ [Paso 4: Licencia] ➔ [Paso 5: Administrador] ➔ [Fin]
```

---

### 🔍 Paso 1: Verificación de Requisitos y Entorno (`/install/step1`)

En esta primera pantalla, el sistema audita automáticamente el servidor:

1. **Verificación de Extensiones de PHP:**
   - Valida que la versión de PHP sea **8.1 o superior**.
   - Comprueba extensiones obligatorias: `bcmath`, `ctype`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`.
2. **Permisos de Archivos:**
   - Comprueba que las carpetas `storage/` y `bootstrap/cache/` tengan permisos de escritura.
3. **Detección Automática de Método de Despliegue:**
   - El sistema detectará si estás instalando vía **Git Clone** (si existe la carpeta `.git`) o vía **Copia Manual ZIP**.
   - Verifica la presencia de la carpeta de dependencias `vendor/`.

> 👉 Haz clic en el botón **"Siguiente: Configurar Base de Datos"** una vez que todos los indicadores figuren en verde.

---

### 🗄️ Paso 2: Conexión y Creación de Base de Datos (`/install/step2`)

En este paso ingresarás las credenciales de tu servidor de base de datos MySQL:

1. **Campos del Formulario:**
   - **Servidor (DB Host):** `127.0.0.1` (o la IP de tu servidor MySQL).
   - **Puerto (DB Port):** `3306`.
   - **Nombre de la Base de Datos:** `jspos_sales` (o el nombre deseado).
   - **Usuario (DB Username):** `root`.
   - **Contraseña (DB Password):** *(dejar en blanco en Laragon o colocar la clave asignada)*.

2. **Creación Automática:**
   - No necesitas crear la base de datos previamente en phpMyAdmin o HeidiSQL. Si la base de datos no existe, **el instalador la creará automáticamente** con el cotejo `utf8mb4_unicode_ci`.
   - El instalador escribirá los datos de conexión directamente en tu archivo `.env`.

> 👉 Haz clic en el botón **"Guardar y Probar Conexión"**.

---

### 🏗️ Paso 3: Estructura de Tablas y Carga de Datos Maestros (`/install/step3`)

En esta fase el sistema prepara toda la arquitectura interna del software:

1. Haz clic en el botón **"Iniciar Despliegue Profesional"**.
2. El sistema ejecutará automáticamente en segundo plano:
   - **`key:generate`**: Genera la clave de encriptación única de Laravel en el `.env`.
   - **`migrate`**: Crea todas las tablas e índices de la base de datos.
   - **`db:seed --class=MasterDataSeeder`**: Inserta los datos maestros e institucionales requeridos (Roles de usuarios, Permisos del sistema, Monedas VED/USD/COP, Tasa de cambio base y Catálogo de Bancos).

---

### 🔑 Paso 4: Activación de Licencia (`/install/step4`)

Para que el sistema quede habilitado comercialmente, requiere una clave de licencia activa vinculada a la firma digital del equipo.

En esta pantalla se mostrará tu **ID Único de Sistema / Firma de Máquina** (`Client System ID`):

#### Opción A: Activación por Clave de Licencia (Manual)
1. Si cuentas con un código de licencia en Base64 provisto por tu proveedor:
2. Pega la clave en el recuadro **"Clave de Licencia Base64"**.
3. Haz clic en **"Activar Licencia"**.

#### Opción B: Registro y Aprobación en Línea (Vía Servidor Central)
1. Ingresa la IP de la red VPN (ZeroTier / Tailscale) o la IP del servidor de licencias en **"IP del Servidor de Licencias"**.
2. Escribe el **Nombre del Cliente / Negocio**.
3. Haz clic en **"Registrar y Solicitar Licencia"**.
4. El servidor central registrará el equipo en línea. Una vez que el administrador otorgue la licencia a distancia, presiona **"Consultar Aprobación"** para activar automáticamente el sistema.

---

### 👤 Paso 5: Registro de la Cuenta de Administrador (`/install/step5`)

Aquí crearás el usuario principal que tendrá acceso completo al sistema:

1. **Nombre Completo:** Nombre del dueño o administrador del negocio.
2. **Correo Electrónico:** Correo para iniciar sesión en el POS.
3. **Contraseña y Confirmación:** Clave de acceso (mínimo 8 caracteres).
4. Al enviar el formulario:
   - El instalador creará el usuario y le asignará el Rol **Admin** con permisos totales.
   - Generará el archivo de bloqueo `storage/installed` para proteger la instalación.
   - Actualizará la variable `APP_INSTALLED=true` en el archivo `.env`.

---

## 🎉 3. Finalización y Acceso Directo de Escritorio

Una vez completado el Paso 5, verás la pantalla de **¡Instalación Completada con Éxito!**:

1. **Acceso al Sistema:** Haz clic en **"Ir al Panel de Control / POS"** para iniciar sesión.
2. **Crear Acceso Directo de Escritorio:**
   - La pantalla final incluye una opción para descargar un script ejecutable que crea automáticamente un **Acceso Directo en el Escritorio de Windows**.
   - Este acceso abre **JSPOS Sales** en **Modo App de Google Chrome** (pantalla completa sin barra de navegación), convirtiendo la computadora en una terminal de punto de venta profesional.

---

## 🛠️ 4. Tareas Pos-Instalación Recomendadas

Una vez finalizado el asistente visual:

1. **Crear el enlace de imágenes (`storage:link`):**
   - Abre la consola en `C:\laragon\www\jspos-sales` y ejecuta:
     ```cmd
     php artisan storage:link
     ```
2. **Instalar los Servicios en Segundo Plano:**
   - Haz clic derecho sobre `instalar_servicios.bat` en la raíz del proyecto y selecciona **"Ejecutar como Administrador"**. Esto dejará corriendo los servicios de WhatsApp Web, colas de tareas y respaldos automatizados mediante NSSM.

---

¡**Listo!** El sistema **JSPOS Sales** está 100% operativo y configurado a través del instalador web visual. 🚀
