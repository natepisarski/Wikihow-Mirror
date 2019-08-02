<?php

// Set up this file to autoload the Titus class, so that we don't need
// to explicitly include it in a bunch of spots, like we were doing
// previously.

$wgAutoloadClasses['TitusDB'] = __DIR__ . '/Titus.class.php';
$wgAutoloadClasses['TitusConfig'] = __DIR__ . '/Titus.class.php';
$wgAutoloadClasses['TitusStat'] = __DIR__ . '/Titus.class.php';

$wgAutoloadClasses['TitusData'] = __DIR__ . '/TitusData.php';
