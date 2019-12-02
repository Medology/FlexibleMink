#!/usr/bin/env bash

export PHP_OWNER=chekote
export PHP_REPO=php
export PHP5_TAG=5.6.40.b-behat3.4
export PHP7_TAG=7.2.16-behat3.4

export PHP5_IMAGE=${PHP_OWNER}/${PHP_REPO}:${PHP5_TAG}
export PHP7_IMAGE=${PHP_OWNER}/${PHP_REPO}:${PHP7_TAG}
