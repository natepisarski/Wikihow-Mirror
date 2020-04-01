#!/bin/bash

[ "`pwd | grep -c maintenance`" != 1 ] && echo "must be in maintenance dir" && exit

echo "SELECT page_id FROM page WHERE page_title LIKE '%.php%';" |mysql -N wikidb_112 > delete-ids.txt
php deleteBatch.php -d delete-ids.txt

