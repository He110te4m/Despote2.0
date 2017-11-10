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

        // 获取数据
        $pathInfo   = !empty($_GET['r']) ? explode('/', $_GET['r']) : [];
        $className  = array_shift($pathInfo);
        $methodName = array_shift($pathInfo);

        // 数据校验
        is_null($className) && $className   = $config['default']['controller'];
        is_null($methodName) && $methodName = $config['default']['methon'];

        // 拼接控制器
        $controller = 'App\Controller\\' . $className;
        // 实例化控制器
        $obj = new $controller();
        // 加载方法
        if (method_exists($obj, $methodName)) {
            call_user_func_array([$obj, $methodName], $pathInfo);
        } else {
            header('location: /404.html');
        }
    }
}
