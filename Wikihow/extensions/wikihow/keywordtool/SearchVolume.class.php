<?php

class SearchVolume {
	const END_POINT = "https://api.keywordtool.io/v2/search/volume/google";
	const _API_KEY = "62ee869907ac04b64da71cc61cac6561abeaf519";
	const TABLE_NAME = "search_volume";

	static function hitAPI($keywords){
		$url = self::END_POINT;

		$curl=curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$data = ['apikey'=> self::_API_KEY, 'keyword' => $keywords,];
		$data_string = json_encode($data);

		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);

		$response=curl_exec($curl);
		curl_close($curl);

		$results = [];
		if (strlen($response)) {
			$response = json_decode($response);
			foreach ($keywords as $keyword) {
				$lowercasekeyword = strtolower($keyword);
				$results[$keyword] = is_null($response->results->{$lowercasekeyword}->volume)?0:$response->results->{$lowercasekeyword}->volume;
			}
		}

		return $results;
	}

	public static function updateValues($articleId, $volume) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(self::TABLE_NAME, ['sv_volume' => $volume], ['sv_page_id' => $articleId], __METHOD__);
	}

	public static function addNewTitle($articleId, $volume = -1) {
		$dbw = wfGetDB(DB_MASTER);
		$row = ['sv_page_id' => $articleId, 'sv_volume' => $volume];
		$dbw->upsert(self::TABLE_NAME, $row, [], $row, __METHOD__);
	}

	public static function addNewTitles($values) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->upsert(self::TABLE_NAME, $values, [], ["sv_volume=VALUES(sv_volume)"], __METHOD__);
	}

	public static function onPageContentInsertComplete( $wikiPage ){
		$title = $wikiPage->getTitle();
		if ($title->inNamespace(NS_MAIN)) {
			self::addNewTitle($title->getArticleId());
		}
		return;
	}

	public static function getVolume($pageId) {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField(self::TABLE_NAME, "sv_volume", ["sv_page_id" => $pageId], __METHOD__);
	}

	public static function getNewPageIds() {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select([self::TABLE_NAME, "page"], ['sv_page_id', 'page_title'], ['sv_volume' => -1, 'sv_page_id = page_id'], __METHOD__);

		$titles = [];
		$keywords = [];
		$ids = [];
		$count = 1;
		foreach ($res as $row) {
			$ids[] = $row->sv_page_id;
			$keyword = wfMessage("howto", $row->page_title)->text();
			if (strlen($keyword) > 80) {
				$keyword = substr($keyword, 0, 80);
			}
			$titles[$keyword] = $row->sv_page_id;
			$keywords[] = $keyword;

			if ($count % 800 == 0) { //can only do 800 at a time.
				$results = self::hitAPI($keywords);
				foreach ($results as $keyword => $volume) {
					self::updateValues($titles[$keyword], $volume);
				}
				$titles = [];
				$keywords = [];
			}
			$count++;
		}
		if (count($keywords) > 0) {
			$results = self::hitAPI($keywords);
			foreach ($results as $keyword => $volume) {
				self::updateValues($titles[$keyword], $volume);
			}
		}
		return $ids;
	}

	public static function getVolumeLabel($searchVolume) {
		if ($searchVolume < 10) {
			return "Very low";
		} elseif ($searchVolume <= 50) {
			return "Low";
		} elseif ($searchVolume <= 880) {
			return "Medium";
		} elseif ($searchVolume <= 9900) {
			return "High";
		} else {
			return "Very high";
		}
	}
}
