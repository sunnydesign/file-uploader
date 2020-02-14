FROM ubuntu
LABEL MAINTAINER=am@quancy.com.sg
ENV TZ=Asia/Singapore

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
&&  apt-get -yqq update && apt-get -yqq upgrade && apt-get -yqq install \
    apt-utils \
    nginx \
    php-fpm php-bcmath php-curl php-pgsql \
    php-mbstring \
    supervisor jq \
    mc \
    composer \
    htop \
&& rm /etc/nginx/sites-enabled/default \
&& mkdir /run/php && chown www-data:www-data /run/php

COPY ./ /var/www/api.kubia.com/
COPY ./etc/ /etc/
COPY ./public_html/ /var/www/api.kubia.com/public_html/
COPY ./usr/local/bin/run-php-fpm.sh /usr/local/bin/

RUN mkdir /var/www/api.kubia.com/logs \
  && chmod 777 /var/www/api.kubia.com/storage \
  && chown -R www-data:www-data /var/www/api.kubia.com

# Allow to include custom php-fpm config, e.g. to set environment variables
RUN echo 'include=/etc/php/7.2/fpm/pool.d/*.env' >> /etc/php/7.2/fpm/php-fpm.conf \
  && chmod +x /usr/local/bin/run-php-fpm.sh

#################################
# Composer
#################################

RUN useradd composer -b /home/composer \
    && mkdir /home/composer \
    && chown composer:composer /home/composer \
    && echo "alias composer='composer'" >> /home/composer/.bashrc \
    && cd /var/www/api.kubia.com \
    && chown -R composer:composer /var/www/api.kubia.com \
    && su composer -c 'composer install' \
    && chown -R www-data:www-data /var/www/api.kubia.com

VOLUME ["/var/www/api.kubia.com/storage"]

ENTRYPOINT ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]