<?php

function doc_from_file($filepath) {
	$fileContent = file_get_contents($filepath);
	$tokens = array();
	if( preg_match_all('#/\*(.*?)\*/#s', $fileContent, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER) ){
		foreach ($matches[1] as $m) {

			$token = array('summary' => '');

			//var_dump(substr($fileContent, $m[1] + strlen($m[0])));
			$nextMethodStr = substr($fileContent, $m[1]);
			$nextIndex = strpos($nextMethodStr, "/*");
			if( $nextIndex ){
				$nextMethodStr = substr($nextMethodStr, 0, $nextIndex);
			}
			if( preg_match('/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $nextMethodStr, $mFunc) ){
				$token['function'] = $mFunc[1];
			}
			if( preg_match('#([^\@]+)#', $m[0], $sum) ){	
				$sum = str_replace('*', '', $sum[1]);
				$token['summary'] = trim($sum);
			}
			if( preg_match_all('#\@([a-zA-Z]+)\s([^\@]*)#s', $m[0], $all) ){
				foreach ($all[1] as $key => $value) {
					$content = preg_replace('#\r\n\s+\*\s+#', "\r\n", $all[2][$key]);
					if( !isset($token[$value]) ) {
						$token[$value] = trim($content);
					}else{
						if( !is_array($token[$value]) ){
							$token[$value] = array($token[$value], trim($content));
						}else{
							$token[$value][] = trim($content);
						}
					}
				}
			}
			$tokens[] = $token;
		}
	}
	return $tokens;
}