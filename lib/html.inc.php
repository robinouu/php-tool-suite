<?php
/**
 * HTML Helpers
 * @package php-tool-suite
 * @subpackage HTML
 */

plugin_require(array('event', 'sanitize'));

/**
 * Parse an HTML string into DOM.
 * It's just a wrapper around the <a href="http://simplehtmldom.sourceforge.net/" target="_blank">simplehtmldom</a> string parser.
 * @param string $html The HTML string to parse.
 * @return a DOM representation of the HTML data
 * @see http://simplehtmldom.sourceforge.net/
 * @subpackage HTML
 */
function dom($html) {
	require_once(dirname(__FILE__).'/vendor/simple_html_dom.php');
	return @str_get_html($html, true, false, DEFAULT_TARGET_CHARSET, false);
}


/**
 * Returns an HTML stylesheet link tag
 * @param array $attrs The link tag attributes ('src', 'media'...)
 * @return an HTML stylesheet link
 * 		<link rel="stylesheet" type="text/css" src="jquery.min.css" />
 * @subpackage HTML
 */
function stylesheet($attrs){
	$attrs = array_merge(array(
		'media' => 'screen,projection,tv',
		'rel' => 'stylesheet',
		'href' => ''
	), $attrs);
	return tag('link', '', $attrs, true);
}


/**
 * Returns an HTML external javascript tag
 * @param array $attrs The script tag attributes ('src', 'defer', 'async'...)
 * @return an HTML external javascript tag
 * 		<script type="text/javascript" src="jquery.min.js"></script>
 * @subpackage HTML
 */
