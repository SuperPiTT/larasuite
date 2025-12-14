FROM php:8.4-fpm-alpine

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
    nodejs \
    npm \
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

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user with same UID/GID as host user
RUN addgroup -g ${GID} larasuite \
    && adduser -u ${UID} -G larasuite -s /bin/sh -D larasuite

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Set ownership
RUN chown -R larasuite:larasuite /var/www/html

USER larasuite

EXPOSE 9000

CMD ["php-fpm"]
