#!/usr/bin/env bash

if [ ! -f box.phar ]; then
    wget https://github.com/humbug/box/releases/download/3.0.0-alpha.5/box.phar -O box.phar
fi
