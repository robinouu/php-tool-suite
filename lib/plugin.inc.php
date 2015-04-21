<?php
/**
 * Plugin
 * @package php-tool-suite
 * @subpackage plugin
 */

require_once('hook.inc.php');
require_once('fs.inc.php');

function plugin_load_dir($dir) {
	browse_recursive($dir, function ($file) {
		plugin_load_file($file);
	}, null, var_get('plugins/disabled', array()));
}

function plugin_load_file($file) {
	if( substr($file, strlen($file) - 4) === '.php' ){		
		require($file);
	}
}

function plugin_require($subpackages = null) {
	if( is_string($subpackages) ){
		$subpackages = array($subpackages);
	}
	if( is_array($subpackages) ){
		foreach ($subpackages as $subpackage) {
			require_once($subpackage . '.inc.php');
		}
	}
}