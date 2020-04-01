<?php
namespace ContentPortal;
use __;
use Title;
use mnshankar\CSV\CSV;

class ArticleCSV extends ExportCSV {

	static $csvFields = [
		"id",
		"article_id",
		"article_url",
		"is_wrm",
		"document_link",
		"created",
		"assigned_to",
		"state",
		"category",

		"writer_name",
		"proofreader_name",
		"editor_name",
		"reviewer_name",
		"review_return_count",
		"verifier_id",
		"verifier_name",

		"editor_established",
		"date_written",
		"date_proof_read",
		"date_edited",
		"date_reviewed",
		"date_verified",
	];

	static $model = 'ContentPortal\Article';

	static function getVars() {
		return [
			'write_id'        => Role::write()->id,
			'proof_read_id'   => Role::proofRead()->id,
			'edit_id'         => Role::edit()->id,
			'review_id'       => Role::review()->id,
			'verify_id'       => Role::verify()->id,
			'date_format'     => self::$dateFormat,
			'sql_date_format' => self::$sqlDateFormat
		];
	}

	static function dumpToFile() {
		$articles = self::getAll(self::$csvFields);
		file_put_contents(
			self::dumpPath(),
			(new CSV)->fromArray($articles)->toString()
		);
	}

	static function dumpPath() {
		return Config::getInstance()->dumpDir . date("j-n-y") . '.csv';
	}

	static function byRole($key, $fields) {
		$role = Role::find_by_key($key);
		return self::getAll($fields, "WHERE cf_articles.state_id = {$role->id}");
	}

	static function byUrls($inputUrls, $fields) {

		$urls = __::chain($inputUrls)->reject(function ($url) {
			return trim($url) == '';
		})->map(function ($url) {
			$article = new Article(['title' => $url, 'is_wrm' => true]);
			$article->is_valid();
			$existing = Article::find_by_title($article->title);
			return $existing ? $existing->id: $url;
		})->value();


		$ids = implode(',', __::filter($urls, function ($url) {
			return is_numeric($url);
		}));

		$result = empty($ids) ? [] : self::getAll($fields, "WHERE cf_articles.id IN ($ids)");

		if (count($result) < count($urls)) {
			return __::map($urls, function ($url) use ($result, $fields){
				if (is_string($url)) {
					$na = [];
					__::each($fields, function ($field) use (&$na){ $na[$field] = "N/A"; });
					$field = __::includ($fields, 'article_url') ? 'article_url' : $fields[0];
					$na[$field] = $url;
					return $na;
				}
				return __::find($result, function ($row) use ($url) {
					return $row['id'] == $url;
				});
			});

		} else {
			return $result;
		}
	}

	static function getAll($fields, $conditions=null) {
		$vars = self::getVars();
		$vars['conditions'] = $conditions;
		$result = self::getSql("AllArticlesCSV", $vars);

		$ret = [];
		foreach($result as $row) {
			$filtered = [];
			foreach($fields as $field) {
				$filtered[$field] = $row[$field];
			}
			array_push($ret, $filtered);
		}
		return $ret;
	}

	static function byRoleAndDateRange($key, $range, $fields) {
		$role  = Role::find_by_key($key);
		$segs  = explode(' - ', $range);
		$start = $segs[0];
		$end   = end($segs);

		$ids = __::pluck(self::getSql('ArticlesInRange', [
			'role_id' => $role->id,
			'start'   => $start,
			'end'     => $end
		]), 'article_id');

		$ids = implode(',', $ids);
		if (empty($ids)) exit; //short circuit if there are none

		return self::getAll($fields, "WHERE cf_articles.id IN ($ids)");
	}

}
