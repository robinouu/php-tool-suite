<?php
/**
 * Hooks
 * @package php-tool-suite
 */
require_once('var.inc.php');
require_once('Hook.class.php');

function hook_register($name, $callback, $priority = 1) {
    Hook::add($name, $callback, $priority);
}

function hook_do($name, $args = array()) {
    return Hook::call($name, $args);
};

function hook_unregister($name) {
    Hook::remove($name);
}