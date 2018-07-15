SELECT 
cf_users.id,
cf_users.wh_user_id, 
cf_users.username, 
(CASE WHEN cf_users.is_established <> 0 THEN 'yes' ELSE 'no' END) AS is_established, 
(CASE WHEN cf_users.disabled <> 0 THEN 'yes' ELSE 'no' END) AS is_disabled, 
DATE_FORMAT(cf_users.updated_at, '{{date_format}}') AS last_seen,

cat.title AS category,
(
	SELECT COUNT(id) FROM cf_user_articles 
	WHERE cf_users.id = user_id AND complete = 1
) AS completed_tasks,
(
	SELECT GROUP_CONCAT(roles.title SEPARATOR ', ') AS roles 
	FROM cf_user_roles
	LEFT JOIN cf_roles roles ON role_id = roles.id
	where cf_user_roles.user_id = cf_users.id
) AS roles

FROM cf_users
LEFT JOIN cf_categories cat ON cf_users.category_id = cat.id
