<?php

require_once dirname(__FILE__) . '/../commandLine.inc';
require_once "$IP/extensions/wikihow/thumbsup/ThumbsEmailNotifications.php";

ThumbsEmailNotifications::sendNotifications();
