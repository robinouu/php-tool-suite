<?php
/**
 * Arrays
 * @package php-tool-suite
 */
function is_simple_array($arr = array()) {
	return (is_array($arr) && count($arr) == count($arr, COUNT_RECURSIVE));
}

function is_assoc_array($arr) {
    return (bool)count(array_filter(array_keys($arr), 'is_string'));
}

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

function array_set(&$arr, $path, $value)
{
	if (!$path)
		return null;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $arr;

	foreach ($segments as $segment) {
		if (!isset($cur[$segment])){
			if( !is_array($cur) )	{
				$cur = array();
			}
			$cur[$segment] = array();
		}
		$cur =& $cur[$segment];
	}
	$cur = $value;
	return true;
}

function array_append(&$arr, $path, $key, $value = null)
{
	if (!$path)
		return null;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur = &$arr;
	$i = 0;
	foreach ($segments as $segment) {
		if( !is_array($cur[$segment]) ) {
			$cur[$segment] = array();
		}
		if( $i == sizeof($segments) - 1){
			if( is_null($value) ){
				$cur[$segment][] = $key;
			}else{
				$cur[$segment][$key] = $value;
			}
		}
		$cur =& $cur[$segment];
		++$i;
	}
}

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
			return $arr;
		}
		if( $i == $size - 1) {
			unset($cur[$segment]);
			return $arr;
		}
		$cur =& $cur[$segment];
		++$i;
	}
	
	return $arr;
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