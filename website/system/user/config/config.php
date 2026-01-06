<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['site_license_key'] = '';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system-configuration-overrides.html

$config['app_version'] = '7.5.6';
$config['encryption_key'] = '6ed78d7545f63712b5f9860342e8e5a4589a0a86';
$config['session_crypt_key'] = 'eece761d6a34938d7eda59b587f7d2ec6f4d7a0a';
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