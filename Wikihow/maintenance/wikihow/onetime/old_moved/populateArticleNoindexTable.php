<?php

global $IP;
require_once 'commandLine.inc';
require_once "$IP/extensions/wikihow/RobotPolicy.class.php";

RobotPolicy::populateAllMainArticlesNoindex();

