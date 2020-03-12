#!/usr/bin/env bash

set -eu

TTY='';
if [ -t 0 ] ; then
  TTY=t;
fi
