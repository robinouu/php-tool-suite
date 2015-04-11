<?php
/**
 * Request
 * @package php-tool-suite
 */
function redirect($url) {
	header('Location: ' . $url);
	exit;
}

function request_referer() {
	return $_SERVER['HTTP_REFERER'];
}