function javascript($attrs, $content = '') {
	$attrs = array_merge(array(
		'type' => 'text/javascript'
	), $attrs);
	return tag('script', $content, $attrs);
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
 * @subpackage HTML
 */
function html5($args) {

	$stylesheets_str = '';
	if( isset($args['stylesheets']) ){
		if( is_array($args['stylesheets']) ) {
			foreach ($args['stylesheets'] as $stylesheet) {
				$stylesheets_str .= stylesheet(array('href' => $stylesheet));
			}
			unset($args['stylesheets']);
		}else{
			$stylesheets_str = (string)$args['stylesheets'];
		}
	}
	$stylesheets_str .= trigger('html/stylesheets');


	$scripts_str = '';
	if( isset($args['scripts']) ){
		if( is_array($args['scripts']) ){
			foreach ($args['scripts'] as $script) {
				$scripts_str .= javascript(array('src' => $script));
			}
			unset($args['scripts']);
		}elseif( is_string($args['scripts']) ){
			$scripts_str .= $args['scripts'];
		}
	}
	$scripts_str .= trigger('html/scripts');

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

	$head .= trigger('html_stylesheets', '');

	$head .= $stylesheets_str;
	$head .= '<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />';
	$head .= trigger('html_head', '');

	$page = tag('head', $head);

	$page .= tag('body', $args['body'] . $scripts_str, $args['bodyAttrs']);

	return '<!DOCTYPE html>' . tag('html', $page, array('lang' => $args['lang'], 'xml:lang' => $args['lang'] ));
}

function code($content, $language) {
	return tag('pre', tag('code', $content, array('data-language' => $language)));
}

/* 
 * Returns a valid W3C image tag
 * @subpackage HTML
 */
function image($attrs) {
	if( !isset($attrs['width']) && !isset($attrs['height']) ){
		list($width, $height, $type, $attr) = getimagesize($attrs['src']);
		$attrs['width'] = $size[0];
		$attrs['height'] = $size[1];
	}
	$attrs = array_merge(array(
		'alt' => '',
		'src' => ''
	), $attrs);
	return tag('img', '', $attrs, true);
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
 * @subpackage HTML
 */
function menu($items = array(), $attrs = array(), $callback = array(), $isNav = false) {

	$html = '';
	if( $isNav ){
		$html .= '<nav role="navigation">';
	}
	$html .= '<ul ' . attrs($attrs) . '>';
	foreach ($items as $key => $value) {
		if( is_string($value) ){
			$html .= '<li>';
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
	plugin_require('scrud');
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

/**
 * @return '<br />'
 * @subpackage HTML
 */
function br() {
	return '<br />';
}

/**
 * @return '<hr />'
 * @subpackage HTML
 */
function hr() {
	return '<hr />';
}

/**
 * Returns an HTML tag
 * @param string $tag The tag name.
 * @param string $content The tag content.
 * @param array $attrs The tag attributes
 * @param boolean $inline If TRUE if specified, the HTML inline format will be used. (for tags like link,br,hr...)
 * @return a properly formatted HTML tag
 * @subpackage HTML
 */
function tag($tag, $content, $attrs = array(), $inline = false) {
	if( $inline ){
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . ' />';
	}else{
		return '<' . $tag . (sizeof($attrs) ? ' ' . attrs($attrs) : '') . '>' . $content . '</' . $tag . '>';
	}
}

/**
 * Returns an HTML title (hn)
 * @param string $content The content of the hn
 * @param int $level The hn hierarchy level (1-6)
 * @param array $attrs The hn attributes
 * @return The hn title tag
 * @subpackage HTML
 */
function title($label, $level = 1, $attrs = array()){
	return tag('h' . $level, $label, $attrs);
}

/**
 * Encodes a text to its HTML representation
 * @param string $content The text content
 * @return The HTML representation of a text.
 * @subpackage HTML
 */
function text($content) {
	return nl2br(htmlspecialchars($content));
}

function paragraph($content, $attrs = array()) {
	return tag('p', $content, $attrs);
}

/**
 * @return a <p> paragraph
 * @see paragraph()
 * @subpackage HTML
 */
function p($content, $attrs = array()){
	return paragraph($content, $attrs);
}


function btn($tag, $label, $attrs = array()) {
	$attrs = array_merge(array('class'=> 'btn'), $attrs);
	return tag($tag, $label, $attrs);
}

function button_submit($label = 'Submit', $attrs = array()) {
	$attrs = array_merge(array(
		'type' => 'submit',
		'name' => 'btnSubmit',
		'value' => $label
	), $attrs);
	return tag('input', '', $attrs, true);
}


/**
 * Returns an HTML tag attributes.
 * @param array $attrs The attributes with their respective string values.
 * @return string an HTML list of attributes, joined by spaces.
 * @subpackage HTML
 */
function attrs($attrs = array()) {
	$html = '';
	if( !is_array($attrs) ){
		return '';	
	}
	$attributes = array();
	foreach ($attrs as $key => $value) {
		if( is_string($key) ){
			$attributes[] = $key . '="' . (string)$value . '"';
		}
	}
	return implode(' ', $attributes);
}

function fieldset($name = '', $content = '', $attrs = array()){
	return tag('fieldset', '<legend>' . htmlspecialchars($name) . '</legend>'.$content, $attrs);
}

/**
 * Returns a valid W3C/WCAG table 
 *
 * @param $options 
 * <ul>
 *	<li>caption string</li>
 *	<li>body array|string </li>
 *	<li>meta array An array of meta key/value pairs</li>
 *	<li>lang string The page lang. Default to current_lang()</li>
 *	<li>stylesheets string Appends stylesheets tags to the head</li>
 *	<li>scripts string Appends scripts tags to the end of the body</li>
 *	<li>body string The page content</li>
 * </ul>
 * @subpackage HTML
 *
 */
function table($options, $attrs = array()){

	$options = array_merge(array(
		'caption' => null,
		'head' => null,
		'body' => null,
		'foot' => null
	), $options);

	$html = '';
	if( is_string($options['caption']) ){
		$html .= tag('caption', $options['caption']);
	}
	if( is_string($options['head']) ){
		$html .= tag('thead', $options['head']);
	}elseif( is_array($options['head']) ){
		$head = '';
		foreach ($options['head'] as $key => $value) {
			$thAttrs = array('scope' => 'col');
			if( is_string($key) ){
				$thAttrs['id'] = $key;
			}
			$head .= tag('th', $value, $thAttrs);
		}
		$html .= tag('thead', tag('tr', $head));
	}
	
	if( is_string($options['foot']) ){
		$html .= tag('tfoot', $options['foot']);
	}elseif( is_array($options['foot']) ){
		$foot = '';
		foreach ($options['foot'] as $key => $value) {
			$thAttrs = array('scope' => 'col');
			if( is_string($key) ){
				$thAttrs['id'] = $key;
			}
			$foot .= tag('td', $value, $thAttrs);
		}
		$html .= tag('tfoot', tag('tr', $foot));
	}

	if( is_string($options['body']) ){
		$html .= tag('body', $options['body']);
	}elseif( is_array($options['body']) ){
		$body = '';
		foreach ($options['body'] as $value) {
			$row = '';
			if( is_array($value) ){
				foreach ($value as $headers => $td) {
					$tdAttr = array();
					if( is_string($headers) ){
						$tdAttr['headers'] = $headers;
					}
					$row .= tag('td', $td, $tdAttr);
				}
				$body .= tag('tr', $row, array('scope' => 'row'));
			}elseif( is_string($value) ){
				$body .= $value;
			}
		}
		$html .= tag('tbody', $body);
	}
	return tag('table', $html, $attrs);
}

?>