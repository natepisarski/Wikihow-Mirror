<?php

class GooglePresentationTag {

	/*
	 * create a new parser hook for a tag called googlepres
	 * this will be used to embed a google presentation on a page
	 *
	 */
	public static function wfGooglePresentationParserInit( Parser $parser ) {
		// When the parser sees the <googlepres> tag, it executes
		// the wfGooglePresRender function defined in this class
		$parser->setHook( 'googlepres',array( __CLASS__, 'wfGooglePresRender' ) );

		// Always return true from this hook to continue normal processing
		return true;
	}

	/*
	 * render an embeded google presentation on the page
	 *
	 * @param string $input the contents inside the <googlepres> tag. for now this is
	 *  required to be a link to a google presentation such as:
	 *  https://docs.google.com/presentation/d/1jdIeJ5-NwtpzqGemM8vVF-CNK5Q2iR_6GN4OzfmNGZ8/edit#slide=id.p
	 * @param array $args Tag arguments, which are entered like HTML tag attributes;
	 *  this is an associative array indexed by attribute name.
	 * @param Parser $parser the parser. can be used to get the title and wikitext if needed
	 * @param PPFrame $frame The parent frame (used for more context if needed)
	 * @return String representing the html <embed> code to be inserted onto the page or
	 *  a blank string if the google id cannot be found
	 */
	public static function wfGooglePresRender( $input, array $args, Parser $parser, PPFrame $frame ) {

		// extract the sheet id from the $input then embed it in an iframe like this:
		// <iframe src="https://docs.google.com/presentation/d/1jdIeJ5-NwtpzqGemM8vVF-CNK5Q2iR_6GN4OzfmNGZ8/embed?start=true&loop=false&delayms=3000" frameborder="0" width="480" height="299" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true"></iframe>
		// this is the same code that is given by google when you 'publish to the web' a document

		// remove any whitespace surrounding the $input
		$input = trim( $input );
		// make sure the input has the format we are looking for
		if ( strpos( $input, 'docs.google.com/presentation/d' ) === false ) {
			return;
		}

		// extract the 4rd path param which is our id
		// escape any html special characters so that no one can inject any other html in here
		$path = parse_url( $input, PHP_URL_PATH );
		$path = explode( '/', $path );
		$gid = htmlspecialchars( $path[3] );

		// wrap it all in two divs so we can style it to take up
		// the full width available
		$ret = '<div class="gpresentation_box">';
		$ret .= '<div class="gpresentation">';
		// create the iframe we will return..only inserting the id of the google doc
		$ret .= '<iframe src="https://docs.google.com/presentation/d/';
		$ret .= $gid;
		$ret .= '/embed?';
		$ret .= 'start=false&loop=true&delayms=3000" frameborder="0" ';
		// important to use 100% width and height because these numbers
		// will show up in the wikitext and cannot be mobile/desktop dependent
		// since only one can be in the parser cache at any given time
		$ret .= 'width="100%" height="100%" allowfullscreen="true" ';

		$ret .= 'mozallowfullscreen="true" webkitallowfullscreen="true">';
		$ret .= '</iframe>';
		$ret .= '</div>';
		$ret .= '</div>';
		return $ret;
	}
}
