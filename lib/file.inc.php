<?php
/**
 * File
 * @package php-tool-suite
 */
function csv_write($filepath, $data, $colSep = ','){
	$lines = array();
	foreach ($data as $d) {
		$lines[] = implode($colSep, $d);
	}
	return file_put_contents($filepath, implode("\r\n", $lines));
}

function csv_load($filepath, $callback = null, $ignoreFirstLine = false, $colSep = ","){
	$opened = false;
	$all_data = array();
	if (($handle = fopen($filepath, "r")) !== FALSE) {
		$opened = true;
		$l = 0;
		while (($data = fgetcsv($handle, 8000, $colSep)) !== FALSE) {
			++$l;
			if( $ignoreFirstLine && $l == 1 ){
				continue;
			}
			if( is_callable($callback) ){
				$callback($data, $l);
			}elseif (is_string($callback) ){
				call_user_func_array($callback, array($data, $l));
			}
			$all_data[] = $data;
		}
		fclose($handle);
	}
	if( !$opened ){
		return false;
	}
	return $all_data;
}