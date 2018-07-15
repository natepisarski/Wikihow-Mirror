/*
 * IMPORTANT: remember to remove the LOCAL keyword on live DB host
 * and preferably use absolute path to CSV file.
 */

LOAD DATA LOCAL INFILE 'kbwhitelist.csv'
    INTO TABLE `knowledgebox_articles`
    FIELDS TERMINATED BY ','
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (@dummy,@kbw_aid,@dummy,@dummy,@kbw_topic,@kbw_phrase)
    set `kba_aid`=@kbw_aid,`kba_topic`=@kbw_topic,`kba_phrase`=@kbw_phrase;
