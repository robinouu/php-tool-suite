<?php

function guid() {
	if( function_exists('com_create_guid') ){
        return com_create_guid();
    }else{
    	require_once('random.inc.php');
        $c = strtoupper(md5(uniqid(rand(), true)));
        $uuid = '{' . substr($c, 0, 8) . '-' . substr($c, 8, 4) . '-' . substr($c,12, 4) . '-' . substr($c, 16, 4) . '-' . substr($c, 20, 12) . '}';
        return $uuid;
    }
}

function server_is_secure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

function path_document_root() {
	return realpath(basename(getenv("SCRIPT_NAME")));
}