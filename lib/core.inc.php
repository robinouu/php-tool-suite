<?php
/**
 * Core
 * @package php-tool-suite
 * @subpackage core
 */

/**
 * Generates a GUID (Globally Unique Identifier)
 * @return string a unique identifier formatted like this {XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}
 */
function guid() {
	if( function_exists('com_create_guid') ){
		return com_create_guid();
	}else{
		require_once('random.inc.php');
		$c = strtoupper(md5(uniqid(rand(), true)));
		$uuid = '{' . substr($c, 0, 8) . '-' . substr($c, 8, 4) . '-' . substr($c,12, 4) . '-' . substr($c, 16, 4) . '-' . substr($c, 20, 12) . '}';
		return $uuid;
	}
}

/**
 * Returns the security state of the current connection.
 * @return boolean TRUE if a secured connection is active. FALSE otherwhise.
 */
function server_is_secure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

function object_hash($obj) {
	if (is_object($obj)) {
		return spl_object_hash($obj);
	}
	return md5(print_r($obj, true));
}