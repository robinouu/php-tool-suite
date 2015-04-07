<?php

require_once('hook.inc.php');
require_once('sanitize.inc.php');
require_once('vendor/simple_html_dom.php');

function dom($html) {
	return @str_get_html($html, true, false, DEFAULT_TARGET_CHARSET, false);
}

function stylesheet($attrs){
	return '<link rel="stylesheet" type="text/css" ' . attrs($attrs) . ' />';
}

function javascript($attrs) {
	return '<script type="text/javascript" ' . attrs($attrs) . '></script>';
}

function html5($args) {
	require_once(dirname(__FILE__).'/partial.class.php');

	$stylesheets_str = '';
	if( isset($args['stylesheets']) && is_array($args['stylesheets'])) {
		foreach ($args['stylesheets'] as $stylesheet) {
			$stylesheets_str .= stylesheet(array('href' => $stylesheet));
		}
		unset($args['stylesheets']);
	}

	$scripts_str = '';
	if( isset($args['scripts']) && is_array($args['scripts']) ){
		foreach ($args['scripts'] as $script) {
			$scripts_str .= javascript(array('src' => $script));
		}
		unset($args['scripts']);
	}

	$args = array_merge(array(
	'title' => '',
	'encoding' => 'UTF-8',
	'lang' => current_lang(),
	'description' => '',
	'keywords' => '',
	'body' => '',
	'stylesheets' => $stylesheets_str,
	'scripts' => $scripts_str,
	'webfonts' => ''), $args));

	$head = tag('title', $arg['title']) . 
		tag('meta', '', array('charset' => $args['charset']), true) . 
		tag('meta', '', array('name' => 'description', 'content' => $args['description']), true) . 
		tag('meta', '', array('name' => 'keywords', 'content' => $args['keywords']), true) . 
		tag('meta', '', array('name' => 'viewport', 'content' => $args['viewport'] ? $args['viewport'] : 'width=device-width,initial-scale=1.0'), true)
	

	$head .= hook_do('html_stylesheets');

	$head .= $stylesheets_str;
	$head .= $scripts_str;

	$head .= hook_do('html_head');

	$page .= tag('head', $head);

	$page .= tag('body', $args['body']);

	$page .= hook_do('html_scripts');

	return '<!DOCTYPE html>' . tag('html', $page, array('lang' => $args['lang'], 'xml:lang' => $args['lang'] ));
}

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

function block($str = '', $callback = null) {
	if( $callback ) {
		var_set('site/page/blocks/' . slug($str), $callback);
	}
	//return LOG_ERROR('Vous devez indiquer un callback d\'affichage pour le blog "' . $str . '")');
}

function code($content, $language) {
	return '<pre><code data-language="' . $language . '">' . $content . '</code></pre>';
}

function image($attrs) {
	$attrs = array_merge(array(
		'alt' => '',
		'src' => ''), $attrs);
	return '<img ' . attrs($attrs) . ' />';
}

function block_load($block, $args = array()) {
	$cb = var_get('site/page/blocks/' . slug($block));
	if( is_callable($cb) ) {
		return $cb($args);	
	}
	return $cb;
}

function text_vars($text, $vars) {
	foreach ($vars as $key => $value) {
		if( is_string($value) ){
			$text = str_replace('{#' . $key . '}', $value, $text);
		}
	}
	return $text; 
}


function route($route = '/', $callback = null){
	current_url();
	$url = var_get('site/url/path', '/');

	if( preg_match("#^" . $route . "$#ui", $url, $m) ){
		ob_start();
		$callback($m);
		$content = ob_get_contents();
		ob_end_clean();
		//var_push("route/headers", '');
		print $content;
		var_set('routeFound', true);
		return true;
	}

	return false;	
}

function no_route($callback) {
	if( !var_get('routeFound', false) ){
		$callback();
	}
}

function menu($items = array(), $attrs = array()) {

	$attrs = array_merge(array('class' => 'menu', 'callback' => null), $attrs);

	$cb = null;
	if( is_callable($attrs['callback']) ){
		$cb = $attrs['callback'];
		unset($attrs['callback']);
	}

	$html = '<nav role="navigation"><ul ' . attrs($attrs) . '>';
	foreach ($items as $key => $value) {
		if( is_string($value) ){
			$html .= '<li class="item item-link">';
			if( $cb ){
				$html .= $cb($key, $value);
			}elseif( is_integer($key) ){
				$html .= $value;
			}else {
				$html .= hyperlink($key, $value);
			}
			$html .= '</li>';
		}
	}
	$html .= '</ul></nav>';
	return $html;
}

