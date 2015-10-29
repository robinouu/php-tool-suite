<?php
/**
 * Internationalization
 * @package php-tool-suite
 * @subpackage i18n
 */

plugin_require(array('var', 'event'));

/**
 * Sets the current locale
 * @param string|array $locale The locale to set, or an array of locales ordered by priority. By default, it is set to 'en_US'
 * @return boolean TRUE if a locale have been set, FALSE otherwise.
 */
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

/**
 * Returns the current locale, that has been set by set_locale()
 * @return string The current locale or an empty string.
 */
function current_locale() {
	if( ($locale = session_var_get('i18n/locale')) !== null ) {
		return $locale;
	}
	return '';
}

/**
 * Returns the user preferred locale (uses detect_languages() internally)
 * @return string The user preferred locale or an empty string.
 */
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

/**
 * Returns the current lang
 * @return string The current lang or an empty string
 */
function current_lang() {
	$locale = current_locale();
	if( preg_match('#^([a-z]{2})[\-_]#', $locale, $m) ){
		return $m[1];
	}
	return '';
}


/**
 * Returns the user preferred lang (uses detect_languages() internally)
 * @return string The user preferred lang or an empty string
 */
function preferred_lang() {
	$locale = preferred_locale();
	if( preg_match('#^([a-z]{2})[\-_]#', $locale, $m) ){
		return $m[1];
	}
	return '';
}

/**
 * Detects user preferred languages, based on HTTP_ACCEPT_LANGUAGE request variable
 * @param $forceDetection Force re-parsing the HTTP_ACCEPT_LANGUAGE. Default to TRUE.
 * @return array An array of detected languages, sorted by priority (q).
 */
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

/**
 * Loads translation in memory
 * @param array $options The translation options
 * <ul>
 * 	<li>method string Can be 'gettext', or 'array'.</li>
 * 	<li>dir string Used by gettext method, the directory containing the .po/.mo files</li>
 * 	<li>file string Used by gettext method, the translation filename to load (without the extension)</li>
 * 	<li>codeset string Used by gettext method, 'UTF-8' by default. Change it if you have set a custom locale codeset</li>
 * 	<li>translations array Used by array method, an array of translations (the key contains the context, the value contains the translation).</li>
 * </ul>
 */
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

		on('i18n/translations', function () use ($options) {
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

		on('i18n/translations', function () use (&$options) {
			return $options['translations'];
		});
	}else{
		return false;
	}

	$translations = trigger('i18n/translations');
	$tData = new stdclass;
	$tData->versions = $translations;
	var_set('i18n/translationData', $tData);
	return true;
}

/**
 * Translates a string using current translation method
 * @param string $context The string to translate
 * @param array $args The argument to use for that string. This method works in fact like vsprintf() for translated strings.
 * @see http://php.net/manual/en/function.vsprintf.php
 * @return string The translated string or the context string if not found.
 */
function t($str, $args = array()){
	$translated = $str;
	if( var_get('i18n/domain') !== null ){
		$translated = gettext($str);
	}else{
		$tData = var_get('i18n/translationData');
		if( isset($tData->versions[$str]) ){
			$translated = $tData->versions[$str];
		}
	}
	if( sizeof($args) ){
		return vsprintf($translated, $args);
	}
	return $translated;
}


if( !function_exists('__') ){
	/**
	 * An alias of t() for compatibility purpose
	 */
	function __($str = '') {
		return t($str);
	}
}

