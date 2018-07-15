LOAD DATA LOCAL INFILE '/home/george/wikihow/prod/extensions/wikihow/KnowledgeBox/util/kbadd.csv'
    INTO TABLE `knowledgebox_articles`
    FIELDS TERMINATED BY ','
    LINES TERMINATED BY '\n'
    IGNORE 1 LINES
    (@kbw_aid,@kbw_topic,@kbw_phrase)
    set `kba_aid`=@kbw_aid,`kba_topic`=@kbw_topic,`kba_phrase`=@kbw_phrase;

