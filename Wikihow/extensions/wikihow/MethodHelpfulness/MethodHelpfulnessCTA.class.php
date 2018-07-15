<?
namespace MethodHelpfulness;
use MethodHelpfulness\ArticleMethod;
use EasyTemplate;

abstract class CTA {
	const CTA_TYPE_STANDALONE = 'standalone';
	const CTA_TYPE_PER_METHOD = 'per_method';

	const PLATFORM_DESKTOP = 'desktop';
	const PLATFORM_MOBILE = 'mobile';

	public static function getCTAClass($type) {
		$classes = array(
			'bottom_form' => BottomFormCTA,
			'method_thumbs' => MethodThumbsCTA
		);

		return $classes[$type];
	}

	public function getCTA(&$t, $platform, $methods=false, $vars=array()) {
		$validPlatform = in_array(
			$platform,
			$this->getValidPlatforms(),
			true
		);
		
		if (!$validPlatform || !$this->isActiveContext($t)) {
			return false;
		}

		if ($methods === false) {
			return false;
		}

		$vars['methods'] = $methods;
		$vars['platform'] = $platform;

		$baseSkinPath = $this->getBaseSkinPath();
		EasyTemplate::set_path($baseSkinPath);

		$ctaType = $this->getCTAType();

		if ($ctaType === self::CTA_TYPE_STANDALONE) {
			return $this->getStandaloneCTA($t, $platform, $methods, $vars);
		} elseif ($ctaType === self::CTA_TYPE_PER_METHOD) {
			return $this->getPerMethodCTA($t, $platform, $methods, $vars);
		} else {
			return false;
		}
	}

	public function getStandaloneCTA(&$t, $platform, $methods=false, $vars=array()) {
		$tmplPathCommon = "common/templates/mh.tmpl.php";
		$tmplPathPlatform = "$platform/templates/mh.tmpl.php";

		$tmplCommon = EasyTemplate::html(
			$tmplPathCommon,
			$vars
		);

		$vars['commonTemplate'] = $tmplCommon;

		$tmplPlatform = EasyTemplate::html(
			$tmplPathPlatform,
			$vars
		);

		return $tmplPlatform;
	}

	public function getPerMethodCTA(&$t, $platform, $methods=false, $vars=array()) {
		$tmplPathCommon = "common/templates/mh.tmpl.php";
		$tmplPathPlatform = "$platform/templates/mh.tmpl.php";

		$templates = array();

		foreach ($methods as $k=>$method) {
			$vars['methodIndex'] = $k;
			$vars['currentMethod'] = $method;

			$tmplCommon = EasyTemplate::html(
				$tmplPathCommon,
				$vars
			);

			$vars['commonTemplate'] = $tmplCommon;

			$tmplPlatform = EasyTemplate::html(
				$tmplPathPlatform,
				$vars
			);

			$templates[] = $tmplPlatform;
		}

		return $templates;
	}

	public function getMustacheCTATemplate(&$t, $platform, $vars=array()) {
		if (!$this->supportsMustacheCTATemplate()) {
			return '';
		}

		$vars['platform'] = $platform;

		$baseSkinPath = $this->getBaseSkinPath();
		EasyTemplate::set_path($baseSkinPath);

		$tmplPathCommon = "common/templates/mh_mustache.tmpl.php";
		$tmplPathPlatform = "$platform/templates/mh_mustache.tmpl.php";

		$tmplCommon = EasyTemplate::html(
			$tmplPathCommon,
			$vars
		);

		$vars['commonTemplate'] = $tmplCommon;

		$tmplPlatform = EasyTemplate::html(
			$tmplPathPlatform,
			$vars
		);

		return $tmplPlatform;
	}

	/**
	 * Return a string specifying which module to use for ResourceLoader
	 */
	abstract public static function getResourceModule($platform);

	/**
	 * Return a string with the base path to the skin's template, CSS and JS files.
	 */
	abstract protected static function getBaseSkinPath();

	abstract public static function getCTAType();

	public static function supportsMustacheCTATemplate() {
		return false;
	}

	public function getValidPlatforms() {
		return array(self::PLATFORM_DESKTOP, self::PLATFORM_MOBILE);
	}

	public function isActiveContext(&$t) {
		return ArticleMethod::hasMethods($t);
	}
}

class BottomFormCTA extends CTA {
	public static function getResourceModule($platform) {
		if ($platform == self::PLATFORM_DESKTOP) {
			return 'ext.wikihow.methodhelpfulness.cta.bottom_form.desktop';
		} elseif ($platform == self::PLATFORM_MOBILE) {
			return 'ext.wikihow.methodhelpfulness.cta.bottom_form.mobile';
		} else {
			return false;
		}
	}

	protected static function getBaseSkinPath() {
		global $IP;
		return "$IP/extensions/wikihow/MethodHelpfulness/resources/cta/bottom_form";
	}

	public static function getCTAType() {
		return self::CTA_TYPE_STANDALONE;
	}
}

class MethodThumbsCTA extends CTA {
	public static function getResourceModule($platform) {
		if ($platform == self::PLATFORM_DESKTOP) {
			return 'ext.wikihow.methodhelpfulness.cta.method_thumbs.desktop';
		} elseif ($platform == self::PLATFORM_MOBILE) {
			return 'ext.wikihow.methodhelpfulness.cta.method_thumbs.mobile';
		} else {
			return false;
		}
	}

	protected static function getBaseSkinPath() {
		global $IP;
		return "$IP/extensions/wikihow/MethodHelpfulness/resources/cta/method_thumbs";
	}

	public static function getCTAType() {
		return self::CTA_TYPE_PER_METHOD;
	}

	public static function supportsMustacheCTATemplate() {
		return true;
	}

	/**
	 * Restrict to mobile.
	 */
	public function getValidPlatforms() {
		return array(self::PLATFORM_MOBILE);
	}
}

