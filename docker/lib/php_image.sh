#!/usr/bin/env bash

if [ "$USE_PHP5" = "1" ]; then
    export RESOLVED_PHP_IMAGE=${PHP5_IMAGE}
else
    export RESOLVED_PHP_IMAGE=${PHP7_IMAGE}
fi
