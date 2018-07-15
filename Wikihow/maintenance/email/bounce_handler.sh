#!/bin/bash

this_dir="`dirname $0`"
log="/usr/local/wikihow/log/email_bounce_handler.log"
echo "Init [$(date)]" >> $log 2>&1
main_class="BounceHandler"

echo "Start [$(date)]"  >> $log 2>&1
# make sure this isn't already running
if [ "`ps auxww |grep ${main_class} |grep -c -v grep`" = "0" ]; then

	CMD="${main_class}.php"
	cd "$this_dir"
	if tty -s; then
		sudo -u apache /usr/local/bin/php $CMD 2 2>&1 | tee -a $log
	else
		sudo -u apache /usr/local/bin/php $CMD 2 >> $log 2>&1
	fi
else
	echo "Script is already running, hence skip this run." >> $log 2>&1
	
fi
echo "End [$(date)]" >> $log 2>&1

