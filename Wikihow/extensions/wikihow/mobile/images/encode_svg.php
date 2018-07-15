<?php

if (!empty($_GET)) exit;
if (!isset($argv[1])) {
	print "usage: php encode_svg.php file.svg\n";
	exit;
}

$encoded = base64_encode( file_get_contents($argv[1]) );
print "url('data:image/svg+xml;base64,$encoded')\n";
