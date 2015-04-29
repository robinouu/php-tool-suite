<?php
/**
 * Logger
 * @package php-tool-suite
 */

function LOG_INFO($str) {
	print '[INFO]' . $str . '<br />';
}
function LOG_WARNING($str) {
//	error_log($str);
	print '[WARNING]' . $str . '<br />';
}

function LOG_ERROR($str) {
	print '[ERROR]' . $str . '<br />';
	error_log($str);
}

function LOG_ARRAY($arr) {
	print '<pre>';
	var_dump($arr);
	print '</pre>';
}