<?

class Wikihow_i18n {

	/**
	 * Generates and returns a string of javascript that can be embedded in
	 * your HTML to make Mediawiki messages available via the wfMessage()->text() call.
	 * Requires that jQuery is already loaded.
	 *
	 * Example usage:
	 * <?php
	 *   $langKeys = array('done-button', 'welcome', 'some-random-message-key');
	 *   $js = Wikihow_i18n::genJSMsgs($langKeys);
	 *   echo $js;
	 * ?>
	 * <script> alert('my message: ' + wfMessage('welcome')->text()); </script>
	 */
	public static function genJSMsgs($langKeys) {

		$js = "
<script>
	WH.mergeLang({
";
		$len = count($langKeys);
		foreach ($langKeys as $i => $key) {
			$msg = preg_replace('@([\'\\\\])@', '\\\\$1', wfMessage($key)->text());
			$js .= "'$key': '$msg'" . ($i == $len - 1 ? '' : ',');
			if ($i % 5 == 4 && $i < $len - 1) {
				$js .= "\n";
			}
		}
		$js .= "
	});
</script>
";

		return $js;
	}

	public static function genCSSMsgs($langKeys) {
		// TODO
	}
}

