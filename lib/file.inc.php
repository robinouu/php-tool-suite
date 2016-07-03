<?php
/**
 * You can write CSV, INI or JSON data files with these methods :
 * 
 * @package php-tool-suite
 * @subpackage File operations
 */

/**
 * Loads an INI configuration from file
 * @param string $filename 
 * @return The parsed INI file.
 */
function ini_load($filename){
	return parse_ini_file($filename, true);
}

/**
 * Saves an INI configuration in file.
 * Supports for sections. 
 * @param string $filename 
 * @param array $data 
 * @return TRUE if the file has been written to the disk, FALSE otherwise.
 */
function ini_save($filename, $data=array()){
    $res = array();
    foreach($data as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
	return file_put_contents($file, implode("\r\n", $res));
}

/**
 * Loads a JSON object from file
 * @param string $filename The JSON file path to load
 * @return array The JSON object
 * @subpackage File operations
 */
function json_load($filename){
	$json = json_decode(file_get_contents($filename), true);
	if( !$json || !is_array($json) ){
		return array();
	}
	return $json;
}

/**
 * Saves a JSON object in a file
 * @param string $filename The JSON file to save into.
 * @param array $data The data to save. Must be an array.
 * @return TRUE if saved correctly, FALSE otherwise.
 * @subpackage File operations
 */
function json_save($filename, $data, $beautify=false){
	if( $beautify )
		return file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) !== FALSE;
	return file_put_contents($filename, json_encode($data)) !== FALSE;
}

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
 * @subpackage File operations
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
 * @subpackage File operations
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