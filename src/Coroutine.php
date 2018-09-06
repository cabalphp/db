<?php
namespace Cabal\DB;

class Coroutine
{
    static public function callUserFuncArray($callable, $params = [])
    {
        if (version_compare(phpversion('swoole'), '4.0.0', '<')) {
            return \Swoole\Coroutine::call_user_func_array($callable, $params);
        } else {
            return call_user_func_array($callable, $params);
        }
    }
}