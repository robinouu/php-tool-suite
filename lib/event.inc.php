<?php
/**
 * Events
 * 
 * Triggering an event and merge event results :
 * 
 * ```php
 * on('event', function (){
 * 	return 'a';
 * }, 1);
 * on('event', function (){
 * 	return 'b';
 * ));
 * 
 * trigger('event'); // returns 'ba';
 * ```
 * 
 * The result of the trigger depends on the return types of the callbacks.
 * If the results are arrays, we merge each other.
 * If they are strings, we concatenate them
 * It they are booleans, we do AND operation on them
 * If they are numerics, we do ADD operation on them
 * 
 * @package php-tool-suite
 * @subpackage Events
 */

plugin_require(array('var'));

/**
 * Registers a callback for a specified event.
 * ```php
 * on('event', function (){
 * 	return 'a';
 * }, 1);
 * ```
 * You can also pass multiple variables to your callback.
 * 
 * @param string $name The event name.
 * @param callable $callback A callback that will be executed on event_do call. 
 * @param int $priority An optional priority value of execution.
 * @subpackage Events
 */
function on($name, $callback, $priority = 1) {
	$name = object_hash($name);
	$eventPath = 'events/' . $name . '/' . $priority;

	return var_set($eventPath . '/' . object_hash($callback), $callback);
}

function event_get($name = null, $priority = null){
	$name = object_hash($name);
	$events = array();

	// return all events for all event names
	if( is_null($name) ){
		$events = array_values(var_get('events', array()));
		return $events;
	}

	if( !($event = var_get('events/' . $name)) ){
		return array();
	}

	// return all events for a given name
	if( is_null($priority) ){
		ksort($event);
		foreach ($event as &$callbacks) {
			foreach ($callbacks as $callback) {
				$events[] = $callback;
			}
		}
		return $events;
	}

	// return only events for given name and priority
	if( !isset($event[$priority]) ) {
		return array();
	}
	return array_values($event[$priority]);
}

/**
 * Executes a event.
 * 
 * ```php
 * on('error', function ($err) { print '[Error] ' . $err; die; })
 * trigger('error', 'Division by zero.');
 * ```
 * 
 * Callbacks are sorted by ascendant priority (from -PHP_INT_MAX to PHP_INT_MAX), and stacked by calling order.
 * 
 * @param string $name The event name.
 * @param mixed $args Arbitrary arguments that will be passed to each callback
 * @return mixed Hook result values are merged like this :
 * <ul>
 * 	<li>Strings are concatenated each other.</li>
 * 	<li>Numeric values are added each other.</li>
 *  <li>Boolean values use the AND logical operation.</li>
 *  <li>Arrays use recursive merging</li>
 * </ul>
 * @subpackage Events
 */
function trigger($name, $args = null) {
	$realName = $name;
	$name = object_hash($name);

	if( !($event = var_get('events/' . $name)) ){
		return $args;
	}

	$backValue = $args;	
	$args = array($args);
	
	$events = event_get($realName);

	foreach ($events as &$callback) {
		// Merge back args
		$value = call_user_func_array($callback, $args);
		if( is_array($value) ){
			$backValue = is_null($backValue) ? $value : (is_array($backValue) ? array_merge($backValue, $value) : array_merge_recursive(array($backValue), $value));
		}elseif( is_string($value) ){
			$backValue = (is_string($backValue) ? $backValue : '') . $value;
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
 * Unregisters an event or a specific callback.
 * 
 * ```php
 * on('error', $errFunc = function () { }, 1);
 * 
 * off('error', null, 1);
 * off('error', $errorFunc);
 * off('error');
 * 
 * ```
 * 
 * You can unregister callbacks by priority, or by a specific callback, or by name.
 * 
 * @param string $name The event name.
 * @param callable $callback A callback that will be unregistered. 
 * @param int $priority An optional priority value of execution to search for.
 * @subpackage Events
 */
function off($name = null, $callback = null, $priority = null) {
	$name = object_hash($name);

	//Hook::remove($name);
	if( is_null($name) ){
		var_set('events', array());
		return;
	}

	if( !($event = var_get('events/' . $name)) ){
		return;
	}

	if( is_null($callback) ){
		// remove all callbacks for this event name
		if( is_null($priority) ){
			var_unset('events/' . $name);
			return;
		}
		// remove all callbacks for this event name for specific priority
		unset($event[$priority]);
		return;
	}

	$id = object_hash($callback);

	// remove all callbacks for specific event at specific priority level
	if( !is_null($callback) && !is_null($priority) ) {
		unset($event[$priority][$id]);
		return;
	}

	// remove all callbacks for specific event at all priority levels
	foreach ($event as $priority => &$callbacks) {
		unset($callbacks[$id]);
	}
}