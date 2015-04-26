<?php
/**
 * Internationalization
 * @package php-tool-suite
 */
require_once('var.inc.php');

if( !function_exists('__') ){
	function __($str = '') {
		return t($str);
	}
}

function set_locale($locales) {
	if( !is_array($locales) ){
		$locales = array($locales);
	}

	$locales[] = 'en_US';

	foreach ($locales as $key => $value) {
		if( is_string($key) ){
			$locale = $key;
		}
		elseif( is_numeric($key) && is_string($value) ){
			$locale = $value;
		}

		$windowsLocale = str_replace('_', '-', str_replace('.UTF-8', '', $locale));
		$finalLocale = setlocale(LC_ALL, $locale, $windowsLocale);
		if( $finalLocale ){
			break;
		}
	}

	if( !$finalLocale ) {
		return false;
	}else{
		putenv('LC_ALL=' . $finalLocale);
		session_var_set('i18n/locale', $finalLocale);
		return true;
	}

	return false;
}

function current_lang() {
	$locale = current_locale();
	if( preg_match('#^([a-z]{2})[\-_]#', $locale, $m) ){
		return $m[1];
	}
	return '';
}

function preferred_lang() {
	$locale = preferred_locale();
	if( preg_match('#^([a-z]{2})[\-_]#', $locale, $m) ){
		return $m[1];
	}
	return '';
}

function current_locale() {
	if( ($locale = session_var_get('i18n/locale')) !== null ) {
		return $locale;
	}
	return '';
}

function preferred_locale() {
	$languages = detect_languages();

	foreach ($languages as $key => $value) {
		$locale = $value;
		if( is_string($key) ){
			$locale = $key;
		}
		if( preg_match('/^(.+\-.+)(\.UTF\-8)?$/', $locale, $m) ){
			return str_replace('-', '_', $m[1]) . '.UTF-8';
		}
	}
	return '';
}

function detect_languages($forceDetection = true) {
	$langs = array();
	if( !$forceDetection && ($langs = var_get('i18n/userLanguages')) !== null ){
		return $langs;
	}
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		// break up string into pieces (languages and q factors)
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

		if (count($lang_parse[1])) {
			// create a list like "en" => 0.8
			$langs = array_combine($lang_parse[1], $lang_parse[4]);

			// set default to 1 for any without q factor
			foreach ($langs as $lang => $val) {
				if ($val === '') {
					$langs[$lang] = 1;
				}
			}
			// sort list based on value	
			arsort($langs, SORT_NUMERIC);
		}
	}
	var_set('i18n/userLanguages', $langs);
	return array_keys($langs);
}

function load_translations($options = array()) {
	$options = array_merge(array(
		'method' => 'array'
	), $options);

	if( $options['method'] === 'gettext' ){
		
		$options = array_merge(array(
			'dir' => dirname(__FILE__),
			'file' => 'messages',
			'codeset' => 'UTF-8'
		), $options);

		hook_register('i18n/translations', function () use ($options) {
			if( is_dir($options['dir']) ){
				$lang = current_locale();
				bindtextdomain($options['file'], $options['dir']);
				bind_textdomain_codeset($options['file'], $options['codeset']);
				textdomain($options['file']);
				var_set('i18n/domain', $options['file']);
				return true;
			}
		});
	}elseif( $options['method'] === 'array' ){
		$options = array_merge(array(
			'translations' => array()
		), $options);

		hook_register('i18n/translations', function () use (&$options) {
			return $options['translations'];
		});
	}

	$translations = hook_do('i18n/translations');

	$tdata = new stdclass();
	$tdata->versions = $translations;
	var_set('i18n/translationData', $tdata);
}

function t($str, $lang = null, $castTo = 'string'){

	if( var_get('i18n/domain') !== null ){
		return gettext($str);
	}
	if( $castTo == 'bool' ){
		return strtolower($string) === 'true';
	}
	if ( is_string($str) ) {
		
		$translations = var_get('i18n/translationData', new stdclass);

		if( isset($translations->versions[$str]) ){
			return $translations->versions[$str];
		}
		return $str;
	}elseif( is_numeric($str) ){
		if( is_double($str) ){
			return (double)$str;
		}elseif( is_float($str) ){
			return (float)$str;
		}
	}elseif( is_null($str) || mb_strtolower($str) === 'null' ){
		return null;
	}
	return '';
}

