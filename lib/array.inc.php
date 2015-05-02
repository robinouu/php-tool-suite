<?php
/**
 * Arrays
 * @package php-tool-suite
 * @subpackage arrays
 */

/**
 * Checks if the specified array is simple or not. (one level)
 * @param array $array The array to test
 * @return boolean TRUE if the array is simple. FALSE otherwise.
 */
function is_simple_array($arr = array()) {
	return (is_array($arr) && count($arr) == count($arr, COUNT_RECURSIVE));
}


/**
 * Checks if the specified array is associative or not.
 * @param array $array The array to test
 * @return boolean TRUE if the array is associative. FALSE otherwise.
 */
function is_assoc_array($arr) {
	return (bool)count(array_filter(array_keys($arr), 'is_string'));
}


/**
 * Gets an array value by path
 * @param array $array The reference array.
 * @param string|array $path A path to the key location. Can be a string like 'foo/bar', or an array('foo', 'bar')
 * @return mixed Return the current path value.
 */
function array_get($arr, $path)
{
	if (!$path)
		return $arr;

	$segments = is_array($path) ? $path : explode('/', $path);

	$cur =& $arr;

	foreach ($segments as $segment) {
		if (!isset($cur[$segment])){
			return null;
		}
		$cur = $cur[$segment];
	}

	return $cur;
}

/**
 * Sets an array value by path
 * @param array $array The reference array.
 * @param string|array $path A path to the key location. Can be a string like 'foo/bar', or an array('foo', 'bar')
 * @param mixed $value The value to set
 * @return boolean TRUE if the variable has been correctly set. FALSE otherwise.
 */
function array_set(&$arr, $path, $value)
{
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $arr;
	foreach ($segments as $segment) {
		if( !is_array($cur) ){
			$cur = array($segment => array());
		}
		if( !isset($cur[$segment]) ){
			$cur[$segment] = array();
		}
		$cur =& $cur[$segment];
	}
	$cur = $value;
	return true;
}


/**
 * Appends a value to a subarray by path
 * @param array $array The reference array.
 * @param string|array $path A path to the key location. Can be a string like 'foo/bar', or an array('foo', 'bar')
 * @param mixed $value The value to inject
 * @return boolean TRUE if the variable has been correctly set. FALSE otherwise.
 */
function array_append(&$arr, $path, $value = null)
{
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur = &$arr;
	$i = 0;

	foreach ($segments as $segment) {
		if( is_null($cur) ){
			$cur = array($segment => array());
		}elseif( !is_array($cur) ){
			return false;
		}
		$cur =& $cur[$segment];
		++$i;
	}

	if( is_array($cur) ){
		$cur[] = $value;
		return true;
	}
	return false;
}

/**
 * Unsets a value from array by path
 * @param array $array The reference array.
 * @param string|array $path A path to the key location. Can be a string like 'foo/bar', or an array('foo', 'bar')
 * @return boolean TRUE if the variable has been correctly unset. FALSE otherwise.
 */
function array_unset(&$arr, $path)
{
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $arr;

	$i = 0;
	$size = sizeof($segments);
	foreach ($segments as $segment) {
		if( !isset($cur[$segment]) ){
			return FALSE;
		}
		if( $i == $size - 1) {
			unset($cur[$segment]);
			return FALSE;
		}
		$cur =& $cur[$segment];
		++$i;
	}
	
	return TRUE;
}

function array_iunique($array) {
    return array_intersect_key($array, array_unique(array_map('strtolower', $array)));
}

function array_find_deep(array $array, $string, array &$result) {
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			$success = array_find_deep($value, $string, $result);
			if ($success) {
				array_unshift($result, $key);
				return true;
			}
		} else {
			if (strcmp($string, $value) == 0) {
				array_unshift($result, $key);
				return true;
			}
		}
	}
	return false;
}
function array_merge_recursive_unique($array1, $array2) {   
	foreach($array2 AS $k => $v) {
		if(!isset($array1[$k]))
		{
			$array1[$k] = $v;
		}
		else
		{
			if(!is_array($v)){
				if(is_array($array1[$k])) {
					if(!in_array($v,$array1[$k]))
					{
						$array1[$k][] = $v;
					}
				}
				else
				{
					if($array1[$k] != $v)
						$array1[$k] = array($array1[$k], $v);
				}
			}
			else
			{
				$array1[$k] =    array_merge_recursive_unique($array1,$array2[$k]);
			}
			
		}
	
	}
	unset($k, $v);
	return $array1;
}

