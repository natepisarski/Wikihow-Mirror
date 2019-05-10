<?php

/**#@+
 * A simple extension that allows users to enter a title before creating a page.
 *
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @author wikiHow
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class CreatePage extends SpecialPage {
	function __construct() {
		parent::__construct('CreatePage');
		EasyTemplate::set_path( __DIR__ );
	}

	function cleanupProposedRedirects(&$text) {
		$lines = explode("\n", $text);
		$uniques = array();
		foreach ($lines as $line) {
			$params = explode("\t", $line);
			if (sizeof($params) != 3) continue;
			$uniques[$line] = $line;
		}
		$text = trim(implode("\n", $uniques));
	}

	function addProposedRedirect($from, $to) {
		global $wgUser;
		if ($wgUser->getID() > 0) {
			ProposedRedirects::createProposedRedirect($from->getDBKey(), $to->getDBKey());
		}
	}

	function getTitleResult($t, $target, $redir=false) {
		$title_text = $redir ? $target : $t->getText();
		$html = "<div id='cpr_text_top'>".
				wfMessage('cp_title_exists_top',$title_text,$t->getPartialURL())->text() . "<br /><br />" .
				"<input id='cpr_write_something' type='button' class='button secondary' value='".wfMessage('cp_left_btn')->text()."' />" .
				"<input id='cpr_add_something' type='button' class='button primary' value='".wfMessage('cp_right_btn')->text()."' /></div>" .
				"<div id='cpr_text_bottom'><div class='triangle_up'></div>" .
				"<div id='cp_title_input_block' class='cp_block'><form>" .
				"<b>".wfMessage('howto','')->text()."</b><input autocomplete='off' maxLength='256' id='cp_existing_title_input' name='target' value='' class='search_input' type='text' placeholder='".wfMessage('cp_title_ph2')->text()."' />" .
				"<input type='submit' id='cp_existing_title_btn' value='". wfMessage('cp_title_submit2')->text() ."' class='button primary createpage_button' />" .
				"</form></div>" .
				"<div id='cpr_text_details'>".wfMessage('cp_title_exists_details',$t->getText(),$t->getPartialURL(),$t->getEditURL())->text()."</div></div>";
		return $html;
	}

	function getRelatedTopicsText($target) {
		global $wgOut, $wgUser, $wgLanguageCode;

		// INTL: Don't return related topics for non-english sites
		if ($wgLanguageCode != 'en') return '';


		$hits = array();
		$t = Title::newFromText(GuidedEditorHelper::formatTitle($target));
		$overwriteAllowed = NewArticleBoost::isOverwriteAllowed($target);
		$l = new LSearch();
		$hits  = $l->externalSearchResultTitles($target, 0, 10);
		$count = 0;
		if (sizeof($hits) > 0) {
			foreach  ($hits as $hit) {
				$t1 = $hit;
				if ($count == 5) break;
				if ($t1 == null) continue;
				if (!$t1->inNamespace(NS_MAIN)) continue;

				// check if the result is a redirect
				$a = new Article($t1);
				if ($a && $a->isRedirect()) continue;

				$s .= "<div><input type='radio' id='at_".$t1->getArticleID()."' class='article_topic' name='article_topic' /> " .
						"<label for='at_".$t1->getArticleID()."' class='cpr_green'>". wfMessage('howto', $t1->getText() ) . "</label>" .
						"<div class='article_options'>".wfMessage('cp_title_new_option')->text().
						"<div class='article_options_options' data-id='".urlencode($t1->getDBKey())."'><a href='".$t1->getEditURL()."' class='button secondary' target='new'>".wfMessage('cpr_add_to')->text()."</a> ".
						"<a href='/Special:CreatePage'>".wfMessage('cpr_write_something')->text()."</a>".
						"</div></div></div>";
				$count++;
			}
			if ($count == 0) return '';

			$html = $s."<div><input type='radio' id='cpr_article_none' class='article_topic' name='article_topic' /> " .
					"<label for='cpr_article_none' class='cpr_black'>". wfMessage('cp_related_none', wfMessage('howto',$target->getText()))->text() ."</label>" .
					"<div class='article_options'>" . wfMessage('cp_ready_write',$target->getText())->text() .
					"<div class='article_options_options'><a class='button primary' href='".self::grabEditURL($target)."'>".wfMessage('cp_write_it')->text()."</a>".
					"<div></div></div>";
			return $html;
		}
		return '';
	}

	public function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgLanguageCode, $wgScriptPath;
		global $wgMimeType;
		$target = $wgRequest->getVal( 'target' );
		$topic = $wgRequest->getVal('topic');

		//redir if the page is using the old url format
		if (isset($par)) $wgOut->redirect('/Special:CreatePage?target='.urlencode($par));

		if ($wgRequest->getVal('ajax') == 'true') {
			$wgMimeType = 'application/json';
			$wgOut->setArticleBodyOnly(true);

			if ($topic) {

				$matches = SuggestionSearch::matchKeyTitles($topic, 30);

				if (count($matches) > 0) {
					$html = wfMessage('cp_topic_matches_text')->text().
							'<table class="cpresults"><tr>';
					$count = 0;
					for ($i = 0; $i < count($matches); $i++) {
						$t = Title::newFromDBkey($matches[$i][0]);
						if ($t) {
							$html .= "<td><a href='".$t->getDBKey()."'>{$t->getFullText()}</a></td>";
							$count++;
						}
						if ($count % 2 == 0) $html .= "</tr><tr>";
					}

					$html .= "</tr></table>".wfMessage('cp_topic_tryagain');
					$class = 'cp_result_good';
					$hdr = wfMessage('cp_topic_matches_hdr',$topic);
				}
				else {
					$html = wfMessage('cp_no_topics')->text();
					$class = 'cp_result_err';
					$hdr = '';
				}

				$result['topic'] = $topic;
				$result['class'] = $class;
				$result['header'] = $hdr;
				$result['html'] = $html;

				$wgOut->addHTML(json_encode($result));

			} elseif ($target) {

				$t = Title::newFromText($target);

				//creating a redirect?
				if ($wgRequest->getVal('createpage_title') != null) {
					//SO MANY REDIRECTS...let's take a break
					// $from = $t;
					// $to = Title::newFromText(GuidedEditorHelper::formatTitle($wgRequest->getVal('createpage_title')));
					// if ($from && $to) ProposedRedirects::createProposedRedirect($from->getDBKey(), $to->getDBKey());
					return;
				}

				if ($wgLanguageCode == 'en' && (!$t || !$t->exists())) {
					$t2 = Title::newFromText(GuidedEditorHelper::formatTitle($target));
					if ($t2) $t = $t2;
				}

				if (!$t) {

					$result = [
						'target' => $target,
						'class' => 'cp_result_err',
						'header' => 'Bad title',
						'button_text' => wfMessage('cp_existing_btn')->text(),
						'html' => 'Bad title: ' . $target,
						'edit_url' => '',
					];

				} else {

					// redirect check
					if ($t->isRedirect()) {
						$wikiPage = WikiPage::factory($t);
						$t = $wikiPage->getRedirectTarget();
						$redir = true;
					} else {
						$redir = false;
					}

					if ($t->getArticleID() > 0 && !NewArticleBoost::isOverwriteAllowed($t) && !CreateEmptyIntlArticle::isEligibleToTranslate($t, $wgLanguageCode, $wgUser)) {
						// existing title -> error
						$result = [
							'target' => $target,
							'class' => 'cp_result_err',
							'header' => wfMessage('cp_title_exists')->text(),
							'button_text' => wfMessage('cp_existing_btn')->text(),
							'html' => $this->getTitleResult($t, $target, $redir),
							'edit_url' => $t->getEditURL(),
						];
					} else {
						// new title
						$result = [
							'new' => 'true',
							'target' => $target,
							'list' => $this->getRelatedTopicsText($t),
							'class' => 'cp_result_good',
							'header' => wfMessage('cp_title_new')->text(),
							'text' => wfMessage('cp_title_new_details',$t->getText())->text(),
						];
					}

				}

				$wgOut->addHTML(json_encode($result));
			}
			return;
		}

		$me = Title::newFromText("CreatePage", NS_SPECIAL);
		$sk = $this->getSkin();
		$this->setHeaders();
		$wgOut->addModules('ext.wikihow.createpage');

		if ($wgRequest->wasPosted() && $wgRequest->getVal('create_redirects') != null) {
			// has the user submitted a redirect?
			$source = Title::newFromText(GuidedEditorHelper::formatTitle($wgRequest->getVal('createpage_title')));
			$p1 = Title::newFromText($wgRequest->getVal('createpage_title'));
			if ($wgRequest->getVal($p1->getDBKey()) == 'none' || $wgRequest->getVal($p1->getDBKey()) == null) {
				$wgOut->redirect(self::grabEditURL($source));
				$editor = $wgUser->getOption('defaulteditor', '');
				if (empty($editor)) {
					$editor = 'advanced';
				}
				return;
			} else {
				$target = Title::newFromText($wgRequest->getVal($p1->getDBKey()));
				if (!$target && $source->getArticleID() > 0){
					$wgOut->redirect($source->getEditURL());
					return;
				}

				// add redirect to list of proposed redirects
				CreatePage::addProposedRedirect($source, $target);
				$wgOut->addWikiText(wfMessage('createpage_redirect_confirmation', $source->getText(), $target->getText(), $target->getEditURL()));
				$wgOut->addHTML(wfMessage('createpage_redirect_confirmation_bottom', $source->getText(), $target->getText(), $target->getEditURL()));
				return;
			}
		}

		$this->outputCreatePageForm();
	}

	function outputCreatePageForm() {
		global $wgOut, $wgScriptPath;

		$boxes = EasyTemplate::html('createpage_boxes.tmpl.php');

		$wgOut->addModules('ext.wikihow.editor_script');

		$wgOut->addHTML("
		<script>
			function checkform() {
				if (document.createform.target.value.indexOf('?') > 0 ) {
					alert('The character ? is not allowed in the title of an article.');
					return false;
				}
				return true;
			}
		</script>
		"
		. $boxes
		);

		$wgOut->addModules(array('ext.wikihow.common_top', 'ext.wikihow.common_bottom'));

		return;
	}

	function grabEditURL($t) {
		global $wgUser;
		if (!class_exists('ArticleCreator') || !$wgUser->getOption('articlecreator')) {
			if (NewArticleBoost::isOverwriteAllowed($t)) {
				$url = $t->getEditURL() . "&review=1&overwrite=yes";
			} else {
				$url = $t->getEditURL() . "&review=1";
			}
		} else {
			$url = '/Special:ArticleCreator?t=' . $t->getPartialUrl();
			if (NewArticleBoost::isOverwriteAllowed($t)) {
				$url .=  "&overwrite=yes";
			}
		}
		return $url;
	}

	function sendTalkPageMsg($user, $t) {
		$talkPage  = $user->getUserPage()->getTalkPage();
		if ($talkPage) {
			$submitter = User::newFromName( 'Article-Review-Team' );
			$comment = TalkPageFormatter::createComment($submitter, wfMessage('usertalk_first_article_message')->text(), true, $t, false);
			$talkPage  = $user->getUserPage()->getTalkPage();
			$text = '';

			//add to existing?
			if ($talkPage->exists()) {
				$revision = Revision::newFromTitle($talkPage);
				$content = $revision->getContent(Revision::RAW);
				$text = ContentHandler::getContentText($content);
			}

			$text .= $comment;
			$page = WikiPage::factory($talkPage);
			$content = ContentHandler::makeContent($text, $talkPage);

			try {
				$page->doEditContent($content, '', EDIT_SUPPRESS_RC, false, $submitter);
			} catch (MWException $e) {
				wfDebugLog( 'CreateFirstArticle', 'exception in ' . __METHOD__ . ':' . $e->getText() );
			}

		}

	}
}
