#!/bin/bash
set -e

# Disable conflicting MPM modules that platforms like Railway may activate
a2dismod -f mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway provides PORT dynamically; default to 80 if not set
TARGET_PORT="${PORT:-80}"

# Configure Apache to listen on $TARGET_PORT
sed -i "s/Listen [0-9]*/Listen ${TARGET_PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${TARGET_PORT}>/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
