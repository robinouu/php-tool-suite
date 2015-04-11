<?php
/**
 * File system
 * @package php-tool-suite
 */
function mkdir_recursive($dir) {
	return mkdir($dir, 0777, true);
}

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

function make_sure_dir_is_created($directory, $chmod = 0777) {
	if ( !is_dir($directory) ) {
  		return mkdir($directory, 0777, true);
	}
	return true;
}