#!/usr/bin/env bash

set -eu

# colors
export RED='\033[0;31m'
export GREEN='\033[0;32m'
export NC='\033[0m'

# grep flavor
GREP='grep -cE' # FreeBSD (osx)

if [[ "$OSTYPE" == "linux-gnu" ]]; then
  GREP='grep -cP'
fi

# Feature files
FEATURE_FILES=$(echo "$@" | awk '/\.feature$/')

if [[ FEATURE_FILES != "" ]] ; then
  for FILE in $FEATURE_FILES
  do
    if [[ $($GREP "(I )?(have )?wait(ed)? (for )?(\d+) seconds?" $FILE) -gt 0 ]] ; then
      echo -e "${RED}You should remove all 'I wait for n seconds' steps in feature files before commit.${NC}"
      exit 1
    fi
  done
fi

exit 0
