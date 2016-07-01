<?php
/**
 * Logging system
 * 
 * PHP Tool Suite logging system is flexible, you just have to register for the current log handlers (that are basically callable methods)
 * and use log_var to log an information into the loggers.
 * 
 * PTS comes with one default logger that logs to the standard output.
 * 
 * @package php-tool-suite
 * @subpackage Logging
 */

plugin_require('var');

/**
 * Handles the datas that are injected through log methods.
 * You can create advanced log files, or inline ones.
 * @param string $name The name of the log handler
 * @param callable $callback The callback to use when having data from logger.
 * @return TRUE if succeed, FALSE otherwhise.
 * @subpackage Logging
 */
function log_add_handler($name, $callback){
	return var_set('log/callbacks/'.$name, $callback);
}

/**
 * Removes an handler from the log callbacks by name
 * @param string $name The name of the log handler
 * @return TRUE if succeed, FALSE otherwhise.
 * @subpackage Logging
 */
function log_remove_handler($name){
	return var_unset('log/callbacks/'.$name);
}

function log_use_handler($name=null){
	var_set('log/currentHandler', $name);
}

function logmsg(){
	return log_var(func_get_args());
}

/**
 * Logs a variable in the shell or in HTML format (it uses is_cli() internally)
 * @return type
 * @subpackage Logging
 */
function log_var() {
	$args = func_get_args();
	$res = '';

	$callback = array();
	// Current callback if defined
	if( ($currentHandler = var_get('log/currentHandler')) ){
		$callback = var_get('log/callbacks/'.$currentHandler, array());
	}

	// Default callback	
	$callback = var_get('log/callbacks', $callback);
	if( !sizeof($callback) ){
		var_set('log/callbacks', $callback = array('default' => function ($data){
			print $data . (is_cli() ? PHP_EOL : br());
		}));
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
		foreach ($callback as $cb) {
			$cb($txt);
		}

		if( is_cli() ){
			$txt .= PHP_EOL;
		}else{
			$txt .= br();
		}

		$res .= $txt;
	}
	return $res;
}