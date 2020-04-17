<?php

class EventQueryTool extends UnlistedSpecialPage
{
	public function __construct() {
		global $wgHooks;
		$wgHooks['ShowSideBar'][] = array($this, 'removeSideBarCallback');
		parent::__construct('EventQueryTool');
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( $user->isBlocked() || !in_array('staff', $user->getGroups()) ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		// POST

		if ( $req->wasPosted() ) {
			$out->setArticleBodyOnly(true);
			$this->downloadCSV($req);
			return;
		}

		// GET

		$out->setPageTitle('Event Query Tool');
		$out->addModules('ext.wikihow.EventQueryTool');

		$m = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);

		$events = $this->getEvents();
		$html = $m->render( 'EventQueryTool.mustache', ['dropdown_config' => $events] );
		$out->addHTML( $html );
	}

	private function getEvents(): array {
		$events = [];
		foreach (EventConfig::EVENTS as $eventName => $eventConf) {
			// TODO remove temporary code to hide aliases
			$isAlias = isset($eventConf[2]);
			if ($isAlias) {
				continue;
			}
			$requester = $eventConf[0];
			$events[$requester]['requester'] = $requester; // extra field for Mustache
			$events[$requester]['events'][] = $eventName; // group events by requester
		}
		ksort($events); // sort event groups by requester name
		$events = array_values($events); // use numeric keys for Mustache rendering
		return $events;
	}

	private function downloadCSV(WebRequest $req) {
		$dbr = wfGetDB(DB_REPLICA);

		// Parse request parameters

		$dtA = DateTime::createFromFormat( 'M j, Y', $req->getText('date_start') );
		$dtB = DateTime::createFromFormat( 'M j, Y', $req->getText('date_end') )->modify('+ 1 days');

		$event = $req->getText('event');
		$dateStart = $dbr->addQuotes( $dtA->format('Y-m-d 00:00:00') );
		$dateEnd = $dbr->addQuotes( $dtB->format('Y-m-d 00:00:00') );

		$validFields = [ 'date', 'domain', 'page', 'screen' ];
		$groupBy = $req->getArray('breakdownby', []);
		$groupBy = array_intersect($validFields, $groupBy);

		// Read events from DB

		$table = 'event_log';
		$fields = [
			'event' => 'el_action',
			'date' => 'date(el_date)',
			'domain' => 'el_domain',
			'page' => 'el_page_id',
			'screen' => 'el_screen',
			'params' => 'el_params',
		];
		$where = [
			'el_action' => $event,
			"el_date BETWEEN $dateStart AND $dateEnd"
		];

		if ($groupBy) {
			foreach ( array_keys($fields) as $field) {
				// always show this field
				if ( $field == 'event' ) { continue; }
				// hide fields that are not included in the GROUP BY
				if ( !in_array($field, $groupBy) ) { unset( $fields[$field] ); }
			}
			$fields['count'] = 'SUM(el_count)';
			$opts = [
				'GROUP BY' => $groupBy,
				'ORDER BY' => $groupBy,
			];
		} else {
			$opts = [ 'ORDER BY' => 'date, domain, page, screen' ];
		}

		$rows = $dbr->select($table, $fields, $where, __METHOD__, $opts);

		// Transform DB rows into CSV $lines

		$headers = array_keys($fields);
		$lines = [ $headers ];
		foreach ($rows as $r) {
			$line = [];
			foreach ($headers as $header) {
				$line[] = $r->$header;
			}
			$lines[] = $line;
		}

		// Download the CSV file

		$fname = "event.{$event}." . $dtA->format('ymd') . '-' . $dtB->format('ymd') . '.csv';
		FileUtil::writeCSV($fname, $lines);
		FileUtil::downloadFile($fname, 'text/csv');
		FileUtil::deleteFile($fname);
	}

}
