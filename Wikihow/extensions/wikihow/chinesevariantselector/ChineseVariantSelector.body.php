<?php

class ChineseVariantSelector
{
	/**
	 * Check a variant cookie for tracking the variant
	 */
	public static function onGetPreferredVariant(&$variant) {
		global $wgCookiePath, $wgCookieDomain, $wgCookiePrefix;

		$cookiename = $wgCookiePrefix.'variant';
		if (isset($_COOKIE[$cookiename])) {
			$variant = $_COOKIE[$cookiename];
		}
		return(true);
	}

	/**
	 * Add message post processing to convert messages to variant
	 * when needed
	 */
	public static function onMessagePostProcess(&$message) {
		global $wgContLang, $wgLanguageCode;

		if ($wgLanguageCode == "zh" && $message && $message != "首页") {
			$message = $wgContLang->convert($message);
		}
		return(true);
	}

	/**
	 * Add variant selector to page
	 */
	public static function onEndOfHeader($wgOut) {
		global $wgLanguageCode, $wgContLang;
		if ($wgLanguageCode == "zh") {
?>
                <style>
                    #header #wpUserVariant { background-color: #C9DCB9; border: medium none; left: 590px; position: relative; top: -48px; }
                    #header.shrunk #wpUserVariant { top: -35px; }
                    .search_box { width: 119px }
                </style>
                <form action="" method="post">
                <select id="wpUserVariant">
                <?php
                    $variant = $wgContLang->getPreferredVariant();
                    $zhVarArr = array("zh" => "选择语言", "zh-hans"=>"中文(简体)", "zh-hant"=>"中文(繁體)",  "zh-tw"=>"中文(台灣)", "zh-sg" => "中文(新加坡)", "zh-hk" => "中文(香港)");
                    foreach ($zhVarArr as $k => $v) { ?>
                        <option <? if ($variant == $k) { ?>selected <?php } ?> value="<?= $k ?>"><?= $v ?></option>
                    <?php } ?>
                </select>
                </form>
<?php
		}
		return(true);
	}
}
