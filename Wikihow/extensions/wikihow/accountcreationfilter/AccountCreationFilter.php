<?php

/// Written by Gershon Bialer on 1/21/2014
/// Blocks bad usernames containing two spaces

$wgHooks['AbortNewAccount'][] = 'AccountCreationFilter::abortNewAccount';
$wgAutoloadClasses['AccountCreationFilter'] = dirname( __FILE__ ) . '/AccountCreationFilter.body.php';

