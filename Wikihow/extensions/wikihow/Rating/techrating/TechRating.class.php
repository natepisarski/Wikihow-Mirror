<?php

class TechRating {

	public static function techRatingHtml($articleId) {
		if(AlternateDomain::getAlternateDomainForCurrentPage() != "wikihow.tech") return;

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		//find out if we've helped someone recently
		$weekAgo = wfTimestamp(TS_DB, strtotime("1 week ago"));
		$forever = wfTimestamp(TS_DB, strtotime("January 1, 2005"));

		$articleRating = new RatingArticle();
		$foreverCount = $articleRating->getRatingCountForPeriod($articleId, $forever);
		$weekCount = $articleRating->getRatingCountForPeriod($articleId, $weekAgo);
		if($foreverCount > 0) {
			$readers = $foreverCount == 1 ? "reader" : "readers";
			$data['helped_count'] = $foreverCount;
			$data['helped_text'] = "<br /><span class='tech_reader'>{$readers}</span>&nbsp;helped";
			if($weekCount >= 2) {
				$data['count_back'] = "<span>" . $weekCount . "</span> this week!";
			} else {
				$data['helped_text'] .= "!";
			}
		} else {
			$data['helped_text'] = "Did this article<br />help you?";
			$data['tech_class'] = "none";
		}
		$data['tech_rating_help']  = "This helped me";

		return $m->render('techrating.mustache', $data);
	}
}
