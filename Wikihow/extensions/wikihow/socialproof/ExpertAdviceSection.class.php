<?php

class ExpertAdviceSection {

	private static function expertHtml( OutputPage $out ): String {
		$title = $out->getTitle();

		if ($title) {
			$vdata = VerifyData::getByPageId( $title->getArticleId() );

			if (!isset($vdata[0])) return '';

			$vdata = $vdata[0];

			//kinda dumb, but we need the image, so we have to call ANOTHER VerifyData function
			//that gets pretty much the same data from a different table
			$vInfo = VerifyData::getVerifierInfoById( $vdata->verifierId );
			$avatar = SocialProofStats::getAvatarImageHtml( $vInfo, 'expert_advice_avatar' );

			$loader = new Mustache_Loader_CascadingLoader( [
				new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
			$m = new Mustache_Engine(['loader' => $loader]);

			$vars = [
				'name' => $vdata->name,
				'label' => $vdata->blurb,
				'avatar' => $avatar
			];

			$html = $m->render('expert_advice_expert.mustache', $vars);
		}

		return $html;
	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin $skin ) {
		$title = $out->getTitle();
		if ($title && $title->inNamespace(NS_MAIN)) {
			$out->addModuleStyles('ext.wikihow.expertadvicesection.styles');
		}
	}

	//this uses the phpQuery object
	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		if (!pq('#expertadvice')->length()) return;

		$expertHtml = self::expertHtml( $out );
		pq('#expertadvice')->prepend($expertHtml);

		//put above the Q&A section
		if (pq('.qa.section')->length()) pq('.qa.section')->before(pq('.expertadvice'));

		//add it to desktop TOC
		WikihowToc::setExpertAdvice();
	}
}