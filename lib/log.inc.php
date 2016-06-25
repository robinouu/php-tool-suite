<?php
/**
 * Logger
 * @package php-tool-suite
 * @subpackage Logging
 */

plugin_require('var');

/**
 * Handles the datas that are injected through log methods.
 * You can create advanced log files, or inline ones.
 * @param callable $callback The callback to use when having data from logger.
 * @subpackage Logging
 */
function log_handler($callback){
	var_set('log/callback', $callback);
}

/**
 * Logs a variable in the shell or in HTML format (it uses is_cli() internally)
 * @return type
 * @subpackage Logging
 */
function log_var() {
	$args = func_get_args();
	$res = '';
	
	$callback = var_get('log/callback');
	if( !$callback ){
		var_set('log/callback', $callback = function ($data){
			print $data . PHP_EOL;
		});
	}
	foreach ($args as $arg) {
		$txt = '';
		if( is_array($arg) ){
			$txt = print_r($arg, true);
		}elseif( is_callable($arg) ){
			$txt = 'Callable';
		}else{
			$txt = (string)$arg;
		}

		$callback($txt);

		if( is_cli() ){
			$txt .= PHP_EOL;
		}else{
			$txt .= br();
		}

		$res .= $txt;
	}
	return $res;
}