#!/bin/bash

. /opt/wikihow/scripts/wikihow.sh

if [[ $# -ne 1 ]]; then
    echo "usage: $0 YYYY-MM-DD"
    exit 1
fi

date="$1"
date "+%Y-%m-%d" -d "$date" > /dev/null 2>&1
if [[ $? -ne 0 ]]; then
	echo "error: malformed date '$date'"
    exit 1
fi

$s3cmd ls "s3://fastlyeventlog/en/${date}*" | tr -s ' ' | cut -d ' ' -f4 | sort
