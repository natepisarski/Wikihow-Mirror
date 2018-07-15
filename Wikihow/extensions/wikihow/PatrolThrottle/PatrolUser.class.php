<?php
/**
 * PatrolUser class
 * Represents an individual recent changes patroller
 * Provides access to methods for setting and changing their thresholds (max patrols per day)
 * Provides access to output HTML to show message if the threshold has been reached
 *
 * @author Lojjik Braughler <llbraughler@gmail.com>
 * 7/22/2014
 * @package PatrolThrottle
 * @ingroup Extensions
 */
class PatrolUser {
	private $mUser, $mUserInfo, $mCacheKey;
	const LIMITS_TABLE = 'patrol_limits';
	const CACHE_EXPIRY = 43200; // memcache TTL (secs)
	const DEFAULT_LIMIT_EXPIRY = 30; // max age of a throttle (days)

	private function __construct( User $user ) {
		$this->mUser = $user;
		$this->mUserInfo = false;
		$this->mCacheKey = wfMemcKey( 'patrolthrottle', 'user', $user->getId() );
	}

	/**
	 * Factory method to create a PatrolUser object (representing an individual recent changes
	 * patroller)
	 * Used primarily to check whether the user has a limit and whether we should be throttling them
	 *
	 * @param User $user
	 *        	- user who is patrolling recent changes
	 * @return PatrolUser object
	 */
	public static function newFromUser( User $user ) {
		return new PatrolUser( $user );
	}

	/**
	 * Determines whether this user is being throttled.
	 * If they are, but it's expired, we remove the limit
	 *
	 * @return bool - whether the user has a throttle applied
	 */
	public function isLimited() {
		$userInfo = $this->fetchInformation();

		if ( $userInfo['limit'] == 0 ) {
			return false; // quick exit in case this user does not have a limit
		}
		$autoExpiryDays = wfMessage( 'patrolthrottle-auto-expiry-age ' )->plain();
		$currentTime = wfTimestamp();
		$entryTime = wfTimestamp( TS_UNIX, $userInfo['timestamp'] );

		if ( !is_numeric( $autoExpiryDays ) ) { // didn't look like a number
			$autoExpiryDays = self::DEFAULT_LIMIT_EXPIRY;
		} else {
			$autoExpiryDays = (int)$autoExpiryDays;
		}

		$maxAge = $autoExpiryDays * 86400;

		if ( $currentTime - $entryTime > $maxAge ) {
			$this->removeLimit();
			return false; // their limit expired, let them go
		} else {
			return true; // their limit did not expire
		}
	}

	/**
	 * Get this user's patrol limit
	 *
	 * @return int - the user's patrol limit (or 0, if none is set)
	 */
	public function getLimit() {
		$information = $this->fetchInformation();
		return $information['limit'];
	}
	/**
	 * Set this user's patrol limit & recache their information
	 */
	public function setLimit( $limit ) {
		if ( $limit < 0 || ( $limit >= 1 && $limit <= 9 ) || $limit > 9999 ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );
		$result = $dbw->replace( self::LIMITS_TABLE, array(
			'pl_user_id'
		), array(
			'pl_user_id' => $this->mUser->getId(),
			'patrol_limit' => $limit,
			'patrol_added_date' => $dbw->timestamp()
		), __METHOD__ );

		$this->recache();
	}

	/**
	 * Remove this patroller's limit
	 */
	public function removeLimit() {
		$this->setLimit( 0 );
	}

	/**
	 * Fetches the daily patrol count from the database
	 */
	private function getDailyPatrolCount() {
		$ts1 = $this->getMidnightTimestamp();
		$ts2 = $ts1 + 86400;

		return $this->getPatrolCount( wfTimestamp( TS_MW, $ts1 ), wfTimestamp( TS_MW, $ts2 ) );
	}

