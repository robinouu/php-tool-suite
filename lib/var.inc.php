<?php

require_once('session.inc.php');
require_once('array.inc.php');

if( !isset($_SESSION['vars']) ){
	$_SESSION['vars'] = array();
}
if( !isset($GLOBALS['vars']) ){
	$GLOBALS['vars'] = array();
}

function session_var_set($path = array(), $value = null) {
	return var_set($path, $value, true);
}

function session_var_unset($path = array()) {
	return var_unset($path, true);
}

function session_var_get($path = array(), $default = null) {
	return var_get($path, $default, true);
}

function var_set($path = array(), $value = null, $persistent = false) {
	if( $persistent ){
		return array_set($_SESSION['vars'], $path, $value);
	}
	return array_set($GLOBALS['vars'], $path, $value);	
}

function var_unset($path, $persistent = false) {
	if( $persistent ){
		return array_unset($_SESSION['vars'], $path);	
	}
	return array_unset($GLOBALS['vars'], $path);	
}

function var_get($path = array(), $default = null, $persistent = false) {
	if( !is_null($back = array_get($persistent ? $_SESSION['vars'] : $GLOBALS['vars'], $path)) ){
		return $back;
	}
	if( is_callable($default) ){
		return $default();
	}
	return $default;
}

function vars($persistent = true) {
	return !$persistent ? $GLOBALS['vars'] : $_SESSION['vars'];
}