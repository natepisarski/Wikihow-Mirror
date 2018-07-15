<?php

class FeaturedContributor extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'FeaturedContributor' );
	}

	function getFCList($top = false) {
		$list = preg_split('/\n==/', wfMessage('fc_list')->text());

		if ($top) {
			return $list[0];
		}else {
			$r = rand(0,(count($list)-1));
			if ($r == 0) {
				return $list[0];
			} else {
				return "== " . $list[$r];
			}	
		}
	}

	function showWidget( $top = false ) {
		global $wgLanguageCode, $wgParser;

		$rec = FeaturedContributor::getFCList($top);
		preg_match('/== (.*?) ==/',$rec,$matches);
		$fc_user =  $matches[1];
		preg_match('/==\n(.*)/',$rec,$matches);
		$fc_blurb =  $matches[1];

		$u = User::newFromName( $fc_user );

		if (!$u) {
			return;
		}

		$u->load();
		$avatar = ($wgLanguageCode == 'en') ? Avatar::getPicture($u->getName(), true, true) : "";
	
		$t = new Title();
		$output = $wgParser->parse($fc_blurb, $t, new ParserOptions() );
		$fc_blurb = preg_replace("/\n/","",strip_tags($output->getText() , '<p><b><a><br>'));

		$fc_blurb = str_replace("$1", $u->getName(), $fc_blurb);
		$regYear = gmdate('Y', wfTimestamp(TS_UNIX, $u->getRegistration()));
		$fc_blurb = str_replace("$2", $regYear, $fc_blurb);

?>
	<div>
		<h3><?php echo wfMessage('fc_title')->text();?></h3>
		<div class='featuredContrib_id'>
		<?php if ($avatar != ''): ?>
			<span id='fc_id_img' class='fc_id_img'><a href='/<?php echo $u->getUserPage(); ?>' onclick='gatTrack("Browsing","Feat_contrib_img","Feat_contrib_wgt");'><?php echo $avatar ?></a></span>
		<? endif; ?>
		<span id='fc_id' class='fc_id' onclick='gatTrack("Browsing","Feat_contrib_blurb","Feat_contrib_wgt");'><?php echo $fc_blurb ?></span>
		</div>
        <div class="clearall"></div>
	</div>

<?php

		return;
	}

	function execute ($par) {
		return;
	}
}
