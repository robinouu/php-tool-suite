<?php
/**
 * File system
 * @package php-tool-suite
 * @subpackage file system
 */

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
	$files = scandir($dir);
    foreach($files as $file) {
        if( $file === '.' || $file === '..' ){
        	continue;
        }
        if( is_dir($dir.'/'.$file) ){
        	rmdir_recursive($dir.'/'.$file);
        }
        else{
        	unlink($dir.'/'.$file);
        }
    }
   	return rmdir($dir);
}
