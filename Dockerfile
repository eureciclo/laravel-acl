FROM php:7.2.34-fpm-alpine3.12
RUN apk add --no-cache openssl \
            bash
            # freetype-dev \
            # libjpeg-turbo-dev \
            # libpng-dev
# RUN docker-php-ext-install pdo pdo_mysql
# RUN docker-php-ext-configure gd --with-gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/
# RUN docker-php-ext-install -j$(nproc) gd
RUN curl -k -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --disable-tls true --filename=composer && \
    composer about
ENV export COMPOSER_MEMORY_LIMIT=-1