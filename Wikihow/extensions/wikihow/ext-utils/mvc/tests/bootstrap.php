<?php
define('ENV', 'test');
$GLOBALS['IP'] = realpath(__DIR__ . "/../../../../../");
global $IP;

require __DIR__ . "./../CLI.php";
require __DIR__ . "./../test_harness/Output.php";
require __DIR__ . "./../test_harness/ControllerTestClass.php";
require __DIR__ . "/Stubs.php";

MVC\Debugger::$disabled = true;

