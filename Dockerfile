FROM alpine:edge

MAINTAINER Abdul Hafidz A "aditans88@gmail.com"

# Install packages
RUN apk add --no-cache php7 php7-fpm php7-pear \
    php7-pdo php7-pdo_mysql php7-pdo_pgsql php7-pdo_sqlite php7-mysqli \
    php7-mbstring php7-tokenizer php7-xml php7-simplexml \
    php7-zip php7-opcache php7-iconv php7-intl php7-pcntl \
    php7-json php7-gd php7-ctype php7-phar \
    php7-redis php7-pecl-apcu \
    php7-memcached php7-pecl-igbinary \
    php7-exif php7-curl php7-bcmath \
    openssl-dev nginx supervisor curl tzdata iputils 

# Install composer
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Clear lib not used & clean apk cache
# RUN apk del build-base
RUN rm -f /var/cache/apk/*

# run service php-fpm    
RUN ln -s /usr/sbin/php-fpm7 /usr/sbin/php-fpm

# Configure nginx
COPY ./deploy/nginx/nginx.conf /etc/nginx/nginx.conf

# Remove default server definition
RUN rm /etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY ./deploy/php/jwt.so.ext /usr/lib/php7/modules/jwt.so
COPY ./deploy/php/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY ./deploy/php/php.ini /etc/php7/conf.d/custom.ini

# Configure supervisord
COPY ./deploy/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# COPY ./deploy/supervisor/conf.d/worker.conf /etc/supervisor/conf.d/worker.conf

# Setup document root
RUN mkdir -p /var/www/app

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/app && \
  chown -R nobody.nobody /run && \
  chown -R nobody.nobody /var/lib/nginx && \
  chown -R nobody.nobody /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Copy application
WORKDIR /var/www/app
COPY --chown=nobody . /var/www/app/

# If composer vendor not installed
# RUN cd /var/www/app && composer install --no-scripts --no-autoloader

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
