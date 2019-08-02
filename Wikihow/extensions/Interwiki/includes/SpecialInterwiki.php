<?php

use MediaWiki\MediaWikiServices;

/**
 * Implements Special:Interwiki
 * @ingroup SpecialPage
 */
class SpecialInterwiki extends SpecialPage {
	/**
	 * Constructor - sets up the new special page
	 */
	public function __construct() {
		parent::__construct( 'Interwiki' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Different description will be shown on Special:SpecialPage depending on
	 * whether the user can modify the data.
	 * @return String
	 */
	public function getDescription() {
		return $this->msg( $this->canModify() ?
			'interwiki' : 'interwiki-title-norights' )->plain();
	}

	public function getSubpagesForPrefixSearch() {
		// delete, edit both require the prefix parameter.
		return [ 'add' ];
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par parameter passed to the page or null
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();
		$request = $this->getRequest();

		$out->addModuleStyles( 'ext.interwiki.specialpage' );

		$action = $par ?: $request->getVal( 'action', $par );

		$action = $par ?: $request->getVal( 'action', $par );
		if ( !in_array( $action, [ 'add', 'edit', 'delete' ] ) || !$this->canModify( $out ) ) {
			$this->showList();
		} else {
			$this->showForm( $action );
		}
	}

	/**
	 * Returns boolean whether the user can modify the data.
	 * @param OutputPage|bool $out If $wgOut object given, it adds the respective error message.
	 * @throws PermissionsError|ReadOnlyError
	 * @return bool
	 */
	public function canModify( $out = false ) {
		global $wgInterwikiCache;
		if ( !$this->getUser()->isAllowed( 'interwiki' ) ) {
			// Check permissions
			if ( $out ) {
				throw new PermissionsError( 'interwiki' );
			}

			return false;
		} elseif ( $wgInterwikiCache ) {
			// Editing the interwiki cache is not supported
			if ( $out ) {
				$out->addWikiMsg( 'interwiki-cached' );
			}

			return false;
		} elseif ( wfReadOnly() ) {
			throw new ReadOnlyError;
		}

		return true;
	}

	/**
	 * @param string $action The action of the form
	 */
	protected function showForm( $action ) {
		$formDescriptor = [];
		$hiddenFields = [
			'action' => $action,
		];

		$status = Status::newGood();
		$request = $this->getRequest();
		$prefix = $request->getVal( 'prefix', $request->getVal( 'hiddenPrefix' ) );

		switch ( $action ) {
			case 'add':
			case 'edit':
				$formDescriptor = [
					'prefix' => [
						'type' => 'text',
						'label-message' => 'interwiki-prefix-label',
						'name' => 'prefix',
					],

					'local' => [
						'type' => 'check',
						'id' => 'mw-interwiki-local',
						'label-message' => 'interwiki-local-label',
						'name' => 'local',
					],

					'trans' => [
						'type' => 'check',
						'id' => 'mw-interwiki-trans',
						'label-message' => 'interwiki-trans-label',
						'name' => 'trans',
					],

					'url' => [
						'type' => 'url',
						'id' => 'mw-interwiki-url',
						'label-message' => 'interwiki-url-label',
						'maxlength' => 200,
						'name' => 'wpInterwikiURL',
						'size' => 60,
						'tabindex' => 1,
					],

					'reason' => [
						'type' => 'text',
						'id' => "mw-interwiki-{$action}reason",
						'label-message' => 'interwiki_reasonfield',
						'maxlength' => 200,
						'name' => 'wpInterwikiReason',
						'size' => 60,
						'tabindex' => 1,
					],
				];

				break;
			case 'delete':
				$formDescriptor = [
					'prefix' => [
						'type' => 'hidden',
						'name' => 'prefix',
						'default' => $prefix,
					],

					'reason' => [
						'type' => 'text',
						'name' => 'reason',
						'label-message' => 'interwiki_reasonfield',
					],
				];

				break;
		}

		$formDescriptor['hiddenPrefix'] = [
			'type' => 'hidden',
			'name' => 'hiddenPrefix',
			'default' => $prefix,
		];

		if ( $action === 'delete' ) {
			$topmessage = $this->msg( 'interwiki_delquestion', $prefix )->text();
			$intromessage = $this->msg( 'interwiki_deleting', $prefix )->escaped();
			$wpPrefix = Html::hidden( 'wpInterwikiPrefix', $prefix );
			$button = 'delete';
			$formContent = '';
		} elseif ( $action === 'edit' ) {
			$dbr = wfGetDB( DB_REPLICA );
			$row = $dbr->selectRow( 'interwiki', '*', [ 'iw_prefix' => $prefix ], __METHOD__ );

			$formDescriptor['prefix']['disabled'] = true;
			$formDescriptor['prefix']['default'] = $prefix;
			$hiddenFields['prefix'] = $prefix;

			if ( !$row ) {
				$status->fatal( 'interwiki_editerror', $prefix );
			} else {
				$formDescriptor['url']['default'] = $row->iw_url;
				$formDescriptor['url']['trans'] = $row->iw_trans;
				$formDescriptor['url']['local'] = $row->iw_local;
			}
		}

		if ( !$status->isOK() ) {
			$formDescriptor = [];
		}

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->addHiddenFields( $hiddenFields )
			->setSubmitCallback( [ $this, 'onSubmit' ] );

		if ( $status->isOK() ) {
			if ( $action === 'delete' ) {
				$htmlForm->setSubmitDestructive();
			}

			$htmlForm->setSubmitTextMsg( $action !== 'add' ? $action : 'interwiki_addbutton' )
				->setIntro( $this->msg( $action !== 'delete' ? "interwiki_{$action}intro" :
					'interwiki_deleting', $prefix ) )
				->show();
		} else {
			$htmlForm->suppressDefaultSubmit()
				->prepareForm()
				->displayForm( $status );
		}

		$this->getOutput()->addBacklinkSubtitle( $this->getPageTitle() );
	}

	public function onSubmit( array $data ) {
		global $wgContLang;

		$status = Status::newGood();
		$request = $this->getRequest();
		$prefix = $this->getRequest()->getVal( 'prefix', '' );
		$do = $request->getVal( 'action' );
		// Show an error if the prefix is invalid (only when adding one).
		// Invalid characters for a title should also be invalid for a prefix.
		// Whitespace, ':', '&' and '=' are invalid, too.
		// (Bug 30599).
		global $wgLegalTitleChars;
		$validPrefixChars = preg_replace( '/[ :&=]/', '', $wgLegalTitleChars );
		if ( $do === 'add' && preg_match( "/\s|[^$validPrefixChars]/", $prefix ) ) {
			$status->fatal( 'interwiki-badprefix', htmlspecialchars( $prefix ) );
			return $status;
		}
		$reason = $data['reason'];
		$selfTitle = $this->getPageTitle();
		$lookup = MediaWikiServices::getInstance()->getInterwikiLookup();
		$dbw = wfGetDB( DB_MASTER );
		switch ( $do ) {
		case 'delete':
			$dbw->delete( 'interwiki', [ 'iw_prefix' => $prefix ], __METHOD__ );

			if ( $dbw->affectedRows() === 0 ) {
				$status->fatal( 'interwiki_delfailed', $prefix );
			} else {
				$this->getOutput()->addWikiMsg( 'interwiki_deleted', $prefix );
				$log = new LogPage( 'interwiki' );
				$log->addEntry( 'iw_delete', $selfTitle, $reason, [ $prefix ] );
				$lookup->invalidateCache( $prefix );
			}
			break;
		/** @noinspection PhpMissingBreakStatementInspection */
		case 'add':
			$prefix = $wgContLang->lc( $prefix );
		case 'edit':
			$theurl = $data['url'];
			$local = $data['local'] ? 1 : 0;
			$trans = $data['trans'] ? 1 : 0;
			$rows = [
				'iw_prefix' => $prefix,
				'iw_url' => $theurl,
				'iw_local' => $local,
				'iw_trans' => $trans
			];

			if ( $prefix === '' || $theurl === '' ) {
				$status->fatal( 'interwiki-submit-empty' );
				break;
			}

			// Simple URL validation: check that the protocol is one of
			// the supported protocols for this wiki.
			// (bug 30600)
			if ( !wfParseUrl( $theurl ) ) {
				$status->fatal( 'interwiki-submit-invalidurl' );
				break;
			}

			if ( $do === 'add' ) {
				$dbw->insert( 'interwiki', $rows, __METHOD__, 'IGNORE' );
			} else { // $do === 'edit'
				$dbw->update( 'interwiki', $rows, [ 'iw_prefix' => $prefix ], __METHOD__, 'IGNORE' );
			}

			// used here: interwiki_addfailed, interwiki_added, interwiki_edited
			if ( $dbw->affectedRows() === 0 ) {
				$status->fatal( "interwiki_{$do}failed", $prefix );
			} else {
				$this->getOutput()->addWikiMsg( "interwiki_{$do}ed", $prefix );
				$log = new LogPage( 'interwiki' );
				$log->addEntry( 'iw_' . $do, $selfTitle, $reason, [ $prefix, $theurl, $trans, $local ] );
				$lookup->invalidateCache( $prefix );
			}
			break;
		}

		return $status;
	}

	protected function showList() {
		global $wgInterwikiCentralDB, $wgInterwikiViewOnly;
		$canModify = $this->canModify();

		// Build lists
		$lookup = MediaWikiServices::getInstance()->getInterwikiLookup();
		$iwPrefixes = $lookup->getAllPrefixes( null );
		$iwGlobalPrefixes = [];
		if ( $wgInterwikiCentralDB !== null && $wgInterwikiCentralDB !== wfWikiID() ) {
			// Fetch list from global table
			$dbrCentralDB = wfGetDB( DB_REPLICA, [], $wgInterwikiCentralDB );
			$res = $dbrCentralDB->select( 'interwiki', '*', false, __METHOD__ );
			$retval = [];
			foreach ( $res as $row ) {
				$row = (array)$row;
				if ( !Language::fetchLanguageName( $row['iw_prefix'] ) ) {
					$retval[] = $row;
				}
			}
			$iwGlobalPrefixes = $retval;
		}

		// Split out language links
		$iwLocalPrefixes = [];
		$iwLanguagePrefixes = [];
		foreach ( $iwPrefixes as $iwPrefix ) {
			if ( Language::fetchLanguageName( $iwPrefix['iw_prefix'] ) ) {
				$iwLanguagePrefixes[] = $iwPrefix;
			} else {
				$iwLocalPrefixes[] = $iwPrefix;
			}
		}

		// Page intro content
		$this->getOutput()->addWikiMsg( 'interwiki_intro' );

		// Add 'view log' link when possible
		if ( $wgInterwikiViewOnly === false ) {
			$logLink = $this->getLinkRenderer()->makeLink(
				SpecialPage::getTitleFor( 'Log', 'interwiki' ),
				$this->msg( 'interwiki-logtext' )->text()
			);
			$this->getOutput()->addHTML( '<p class="mw-interwiki-log">' . $logLink . '</p>' );
		}

		// Add 'add' link
		if ( $canModify ) {
			if ( count( $iwGlobalPrefixes ) !== 0 ) {
				$addtext = $this->msg( 'interwiki-addtext-local' )->text();
			} else {
				$addtext = $this->msg( 'interwiki_addtext' )->text();
			}
			$addlink = $this->getLinkRenderer()->makeKnownLink(
				$this->getPageTitle( 'add' ), $addtext );
			$this->getOutput()->addHTML(
				'<p class="mw-interwiki-addlink">' . $addlink . '</p>' );
		}

		$this->getOutput()->addWikiMsg( 'interwiki-legend' );

		if ( ( !is_array( $iwPrefixes ) || count( $iwPrefixes ) === 0 ) &&
			( !is_array( $iwGlobalPrefixes ) || count( $iwGlobalPrefixes ) === 0 )
		) {
			// If the interwiki table(s) are empty, display an error message
			$this->error( 'interwiki_error' );
			return;
		}

		// Add the global table
		if ( count( $iwGlobalPrefixes ) !== 0 ) {
			$this->getOutput()->addHTML(
				'<h2 id="interwikitable-global">' .
				$this->msg( 'interwiki-global-links' )->parse() .
				'</h2>'
			);
			$this->getOutput()->addWikiMsg( 'interwiki-global-description' );

			// $canModify is false here because this is just a display of remote data
			$this->makeTable( false, $iwGlobalPrefixes );
		}

		// Add the local table
		if ( count( $iwLocalPrefixes ) !== 0 ) {
			if ( count( $iwGlobalPrefixes ) !== 0 ) {
				$this->getOutput()->addHTML(
					'<h2 id="interwikitable-local">' .
					$this->msg( 'interwiki-local-links' )->parse() .
					'</h2>'
				);
				$this->getOutput()->addWikiMsg( 'interwiki-local-description' );
			} else {
				$this->getOutput()->addHTML(
					'<h2 id="interwikitable-local">' .
					$this->msg( 'interwiki-links' )->parse() .
					'</h2>'
				);
				$this->getOutput()->addWikiMsg( 'interwiki-description' );
			}
			$this->makeTable( $canModify, $iwLocalPrefixes );
		}

		// Add the language table
		if ( count( $iwLanguagePrefixes ) !== 0 ) {
			$this->getOutput()->addHTML(
				'<h2 id="interwikitable-language">' .
				$this->msg( 'interwiki-language-links' )->parse() .
				'</h2>'
			);
			$this->getOutput()->addWikiMsg( 'interwiki-language-description' );

			$this->makeTable( $canModify, $iwLanguagePrefixes );
		}
	}

	protected function makeTable( $canModify, $iwPrefixes ) {
		// Output the existing Interwiki prefixes table header
		$out = '';
		$out .= Html::openElement(
			'table',
			[ 'class' => 'mw-interwikitable wikitable sortable body' ]
		) . "\n";
		$out .= Html::openElement( 'thead' ) .
			Html::openElement( 'tr', [ 'class' => 'interwikitable-header' ] ) .
			Html::element( 'th', null, $this->msg( 'interwiki_prefix' )->text() ) .
			Html::element( 'th', null, $this->msg( 'interwiki_url' )->text() ) .
			Html::element( 'th', null, $this->msg( 'interwiki_local' )->text() ) .
			Html::element( 'th', null, $this->msg( 'interwiki_trans' )->text() ) .
			( $canModify ?
				Html::element(
					'th',
					[ 'class' => 'unsortable' ],
					$this->msg( 'interwiki_edit' )->text()
				) :
				''
			);
		$out .= Html::closeElement( 'tr' ) .
			Html::closeElement( 'thead' ) . "\n" .
			Html::openElement( 'tbody' );

		$selfTitle = $this->getPageTitle();

		// Output the existing Interwiki prefixes table rows
		foreach ( $iwPrefixes as $iwPrefix ) {
			$out .= Html::openElement( 'tr', [ 'class' => 'mw-interwikitable-row' ] );
			$out .= Html::element( 'td', [ 'class' => 'mw-interwikitable-prefix' ],
				$iwPrefix['iw_prefix'] );
			$out .= Html::element(
				'td',
				[ 'class' => 'mw-interwikitable-url' ],
				$iwPrefix['iw_url']
			);
			$attribs = [ 'class' => 'mw-interwikitable-local' ];
			// Green background for cells with "yes".
			if ( isset( $iwPrefix['iw_local'] ) && $iwPrefix['iw_local'] ) {
				$attribs['class'] .= ' mw-interwikitable-local-yes';
			}
			// The messages interwiki_0 and interwiki_1 are used here.
			$contents = isset( $iwPrefix['iw_local'] ) ?
				$this->msg( 'interwiki_' . $iwPrefix['iw_local'] )->text() :
				'-';
			$out .= Html::element( 'td', $attribs, $contents );
			$attribs = [ 'class' => 'mw-interwikitable-trans' ];
			// Green background for cells with "yes".
			if ( isset( $iwPrefix['iw_trans'] ) && $iwPrefix['iw_trans'] ) {
				$attribs['class'] .= ' mw-interwikitable-trans-yes';
			}
			// The messages interwiki_0 and interwiki_1 are used here.
			$contents = isset( $iwPrefix['iw_trans'] ) ?
				$this->msg( 'interwiki_' . $iwPrefix['iw_trans'] )->text() :
				'-';
			$out .= Html::element( 'td', $attribs, $contents );

			// Additional column when the interwiki table can be modified.
			if ( $canModify ) {
				$out .= Html::rawElement( 'td', [ 'class' => 'mw-interwikitable-modify' ],
					$this->getLinkRenderer()->makeKnownLink(
						$selfTitle,
						$this->msg( 'edit' )->text(),
						[],
						[ 'action' => 'edit', 'prefix' => $iwPrefix['iw_prefix'] ]
					) .
					$this->msg( 'comma-separator' ) .
					$this->getLinkRenderer()->makeKnownLink(
						$selfTitle,
						$this->msg( 'delete' )->text(),
						[],
						[ 'action' => 'delete', 'prefix' => $iwPrefix['iw_prefix'] ]
					)
				);
			}
			$out .= Html::closeElement( 'tr' ) . "\n";
		}
		$out .= Html::closeElement( 'tbody' ) .
			Html::closeElement( 'table' );

		$this->getOutput()->addHTML( $out );
	}

	protected function error() {
		$args = func_get_args();
		$this->getOutput()->wrapWikiMsg( "<p class='error'>$1</p>", $args );
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
