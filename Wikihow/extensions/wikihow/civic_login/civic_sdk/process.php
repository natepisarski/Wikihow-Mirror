<?php
include ('Civic_JWT.php');
/**
 * Created by PhpStorm.
 * User: franky
 * Date: 2017/05/16
 * Time: 1:46 PM
 */

// Load system defines
define( '_JEXEC', 1 );
error_reporting(0);
define( 'JPATH_BASE', realpath(dirname(__FILE__).'/../../..' ));
require_once ( JPATH_BASE .'/includes/defines.php' );
require_once ( JPATH_BASE .'/includes/framework.php' );

$app = JFactory::getApplication('site');
$module = JModuleHelper::getModule('civicapp');
$input  = JFactory::getApplication()->input;
$params = new JRegistry();
$params->loadString($module->params);


$tokenstr = $_POST['token'];

$appId = $params['apid'];
$iss = $params['issuer'];
$aud = $params['audience'];
$priv = $params['privatekey'];
$pub = $params['publickey'];
$sec = $params['secret'];
$sip_url = $params['sipurl'];
$sub = $iss;
$keyFromFile = false;


//$civic_JWT = new Civic_JWT($appId,$iss,$aud,$priv,$pub,$sec,$sip_url);
$civic_JWT = new Civic_JWT($appId,$iss,$aud,$priv,$pub,$sec,$sip_url,$keyFromFile, $sub);
//$civic_JWT = new Civic_JWT();
$civic_JWT->createRequestBodyFromToken($tokenstr);
$civic_JWT->createCivicExt();
$result = $civic_JWT->createAuthHeader('scopeRequest/authCode', 'POST');
if (is_array($result)) {
    echo json_encode($result);
} else {
    echo json_encode($civic_JWT->exchangeCode());
}
