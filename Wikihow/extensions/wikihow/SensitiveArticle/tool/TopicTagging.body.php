<?php

namespace SensitiveArticle;

class TopicTagging extends \UnlistedSpecialPage {

	var $isMobile = false;

	public function __construct() {
		parent::__construct( 'TopicTagging');
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotpolicy('noindex, nofollow');

		if ($user->isBlocked()) {
			$out->blockedPage();
			return;
		}

		$this->isMobile = \Misc::isMobileMode();

		$this->skipTool = new \ToolSkip("TopicTagging");

		$action = $request->getVal('action','');

		if (!empty($action)) {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			$topic_id = $request->getInt('topic_id', 0);
			$page_id = $request->getInt('page_id', 0);

			if ($action == 'next') {
				$result = $this->getNextArticleToTag($topic_id);
			}
			elseif ($action == 'vote') {
				$vote = $request->getInt('vote');
				$result = $this->vote($page_id, $topic_id, $vote);
			}
			elseif ($action == 'skip') {
				$result = $this->skip($page_id, $topic_id);
			}

			print json_encode($result);
			return;
		}

		$out->addModules('ext.wikihow.topic_tagging_tool');
		$out->setHtmlTitle(wfMessage('topic_tagging_title')->text());
		$out->addHTML($this->toolHtml());
		$this->addStandingGroups();
	}

	private function numberOfVotesForUser(): int {
		$user = \RequestContext::getMain()->getUser();
		return $this->powerVoter($user) ? SensitiveArticleVote::VOTE_POWER_VOTER : 1;
	}

	private function powerVoter(\User $user): bool {
		$power_voter_groups = [
			'sysop',
			'staff',
			'staff_widget',
			'newarticlepatrol'
		];

		return !$user->isAnon() && \Misc::isUserInGroups($user, $power_voter_groups);
	}

	private function toolHtml(): string {
		$loader = new \Mustache_Loader_CascadingLoader( [
			new \Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new \Mustache_Engine(['loader' => $loader]);

		$vars = [
			'tool_title' => wfMessage('topic_tagging_title')->text(),
			'remaining_label' => wfMessage('topic_tagging_remaining')->text(),
			'yes' => wfMessage('htmlform-yes')->text(),
			'no' => wfMessage('htmlform-no')->text(),
			'skip' => wfMessage('topic_tagging_button_skip')->text(),
			'is_mobile' => $this->isMobile
		];

		$html = $m->render('topic_tagging_tool', $vars);
		return $html;
	}

	private function getNextArticleToTag(int $topic_id = 0): array {
		$next = [];

		$userId = \RequestContext::getMain()->getUser()->getId();
		$visitorId = empty($userId) ? \WikihowUser::getVisitorId() : '';

		$skip_ids = $this->skipTool->getSkipped();
		if (!is_array($skip_ids)) $skip_ids = [];

		$sav = SensitiveArticleVote::getNextArticleVote($topic_id, $skip_ids, $userId, $visitorId);

		if (!empty($sav)) {
			$title = \Title::newFromId($sav->pageId);
			if ($title) {
				$page_link = 	'<a href="'.$title->getDBkey().'" target="_blank">'.
											wfMessage('howto',$title->getText())->text().'</a>';

				$remaining = !$this->isMobile ? self::articlesRemaining($skip_ids, $userId, $visitorId) : '';

				$sr = SensitiveReason::getReason($sav->reasonId);

				$next = [
					'page_id' => $sav->pageId,
					'page_title' => $page_link,
					'topic_id' => $sav->reasonId,
					'question' => $sr->question,
					'description' => $sr->description,
					'article_html' => $this->articleHtml($sav->pageId),
					'remaining' => $remaining
				];
			}
			else {
				$this->markBadTitleComplete($sav);
			}
		}

		if (empty($next)) {
			$eoq = new \EndOfQueue();
			$next = ['end_of_queue' => $eoq->getMessage('ttt')];
		}

		return $next;
	}

	private function markBadTitleComplete(SensitiveArticleVote $sav): bool {
		$sav->complete = 1;
		return $sav->save();
	}

	private function articleHtml(int $page_id): string {
		$html = '';

		$page = \WikiPage::newFromId($page_id);
		if ($page) {
			$title = $page->getTitle();

			if ($title && $title->exists()) {

				if ($this->isMobile) {
					$revision = \Revision::newFromTitle($title);
					if (!empty($revision)) {
						$config = \WikihowMobileTools::getToolArticleConfig();
						$html = \WikihowMobileTools::getToolArticleHtml($title, $config, $revision);
					}
				}
				else {
					$out = $this->getOutput();
					$popts = $out->parserOptions();
					$popts->setTidy(true);
					$content = $page->getContent();

					if ($content) {
						$parserOutput = $content->getParserOutput($title, null, $popts, false)->getText();
						$whOpts = [ 'no-ads' => true, 'ns' => NS_MAIN ];
						$articleHtml = new \WikihowArticleHTML( $parserOutput, $whOpts );
						$html = $articleHtml->processBody();
					}
				}
			}
		}

		if (!empty($html) && !$this->isMobile) {
			$html = '<h2>'.wfMessage('topic_tagging_article_header')->text().'</h2>'.$html;
		}

		return $html;
	}

	private function addStandingGroups() {
		if ($this->isMobile) return;

		$indi = new \TopicTaggingStandingsIndividual();
		$indi->addStatsWidget();

		$group = new \TopicTaggingStandingsGroup();
		$group->addStandingsWidget();
	}


	private function vote(int $page_id, int $topic_id, int $vote): array {
		$result = false;

		$sav = SensitiveArticleVote::newFromDB($page_id, $topic_id);
		if (!empty($sav)) {
			$num_of_votes = $this->numberOfVotesForUser();

			$vote ? $sav->voteYes += $num_of_votes : $sav->voteNo += $num_of_votes;
			$result = $sav->save();

			$action = $vote ? 'upvote' : 'downvote';
			self::log($page_id, $topic_id, $action);

			SensitiveArticleVoteAction::markVoted($sav->rowId, $vote);
		}

		return ['success' => $result];
	}

	private function skip(int $page_id, int $topic_id): array {
		$result = false;

		$sav = SensitiveArticleVote::newFromDB($page_id, $topic_id);
		if (!empty($sav->rowId)) {
			$sav->skip += 1;
			$result = $sav->save();

			$this->skipTool->skipItem($sav->rowId);
		}

		return ['success' => $result];
	}

	public static function log(int $page_id, int $topic_id, string $action) {
		$sr = SensitiveReason::getReason($topic_id);
		if (empty($sr)) return;
		$topic = $sr->name;

		$title = \Title::newFromID($page_id);
		if (empty($title)) return;

		$logPage = new \LogPage('topic_tagging', false);
		$logMsg = wfMessage('topic_tagging_log_'.$action, $title, $topic)->text();
		$logPage->addEntry($action, $title, $logMsg);
	}

	public static function articlesRemaining(array $skip_ids = [], int $userId = 0, string $visitorId = ''): int {
		return SensitiveArticleVote::remainingCount($skip_ids, $userId, $visitorId);
	}
}