<?php

class HighSchoolHacks extends SpecialPage {

	public static $custom_url = 'wikiHow:High-School-Hacks';

	//in layout order
	private static $topics = [
		'relationships',
		'social_media',
		'health',
		'academics',
		'fun',
		'friendships',
		'bullying',
		'family',
		'mental_health',
		'future',
		'confidence',
		'leadership'
	];

	public function __construct() {
		parent::__construct( 'HighSchoolHacks');

		global $wgHooks;
		$wgHooks['ShowSideBar'][] = ['HighSchoolHacks::removeSideBarCallback'];
		$wgHooks['ShowBreadCrumbs'][] = ['HighSchoolHacks::removeBreadCrumbsCallback'];
	}

	public function isMobileCapable() {
		return true;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();

		$action = $request->getVal('action','');

		if (!empty($action)) {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			$topic = strip_tags($request->getVal('topic'));

			if ($action == 'get_articles') {
				$result = $this->getArticleList($topic);
			}
			elseif ($action == 'article_icon') {
				$result = $this->articleIconHtml($topic);
			}

			print json_encode($result);
			return;
		}

		$out->addModules(['ext.wikihow.high_school_hacks.styles','ext.wikihow.high_school_hacks.scripts']);
		$out->setHtmlTitle(wfMessage('high_school_hacks')->text());
		$out->setCanonicalUrl( Misc::getLangBaseURL().'/'.self::$custom_url );
		$out->addHTML($this->bodyHtml());
	}

	private function bodyHtml(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'wH_logo_url' => wfGetPad('/extensions/wikihow/HighSchoolHacks/resources/images/wH_logo_green.png'),
			'headline' => wfMessage('hsh_header')-> text(),
			'topics' => $this->topicData(),
			'high_school_hacks_article_list' => $loader->load('high_school_hacks_article_list')
		];

