<?php
/**
 * Request
 * @package php-tool-suite
 */

plugin_require(array('var'));

function request_route() {
	request_url();
	return var_get('request/url/path');
}

function request_url($parsed = false) {
	$url = var_get('request/url', '');
	if( !$url && isset($_SERVER['SERVER_NAME'])){
		$strUrl = server_is_secure() ? 'https://' : 'http://';
		if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
			$strUrl .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$strUrl .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		var_set('request/url', $url = parse_url($strUrl));
	}
	if( isset($url['scheme'], $url['host'], $url['path']) ){
		return $url['scheme'] . '://' . $url['host'] . $url['path'];
	}
	return '';
}

function root_url() {
	request_url();
	$url = var_get('request/url');
	if( isset($url['scheme'], $url['host'], $url['path']) ){
		return $url['scheme'] . '://' . $url['host'] . $url['path'];
	}
	return '';
}

function url_website($url, $noScheme = false) {
	$urlInfo = parse_url($url);
	if( !isset($urlInfo['path']) || !isset($urlInfo['host']) ){
		return $url;
	}
	if( !$noScheme ){
		return (isset($urlInfo['scheme']) ? $urlInfo['scheme'] : 'http') . '://' .
				(isset($urlInfo['host']) ? $urlInfo['host'] : $urlInfo['path']) .
				(isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
	}else{
		return (isset($urlInfo['host']) ? $urlInfo['host'] : $urlInfo['path']) .
				(isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
	}
}

function request_referer() {
	return $_SERVER['HTTP_REFERER'];
}
