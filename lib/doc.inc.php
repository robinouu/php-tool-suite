<?php
/**
 * This package can manage the process of generating a PHP documentation from phpDoc syntax convention.
 * 
 * So, you can create a minimal documentation from this code :
 * ```php
 * $doc = doc_from_dir("src");
 *	foreach( $doc as $element ){
 *  	if( isset($element['summary']) ){
 *		print $element['summary'] . br();
 *	}
 * }
 * ```
 * 
 * It parses the code into array('param' => 'value') arrays of tokens ($doc).
 * After that, you browse the documentation tokens and display their summary.
 * 
 * There are plenty of tags (@package, @subpackage, @param, @return, @see that can be parsed, basically everything with a @ in front of it)
 * 
 * @package php-tool-suite
 * @subpackage Documentation
 */

/**
 * Returns the tokens generated from a directory
 * @param $dirpath string The path to explore.s
 * @param $exclude string An optional array of paths to ignore.
 * @return array The documentation tokens. 
 * @subpackage Documentation
 */
function doc_from_dir($dirpath, $exclude = array()) {
	$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirpath));
	$doc = array();
	foreach ($exclude as &$p) {
		$p = str_replace('\\', '/', $p);
	}
	foreach( $directory_iterator as $filepath => $path_object )
	{
		$filepath = str_replace('\\', '/', $filepath);
		$excluded = false;

		foreach ($exclude as $path) {
			if( substr($filepath, 0, strlen($path)) === $path){
				$excluded = true;
				break;
			}

		}
		if( $excluded ){
			continue;
		}

		$info = pathinfo($filepath);
		if( $info['basename'] !== '.' && $info['basename'] !== '..' ){
			if( in_array($info['extension'], array('php')) ){
				$doc = array_merge($doc, doc_from_file($filepath));
			}
		}
	}

	return $doc;
}


/**
 * Returns the tokens generated from a file
 * @param $filepath string The filepath.
 * @return array The documentation tokens.
 * @subpackage Documentation
 */
function doc_from_file($filepath) {
	$fileContent = file_get_contents($filepath);
	$tokens = array();
	if( preg_match_all('#(/\*\*.*\*\/)#Us', $fileContent, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER) ){
		foreach ($matches[1] as $key =>$m) {

			$token = array('summary' => '');
			if( !$key ){
				$token['file'] = basename($filepath);
			}

			$nextMethodStr = substr($fileContent, strlen($m[0]) + $m[1]);
			
			if( preg_match('/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $nextMethodStr, $mFunc) ){
				$token['function'] = $mFunc[1];
			}
			if( preg_match('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $nextMethodStr, $mFunc) ){
				$token['class'] = $mFunc[1];
			}
			
			//var_dump($m[0]);
			$com = strpos($m[0], "/**");
			if( preg_match_all('#\*\s(.*)#', substr($m[0], $com + 3), $sum) ){	
				
				$hasTag = false;
				$buf = '';
				$value = null;
				foreach ($sum[1] as $c) {
					$c = trim($c);
					if( preg_match('#^\@([a-zA-Z]+)(.*)#', $c, $matchParam) ){
						$hasTag = true;
						$value = $matchParam[1];
						$content = trim($matchParam[2]);
						if( !isset($token[$value]) ) {
							$token[$value] = array($content);
						}else{
							$token[$value][] = $content;
						}
					}else{
						if( !$hasTag ){
							$token['summary'] .= $c . PHP_EOL;
						}elseif( $value ){
							if( ($index = sizeof($token[$value])-1) >= 0 ){
								$token[$value][$index] .= PHP_EOL . $c;
							}else{
								$token[$value] = array($c);
							}
						}
					}
				}
			}
			
//			$token['summary'] = trim($token['summary'], "\r\n");
			$tokens[] = $token;
		}
	}
	return $tokens;
}