	/**
	 * Gets the patrol count between two timestamp ranges
	 *
	 * @param bool|string $fromTimestamp
	 *        	- earliest date to search from
	 * @param bool|string $maxTimestamp
	 *        	- latest date to search to
	 */
	private function getPatrolCount( $fromTimestamp = false, $maxTimestamp = false ) {
		$dbr = wfGetDB( DB_SLAVE );
		$conds = array(
			'log_type' => 'patrol',
			'log_user' => $this->mUser->getId()
		);

		if ( $fromTimestamp ) {
			$conds = array_merge( $conds, array(
				'log_timestamp > ' . $dbr->addQuotes( $fromTimestamp )
			) );
		}
		if ( $maxTimestamp ) {
			$conds = array_merge( $conds, array(
				'log_timestamp < ' . $dbr->addQuotes( $maxTimestamp )
			) );
		}

		return $dbr->selectField( 'logging', 'COUNT(*)', $conds, __METHOD__ );
	}

	/**
	 * Finds the timestamp of the last midnight to occur in this user's timezone
	 * Takes their timezone preference (if they have one), otherwise defaults to GMT
	 *
	 * @return int $today - UNIX timestamp of the last midnight to occur for this user
	 */
	public function getMidnightTimestamp() {
		$timePreference = $this->mUser->getOption( 'timecorrection' );

		if ( !stripos( $timePreference, '|' ) ) {
			return mktime( 0, 0, 0 ); // No timezone setting in preferences, fall back to GMT
		}

		$timeInformation = explode( '|', $timePreference );
		$loc = array_search( 'ZoneInfo', $timeInformation );

		if ( $loc !== false ) {
			// Yay, we have a timezone identifier so we don't need to calculate it ourselves
			$timezone = $timeInformation[$loc + 2];
			$date = new DateTime( 'midnight', new DateTimeZone( $timezone ) );
			$today = $date->getTimestamp();
		} else {
			// Oh, boo. Check if we have an offset to calculate from.
			$loc = array_search( 'Offset', $timeInformation );
			if ( $loc !== false ) {
				$minutesOffset = (int)($timeInformation[$loc + 1]);
				$today = PatrolCount::getLastMidnightForOffset( $minutesOffset );
			} else {
				// Unable to determine timezone information. Falling back to GMT
				$today = mktime( 0, 0, 0 );
			}
		}

		return $today;
	}

	/**
	 * Fetches timestamp the user's patrol limit was added/modified
	 * Fetches the number of patrols (daily and overall) by the user
	 *
	 * @return array( 'timestamp' => string, 'limit' => integer )
	 */
	private function fetchInformation() {
		// if the page was already loaded, get the information we already have
		// rather than begging memcache for it
		if ( is_array( $this->mUserInfo ) ) {
			wfDebugLog( 'PatrolThrottle', 'mUserInfo cache hit: Already loaded in page.' );
			return $this->mUserInfo;
		}

		$cache = wfGetCache( CACHE_ANYTHING );
		$userId = $this->mUser->getId();
		$userInfo = $cache->get( $this->mCacheKey );
		$midnight = $this->getMidnightTimestamp();

		if ( $userInfo && $userInfo['midnight'] < $midnight ) {
			wfDebugLog( 'PatrolThrottle', 'last midnight mismatch, forcing recache for ' . $this->mUser->getName() );
			$userInfo = false; // force recaching of patrol count information if the day rolled over
		}

		if ( !is_array( $userInfo ) ) {
			wfDebug( __METHOD__ . " cache miss for $userId\n" );
			$userInfo = array();
			$dbr = wfGetDB( DB_SLAVE );
			$result = $dbr->select( array(
				self::LIMITS_TABLE
			), array(
				'patrol_added_date',
				'patrol_limit'
			), array(
				'pl_user_id' => $userId
			), __METHOD__ );

			$row = $result->fetchObject();

			if ( $result->numRows() < 1 ) {
				wfDebugLog( 'PatrolThrottle', 'User not on throttle list, setting values to 0.' );
				// user not on throttle list
				$userInfo['timestamp'] = 0;
				$userInfo['limit'] = 0;
				// not important to keep an accurate patrol count for non-throttled users, so start from 0
				$userInfo['daily'] = 0;
			} else {
				// user is on throttle list
				$userInfo['timestamp'] = $row->patrol_added_date;
				$userInfo['limit'] = (int)$row->patrol_limit;
				$userInfo['daily'] = $this->getDailyPatrolCount();
			}
			$userInfo['midnight'] = $midnight;
			$cache->set( $this->mCacheKey, $userInfo, self::CACHE_EXPIRY );
			wfDebugLog( 'PatrolThrottle', 'Fetched new information for ' . $this->mUser->getName() . ' from the db' );
		}

		$this->mUserInfo = $userInfo;
		return $userInfo;
	}

