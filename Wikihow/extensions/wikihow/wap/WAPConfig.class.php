<?
interface WAPConfig {
	// Specifies which datasource for WAP to use
	public function getDBType();

	// Table names for data storage
	public function getArticleTableName();
	public function getTagTableName();
	public function getArticleTagTableName();
	public function getUserTagTableName();
	public function getUserTableName();

	// Group name system users are assigned to
	public function getWikiHowGroupName();

	// Group name system power users are assigned to
	// Power users have access to specific reports 
	public function getWikiHowPowerUserGroupName();

	// Admin group name
	public function getWikiHowAdminGroupName();

	// System class names
	public function getDBClassName();
	public function getArticleClassName();
	public function getUserClassName();
	public function getLinkerClassName();
	public function getPagerClassName();
	public function getReportClassName();
	public function getMaintenanceClassName();
	public function getUIUserControllerClassName();

	// Special page names
	public function getUserPageName();
	public function getAdminPageName();

	// Location of UI Templates
	public function getWAPUITemplatesLocation();
	public function getSystemUITemplatesLocation();

	// ConfigStorage excluded articles key name
	public function getExcludedArticlesKeyName();

	public function getSystemName();

	public function getUserDisplayName();

	public function getSupportEmailAddress();
	public function getNewArticleMessage($supportEmail);

	public function getSupportedLanguages();

	public function getMaintenanceStandardEmailList();
	public function getMaintenanceCompletedEmailList();
	public function getDefaultUserName();


	// A flag letting the system know if it's in maintenance mode
	public function isMaintenanceMode();
}

