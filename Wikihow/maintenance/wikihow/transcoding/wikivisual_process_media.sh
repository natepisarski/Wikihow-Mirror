#!/bin/bash
#
# Runs Bambam, the Wikivisual bot that puts photos and videos on articles
#

. /opt/wikihow/scripts/wikihow.sh

this_dir="`dirname $0`"
log="$wikihow/log/wikivisual-processing.log"
echo "Init [$(date)]" >> $log 2>&1
staging_dir="$wikihow/wikivisual"
main_class="WikiVisualTranscoder"
[ ! -d $staging_dir ] && mkdir $staging_dir && chmod 777 $staging_dir

echo "Start [$(date)]"  >> $log 2>&1

# kill this if it's been running for longer than 3h -- this hangs occasionally
# when there are database interruptions
kill_older_than "php ${main_class}.php" "180"

# make sure this isn't already running
if [ "`ps auxww |grep ${main_class} |grep -c -v grep`" = "0" ]; then
	# check if an ID keeps getting retried (likely because of crash)
	# and permanently skip it
	skip_id=`find $staging_dir -mmin -30 -type d |grep -v "^$staging_dir$" |head -1 |sed 's/^.*\/\([0-9]*\)-.*$/\1/'`
	if [ "`echo $skip_id |egrep -c '^[0-9]*$'`" != "0" ]; then
		count=`ls -ld $staging_dir/$skip_id* |wc -l`
		if  [ "$count" -gt "3" ]; then
			params="--staging-dir=$staging_dir --exclude-article-id=$skip_id"
		fi
	fi

	CMD="${main_class}.php"
	cd "$this_dir"
	if tty -s; then
		whrun --user=apache $CMD $params 2>&1 | tee -a $log
	else
		whrun --user=apache $CMD $params >> $log 2>&1
	fi
else
	echo "Script is already running, hence skip this run." >> $log 2>&1

fi
echo "End [$(date)]" >> $log 2>&1

