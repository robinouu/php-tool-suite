<?php
require_once('var.inc.php');
require_once('Hook.class.php');

function hook_init()  {
}

function hook_register($name, $callback, $priority = 1, $persistent = false) {
    Hook::add($name, $callback, $priority);
}

function hook_do($name, $args = array()) {
    return Hook::call($name, $args);
};

function hook_unregister($name) {
    Hook::remove($name);
}

/*
function hook_unregister_all($prefix, $where = null) {
    foreach ($GLOBALS['proto-hooks'] as $key => $value) {
        if( is_callable($where) ){
            if( $where($prefix) ){
                unset($GLOBALS['proto-hooks'][$key]);
            }
        }elseif( substr($key, 0, strlen($prefix)) === $prefix ) {
            unset($GLOBALS['proto-hooks'][$key]);
        }
    }
}

function hook_unregister($name) {
    Hook::remove($name);
    //unset($GLOBALS['proto-hooks'][$name]);
}

function hook_register($name, $callback, $priority = 1) {
    Hook::add($name, $callback, $priority);
}

function hook_do($name, $args = array()) {
    Hook::call($name, $args);
    return;
    $back = null;
    if( !is_array($args) ) {
        $args = array($args);
    }
    print $name . '<br />';
    if( isset($GLOBALS['proto-hooks'][$name]) ){
        array_multisort($GLOBALS['proto-hooks-priority'], SORT_ASC);
        foreach ($GLOBALS['proto-hooks-priority'] as $key => $test) {

            $test = array_keys($test);

            $value = $GLOBALS['proto-hooks'][$key];
            if( is_callable($value) ){

                $ret = call_user_func_array($value, $args);


                if( is_string($ret) && is_string($back)){
                    $back .= $ret;
                }elseif ( is_array($ret) && is_array($ret) ) {
                    $back = array_merge_recursive($back, $ret);
                }elseif( is_numeric($ret) && is_numeric($ret) ){
                    $back += $ret;
                }
            }
        }
    }
    return $back;
}

*/