<?php

class Hello extends UnlistedSpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

	public function isMobileCapable() {
		return true;
	}

    public function execute( $subPage ) {
		global $wgSquidMaxage;

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		if ( !Misc::isMobileMode() ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$out->setSquidMaxage($wgSquidMaxage);

		$out->setPageTitle("WikiHow");
        $vars = $this->getDefaultVars();
        $out->addHtml($this->getTemplateHtml('Hello.tmpl.php', $vars));

    }

    public function getTemplateHtml($templateName, &$vars) {
        global $IP;
        $path = "$IP/extensions/wikihow/NoScriptHomepage";
        EasyTemplate::set_path($path);
        return EasyTemplate::html($templateName, $vars);
    }

    protected function getDefaultVars() {
        $vars = array();
		$vars['topMessage'] = wfMessage( 'nojs_welcome' )->text();
		$vars['faSection'] = wfMessage( 'nojs_fa_section' )->text();

		// requesting the same amount as the regular homepage to take advantage of caching
		$fas = FeaturedArticles::getTitles( 18 );
		$links = array();
		$count = 0;
		foreach ( $fas as $fa ) {
			if ( $count > 15 ) {
				break;
			}
			$title = $fa['title'];
			if ( $title && $title->exists() ) {
				$count++;
				$links[] = Linker::link( $fa['title'], wfMessage( 'Howto', $fa['title'] )->text() );
			}
		}
		$vars['fas'] = $links;
		$vars['searchText'] = wfMessage( 'nojs_search_text' )->text();
		$vars['howTo'] = wfMessage( 'nojs_search_placeholder' )->text();
		$vars['surpriseMe'] = wfMessage( "nojs_surpriseme" )->text();
        $vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/NoScriptHomepage/hello.css');

        return $vars;
    }

}
