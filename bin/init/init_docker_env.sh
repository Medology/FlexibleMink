#!/usr/bin/env bash

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"

. "${ROOT}"/bin/lib/exitCheck.sh

echo "Checking .env file..."
if [ ! -e "$ROOT"/docker/.env ]; then
  echo "No docker/.env file found. Creating from .env.example..."
  cp "$ROOT"/docker/.env.example "$ROOT"/docker/.env
  exitCheck $?
  echo "NOTE: Please review the docker/.env file and configure any required parameters."
else
  echo "docker/.env file exists. Skipping create."
  echo "NOTE: Please review the docker/.env.example file and ensure all required parameters are present in docker/.env"
fi
