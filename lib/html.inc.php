<?php
/**
 * HTML Helpers
 * @package php-tool-suite
 * @subpackage HTML helpers
 */
require_once('hook.inc.php');
require_once('sanitize.inc.php');
require_once('vendor/simple_html_dom.php');

/**
 * Parse an HTML string into DOM.
 * It's just a wrapper around the <a href="http://simplehtmldom.sourceforge.net/" target="_blank">simplehtmldom</a> string parser.
 * @param string $html The HTML string to parse.
 * @return a DOM representation of the HTML data
 * @see http://simplehtmldom.sourceforge.net/
 */
function dom($html) {
	return @str_get_html($html, true, false, DEFAULT_TARGET_CHARSET, false);
}


/**
 * Returns an HTML stylesheet link tag
 * @param array $attrs The link tag attributes ('src', 'media'...)
 * @return an HTML stylesheet link
 * 		<link rel="stylesheet" type="text/css" src="jquery.min.css" />
 */
function stylesheet($attrs){
	return '<link rel="stylesheet" type="text/css" ' . attrs($attrs) . ' />';
}


/**
 * Returns an HTML external javascript tag
 * @param array $attrs The script tag attributes ('src', 'defer', 'async'...)
 * @return an HTML external javascript tag
 * 		<script type="text/javascript" src="jquery.min.js"></script>
 */
function javascript($attrs) {
	return '<script type="text/javascript" ' . attrs($attrs) . '></script>';
}

/**
 * Returns a minimal template of an HTML 5 valid page
 * @param array $options The page options
 * <ul>
 *	<li>title string The page title</li>
 *	<li>meta array An array of meta key/value pairs</li>
 *	<li>lang string The page lang. Default to current_lang()</li>
 *	<li>stylesheets string Appends stylesheets tags to the head</li>
 *	<li>scripts string Appends scripts tags to the end of the body</li>
 *	<li>body string The page content</li>
 * </ul>
 * @return string The HTML 5 template.
 */
function html5($args) {

	$stylesheets_str = '';
	if( isset($args['stylesheets']) && is_array($args['stylesheets'])) {
		foreach ($args['stylesheets'] as $stylesheet) {
			$stylesheets_str .= stylesheet(array('href' => $stylesheet));
		}
		unset($args['stylesheets']);
	}
	$stylesheets_str .= hook_do('html/stylesheets');


	$scripts_str = '';
	if( isset($args['scripts']) && is_array($args['scripts']) ){
		foreach ($args['scripts'] as $script) {
			$scripts_str .= javascript(array('src' => $script));
		}
		unset($args['scripts']);
	}
	$scripts_str .= hook_do('html/scripts');

	$args = array_merge(array(
		'title' => '',
		'meta' => array(
			'charset' => 'UTF-8',
			'description' => '',
			'keywords' => '',
			'viewport' => 'width=device-width,initial-scale=1.0',
		),
		'lang' => current_lang(),
		'body' => '',
		'stylesheets' => $stylesheets_str,
		'scripts' => $scripts_str
	), $args);

	$head = tag('title', $args['title']);

	foreach ($args['meta'] as $key => $value) {
		$head .= tag('meta', '', array('name' => $key, 'content' => $value), true);
	}

	$head .= hook_do('html_stylesheets');

	$head .= $stylesheets_str;

	$head .= hook_do('html_head');

	$page = tag('head', $head);

	$page .= tag('body', $args['body'] . $scripts_str);

	$page .= hook_do('html_scripts');

	return '<!DOCTYPE html>' . tag('html', $page, array('lang' => $args['lang'], 'xml:lang' => $args['lang'] ));
}


function block($block = '', $callback = null) {
	hook_register('html/blocks/' . object_hash($block), $callback);
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
	return hook_do('html/blocks/' . object_hash($block), $args);
}

function text_vars($text, $vars) {
	foreach ($vars as $key => $value) {
		if( is_string($value) ){
			$text = str_replace('{#' . $key . '}', $value, $text);
		}
	}
	return $text; 
}

/**
 * Returns an HTML representation of a menu.
 * @param array $items The menu items. If the array is associative, creates hyperlinks for values.
 * @param array $attrs The ul attributes.
 * @param callable $callback An optional callback to use to print the items content.
 * @param boolean $isNav If set to TRUE, wraps the menu with a navigation ARIA role on a nav tag.
 * @return An HTML representation of a menu
 */
function menu($items = array(), $attrs = array(), $callback = array(), $isNav = false) {

	$html = '';
	if( $isNav ){
		$html .= '<nav role="navigation">';
	}
	$html .= '<ul ' . attrs($attrs) . '>';
	foreach ($items as $key => $value) {
		if( is_string($value) ){
			$html .= '<li class="item item-link">';
			if( is_callable($callback) ){
				$html .= $callback($key, $value);
			}elseif( is_integer($key) ){
				$html .= $value;
			}else {
				$html .= hyperlink($key, $value);
			}
			$html .= '</li>';
		}
	}
	$html .= '</ul>';
	if( $isNav ){
		$html .= '</nav>';
	}
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

/**
 * Returns an HTML tag
 * @param string $tag The tag name.
 * @param string $content The tag content.
 * @param array $attrs The tag attributes
 * @param boolean $inline If TRUE if specified, the HTML inline format will be used. (for tags like link,br,hr...)
 * @return a properly formatted HTML tag
 */
function tag($tag, $content, $attrs = array(), $inline = false) {
	if( $inline ){
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . ' />';
	}else{
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . '>' . $content . '</' . $tag . '>';
	}
}

function search($options = array()) {
	$options = array_merge(array(
		'title' => t('Search for'),
		'form' => array('role' => 'search', 'id' => 'search', 'method' => 'POST'),
		'searchField' => array('name' => 'search', 'type' => 'search', 'placeholder' => ''),
		'button.label' => t('Search'),
		'button.field' => array('name' => 'search_submit')
		), $options);

	return tag('form', fieldset($options['title'], field($options['searchField']) . button_submit($options['button.label'], $options['button.field'])), $options['form']);
}

/**
 * Returns an HTML title (hn)
 * @param string $content The content of the hn
 * @param int $level The hn hierarchy level (1-6)
 * @param array $attrs The hn attributes
 * @return The hn title tag
 */
function title($label, $level = 1, $attrs = array()){
	return tag('h' . $level, $label, $attrs);
}

/**
 * Encodes a text to its HTML representation
 * @param string $content The text content
 * @return The HTML representation of a text.
 */
function text($content) {
	return nl2br(htmlspecialchars($content));
}

function paragraph($content, $attrs = array()) {
	return tag('p', $content, $attrs);
}
function p($content, $attrs = array()){
	return paragraph($content, $attrs);
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


/**
 * Returns an HTML tag attributes.
 * @param array $attrs The attributes with their respective string values.
 * @return string an HTML list of attributes, joined by spaces.
 */
function attrs($attrs = array()) {
	$html = '';
	if( !is_array($attrs) ){
		LOG_ARRAY($attrs);
		print 'ERROR';
		return $html;	
	}
	$attributes = array();
	foreach ($attrs as $key => $value) {
		if( is_string($key) ){
			$attributes[] = $key . '="' . (string)$value . '"';
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