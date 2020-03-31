#!/usr/bin/env bash

set -eu

if [ "${USE_PHP5:='false'}" == true ]; then
    export RESOLVED_PHP_IMAGE=${PHP5_IMAGE}
else
    export RESOLVED_PHP_IMAGE=${PHP7_IMAGE}
fi
