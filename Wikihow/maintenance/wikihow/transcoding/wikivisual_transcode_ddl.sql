    CREATE TABLE wikivisual_article_status ( 
        article_id INT(10) UNSIGNED NOT NULL, 
        status INT UNSIGNED not null default 0, 
        creator VARCHAR(32) NOT NULL default '', 
        reviewed TINYINT(3) UNSIGNED NOT NULL default 0, 
        processed VARCHAR(14) NOT NULL default '', 
        vid_processed VARCHAR(14) NOT NULL default '', 
        gif_processed VARCHAR(14) NOT NULL default '', 
        gif_processed_error VARCHAR(14) NOT NULL default '', 
        photo_processed VARCHAR(14) NOT NULL default '', 
        warning TEXT not null, 
        error TEXT not null, 
        article_url VARCHAR(255) NOT NULL default '', 
        retry TINYINT(3) UNSIGNED NOT NULL default 0, 
        vid_cnt INT(10) UNSIGNED NOT NULL default 0, 
        photo_cnt INT UNSIGNED NOT NULL default 0, 
        replaced INT(10) UNSIGNED NOT NULL default 0, 
        steps INT(10) UNSIGNED NOT NULL default 0,
        staging_dir VARCHAR(255) NOT NULL default '',
        incubation TINYINT(3) UNSIGNED NOT NULL default 0,
        PRIMARY KEY (article_id) 
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
        
    CREATE TABLE wikivisual_vid_names ( 
        filename VARCHAR(255) NOT NULL, 
        wikiname VARCHAR(255) NOT NULL 
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
    
    CREATE TABLE wikivisual_photo_names ( 
        filename VARCHAR(255) NOT NULL, 
        wikiname VARCHAR(255) NOT NULL 
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1; 
    
    CREATE TABLE wikivisual_vid_transcoding_status ( 
        article_id INT(10) UNSIGNED NOT NULL, 
        aws_job_id VARCHAR(32) NOT NULL default '', 
        aws_uri_in TEXT, 
        aws_uri_out TEXT, 
        aws_thumb_uri TEXT, 
        processed VARCHAR(14) NOT NULL default '', 
        status VARCHAR(32) NOT NULL default '', 
        status_msg TEXT NOT NULL, 
        PRIMARY KEY (aws_job_id), 
        KEY article_id (article_id) 
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1
