<?php
/**
 * Request
 * @package php-tool-suite
 * @subpackage HTTP request
 */

plugin_require(array('var'));

/**
 * Gets the HTTP request route
 * @see $_SERVER['REQUEST_URI']
 * @return type
 * @subpackage HTTP request
 */
function request_route() {
	request_url();
	return var_get('request/url/path');
}

/**
 * Gets the current request URL
 * @return string the request URL
 * @subpackage HTTP request
 */
function request_url() {
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

/**
 * Gets the root url (without URI component)
 * @return string The root url
 * @subpackage HTTP request
 */
function root_url() {
	request_url();
	$url = var_get('request/url');
	if( isset($url['scheme'], $url['host'], $url['path']) ){
		return $url['scheme'] . '://' . $url['host'] . $url['path'];
	}
	return '';
}

/**
 * Returns a website url (without URI component)
 * @param string $url The full URL to parse 
 * @param bool $noScheme If set to TRUE, the url will be returned without scheme information.
 * @return string the website url
 * @subpackage HTTP request
 */
function url_website($url = null, $noScheme = false) {
	if( is_null($url) ){
		$url = root_url();
	}
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

/**
 * Gets the HTTP referer
 * @return string the HTTP referer
 * @subpackage HTTP request
 */
function request_referer() {
	return $_SERVER['HTTP_REFERER'];
}
