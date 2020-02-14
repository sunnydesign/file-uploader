#!/bin/bash

env | sed "s/\(.*\)=\(.*\)/env[\1]='\2'/" > /etc/php/7.2/fpm/pool.d/env.env

/usr/sbin/php-fpm7.2 -F -y /etc/php/7.2/fpm/php-fpm.conf