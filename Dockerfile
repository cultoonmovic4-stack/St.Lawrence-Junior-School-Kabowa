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

# Run lightweight, lightning-fast PHP server with dynamic PORT binding
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80}"]
