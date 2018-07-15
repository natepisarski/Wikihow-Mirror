<?php
/**
 * PatrolCount extension
 * Shows the number and percentage of patrols done in the past day by the top 20 users
 */

class PatrolCount extends SpecialPage {

	private $tzFlags;
	private $tzOffset;
	private $timeRange;
	private $totalCount;

	const CACHE_TTL = 1200; // 20 minutes
	const MAX_PATROLLERS = 20;
	const TZ_GMT = 0;
	const TZ_LOCAL = 1;

	public function __construct() {
		parent::__construct( 'PatrolCount' );
		$this->tzFlags = 0;
		$this->tzOffset = 'GMT';
		$this->timeRange = new stdClass();
		$this->totalCount = 0;
	}

	public function execute( $param ) {
		$this->setHeaders();
		$this->setTimezone( $param );
		$this->setTotalCount();
		$this->show();
	}

	// Set the section in Special:SpecialPages
	public function getGroupName() {
		return 'changes';
	}

	/**
	 * Gets the correct range of timestamps from midnight to midnight for the current user's timezone
	 * Timestamps are returned in MediaWiki format (YmdHis)
	 * @return array( timestamp1, timestamp2)
	 */
	public static function getPatrolcountWindow( $user = null, $duration = 86400 ) {

		if ( $user === null || !$user instanceof User || !$user->getId() ) {
			$user = RequestContext::getMain()->getUser();
		}

		$timePreference = $user->getOption( 'timecorrection', false );

		if ( $timePreference === false || !strpos( $timePreference, '|' ) ) {
			return self::GMTDateRange( $duration );
		}

		$timeInformation = explode( '|', $timePreference );
		$loc = array_search( 'ZoneInfo', $timeInformation );

		if ( $loc !== false ) {
			// We have a timezone identifier so we don't need to calculate it ourselves :)
			$timezone = $timeInformation[$loc + 2];
			$date = new DateTime( 'midnight', new DateTimeZone( $timezone ) );
			$today = $date->getTimestamp();
		} else {
			// We have an offset to work with, so let's calculate their last midnight
			$loc = array_search( 'Offset', $timeInformation );
			if ( $loc !== false ) {
				$minutesOffset = (int)($timeInformation[$loc + 1]);
				$today = self::getLastMidnightForOffset( $minutesOffset );
			} else {
				// We were unable to find the timezone using Offset or ZoneInfo
				return self::GMTDateRange( $duration );
			}
		}

		$next = $today + $duration;
		$result[] = wfTimestamp( TS_MW, $today );
		$result[] = wfTimestamp( TS_MW, $next );
		$result[] = $timePreference;

		return $result;
	}

	/**
	 * Return the midnight timestamp range for GMT timezone (server default) in MediaWiki format
	 * @return array( timestamp1, timestamp2 )
	 */
	public static function GMTDateRange( $duration = 86400 ) {
		$today = mktime( 0, 0, 0 );
		$next = $today + $duration;

		$result = array();
		$result[] = wfTimestamp( TS_MW, $today );
		$result[] = wfTimestamp( TS_MW, $next );
		$result[] = 'GMT';

		return $result;
	}

	/**
	 * Gets the UNIX timestamp for the last midnight to occur at a given GMT offset
	 * Used when we don't have a timezone specifier and only an offset in minutes
	 * @return int $localMidnight UNIX timestamp representing the last midnight for that offset
	 */
	public static function getLastMidnightForOffset( $minuteOffset ) {
		$today = mktime( 0, 0, 0 );
		$yesterday = $today - 86400;
		$tomorrow = $today + 86400;

		$offset = $minuteOffset * 60;
		$curTime = time();

		if ( $curTime + $offset >= $tomorrow ) {
			$localMidnight = $tomorrow - $offset;
		} elseif ( $curTime + $offset >= $today ) {
			$localMidnight = $today - $offset;
		} else {
			$localMidnight = $yesterday - $offset;
		}

		return $localMidnight;
	}

	private function comparePatrols($user1, $user2) {
		if ( $user1->patrols === $user2->patrols ) {
			return 0;
		}

		return ( $user1->patrols < $user2->patrols ) ? 1 : -1;
	}

