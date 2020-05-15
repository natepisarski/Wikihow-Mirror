<?php

// Blocks bad usernames containing two spaces

$wgHooks['AbortNewAccount'][] = 'AccountCreationFilter::abortNewAccount';
$wgAutoloadClasses['AccountCreationFilter'] = __DIR__ . '/AccountCreationFilter.body.php';
