#!/usr/bin/env bash

export DOCKER_REGISTRY_HOST=registry.hub.docker.com

export PHP_OWNER=chekote
export PHP_REPO=php
export PHP5_TAG=5.6.40.b-behat3.4
export PHP7_TAG=7.2.16-behat3.4

export PHP5_IMAGE=${DOCKER_REGISTRY_HOST}/${PHP_OWNER}/${PHP_REPO}:${PHP5_TAG}
export PHP7_IMAGE=${DOCKER_REGISTRY_HOST}/${PHP_OWNER}/${PHP_REPO}:${PHP7_TAG}

export SED_ACCOUNT=library
export SED_REPO=alpine
export SED_TAG=3.11.3
export SED_IMAGE=${DOCKER_REGISTRY_HOST}/${SED_ACCOUNT}/${SED_REPO}:${SED_TAG}