		return $m->render('high_school_hacks', $vars);
	}

	private function topicData(): array {
		$topic_data = [];

		foreach (self::$topics as $topic) {
			$topic_data[] = [
				'keyword' => $topic,
				'name' => wfMessage('hsh_topic_name_'.$topic)->text()
			];
		}

		return $topic_data;
	}

	private function getArticleList(string $topic): array {
		return !empty($this->articles[$topic]) ? $this->articles[$topic] : [];
	}

	private function articleIconHtml(string $topic): string {
		if (!in_array($topic, self::$topics)) return '';

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'topic' => $topic,
			'link' => '/'.self::$custom_url,
			'text' => wfMessage('high_school_hacks')->text()
		];

		return $m->render('article_icon', $vars);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		$title = $out->getTitle();
		$action = Action::getActionName($out->getContext());
		$high_school_hack = strip_tags($out->getRequest()->getVal('hsh',''));

		$showHSHIcon = $title &&
			$title->inNamespace( NS_MAIN ) &&
			$action === 'view' &&
			!empty($high_school_hack) &&
			in_array($high_school_hack, self::$topics);

		if ($showHSHIcon) $out->addModules('ext.wikihow.high_school_hacks.article_icon');
	}

	public static function onWebRequestPathInfoRouter( $router ) {
		$router->addStrict( self::$custom_url, array( 'title' => 'Special:HighSchoolHacks') );
	}

	//hard-coded because this is a proof-of-concept
	//TODO: put these in a json file or a db table
	var $articles = [
		'academics' => [
			['url' => '/Study', 'article' => 'Study'],
			['url' => '/Take-Notes', 'article' => 'Take Notes'],
			['url' => '/Stay-Calm-During-a-Test', 'article' => 'Stay Calm During Tests'],
			['url' => '/Write-a-Book-Report', 'article' => 'Write Book Reports'],
			['url' => '/Write-an-Academic-Essay', 'article' => 'Write Academic Essays'],
			['url' => '/Study-Math', 'article' => 'Study Math']
		],
		'bullying' => [
			['url' => '/Deal-With-Homophobic-Bullying', 'article' => 'Deal with Homophobic Bullying'],
			['url' => '/Deal-With-People-Talking-About-You-Behind-Your-Back', 'article' => 'Deal with Gossip'],
			['url' => '/Stop-Cyber-Bullying', 'article' => 'Stop Digital Bullying'],
			['url' => '/Deal-With-Bullies-as-a-Teenager', 'article' => 'Deal with Bullies'],
			['url' => '/Deal-with-Rumors-and-Drama', 'article' => 'Handle Rumors and Drama'],
			['url' => '/Deal-With-Peer-Pressure', 'article' => 'Deal with Peer Pressure']
		],
		'confidence' => [
			['url' => '/Come-Out', 'article' => 'Come Out'],
			['url' => '/Love-Yourself', 'article' => 'Love Yourself'],
			['url' => '/Build-Self-Confidence', 'article' => 'Build Self-Confidence'],
			['url' => '/Improve-Your-Appearance', 'article' => 'Improve Your Appearance']
		],
		'family' => [
			['url' => '/Deal-with-Stressed-and-Overly-Emotional-Parents', 'article' => 'Handle Stressed Parents'],
			['url' => '/Deal-With-Family-Problems', 'article' => 'Deal with Family Problems'],
			['url' => '/Love-Your-Parents', 'article' => 'Love Your Parents'],
			['url' => '/Deal-With-Your-Parents%27-Divorce', 'article' => 'Deal with Divorce']
		],
		'friendships' => [
			['url' => '/Be-a-Good-Friend', 'article' => 'Be a Good Friend'],
			['url' => '/Deal-With-Frenemies', 'article' => 'Deal with Frenemies'],
			['url' => '/Become-a-More-Social-Person', 'article' => 'Become More Social'],
			['url' => '/Make-Friends-After-Coming-out-As-Lesbian,-Gay,-Bisexual-or-Transgender', 'article' => 'Make Friends After Coming Out'],
			['url' => '/Make-Friends', 'article' => 'Make Friends']
		],
		'fun' => [
			['url' => 'Hang-out-with-Friends-on-a-School-Night', 'article' => 'Hang Out on a School Night'],
			['url' => 'Have-Fun-During-the-Weekend-(Teens)', 'article' => 'Have Fun on the Weekends'],
			['url' => 'Enjoy-Summer-Vacation-%28for-Teens%29', 'article' => 'Enjoy Summer Vacation'],
			['url' => 'Enjoy-High-School', 'article' => 'Enjoy High School'],
			['url' => 'Find-a-Hobby', 'article' => 'Find a Hobby'],
			['url' => 'Have-a-Movie-Night-With-Friends', 'article' => 'Have a Movie Night with Friends']
		],
		'future' => [
			['url' => '/Get-into-College', 'article' => 'Get into College'],
			['url' => '/Save-Money-for-Teenagers', 'article' => 'Save Money'],
			['url' => '/Get-Your-First-Job-(for-Teens)', 'article' => 'Get Your First Job'],
			['url' => '/Prepare-Yourself-for-Entrance-Exams', 'article' => 'Prepare For Entrance Exams'],
			['url' => '/Make-a-Resume', 'article' => 'Make a Resume'],
			['url' => '/Achieve-As-a-Teen', 'article' => 'Achieve']
		],
		'health' => [
			['url' => '/Overcome-Eating-Disorders', 'article' => 'Overcome Eating Disorders'],
			['url' => '/Be-Drug-Free', 'article' => 'Be Drug Free'],
			['url' => '/Maintain-a-Healthy-Diet-at-School-(Teens)', 'article' => 'Maintain a Healthy Diet'],
			['url' => '/Stay-Healthy-As-a-Teen', 'article' => 'Stay Healthy'],
			['url' => '/Get-in-Shape-as-a-Teen', 'article' => 'Get in Shape']
		],
		'leadership' => [
			['url' => '/Help-Save-the-Environment', 'article' => 'Help Save the Environment'],
			['url' => '/Be-a-Leader', 'article' => 'Be a Leader'],
			['url' => '/Write-a-Letter-to-Your-United-States-Senator', 'article' => 'Write Your United States Senator'],
			['url' => '/Volunteer', 'article' => 'Volunteer'],
			['url' => '/Get-Involved-in-Your-Community', 'article' => 'Be Involved in Your Community']
		],
		'mental_health' => [
			['url' => '/Deal-With-Stress', 'article' => 'Handle Stress'],
			['url' => '/Meditate', 'article' => 'Learn to Meditate'],
			['url' => '/Get-Over-Depression-As-a-Teenager', 'article' => 'Get over Depression'],
			['url' => '/Cope-With-Suicidal-Thoughts', 'article' => 'Cope with Suicidal Thoughts'],
			['url' => '/Control-Anxiety', 'article' => 'Control Anxiety']
		],
		'relationships' => [
			['url' => '/Be-a-Good-Boyfriend-as-a-Teen', 'article' => 'Be a Good Boyfriend'],
			['url' => '/Be-a-Good-Girlfriend-as-a-Teen', 'article' => 'Be a Good Girlfriend'],
			['url' => '/Handle-a-Teenage-Breakup', 'article' => 'Handle a Breakup'],
			['url' => '/Find-a-Queer-Relationship-in-High-School', 'article' => 'Find a Queer Relationship'],
			['url' => '/Talk-to-Your-Crush-Without-Being-Crushed', 'article' => 'Talk to a Crush'],
		],
		'social_media' => [
			['url' => '/Deal-with-Social-Media-Jealousy', 'article' => 'Deal with Jealousy'],
			['url' => '/Avoid-Oversharing-on-Social-Media', 'article' => 'Avoid Oversharing'],
			['url' => '/Defeat-a-Social-Networking-Addiction', 'article' => 'Defeat a Social Media Addiction'],
			['url' => '/Handle-Toxic-People-on-Social-Media', 'article' => 'Handle Toxic People'],
			['url' => '/Shape-Your-Social-Network-for-Happiness', 'article' => 'Shape Your Network for Happiness']
		]
	];


}
