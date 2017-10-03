<?php
/**
 * 路由解析类，用于路由解析，使用 PATHINFO 的方式解析
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote\Base;

class Route
{
    public function parse()
    {
        // 获取全局配置
        global $config;

        // 获取 URL 中的 pathinfo
        $pathInfo = !empty($_GET['r']) ? explode('/', $_GET['r']) : [];
        // 获取控制器名称，如果不存在则使用默认值，默认值可以在 Config/Config.php 中修改
        $className  = !empty($pathInfo[0]) ? $pathInfo[0] : $config['default']['controller'];
        // 获取控制器方法，如果不存在则使用默认值，默认值可以在 Config/Config.php 中修改
        $methodName = !empty($pathInfo[1]) ? $pathInfo[1] : $config['default']['methon'];
        // 拼接控制器
        $controller = 'App\Controller\\' . $className;
        // 实例化控制器
        $obj        = new $controller();
        // 加载方法
        $obj->$methodName();
    }
}
