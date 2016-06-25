<?php
/**
 * Core
 * @package php-tool-suite
 * @subpackage Core
 */


function timer_start(){
	return microtime(true);
}
function timer_end($timer){
	return microtime(true)-$timer;
}

function deref($obj) {
	return $obj;
}

/**
 * Returns a unique hash of an object or a mixed hash value
 * @param mixed $value an object or a mixed value (string, bool, number)
 * @return string a unique hash to identify the value
 * @subpackage Core
 */
function object_hash($obj) {
	if (is_object($obj)) {
		return spl_object_hash($obj);
	}
	return md5(print_r($obj, true));
}

/**
 * Generates a GUID (Globally Unique Identifier)
 * @return string a unique identifier formatted like this {XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX}
 * @subpackage Core
 */
function guid() {
	if( function_exists('com_create_guid') ){
		return com_create_guid();
	}else{
		plugin_require(array('random'));
		$c = strtoupper(md5(uniqid(rand(), true)));
		$uuid = '{' . substr($c, 0, 8) . '-' . substr($c, 8, 4) . '-' . substr($c,12, 4) . '-' . substr($c, 16, 4) . '-' . substr($c, 20, 12) . '}';
		return $uuid;
	}
}


function plugin_load_dir($dir) {
	plugin_require(array('fs', 'var'));

	browse_recursive($dir, function ($file) {
		plugin_load_file($file);
	}, null, var_get('plugins/disabled', array()));
}

function plugin_load_file($file) {
	if( substr($file, strlen($file) - 4) === '.php' ){
		require_once($file);
	}
}

function plugin_require($subpackages = null) {
	if( is_string($subpackages) ){
		$subpackages = array($subpackages);
	}
	if( is_array($subpackages) ){
		foreach ($subpackages as $subpackage) {
			$filename = dirname(__FILE__). '/' . $subpackage . '.inc.php';
			require_once($filename);
		}
	}
}


/**
 * Returns the security state of the current connection.
 * @return boolean TRUE if a secured connection is active. FALSE otherwhise
 * @subpackage Core
 */
function server_is_secure() {
	return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

/**
 * Is the client in CLI mode ?
 * @return TRUE if the client is in CLI mode. FALSE otherwise.
 */
function is_cli(){
	return php_sapi_name() == "cli";
}