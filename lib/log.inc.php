<?php

ini_set('xdebug.var_display_max_depth', -1);

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