#!/bin/sh

_term() {
    echo "SIGTERM signal!";
    kill -TERM "$child" 2>/dev/null
}

trap _term SIGTERM

php /var/www/faro.phar swoole:server:start 0.0.0.0:8080 &
child=$!
wait "$child"
