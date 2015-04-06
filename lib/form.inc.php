<?php

require_once('i18n.inc.php');
require_once('html.inc.php');


function form($content, $attrs = array()) {
	$html = '<form method="POST" ' . attrs($attrs) . '>';
	$html .= $content;
	//$html .= tag('input', '', array('type' => 'hidden', 'name' => $id, 'value' => true));
	$html .= '</form>';
	return $html;
}

function form_errors($errors) {
	$c = array();

	foreach ($errors as $key => $errs) {
		$c[] = implode(br(), $errs);
	}
	return tag('div', implode(br(), $c), array('class' => 'msg msg-error'));
}

?>