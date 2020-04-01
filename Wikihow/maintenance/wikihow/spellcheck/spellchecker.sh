#!/bin/bash

. /opt/wikihow/scripts/wikihow.sh

log=/data/spellcheck/log.txt
dir="$(dirname $log)"
[[ ! -d "$dir" ]] && mkdir $dir

cd $wiki/maintenance/wikihow/spellcheck

if [ "`ps auxww | grep \"spellchecker.php.*$1\" |grep -c -v grep`" = "0" ]; then
	(whrun -- spellchecker.php --$1) &>> $log
fi