function hidden($name, $value, $attrs = array()) {
	$attrs = array_merge(array('type' => 'hidden', 'name' => $name, 'value' => $value), $attrs);
	return tag('input', '', $attrs);
}


function format_date($d = 'now', $format = '%d %B %Y, %H:%M') {
	$datetime = strtotime($d);
	return strftime($format, $datetime);
}

function timetag($content = '', $attrs = array()) {
	if( is_string($attrs) ){
		$attrs = array('datetime' => $attrs);
	}else{
		$attrs = array_merge(array('datetime' => date('YYYY-MM-DDThh:mm:ssTZD')), $attrs);
	}
	return tag('time', $content, $attrs);
}

// $list_type can be ul, ol, dl
function datalist($name = '', $filters, $list_type = 'ul' ) {
	//return scrud_get($name);

	$data = scrud_list($name, $filters, isset($filters['formatter']) ? array($name => $filters['formatter']) : array());

	$items = '';
	$slug_name = slug($name);
	if( $list_type == 'ul' || $list_type === 'ol' ){
		$html = '<' . $list_type .'>';
		foreach ($data as $value) {
			$html .= '<li class="data data-' . $slug_name . '">';
			$html .= $value['name'];
			$html .= '</li>';
		}
		$html .= '</' . $list_type . '>';
	}else if( $list_type === 'dl') {
		$html = '<' . $list_type .'>';
		foreach ($data as $key => $value) {
			$html .= '<dt class="data data-' . slug($key) . '">' . ucfirst($key) . '</dt><dd>' . $value . '</dd>';
		}
		$html .= '</' . $list_type . '>';
	}
	return $html;
}

function hyperlink($content = 'Link', $attrs = array()){
	if( is_string($attrs) ){
		$attrs = array('href' => $attrs);
	}else{
		$attrs = array_merge(array(
			'href' => '#'), $attrs);
	}
	return tag('a', $content, $attrs);
}

function br() {
	return '<br />'."\r\n";
}

function hr() {
	return '<hr />' . "\r\n";
}

function tag($tag, $content, $attrs = array(), $inline = false) {
	if( $inline ){
		return '<' . $tag . ' ' . attrs($attrs) . ' />';
	}else{
		return '<' . $tag . ' ' . attrs($attrs) . '>' . $content . '</' . $tag . '>';
	}
}

function search($options = array()) {
	$options = array_merge(array(
		'title' => t('Search for'),
		'form' => array('role' => 'search', 'id' => 'search'),
		'searchField' => array('name' => 'search', 'type' => 'search', 'placeholder' => ''),
		'button.label' => t('Search'),
		'button.field' => array('name' => 'search_submit')
		), $options);

	return form(
		fieldset($options['title'], field($options['searchField']) . button_submit($options['button.label'], $options['button.field'])),
		array($options['form'])
	);
}

function title($label, $level = 1){
	return '<h' . $level . '>' . $label . '</h' . $level . '>';
}

function text($content) {
	return nl2br(htmlspecialchars($content));
}

function span($attrs = array()) {
	if( is_string($attrs) ){
		$attrs = array('class' => $attrs);
	}
	return '<span ' . attrs($attrs) . '></span>';
}

function button($content = '', $attrs = array()) {
	$attrs = array_merge(array('class'=> 'btn'), $attrs);
	return tag('a', $content, $attrs);
}

function button_submit($label = 'Submit', $attrs = array()) {
	//static $ids = 0;
	if( !isset($attrs['name']) ){
		$attrs['name'] = slug($label);
	}
	$attrs = array_merge(array('type' => 'submit', 'value' => $label), $attrs);
	//++$ids;
	return tag('input', '', $attrs, true);
}

function attrs($attrs = array()) {
	$html = '';
	if( !is_array($attrs) ){
		LOG_ARRAY($attrs);
		print 'ERROR';
		return $html;	
	}
	$attributes = array();
	foreach ($attrs as $key => $value) {
		if( is_string($value) && is_string($key) ){
			$attributes[] = $key . '="' . $value . '"';
		}
	}
	return implode(' ', $attributes);
}

function fieldset($name = '', $content = ''){
	$html = '<fieldset><legend>' . htmlspecialchars($name) . '</legend>';
	$html .= $content;
	$html .= '</fieldset>';
	return $html;
}


?>