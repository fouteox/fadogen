#!/bin/sh
set -e

if [ -t 1 ]; then
    YELLOW="\033[33m"
    RED="\033[31m"
    GREEN="\033[32m"
    RESET="\033[0m"
else
    YELLOW=""
    RED=""
    GREEN=""
    RESET=""
fi
