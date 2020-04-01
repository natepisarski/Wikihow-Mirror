#!/bin/bash

. /opt/wikihow/scripts/wikihow.sh

this_dir="`dirname $0`"
log="$whlog/email_bounce_handler.log"
main_class="BounceHandler"

echo "Start [$(date)]"  >> $log 2>&1
# make sure this isn't already running
if [ "`ps auxww |grep ${main_class} |grep -c -v grep`" = "0" ]; then

	CMD="${main_class}.php"
	cd "$this_dir"
	if tty -s; then
		sudo -u apache $php $CMD 2 2>&1 | tee -a $log
	else
		sudo -u apache $php $CMD 2 >> $log 2>&1
	fi
else
	echo "Script is already running; skipping this run" >> $log 2>&1
	
fi
echo "End [$(date)]" >> $log 2>&1

