<?php

function csv_import($filepath, $callback, $ignoreFirstLine = false, $colSep = ","){
	$opened = false;

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
		}
		fclose($handle);
	}
	return $opened;
}