<?php
/**
 * Variables and cookies
 *
 * Generic variable accessors.
 * You can use paths to access a particular variable, like the equivalent 'foo/bar' and array('foo', 'bar') parameters.
 * Handle cookie variables, session variables and generic arrays.
 * 
 * @subpackage variables
 */

require_once('core.inc.php');
require_once('array.inc.php');
require_once('crypto.inc.php');


/**
 * Set a global var.
 *
 * @param string|array $path The variable path.
 * @param mixed $value The value to insert.
 * @param array $data The reference data array (if NULL, $_GLOBALS will be used)
 * @return boolean TRUE if the variable has been set. FALSE otherwise.
 */
function var_set($path = array(), $value = null, $data = null) {
	if( !$data ){
		$data = $GLOBALS;
	}
	$contextVar = array_get($data, array_get($GLOBALS, 'var/context'));
	return array_set($contextVar, $path, $value);
}

/**
 * Get a global variable.
 *
 * @param string|array $path The variable path.
 * @param mixed $default The value to return if the variable is not set. Can be a callback. NULL by default.
 * @param array $data The context array where to find the variable. By default, and if NULL, $_GLOBALS will be used.
 * @return mixed The global variable value.
 */
function var_get($path = array(), $default = null, $data = false) {
	if( !$data ){
		$data = $GLOBALS;
	}
	$contextVar = array_get($data, array_get($GLOBALS, 'var/context'));
	if( !is_null($back = array_get($contextVar, $path)) ){
		return $back;
	}
	if( is_callable($default) ){
		return $default();
	}
	return $default;
}


/**
 * Append a value to a global variable array.
 *
 * @param string|array $path The variable path.
 * @param mixed $value The value to append.
 * @param array $data The reference data array where to append the value. By default, and if NULL, $_GLOBALS will be used.
 * @return boolean TRUE if the variable has been added. FALSE otherwise.
 */
function var_append($path = array(), $value = null, $data = null) {
	if( !$data ){
		$data = $GLOBALS;
	}
	$contextVar = array_get($data, array_get($GLOBALS, 'var/context'));
	return array_append($contextVar, $path, $value);
}

/**
 * Unset a global variable.
 *
 * @param string|array $path The variable path.
 * @param array The context array where to unset the variable. By default, and if NULL, $_GLOBALS will be used.
 * @return boolean TRUE if the variable has been unset. FALSE otherwise.
 */
function var_unset($path, $data = null) {
	if( !$data ){
		$data = $GLOBALS;
	}
	$contextVar = array_get($data, array_get($GLOBALS, 'var/context'));
	return array_unset($contextVar, $path);
}


/**
 * Set a session var.
 *
 * @param string|array $path The path where to insert the value.
 * @param mixed $value The value to insert.
 * @return boolean TRUE if the variable has been set. FALSE otherwise.
 */
function session_var_set($path = array(), $value = null) {
	return var_set($path, $value, $_SESSION);
}



/**
 * Unset a session var.
 *
 * @param string|array $path The path where to delete the variable.
 * @return boolean TRUE if the variable has been unset. FALSE otherwise.
 */
function session_var_unset($path = array()) {
	return var_unset($path, $_SESSION);
}

/**
 * Get a session variable value.
 *
 * @param string|array $path The variable path.
 * @param string|array $default The value to use if the variable is not set.
 * @return mixed The variable value.
 */
function session_var_get($path = array(), $default = null) {
	return var_get($path, $default, $_SESSION);
}

/**
 * Set a cookie
 *
 * @param array $options The cookie options.
 * @return boolean TRUE if the cookie has been correctly set. FALSE otherwise.
 * @see cookie_var_set()
 */
function set_cookie($options) {
	return cookie_var_set($options);
}

/**
 * Get a cookie value.
 *
 * @param array $options The cookie options.
 * @return mixed The cookie value.
 * @see cookie_var_get()
 */
function get_cookie($options) {
	return cookie_var_get($options);
}

/**
 * Set a cookie 
 *
 * @param array $options The cookie options.
 * <ul>
 * 	<li>name string The cookie unique id. Required.</li>
 * 	<li>value mixed The value to set. NULL by default.</li>
 * 	<li>expireAt null|int Expiration timestamp or NULL if you don't want an expiration date. NULL by default.</li>
 * 	<li>path string The cookie uri path. '/' by default.</li>
 * 	<li>domain null|string The cookie domain scope. NULL by default.</li>
 * 	<li>encryptionKey null|string If mcrypt is loaded, encrypt cookie data using this key. NULL by default.</li>
 * 	<li>raw boolean If TRUE, send cookie without URL encoding. FALSE by default.</li>
 * 	<li>https boolean The security parameter of your transmission. By default, server_is_secure() is used.</li>
 * 	<li>httpOnly boolean If TRUE, the cookie will be only accessible for HTTP connections. FALSE by default.</li>
 * </ul>
 * @link http://php.net/manual/en/function.setcookie.php
 */
function cookie_var_set($options) {
	$options = array_merge(array(
		'value' => null,
		'expireAt' => null,
		'path' => '/',
		'domain' => null,
		'encryptionKey' => null,
		'raw' => false,
		'https' => server_is_secure(),
		'httpOnly' => false), $options);

	if( !isset($options['name']) ){
		return FALSE;
	}

	if( is_array($options['value']) ){
		$options['value'] = serialize($options['value']);
	}else {
		$options['value'] = (string)$options['value'];
	}

	if( is_string($options['encryptionKey']) ){
		encrypt($options['value'], $options['encryptionKey']);
	}


	if( $options['raw'] ){
		$res = setcookie($options['name'], $options['value'], $options['expireAt'], $options['path'], $options['domain'], $options['https'], $options['httpOnly']);
	}else{
		$res = setrawcookie($options['name'], rawurlencode($options['value']), $options['expireAt'], $options['path'], $options['domain'], $options['https'], $options['httpOnly']);
	}

	return $res;
}

/**
 * Get a cookie value.
 *
 * @param array $options The cookie options.
 * <ul>
 * 	<li>name string The cookie unique name </li>
 * 	<li>defaultValue mixed The default cookie value. NULL by default.</li>
 *  <li>encryptionKey null|string The encryption key used to decode content. NULL by default.</li>
 * </ul>
 * @return mixed The cookie value.
 */
function cookie_var_get($options){
	$options = array_merge(array(
		'name' => 'cookie',
		'defaultValue' => null,
		'encryptionKey' => false), $options);

	$value = $_COOKIE[$options['name']];

	if( is_string($options['encryptionKey']) ){
		decrypt(stripslashes($value), $options['encryptionKey']);
	}

	$asArray = @unserialize(stripslashes($value));
	if( is_array($asArray) ){
		return $asArray;
	}
	return $value;
}



/**
 * Add a global context to all next variable accessor calls
 *
 * @param string|array $context The path of the context. NULL by default. 
 */
function contextify($context = null) {
	array_set($context, 'var/context', $GLOBALS);
}
