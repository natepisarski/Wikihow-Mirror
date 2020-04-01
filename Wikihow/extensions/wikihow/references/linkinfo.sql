CREATE TABLE `link_info` (
`li_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`li_url` varchar(255) NOT NULL,
`li_title` varchar(255) NOT NULL,
`li_code` int(10) unsigned NOT NULL,
`li_date_checked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`li_user_checked` int(10) unsigned NOT NULL,
PRIMARY KEY (`li_id`),
KEY (`li_url`)
);

CREATE TABLE `externallinks_link_info` (
`eli_el_id` int(10) unsigned NOT NULL,
`eli_li_id` int(10) unsigned NOT NULL,
PRIMARY KEY (`eli_el_id`),
KEY (`eli_el_id`, `eli_li_id`)
);
