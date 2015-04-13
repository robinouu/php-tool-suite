<?php
/**
 * Hooks
 * @package php-tool-suite
 * @subpackage Hooks
 */
require_once('var.inc.php');
require_once('Hook.class.php');

/**
 * Registers a callback for a specified hook.
 * @param string $name The hook name.
 * @param callable $callback A callback that will be executed on hook_do call. 
 * @param int $priority An optional priority value of execution.
 */
function hook_register($name, $callback, $priority = 1) {
    Hook::add($name, $callback, $priority);
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
    return Hook::call($name, $args);
};


/**
 * Unregisters a hook and the associated callbacks.
 * @param string $name The hook name.
 */
function hook_unregister($name) {
    Hook::remove($name);
}