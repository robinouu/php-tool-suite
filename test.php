<?php

require_once('lib/core.inc.php');

plugin_require(array('log'));

log_handler(function ($data){
	file_put_contents("file.log", $data . PHP_EOL, FILE_APPEND);
});

$log = array('warn' => array('lang' => 2));
log_var($log);

$log = 'string';
log_var($log);

log_var(1, 1.34, 10e3, 'a', 'ab', array(), function () {});
