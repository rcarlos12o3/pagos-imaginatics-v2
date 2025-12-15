# 📬 Sistema de Cola de Envíos WhatsApp

Sistema inteligente de procesamiento de envíos de WhatsApp con comportamiento humano.

## 🎯 Características de Comportamiento Humano

### ✅ Delays Aleatorios
- **Entre imagen y texto**: 15-30 segundos aleatorios
- **Entre clientes**: 30-90 segundos aleatorios
- Evita patrones detectables por WhatsApp

### ⏰ Horario Laboral
- **Días**: Lunes a Viernes
- **Horario**: 8:00 AM - 6:00 PM (hora de Lima)
- No envía sábados, domingos ni fuera de horario

### 🛡️ Protecciones Anti-Spam
- **Límite**: 30 mensajes por hora máximo
- **Reintentos**: Máximo 3 intentos por mensaje
- **Backoff**: 30 minutos entre reintentos

### 🔒 Protecciones de Concurrencia
- **Cache Lock**: Solo una instancia puede ejecutarse a la vez
- **Database Lock**: `lockForUpdate()` en trabajos pendientes
- **Timeouts**: Sesiones abandonadas después de 2 horas

## 🚀 Uso del Comando

### Ejecución Manual (Desarrollo)

```bash
# Ejecutar normalmente (solo en horario laboral)
php artisan cola:procesar

# Forzar ejecución fuera de horario
php artisan cola:procesar --force

# Ver logs en tiempo real
php artisan cola:procesar --force --verbose
```

### Ejecución Automática (Producción)

#### Opción 1: Cron Job (Recomendado)

Agregar al crontab:

```bash
# Ejecutar cada 2 minutos (solo procesará si hay trabajos pendientes)
*/2 * * * * cd /ruta/a/proyecto && php artisan cola:procesar >> /dev/null 2>&1

# Con logging
*/2 * * * * cd /ruta/a/proyecto && php artisan cola:procesar >> storage/logs/cola.log 2>&1
```

#### Opción 2: Laravel Scheduler

En `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('cola:procesar')
        ->everyTwoMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
```

Luego agregar al crontab:

```bash
* * * * * cd /ruta/a/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

#### Opción 3: Supervisor (Para servidores Linux)

Crear `/etc/supervisor/conf.d/cola-imaginatics.conf`:

```ini
[program:cola-imaginatics]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/proyecto/artisan cola:procesar
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/ruta/a/proyecto/storage/logs/cola.log
stopwaitsecs=300
```

Luego:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cola-imaginatics:*
```

## 📊 Monitoreo

### Ver Estado de Sesiones

```sql
SELECT id, tipo_envio, total_clientes, procesados, exitosos, fallidos, estado
FROM sesiones_envio
WHERE fecha_creacion >= CURDATE()
ORDER BY id DESC;
```

### Ver Trabajos Pendientes

```sql
SELECT c.id, cl.razon_social, c.estado, c.intentos, c.error_detalle
FROM cola_envios c
JOIN clientes cl ON c.cliente_id = cl.id
WHERE c.estado = 'pendiente'
ORDER BY c.fecha_creacion ASC;
```

### Ver Contador de Mensajes por Hora

```bash
php artisan tinker
>>> Cache::get('whatsapp_mensajes_hora_' . now()->format('Y-m-d-H'))
```

## ⚙️ Configuración

### Variables de Entorno

Asegúrate de tener configurado en tu base de datos (tabla `configuracion`):

```sql
INSERT INTO configuracion (clave, valor) VALUES
('token_whatsapp', 'tu_token_aqui'),
('instancia_whatsapp', 'tu_instancia_aqui'),
('api_url_whatsapp', 'https://api.whatsapp.com/');
```

### Ajustar Comportamiento

En `/app/Console/Commands/ProcesarColaEnvios.php`:

```php
// Pausas más cortas (para testing)
const PAUSA_ENTRE_MENSAJES = [5, 10];
const PAUSA_ENTRE_CLIENTES = [10, 20];

// Aumentar límite de mensajes por hora
const MAX_MENSAJES_POR_HORA = 50;

// Cambiar horario laboral
const HORARIO_INICIO = 7; // 7 AM
const HORARIO_FIN = 20; // 8 PM
```

## 🔍 Debugging

### Ver Logs de Laravel

```bash
tail -f storage/logs/laravel.log
```

### Verificar Cache Lock

```bash
php artisan tinker
>>> Cache::has('procesar_cola_whatsapp')
```

### Limpiar Lock Manualmente (si se quedó trabado)

```bash
php artisan tinker
>>> Cache::forget('procesar_cola_whatsapp')
```

## 📈 Estimaciones de Tiempo

Con la configuración por defecto:

- **5 clientes**: ~5-8 minutos
- **10 clientes**: ~10-15 minutos
- **20 clientes**: ~20-30 minutos
- **30 clientes (límite/hora)**: ~30-45 minutos

## ⚠️ Notas Importantes

1. **No ejecutar múltiples instancias**: El sistema tiene protección pero evita ejecutar manualmente si ya hay un cron corriendo

2. **Respetar límites**: No modificar los delays muy agresivamente o WhatsApp podría banear la cuenta

3. **Backups de configuración**: Guarda tus tokens de WhatsApp en un lugar seguro

4. **Testing**: Usa `--force` para testing fuera de horario, pero NUNCA en producción masiva

5. **Monitoreo**: Revisa regularmente los logs para detectar errores de API de WhatsApp

## 🎭 Simulación de Comportamiento Real

El sistema simula un **humano enviando manualmente** los mensajes:

1. Abre WhatsApp Web
2. Selecciona cliente
3. Sube imagen (tarda 15-30s en cargarse)
4. Espera que cargue
5. Escribe mensaje
6. Envía
7. Espera 30-90s antes de ir al siguiente cliente
8. Repite

Esto hace que WhatsApp vea el tráfico como **normal** en lugar de automatizado.
