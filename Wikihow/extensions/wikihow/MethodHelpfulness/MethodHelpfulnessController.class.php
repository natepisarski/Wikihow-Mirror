<?php

namespace MethodHelpfulness;

use MethodHelpfulness\CTA;
use MethodHelpfulness\ArticleMethod;
use MethodHelpfulness\Widget;
use Title;
use WikihowUser;
use SubmittedUserReview;
use RatingRedis;

/*
CREATE TABLE `method_helpfulness_details` (
  `mhd_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mhd_mhe_id` int(11) unsigned NOT NULL,
  `mhd_email` varbinary(255) DEFAULT NULL,
  `mhd_details` text,
  PRIMARY KEY (`mhd_id`),
  KEY `partial_email` (`mhd_email`(32)),
  KEY `mhd_mhe_id` (`mhd_mhe_id`),
  CONSTRAINT `method_helpfulness_details_ibfk_1` FOREIGN KEY (`mhd_mhe_id`) REFERENCES `method_helpfulness_event` (`mhe_id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `method_helpfulness_event` (
  `mhe_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mhe_user` int(11) unsigned NOT NULL DEFAULT '0',
  `mhe_user_repr` varbinary(255) NOT NULL DEFAULT '',
  `mhe_aid` int(11) unsigned NOT NULL DEFAULT '0',
  `mhe_source` varbinary(64) NOT NULL DEFAULT '',
  `mhe_is_mobile` tinyint(1) NOT NULL DEFAULT '0',
  `mhe_label` varbinary(64) NOT NULL DEFAULT '',
  `mhe_timestamp` varbinary(14) NOT NULL DEFAULT '',
  PRIMARY KEY (`mhe_id`),
  KEY `user_id` (`mhe_user`),
  KEY `source` (`mhe_source`),
  KEY `mobile` (`mhe_is_mobile`),
  KEY `article_source` (`mhe_aid`,`mhe_source`),
  KEY `timestamp_source_mobile` (`mhe_timestamp`,`mhe_source`,`mhe_is_mobile`)
);

CREATE TABLE `method_helpfulness_vote` (
  `mhv_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mhv_mhe_id` int(11) unsigned NOT NULL DEFAULT '0',
  `mhv_am_id` int(11) unsigned NOT NULL DEFAULT '0',
  `mhv_vote` varbinary(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`mhv_id`),
  KEY `vote` (`mhv_vote`),
  KEY `foreign_vote` (`mhv_mhe_id`,`mhv_am_id`,`mhv_vote`),
  KEY `_method_helpfulness_vote_ibfk_2` (`mhv_am_id`),
  CONSTRAINT `_method_helpfulness_vote_ibfk_2` FOREIGN KEY (`mhv_am_id`) REFERENCES `article_method` (`am_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `method_helpfulness_vote_ibfk_1` FOREIGN KEY (`mhv_mhe_id`) REFERENCES `method_helpfulness_event` (`mhe_id`) ON DELETE CASCADE ON UPDATE CASCADE
);
 */

class Controller {
	public static function handlePostRequest(&$context) {
		$req = $context->getRequest();
		$out = $context->getOutput();

		$out->setArticleBodyOnly(true);
		$action = $req->getVal('action', '');

		if ($action == 'cta') {
			$result = self::generateCTA($context);
		} elseif ($action == 'mustache_cta') {
			$result = self::generateMustacheCTATemplate($context);
		} elseif ($action == 'submit') {
			$result = self::submitRequest($context);
		} elseif ($action == 'get_widget') {
			$result = self::generateWidget($context);
		} elseif ($action == 'get_header_widget_data') {
			$result = self::generateHeaderWidgetData($context);
		}

		print(json_encode($result));
	}

	public static function handleGetRequest(&$context) {
		global $wgSquidMaxage;
		$req = $context->getRequest();
		$out = $context->getOutput();

		$out->setSquidMaxage($wgSquidMaxage);
		$out->setArticleBodyOnly(true);
		$action = $req->getVal('action', '');

		if ($action == 'get_header_widget_data') {
			$result = self::generateHeaderWidgetData($context);
		}

		print(json_encode($result));
	}

	protected static function generateCTA(&$context) {
		$req = $context->getRequest();

		$result = array();

		$type = $req->getVal('type', '');
		$aid = $req->getVal('aid', '');
		$platform = $req->getVal('platform', '');
		$methods = $req->getVal('methods', false);

		if ($methods !== false) {
			$methods = json_decode($methods);
		}

		$ctaClass = 'MethodHelpfulness\\' . CTA::getCTAClass($type);
		$cta = new $ctaClass();

		$resourceModule = $cta::getResourceModule($platform);
		$result['resourceModule'] = $resourceModule;

		$t = Title::newFromID($aid);

		$ctaReturnKey = 'cta';
		$ctaType = $cta->getCTAType();
		if ($ctaType === CTA::CTA_TYPE_STANDALONE) {
			$ctaReturnKey = 'cta';
		} elseif ($ctaType === CTA::CTA_TYPE_PER_METHOD) {
			$ctaReturnKey = 'ctalist';
		}

		$result[$ctaReturnKey] = $cta->getCTA($t, $platform, $methods);

		return $result;
	}

