#!/usr/bin/env bash

if [ ! -f box.phar ]; then
    wget https://github.com/box-project/box/releases/download/3.16.0/box.phar -O box.phar
fi
