<?php
/**
 * Cache
 * @package php-tool-suite
 * @subpackage cache
 */
require_once('fs.inc.php');
require_once('log.inc.php');

if( !var_get('cache/dir') ){
	var_set('cache/dir', path_document_root().'/cache');
}

/**
 * Cache a buffer into a JSON file, up to the specified expiration date.
 * @param string $name the name of cached variable
 * @param callable $cb the function to call. Output will be sent to the JSON file.
 * @param string $expire The expiration timestamp. +1 month by default.
 */
function cache($name, $cb, $expire = null) {
	$dir = var_get('cache/dir');
	make_sure_dir_is_created($dir);
	$filename = $dir.'/'.$name.'.json';
	if( !$expire ) {
		$expire = strtotime('+1 month');
	}
	if (is_file($filename) && filemtime($filename) + $expire < 2*time() ) {
		return json_decode(file_get_contents($filename))->data;
	}else{

		if( is_callable($cb) ){
			ob_start();
			$cb();
			$data = ob_get_contents();
			ob_end_clean();
		}
		else
			$data = $cb;

		file_put_contents($filename, json_encode(array('data' => $data)));
		return $data;
	}
}

/**
 * Get the default cache directory
 * @return string the default cache directory
 */
function cache_dir(){
	return var_get('cache/dir');
}

