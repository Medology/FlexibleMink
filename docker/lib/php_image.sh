#!/usr/bin/env bash

if [ "$USE_PHP5" = "1" ]; then
    export RESOLVED_PHP_IMAGE=${PHP_OWNER}/${PHP_REPO}:${PHP5_TAG}
else
    export RESOLVED_PHP_IMAGE=${PHP_OWNER}/${PHP_REPO}:${PHP7_TAG}
fi
