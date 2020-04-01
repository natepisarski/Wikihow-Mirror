<?php
// MVC required constants
define('APP_NS', 'ContentPortal');
define('APP_DIR', realpath(__DIR__ . '/../'));
define('MVC_DIR', realpath(__DIR__ . '/../../ext-utils/mvc/'));
define('APP_HTTP_PATH', '/extensions/wikihow/ContentPortal');

define('DISABLED', false);
// Portal specific constants
define('PER_PAGE', 50);
define('IMPERSONATE_ID', 'impersonate_user_id');
define('WH_USER_ID', 'wh_user_id');
define('USER_TOKEN', 'user_token');
define('LOGIN_PATH', 'session/new');
define('REDIRECT_URL', 'redirect_url');
define('PORTAL_CONTACT', 'support@wikihow.com');

define('URL_PREFIX', 'https://www.wikihow.com/');
define('LOGIN_API', 'https://' . WH_DEV_ACCESS_AUTH . '@daikon.wikiknowhow.com/api.php?action=login&format=json');

define('CARRIE', 'Dr. Carrie');
define('DANIEL', 'Daniel');
define('OR', 'Or Gozal');

ini_set('auto_detect_line_endings', TRUE);
date_default_timezone_set('America/Los_Angeles');