	/**
	 * Add a log event of a throttle being cleared/removed
	 *
	 * @param User $admin
	 *        	- the admin removing the limit
	 * @param User $user
	 *        	- the patroller whose limit is being removed
	 */
	public function logThrottleRemove( $admin, $user ) {
		$logEntry = new ManualLogEntry( 'throttle', 'removed' );
		$logEntry->setPerformer( $admin );
		$logEntry->setTarget( $user->getUserpage() );
		$logEntry->insert();
	}

	/**
	 * Add a log event of a throttle being changed
	 *
	 * @param User $admin
	 *        	- the admin changing the limit
	 * @param int $oldLimit
	 *        	- the previous set limit
	 * @param int $newLimit
	 *        	- the new limit being placed
	 * @param User $user
	 *        	- the patroller being throttled
	 * @return none
	 */
	public function logThrottleChanged( $admin, $oldLimit, $newLimit, $user ) {
		$logEntry = new ManualLogEntry( 'throttle', 'changed' );
		$logEntry->setPerformer( $admin );
		$logEntry->setTarget( $user->getUserpage() );
		$logEntry->setParameters( array(
			'4::old' => $oldLimit,
			'5::new' => $newLimit
		) );
		$logEntry->insert();
	}

	/**
	 * Add a log event of a throttle creation
	 *
	 * @param User $admin
	 *        	- the admin setting the limit
	 * @param int $limit
	 *        	- the limit being set on the patroller
	 * @param User $user
	 *        	- the patroller being throttled
	 * @return none
	 */
	public function logThrottleAdd( $admin, $limit, $user ) {
		$logEntry = new ManualLogEntry( 'throttle', 'added' );
		$logEntry->setPerformer( $admin );
		$logEntry->setTarget( $user->getUserpage() );
		$logEntry->setParameters( array(
			'4::limit' => $limit
		) );
		$logEntry->insert();
	}

	/**
	 * Clears cached PatrolUser information
	 * Called upon setting a user limit
	 */
	public function recache() {
		wfGetCache( CACHE_ANYTHING )->delete( $this->mCacheKey );
	}

	/**
	 * Fetch the list of limited patrollers - that is, all users with limits greater than zero
	 *
	 * @return associative array with keys corresponding to field information:
	 *         name - plaintext username (string)
	 *         limit - integer patrol limit (int)
	 *         today - number of patrols done by the user today (int)
	 *         total - total number of all patrols done by that user (int)
	 *         added - a human-readable timestamp detailing what time the entry was added/changed (string)
	 */
	public static function getLimitedPatrollers( $limit = 10, $offset = 0 ) {

		$maxTTL = self::DEFAULT_LIMIT_EXPIRY * 86400;
		$earliestTime = wfTimestamp( TS_MW, time() - $maxTTL );
		$language = wfGetLangObj( true );

		$dbr = wfGetDB( DB_SLAVE );
		$result = $dbr->select( array(
			self::LIMITS_TABLE
		), array(
			'patrol_limit',
			'pl_user_id',
			'patrol_added_date'
		), array(
			'patrol_limit > 0',
		), __METHOD__, array(
			'ORDER BY' => 'patrol_added_date DESC',
			'LIMIT' => $limit,
			'OFFSET' => $offset
		) );

		$patrollers = array();

		foreach ( $result as $row ) {
			$user = User::newFromId( $row->pl_user_id );
			$name = $user->getName();
			$userpage = $user->getUserPage();
			$patroller = self::newFromUser( $user );

			$logPage = SpecialPage::getTitleFor( 'Log' );
			$dailyPatrols = $patroller->getDailyCached();
			$totalPatrols = $patroller->getPatrolCount();

			if ( $dailyPatrols >= $row->patrol_limit ) {
				$class = 'approaching';
			} else {
				$class = false;
			}

			if ( (int)$row->patrol_added_date < $earliestTime ) {
				$patroller->recache();
				continue;
			}

			$patrollers[] = array(
				'name' => Linker::link( $userpage, $name, array(
					'class' => $class
				) ),
				'limit' => $row->patrol_limit,
				'today' => Linker::link( $logPage, $dailyPatrols, array(
					'class' => $class
				), array(
					'type' => 'patrol',
					'user' => $name
				) ),
				'total' => $language->formatNum( $totalPatrols ),
				'added' => $language->timeanddate( $row->patrol_added_date, true )
			);
		}

		return $patrollers;
	}

