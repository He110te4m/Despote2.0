<?php
/**
 * 核心框架类，完成框架的加载
 * @author  He110 (i@he110.top)
 * @date    2017-07-16 08:34:51
 * @version 1.0
 */
namespace Despote;

class App
{
    public function run()
    {
        // 设置 head 头
        header('Content-type: text/html; charset=UTF-8;');
        // 开启自动加载函数
        $this->setAutoload();
        // 自定义错误处理
        $this->debug();
        // 设置路由解析
        $this->setRoute();
        // 根据 DEBUG 的值判断是否加载 Debug 类
        !DEBUG || \Despote\Base\Debug::display();
    }

    private function setAutoload()
    {
        require PATH_BASE . 'Autoload.php';
        $autoloadObj = new \Despote\Base\Autoload();
        $autoloadObj->register();
    }

    private function debug()
    {
        ini_set('display_errors', 'Off');
        error_reporting(0);
        if (ERROR_CATCH) {
            \Despote\Base\ErrCatch::register();
        }
    }

    private function setRoute()
    {
        $routeObj = new \Despote\Base\Route();
        $routeObj->parse();
    }
}
