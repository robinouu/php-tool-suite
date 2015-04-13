<?php
/**
 * File
 * @package php-tool-suite
 * @subpackage files
 */

/**
 * Write a CSV file using array data
 * @param string $filepath The csv filepath where to write data
 * @param array $data The datas to write, formatted like this :
 * $data = array(array('Row 1, column 1', 'Row 1 column 2'),
 * 	array('Row 2, column 1', 'Row 2 column 2')
 * );
 * @param string $columnSeparator The column separator to use. Comma by default.
 * @return boolean TRUE if the file has been wrote. FALSE otherwise.
 */
function csv_write($filepath, $data, $colSep = ','){
	$lines = array();
	foreach ($data as $d) {
		$lines[] = implode($colSep, $d);
	}
	return file_put_contents($filepath, implode("\r\n", $lines));
}


/**
 * Load data from a CSV file
 * @param string $filepath The csv filepath to load
 * @param callable $callback An optional callback to use to browse each line. The function takes two parameters :
 * - the current line data
 * - the current line number
 * @param boolean $ignoreFirstLine Set it to TRUE if you want to ignore the first line, that generally give column information.
 * @param string $columnSeparator The column separator to use. Comma by default.
 * @return array Returns the CSV data.
 */
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