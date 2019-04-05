<?php

class CreatepageWarn extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('CreatepageWarn');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$out->setArticleBodyOnly(true);
		$warn = $req->getVal('warn');
		switch ($warn) {
			case 'caps':
				$out->addHTML( wfMessage('createpage_uppercase')->plain() );
				break;
			case 'sentences':
				$out->addHTML( wfMessage('createpage_sentences', $req->getInt('sen'))->plain() );
				break;
			case 'intro':
				$out->addHTML( wfMessage('createpage_intro', $req->getInt('words'))->plain() );
				break;
			case 'words':
			default:
				$out->addHTML(wfMessage('createpage_tooshort', $req->getInt('words'))->plain() );
		}
		$out->addHTML( wfMessage('createpage_bottomwarning')->plain() );
	}
}

class CreatePageTitleResults  extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('CreatePageTitleResults');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$t = Title::newFromText($req->getVal('target'));
		$s = CreatePage::getRelatedTopicsText($t);
		$out->setArticleBodyOnly(true);
		$out->addHTML($s);
	}
}

class CreatepageReview extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('CreatepageReview');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->addHTML("
			<div id='review_intro'>" . wfMessage('createpage_reviewintro') . "</div>
			<div id='article'>
			<div id='preview_landing' style='height: 280px; overflow:auto;' class='wh_block'>
				<div style='text-align: center; margin-top: 350px;'>" . wfMessage('cp_loading') . "<br/><img src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "'></div>
			</div>
			" . wfMessage('createpage_review_options')->plain() . "
			</div>"
		);
	}
}

class CreatepageFinished extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('CreatepageFinished');
		EasyTemplate::set_path( __DIR__ );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		$authoremail = '';
		$share_fb = '';
		$out->setArticleBodyOnly(true);
		if ($user->getID() > 0) {
			if ($user->getEmail() == '') {
				$authoremail = "<input type='text' maxlength='240' size='30' id='email_me' value='' class='input_med' onkeydown=\"document.getElementById('email_notification').checked = true;\" />
					<input type='hidden' id='email_address_flag' value='0'>";
			} else {
				$authoremail = "<input type='text' readonly='true' maxlength='240' id='email_me' value='".$user->getEmail()."' class='input_med' />
					<input type='hidden' id='email_address_flag' value='1'>";
			}
			if ($user->isFacebookUser()) {
				$template = 'createpage_finished.tmpl.php';
				$share_fb = "share_article('facebook')";
			} else {
				$template = 'createpage_finished.tmpl.php';
				$share_fb = "gatTrack('Author_engagement','Facebook_post','Publishing_popup'); var d=document,f='http://www.facebook.com/share',l=d.location,e=encodeURIComponent,p='.php?src=bm&v=4&i=1178291210&u='+e(l.href)+'&t='+e(d.title);1;try{if (!/^(.*\.)?facebook\.[^.]*$/.test(l.host))throw(0);share_internal_bookmarklet(p)}catch(z){a=function(){if (!window.open(f+'r'+p,'sharer','toolbar=0,status=0,resizable=0,width=626,height=436'))l.href=f+p};if (/Firefox/.test(navigator.userAgent))setTimeout(a,0);else{a()}}void(0)";
			}
		} else {
			$template = 'createpage_finished_anon.tmpl.php';
		}

		$vars = array(
			'authoremail' => $authoremail,
			'share_fb' => $share_fb,
		);
		$box = EasyTemplate::html($template, $vars);
		$out->addHTML($box);
	}
}

class CreatepageEmailFriend extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('CreatepageEmailFriend');
	}

	public function execute($par) {
		global $wgParser;
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if (!$req->wasPosted()) {
			return;
		}

		$out->setArticleBodyOnly(true);
		$friends = explode(",", $req->getVal('friends'));
		$target = Title::newFromURL($req->getVal('target'));
		if (!$target) {
			return;
		}
		$rev = Revision::newFromTitle($target);
		if (!$rev) {
			return;
		}
		$summary = $wgParser->getSection(ContentHandler::getContentText( $rev->getContent() ), 0);
		$summary = preg_replace('@<[^>]*>@', '', $summary);
		$summary = preg_replace('@\[\[[^\]]*\]\]@', '', $summary);
		$summary = preg_replace('@\{\{[^}]*\}\}@', '', $summary);
		$body = wfMessage('createpage_email_body', $target->getFullText(), $summary, $target->getFullURL())->text();
		$subject = wfMessage('createpage_email_subject', $target->getFullText())->text();
		$count = 0;
		if ($user->isAnon() || !$user->getEmail()) {
			$from = new MailAddress("wiki@wikihow.com", "wikiHow");
		} else {
			$from = new MailAddress($user->getEmail());
		}
		foreach ($friends as $f) {
			$to = new MailAddress($f);
			UserMailer::send($to, $from, $subject, $body);
			$count++;
			if ($count == 3) {
				break;
			}
		}
	}
}
