#!/bin/bash
set -e

# Asegurar permisos correctos para Laravel
if [ -d "/var/www/html" ]; then
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
fi

# Si es producción o staging, eliminar Xdebug antes de arrancar FPM
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
  rm -f /usr/local/etc/php/conf.d/*xdebug*.ini || true
  echo "[Entrypoint] Xdebug deshabilitado en $APP_ENV."
fi

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Ejecutar Supervisor en primer plano (para Horizon)
exec supervisord -c /etc/supervisor/supervisord.conf
