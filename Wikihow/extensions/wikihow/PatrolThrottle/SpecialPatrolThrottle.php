<?php
/**
 * SpecialPatrolThrottle
 * This class manages the Patrol Throttle entry form/list
 *
 * @author Lojjik Braughler
 */
class SpecialPatrolThrottle extends SpecialPage {
	// Maximum # of patrollers to show at a time below the form
	const LIST_SIZE = 100;

	function __construct() {
		parent::__construct( 'PatrolThrottle' );
	}

	function execute( $param ) {
		$user = $this->getUser();
		$output = $this->getOutput();

		if ( !$user->isAllowed( 'patrolthrottle' ) || $user->isBlocked() ) {
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$this->setHeaders();
		$output->addModules( 'ext.wikihow.PatrolThrottle' );

		// page title different from Special:Specialpages description
		$output->setPageTitle( $this->msg( 'patrolthrottle-title' ) );

		if ( $this->getRequest()->wasPosted() ) {
			$this->processForm();
		} else {
			$this->showForm();
		}
	}

	function processForm() {
		$errors = array();
		$request = $this->getRequest();
		$username = $request->getVal( 'wpPatroller' );
		$limit = $request->getVal( 'wpLimit' );

		$username = filter_var( $username, FILTER_SANITIZE_STRING );
		$user = User::newFromName( $username );

		if ( !$user instanceof User || !$user->getId() ) {
			$errors[] = $this->msg( 'patrolthrottle-error-no-user' );
		} elseif ( !is_numeric( $limit ) || $limit < 0 || ( $limit >= 1 && $limit <= 9 ) || $limit > 9999 ) {
			$errors[] = $this->msg( 'patrolthrottle-error-number' );
		} else {
			$limit = $request->getInt( 'wpLimit' );
			$patroller = PatrolUser::newFromUser( $user );
			$curLimit = $patroller->getLimit();

			if ( $curLimit === $limit ) {
				$errors[] = $this->msg( 'patrolthrottle-error-nodifference' );
				$this->showForm( $errors );
				return;
			}

			if ( $limit === 0 && $curLimit > 0 ) { // they had a limit and now we're setting it to 0
				PatrolUser::logThrottleRemove( $this->getUser(), $user );
			} elseif ( $limit > 0 && $curLimit > 0 ) { // they already have a limit so we're just changing it
				PatrolUser::logThrottleChanged( $this->getUser(), $curLimit, $limit, $user );
			} elseif ( $limit > 0 ) { // must be creating a new one
				PatrolUser::logThrottleAdd( $this->getUser(), $limit, $user );
			}

			$patroller->setLimit( $limit );
		}

		$this->showForm( $errors );
	}

	function showForm( $errors = array() ) {
		// calculate what offset we should link to (to go back to previous pages)
		// if false, the text won't be made into a link
		if ( $this->getRequest()->getInt( 'ptfrom' ) ) {
			$offsetFrom = $this->getRequest()->getInt( 'ptfrom' );
		} else {
			$offsetFrom = 0;
		}

		if ( $offsetFrom >= self::LIST_SIZE ) {
			$prevOffset = $offsetFrom - self::LIST_SIZE;
		} else {
			$prevOffset = 0;
		}

		$patrollers = PatrolUser::getLimitedPatrollers( self::LIST_SIZE, $offsetFrom );
		$numEntries = count( $patrollers );

		// If we have less than LIST_SIZE results,
		// then we're at the end of the list and don't need to create a link
		$nextOffset = $offsetFrom + self::LIST_SIZE; // default, unless something causes it to be false

		if ( $numEntries < self::LIST_SIZE ) {
			$nextOffset = false;
		} elseif ( $numEntries === self::LIST_SIZE ) {
			// check if the next page actually has any on the list, if not, we won't show the link
			// case where number of patrollers is evenly divisible by LIST_SIZE
			$numNext = count( PatrolUser::getLimitedPatrollers( self::LIST_SIZE, $nextOffset ) );
			if ( $numNext === 0 ) {
				$nextOffset = false;
			}
		}

		$template = new PatrolThrottleUITemplate();
		$template->setRef( 'specialpage', $this );
		$template->set( 'edittoken', $this->getUser()->getEditToken() );
		$template->set( 'submit_label', $this->msg( 'patrolthrottle-button-apply' ) );
		$template->set( 'patrollers', $patrollers );
		$template->set( 'errors', $errors );
		$template->set( 'current', $offsetFrom );
		$template->set( 'prev', $prevOffset );
		$template->set( 'next', $nextOffset );
		$this->getOutput()->addTemplate( $template );
	}
}
