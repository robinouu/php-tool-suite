<?php
/**
 * Date / Time
 * @package php-tool-suite
 * @subpackage DateTime
 */

/**
 * Formats a date using the specified format
 * @param string $date The date string (see strtotime)
 * @param string $format The format string (see strftime)
 * @subpackage DateTime
 */
function format_date($d = 'now', $format = '%d %B %Y, %H:%M') {
	$datetime = strtotime($d);
	return strftime($format, $datetime);
}
