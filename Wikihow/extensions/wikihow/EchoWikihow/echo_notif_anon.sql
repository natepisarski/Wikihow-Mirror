-- allow anonymous users to use echo, DB changes by SC and RS on May 13, 2014
alter table echo_notification add column notification_anon_ip varchar(15) not null default '';                  
alter table echo_notification add index (notification_anon_ip);
