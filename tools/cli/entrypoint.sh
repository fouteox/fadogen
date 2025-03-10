#!/bin/sh
set -e

# Si la commande commence par une option, ajoutez php artisan devant
if [ "${1#-}" != "$1" ]; then
    set -- php artisan "$@"
fi

# Si la commande est artisan, exécutez-la
if [ "$1" = "artisan" ]; then
    shift
    set -- php artisan "$@"
fi

# Exécution de la commande
exec "$@"
