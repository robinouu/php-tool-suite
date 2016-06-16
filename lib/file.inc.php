<?php
/**
 * File
 * @package php-tool-suite
 * @subpackage files
 */

/**
 * Writes a CSV file using array data
 * @param string $filepath The csv filepath where to write data
 * @param array $data The datas to write, formatted like this :
 * <pre>
 * 	<code>
 *  	$data = array(
 * 			array('Row 1, column 1', 'Row 1 column 2'),
 * 			array('Row 2, column 1', 'Row 2 column 2')
 * 		);
 * 	</code>
 * </pre>
 * @param string $columnSeparator The column separator to use. Comma by default.
 * @return boolean TRUE if the file has been wrote. FALSE otherwise.
 */
function csv_write($filepath, $data, $colSep = ','){
	$lines = array();
	foreach ($data as $d) {
		if( is_array($d) ){
			$lines[] = implode($colSep, $d);
		}else{
			$lines[] = (string)$d;
		}
	}
	return file_put_contents($filepath, implode("\r\n", $lines));
}


/**
 * Loads data from a CSV file
 * @param string $filepath The csv filepath to load
 * @param callable $callback An optional callback to use to browse each line. The function takes two parameters :
 * <ol>
 * 	<li>the current line data</li>
 * 	<li>the current line number</li>
 * </ol>
 * @param string $columnSeparator The column separator to use. Comma by default.
 * @return array Returns the CSV data.
 */
function csv_load($filepath, $callback = null, $columnSeparator = ","){
	$opened = false;
	if (($handle = fopen($filepath, "r")) !== FALSE) {
		$opened = true;
		$l = 0;
		while (($data = fgetcsv($handle, 0, $columnSeparator)) !== FALSE) {
			if( is_callable($callback) ){
				$callback($data, $l);
			}elseif (is_string($callback) ){
				call_user_func_array($callback, array($data, $l));
			}
			++$l;
		}
		fclose($handle);
	}
	if( !$opened ){
		return false;
	}
	return true;
}