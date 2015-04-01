<?php

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
function load_gettext_translations($dir, $file = null) {
	if( is_dir($dir) ){
		$lang = current_locale();
		$file = "messages";
		bindtextdomain($file, $dir);
		bind_textdomain_codeset($file, 'UTF-8');
		textdomain($file);
		var_set('i18n/domain', $file);
		return true;
	}
	return false;
}

function load_translations($translations) {
	var_set('i18n/translations', $translations);
}

function load_sql_translations($lang = null) {
	$lang = current_lang();
	if( $lang ){
		$rawTranslations = data('translation', array('where' => array('language' => $lang['id']), 'asArray' => true));
		$translations = array();
		if( $rawTranslations ){
			foreach ($rawTranslations as $translation) {
				
				$translations[$translation['context']] = $translation;
			}
		}
		var_set('i18n/translations', $translations);
		return $translations;
	}	
	return false;
}

function t($str, $lang = null, $castTo = 'string'){
	if( var_get('i18n/domain') !== null ){
		return gettext($str);
	}
	if( $castTo == 'bool' ){
		return mb_strtolower($string) === 'true';
	}
	if( is_numeric($str) ){
		if( is_double($str) ){
			return (double)$str;
		}elseif( is_float($str) ){
			return (float)$str;
		}
	}elseif( is_null($str) || mb_strtolower($str) === 'null' ){
		return null;
	}elseif ( is_string($str) ) {
		$translations = var_get('i18n/translations', array());
		if( isset($translations[$str]['version']) ){
			return $translations[$str]['version'];
		}/*elseif (sql_table_exists('translation')){
			$lang = current_lang();
			$data = data('translation', array('where' => array('language' => $lang['id'], 'context' => $context ? $context : $str)));
			if( $data ){
				return $data['version'];
			}else{
				LOG_INFO('Could not find a traduction or version for the context "' . ($context ? $context : $str));
			}
		}*/
		return $str;
	}
	return '';
}

