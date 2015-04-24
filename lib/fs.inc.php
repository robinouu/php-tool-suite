<?php
/**
 * File system
 * @package php-tool-suite
 * @subpackage file system
 */

require_once('core.inc.php');

/**
 * Creates directories recursively.
 * If the path does not exist in the file system, it will be automatically created.
 * @param string $dir The path to create.
 * @return boolean TRUE if the path has been set. FALSE otherwise.
 */
function mkdir_recursive($dir) {
	return mkdir($dir, 0777, true);
}


/**
 * Removes a directory and all of its subdirectories.
 * @param string $dir The directory to remove.
 * @return boolean TRUE if the path has been correctly removed. FALSE otherwise.
 */
function rmdir_recursive($dir) {
	return browse_recursive($dir, 'unlink', 'rmdir');
}

function browse_recursive($dir, $cbEach = null, $cbAfter = null, $ignorePaths = array()) {
	$files = scandir($dir);
	if( !$files ){
		return FALSE;
	}
	foreach( $files as $file ) {
		if( $file === '.' || $file === '..' || in_array($file, $ignorePaths) ){
			continue;
		}
		if( is_dir($dir.'/'.$file) ){
			browse_recursive($dir.'/'.$file, $cbEach, $cbAfter);
		}
		elseif ($cbEach) {
			call_user_func($cbEach, $dir.'/'.$file);
		}
	}
	if( $cbAfter ){
		return call_user_func($cbAfter, $dir);
	}
}