	/**
	 * Hook for MarkPatrolledBatchComplete
	 * RC patrol app should call wfRunHooks('MarkPatrolledBatchComplete', ...)
	 */
	public static function onMarkPatrolledBatchComplete(&$article, &$rcids, &$user) {
		if ( $user instanceof User ) {
			$patroller = PatrolUser::newFromUser( $user );
			$patroller->incrementDailyPatrols( count( $rcids ) );
		}
		return true;
	}

	/**
	 * Increment the daily patrol count for this user
	 * So we don't have to keep querying the logging table
	 * @param int $increment (how much to increase the count)
	 */
	public function incrementDailyPatrols( $increment = 1 ) {
		$cache = wfGetCache( CACHE_ANYTHING );
		$information = $this->fetchInformation();
		$information['daily']++;
		$cache->set( $this->mCacheKey, $information, self::CACHE_EXPIRY );
		wfDebugLog( 'PatrolThrottle', 'Incrementing daily patrol count by ' . $increment. ' for ' . $this->mUser->getName() );
	}

	/**
 	* Automatically fetches the daily patrol count using the most accessible means (property, cache, or query)
 	* @return int
 	*/
	function getDailyCached() {
		wfDebugLog( 'PatrolThrottle', 'Attempting to fetch the cached daily count value for ' . $this->mUser->getName() );
		$information = $this->fetchInformation();
		wfDebugLog( 'PatrolThrottle', 'Daily count: ' . $information['daily'] . '/' . $information['limit'] );
		return $information['daily'];
	}

	/**
	 * Determines whether or not the user has reached their patrol limit threshold
	 *
	 * @param bool $alreadyLoadedRC
	 *        	- whether we are checking the limit within RC after we already loaded it
	 */
	private function thresholdReached( $alreadyLoadedRC ) {
		$limit = $this->getLimit();

		if ( $alreadyLoadedRC ) {
			// We check for the limit after they mark as patrolled (and they were already in RC).
			// So, show the message before they exceed the limit.
			wfDebugLog( 'PatrolThrottle', 'User is in RC, decrementing limit.' );
			$limit--;
		}
		return $this->getDailyCached() >= $limit;
	}

	/**
	 * Fetches the HTML for the throttle message
	 * Call this from RC Patrol to output the message once they've hit their limit (check using canUseRCPatrol)
	 * @return string $html - contains the HTML to be output to the user if they are throttled
	 */

	public static function getThrottleMessageHTML($desktop = true) {
		$html = Html::openElement( 'div', array(
			'id' => 'bodycontents',
			'class' => 'minor_section bc_view'
		) );
		$msg = $desktop ? 'patrolthrottle-hit-message' : 'patrolthrottle-hit-message-mobile';
		$html .= wfMessage( $msg )->parse();
		$html .= Html::closeElement( 'div' );
		return $html;
	}

	/**
	 * Checks whether or not the user is allowed to continue using RC Patrol
	 * If they don't have a limit, they're allowed.
	 * If they do, they can as long as they haven't reached the threshold.
	 *
	 * @param $alreadyLoadedRC -
	 *        	whether or not we're checking from within RC patrol after we already loaded the
	 *        	diffs
	 */
	public function canUseRCPatrol( $alreadyLoadedRC ) {
		if ( !$this->isLimited() ) {
			return true;
		} else {
			return !$this->thresholdReached( $alreadyLoadedRC );
		}
	}
}
