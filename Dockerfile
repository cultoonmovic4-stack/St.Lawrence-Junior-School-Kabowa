FROM php:8.2-cli

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure large upload limits in php.ini
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_file_uploads = 250" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

# Copy application code
COPY . /app

# Ensure upload and cache directories exist with full write access
RUN mkdir -p /app/backend/uploads/admissions \
    /app/backend/uploads/gallery \
    /app/backend/uploads/library \
    /app/backend/uploads/profiles \
    /app/backend/uploads/teachers \
    /app/backend/uploads/videos \
    /app/backend/cache \
    && chmod -R 777 /app/backend/uploads /app/backend/cache

EXPOSE 80

# Run lightweight, lightning-fast PHP server with dynamic PORT binding and upload limits
CMD ["sh", "-c", "php -d upload_max_filesize=50M -d post_max_size=100M -d memory_limit=512M -d max_file_uploads=250 -d max_execution_time=300 -S 0.0.0.0:${PORT:-80}"]
