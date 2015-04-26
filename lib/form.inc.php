<?php
/**
 * Forms
 * @package php-tool-suite
 */
require_once('i18n.inc.php');
require_once('html.inc.php');


function form_errors($errors) {
	$c = array();

	foreach ($errors as $key => $errs) {
		$c[] = implode(br(), $errs);
	}
	return tag('div', implode(br(), $c), array('class' => 'msg msg-error'));
}

?>