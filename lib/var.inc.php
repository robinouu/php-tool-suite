 <?php

require_once('core.inc.php');
require_once('array.inc.php');
require_once('crypto.inc.php');

function set_cookie($options) {
	return cookie_var_set($options);
}

function get_cookie($options) {
	return cookie_var_get($options);
}

function cookie_var_set($options) {
	$options = array_merge(array(
		'name' => 'cookie',
		'value' => null,
		'expireAt' => null,
		'path' => '/',
		'domain' => null,
		'encryptionKey' => null,
		'https' => server_is_secure(),
		'httpOnly' => false,
		'encodeURI' => false), $options);

	if( is_array($options['value']) ){
		$options['value'] = serialize($options['value']);
	}else {
		$options['value'] = (string)$options['value'];
	}

	if( is_string($options['encryptionKey']) ){
		encrypt($options['value'], $options['encryptionKey']);
	}

	if( $options['raw'] ){
		 setcookie($options['name'], $options['value'], $options['expireAt'], $options['path'], $options['domain'], $options['https'], $options['httpOnly']);
	}else{
		 setrawcookie($options['name'], rawurlencode($options['value']), $options['expireAt'], $options['path'], $options['domain'], $options['https'], $options['httpOnly']);
	}
}

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

function session_var_set($path = array(), $value = null) {
	return var_set($path, $value, $_SESSION);
}

function session_var_unset($path = array()) {
	return var_unset($path, $_SESSION);
}

function session_var_get($path = array(), $default = null) {
	return var_get($path, $default, $_SESSION);
}

function var_set($path = array(), $value = null, $context = null) {
	if( !$context ){
		$context = $GLOBALS;
	}
	return array_set($context, $path, $value);
}

function var_append($path = array(), $value = null, $context = null) {
	if( !$context ){
		$context = $GLOBALS;
	}
	return array_append($context, $path, $value);
}

function var_unset($path, $context = null) {
	if( !$context ){
		$context = $GLOBALS;
	}
	return array_unset($context, $path);
}

function var_get($path = array(), $default = null, $context = false) {
	if( !$context ){
		$context = $GLOBALS;
	}
	if( !is_null($back = array_get($context, $path)) ){
		return $back;
	}
	if( is_callable($default) ){
		return $default();
	}
	return $default;
}
