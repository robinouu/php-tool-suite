<?php

require_once(dirname(__FILE__).'/log.inc.php');

$GLOBALS['proto_partials'] = array('library' => array());
//var_dump($GLOBALS);
class Partial {
	
	public $tplArgs = array();

	private $name = '';
	private $tpl = null;

	static public function get($name) {
		return clone $GLOBALS['proto_partials']['library'][$name];
	}

	public function __construct($name = null, $tpl = '', $tplArgs = array()) {
		$this->tpl = $tpl;
		$this->tplArgs = $tplArgs;

		if ($name) {
			$this->saveAs($name);
			$this->name = $name;
		}
	}

	public function getArgs() {
		return $this->tplArgs;
	}

	public function saveAs($name) {
		$GLOBALS['proto_partials']['library'][$name] = $this;
	}

	public function using($params = array()) {
		/*$this->tplArgs = array_merge($this->tplArgs, $params);
		
		if( !sizeof($this->tplArgs) ){
			$i = 0;
			if( is_string($params) ){
				$params = array($params);
			}
			if( is_array($params) ){
				$i = 0;
				if( preg_match_all('#\{([a-zA-Z_\-]+)\}#i', $content, $match)) {
					foreach ($match[1] as $k => $m) {
						$content = str_replace('{' . $m . '}', $params[$m], $content);
						++$i;
					}
				}
			}
		}
		else {
			// cas ou il existe des arguments
			$i = 0;
			$args = array_keys($this->tplArgs);
			//var_dump(array_keys($this->tplArgs));
			foreach ($args as $key => $arg) {
				var_dump($key, $arg);
				if( is_numeric($key) && isset($params[$key]) ){
					$content = str_replace('{' . $arg . '}', $params[$key], $content);
				}else if (is_string($key)) {
					$content = str_replace('{' . $arg . '}', $arg, $content);
				}
			}
		}
		return $content;
	}
*/
		$args = array_merge($this->tplArgs, $params);
		$content = $this->tpl;
		foreach ($args as $key => $value) {
			if( is_string($value) ){
				$content = str_replace('{' . $key . '}', $value, $content);
			}
		}
		return $content;
	}
}



