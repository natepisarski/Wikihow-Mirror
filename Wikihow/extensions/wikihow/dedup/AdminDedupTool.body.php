<?php

class AdminDedupTool extends UnlistedSpecialPage {

	const TABLE_NAME = 'dedup.deduptool';

	public function __construct() {
		parent::__construct( 'AdminDedupTool' );
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute( $subPage ) {
		$output = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();

		$output->setRobotPolicy( "noindex,nofollow" );

		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( $this->getLanguage()->getCode() != 'en' ) {
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$groups = $user->getGroups();
		if ( !in_array('staff', $groups) && !in_array('staff_widget', $groups)) {
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $request->getVal('timestamp') ) {
			$output->setArticleBodyOnly( true );

			$this->getBatch($request->getVal('timestamp'));

			return;
		}

		$output->setPageTitle( wfMessage( 'admindeduptool' )->text() );
		$output->addModules( 'ext.wikihow.AdminDedupTool' );

		$html = $this->getToolHTML();
		$output->addHTML( $html );
	}

	private function getToolHTML() {
		$vars = [
			'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : '',
			'queries' => $this->getData()
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'admindeduptool', $vars );

		return $html;
	}

	private function getData() {
		$data =[];
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			DedupTool::TABLE_NAME,
			['*', 'count(*) as count', 'sum(case when ddt_final = 0 then 1 else 0 end) as remaining'],
			[],
			__METHOD__,
			['GROUP BY' => 'ddt_import_timestamp', 'ORDER BY' => 'ddt_import_timestamp DESC']
		);

		foreach ($res as $row) {
			$data[] = [
				'date' => date("F j Y, G:i", wfTimestamp(TS_UNIX, $row->ddt_import_timestamp)),
				'numQueries' => $row->count,
				'link' => '/Special:AdminDedupTool?timestamp='.$row->ddt_import_timestamp,
				'remaining' => $row->remaining
			];
		}

		return $data;
	}

	private function getBatch($timestamp) {
		header("Content-Type: text/tsv");
		header('Content-Disposition: attachment; filename="Dedup_'.$timestamp.'.xls"');

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			DedupTool::TABLE_NAME,
			'*',
			['ddt_import_timestamp' => $timestamp],
			__METHOD__
		);

		print "Query\tMatch\tMatched URL\tMatched article ID\tUser ID\tTimestamp\n";

		foreach ($res as $row) {
			print $row->ddt_query . "\t";
			if ($row->ddt_final > 0) {
				$title = Title::newFromId($row->ddt_final);
				if ($title) {
					print "1\thttp://www.wikihow.com/" . $title->getPartialURL() . "\t" . $row->ddt_final . "\t";
				} else {
					print "1\tMatched article deleted\t0\t";
				}
			} else {
				print "0\t\t\t";
			}
			if ($row->ddt_final_userid > 0) {
				$user = User::newFromId($row->ddt_final_userid);
				print $user->getName() . "\t" . date("F n Y, G:i", wfTimestamp(TS_UNIX, $row->ddt_import_timestamp)) . "\n";
			} else {
				print "\t\n";
			}
		}
	}
}
