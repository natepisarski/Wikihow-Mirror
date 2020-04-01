SELECT article_id FROM `cf_user_articles` 
WHERE `role_id` = {{role_id}} AND `complete` = 1 
AND `completed_at` BETWEEN '{{start}} 00:00:00' AND '{{end}} 23:59:59' 
ORDER BY `completed_at` DESC;