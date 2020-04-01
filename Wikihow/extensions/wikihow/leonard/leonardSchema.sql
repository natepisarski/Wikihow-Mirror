CREATE DATABASE IF NOT EXISTS leonard
  DEFAULT CHARACTER SET utf8;
USE leonard;

CREATE TABLE IF NOT EXISTS leo_topics (
  id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  seed  VARCHAR(255) NOT NULL,
  fetched_ts CHAR(14) NOT NULL DEFAULT '',
  source  CHAR(3) NOT NULL DEFAULT 'gad',
  title_grps_updated CHAR(1) NOT NULL DEFAULT 'N',
  UNIQUE INDEX seed_idx (seed),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS leo_keywords (
  id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  seed  VARCHAR(255) NOT NULL, -- a value from leo_topics
  keyword VARCHAR(255) NOT NULL,
  avg_month_searches INT(10) NULL,
  ip_rank INT(10) NOT NULL DEFAULT 0, -- normally position in the adwords csv which is sorted by relevance
  num_search_results INT(10) NULL,
  title_source CHAR(3) NULL DEFAULT 'yb',
  fetched_ts CHAR(14) NOT NULL DEFAULT '',
  status CHAR(1) NOT NULL DEFAULT 'A',
  KEY leo_keywords_status_idx (keyword, status, seed),
  UNIQUE KEY leo_keywords_topic_idx (keyword, seed),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS leo_titles (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  seed  VARCHAR(255) NOT NULL, -- a value from leo_topics
  keyword VARCHAR(255) NOT NULL, -- a value from leo_keywords
  position_in_results INT(10) NULL,
  original_title VARCHAR(255) NOT NULL,
  short_title VARCHAR(255) NOT NULL,
  site VARCHAR(255) NOT NULL,
  url VARCHAR(255) NOT NULL,
  fetched_ts CHAR(14) NOT NULL DEFAULT '',
  status CHAR(1) NOT NULL DEFAULT 'A',
  dup_grp_id INT(10) NULL DEFAULT -1,
  wh_title VARCHAR(255) NULL,
  wh_aid INT(10) NULL DEFAULT 0,
  KEY leop_title_short_title_idx (short_title),
  KEY leo_tital_kss_idx (keyword, status, seed),
  UNIQUE KEY leo_original_title_site_kwid (keyword, site, seed, original_title),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
