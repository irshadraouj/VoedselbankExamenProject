<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['enable_devlog_alerts'] = 'n';
$config['site_license_key'] = '';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system-configuration-overrides.html

$config['app_version'] = '7.5.6';
$config['encryption_key'] = 'e4d2f4d5dc5ab75d2cf151c3ae76b8b2a3ef1fb2';
$config['session_crypt_key'] = 'eec73f96e6c8883acc9ec316b4d938cfa9bdae63';
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => 'db',
		'database' => 'db',
		'username' => 'db',
		'password' => 'db',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);
$config['show_ee_news'] = 'n';


include SYSPATH.'/../config.php';

// EOF