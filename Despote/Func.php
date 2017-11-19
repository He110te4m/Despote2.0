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
    return isset($_POST[$name]) ? $_POST[$name] : null;
}

/**
 * 创建多级目录
 * @param $path string 目标路径
 * @param $mode int 权限
 * @return bool 是否成功
 * @author yuri2
 * */
function createDir($path, $mode = 0775)
{
    if (is_dir($path)) {
        //判断目录存在否，存在不创建
        return true;
    } else {
        //不存在则创建
        // 第三个参数为true即可以创建多极目录
        $re = @mkdir($path, $mode, true);

        return $re;
    }
}
