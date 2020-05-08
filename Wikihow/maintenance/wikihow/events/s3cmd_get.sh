#!/bin/bash

. /opt/wikihow/scripts/wikihow.sh

logpath="$1"
infile="$2"
outfile="$3"

error() { echo "$1"; exit 1; }

[[ $# -ne 3 ]]      && error "usage: $0 LOGPATH INFILE OUTFILE"
[[ ! -d $logpath ]] && error "error: log directory does not exist: '$logpath'"
[[ ! -s $infile ]]  && error "error: input file is empty: '$infile'"

rm -f $logpath/*.log 2> /dev/null
rm -f $logpath/s3get.stdout
rm -f $logpath/s3get.stderr

while read -r line; do
	$s3cmd get $line $logpath/ >>$logpath/s3get.stdout 2>>$logpath/s3get.stderr
done < $infile

cat $logpath/*.log > $outfile

[[ ! -s $outfile ]] && error "error: output file is empty ($outfile)"

exit 0
