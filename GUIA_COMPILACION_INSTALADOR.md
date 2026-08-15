# 🛠️ Guía Definitiva de Compilación del Instalador 1-Click (`.exe`) con Inno Setup

Esta guía detalla los pasos sencillos para generar el archivo instalador automático **`Setup_JSPOS_Sales_v1.10.exe`** listo para entregar e instalar en computadoras de clientes finales.

---

## 📋 1. Requisitos Previos (En tu equipo de desarrollo)

1. **Descargar e Instalar Inno Setup Compiler:**
   - Descarga **Inno Setup 6** (Gratuito): [https://jrsoftware.org/isdl.php](https://jrsoftware.org/isdl.php)
   - Ejecuta e instala el programa en tu computadora con las opciones predeterminadas.

2. **Estructura de la carpeta del proyecto:**
   Verifica que la carpeta `installer/` exista dentro del proyecto `C:\laragon\www\jspos-sales`:
   ```text
   C:\laragon\www\jspos-sales\installer\
   ├── installer.iss           (Script de Inno Setup)
   ├── post_install.bat        (Script de configuración de IPs, Apache, Artisan y NSSM)
   ├── output/                 (Carpeta donde se generará el .exe final)
   └── tools/                  (Carpeta opcional para laragon-wamp.exe / ZeroTierOne.msi / Tailscale-setup.exe)
   ```

---

## ⚙️ 2. Paso a Paso para Compilar el Instalador `.exe`

### Método 1: Compilación en 1 Solo Clic (Recomendado)
1. Abre el Explorador de Archivos de Windows y navega hasta:
   `C:\laragon\www\jspos-sales\installer\`
2. Haz **doble clic** sobre el archivo **`installer.iss`**.
3. Se abrirá la interfaz gráfica de **Inno Setup Compiler**.
4. Haz clic en el botón **Compile** (ícono de triángulo verde ▶️ en la barra superior o presiona `Ctrl + F9`).
5. Inno Setup comenzará a empaquetar y comprimir todo el sistema en ultra-alta densidad.
6. Al finalizar (1 a 2 minutos), la ventana mostrará el mensaje *"Compile finished"*.

### Ubicación del Ejecutable Generado
El instalador final generado estará listo en la siguiente ruta:
👉 `C:\laragon\www\jspos-sales\installer\output\Setup_JSPOS_Sales_v1.10.exe`

---

## 🚀 3. ¿Qué hace el instalador cuando se ejecuta en la PC del Cliente?

Al llevar el archivo `Setup_JSPOS_Sales_v1.10.exe` a la computadora del cliente y hacer doble clic:

1. **Descomprime el sistema:** Copia el proyecto a `C:\laragon\www\jspos-sales`.
2. **VPN Opcional:** Si se incluyó en la carpeta `installer/tools/`, instala silenciosamente ZeroTier / Tailscale.
3. **Detección Automática de IPs:** Detecta la IP Local (`SITE3`), IP ZeroTier (`SITE4`) e IP Tailscale (`SITE5`).
4. **Configuración de Apache VirtualHost:** Genera automáticamente el archivo `auto.jspos-sales.test.conf` en Laragon para que el sistema responda de inmediato por `http://jspos-sales.test` y por cualquiera de las 3 direcciones IP en el puerto 80.
5. **Comandos Laravel en Segundo Plano:** Genera `.env`, clave de aplicación `key:generate`, `storage:link`, ejecuta las migraciones (`migrate --seed`) y limpia la caché.
6. **Servicios de Windows (NSSM):** Registra e inicia invisiblemente los 3 servicios del sistema:
   - **`JSPOS_WhatsApp_API`**
   - **`JSPOS_Queue_Worker`**
   - **`JSPOS_Scheduler`**
7. **Acceso Directo:** Crea el acceso directo con el logo oficial en el Escritorio del cliente.
8. **Asistente de Licencia:** Abre automáticamente el navegador web en `http://jspos-sales.test/install/step4` para registrar la máquina en tu Servidor de Licencias.

---

¡**Felicidades!** Ya dispones de la solución completa de despliegue profesional automatizado para **JSPOS Sales**. 🚀
