--this is an extra column added to the table to store the rating value:
--alter table rating_reason add ratr_rating tinyint(1) unsigned NOT NULL DEFAULT '0';

-- 15 sept 2014
--this is an extra constraint added to the table for filtering between article and sample ratings
--alter table rating_reason add index `ratr_type` (`ratr_type`);

-- 1 oct 2014
-- the rating reason now has a detail field which is a mediawiki message that the user chose on in a radio button
alter table rating_reason add `ratr_detail` varchar(255) DEFAULT NULL;

alter table rating add `rat_source` varchar(7) DEFAULT NULL;
alter table rating add `rat_detail` tinyint(1) unsigned NOT NULL DEFAULT '0';

alter table ratesample add `rats_source` varchar(7) DEFAULT NULL;
alter table ratesample add `rats_detail` tinyint(1) unsigned NOT NULL DEFAULT '0';

-- added an additional index to the table so we can group the rat_detail results
alter table rating add index (rat_page, rat_detail);
-- we used the pt-online-schema-change tool to do the alter on the live db:
-- pt-online-schema-change --execute --alter "add index (rat_page, rat_detail)" D=wikidb_en,t=rating --set-vars innodb_lock_wait_timeout=50 --progress=time,15 --print | tee ratingschemachange.out 2>&1
