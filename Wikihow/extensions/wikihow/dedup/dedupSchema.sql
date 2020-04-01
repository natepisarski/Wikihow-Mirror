create table if not exists query_lookup_log(
	qll_query varchar(255),
	qll_result varchar(10),
	qll_timestamp varchar(14),
	qll_comment text
);
create table if not exists query_lookup(
	ql_query varchar(255),
	ql_pos int,
	ql_url varchar(255),
	ql_time_fetched datetime,
	index idx_query(ql_query),
	index idx_url(ql_url)
);
create table if not exists special_query(
	sq_query varchar(255) primary key,
	sq_import_date datetime
);
create table if not exists title_update_log(
	tul_title varchar(255),
	tul_lang varchar(2),
	tul_page_id int,
	tul_page_action varchar(2), /* 'd' or 'a', 'fd' fail delete, 'fa' fail add*/
	tul_timestamp varchar(14)
);
create table if not exists title_query(
	tq_page_id int NOT NULL,
	tq_lang varchar(2) NOT NULL,
	tq_title varchar(255) NOT NULL,
	tq_query varchar(255) NOT NULL,
	primary key(tq_page_id,tq_lang),
	index idx_title(tq_title),
	index idx_query(tq_query)
);
create table if not exists keyword_load_log(
	kll_title varchar(255),
	kll_source varchar(255),
	kll_keywords text,
	kll_timestamp varchar(14)
);
