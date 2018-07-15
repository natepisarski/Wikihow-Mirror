#!/bin/bash

. /opt/wikihow/scripts/wikihow.sh

log=/data/spellcheck/log.txt
cd $wiki/maintenance/spellcheck

if [ "`ps auxww | grep \"spellchecker.php $1\" |grep -c -v grep`" = "0" ]; then
	/usr/local/bin/php spellchecker.php $1 >> $log
fi
