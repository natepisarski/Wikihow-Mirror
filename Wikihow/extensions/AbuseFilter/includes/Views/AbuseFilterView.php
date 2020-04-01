<?php

use Wikimedia\Rdbms\IDatabase;

abstract class AbuseFilterView extends ContextSource {
	public $mFilter, $mHistoryID, $mSubmit, $mPage, $mParams;

	/**
	 * @var \MediaWiki\Linker\LinkRenderer
	 */
	protected $linkRenderer;

	/**
	 * @param SpecialAbuseFilter $page
	 * @param array $params
	 */
	public function __construct( SpecialAbuseFilter $page, $params ) {
		$this->mPage = $page;
		$this->mParams = $params;
		$this->setContext( $this->mPage->getContext() );
		$this->linkRenderer = $page->getLinkRenderer();
	}

	/**
	 * @param string $subpage
	 * @return Title
	 */
	public function getTitle( $subpage = '' ) {
		return $this->mPage->getPageTitle( $subpage );
	}

	/**
	 * Function to show the page
	 */
	abstract public function show();

	/**
	 * @return bool
	 */
	public function canEdit() {
		$block = $this->getUser()->getBlock();

		return (
			!( $block && $block->isSitewide() ) &&
			$this->getUser()->isAllowed( 'abusefilter-modify' )
		);
	}

	/**
	 * @return bool
	 */
	public function canEditGlobal() {
		return $this->getUser()->isAllowed( 'abusefilter-modify-global' );
	}

	/**
	 * Whether the user can edit the given filter.
	 *
	 * @param object $row Filter row
	 *
	 * @return bool
	 */
	public function canEditFilter( $row ) {
		return (
			$this->canEdit() &&
			!( isset( $row->af_global ) && $row->af_global == 1 && !$this->canEditGlobal() )
		);
	}

	/**
	 * @param string $rules
	 * @param string $textName
	 * @param bool $addResultDiv
	 * @param bool $externalForm
	 * @param bool $needsModifyRights
	 * @param-taint $rules none
	 * @return string
	 */
	public function buildEditBox(
		$rules,
		$textName = 'wpFilterRules',
		$addResultDiv = true,
		$externalForm = false,
		$needsModifyRights = true
	) {
		$this->getOutput()->enableOOUI();

		// Rules are in English
		$editorAttribs = [ 'dir' => 'ltr' ];

		$noTestAttrib = [];
		$isUserAllowed = $needsModifyRights ?
			$this->getUser()->isAllowed( 'abusefilter-modify' ) :
			$this->canViewPrivate();
		if ( !$isUserAllowed ) {
			$noTestAttrib['disabled'] = 'disabled';
			$addResultDiv = false;
		}

		$rules = rtrim( $rules ) . "\n";
		$canEdit = $needsModifyRights ? $this->canEdit() : $this->canViewPrivate();
		$switchEditor = null;

		$rulesContainer = '';
		if ( ExtensionRegistry::getInstance()->isLoaded( 'CodeEditor' ) ) {
			$aceAttribs = [
				'name' => 'wpAceFilterEditor',
				'id' => 'wpAceFilterEditor',
				'class' => 'mw-abusefilter-editor'
			];
			$attribs = array_merge( $editorAttribs, $aceAttribs );

			$switchEditor =
				new OOUI\ButtonWidget(
					[
						'label' => $this->msg( 'abusefilter-edit-switch-editor' )->text(),
						'id' => 'mw-abusefilter-switcheditor'
					] + $noTestAttrib
				);

			$rulesContainer .= Xml::element( 'div', $attribs, $rules );

			// Add Ace configuration variable
			$editorConfig = AbuseFilter::getAceConfig( $canEdit );
			$this->getOutput()->addJsConfigVars( 'aceConfig', $editorConfig );
		}

		// Build a dummy textarea to be used: for submitting form if CodeEditor isn't installed,
		// and in case JS is disabled (with or without CodeEditor)
		if ( !$canEdit ) {
			$editorAttribs['readonly'] = 'readonly';
		}
		if ( $externalForm ) {
			$editorAttribs['form'] = 'wpFilterForm';
		}
		$rulesContainer .= Xml::textarea( $textName, $rules, 40, 15, $editorAttribs );

		if ( $canEdit ) {
			// Generate builder drop-down
			$rawDropDown = AbuseFilter::getBuilderValues();

			// The array needs to be rearranged to be understood by OOUI. It comes with the format
			// [ group-msg-key => [ text-to-add => text-msg-key ] ] and we need it as
			// [ group-msg => [ text-msg => text-to-add ] ]
			// Also, the 'other' element must be the first one.
			$dropDownOptions = [ $this->msg( 'abusefilter-edit-builder-select' )->text() => 'other' ];
			foreach ( $rawDropDown as $group => $values ) {
				// Give grep a chance to find the usages:
				// abusefilter-edit-builder-group-op-arithmetic, abusefilter-edit-builder-group-op-comparison,
				// abusefilter-edit-builder-group-op-bool, abusefilter-edit-builder-group-misc,
				// abusefilter-edit-builder-group-funcs, abusefilter-edit-builder-group-vars
				$localisedGroup = $this->msg( "abusefilter-edit-builder-group-$group" )->text();
				$dropDownOptions[ $localisedGroup ] = array_flip( $values );
				$newKeys = array_map(
					function ( $key ) use ( $group ) {
						return $this->msg( "abusefilter-edit-builder-$group-$key" )->text();
					},
					array_keys( $dropDownOptions[ $localisedGroup ] )
				);
				$dropDownOptions[ $localisedGroup ] = array_combine(
					$newKeys, $dropDownOptions[ $localisedGroup ] );
			}

			$dropDownList = Xml::listDropDownOptionsOoui( $dropDownOptions );
			$dropDown = new OOUI\DropdownInputWidget( [
				'name' => 'wpFilterBuilder',
				'inputId' => 'wpFilterBuilder',
				'options' => $dropDownList
			] );

			$formElements = [ new OOUI\FieldLayout( $dropDown ) ];

			// Button for syntax check
			$syntaxCheck =
				new OOUI\ButtonWidget(
					[
						'label' => $this->msg( 'abusefilter-edit-check' )->text(),
						'id' => 'mw-abusefilter-syntaxcheck'
					] + $noTestAttrib
				);

			// Button for switching editor (if Ace is used)
			if ( $switchEditor !== null ) {
				$formElements[] = new OOUI\FieldLayout(
					new OOUI\Widget( [
						'content' => new OOUI\HorizontalLayout( [
							'items' => [ $switchEditor, $syntaxCheck ]
						] )
					] )
				);
			} else {
				$formElements[] = new OOUI\FieldLayout( $syntaxCheck );
			}

			$fieldSet = new OOUI\FieldsetLayout( [
				'items' => $formElements,
				'classes' => [ 'mw-abusefilter-edit-buttons', 'mw-abusefilter-javascript-tools' ]
			] );

			$rulesContainer .= $fieldSet;
		}

		if ( $addResultDiv ) {
			$rulesContainer .= Xml::element( 'div',
				[ 'id' => 'mw-abusefilter-syntaxresult', 'style' => 'display: none;' ],
				'&#160;' );
		}

		// Add script
		$this->getOutput()->addModules( 'ext.abuseFilter.edit' );
		AbuseFilter::$editboxName = $textName;

		return $rulesContainer;
	}

