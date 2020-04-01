<?php
/**
 * PHP script to stream out an image file (meant for dev only)
 *
 */

/**
 * Handle an image request which was a 404 (since dev doesn't have the prod images)
 * based off of wfThumbHandle404 in thumb.php
 *
 * @return void
 */
function wfImageHandle404() {
	$matches = WebRequest::getPathInfo();

	if ( !isset( $matches['title'] ) ) {
		wfThumbError( 404, 'Could not determine the name of the requested image.' );
		return;
	}

	$params = wfExtractImageRequestInfo( $matches['title'] ); // basic wiki URL param extracting
	if ( $params == null ) {
		wfThumbError( 400, 'The specified image parameters are not recognized.' );
		return;
	}

	wfStreamImage( $params ); // stream the thumbnail
}

/**
 * Convert pathinfo type parameter, into normal request parameters
 * based off of wfExtractThumbRequestInfo in thumb.php
 *
 * So for example, if the request was redirected from
 * /w/images/a/ab/Foo.png then this method is responsible for turning that into an array
 * with the folowing key:
 *  * f => the filename (Foo.png)
 *
 * @param $imagePath String path to the image
 * @return Array|null associative params array or null
 */
function wfExtractImageRequestInfo( $imagePath ) {
	$repo = RepoGroup::singleton()->getLocalRepo();

	$hashDirReg = $subdirReg = '';
	for ( $i = 0; $i < $repo->getHashLevels(); $i++ ) {
		$subdirReg .= '[0-9a-f]';
		$hashDirReg .= "$subdirReg/";
	}
	$regexp = "!^((images/)?$hashDirReg([^/]*))$!";
	if ( preg_match( $regexp, $imagePath, $m ) ) {
		list( /*all*/, $rel, $images, $filename ) = $m;
	} else {
		return null;
	}

	$params = array( 'f' => $filename );
	return $params;
}

/**
 * Stream an image.  will try to get it from s3
 * based off of wfStreamThumb in thumb.php
 *
 * @param $params Array name of the file to stream
 * @return void
 */
function wfStreamImage( array $params ) {
	$fileName = isset( $params['f'] ) ? $params['f'] : '';
	$fileName = strtr( $fileName, '\\', '_' );

	$headers = array(); // HTTP headers to send
	$disp = FileBackend::makeContentDisposition( 'inline', $fileName );
	$headers[] = "Content-Disposition: {$disp}";

	$img = wfLocalFile( $fileName );

	// Check the source file title
	if ( !$img ) {
		wfThumbError( 404, wfMessage( 'badtitletext' )->text() );
		return;
	}
	if ( !$img->exists() ) {
		wfThumbError( 404, "The source file '$fileName' does not exist." );
		return;
	} elseif ( $img->getPath() === false ) {
		wfThumbError( 500, "The source file '$fileName' is not locally accessible." );
		return;
	}

	// get the file from s3 if possible
	DevImageHooks::onFileThumbName( $img );
	$img->getRepo()->streamFile( $img->getPath(), $headers );
	return;
}
