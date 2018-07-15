#!/bin/bash
. /opt/wikihow/scripts/wikihow.sh
cd /opt/wkh/daikon/prod/extensions/wikihow/ContentPortal/tasks/
whrun -- Nightly.php --env=${1:-production} --cronjob=true --method=backupDb
whrun -- Nightly.php --env=${1:-production} --cronjob=true --method=createDump
whrun -- Nightly.php --env=${1:-production} --cronjob=true --method=syncArticles