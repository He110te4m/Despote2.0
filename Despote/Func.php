<?php

/**
 * 获取配置文件内容
 * @param  String $name 配置项下标
 * @return Mixed        返回 $conf[$name] 的内容，如果不存在则返回 null
 */
function c($name)
{
    $conf = require PATH_CONFIG . 'Config.php';

    return isset($conf[$name]) ? $conf[$name] : null;
}

/**
 * 获取 GET 数组指定下标值
 * @param  String $name GET 数组下标
 * @return Mixed        返回 $_GET[$name] 的内容，如果不存在则返回 null
 */
function g($name)
{
    return isset($_GET[$name]) ? $_GET[$name] : null;
}

/**
 * 获取 POST 数组指定下标值
 * @param  String $name POST 数组下标
 * @return Mixed        返回 $_POST[$name] 的内容，如果不存在则返回 null
 */
function p($name)
{
    return isset($_GET[$name]) ? $_GET[$name] : null;
}
