<?php

function server_is_secure() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

function path_document_root() {
	return realpath(basename(getenv("SCRIPT_NAME")));
}