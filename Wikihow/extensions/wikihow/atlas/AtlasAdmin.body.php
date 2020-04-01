<?php

/*
	create table url_list_name(
		unl_id integer primary key auto_increment,
		uln_name varchar(255),
		uln_date_added varchar(12)
	);
	create table url_list(
		ul_uln integer NOT NULL
		ul_url varchar(255) NOT NULL,
		ul_article_id integer NOT NULL,
		ul_revision_id integer NOT NULL,
		primary key(ul_id, ul_revision_id)
);*/
/**
  * Class for creating lists of things for training
  */
class AtlasAdmin extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('AtlasAdmin');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ( $user->isBlocked() ||  !in_array( 'staff', $userGroups ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$urls = $req->getVal('urls');
		if ($urls) {
			$listName = $req->getVal('listName');
			$urls = preg_split("@[\r\n]+@",$urls, $matches);

			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('url_list_name',array('uln_name' => $listName, 'uln_date_added' => wfTimestampNow()), __METHOD__);
			$id = $dbw->insertId();
			foreach ($urls as $url) {
				if (preg_match("@http://www\.wikihow\.com/(.+)@", $url, $matches)) {
					$t = Title::newFromText($matches[1]);
					if ($t) {
						$r = Revision::newFromTitle($t);
						if ($r) {
							$dbw->insert('url_list',
								array('ul_uln' => $id, 'ul_url' => $url, 'ul_article_id' => $t->getArticleId(), 'ul_revision_id' => $r->getId()),
								__METHOD__ ,
								array('ignore'));
						}
					}
				}
			}
		} else {
			EasyTemplate::set_path(__DIR__.'/');
			$vars = array();
			$html = EasyTemplate::html('atlasadmin.tmpl.php', $vars);
			$out->addHTML($html);
		}
	}
}
