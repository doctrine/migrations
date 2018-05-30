#!/usr/bin/env bash

./download-box.sh

# lock PHP to minimum allowed version
composer config platform.php 7.1.0
composer update

php box.phar compile -vv

composer config --unset platform
