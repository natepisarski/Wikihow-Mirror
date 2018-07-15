<?

/**
 * Supplies base64 encodings for inline data: URLs for known images.  You should
 * only apply these to small icon type images.  You can create a base64 
 * encoding of an image with this command on linux:
 *
 * base64 <image-file>
 *
 * Because IE 6 and IE 7 don't understand data: URLs, you need to redefine
 * the CSS rules with the URLs for them separate, after the rules for the
 * rest of the browsers.  For example:
 *
    <style>
        .rcw-help-icon { background-image:url(<?= Base64Images::getDataURL('icon_help_tan.jpg') ?>); }
    </style>
    <!--[if lte IE 7]>
    <style>
        .rcw-help-icon { background-image:url(<?= Base64Images::getImageURL('icon_help_tan.jpg') ?>); }
    </style>
    <![endif]-->
 *
 */
class Base64Images {

	static $images = array(
		'example.jpg' => array(
			'file' => '/skins/WikiHow/example.jpg',
			'data' => 'data:image/jpg;base64,ABCDE...==',
		),

		// Used in RCWidget.body.php
		'icon_help_tan.jpg' => array(
			'file' => '/skins/WikiHow/images/icon_help_tan.jpg',
			'data' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAcEBAQFBAcFBQcKBwUHCgwJBwcJDA0LCwwLCw0RDQ0NDQ0NEQ0PEBEQDw0UFBYWFBQeHR0dHiIiIiIiIiIiIiL/2wBDAQgHBw0MDRgQEBgaFREVGiAgICAgICAgICAgICAhICAgICAgISEhICAgISEhISEhISEiIiIiIiIiIiIiIiIiIiL/wAARCAASAAwDAREAAhEBAxEB/8QAFwAAAwEAAAAAAAAAAAAAAAAAAAQFB//EACQQAAICAQQBBAMAAAAAAAAAAAECAwQRAAUGEiITFCFxFTJB/8QAFgEBAQEAAAAAAAAAAAAAAAAAAQIA/8QAFxEBAQEBAAAAAAAAAAAAAAAAAAECEf/aAAwDAQACEQMRAD8A1Gakd32Lbt7kqx7he3KZAlSdTJHFDI2SieSLEyRrlpMZLD6GiTjWkOXc9bg27/hEr+9rems1cvKQ8cbePpMSGLdShIJ+cEfepuFTS/a47ajZDtM0MKRze4igtVxYihlbIeWAdo2jc9yf265/gznUzZuTUPH9tHZ7ca3bcrd5rVhEeR2xj58QAAAAABgDRdU8UNSRrM//2Q==',
		),
	);

	/**
	 * Retrieve the data: and http: URLs for a known encoded image.
	 */
	private static function getURLs($img) {
		$file = preg_replace('@([^/]*/)+@', '', $img);
		$urls = @self::$images[$file];
		if (is_array($urls)) {
			return $urls;
		} else {
			throw new Exception('file: '.$file.' needs to be added to Base64Images class');
		}
	}

	/**
	 * Get the http: URL of an image that's base64 encoded.
	 */
	public static function getImageURL($img) {
		$urls = self::getURLs($img);
		return $urls['file'];
	}

	/**
	 * Get the data: URL of an image that's base64 encoded.
	 */
	public static function getDataURL($img) {
		$urls = self::getURLs($img);
		return $urls['data'];
	}

}

