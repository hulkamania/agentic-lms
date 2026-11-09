FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    git \
    curl \
    sudo \
    libonig-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip \
    gd \
    intl \
    xml \
    curl \
    mbstring \
    soap \
    exif \
    opcache

# Enable Apache mods
RUN a2enmod rewrite ssl headers

# Install Moodle 4.4.4 (stable)
ENV MOODLE_VERSION=404
RUN rm -rf /var/www/html/* \
    && curl -L https://github.com/moodle/moodle/archive/refs/tags/v4.4.4.tar.gz -o /tmp/moodle.tar.gz \
    && tar -xzf /tmp/moodle.tar.gz -C /tmp \
    && mv /tmp/moodle-4.4.4/* /var/www/html/ \
    && rm -rf /tmp/moodle.tar.gz /tmp/moodle-4.4.4

# Fix permissions
RUN mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod -R 755 /var/www/html

# PHP settings
RUN echo 'upload_max_filesize = 128M' > /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'post_max_size = 128M' >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'max_execution_time = 600' >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'opcache.enable = 1' >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'opcache.memory_consumption = 256' >> /usr/local/etc/php/conf.d/moodle.ini \
    && echo 'opcache.max_accelerated_files = 10000' >> /usr/local/etc/php/conf.d/moodle.ini

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www/html
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
