# 🚀 GUÍA DE DEPLOYMENT - Sistema Laravel Imaginatics

## 📋 Resumen

Sistema de gestión de clientes y pagos desarrollado en Laravel 12 con Blade + Alpine.js.

**Fecha de desarrollo**: Diciembre 2025
**Ambiente de desarrollo**: macOS con Herd (PHP 8.4, MySQL 8.0)
**Sistema**: Imaginatics Perú SAC - Sistema de Gestión Mejorado

---

## 🎯 Características Implementadas

### Módulos Principales:
1. **Autenticación** - Login con primera vez
2. **Clientes** - CRUD completo
3. **Servicios Contratados** - Gestión con suspensión/reactivación
4. **Consulta RUC** - Integración con Factiliza API
5. **Pagos Pendientes** - Dashboard con métricas
6. **Registro de Pagos** - Multi-servicio con renovación automática
7. **Envíos WhatsApp** - Sistema de cola (pendiente migración)

---

## 📁 Estructura del Proyecto

```
pagos-imaginatics-laravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── ClienteController.php
│   │   ├── DashboardController.php
│   │   ├── PagoController.php
│   │   ├── PagosPendientesController.php
│   │   ├── RucController.php
│   │   └── ServicioContratadoController.php
│   └── Models/
│       ├── Cliente.php
│       ├── ConsultaRuc.php
│       ├── HistorialPago.php
│       ├── ServicioContratado.php
│       ├── CatalogoServicio.php
│       └── Usuario.php
├── resources/
│   └── views/
│       ├── auth/
│       ├── clientes/
│       ├── servicios/
│       ├── pagos/
│       ├── pagos-pendientes/
│       └── layouts/app.blade.php
├── routes/web.php
└── .github/workflows/deploy.yml
```

---

## 🗄️ Base de Datos

### Base de Datos: `imaginatics_ruc`

**IMPORTANTE**: El sistema Laravel usa la **misma base de datos** que el monolítico, pero se despliega en **diferente carpeta**.

### Tablas Utilizadas:
- `usuarios` - Sistema de autenticación
- `clientes` - Información de clientes
- `servicios_contratados` - Servicios contratados por cliente
- `catalogo_servicios` - Catálogo de servicios disponibles
- `historial_pagos` - Registro de pagos con servicios_pagados (JSON)
- `consultas_ruc` - Cache de consultas RUC (24 horas)
- `envios_whatsapp` - Registro de envíos WhatsApp
- `sesiones_envio` - Sesiones de envío masivo
- `cola_envios` - Cola de trabajos de envío

---

## ⚙️ CONFIGURACIÓN DE PRODUCCIÓN

### 1. **Estructura de Carpetas en Servidor**

```bash
/var/www/
├── pagos_imaginatics/           # Sistema monolítico (PHP vanilla)
│   ├── index.php
│   ├── api/
│   └── js/
│
└── pagos-imaginatics-laravel/   # Sistema Laravel (NUEVO)
    ├── app/
    ├── public/                  # Document root para Nginx/Apache
    ├── storage/
    ├── bootstrap/cache/
    └── .env
```

### 2. **Archivo .env en Producción**

Crear `/var/www/pagos-imaginatics-laravel/.env`:

```env
APP_NAME="Imaginatics Pagos"
APP_ENV=production
APP_KEY=base64:TU_KEY_GENERADA_AQUI
APP_DEBUG=false
APP_URL=https://tu-dominio.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imaginatics_ruc
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password_segura

SESSION_DRIVER=database
SESSION_LIFETIME=120

# API Factiliza (para consulta RUC)
FACTILIZA_API_TOKEN=tu_token_factiliza

# API WhatsApp (para envío de órdenes)
WHATSAPP_API_URL=https://api.whatsapp.com/send
WHATSAPP_API_TOKEN=tu_token_whatsapp
```

**Generar APP_KEY:**
```bash
php artisan key:generate
```

### 3. **Configuración Nginx**

Crear `/etc/nginx/sites-available/imaginatics-laravel`:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/pagos-imaginatics-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Activar sitio:**
```bash
sudo ln -s /etc/nginx/sites-available/imaginatics-laravel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 4. **Permisos**

```bash
cd /var/www/pagos-imaginatics-laravel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 🔐 SECRETS DE GITHUB

Ir a tu repositorio → Settings → Secrets and variables → Actions → New repository secret

### Secrets Requeridos:

