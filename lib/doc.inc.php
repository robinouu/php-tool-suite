<?php

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

function doc_from_file($filepath) {
	$fileContent = file_get_contents($filepath);
	$tokens = array();
	if( preg_match_all('#/\*\*(.*?)\*/#s', $fileContent, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER) ){
		foreach ($matches[1] as $key =>$m) {

			$token = array('summary' => '');

			$nextMethodStr = substr($fileContent, $key ? $m[1] : null);
			//var_dump($nextMethodStr);
			$nextIndex = strpos($nextMethodStr, "/*");
			if( $nextIndex ){
				$nextMethodStr = substr($nextMethodStr, 0, $nextIndex);
			}
			if( preg_match('/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $nextMethodStr, $mFunc) ){
				$token['function'] = $mFunc[1];
			}
			if( preg_match('/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $nextMethodStr, $mFunc) ){
				$token['class'] = $mFunc[1];
			}
			if( preg_match('#([^\@]+)#', $m[0], $sum) ){	
				$sum = str_replace('*', '', $sum[1]);
				$token['summary'] = trim($sum);
			}
			if( preg_match_all('#\@([a-zA-Z]+)(.+?(?=(\*\s+\@)|\\r\\n))#s', $m[0], $all) ){

				foreach ($all[1] as $key => $value) {
					$content = preg_replace('#\r\n\s+\*\s+#', "\r\n", $all[2][$key]);
					if( !isset($token[$value]) ) {
						$token[$value] = array(trim($content));
					}else{
						$token[$value][] = trim($content);
					}
				}
			}
			$tokens[] = $token;
		}
	}
	return $tokens;
}