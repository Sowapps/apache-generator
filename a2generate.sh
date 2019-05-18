#!/bin/bash

cd "$(dirname "$(readlink -f "$0")")"

php -f generate-apache2-configuration.php -- "$@"
