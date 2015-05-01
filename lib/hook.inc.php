<?php
/**
 * Hooks
 * @package php-tool-suite
 * @subpackage Hooks
 */

plugin_require(array('var'));

/**
 * Registers a callback for a specified hook.
 * @param string $name The hook name.
 * @param callable $callback A callback that will be executed on hook_do call. 
 * @param int $priority An optional priority value of execution.
 */
function hook_register($name, $callback, $priority = 1) {
	if( !var_get('hooks/' . $name) ){
		var_set('hooks/' . $name, array());
	}
	var_append('hooks/' . $name, $priority, array(object_hash($callback) => $callback));
}

function hook_get($name = null, $priority = null){
	$hooks = array();

	// return all hooks for all hook names
	if( is_null($name) ){
		$hooks = array_values(var_get('hooks', array()));
		return $hooks;
	}

	if( !($hook = var_get('hooks/' . $name)) ){
		return array();
	}

	// return all hooks for a given name
	if( is_null($priority) ){
		ksort($hook);
		foreach ($hook as &$callbacks) {
			foreach ($callbacks as $callback) {
				$hooks[] = $callback;
			}
		}
		return $hooks;
	}

	// return only hooks for given name and priority
	if( !isset($hook[$priority]) ) {
		return array();
	}
	return array_values($hook[$priority]);
}

/**
 * Executes a hook.
 * Callbacks are sorted by ascendant priority, and stacked by calling order.
 * @param string $name The hook name.
 * @param mixed $args Arbitrary arguments that will be passed to each callback
 * @return mixed Hook result values are merged like this :
 * <ul>
 * 	<li>Strings are concatenated each other.</li>
 * 	<li>Numeric values are added each other.</li>
 *  <li>Boolean values use the AND logical operation.</li>
 *  <li>Arrays use recursive merging</li>
 * </ul>
 */
function hook_do($name, $args = array()) {
	if( !($hook = var_get('hooks/' . $name)) ){
		return NULL;
	}
		
	$args = array($args);

	$backValue = null;
	foreach (hook_get($name) as $callback) {
		// Merge back args
		$value = call_user_func_array($callback, $args);
		if( is_array($value) ){
			$backValue = is_null($backValue) ? $value : (is_array($backValue) ? array_merge($backValue, $value) : array_merge_recursive(array($backValue), $value));
		}elseif( is_string($value) ){
			$backValue = (string)$backValue . $value;
		}elseif( is_integer($value) ){
			$backValue += (int)$backValue + $value;
		}elseif( is_float($value) ){
			$backValue += (float)$backValue + $value;
		}elseif( is_double($value) ){
			$backValue += (double)$backValue + $value;
		}elseif( is_bool($value) ){
			$backValue = !is_bool($backValue) ? $value : (bool)$backValue && $value;
		}
	}
	return $backValue;
}


/**
 * Unregisters a hook and the associated callbacks.
 * @param string $name The hook name.
 */
function hook_unregister($name = null, $callback = null, $priority = null) {
	//Hook::remove($name);
	if( is_null($name) ){
		var_set('hooks', array());
		return;
	}

	if( !($hook = var_get('hooks/' . $name)) ){
		return;
	}

	if( is_null($callback) ){
		// remove all callbacks for this hook name
		if( is_null($priority) ){
			var_unset('hooks/' . $name);
			return;
		}
		// remove all callbacks for this hook name for specific priority
		unset($hook[$priority]);
		return;
	}

	$id = object_hash($callback);

	// remove all callbacks for specific hook at specific priority level
	if( !is_null($callback) && !is_null($priority) ) {
		unset($hook[$priority][$id]);
		return;
	}

	// remove all callbacks for specific hook at all priority levels
	foreach ($hook as $priority => &$callbacks) {
		unset($callbacks[$id]);
	}
}