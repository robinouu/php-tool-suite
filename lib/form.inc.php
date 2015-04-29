<?php
/**
 * Forms
 * @package php-tool-suite
 */

plugin_require(array('i18n', 'html'));

function form_errors($errors) {
	$c = array();

	foreach ($errors as $key => $errs) {
		$c[] = implode(br(), $errs);
	}
	return tag('div', implode(br(), $c), array('class' => 'msg msg-error'));
}

?>