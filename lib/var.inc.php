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

plugin_require(array('array', 'crypto'));

/* Scoped global variables */
function &vars() {
	static $vars = array();
	return $vars;
}

/**
 * Sets a variable.
 *
 * @param string|array $path The variable path.
 * @param mixed $value The value to insert.
 * @param array $data The reference data array (if NULL, vars() will be used)
 * @return boolean TRUE if the variable has been set. FALSE otherwise.
 */
function var_set($path = array(), $value = null, $data = null) {
	if( is_null($data) ){
		$data = &vars();
	}
	return array_set($data, $path, $value);
}

/**
 * Gets a variable.
 *
 * @param string|array $path The variable path.
 * @param mixed $default The value to return if the variable is not set. Can be a callback. NULL by default.
 * @param array $data The context array where to find the variable. By default, and if NULL, vars() will be used.
 * @return mixed The global variable value.
 */
function var_get($path = array(), $default = null, $data = null) {
	if( is_null($data) ){
		$data = &vars();
	}
	if( !is_null($back = array_get($data, $path)) ){
		return $back;
	}
	if( is_callable($default) ){
		return $default();
	}
	return $default;
}


/**
 * Appends a key-value pair to a variable.
 *
 * @param string|array $path The variable path.
 * @param string $key The associative key to append.
 * @param mixed $value The associative value to append.
 * @param array $data The reference data array where to append the value. By default, and if NULL, vars() will be used.
 * @return boolean TRUE if the variable has been added. FALSE otherwise.
 */
function var_append($path = array(), $key, $value = null, $data = null) {
	if( is_null($data) ){
		$data = &vars();
	}
	return array_append($data, $path, $key, $value);
}

/**
 * Unsets a variable.
 *
 * @param string|array $path The variable path.
 * @param array The context array where to unset the variable. By default, and if NULL, vars() will be used.
 * @return boolean TRUE if the variable has been unset. FALSE otherwise.
 */
function var_unset($path, $data = null) {
	if( is_null($data) ){
		$data = &vars();
	}
	return array_unset($data, $path);
}


/**
 * Sets a session var.
 *
 * @param string|array $path The path where to insert the value.
 * @param mixed $value The value to insert.
 * @return boolean TRUE if the variable has been set. FALSE otherwise.
 */
function session_var_set($path = array(), $value = null) {
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $_SESSION;
	foreach ($segments as $segment) {
		if( !is_array($cur) ){
			$cur = array($segment => array());
		}
		if( !isset($cur[$segment]) ){
			$cur[$segment] = array();
		}
		$cur =& $cur[$segment];
	}
	$cur = $value;
	return true;
}



/**
 * Unsets a session var.
 *
 * @param string|array $path The path where to delete the variable.
 * @return boolean TRUE if the variable has been unset. FALSE otherwise.
 */
function session_var_unset($path = array()) {
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $_SESSION;

	$i = 0;
	$size = sizeof($segments);
	foreach ($segments as $segment) {
		if( !isset($cur[$segment]) ){
			return FALSE;
		}
		if( $i == $size - 1) {
			unset($cur[$segment]);
			return TRUE;
		}
		$cur =& $cur[$segment];
		++$i;
	}
	
	return TRUE;
}

/**
 * Gets a session variable value.
 *
 * @param string|array $path The variable path.
 * @param string|array $default The value to use if the variable is not set.
 * @return mixed The variable value.
 */
function session_var_get($path = array(), $default = null) {
	if (!$path)
		return $_SESSION;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur = $_SESSION;
	foreach ($segments as $segment) {
		if (!isset($cur[$segment])){
			return null;
		}
		$cur = $cur[$segment];
	}
	return $cur;
}

/**
 * Sets a cookie
 *
 * @param array $options The cookie options.
 * @return boolean TRUE if the cookie has been correctly set. FALSE otherwise.
 * @see cookie_var_set()
 */
function set_cookie($options) {
	return cookie_var_set($options);
}

/**
 * Gets a cookie value.
 *
 * @param array $options The cookie options.
 * @return mixed The cookie value.
 * @see cookie_var_get()
 */
function get_cookie($options) {
	return cookie_var_get($options);
}

/**
 * Sets a cookie 
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
 * Gets a cookie value.
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
