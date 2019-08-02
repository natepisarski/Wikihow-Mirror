<?php
namespace MobileFrontend\AMC;

/**
 * Hooks for Advanced Mobile Contributions
 *
 * @package MobileFrontend\AMC
 */
final class Hooks {

	/**
	 * Register default preference value for AMC opt-in
	 *
	 * @param array &$defaultUserOptions Reference to default options array
	 */
	public static function onUserGetDefaultOptions( &$defaultUserOptions ) {
		$defaultUserOptions[UserMode::USER_OPTION_MODE_AMC] = UserMode::OPTION_DISABLED;
	}

	/**
	 * ListDefinedTags and ChangeTagsListActive hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ListDefinedTags
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangeTagsListActive
	 *
	 * @param array &$tags The list of tags. Add your extension's tags to this array.
	 * @return bool
	 */
	public static function onListDefinedTags( &$tags ) {
		$tags[] = Manager::AMC_EDIT_TAG;
		return true;
	}

	/**
	 * RecentChange_save hook handler that tags mobile changes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RecentChange_save
	 *
	 * @param \RecentChange $rc
	 * @return bool
	 */
	public static function onRecentChangeSave( \RecentChange $rc ) {
		// To be safe, we should use the User objected provided via RecentChange, not the
		// currently logged-in user.
		$userMode = UserMode::newForUser( $rc->getPerformer() );
		if ( $userMode->isEnabled() ) {
			$rc->addTags( Manager::AMC_EDIT_TAG );
		}
		return true;
	}
}
