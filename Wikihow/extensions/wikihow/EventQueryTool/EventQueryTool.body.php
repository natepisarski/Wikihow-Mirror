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
			ini_set( 'memory_limit', '512M' );
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

		$events = $req->getArray('events', ['all']);
		if ( $events !== ['all'] ) {
			$validEvents = array_keys(EventConfig::EVENTS);
			$events = array_intersect($validEvents, $events); // sanitize
		}
		$dateStart = $dbr->addQuotes( $dtA->format('Y-m-d 00:00:00') );
		$dateEnd = $dbr->addQuotes( $dtB->format('Y-m-d 00:00:00') );

		$groupBy = $req->getArray('groupby', []);
		if ($groupBy) {
			$groupBy = array_merge( ['event'], $groupBy ); // always include the 'event' field
			$validFields = [ 'event', 'date', 'domain', 'page_id', 'screen' ];
			$groupBy = array_intersect($validFields, $groupBy); // sanitize
		}

		// Read events from DB

		$table = 'event_log';
		$fields = [
			'event' => 'el_action',
			'date' => 'date(el_date)',
			'domain' => 'el_domain',
			'page_id' => 'el_page_id',
			'screen' => 'el_screen',
			'params' => 'el_params',
		];
		$where = [ "el_date BETWEEN $dateStart AND $dateEnd" ];
		if ( ! in_array('all', $events) ) {
			$where['el_action'] = $events;
		}

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
			$paramNames = []; // results are being aggregated, so we won't show any extra params
		} else {
			$opts = [ 'ORDER BY' => 'date, domain, page_id, screen' ];
			$eventConf = EventConfig::EVENTS[$event] ?? [];
			$paramNames = $eventConf[1] ?? [];
		}

		$rows = $dbr->select($table, $fields, $where, __METHOD__, $opts);
		$pages = isset($fields['page_id']) ? $this->getPages($where) : [];

		// Determine the CSV headers

		unset($fields['params']); // JSON properties are unpacked into CSV columns
		$colNames = array_keys($fields);
		$headers = $colNames;
		if ($pages) {
			$headers[] = 'orig_url';
		}
		$headers = array_merge($headers, $paramNames);

		// Assemble the CSV lines

		$fname = "events." . implode('+', $events) . '.' . $dtA->format('ymd') . '-' . $dtB->format('ymd') . '.csv';
		$fp = fopen( FileUtil::getPath($fname), 'w' );
		fputcsv($fp, $headers);
		foreach ($rows as $r) {
				$line = [];
			foreach ($colNames as $colName) {
				$line[] = $r->$colName;
			}
			if ($pages) {
				$path = $pages[$r->page_id] ?? null;
				$line[] = $path ? "/{$path}" : '';
			}
			if ($paramNames) {
				$params = json_decode($r->params, true);
				foreach ($paramNames as $paramName) {
					$line[] = $params[$paramName] ?? null;
				}
			}
			fputcsv($fp, $line);
		}
		fclose($fp);

		// Download the CSV file

		FileUtil::streamFile($fname, 'text/csv');
		FileUtil::deleteFile($fname);
	}

	private function getPages(array $where): array {
		if ( Misc::isIntl() ) {
			return [];
		}
		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['event_log', 'page'];
		$fields = ['el_page_id' => 'DISTINCT(el_page_id)', 'page_title'];
		$opts = [];
		$join = [ 'page' => [ 'LEFT JOIN', [ 'el_page_id = page_id' ] ] ];
		$rows = $dbr->select($tables, $fields, $where, __METHOD__, $opts, $join);

		$pages = [];
		foreach ($rows as $r) {
			$pages[$r->el_page_id] = $r->page_title;
		}
		return $pages;
	}
}
