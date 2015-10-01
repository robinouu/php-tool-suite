<?php
/**
 * File system
 * @package php-tool-suite
 * @subpackage file system
 */


/**
 * Get a PHP file content
 * @param string $filepath The filepath to load
 * @return string The content served by the PHP file.
 */
function include_file($filepath = null) {
	if( !is_null($filepath) ){
		ob_start();

		include($filepath);
		$fileContent = ob_get_contents();
		ob_end_clean();
		return $fileContent;
	}
	return null;
}

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

/**
 * Browses a directory recursively.
 * @param string $dir The directory to browse
 * @param callable $callback A callback to use for each file in the current directory. The current path is passed to the function.
 * @param callable $callbackAfter A callback called after each browsed directory. The current dir is passed to the function.
 * @param array $ignorePaths An array of paths to ignore.
 * @return boolean TRUE if the directory has been browsed. FALSE otherwise.
 */
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
		call_user_func($cbAfter, $dir);
	}
	return TRUE;
}
