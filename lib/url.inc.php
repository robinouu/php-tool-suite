<?php
/**
 * URLs
 * @package url
 */


function current_url() {
	$url = var_get('site/url', '');
	if( !$url && isset($_SERVER['SERVER_NAME'])){
		$url = server_is_secure() ? 'https://' : 'http://';
		if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
			$url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		var_set('site/url', parse_url($url));
	}
	return $url;
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