	public static function generateMustacheCTATemplate($type, &$t, $platform) {
		$ctaClass = 'MethodHelpfulness\\' . CTA::getCTAClass($type);
		$cta = new $ctaClass();

		$resourceModule = $cta::getResourceModule($platform);
		$result['resourceModule'] = $resourceModule;

		$result['template'] = $cta->getMustacheCTATemplate($t, $platform);

		return $result;
	}

	protected static function submitRequest(&$context) {
		$req = $context->getRequest();

		$type = strip_tags($req->getVal('type', ''));
		$aid = $req->getVal('aid', 0);
		$platform = strip_tags($req->getVal('platform', ''));
		$label = strip_tags($req->getVal('label', ''));

		if (!$aid) {
			return array('error' => 'Invalid article requested.');
		}

		$submissionClass  =
			'MethodHelpfulness\\' . SubmissionHandler::getSubmissionClass($type);

		if ($submissionClass === false) {
			return array('error' => 'Cannot process request.');
		}

		return $submissionClass::submitRequest($req, $aid, $platform, $label);
	}

	protected static function generateWidget(&$context) {
		$req = $context->getRequest();

		$aid = $req->getVal('aid', 0);

		$result = array('widget_summary' => '');

		if (!$aid) {
			return $result;
		}
		$widgetSectionTypes = array('bottom_form', 'method_thumbs');
		$result['widget_summary'] = Widget::getWidget($aid, $widgetSectionTypes);

		return $result;
	}

	protected static function generateHeaderWidgetData(&$context) {
		$req = $context->getRequest();

		$aid = $req->getInt('aid', 0);

		if (!$aid) {
			return $result;
		}
		$ctaDetails = ArticleMethod::getCTAVoteDetails($req->getVal('aid'));
		$result = MethodHeaderWidgetSection::getFormattedVars($ctaDetails);
		return $result;
	}

	public static function onRatingsCleared($_, $aid) {
		ArticleMethod::clearArticleMethods($aid);

		return true;
	}
}

abstract class SubmissionHandler {
	/**
	 * Handle a request for submission and return an associative array with status info
	 */
	abstract public static function submitRequest(&$req, $aid, $platform, $label);

	/**
	 * Submit prepared data.
	 */
	protected static function submit($eventData, $voteData, $detailsData=false) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->insert(
			'method_helpfulness_event',
			$eventData,
			__METHOD__
		);

		$eventId = $dbw->insertId();

		$sqlVoteData = array();
		$aid = $voteData['aid'];
		foreach ($voteData['votes'] as $methodName=>$vote) {
			$methodId = ArticleMethod::getMethodIdOrInsert($aid, $methodName);

			$sqlVoteData[] = array(
				'mhv_mhe_id' => $eventId,
				'mhv_am_id' => $methodId,
				'mhv_vote' => $vote
			);
		}

		$dbw->insert(
			'method_helpfulness_vote',
			$sqlVoteData,
			__METHOD__
		);

		if ($detailsData !== false) {
			$detailsData['mhd_mhe_id'] = $eventId;

			$dbw->insert(
				'method_helpfulness_details',
				$detailsData,
				__METHOD__
			);
		}

