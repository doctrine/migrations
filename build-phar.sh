#!/usr/bin/env bash

composer install --no-dev --optimize-autoloader

mkdir build

wget https://github.com/box-project/box2/releases/download/2.6.0/box-2.6.0.phar -O box.phar

php box.phar build
