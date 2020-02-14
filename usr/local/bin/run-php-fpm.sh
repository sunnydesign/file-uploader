#!/bin/bash

env | perl -pe 's/(.+?)=(.*)/env[\1]=\$\1/' > /etc/php/7.2/fpm/pool.d/env.env

/usr/sbin/php-fpm7.2 -F -y /etc/php/7.2/fpm/php-fpm.conf

cd /var/www/api.kubia.com/
php ./migrate.php