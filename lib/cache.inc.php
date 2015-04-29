<?php
/**
 * Cache
 * @package php-tool-suite
 * @subpackage cache
 */

plugin_require(array('fs'));

/**
 * Cache data into a file, up to the specified expiration date.
 * @param string $filepath The cached file path.
 * @param mixed|callable $data the data to cache. It can also be the return of a callback.
 * @param string $expire The expiration timestamp. +1 month by default.
 * @return mixed The cached data.
 */
function cache($filename, $data, $expire = null) {
	if( !$expire ) {
		$expire = strtotime('+1 month');
	}
	if (is_file($filename) && filemtime($filename) + $expire < 2*time() ) {
		return file_get_contents($filename);
	}else{
		if( is_callable($data) ){
			ob_start();
			$data();
			$backData = ob_get_contents();
			ob_end_clean();
		}
		else{
			$backData = &$data;
		}
		file_put_contents($filename, $backData);
		return $backData;
	}
}