	/**
	 * Get the rankings table, from cache if it hasn't expired yet.
	 */
	private function getUserPatrols() {
		$cache = wfGetCache( CACHE_ANYTHING );
		$cachedUsers = $cache->get( wfMemcKey( 'patrolcount', 'users', $this->tzOffset ) );

		if ( is_array( $cachedUsers ) ) {
			return $cachedUsers;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( array( 'logging' ),
			array( 'log_user_text AS username', 'COUNT(*) AS patrols' ),
			array(  'log_type' => 'patrol',
					'log_timestamp > ' . $dbr->addQuotes( $this->timeRange->min ),
					'log_timestamp < ' . $dbr->addQuotes( $this->timeRange->max ),
					'log_user > 0'),
			__METHOD__,
			array( 	'GROUP BY' => 'log_user',
					'LIMIT' => self::MAX_PATROLLERS )
		);

		// copying into array of objects because we can't store the result pointer in cache
		$users = array();

		foreach( $res as $row ) {
			$user = new stdClass();
			$user->username = $row->username;
			$user->patrols = $row->patrols;
			array_push( $users, $user );
		}

		unset( $row );
		usort( $users, 'self::comparePatrols' );
		$cache->set( wfMemcKey( 'patrolcount', 'users', $this->tzOffset ), $users, self::CACHE_TTL );
		return $users;
	}

	private function setTimezone( $timezone ) {
		$user = $this->getUser();

		// If a timezone was specified, we'll use that.
		// Otherwise, use the setting they have in their preferences, falling back to GMT if not set
		if ( empty( $timezone ) ) {
			$timezonePreference = $user->getIntOption( 'patrolcountlocal', self::TZ_GMT );
		} else {
			// save their settings so we'll open up in this mode next time
			$timezonePreference = ( $timezone === 'GMT' ) ? self::TZ_GMT : self::TZ_LOCAL;
			$user->setOption( 'patrolcountlocal', $timezonePreference );
			$user->saveSettings();
		}

		$this->tzFlags = $timezonePreference;

		// Set the min, max date range for this timezone
		$this->setTimeBounds();

	}

	private function setTimeBounds() {
		$timeRange = new stdClass();

		if ( $this->tzFlags & self::TZ_LOCAL ) {
			list( $this->timeRange->min, $this->timeRange->max, $this->tzOffset ) = self::getPatrolcountWindow( $this->getUser(), 86400 );
		} else {
			list( $this->timeRange->min, $this->timeRange->max, $this->tzOffset ) = self::GMTDateRange( 86400 );
		}
	}

	/**
	 * Gets the total number of patrols to occur between two timestamps
	 */
	private function setTotalCount() {
		// Fetch the cached count for this timezone, if there is one available
		$cache = wfGetCache( CACHE_ANYTHING );
		$cachedCount = $cache->get( wfMemcKey( 'patrolcount', 'totalcount', $this->tzOffset ) );

		if ( $cachedCount !== false ) {
			$this->totalCount = $cachedCount;
			return;
		}

		$dbr = wfGetDB( DB_SLAVE );
		$total = $dbr->selectField( 'logging', 'COUNT(*)',
			array(
					'log_type' => 'patrol',
					'log_timestamp > ' . $dbr->addQuotes( $this->timeRange->min ),
					'log_timestamp < ' . $dbr->addQuotes( $this->timeRange->max )
			), __METHOD__ );

		$cache->set( wfMemcKey( 'patrolcount', 'totalcount', $this->tzOffset ), $total, self::CACHE_TTL );
		$cache->set( wfMemcKey( 'patrolcount', 'lastrefresh', $this->tzOffset ), wfTimestamp( TS_MW ), self::CACHE_TTL );
		$this->totalCount = $total;
	}

	private function show() {
		$output = $this->getOutput();
		$output->addModuleStyles( 'ext.wikihow.PatrolCount' );

		$this->showHeader();
		$this->showTable();
		$this->showFooter();
	}

	private function getTZLinks() {

		if ( $this->tzFlags & self::TZ_LOCAL ) {
			$gmtLink = Linker::linkKnown( $this->getPageTitle( 'GMT'), $this->msg( 'patrolcount_viewGMT' ) );
			$localLink = $this->msg( 'patrolcount_viewlocal' );
		} else {
			$gmtLink = $this->msg( 'patrolcount_viewGMT' );
			$localLink = Linker::linkKnown( $this->getPageTitle( 'local' ), $this->msg( 'patrolcount_viewlocal' )->parse() );
		}

		$links = "[$gmtLink] [$localLink]";

		return $links;

	}

	/**
	 * Adds a new row to the Patrolcount table
	 *
	 * @param int $index - listed next to the row, shows which row number we are on
	 * @param string $user - plaintext username
	 * @param string $patrols - a formatted number representing how many patrols a user did in a time period
	 * @param string $percentage - a number (with % sign) representing percentage of the patrols done in that day
	 */

	private function addRow( $index, $user, $patrols, $percentage ) {
		$output = $this->getOutput();
		$row = array();

		$class = ( empty( $index ) || !( $index & 1 ) ) ? 'even' : 'odd';
		$row[] = Html::openElement( 'tr', array( 'class' => $class ) );
		$row[] = Html::openElement( 'td' ) . ( $index ) . Html::closeElement( 'td' );
		$row[] = Html::openElement( 'td', array( 'align' => 'right' ) ) . ( $user ) . Html::closeElement( 'td' );
		$row[] = Html::openElement( 'td', array( 'align' => 'right' ) ) . ( $patrols ) . Html::closeElement( 'td' );
		$row[] = Html::openElement( 'td', array( 'align' => 'right' ) ) . ( $percentage ) . Html::closeElement( 'td' );
		$row[] = Html::closeElement( 'tr' );

		$output->addHTML( implode( $row ) );
	}

	private function getLastRefresh() {
		$value = wfGetCache( CACHE_ANYTHING )->get( wfMemcKey( 'patrolcount', 'lastrefresh', $this->tzOffset ) );
		if ( $value ) {
			return $value;
		} else {
			return wfTimestamp( TS_MW );
		}
	}

	private function showHeader() {
		$output = $this->getOutput();
		$output->addHTML( Html::openElement( 'div', array( 'id' => 'Patrolcount' ) ) );
		$output->addWikiText( $this->msg( 'patrolcount_summary' )->plain() );
		$output->addWikiText( $this->msg( 'patrolcount_total' )->numParams( $this->totalCount )->plain() );
		// When was the information last updated?
		// you pass true as last param to timeanddate() to put it in user's timezone, false for GMT
		// so instead of an if statement here, we can just quickly cast our timezone setting to bool
		$output->addWikiText( $this->msg( 'patrolcount_refresh', $this->getLanguage(true)->timeanddate( $this->getLastRefresh(), (bool)$this->tzFlags ) ) );
		$output->addHTML( Html::openElement( 'div', array( 'class' => 'center' ) ) );
		$output->addHTML( $this->getTZLinks() );
	}

	private function openTable() {
		$output = $this->getOutput();
		$output->addHTML( Html::openElement( 'table' ) );
	}

	private function closeTable() {
		$output = $this->getOutput();
		$output->addHTML( Html::closeElement( 'table' ) );
	}

	private function showTable() {
		// don't show the table if there were no patrols today
		if ( $this->totalCount === 0 ) {
			return;
		}

		$this->openTable();
		$this->addRow( '', $this->msg( 'patrolcount_user' ), $this->msg( 'patrolcount_numberofeditspatrolled' )->plain(),
			$this->msg( 'patrolcount_percentageheader' ) );

		$users = $this->getUserPatrols();
		$index = 1;

		foreach ( $users as $row ) {
			$u = User::newFromName( $row->username );

			if ( !$u instanceof User || !$u->getId() ) {
				continue;
			}

			// skip bots
			if ( in_array( 'bot', $u->getGroups() ) ) {
				continue;
			}
			$count = $this->getLanguage()->formatNum( $row->patrols );
			$percent = round( $row->patrols / $this->totalCount * 100, 2 ) . '%';

			$logPage = Linker::link( SpecialPage::getTitleFor( 'Log' ), $count, array(),
				array(
						'type' => 'patrol',
						'user' => $row->username
				) );
			$userPage = Linker::link( $u->getUserPage(), $row->username );
			$this->addRow( $index, $userPage, $logPage, $percent );
			$index++;
		}

		$this->closeTable();
	}

	private function getFooter() {
		$message = $this->msg( 'patrolcount_viewlocal_info' );
		$span = Html::openElement( 'span', array( 'class' => 'viewlocal' ) ) . $message . Html::closeElement( 'span' );
		return $span;
	}

	function showFooter() {
		$output = $this->getOutput();

		if ( $this->tzFlags & self::TZ_LOCAL ) {
			$output->addHTML( $this->getFooter() );
		}

		$output->addHTML( Html::closeElement( 'div' ) );
		$output->addHTML( Html::closeElement( 'div' ) );
	}
}
