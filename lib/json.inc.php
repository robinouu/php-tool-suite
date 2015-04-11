<?php
/**
 * JSON
 * @package php-tool-suite
 */
require_once('core.inc.php');
require_once('var.inc.php');

function json_find($data, $key, $value, &$path = array()) {
	foreach ($data as $k => $v) {
		if( is_array($v) ){
			array_push($path, $k);
			$success = json_find($v, $key, $value, $path);
			
			if( $success ){
				return true;
			}
			array_pop($path);

		} else {
			if( $key === $k && $v === $value ){
				return true;
			}
		}
	}
	return false;
}

/*function json_browse(&$data, $callback = null, &$path = array()) {
	foreach ($data as $k => &$v) {
		if( is_array($v) ){
			array_push($path, $k);
			json_browse($v, $callback, $path);
			array_pop($path);
		}elseif( is_callable($callback) ){
			$callback($k, $v, $path);
		}
	}
}*/


function json_browse(&$data, &$path = array(), $callback = null, $callbackStart = null, $callbackEnd = null) {
	if( is_callable($callbackStart) ){
		//var_dump('onStart:', $path);
		$callbackStart($path);
	}
	foreach ($data as $key => &$value) {
		if( is_callable($callback) ){
			$callback($path, $key, $value);
		}
		array_push($path, $key);
		if( is_array($value) ){
			json_browse($value, $path, $callback, $callbackStart, $callbackEnd);
		}   
		array_pop($path);
	}
	if( is_callable($callbackEnd)){
		$callbackEnd($path);
	}

}

function json_set(&$data, $path, $value, $currentPath = array()) {
	// pour chaque sous élément de data, je dois vérifier que la clé existe bien dans
	if( $currentPath === $path ){
		$data = $value;
		return $data;
	}
	foreach ($data as $k => $v) {
		if( is_array($v) ){
			array_push($currentPath, $k);
			$data[$k] = json_set($v, $path, $value, $currentPath);
			array_pop($currentPath);
		}
	}
	return $data;
}

function json_get($data, $path, $currentPath = array()) {
	foreach ($data as $k => $v) {
		if( is_array($v) ){
			array_push($currentPath, $k);
			if( ($back = json_get($v, $path, $currentPath) ) ){
				return $back;
			}
			array_pop($currentPath);
		}elseif( $currentPath === $path && sizeof($currentPath) ){
			return $data;
		}
	}
	return null;
}

function json_is_parent($potentialParentPath, $potentialChildPath) {
	foreach( $potentialParentPath as $k => $v ){
		if( $v !== $potentialChildPath[$k] ){
			return false;
		}
	}
	return true;
}