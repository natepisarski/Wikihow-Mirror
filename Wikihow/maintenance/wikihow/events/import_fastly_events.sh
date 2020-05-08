#!/bin/bash

# To be run by a cron job

. /opt/wikihow/scripts/wikihow.sh

log="$whlog/event_import.hourly.log"
whrun -- $wiki/maintenance/wikihow/events/importFastlyEvents.php >> $log 2>&1
