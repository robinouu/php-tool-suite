<?php

function is_simple_array($arr = array()) {
	return (is_array($arr) && count($arr) == count($arr, COUNT_RECURSIVE));
}

function is_assoc_array($arr) {
    return (bool)count(array_filter(array_keys($arr), 'is_string'));
}

function array_get($arr, $path)
{
	if (!$path)
		return null;
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
			if( !is_array($cur) ){
				$cur = array();
			}
			$cur[$segment] = array();
		}
		$cur =& $cur[$segment];
	}
	$cur = $value;
	return true;
}

function array_append(&$arr, $path, $value)
{
	if (!$path)
		return null;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $arr;

	foreach ($segments as $segment) {
		if (!isset($cur[$segment]))
			$cur[$segment] = array();
		$cur =& $cur[$segment];
	}

	if( !is_array($cur) ){
		$cur = array($value);
	}else{
		$cur[] = $value;
	}
	return true;
}

function array_unset(&$arr, $path)
{
	if (!$path)
		return false;

	$segments = is_array($path) ? $path : explode('/', $path);
	$cur =& $arr;

	foreach ($segments as $segment) {
		$cur =& $cur[$segment];
	}
	unset($cur);
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