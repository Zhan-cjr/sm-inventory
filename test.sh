#!/bin/bash
if [ $# -gt 0 ]; then
    echo "Args: $@"
    exec "$@"
fi
