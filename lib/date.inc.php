<?php
/**
 * Date / Time
 * @package php-tool-suite
 * @subpackage Date Time
 */

function format_date($d = 'now', $format = '%d %B %Y, %H:%M') {
	$datetime = strtotime($d);
	return strftime($format, $datetime);
}
