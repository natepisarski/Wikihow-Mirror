<?php

// NOTE from Reuben: I tried adding the sandbox attribute to the <iframe> tag in the
//   embedvideo-embed-clause message below, but youtube needs both
//   allow-scripts and allow-same-origin, which is the same level of security
//   as not sandboxing.
// See: https://blog.dareboost.com/en/2015/07/securing-iframe-sandbox-attribute/

$messages = array();
$messages['en']=
		array(
		'embedvideo-missing-params' => 'EmbedVideo is missing a required parameter.',
		'embedvideo-bad-params' => 'EmbedVideo received a bad parameter.',
		'embedvideo-unparsable-param-string' => 'EmbedVideo received the unparsable parameter string "<tt>$1</tt>".',
		'embedvideo-unrecognized-service' => 'EmbedVideo does not recognize the video service "<tt>$1</tt>".',
		'embedvideo-bad-id' => 'EmbedVideo received the bad id "$1" for the service "$2".',
		'embedvideo-illegal-width' => 'EmbedVideo received the illegal width parameter "$1".',
		'embedvideo-gdpr' => 'Some information may be shared with YouTube',
		'embedvideo-embed-clause' =>
			'<div class="embedvideocontainer"><iframe class="embedvideo" data-src="$1" frameborder="0" allowfullscreen></iframe></div>',
		'embedvideo-embed-clause-popcorn' =>
			'<div class="embedvideocontainer"><iframe class="embedvideo" style="margin-left:-8px;" data-src="$1" frameborder="0" mozallowfullscreen webkitallowfullscreen allowfullscreen></iframe></div>',
		'embedvideo-embed-clause-howcast' =>
			'<div class="embedvideocontainer"><iframe class="embedvideo" data-src="$1" frameborder="0" allowfullscreen></iframe></div>',
		'embedvideo-embed-clause-videojug' =>
			'<div class="embedvideocontainer"><iframe class="embedvideo" data-src="$1" frameborder="0" allowfullscreen></iframe></div>'
	);