1. **SSH_PRIVATE_KEY**
   - Tu clave privada SSH para conectar al servidor
   ```bash
   cat ~/.ssh/id_rsa  # Copiar todo el contenido
   ```

2. **SSH_USER**
   - Usuario SSH del servidor (ejemplo: `root` o `ubuntu`)

3. **SERVER_HOST**
   - IP o dominio del servidor (ejemplo: `123.456.789.0` o `servidor.tudominio.com`)

4. **LARAVEL_PROJECT_PATH**
   - Ruta completa en servidor: `/var/www/pagos-imaginatics-laravel`

---

## 🚀 PROCESO DE DEPLOYMENT

### Deployment Automático (Push a master)

1. Haces push a branch `master`:
   ```bash
   git add .
   git commit -m "feat: nueva funcionalidad"
   git push origin master
   ```

2. GitHub Actions automáticamente:
   - ✅ Ejecuta tests
   - ✅ Compila assets con Vite
   - ✅ Se conecta al servidor por SSH
   - ✅ Hace pull del código
   - ✅ Instala dependencias (composer + npm)
   - ✅ Ejecuta migraciones
   - ✅ Limpia cache de Laravel
   - ✅ Configura permisos
   - ✅ Reinicia servicios

### Deployment Manual

Desde GitHub:
- Actions → Deploy Laravel to Production → Run workflow → Run workflow

---

## 🔄 DIFERENCIAS CON EL MONOLÍTICO

| Aspecto | Monolítico | Laravel |
|---------|-----------|---------|
| Ubicación | `/var/www/pagos_imaginatics` | `/var/www/pagos-imaginatics-laravel` |
| Framework | PHP Vanilla | Laravel 12 |
| Frontend | JavaScript Vanilla | Blade + Alpine.js |
| Build | No build | Vite (npm run build) |
| Migraciones | Scripts bash propios | Laravel migrations |
| Cache | No cache | Config/Route/View cache |
| Assets | Directos | Compilados en public/build |
| Base de datos | `imaginatics_ruc` | `imaginatics_ruc` (misma) |

---

## ⚠️ PROBLEMAS COMUNES Y SOLUCIONES

### 1. Error 500 después de deploy

**Problema**: Permisos incorrectos

**Solución**:
```bash
cd /var/www/pagos-imaginatics-laravel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
php artisan cache:clear
```

### 2. Assets no cargan (404)

**Problema**: Vite no compiló correctamente

**Solución**:
```bash
npm run build
php artisan view:clear
```

### 3. Error de base de datos

**Problema**: Credenciales incorrectas en .env

**Solución**:
```bash
nano .env  # Verificar DB_* variables
php artisan config:clear
php artisan migrate:status
```

### 4. Sesión no funciona

**Problema**: Tabla sessions no existe

**Solución**:
```bash
php artisan session:table
php artisan migrate
```

---

## 📊 MONITOREO

### Ver logs en producción:
```bash
tail -f /var/www/pagos-imaginatics-laravel/storage/logs/laravel.log
```

### Ver estado de servicios:
```bash
sudo systemctl status php8.4-fpm
sudo systemctl status nginx
```

### Verificar conexión a base de datos:
```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 🔧 COMANDOS ÚTILES EN PRODUCCIÓN

```bash
# Limpiar todo el cache
php artisan optimize:clear

# Regenerar cache optimizado
php artisan optimize

# Ver rutas
php artisan route:list

# Ejecutar migraciones
php artisan migrate --force

# Rollback migraciones
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status
```

---

## 📞 SOPORTE

Si algo falla:
1. Revisar logs: `storage/logs/laravel.log`
2. Revisar logs de Nginx: `/var/log/nginx/error.log`
3. Verificar permisos: `ls -la storage`
4. Verificar .env: Credenciales de BD correctas

---

## ✅ CHECKLIST ANTES DE PRIMER DEPLOY

- [ ] Servidor con PHP 8.4 instalado
- [ ] Nginx/Apache configurado
- [ ] MySQL 8.0 disponible
- [ ] Base de datos `imaginatics_ruc` existe
- [ ] Usuario SSH configurado
- [ ] Secrets configurados en GitHub
- [ ] .env creado en servidor con credenciales correctas
- [ ] APP_KEY generada
- [ ] Permisos configurados (www-data)
- [ ] Token de Factiliza API configurado

---

**Fecha última actualización**: Diciembre 2025