		return array(
			'success' => true,
			'eventId' => $eventId
		);
	}

	protected static function specialSubmit($table, $data) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->insert(
			$table,
			$data,
			__METHOD__
		);

		return array('success' => true);
	}

	public static function getSubmissionClass($type) {
		if ($type === 'bottom_form') {
			return BottomFormSubmissionHandler;
		} elseif ($type === 'method_thumbs') {
			return MethodThumbsSubmissionHandler;
		} elseif ($type === 'details_form') {
			return DetailsFormSubmissionHandler;
		} else {
			return false;
		}
	}

	protected static function prepareEventData($aid, $type, $platform, $label) {
		$user = RequestContext::getMain()->getUser();

		$data = array(
			'mhe_aid' => $aid,
			'mhe_source' => $type,
			'mhe_is_mobile' => $platform === CTA::PLATFORM_MOBILE,
			'mhe_label' => $label
		);

		$data['mhe_user'] = $user->getId();

		if ($user->isAnon()) {
			$data['mhe_user_repr'] = WikihowUser::getVisitorId();
		} else {
			$data['mhe_user_repr'] = $user->getName();
		}

		$data['mhe_timestamp'] = wfTimestampNow();

		return $data;
	}

	protected static function prepareVoteData($aid, &$voteData) {
		return array(
			'aid' => $aid,
			'votes' => $voteData
		);
	}

	protected static function prepareDetailsData($eventId, $details, $email) {
		if (!$eventId) {
			// Should we also validate that provided event ID actually exists?
			return false;
		}
		if (!$details) {
			$details = NULL;
		}
		if (!$email) {
			$email = NULL;
		}

		if (is_null($details) && is_null($email)) {
			return false;
		} else {
			return array(
				'mhd_mhe_id' => $eventId,
				'mhd_email' => $email,
				'mhd_details' => $details
			);
		}
	}

	protected static function hasVotedMethodRecently(&$eventData, $method) {
		$dbr = wfGetDB(DB_REPLICA);

		$yesterday = wfTimestamp(
			TS_MW,
			strtotime('-1 day', time())
		);

		$row = $dbr->selectRow(
			array(
				'mhe' => 'method_helpfulness_event',
				'am' => 'article_method',
				'mhv' => 'method_helpfulness_vote'
			),
			array('mhe_id'),
			array(
				'am_aid' => $eventData['mhe_aid'],
				'am_title_hash' => ArticleMethod::getTitleHash($method),
				'am_active' => '1',
				'mhe_source' => $eventData['mhe_source'],
				'mhe_user_repr' => $eventData['mhe_user_repr'],
				'mhe_timestamp > ' . $dbr->addQuotes($yesterday)
			),
			__METHOD__,
			array(
				'GROUP BY' => array(
					'am_aid',
					'am_title_hash',
					'mhe_source',
					'mhe_user_repr'
				),
				'ORDER BY' => array(
					'am_timestamp DESC'
				)
			),
			array(
				'am' => array(
					'INNER JOIN',
					array(
						'am_aid=mhe_aid'
					)
				),
				'mhv' => array(
					'INNER JOIN',
					array(
						'mhv_mhe_id=mhe_id',
						'mhv_am_id=am_id'
					)
				)
			)
		);

		return $row !== false;
	}

	public static function getDetailsTable() {
		return 'method_helpfulness_details';
	}
}

class BottomFormSubmissionHandler extends SubmissionHandler {
	const CHECKBOX_CHECKED = 'checkbox_checked';
	const CHECKBOX_UNCHECKED = 'checkbox_unchecked';

	public static function submitRequest(&$req, $aid, $platform, $label) {
		$methodsChecked = $req->getVal('methodsChecked', false);

		if ($methodsChecked === false) {
			return array('error' => 'No methods received.');
		}

		$methodsChecked = get_object_vars(json_decode($methodsChecked));

		if (is_null($methodsChecked) || !is_array($methodsChecked)) {
			return array('error' => 'No valid methods received.');
		}

		$sanitizedMethodsChecked = array();
		foreach ($methodsChecked as $methodName=>$checked) {
			$sanitizedMethodsChecked[$methodName] =
				$checked
				? self::CHECKBOX_CHECKED
				: self::CHECKBOX_UNCHECKED;
		}

		$eventData = self::prepareEventData(
			$aid,
			'bottom_form',
			$platform,
			$label
		);

		$voteData = self::prepareVoteData(
			$aid,
			$sanitizedMethodsChecked
		);

		return self::submit($eventData, $voteData);
	}
}

class MethodThumbsSubmissionHandler extends SubmissionHandler {
	public static function submitRequest(&$req, $aid, $platform, $label) {
		$method = strip_tags($req->getVal('method', false));

		if ($method === false) {
			return array('error' => 'No method received.');
		}

		$voteType = $req->getVal('voteType', false);

		if ($voteType === false) {
			return array('error' => 'Vote type not received.');
		}

		$sanitizedVoteData = array(
			$method => $voteType
		);

		$eventData = self::prepareEventData(
			$aid,
			'method_thumbs',
			$platform,
			$label
		);

		if (self::hasVotedMethodRecently($eventData, $method)) {
			return array('error' => 'You recently voted for this method.');
		}

		$voteData = self::prepareVoteData(
			$aid,
			$sanitizedVoteData
		);

		return self::submit($eventData, $voteData);
	}
}

class DetailsFormSubmissionHandler extends SubmissionHandler {
	public static function submitRequest(&$req, $aid, $platform, $label) {
		$eventId = $req->getVal('eventId', false);

		if ($eventId === false) {
			return array('error' => 'No associated event ID provided.');
		}

		$email = strip_tags($req->getVal('email', null));
		$details = strip_tags($req->getVal('details', null));

		$detailsData = self::prepareDetailsData(
			$eventId,
			$details,
			$email
		);

		if ($detailsData === false) {
			return array('error' => 'Broken details data provided.');
		}

		//if it's public, then also add to UserReview table
		$isPublic = $req->getVal('isPublic', false);
		$firstname = $req->getVal('firstname');
		$lastname = $req->getVal('lastname');
		if ($isPublic) {
			$sur = SubmittedUserReview::newFromFields(
				$aid,
				$firstname,
				$lastname,
				$details,
				$email,
				RequestContext::getMain()->getUser()->getId(),
				WikihowUser::getVisitorId()
			);
			if ( $sur->isQualified()) {
				$sur->correctFields();
				$sur->save();
			}
		}

		RatingRedis::addRatingReason($aid, $details);
		return self::specialSubmit(self::getDetailsTable(), $detailsData);
	}
}

