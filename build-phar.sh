#!/usr/bin/env bash

git pull origin master --tags

if [ -f composer.lock ]; then
  rm composer.lock
fi

composer install --no-dev --optimize-autoloader

mkdir -p build

if [ ! -f box.phar ]; then
    wget https://github.com/box-project/box2/releases/download/2.6.0/box-2.6.0.phar -O box.phar
fi

php box.phar build -vv