	/**
	 * Build input and button for loading a filter
	 *
	 * @return string
	 */
	public function buildFilterLoader() {
		$loadText =
			new OOUI\TextInputWidget(
				[
					'type' => 'number',
					'name' => 'wpInsertFilter',
					'id' => 'mw-abusefilter-load-filter'
				]
			);
		$loadButton =
			new OOUI\ButtonWidget(
				[
					'label' => $this->msg( 'abusefilter-test-load' )->text(),
					'id' => 'mw-abusefilter-load'
				]
			);
		$loadGroup =
			new OOUI\ActionFieldLayout(
				$loadText,
				$loadButton,
				[
					'label' => $this->msg( 'abusefilter-test-load-filter' )->text()
				]
			);
		// CSS class for reducing default input field width
		$loadDiv =
			Xml::tags(
				'div',
				[ 'class' => 'mw-abusefilter-load-filter-id' ],
				$loadGroup
			);
		return $loadDiv;
	}

	/**
	 * @param IDatabase $db
	 * @param string|bool $action 'edit', 'move', 'createaccount', 'delete' or false for all
	 * @return string
	 */
	public function buildTestConditions( IDatabase $db, $action = false ) {
		// If one of these is true, we're abusefilter compatible.
		switch ( $action ) {
			case 'edit':
				return $db->makeList( [
					// Actually, this is only one condition, but this way we get it as string
					'rc_source' => [
						RecentChange::SRC_EDIT,
						RecentChange::SRC_NEW,
					]
				], LIST_AND );
			case 'move':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'move',
					'rc_log_action' => 'move'
				], LIST_AND );
			case 'createaccount':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'newusers',
					'rc_log_action' => [ 'create', 'autocreate' ]
				], LIST_AND );
			case 'delete':
				return $db->makeList( [
					'rc_source' => RecentChange::SRC_LOG,
					'rc_log_type' => 'delete',
					'rc_log_action' => 'delete'
				], LIST_AND );
			case false:
				// Done later
				break;
			// @ToDo: case 'upload'
			default:
				throw new MWException( __METHOD__ . ' called with invalid action: ' . $action );
		}

		return $db->makeList( [
			'rc_source' => [
				RecentChange::SRC_EDIT,
				RecentChange::SRC_NEW,
			],
			$db->makeList( [
				'rc_source' => RecentChange::SRC_LOG,
				$db->makeList( [
					$db->makeList( [
						'rc_log_type' => 'move',
						'rc_log_action' => 'move'
					], LIST_AND ),
					$db->makeList( [
						'rc_log_type' => 'newusers',
						'rc_log_action' => [ 'create', 'autocreate' ]
					], LIST_AND ),
					$db->makeList( [
						'rc_log_type' => 'delete',
						'rc_log_action' => 'delete'
					], LIST_AND ),
					// @todo: add upload
				], LIST_OR ),
			], LIST_AND ),
		], LIST_OR );
	}

	/**
	 * @param string|int $id
	 * @param string|null $text
	 * @return string HTML
	 */
	public function getLinkToLatestDiff( $id, $text = null ) {
		return $this->linkRenderer->makeKnownLink(
			$this->getTitle( "history/$id/diff/prev/cur" ),
			$text
		);
	}

	/**
	 * @return bool
	 */
	public static function canViewPrivate() {
		global $wgUser;
		static $canView = null;

		if ( is_null( $canView ) ) {
			$canView = $wgUser->isAllowedAny( 'abusefilter-modify', 'abusefilter-view-private' );
		}

		return $canView;
	}

}
