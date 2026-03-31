# syntax=docker/dockerfile:1.7

FROM node:20-alpine AS assets
WORKDIR /app

COPY src/package.json src/package-lock.json ./
RUN npm ci

COPY src/resources ./resources
COPY src/public ./public
COPY src/vite.config.js ./
COPY src/postcss.config.js ./
COPY src/tailwind.config.js ./

RUN npm run build


FROM php:8.3-cli-alpine AS vendor
WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd \
    mbstring \
    zip

COPY src/composer.json src/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


FROM php:8.3-cli-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    linux-headers \
    nodejs \
    npm \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    bcmath \
    gd \
    intl \
    mbstring \
    pcntl \
    pdo_mysql \
    sockets \
    zip \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps

COPY src/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_RUNTIME_ROLE=web
ENV PORT=8080

EXPOSE 8080

ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
CMD ["web"]
