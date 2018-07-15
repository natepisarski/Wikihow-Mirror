#!/bin/bash
#
# Runs a php script that will create a gif from an article with videos
#

. /opt/wikihow/scripts/wikihow.sh

this_dir="`dirname $0`"
log="$wikihow/log/wikivisual-gif-processing.log"
echo "Init [$(date)]" >> $log 2>&1
main_class="WikiVisualMakeGifs"

echo "Start [$(date)]"  >> $log 2>&1

# kill this if it's been running for longer than 30 minutes in case it's hanging for some reason
kill_older_than "php ${main_class}.php" "30"

# make sure this isn't already running
if [ "`ps auxww |grep ${main_class} |grep -c -v grep`" = "0" ]; then
	# check if an ID keeps getting retried (likely because of crash)
	# and permanently skip it
	CMD="${main_class}.php"
	cd "$this_dir"
	if tty -s; then
		whrun --user=apache $CMD 2>&1 | tee -a $log
	else
		whrun --user=apache $CMD >> $log 2>&1
	fi
else
	echo "Script is already running, hence skip this run." >> $log 2>&1

fi
echo "End [$(date)]" >> $log 2>&1

