const express = require('express');
const cors = require('cors');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const multer = require('multer');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());

// Setup file upload for attachments (PDFs)
const upload = multer({ dest: 'uploads/' });

// WhatsApp Client Initialization
const client = new Client({
    authStrategy: new LocalAuth({ dataPath: 'sessions' }),
    puppeteer: {
        headless: false,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ]
    }
});

const qrcodeLib = require('qrcode');

let isReady = false;
let currentQrBase64 = null;
let connectionStatus = 'DISCONNECTED';

client.on('qr', async (qr) => {
    console.log('----------------------------------------------------');
    console.log('Escanea este código QR con el WhatsApp de la Tienda');
    console.log('----------------------------------------------------');
    qrcode.generate(qr, { small: true });

    try {
        currentQrBase64 = await qrcodeLib.toDataURL(qr);
        connectionStatus = 'QR_READY';
    } catch (err) {
        console.error('Error generating base64 QR:', err);
    }
});

client.on('ready', () => {
    console.log('✅ Cliente de WhatsApp está LISTO y CONECTADO!');
    isReady = true;
    currentQrBase64 = null;
    connectionStatus = 'CONNECTED';
});

client.on('authenticated', () => {
    console.log('✅ Autenticación exitosa!');
    connectionStatus = 'AUTHENTICATED';
});

client.on('auth_failure', msg => {
    console.error('❌ Error de autenticación:', msg);
    connectionStatus = 'AUTH_FAILED';
    currentQrBase64 = null;
});

client.on('disconnected', (reason) => {
    console.log('❌ Cliente desconectado:', reason);
    isReady = false;
    currentQrBase64 = null;
    connectionStatus = 'DISCONNECTED';
    console.log('Reiniciando cliente...');
    client.initialize();
});

// Initialize WhatsApp client
console.log('Inicializando WhatsApp...');
client.initialize();

// API ENDPOINTS //

// Status check
app.get('/status', (req, res) => {
    res.json({
        success: true,
        isReady: isReady,
        status: connectionStatus,
        message: isReady ? 'WhatsApp está conectado' : 'WhatsApp no está conectado'
    });
});

// Get QR Code
app.get('/qr', (req, res) => {
    if (isReady) {
        return res.json({ success: true, isReady: true, status: connectionStatus, message: 'Client is already connected.' });
    }

    if (currentQrBase64) {
        return res.json({ success: true, isReady: false, status: connectionStatus, qr: currentQrBase64 });
    }

    res.json({ success: false, isReady: false, status: connectionStatus, message: 'QR no está listo todavía, por favor espera.' });
});

// Logout User
app.post('/logout', async (req, res) => {
    try {
        await client.logout();
        isReady = false;
        currentQrBase64 = null;
        connectionStatus = 'DISCONNECTED';
        res.json({ success: true, message: 'Se ha cerrado la sesión exitosamente.' });
    } catch (error) {
        console.error('Error cerrando sesión:', error);
        res.status(500).json({ success: false, error: 'No se pudo cerrar la sesión.' });
    }
});

// Send message
app.post('/send', upload.single('attachment'), async (req, res) => {
    if (!isReady) {
        return res.status(503).json({ success: false, error: 'El cliente de WhatsApp no está listo todavía.' });
    }

    try {
        const { phone, message } = req.body;
        const file = req.file;

        if (!phone || !message) {
            return res.status(400).json({ success: false, error: 'Faltan parámetros: phone, message' });
        }

        // Format phone to WhatsApp jid (e.g., 573001234567@c.us)
        const formattedPhone = phone.replace(/[^0-9]/g, '') + '@c.us';

        let response;
        if (file) {
            // Read file as base64 to completely avoid extensionless inference issues
            const mediaData = fs.readFileSync(file.path).toString('base64');
            const customFileName = req.body.filename || file.originalname || 'Documento.pdf';
            const mimetype = file.mimetype && file.mimetype !== 'application/octet-stream' ? file.mimetype : 'application/pdf';

            const media = new MessageMedia(mimetype, mediaData, customFileName);

            // Enviar caption + document + specify sendMediaAsDocument if it's pdf
            response = await client.sendMessage(formattedPhone, media, {
                caption: message,
                sendMediaAsDocument: true
            });

            // Clean up uploaded file
            fs.unlinkSync(file.path);
        } else {
            // Send text only
            response = await client.sendMessage(formattedPhone, message);
        }

        res.json({ success: true, response: response.id._serialized });
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        res.status(500).json({ success: false, error: error.message || 'Error interno del servidor' });
    }
});

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`🚀 API de WhatsApp corriendo en http://localhost:${PORT}`);
});
