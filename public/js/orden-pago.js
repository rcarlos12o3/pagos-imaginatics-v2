/**
 * Generación de imágenes de órdenes de pago
 */

const CONFIG_IMAGINATICS = {
    COLORES: {
        PRIMARIO: '#2563eb',
        SECUNDARIO: '#dc2626',
        FONDO_BLANCO: '#ffffff',
        TEXTO_PRINCIPAL: '#1f2937',
        TEXTO_SECUNDARIO: '#4b5563',
    },
    CUENTAS_BANCARIAS: [
        'BCP: 19393234096052',
        'SCOTIABANK: 940-0122553',
        'INTERBANK: 562-3108838683',
        'BBVA: 0011-0057-0294807188',
        'YAPE/PLIN: 989613295'
    ]
};

/**
 * Convertir fecha a texto legible
 * Acepta formato dd/mm/yyyy o yyyy-mm-dd
 */
function convertirFechaATexto(fecha) {
    const meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    let dia, mes, anio;

    // Si viene en formato dd/mm/yyyy
    if (fecha.includes('/')) {
        const partes = fecha.split('/');
        dia = parseInt(partes[0], 10);
        mes = meses[parseInt(partes[1], 10) - 1]; // Mes es 0-indexed
        anio = parseInt(partes[2], 10);
    } else {
        // Si viene en formato yyyy-mm-dd o es objeto Date
        const fechaObj = new Date(fecha);
        dia = fechaObj.getDate();
        mes = meses[fechaObj.getMonth()];
        anio = fechaObj.getFullYear();
    }

    return `${dia} de ${mes} del ${anio}`;
}

/**
 * Generar canvas de orden de pago
 */
async function generarCanvasOrdenPago(servicio) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = 915;
        canvas.height = 550;

        // Fondo blanco
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.FONDO_BLANCO;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Configurar fuentes
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';

        // Título principal
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.PRIMARIO;
        ctx.font = 'bold 28px Arial';
        ctx.fillText('IMAGINATICS PERU SAC', 50, 40);

        // Línea separadora
        ctx.strokeStyle = CONFIG_IMAGINATICS.COLORES.PRIMARIO;
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(50, 80);
        ctx.lineTo(865, 80);
        ctx.stroke();

        // Texto principal
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.TEXTO_PRINCIPAL;
        ctx.font = '24px Arial';
        ctx.fillText('Queremos recordarte que tiene 1', 50, 120);
        ctx.fillText('orden de pago que vence el dia', 50, 150);

        // Fecha destacada
        const fechaTexto = convertirFechaATexto(servicio.fecha_vencimiento_periodo_actual);
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.SECUNDARIO;
        ctx.font = 'bold 32px Arial';
        const fechaWidth = ctx.measureText(fechaTexto).width;
        const centerX = (canvas.width - fechaWidth) / 2;
        ctx.fillText(fechaTexto, centerX, 200);

        // Marco para la fecha
        ctx.strokeStyle = CONFIG_IMAGINATICS.COLORES.SECUNDARIO;
        ctx.lineWidth = 2;
        ctx.strokeRect(centerX - 20, 195, fechaWidth + 40, 45);

        // Información del cliente
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.TEXTO_SECUNDARIO;
        ctx.font = '18px Arial';
        ctx.fillText('Cliente: ' + servicio.empresa, 50, 270);
        ctx.fillText('RUC: ' + servicio.ruc, 50, 295);
        ctx.fillText('Monto a pagar: ' + servicio.moneda + ' ' + servicio.precio, 50, 320);

        // Cuentas bancarias
        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.PRIMARIO;
        ctx.font = 'bold 20px Arial';
        ctx.fillText('Realice su pago a las siguientes cuentas:', 50, 360);

        ctx.fillStyle = CONFIG_IMAGINATICS.COLORES.TEXTO_SECUNDARIO;
        ctx.font = '16px Arial';
        CONFIG_IMAGINATICS.CUENTAS_BANCARIAS.forEach((cuenta, index) => {
            ctx.fillText(cuenta, 50, 390 + (index * 25));
        });

        // Cargar imágenes (opcional)
        let imagenesRestantes = 2;
        function imagenCargada() {
            imagenesRestantes--;
            if (imagenesRestantes === 0) {
                resolve(canvas);
            }
        }

        // Logo (opcional - si no existe, continuar)
        const logo = new Image();
        logo.onload = function () {
            try {
                ctx.drawImage(logo, 720, 40, 145, 80);
            } catch (e) {
                console.warn('Error al dibujar logo:', e);
            }
            imagenCargada();
        };
        logo.onerror = imagenCargada;
        logo.src = '/logo.png';

        // Mascota (opcional - si no existe, continuar)
        const mascota = new Image();
        mascota.onload = function () {
            try {
                ctx.drawImage(mascota, 650, 270, 200, 200);
            } catch (e) {
                console.warn('Error al dibujar mascota:', e);
            }
            imagenCargada();
        };
        mascota.onerror = imagenCargada;
        mascota.src = '/mascota.png';

        // Timeout de seguridad (resolver después de 3 segundos sin importar si las imágenes cargaron)
        setTimeout(() => resolve(canvas), 3000);
    });
}

/**
 * Convertir canvas a base64
 * Usa JPEG con calidad 0.7 para reducir tamaño (igual que sistema monolítico)
 */
function canvasToBase64(canvas) {
    return canvas.toDataURL('image/jpeg', 0.7);
}
