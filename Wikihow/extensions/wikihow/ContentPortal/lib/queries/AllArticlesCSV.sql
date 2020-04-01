SELECT
id,
wh_article_id AS article_id,
wh_article_url AS article_url,
(CASE WHEN is_wrm = 0 THEN 'no' ELSE 'yes' END) AS is_wrm,

(SELECT doc_url FROM cf_documents WHERE `type` = "writing" AND article_id = cf_articles.id LIMIT 1) AS document_link,
DATE_FORMAT(created_at, '{{sql_date_format}}') AS created,
(SELECT username FROM cf_users WHERE id = assigned_id) AS assigned_to,
(SELECT present_tense FROM cf_roles WHERE id = state_id) AS state,

(SELECT title FROM cf_categories WHERE id = category_id) AS category,
-- writer name
(
	SELECT cf_users.username FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{write_id}} AND article_id = cf_articles.id
) AS writer_name,

-- editor name
(
	SELECT username FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{edit_id}} AND article_id = cf_articles.id LIMIT 1
) AS editor_name,
-- editor is trusted
(
	SELECT (CASE WHEN is_established = 0 THEN 'no' ELSE 'yes' END) AS trusted FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{edit_id}} AND article_id = cf_articles.id LIMIT 1
) AS editor_established,

-- reviewer name
(
	SELECT username FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{review_id}} AND article_id = cf_articles.id LIMIT 1
) AS reviewer_name,

-- review return count
(
	SELECT COUNT(*) FROM cf_notes
	WHERE role_id = {{edit_id}} AND `type` = 'info' AND article_id = cf_articles.id
) AS review_return_count,

-- Verifier ID
(
	SELECT vi_id FROM wikidb_112.verifier_info
	RIGHT JOIN cf_users u ON u.wh_user_id = vi_wh_id
	RIGHT JOIN cf_user_articles ua ON ua.user_id = u.id
	WHERE ua.role_id = 10 AND ua.article_id = cf_articles.id LIMIT 1
) AS verifier_id,

-- verifier name
(
	SELECT username FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{verify_id}} AND article_id = cf_articles.id LIMIT 1
) AS verifier_name,

-- proof reader name
(
	SELECT username FROM cf_user_articles
	LEFT JOIN cf_users ON cf_users.id = user_id WHERE role_id = {{proof_read_id}} AND article_id = cf_articles.id LIMIT 1
) AS proofreader_name,

-- date written
(
	SELECT DATE_FORMAT(completed_at, '{{sql_date_format}}') FROM cf_user_articles
	WHERE role_id = {{write_id}} AND article_id = cf_articles.id AND complete = 1 LIMIT 1
) AS date_written,

-- date proof read
(
	SELECT DATE_FORMAT(completed_at, '{{sql_date_format}}') FROM cf_user_articles
	WHERE role_id = {{proof_read_id}} AND article_id = cf_articles.id AND complete = 1 LIMIT 1
) AS date_proof_read,

-- date edited
(
	SELECT DATE_FORMAT(completed_at, '{{sql_date_format}}') FROM cf_user_articles
	WHERE role_id = {{edit_id}} AND article_id = cf_articles.id AND complete = 1 LIMIT 1
) AS date_edited,

-- date reviewed
(
	SELECT DATE_FORMAT(completed_at, '{{sql_date_format}}') FROM cf_user_articles
	WHERE role_id = {{review_id}} AND article_id = cf_articles.id AND complete = 1 LIMIT 1
) AS date_reviewed,

-- date verified
(
	SELECT DATE_FORMAT(completed_at, '{{sql_date_format}}') FROM cf_user_articles
	WHERE role_id = {{verify_id}} AND article_id = cf_articles.id AND complete = 1 LIMIT 1
) AS date_verified

FROM cf_articles
{{conditions}}
GROUP BY cf_articles.id;
