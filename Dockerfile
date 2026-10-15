FROM php:8.2-apache

# Install dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip opcache \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Allow .htaccess overrides in /var/www/html
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create required upload directories if not exist and set permissions
RUN mkdir -p /var/www/html/backend/uploads/admissions \
    /var/www/html/backend/uploads/gallery \
    /var/www/html/backend/uploads/library \
    /var/www/html/backend/uploads/profiles \
    /var/www/html/backend/uploads/teachers \
    /var/www/html/backend/uploads/videos \
    /var/www/html/backend/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/backend/uploads /var/www/html/backend/cache \
    && chmod +x /var/www/html/docker-entrypoint.sh

# Fix CRLF to LF in entrypoint script for Linux containers
RUN sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
