#!/usr/bin/env bash

if [ ! -f box.phar ]; then
    wget https://github.com/box-project/box/releases/download/$(php -r 'echo PHP_VERSION_ID >= 70300 ? "3.11.0" : "3.9.1";')/box.phar -O box.phar
fi
