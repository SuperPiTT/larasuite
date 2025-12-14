FROM php:8.4-cli-alpine

ARG UID=1000
ARG GID=1000

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    linux-headers \
    supervisor \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        intl \
        bcmath \
        opcache \
        pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Create user with same UID/GID as host user
RUN addgroup -g ${GID} larasuite \
    && adduser -u ${UID} -G larasuite -s /bin/sh -D larasuite

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor && chown -R larasuite:larasuite /var/log/supervisor

# Set working directory
WORKDIR /var/www/html

# Copy supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
