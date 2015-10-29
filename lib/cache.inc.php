<?php
/**
 * Cache
 * @package php-tool-suite
 * @subpackage cache
 */

plugin_require(array('fs'));

if( class_exists('Memcache') ){
	$memcachedOptions = array_merge(
		array(
			'host' => '127.0.0.1',
			'port' => 11211
		), var_get('memcached/options')
	);
	$service = new Memcache();
	$service->connect($cacheOptions['host'], $cacheOptions['port']);
	var_set('cache/currentService', $service);
}

if( ($cacheDir = var_get('cache/dir')) == null ){
	var_set('cache/dir', $cacheDir = dirname(__FILE__).'/cache/');
}

if( !is_dir($cacheDir) ){
	mkdir_recursive($cacheDir);
}

/**
 * Cache data into memory if memcached is installed, or into a JSON file by default.
 * @param string $resourceID string the resource identifier
 * @param mixed|callable $data the data to cache.
 * @param string $expire The expiration timestamp. +1 month by default.
 * @return mixed The cached data.
 */
function cache_set($resourceID=null, $data, $expire = null) {
	if( !$expire ) {
		$expire = strtotime('+1 month');
	}
	if( class_exists('Memcache') && ($memcached = var_get('cache/currentService')) != null ){
		return $memcached->set($resourceID, $data, $expire);
	}else{
		$filename = var_get('cache/dir', '') . slug($resourceID) . '.json';
		if (is_file($filename) && filemtime($filename) + $expire < 2*time() ) {
			return cache_get($resourceID);
		}else{
			file_put_contents($filename, json_encode($data));
			return true;
		}
	}
	return false;
}


function cache_get($resourceID=null, $defaultValue=null){
	$value = null;
	if( is_null($resourceID) ){
		return $defaultValue;
	}
	if( class_exists('Memcache') ){
		$memcached = var_get('cache/currentService');
		return $memcached->get($resourceID);
	}else{
		$filename = var_get('cache/dir', '') . slug($resourceID) . '.json';
		if( is_file($filename) ){
			$value = json_decode(file_get_contents($filename), true);
		}
	}
	return !is_null($value) ? $value : $defaultValue;
}
